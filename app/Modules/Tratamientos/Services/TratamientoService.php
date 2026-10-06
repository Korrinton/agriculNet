<?php

namespace App\Modules\Tratamientos\Services;

use App\Modules\Alertas\Models\Alerta;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\Costes\Models\CategoriaCoste;
use App\Modules\Costes\Models\Coste;
use App\Modules\Tratamientos\Events\TratamientoRegistrado;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Parcela;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TratamientoService
{
    public const CATEGORIA_COSTE = 'Productos fitosanitarios';

    /** Una observación fenológica sirve para proponer el estadio BBCH de un tratamiento durante este tiempo. */
    public const DIAS_VALIDEZ_OBSERVACION = 21;

    public function registrar(Parcela $parcela, User $user, array $data): Tratamiento
    {
        return $this->registrarEnParcelas(collect([$parcela]), $user, $data)->first();
    }

    /**
     * Una misma aplicación en varias parcelas (normalmente la finca entera). El cuaderno de
     * explotación lleva el registro por parcela, así que se crea un tratamiento en cada una;
     * si son varias, sobre toda su superficie.
     *
     * Con precio, se recuerda como el del usuario para ese producto y cada tratamiento genera
     * su coste en la categoría de productos fitosanitarios.
     *
     * @param  Collection<int, Parcela>  $parcelas
     * @return Collection<int, Tratamiento>
     */
    public function registrarEnParcelas(Collection $parcelas, User $user, array $data): Collection
    {
        if ($parcelas->count() > 1) {
            $data['superficie_tratada_ha'] = null;
        }

        $producto = ProductoFitosanitario::findOrFail($data['producto_id']);
        // La dosis va en la unidad del producto; se guarda por si después se reclasifica
        $data['unidad'] = $producto->unidad;

        $tratamientos = DB::transaction(function () use ($parcelas, $user, $data, $producto) {
            if (array_key_exists('precio_unitario', $data) && $data['precio_unitario'] !== null) {
                $producto->fijarPrecio($user, $data['precio_unitario']);
            }

            return $parcelas->map(function (Parcela $parcela) use ($user, $data, $producto) {
                // Sin plazo anotado, el oficial de la ficha para el cultivo de la parcela
                if (($data['plazo_seguridad_dias'] ?? null) === null) {
                    $oficial = $producto->plazoOficialPara(ImportadorFitosanitarios::cultivoRegistroDe($parcela));
                    if ($oficial !== null) {
                        $data['plazo_seguridad_dias'] = $oficial;
                    }
                }

                // Cultivo (código EPPO) y estadio de cada parcela: con varias parcelas pueden ser distintos
                $tratamiento = Tratamiento::create(array_merge($data, [
                    'parcela_id'   => $parcela->id,
                    'user_id'      => $user->id,
                    'cultivo_eppo' => ImportadorFitosanitarios::codigoEppoDe($parcela),
                    'bbch'         => ($data['bbch'] ?? null) ?: self::bbchObservado($parcela, Carbon::parse($data['fecha'])),
                ]));
                $tratamiento->setRelation('parcela', $parcela);
                $this->registrarCoste($tratamiento);

                return $tratamiento;
            });
        });

        $tratamientos->each(fn (Tratamiento $t) => TratamientoRegistrado::dispatch($t));

        return $tratamientos;
    }

    /**
     * Corrige un tratamiento: se recalculan su coste y sus alertas de dosis y plazo de seguridad
     * (las alertas tienen clave por tratamiento, así que se borran para que se generen con los datos nuevos).
     */
    public function actualizar(Tratamiento $tratamiento, User $user, array $data): Tratamiento
    {
        $producto = ProductoFitosanitario::findOrFail($data['producto_id']);
        if ((int) $data['producto_id'] !== $tratamiento->producto_id) {
            $data['unidad'] = $producto->unidad;
        }
        // Los tratamientos anteriores a guardar el cultivo lo reciben al corregirlos
        $data['cultivo_eppo'] = $tratamiento->cultivo_eppo ?? ImportadorFitosanitarios::codigoEppoDe($tratamiento->parcela);

        DB::transaction(function () use ($tratamiento, $user, $data, $producto) {
            if (array_key_exists('precio_unitario', $data) && $data['precio_unitario'] !== null) {
                $producto->fijarPrecio($user, $data['precio_unitario']);
            }

            $tratamiento->update($data);
            $tratamiento->unsetRelation('producto');

            $tratamiento->coste()->delete();
            $this->registrarCoste($tratamiento);

            Alerta::where('user_id', $tratamiento->user_id)
                ->whereIn('clave', ["dosis_excedida:{$tratamiento->id}", "plazo_seguridad:{$tratamiento->id}", "fin_plazo:{$tratamiento->id}"])
                ->delete();
        });

        TratamientoRegistrado::dispatch($tratamiento);

        return $tratamiento;
    }

    /**
     * Estadio BBCH de la última observación fenológica de la parcela en las semanas anteriores
     * a la fecha, o null si no hay ninguna reciente.
     */
    public static function bbchObservado(Parcela $parcela, Carbon $fecha): ?string
    {
        return RegistroFenologico::where('parcela_id', $parcela->id)
            ->whereBetween('fecha_observacion', [$fecha->copy()->subDays(self::DIAS_VALIDEZ_OBSERVACION)->toDateString(), $fecha->toDateString()])
            ->join('estados_fenologicos', 'estados_fenologicos.id', '=', 'registro_fenologico.estado_fenologico_id')
            ->orderByDesc('fecha_observacion')->orderByDesc('registro_fenologico.id')
            ->value('estados_fenologicos.codigo_bbch');
    }

    private function registrarCoste(Tratamiento $tratamiento): void
    {
        $importe = $tratamiento->importeProducto();
        if (!$importe || $importe < 0.01) {
            return;
        }

        $categoria = CategoriaCoste::firstOrCreate(['nombre' => self::CATEGORIA_COSTE], ['tipo' => 'insumos']);
        $producto = $tratamiento->producto;
        $num = fn ($v, $d) => rtrim(rtrim(number_format((float) $v, $d, ',', '.'), '0'), ',');

        Coste::create([
            'parcela_id'     => $tratamiento->parcela_id,
            'categoria_id'   => $categoria->id,
            'tratamiento_id' => $tratamiento->id,
            'user_id'        => $tratamiento->user_id,
            'fecha'          => $tratamiento->fecha,
            'importe'        => $importe,
            'descripcion'    => mb_substr(sprintf(
                '%s: %s %s × %s ha × %s %s',
                $producto->nombre,
                $num($tratamiento->dosis_l_ha, 3),
                $tratamiento->unidadDosis(),
                $num($tratamiento->superficieTratadaHa(), 4),
                number_format((float) $tratamiento->precio_unitario, 2, ',', '.'),
                $tratamiento->unidadPrecio(),
            ), 0, 500),
        ]);
    }
}
