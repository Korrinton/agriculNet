<?php

namespace App\Modules\Meteorologia\Jobs;

use App\Modules\CalendarioFenologico\Jobs\RecalcularGradosDia;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Meteorologia\Services\ImportadorMeteorologico;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportarDatosMeteorologicos implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly EstacionMeteorologica $estacion,
        public readonly Carbon $desde,
        public readonly Carbon $hasta,
    ) {}

    public function handle(ImportadorMeteorologico $importador): void
    {
        $importador->importarDesdeAemet($this->estacion, $this->desde, $this->hasta);

        for ($anio = $this->desde->year; $anio <= $this->hasta->year; $anio++) {
            RecalcularGradosDia::paraEstacion($this->estacion->id, $anio);
        }
    }
}
