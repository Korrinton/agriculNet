<?php

namespace App\Services;

use App\Models\User;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\Alertas\Services\GeneradorAlertas;
use App\Modules\CalendarioFenologico\Models\GradoDia;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\CalendarioFenologico\Services\CalendarioCampana;
use App\Modules\CalendarioFenologico\Services\GradosDiaCalculator;
use App\Modules\Costes\Models\Coste;
use App\Modules\Meteorologia\Models\PrediccionMeteorologica;
use App\Modules\Riegos\Models\Riego;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Finca;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Lo que el agricultor necesita ver al entrar: el tiempo que viene en su finca, lo que requiere
 * atención (alertas y plazos de seguridad en curso) y cómo va la campaña.
 */
class PanelInicio
{
    private const DIAS_SEMANA = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    public function __construct(
        private readonly CalendarioCampana $calendario,
        private readonly GradosDiaCalculator $gradosDia,
    ) {}

    public function para(User $user, ?int $fincaId, Carbon $hoy): array
    {
        $hoy = $hoy->copy()->startOfDay();
        $fincas = Finca::where('user_id', $user->id)
            ->with(['parcelas.variedad', 'estacion'])
            ->get()
            ->sortByDesc(fn (Finca $f) => $f->parcelas->sum('superficie_ha'))
            ->values();

        if ($fincas->isEmpty()) {
            return ['fincas' => $fincas, 'finca' => null];
        }

        $finca = $fincas->firstWhere('id', $fincaId) ?? $fincas->first();
        $parcelaIds = $fincas->flatMap->parcelas->pluck('id');

        return [
            'fincas'    => $fincas,
            'finca'     => $finca,
            'prevision' => $this->prevision($finca, $hoy),
            'alertas'   => Alerta::where('user_id', $user->id)->where('leida', false)
                ->with('parcela.finca')->latest()->latest('id')->limit(5)->get(),
            'alertasTotal' => Alerta::where('user_id', $user->id)->where('leida', false)->count(),
            'plazos'    => $this->plazosEnCurso($parcelaIds, $hoy),
            'campana'   => $this->campana($finca, $hoy),
            'cifras'    => $this->cifras($user, $finca, $parcelaIds, $hoy),
            'actividad' => $this->actividad($user, $parcelaIds),
            'superficie' => (float) $fincas->flatMap->parcelas->sum('superficie_ha'),
        ];
    }

    /** Previsión AEMET de los próximos 7 días en el municipio de la finca. */
    private function prevision(Finca $finca, Carbon $hoy): array
    {
        $dias = PrediccionMeteorologica::where('provincia_cod', $finca->provincia_cod)
            ->where('municipio_cod', $finca->municipio_cod)
            ->whereDate('fecha', '>=', $hoy)
            ->orderBy('fecha')
            ->limit(7)
            ->get()
            ->filter(fn ($d) => $d->temp_max !== null && $d->temp_min !== null)
            ->values();

        if ($dias->isEmpty()) {
            return ['dias' => [], 'min' => null, 'max' => null, 'heladas' => 0];
        }

        // Escala común para que las barras de todos los días sean comparables
        $min = (float) floor(min($dias->min('temp_min'), 0) / 5) * 5;
        $max = (float) ceil($dias->max('temp_max') / 5) * 5;
        $rango = max($max - $min, 1);

        $filas = $dias->map(function ($d) use ($min, $rango, $hoy) {
            $tMin = (float) $d->temp_min;
            $tMax = (float) $d->temp_max;

            return [
                'fecha'   => $d->fecha,
                'dia'     => $d->fecha->isSameDay($hoy) ? 'Hoy' : self::DIAS_SEMANA[$d->fecha->dayOfWeek],
                'numero'  => $d->fecha->day,
                'max'     => (int) round($tMax),
                'min'     => (int) round($tMin),
                'lluvia'  => $d->prob_precipitacion,
                'cielo'   => $d->estado_cielo,
                'icono'   => self::iconoCielo($d->estado_cielo),
                'helada'  => $tMin <= GeneradorAlertas::PREVISION_HELADA_TEMP_MIN,
                // Posición de la barra en % de la escala (desde abajo)
                'desde'   => round(($tMin - $min) / $rango * 100, 1),
                'alto'    => max(round(($tMax - $tMin) / $rango * 100, 1), 2),
            ];
        })->all();

        return [
            'dias'    => $filas,
            'min'     => $min,
            'max'     => $max,
            'heladas' => collect($filas)->where('helada', true)->count(),
            'elaborado' => $dias->max('elaborado_at'),
        ];
    }

    /** Icono según el estado del cielo que da AEMET («Intervalos nubosos con lluvia»…). */
    public static function iconoCielo(?string $cielo): string
    {
        $c = mb_strtolower((string) $cielo);

        return match (true) {
            str_contains($c, 'tormenta')                                => 'tormenta',
            str_contains($c, 'nieve')                                   => 'nieve',
            str_contains($c, 'lluvia') || str_contains($c, 'llovizna')  => 'lluvia',
            str_contains($c, 'niebla') || str_contains($c, 'bruma')     => 'niebla',
            str_contains($c, 'despejado')                               => 'sol',
            str_contains($c, 'poco nuboso') || str_contains($c, 'intervalos') => 'sol-nubes',
            $c === ''                                                   => 'desconocido',
            default                                                     => 'nubes',
        };
    }

    /** Tratamientos cuyo plazo de seguridad aún no ha terminado: no se puede cosechar esa parcela. */
    private function plazosEnCurso(Collection $parcelaIds, Carbon $hoy): Collection
    {
        return Tratamiento::with(['producto', 'parcela.finca'])
            ->whereIn('parcela_id', $parcelaIds)
            ->whereDate('fecha', '>=', $hoy->copy()->subDays(255))
            ->get()
            ->map(function (Tratamiento $t) use ($hoy) {
                $fin = $t->fechaFinalPlazoSeguridad();

                return $fin && $fin->gte($hoy) ? [
                    'tratamiento' => $t,
                    'fin'         => $fin,
                    'quedan'      => (int) $hoy->diffInDays($fin),
                    'plazo'       => $t->plazoSeguridadDias(),
                    // Parte del plazo ya transcurrida, para la barra de progreso
                    'avance'      => round(min(1, $t->fecha->diffInDays($hoy) / max(1, $t->plazoSeguridadDias())) * 100),
                ] : null;
            })
            ->filter()
            // Por parcela, el que termina más tarde es el que manda
            ->groupBy(fn ($p) => $p['tratamiento']->parcela_id)
            ->map(fn ($grupo) => $grupo->sortByDesc('fin')->first())
            ->sortBy('fin')
            ->values();
    }

    /** Fase de la viña: la última observada este año o, si no hay, la esperada para la variedad. */
    private function campana(Finca $finca, Carbon $hoy): ?array
    {
        $vinas = $finca->parcelas->filter->esVina();
        if ($vinas->isEmpty()) {
            return null;
        }

        $calendario = $this->calendario->construir($vinas->pluck('id'), $hoy->year, $hoy);
        $fila = $calendario['filas'][$vinas->first()->id];

        $observado = RegistroFenologico::with('estado')
            ->whereIn('parcela_id', $vinas->pluck('id'))
            ->whereYear('fecha_observacion', $hoy->year)
            ->latest('fecha_observacion')
            ->first();

        $faseEsperada = collect($fila['referencia']['tramos'])->last(fn ($t) => $t['desde']->lte($hoy))['fase'] ?? null;
        $faseObservada = $observado ? CalendarioCampana::fase((int) $observado->estado?->codigo_bbch) : null;
        $fase = $faseObservada ?? $faseEsperada;

        return [
            'bandas'      => $fila['referencia']['bandas'],
            'meses'       => $calendario['meses'],
            'hoy'         => $calendario['hoy'],
            'fase'        => $fase ? CalendarioCampana::FASES[$fase] + ['clave' => $fase] : null,
            'observado'   => $observado,
            'variedad'    => $fila['referencia']['variedad'],
            'parcelas'    => $vinas->count(),
        ];
    }

    private function cifras(User $user, Finca $finca, Collection $parcelaIds, Carbon $hoy): array
    {
        $anio = $hoy->year;
        $costes = Coste::whereHas('finca', fn ($q) => $q->where('user_id', $user->id))->whereYear('fecha', $anio);

        // Grados-día del ciclo de la vid (1-abr a 31-oct) con la estación de la finca: los guarda
        // grados-dia:recalcular; si aún no se han calculado, se calculan al vuelo
        $vina = $finca->parcelas->first(fn ($p) => $p->esVina());
        $gdd = null;
        if ($finca->estacion && $vina) {
            $guardado = GradoDia::where('parcela_id', $vina->id)->whereYear('fecha', $anio)->orderByDesc('fecha')->value('acumulado');
            $gdd = round((float) ($guardado ?? $this->gradosDia->serieCampana($vina, $anio)->last() ?? 0));
        }

        return [
            'anio'         => $anio,
            'tratamientos' => Tratamiento::whereIn('parcela_id', $parcelaIds)->whereYear('fecha', $anio)->count(),
            'gastos'       => (float) (clone $costes)->sum('importe'),
            'generales'    => (float) (clone $costes)->whereNull('parcela_id')->sum('importe'),
            'agua'         => (float) Riego::whereIn('parcela_id', $parcelaIds)->whereYear('fecha', $anio)->sum('volumen_m3'),
            'gdd'          => $gdd,
        ];
    }

    /** Últimos registros de cualquier módulo, del más reciente al más antiguo. */
    private function actividad(User $user, Collection $parcelaIds): Collection
    {
        $tratamientos = Tratamiento::with(['producto', 'parcela'])->whereIn('parcela_id', $parcelaIds)
            ->latest('fecha')->latest('id')->limit(6)->get()
            ->map(fn ($t) => [
                'fecha' => $t->fecha, 'tipo' => 'tratamiento', 'orden' => $t->created_at,
                'titulo' => $t->producto?->nombre ?? 'Tratamiento',
                'detalle' => $t->parcela?->etiqueta . ($t->motivo ? ' · ' . $t->motivo : ''),
                'url' => route('vinedo.parcelas.show', $t->parcela_id),
            ]);

        $costes = Coste::with(['parcela', 'finca', 'categoria'])
            ->whereHas('finca', fn ($q) => $q->where('user_id', $user->id))
            ->whereNull('tratamiento_id')   // el de un tratamiento ya aparece como tratamiento
            ->latest('fecha')->latest('id')->limit(6)->get()
            ->map(fn ($c) => [
                'fecha' => $c->fecha, 'tipo' => 'gasto', 'orden' => $c->created_at,
                'titulo' => $c->descripcion ?: ($c->categoria?->nombre ?? 'Gasto'),
                'detalle' => number_format((float) $c->importe, 2, ',', '.') . ' € · ' . ($c->parcela?->etiqueta ?? 'Toda la finca'),
                'url' => $c->parcela_id ? route('vinedo.parcelas.show', $c->parcela_id) : route('vinedo.fincas.show', $c->finca_id),
            ]);

        $riegos = Riego::with('parcela')->whereIn('parcela_id', $parcelaIds)
            ->latest('fecha')->latest('id')->limit(6)->get()
            ->map(fn ($r) => [
                'fecha' => $r->fecha, 'tipo' => 'riego', 'orden' => $r->created_at,
                'titulo' => 'Riego de ' . number_format((float) $r->volumen_m3, 0, ',', '.') . ' m³',
                'detalle' => $r->parcela?->etiqueta,
                'url' => route('vinedo.parcelas.show', $r->parcela_id),
            ]);

        $fenologia = RegistroFenologico::with(['estado', 'parcela'])->whereIn('parcela_id', $parcelaIds)
            ->latest('fecha_observacion')->latest('id')->limit(6)->get()
            ->map(fn ($r) => [
                'fecha' => $r->fecha_observacion, 'tipo' => 'fenologia', 'orden' => $r->created_at,
                'titulo' => 'BBCH ' . $r->estado?->codigo_bbch . ' · ' . $r->estado?->nombre,
                'detalle' => $r->parcela?->etiqueta,
                'url' => route('vinedo.parcelas.show', $r->parcela_id),
            ]);

        return $tratamientos->concat($costes)->concat($riegos)->concat($fenologia)
            ->sortByDesc(fn ($e) => $e['fecha']->format('Ymd') . optional($e['orden'])->format('His'))
            ->take(6)
            ->values();
    }
}
