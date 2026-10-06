<?php

namespace App\Modules\CalendarioFenologico\Jobs;

use App\Modules\CalendarioFenologico\Services\GradosDiaCalculator;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Recalcula y guarda los grados-día acumulados de la campaña de una viña. */
class RecalcularGradosDia implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public readonly Parcela $parcela,
        public readonly int $anio,
    ) {}

    public function uniqueId(): string
    {
        return "{$this->parcela->id}-{$this->anio}";
    }

    public function handle(GradosDiaCalculator $calculator): void
    {
        $calculator->guardarCampana($this->parcela, $this->anio);
    }

    /** Encola el recálculo de las viñas de las fincas que usan una estación (sus datos han cambiado). */
    public static function paraEstacion(int $estacionId, int $anio): void
    {
        Parcela::vina()
            ->whereHas('finca', fn ($q) => $q->where('estacion_meteorologica_id', $estacionId))
            ->each(fn (Parcela $parcela) => self::dispatch($parcela, $anio));
    }

    /** Encola el recálculo de las viñas de una finca (le han cambiado la estación). */
    public static function paraFinca(int $fincaId, int $anio): void
    {
        Parcela::vina()->where('finca_id', $fincaId)
            ->each(fn (Parcela $parcela) => self::dispatch($parcela, $anio));
    }
}
