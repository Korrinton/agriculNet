<?php

namespace App\Modules\Admin\Services;

use App\Models\User;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\Costes\Models\Coste;
use App\Modules\CuadernoDigital\Models\Cosecha;
use App\Modules\CuadernoDigital\Models\Fertilizacion;
use App\Modules\Riegos\Models\Riego;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Cifras globales de uso para el backoffice. Solo agregados: el administrador no ve aquí
 * los datos de ninguna explotación concreta.
 */
class MetricasUso
{
    public const DIAS_ACTIVO = 30;

    public function calcular(Carbon $hoy): array
    {
        $anio = $hoy->year;
        $hace = $hoy->copy()->subDays(self::DIAS_ACTIVO);

        return [
            'usuarios' => [
                'total'      => User::count(),
                'activos'    => User::where('ultimo_acceso_at', '>=', $hace)->count(),
                'nuevos'     => User::where('created_at', '>=', $hace)->count(),
                'bloqueados' => User::whereNotNull('bloqueado_at')->count(),
            ],
            'explotaciones' => [
                'fincas'    => Finca::count(),
                'parcelas'  => Parcela::count(),
                'hectareas' => (float) Parcela::sum('superficie_ha'),
            ],
            'porCultivo' => $this->hectareasPorCultivo(),
            'registrosAnio' => [
                'Tratamientos'   => Tratamiento::whereYear('fecha', $anio)->count(),
                'Observaciones'  => RegistroFenologico::whereYear('fecha_observacion', $anio)->count(),
                'Fertilizaciones' => Fertilizacion::whereYear('fecha', $anio)->count(),
                'Riegos'         => Riego::whereYear('fecha', $anio)->count(),
                'Cosechas'       => Cosecha::whereYear('fecha', $anio)->count(),
                'Gastos'         => Coste::whereYear('fecha', $anio)->count(),
            ],
            'tratamientosPorMes' => $this->porMes(Tratamiento::query(), 'fecha', $hoy),
            'altasPorMes'        => $this->porMes(User::query(), 'created_at', $hoy),
            'anio'               => $anio,
        ];
    }

    /** @return Collection<string, float> nombre del cultivo => ha, de más a menos */
    private function hectareasPorCultivo(): Collection
    {
        return Parcela::selectRaw('uso, sum(superficie_ha) as ha')->groupBy('uso')->pluck('ha', 'uso')
            ->groupBy(fn ($ha, $uso) => Variedad::CULTIVOS[Parcela::cultivoDeUso($uso)]['nombre'] ?? 'Sin uso', true)
            ->map(fn (Collection $grupo) => round((float) $grupo->sum(), 2))
            ->sortDesc();
    }

    /**
     * Recuento de los últimos 12 meses (incluido el actual), con los meses vacíos a cero.
     *
     * @return array<int, array{mes: string, etiqueta: string, total: int}>
     */
    private function porMes($query, string $columna, Carbon $hoy): array
    {
        $desde = $hoy->copy()->startOfMonth()->subMonths(11);
        $totales = $query->where($columna, '>=', $desde)
            ->selectRaw("to_char({$columna}, 'YYYY-MM') as mes, count(*) as total")
            ->groupBy('mes')->pluck('total', 'mes');

        $meses = [];
        for ($mes = $desde->copy(); $mes->lte($hoy); $mes->addMonth()) {
            $clave = $mes->format('Y-m');
            $meses[] = [
                'mes'      => $clave,
                'etiqueta' => ucfirst($mes->locale('es')->isoFormat('MMM YY')),
                'total'    => (int) ($totales[$clave] ?? 0),
            ];
        }

        return $meses;
    }
}
