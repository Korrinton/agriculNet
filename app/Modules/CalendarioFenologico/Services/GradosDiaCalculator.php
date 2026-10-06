<?php

namespace App\Modules\CalendarioFenologico\Services;

use App\Modules\CalendarioFenologico\Models\GradoDia;
use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Vinedo\Models\Parcela;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradosDiaCalculator
{
    private const TEMPERATURA_BASE_DEFAULT = 10.0;

    /** Ciclo vegetativo de la vid (índice de Winkler): del 1 de abril al 31 de octubre. */
    public static function cicloVegetativo(int $anio): array
    {
        return [Carbon::create($anio, 4, 1)->startOfDay(), Carbon::create($anio, 10, 31)->startOfDay()];
    }

    /**
     * Grados-día acumulados de cada día de la campaña con los datos de la estación de la
     * finca, en orden de fecha. Los días sin dato no suman.
     *
     * @return Collection<string, float> fecha (Y-m-d) => acumulado
     */
    public function serieCampana(Parcela $parcela, int $anio, float $base = self::TEMPERATURA_BASE_DEFAULT): Collection
    {
        $estacionId = $parcela->finca?->estacion_meteorologica_id;
        if (! $estacionId) {
            return collect();
        }

        [$desde, $hasta] = self::cicloVegetativo($anio);
        $acumulado = 0.0;

        return DatoMeteorologico::where('estacion_id', $estacionId)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->whereNotNull('temp_max')
            ->whereNotNull('temp_min')
            ->orderBy('fecha')
            ->get()
            ->mapWithKeys(function (DatoMeteorologico $dato) use (&$acumulado, $base) {
                $acumulado += $this->calcularDiario((float) $dato->temp_max, (float) $dato->temp_min, $base);

                return [$dato->fecha->toDateString() => round($acumulado, 2)];
            });
    }

    /**
     * Guarda en grados_dia el acumulado diario de la campaña de una viña, sustituyendo lo
     * anterior (los datos de AEMET llegan con retraso y se corrigen; la estación puede cambiar).
     * En parcelas que no son viña o sin estación solo borra lo que hubiera.
     *
     * @return int días guardados
     */
    public function guardarCampana(Parcela $parcela, int $anio, float $base = self::TEMPERATURA_BASE_DEFAULT): int
    {
        [$desde, $hasta] = self::cicloVegetativo($anio);
        $serie = $parcela->esVina() ? $this->serieCampana($parcela, $anio, $base) : collect();
        $ahora = now();

        DB::transaction(function () use ($parcela, $desde, $hasta, $serie, $base, $ahora) {
            GradoDia::where('parcela_id', $parcela->id)
                ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
                ->delete();

            GradoDia::insert($serie->map(fn (float $acumulado, string $fecha) => [
                'parcela_id'       => $parcela->id,
                'fecha'            => $fecha,
                'acumulado'        => $acumulado,
                'temperatura_base' => $base,
                'created_at'       => $ahora,
                'updated_at'       => $ahora,
            ])->values()->all());
        });

        return $serie->count();
    }

    public function calcularDiario(float $tempMax, float $tempMin, float $base = self::TEMPERATURA_BASE_DEFAULT): float
    {
        return max(0, (($tempMax + $tempMin) / 2) - $base);
    }
}
