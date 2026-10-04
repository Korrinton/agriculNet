<?php

namespace App\Modules\CalendarioFenologico\Services;

use App\Modules\Alertas\Models\Alerta;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Calendario fenológico de una campaña (año natural): para cada parcela, bandas
 * con la fase en que estaba la viña según las observaciones registradas, más
 * tratamientos y heladas como marcas. Las posiciones se dan en % del año.
 */
class CalendarioCampana
{
    /**
     * Fases principales de la escala BBCH de la vid. Los colores van aquí (estilos en
     * línea) y no como clases Tailwind porque el CSS está precompilado.
     */
    public const FASES = [
        'reposo'        => ['nombre' => 'Reposo y desborre',    'corto' => 'Reposo',    'bbch' => [0, 3],   'color' => '#d6d3d1', 'texto' => '#44403c'],
        'brotacion'     => ['nombre' => 'Brotación',            'corto' => 'Brotación', 'bbch' => [4, 10],  'color' => '#bef264', 'texto' => '#365314'],
        'hojas'         => ['nombre' => 'Desarrollo de hojas',  'corto' => 'Hojas',     'bbch' => [11, 52], 'color' => '#4ade80', 'texto' => '#14532d'],
        'inflorescencia'=> ['nombre' => 'Racimos visibles',     'corto' => 'Racimos',   'bbch' => [53, 59], 'color' => '#2dd4bf', 'texto' => '#134e4a'],
        'floracion'     => ['nombre' => 'Floración',            'corto' => 'Floración', 'bbch' => [60, 68], 'color' => '#f9a8d4', 'texto' => '#831843'],
        'fruto'         => ['nombre' => 'Cuajado y engorde',    'corto' => 'Fruto',     'bbch' => [69, 80], 'color' => '#16a34a', 'texto' => '#ffffff'],
        'maduracion'    => ['nombre' => 'Envero y maduración',  'corto' => 'Maduración','bbch' => [81, 90], 'color' => '#7c3aed', 'texto' => '#ffffff'],
        'senescencia'   => ['nombre' => 'Caída de la hoja',     'corto' => 'Caída hoja','bbch' => [91, 99], 'color' => '#f59e0b', 'texto' => '#451a03'],
    ];

    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    /**
     * Calendario de referencia de una variedad de maduración media en la zona interior
     * peninsular: día (MM-DD) en que suele empezar cada fase. Es orientativo: la
     * campaña real depende del año, la altitud y el manejo.
     */
    public const REFERENCIA_MEDIA = [
        ['01-01', 'reposo'],
        ['04-01', 'brotacion'],
        ['04-20', 'hojas'],
        ['05-10', 'inflorescencia'],
        ['06-01', 'floracion'],
        ['06-15', 'fruto'],
        ['08-01', 'maduracion'],
        ['10-20', 'senescencia'],
        ['12-01', 'reposo'],
    ];

    public const VENDIMIA_MEDIA = ['09-05', '09-30'];

    /** Días que se adelanta (−) o retrasa (+) el ciclo según la época de maduración de la variedad. */
    public const DESFASE_PRECOCIDAD = ['temprana' => -10, 'media' => 0, 'tardia' => 10, 'muy_tardia' => 20];

    /**
     * Cuánto del desfase se aplica a cada fase: la brotación varía poco entre variedades
     * y la maduración mucho; la caída de la hoja la marcan sobre todo las heladas.
     */
    private const PESO_DESFASE = [
        'reposo' => 0, 'brotacion' => 0.5, 'hojas' => 0.5, 'inflorescencia' => 0.5,
        'floracion' => 1, 'fruto' => 1, 'maduracion' => 1, 'senescencia' => 0,
    ];

    private Carbon $inicio;
    private Carbon $finExclusivo;
    private int $diasAnio;

    /**
     * @param Collection $parcelaIds parcelas a incluir
     * @return array{anio: int, meses: array, hoy: ?float, filas: array<int, array>}
     */
    public function construir(Collection $parcelaIds, int $anio, Carbon $hoy): array
    {
        $this->inicio = Carbon::create($anio, 1, 1)->startOfDay();
        $this->finExclusivo = $this->inicio->copy()->addYear();
        $this->diasAnio = (int) $this->inicio->diffInDays($this->finExclusivo);
        $hoy = $hoy->copy()->startOfDay();

        // Hasta dónde llegan las bandas: hoy en la campaña en curso, el año entero en las pasadas
        $corte = $hoy->lt($this->finExclusivo) ? $hoy->copy()->addDay() : $this->finExclusivo->copy();

        $registros = RegistroFenologico::with('estado')
            ->whereIn('parcela_id', $parcelaIds)
            ->where('fecha_observacion', '<', $this->finExclusivo->toDateString())
            ->orderBy('fecha_observacion')
            ->orderBy('id')
            ->get()
            ->groupBy('parcela_id');

        $tratamientos = Tratamiento::with('producto')
            ->whereIn('parcela_id', $parcelaIds)
            ->whereBetween('fecha', [$this->inicio->toDateString(), $this->finExclusivo->copy()->subDay()->toDateString()])
            ->orderBy('fecha')
            ->get()
            ->groupBy('parcela_id');

        $heladas = $this->heladas($parcelaIds, $anio);
        $variedades = Parcela::with('variedad')->whereIn('id', $parcelaIds)->get()->keyBy('id')->map->variedad;

        $filas = [];
        foreach ($parcelaIds as $id) {
            $referencia = $this->referencia($variedades->get($id));
            $delAnio = $registros->get($id, collect())->filter(fn ($r) => $r->fecha_observacion->gte($this->inicio));

            $filas[$id] = [
                'referencia' => $referencia + ['comparacion' => $this->comparar($delAnio->last(), $referencia['tramos'])],
                'bandas' => $this->bandas($registros->get($id, collect()), $corte),
                'tratamientos' => $tratamientos->get($id, collect())->map(fn (Tratamiento $t) => [
                    'pos' => $this->posicion($t->fecha),
                    'titulo' => $t->fecha->format('d/m') . ' · ' . ($t->producto?->nombre ?? 'Tratamiento'),
                ])->all(),
                'heladas' => $heladas->get($id, collect())->all(),
            ];
        }

        return [
            'anio'  => $anio,
            'meses' => $this->meses(),
            'hoy'   => $hoy->gte($this->inicio) && $hoy->lt($this->finExclusivo) ? $this->posicion($hoy) : null,
            'filas' => $filas,
        ];
    }

    public static function fase(?int $bbch): ?string
    {
        if ($bbch === null) {
            return null;
        }

        foreach (self::FASES as $clave => $fase) {
            if ($bbch >= $fase['bbch'][0] && $bbch <= $fase['bbch'][1]) {
                return $clave;
            }
        }

        return null;
    }

    /**
     * Cada observación abre una banda que dura hasta la siguiente. La última observación
     * anterior al año marca el estado de partida desde el 1 de enero.
     */
    private function bandas(Collection $registros, Carbon $corte): array
    {
        $previo = $registros->filter(fn ($r) => $r->fecha_observacion->lt($this->inicio))->last();
        $delAnio = $registros->filter(fn ($r) => $r->fecha_observacion->gte($this->inicio))->values();

        $tramos = $previo ? collect([$previo])->concat($delAnio) : $delAnio;
        $bandas = [];

        foreach ($tramos as $i => $registro) {
            $desde = $registro->fecha_observacion->lt($this->inicio) ? $this->inicio->copy() : $registro->fecha_observacion->copy();
            $siguiente = $tramos->get($i + 1);
            $hasta = $siguiente ? $siguiente->fecha_observacion->copy() : $corte->copy();

            if ($hasta->lte($desde)) {
                continue; // varias observaciones el mismo día: manda la última
            }

            $bbch = $registro->estado?->codigo_bbch !== null ? (int) $registro->estado->codigo_bbch : null;
            $clave = self::fase($bbch) ?? 'reposo';
            $fase = self::FASES[$clave];

            $bandas[] = [
                'fase'      => $clave,
                'color'     => $fase['color'],
                'texto'     => $fase['texto'],
                'etiqueta'  => $fase['corto'],
                'izquierda' => $this->posicion($desde),
                'ancho'     => $this->posicion($hasta) - $this->posicion($desde),
                'arrastrada'=> $registro->fecha_observacion->lt($this->inicio),
                'titulo'    => sprintf(
                    '%s · BBCH %s %s · %s',
                    $fase['nombre'],
                    $registro->estado?->codigo_bbch ?? '?',
                    $registro->estado?->nombre ?? '',
                    $registro->fecha_observacion->lt($this->inicio)
                        ? 'observado el ' . $registro->fecha_observacion->format('d/m/Y') . ' (campaña anterior)'
                        : 'observado el ' . $registro->fecha_observacion->format('d/m/Y'),
                ),
            ];
        }

        return $bandas;
    }

    /**
     * Fases esperadas en el año para la variedad (o una de maduración media si no se
     * conoce), desplazadas según su precocidad, y la ventana habitual de vendimia.
     */
    private function referencia(?Variedad $variedad): array
    {
        $precocidad = $variedad?->precocidad && isset(self::DESFASE_PRECOCIDAD[$variedad->precocidad])
            ? $variedad->precocidad
            : 'media';
        $desfase = self::DESFASE_PRECOCIDAD[$precocidad];
        $anio = $this->inicio->year;

        $tramos = [];
        foreach (self::REFERENCIA_MEDIA as $i => [$inicio, $clave]) {
            $desde = $i === 0 ? $this->inicio->copy() : Carbon::parse("{$anio}-{$inicio}")->addDays((int) round($desfase * self::PESO_DESFASE[$clave]));
            $tramos[] = ['desde' => $desde, 'fase' => $clave];
        }

        $bandas = [];
        foreach ($tramos as $i => $tramo) {
            $hasta = $tramos[$i + 1]['desde'] ?? $this->finExclusivo;
            $fase = self::FASES[$tramo['fase']];
            $bandas[] = [
                'fase'      => $tramo['fase'],
                'color'     => $fase['color'],
                'izquierda' => $this->posicion($tramo['desde']),
                'ancho'     => $this->posicion($hasta) - $this->posicion($tramo['desde']),
                'titulo'    => sprintf('Esperado: %s del %s al %s', $fase['nombre'], $tramo['desde']->format('d/m'), $hasta->copy()->subDay()->format('d/m')),
            ];
        }

        $vendimiaDesde = Carbon::parse("{$anio}-" . self::VENDIMIA_MEDIA[0])->addDays($desfase);
        $vendimiaHasta = Carbon::parse("{$anio}-" . self::VENDIMIA_MEDIA[1])->addDays($desfase);

        return [
            'variedad'   => $variedad?->nombre,
            'precocidad' => Variedad::PRECOCIDADES[$precocidad],
            'generica'   => $variedad?->precocidad === null,
            'tramos'     => $tramos,
            'bandas'     => $bandas,
            'vendimia'   => [
                'izquierda' => $this->posicion($vendimiaDesde),
                'ancho'     => $this->posicion($vendimiaHasta->copy()->addDay()) - $this->posicion($vendimiaDesde),
                'titulo'    => sprintf('Vendimia habitual: del %s al %s', $vendimiaDesde->format('d/m'), $vendimiaHasta->format('d/m')),
            ],
        ];
    }

    /**
     * Compara la última observación del año con la fase que tocaría ese día según la
     * referencia. Devuelve null si no hay observación o la fase observada no se conoce.
     */
    private function comparar(?RegistroFenologico $registro, array $tramos): ?array
    {
        $bbch = $registro?->estado?->codigo_bbch;
        $observada = $bbch !== null ? self::fase((int) $bbch) : null;
        if (!$observada) {
            return null;
        }

        $esperada = collect($tramos)->filter(fn ($t) => $t['desde']->lte($registro->fecha_observacion))->last()['fase'];

        // El reposo de final de año va después de la caída de la hoja, no antes de la brotación
        $orden = array_flip(array_keys(self::FASES));
        $finDeAnio = $registro->fecha_observacion->month >= 10;
        $indice = fn (string $fase) => $fase === 'reposo' && $finDeAnio ? count($orden) : $orden[$fase];
        $diferencia = $indice($observada) - $indice($esperada);

        return [
            'estado' => $diferencia > 0 ? 'adelantada' : ($diferencia < 0 ? 'retrasada' : 'en_fecha'),
            'titulo' => sprintf(
                'El %s observaste %s; lo habitual esa fecha es %s.',
                $registro->fecha_observacion->format('d/m'),
                mb_strtolower(self::FASES[$observada]['nombre']),
                mb_strtolower(self::FASES[$esperada]['nombre']),
            ),
        ];
    }

    /** Heladas del año por parcela, observadas y previstas, a partir de las alertas generadas. */
    private function heladas(Collection $parcelaIds, int $anio): Collection
    {
        return Alerta::whereIn('parcela_id', $parcelaIds)
            ->whereIn('tipo', ['helada', 'prevision_helada'])
            ->where('clave', 'like', "%:{$anio}-%")
            ->get()
            ->map(function (Alerta $a) {
                // clave: "helada:{parcela}:{AAAA-MM-DD}" o "prevision_helada:{parcela}:{AAAA-MM-DD}"
                $fecha = Carbon::parse(substr($a->clave, strrpos($a->clave, ':') + 1));

                return [
                    'parcela_id' => $a->parcela_id,
                    'pos'        => $this->posicion($fecha),
                    'prevista'   => $a->tipo === 'prevision_helada',
                    'titulo'     => ($a->tipo === 'prevision_helada' ? 'Helada prevista el ' : 'Helada el ') . $fecha->format('d/m/Y'),
                ];
            })
            ->unique(fn ($h) => $h['parcela_id'] . $h['pos'] . $h['prevista'])
            ->groupBy('parcela_id');
    }

    private function meses(): array
    {
        $meses = [];
        foreach (self::MESES as $i => $nombre) {
            $desde = $this->inicio->copy()->addMonths($i);
            $meses[] = [
                'nombre'    => $nombre,
                'izquierda' => $this->posicion($desde),
                'ancho'     => $this->posicion($desde->copy()->addMonth()) - $this->posicion($desde),
            ];
        }

        return $meses;
    }

    /** Posición de una fecha en % del año (0 = 1 de enero, 100 = 1 de enero siguiente). */
    private function posicion(Carbon $fecha): float
    {
        $dias = $this->inicio->diffInDays($fecha->copy()->startOfDay(), false);

        return round(max(0, min($this->diasAnio, $dias)) / $this->diasAnio * 100, 3);
    }
}
