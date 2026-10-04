<?php

namespace App\Modules\Alertas\Services;

use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Meteorologia\Models\PrediccionMeteorologica;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Alertas que no nacen de una acción del usuario sino del paso del tiempo o del
 * tiempo atmosférico. Se ejecuta a diario (alertas:generar) y es idempotente:
 * cada alerta lleva una clave única, así que repetir la ejecución no duplica nada.
 */
class GeneradorAlertas
{
    /** Helada: temperatura mínima igual o inferior a este valor (°C). */
    public const HELADA_TEMP_MIN = 0.0;

    /**
     * La predicción AEMET es para el casco urbano del municipio; en hondonadas y
     * fondos de valle la mínima suele ser 1-2 °C más baja, así que se avisa con margen.
     */
    public const PREVISION_HELADA_TEMP_MIN = 1.0;

    /** Regla de los tres dieces para la infección primaria de mildiu. */
    public const MILDIU_LLUVIA_48H_MM = 10.0;
    public const MILDIU_TEMP_MEDIA = 10.0;

    /** Fases BBCH en que la planta es sensible (brotes verdes / brotes de ~10 cm). */
    public const HELADA_BBCH = [7, 89];
    public const MILDIU_BBCH = [12, 89];

    /** Sin registro fenológico se usan estos meses como temporada de riesgo. */
    public const HELADA_MESES = [3, 4, 5];
    public const MILDIU_MESES = [4, 5, 6, 7];

    /** Un registro fenológico más antiguo que esto no describe el estado actual. */
    public const VALIDEZ_REGISTRO_DIAS = 60;

    public function __construct(private readonly AlertaService $alertas) {}

    /**
     * @return array{fin_plazo: int, helada: int, mildiu: int, prevision_helada: int} alertas nuevas creadas
     */
    public function generar(CarbonInterface $hoy, int $diasMeteo = 10): array
    {
        $hoy = Carbon::parse($hoy->toDateString());

        return [
            'fin_plazo' => $this->finPlazosSeguridad($hoy),
            ...$this->meteorologicas($hoy->copy()->subDays($diasMeteo), $hoy),
            'prevision_helada' => $this->previsionHeladas($hoy),
        ];
    }

    /** Heladas previstas por AEMET de hoy en adelante, con la fase fenológica actual. */
    public function previsionHeladas(Carbon $hoy): int
    {
        // Riesgo para brotes de vid: solo parcelas de viña
        $fincas = Finca::whereNotNull('provincia_cod')->whereNotNull('municipio_cod')
            ->with(['parcelas' => fn ($q) => $q->vina()])->get()
            ->filter(fn (Finca $f) => $f->parcelas->isNotEmpty());

        if ($fincas->isEmpty()) {
            return 0;
        }

        $heladas = PrediccionMeteorologica::where('fecha', '>=', $hoy->toDateString())
            ->where('temp_min', '<=', self::PREVISION_HELADA_TEMP_MIN)
            ->orderBy('fecha')
            ->get()
            ->groupBy(fn ($p) => "{$p->provincia_cod}-{$p->municipio_cod}");

        if ($heladas->isEmpty()) {
            return 0;
        }

        $registros = $this->registrosFenologicos($fincas->flatMap->parcelas->pluck('id'), $hoy, $hoy);
        $creadas = 0;

        foreach ($fincas as $finca) {
            foreach ($heladas->get("{$finca->provincia_cod}-{$finca->municipio_cod}", collect()) as $prevision) {
                foreach ($finca->parcelas as $parcela) {
                    $bbch = $this->bbchEn($registros->get($parcela->id, collect()), $hoy);
                    $creadas += $this->evaluarPrevisionHelada($parcela, $finca, $prevision, $bbch);
                }
            }
        }

        return $creadas;
    }

    private function evaluarPrevisionHelada(Parcela $parcela, Finca $finca, PrediccionMeteorologica $prevision, ?int $bbch): int
    {
        $fecha = $prevision->fecha->format('d/m/Y');
        $temp = $this->num($prevision->temp_min, 0);

        if ($bbch !== null) {
            if (!$this->enRango($bbch, self::HELADA_BBCH)) {
                return 0;
            }
            $nivel = 'critical';
            $mensaje = "AEMET prevé una mínima de {$temp} °C el {$fecha} en {$parcela->nombre} con la viña en fase BBCH {$bbch}: riesgo de helada, prepara medidas de protección.";
        } elseif (in_array($prevision->fecha->month, self::HELADA_MESES, true)) {
            $nivel = 'warning';
            $mensaje = "AEMET prevé una mínima de {$temp} °C el {$fecha} en {$parcela->nombre}: riesgo de helada si la viña ya ha brotado.";
        } else {
            return 0;
        }

        return $this->crear($parcela, 'prevision_helada', $nivel, $mensaje, "prevision_helada:{$parcela->id}:{$prevision->fecha->toDateString()}", $finca);
    }

    public function finPlazosSeguridad(Carbon $hoy): int
    {
        // plazo_seguridad_dias es tinyint unsigned: ningún plazo supera 255 días
        $tratamientos = Tratamiento::with(['producto', 'parcela.finca'])
            ->whereHas('producto', fn ($q) => $q->whereNotNull('plazo_seguridad_dias'))
            ->whereHas('parcela')
            ->whereBetween('fecha', [$hoy->copy()->subDays(255)->toDateString(), $hoy->toDateString()])
            ->get();

        $creadas = 0;

        foreach ($tratamientos->groupBy('parcela_id') as $deParcela) {
            $ultimoFin = $deParcela->map->fechaFinalPlazoSeguridad()->filter()->max();

            foreach ($deParcela as $t) {
                $fin = $t->fechaFinalPlazoSeguridad();
                if (!$fin || !$fin->isSameDay($hoy)) {
                    continue;
                }

                $parcela = $t->parcela;
                $mensaje = $ultimoFin->greaterThan($hoy)
                    ? sprintf(
                        'Termina hoy el plazo de seguridad de %s en %s, pero sigue vigente otro tratamiento hasta el %s.',
                        $t->producto->nombre, $parcela->nombre, $ultimoFin->format('d/m/Y'),
                    )
                    : sprintf(
                        'Termina hoy el plazo de seguridad de %s en %s: ya se puede vendimiar.',
                        $t->producto->nombre, $parcela->nombre,
                    );

                $creadas += $this->crear($parcela, 'fin_plazo_seguridad', 'info', $mensaje, "fin_plazo:{$t->id}");
            }
        }

        return $creadas;
    }

    /**
     * @return array{helada: int, mildiu: int}
     */
    public function meteorologicas(Carbon $desde, Carbon $hasta): array
    {
        $creadas = ['helada' => 0, 'mildiu' => 0];

        // Helada en brotes y mildiu de la vid: solo parcelas de viña
        $fincas = Finca::whereNotNull('estacion_meteorologica_id')->with(['parcelas' => fn ($q) => $q->vina()])->get()
            ->filter(fn (Finca $f) => $f->parcelas->isNotEmpty());

        if ($fincas->isEmpty()) {
            return $creadas;
        }

        // Un día extra antes de $desde para sumar la lluvia de 48 h del primer día
        $datos = DatoMeteorologico::whereIn('estacion_id', $fincas->pluck('estacion_meteorologica_id')->unique())
            ->whereBetween('fecha', [$desde->copy()->subDay()->toDateString(), $hasta->toDateString()])
            ->orderBy('fecha')
            ->get()
            ->groupBy('estacion_id');

        $registros = $this->registrosFenologicos($fincas->flatMap->parcelas->pluck('id'), $desde, $hasta);

        foreach ($fincas as $finca) {
            $serie = $datos->get($finca->estacion_meteorologica_id, collect())->keyBy(fn ($d) => $d->fecha->toDateString());

            foreach ($serie as $fecha => $dato) {
                if ($dato->fecha->lt($desde)) {
                    continue;
                }

                $anterior = $serie->get($dato->fecha->copy()->subDay()->toDateString());

                foreach ($finca->parcelas as $parcela) {
                    $bbch = $this->bbchEn($registros->get($parcela->id, collect()), $dato->fecha);

                    $creadas['helada'] += $this->evaluarHelada($parcela, $finca, $dato, $bbch);
                    $creadas['mildiu'] += $this->evaluarMildiu($parcela, $finca, $dato, $anterior, $bbch);
                }
            }
        }

        return $creadas;
    }

    private function evaluarHelada(Parcela $parcela, Finca $finca, DatoMeteorologico $dato, ?int $bbch): int
    {
        if ($dato->temp_min === null || (float) $dato->temp_min > self::HELADA_TEMP_MIN) {
            return 0;
        }

        $fecha = $dato->fecha->format('d/m/Y');
        $temp = $this->num($dato->temp_min, 1);

        if ($bbch !== null) {
            if (!$this->enRango($bbch, self::HELADA_BBCH)) {
                return 0;
            }
            $nivel = 'critical';
            $mensaje = "Helada el {$fecha} ({$temp} °C) en {$parcela->nombre} con la viña en fase BBCH {$bbch}: revisa daños en brotes.";
        } elseif (in_array($dato->fecha->month, self::HELADA_MESES, true)) {
            $nivel = 'warning';
            $mensaje = "Helada el {$fecha} ({$temp} °C) en {$parcela->nombre}: si la viña ya había brotado, revisa daños. Registra la fase fenológica para afinar estos avisos.";
        } else {
            return 0;
        }

        return $this->crear($parcela, 'helada', $nivel, $mensaje, "helada:{$parcela->id}:{$dato->fecha->toDateString()}", $finca);
    }

    private function evaluarMildiu(Parcela $parcela, Finca $finca, DatoMeteorologico $dato, ?DatoMeteorologico $anterior, ?int $bbch): int
    {
        if ($dato->precipitacion_mm === null || $dato->temp_max === null || $dato->temp_min === null) {
            return 0;
        }

        $lluvia48h = (float) $dato->precipitacion_mm + (float) ($anterior?->precipitacion_mm ?? 0);
        $tempMedia = ((float) $dato->temp_max + (float) $dato->temp_min) / 2;

        if ($lluvia48h < self::MILDIU_LLUVIA_48H_MM || $tempMedia < self::MILDIU_TEMP_MEDIA) {
            return 0;
        }

        $fecha = $dato->fecha->format('d/m/Y');
        $detalle = sprintf('%s mm en 48 h, %s °C de media', $this->num($lluvia48h, 1), $this->num($tempMedia, 1));

        if ($bbch !== null) {
            if (!$this->enRango($bbch, self::MILDIU_BBCH)) {
                return 0;
            }
            $mensaje = "Riesgo de infección de mildiu el {$fecha} en {$parcela->nombre} ({$detalle}, fase BBCH {$bbch}): valora un tratamiento preventivo.";
        } elseif (in_array($dato->fecha->month, self::MILDIU_MESES, true)) {
            $mensaje = "Riesgo de infección de mildiu el {$fecha} en {$parcela->nombre} ({$detalle}) si los brotes superan los 10 cm: valora un tratamiento preventivo.";
        } else {
            return 0;
        }

        return $this->crear($parcela, 'riesgo_mildiu', 'warning', $mensaje, "mildiu:{$parcela->id}:{$dato->fecha->toDateString()}", $finca);
    }

    /** Registros que pueden describir la fase de cada parcela entre $desde y $hasta, agrupados por parcela. */
    private function registrosFenologicos(Collection $parcelaIds, Carbon $desde, Carbon $hasta): Collection
    {
        return RegistroFenologico::with('estado')
            ->whereIn('parcela_id', $parcelaIds)
            ->whereBetween('fecha_observacion', [
                $desde->copy()->subDays(self::VALIDEZ_REGISTRO_DIAS)->toDateString(),
                $hasta->toDateString(),
            ])
            ->orderBy('fecha_observacion')
            ->get()
            ->groupBy('parcela_id');
    }

    /** Fase BBCH de la parcela en una fecha según el último registro válido, o null si no se sabe. */
    private function bbchEn(Collection $registros, Carbon $fecha): ?int
    {
        $registro = $registros
            ->filter(fn ($r) => $r->fecha_observacion->lte($fecha)
                && $r->fecha_observacion->gte($fecha->copy()->subDays(self::VALIDEZ_REGISTRO_DIAS)))
            ->last();

        return $registro?->estado ? (int) $registro->estado->codigo_bbch : null;
    }

    private function enRango(int $bbch, array $rango): bool
    {
        return $bbch >= $rango[0] && $bbch <= $rango[1];
    }

    private function crear(Parcela $parcela, string $tipo, string $nivel, string $mensaje, string $clave, ?Finca $finca = null): int
    {
        $userId = ($finca ?? $parcela->finca)->user_id;

        return $this->alertas->crear($parcela, $userId, $tipo, $nivel, $mensaje, $clave)->wasRecentlyCreated ? 1 : 0;
    }

    private function num(float|string $valor, int $decimales): string
    {
        return number_format((float) $valor, $decimales, ',', '.');
    }
}
