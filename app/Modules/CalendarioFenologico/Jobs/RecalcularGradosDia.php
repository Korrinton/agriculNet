<?php

namespace App\Modules\CalendarioFenologico\Jobs;

use App\Modules\CalendarioFenologico\Services\GradosDiaCalculator;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalcularGradosDia implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Parcela $parcela) {}

    public function handle(GradosDiaCalculator $calculator): void
    {
        // TODO: persistir el acumulado diario en grados_dia
    }
}
