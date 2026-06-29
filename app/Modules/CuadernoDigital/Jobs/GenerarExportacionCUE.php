<?php

namespace App\Modules\CuadernoDigital\Jobs;

use App\Modules\CuadernoDigital\Services\ExportadorCUE;
use App\Modules\Vinedo\Models\Finca;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerarExportacionCUE implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Finca $finca,
        public readonly Carbon $desde,
        public readonly Carbon $hasta,
    ) {}

    public function handle(ExportadorCUE $exportador): void
    {
        $xml = $exportador->generar($this->finca, $this->desde, $this->hasta);
        // TODO: guardar en storage y notificar al usuario
    }
}
