<?php

namespace App\Modules\Tratamientos\Services;

use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sincroniza el catálogo con el Registro Oficial de Productos Fitosanitarios del MAPA (REGFIWEB).
 *
 * La exportación JSON del registro trae nombre, nº de registro, titular, formulado y fechas, pero no
 * los usos: las dosis y plazos de seguridad dependen del cultivo y la plaga y están en la ficha PDF
 * de cada producto. Lo que sí permite es filtrar por cultivo, con lo que se marca para cuáles de los
 * cultivos de la app está autorizado cada producto.
 */
class ImportadorFitosanitarios
{
    public const URL_EXPORTACION = 'https://servicio.mapa.gob.es/regfiweb/Exportaciones/ExportJsonProductos';

    /**
     * Cultivo de la app → cultivos del registro (idCultivo). Se pide con ancestrosCultivos para
     * incluir los productos autorizados en cultivos más genéricos (p. ej. «VID» para vid de vinificación,
     * «CEREALES DE INVIERNO» para el trigo). Los herbáceos de secano se distinguen por su variedad.
     */
    public const CULTIVOS_REGISTRO = [
        'vid'       => [2014],       // VID DE VINIFICACIÓN
        'olivo'     => [1974, 1973], // OLIVO DE ALMAZARA, OLIVO DE VERDEO
        'pistacho'  => [2009],       // PISTACHERO
        'trigo'     => [2034],
        'cebada'    => [2035],
        'avena'     => [2036],
        'centeno'   => [2037],
        'triticale' => [2038],
        'veza'      => [2057],
        'guisante'  => [2061],       // GUISANTE PROTEAGINOSO / FORRAJERO
        'garbanzo'  => [2045],
        'lenteja'   => [2048],
        'yero'      => [2053],       // YEROS
        'girasol'   => [2067],
        'colza'     => [2069],
        'barbecho'  => [2244],       // BARBECHOS
    ];

    public const NOMBRES_CULTIVO = [
        'vid' => 'Vid', 'olivo' => 'Olivo', 'pistacho' => 'Pistacho', 'trigo' => 'Trigo', 'cebada' => 'Cebada',
        'avena' => 'Avena', 'centeno' => 'Centeno', 'triticale' => 'Triticale', 'veza' => 'Veza',
        'guisante' => 'Guisante proteaginoso', 'garbanzo' => 'Garbanzo', 'lenteja' => 'Lenteja', 'yero' => 'Yero',
        'girasol' => 'Girasol', 'colza' => 'Colza', 'barbecho' => 'Barbecho',
    ];

    /** Variedad de una parcela de secano (ver VariedadSeeder) → cultivo del registro. */
    private const HERBACEOS_POR_VARIEDAD = [
        'Trigo blando' => 'trigo', 'Trigo duro' => 'trigo', 'Cebada' => 'cebada', 'Avena' => 'avena',
        'Centeno' => 'centeno', 'Triticale' => 'triticale', 'Veza' => 'veza', 'Guisante proteaginoso' => 'guisante',
        'Garbanzo' => 'garbanzo', 'Lenteja' => 'lenteja', 'Yero' => 'yero', 'Girasol' => 'girasol',
        'Colza' => 'colza', 'Barbecho' => 'barbecho',
    ];

    /**
     * Código EPPO de cada cultivo, con el que el Reglamento (UE) 2023/564 pide identificar el
     * cultivo tratado (https://gd.eppo.int). El barbecho no es un cultivo: no tiene código.
     */
    public const CODIGOS_EPPO = [
        'vid' => 'VITVI', 'olivo' => 'OLVEU', 'pistacho' => 'PIAVE', 'trigo' => 'TRZAX', 'cebada' => 'HORVX',
        'avena' => 'AVESA', 'centeno' => 'SECCE', 'triticale' => 'TTLSS', 'veza' => 'VICSA', 'guisante' => 'PIBSX',
        'garbanzo' => 'CIEAR', 'lenteja' => 'LENCU', 'yero' => 'VICER', 'girasol' => 'HELAN', 'colza' => 'BRSNN',
    ];

    /** El trigo duro es otra especie (Triticum durum) que el blando. */
    private const EPPO_TRIGO_DURO = 'TRZDU';

    public static function codigoEppoDe(Parcela $parcela): ?string
    {
        $cultivo = self::cultivoRegistroDe($parcela);
        if ($cultivo === 'trigo' && $parcela->variedad?->nombre === 'Trigo duro') {
            return self::EPPO_TRIGO_DURO;
        }

        return self::CODIGOS_EPPO[$cultivo] ?? null;
    }

    /**
     * Cultivo de la parcela tal como lo clasifica el catálogo, o null si no se puede saber
     * (p. ej. secano sin variedad): entonces no se filtran los productos.
     */
    public static function cultivoRegistroDe(Parcela $parcela): ?string
    {
        $cultivo = Parcela::cultivoDeUso($parcela->uso);
        if ($cultivo === 'herbaceo') {
            return self::HERBACEOS_POR_VARIEDAD[$parcela->variedad?->nombre] ?? null;
        }

        return array_key_exists((string) $cultivo, self::CULTIVOS_REGISTRO) ? $cultivo : null;
    }

    private const ESTADO_VIGENTE = 1;

    /** Tipos de formulado líquidos (código FAO/CropLife entre corchetes en el formulado). */
    private const FORMULADOS_LIQUIDOS = [
        'SC', 'EC', 'SL', 'EW', 'OD', 'FS', 'CS', 'AL', 'SE', 'ME', 'ZC', 'UL', 'DC', 'HN', 'ES', 'CB', 'SD', 'HK', 'EO', 'OL', 'LS', 'SU',
    ];

    /**
     * Unidad en la que se dosifica y se compra el producto: «l» para los líquidos y «kg» para los
     * sólidos. Se deduce del tipo de formulado («AZUFRE 80% [WG] P/P») y, si no se reconoce, de cómo
     * se expresa la riqueza (P/V, peso/volumen, es de líquidos).
     */
    public static function unidadDeFormulado(?string $formulado): string
    {
        if (preg_match('/\[([A-Z]{2})\]/', (string) $formulado, $m)) {
            if (in_array($m[1], self::FORMULADOS_LIQUIDOS, true)) {
                return 'l';
            }
            if (!str_contains($formulado, 'P/V')) {
                return 'kg';
            }
        }

        return str_contains((string) $formulado, 'P/P') ? 'kg' : 'l';
    }

    /**
     * @return array{recibidos: int, nuevos: int, actualizados: int, cancelados: int}
     */
    public function importar(): array
    {
        $productos = $this->descargar();
        if (empty($productos)) {
            // Sin esta guarda, una respuesta vacía daría de baja todo el catálogo
            throw new RuntimeException('El Registro de Productos Fitosanitarios no devolvió ningún producto vigente.');
        }

        $cultivos = [];
        foreach (self::CULTIVOS_REGISTRO as $cultivo => $ids) {
            foreach ($ids as $idCultivo) {
                foreach ($this->descargar($idCultivo) as $p) {
                    $cultivos[$p['IdProducto']][$cultivo] = $cultivo;
                }
            }
        }

        $ahora = now();
        $filas = [];
        foreach ($productos as $p) {
            if (empty($p['IdProducto']) || blank($p['Nombre'] ?? null)) {
                continue;
            }
            $filas[$p['IdProducto']] = [
                'mapa_id'            => $p['IdProducto'],
                'user_id'            => null,
                'nombre'             => mb_substr(trim($p['Nombre']), 0, 255),
                'numero_registro'    => $this->texto($p['NumRegistro'] ?? null, 50),
                'ingrediente_activo' => $this->texto($p['Formulado'] ?? null, 255),
                'unidad'             => self::unidadDeFormulado($p['Formulado'] ?? null),
                'titular'            => $this->texto($p['Titular'] ?? null, 255),
                'vigente'            => true,
                'fecha_caducidad'    => isset($p['FechaCaducidad']) ? substr($p['FechaCaducidad'], 0, 10) : null,
                'cultivos'           => json_encode(array_values($cultivos[$p['IdProducto']] ?? [])),
                'created_at'         => $ahora,
                'updated_at'         => $ahora,
            ];
        }

        return DB::transaction(function () use ($filas) {
            $this->vincularProductosAnteriores($filas);

            $existentes = ProductoFitosanitario::whereIn('mapa_id', array_keys($filas))->count();

            foreach (array_chunk($filas, 500) as $lote) {
                ProductoFitosanitario::upsert($lote, ['mapa_id'], [
                    'nombre', 'numero_registro', 'ingrediente_activo', 'unidad', 'titular',
                    'vigente', 'fecha_caducidad', 'cultivos', 'updated_at',
                ]);
            }

            // Los que ya no están entre los vigentes se conservan (los referencian tratamientos) como cancelados
            $cancelados = ProductoFitosanitario::whereNotNull('mapa_id')
                ->whereNotIn('mapa_id', array_keys($filas))
                ->where('vigente', true)
                ->update(['vigente' => false, 'updated_at' => now()]);

            return [
                'recibidos'    => count($filas),
                'nuevos'       => count($filas) - $existentes,
                'actualizados' => $existentes,
                'cancelados'   => $cancelados,
            ];
        });
    }

    /**
     * Productos del registro dados de alta a mano antes de existir el importador: se enlazan por
     * nº de registro para no duplicarlos y conservar sus tratamientos, dosis y plazos.
     */
    private function vincularProductosAnteriores(array $filas): void
    {
        $porRegistro = collect($filas)->filter(fn ($f) => $f['numero_registro'])->pluck('mapa_id', 'numero_registro');

        ProductoFitosanitario::whereNull('user_id')->whereNull('mapa_id')
            ->whereIn('numero_registro', $porRegistro->keys())
            ->get()
            ->each(fn ($producto) => $producto->update(['mapa_id' => $porRegistro[$producto->numero_registro]]));
    }

    /** @return list<array<string, mixed>> */
    private function descargar(?int $idCultivo = null): array
    {
        $filtro = ['idEstado' => self::ESTADO_VIGENTE];
        if ($idCultivo) {
            $filtro += ['idCultivo' => $idCultivo, 'ancestrosCultivos' => 'true'];
        }

        $respuesta = Http::asForm()->timeout(600)->retry(2, 5000)
            ->post(self::URL_EXPORTACION, ['dataDto' => $filtro]);

        if ($respuesta->failed()) {
            throw new RuntimeException("El Registro de Productos Fitosanitarios respondió HTTP {$respuesta->status()}.");
        }

        // La respuesta es un JSON serializado como cadena; dentro, «Contenido» es a su vez otro JSON
        $cuerpo = json_decode($respuesta->body(), true);
        $envoltorio = is_string($cuerpo) ? json_decode($cuerpo, true) : $cuerpo;
        $productos = is_array($envoltorio) && isset($envoltorio['Contenido'])
            ? json_decode($envoltorio['Contenido'], true)
            : null;

        if (!is_array($productos)) {
            throw new RuntimeException('Respuesta no reconocida del Registro de Productos Fitosanitarios.');
        }

        return $productos;
    }

    private function texto(?string $valor, int $max): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : mb_substr($valor, 0, $max);
    }
}
