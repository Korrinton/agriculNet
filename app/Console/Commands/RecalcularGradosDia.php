<?php

namespace App\Console\Commands;

use App\Modules\CalendarioFenologico\Models\GradoDia;
use App\Modules\CalendarioFenologico\Services\GradosDiaCalculator;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Console\Command;

class RecalcularGradosDia extends Command
{
    protected $signature   = 'grados-dia:recalcular {--anio= : Campaña a recalcular (por defecto, la del año en curso)}';
    protected $description = 'Guarda los grados-día acumulados de la campaña (1-abr a 31-oct) de todas las viñas con estación meteorológica';

    public function handle(GradosDiaCalculator $calculator): int
    {
        $anio = (int) ($this->option('anio') ?: now('Europe/Madrid')->year);

        $parcelas = 0;
        $dias = 0;
        Parcela::vina()->with('finca')->each(function (Parcela $parcela) use ($calculator, $anio, &$parcelas, &$dias) {
            $dias += $calculator->guardarCampana($parcela, $anio);
            $parcelas++;
        });

        // Parcelas que han dejado de ser viña: sus grados-día ya no tienen sentido
        $huerfanos = GradoDia::whereNotIn('parcela_id', Parcela::withTrashed()->vina()->select('id'))->delete();

        $this->table(['Campaña', 'Viñas', 'Días guardados', 'Filas de no-viñas borradas'], [[$anio, $parcelas, $dias, $huerfanos]]);

        return self::SUCCESS;
    }
}
