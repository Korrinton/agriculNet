<?php

namespace App\Modules\Meteorologia\Jobs;

use App\Modules\CalendarioFenologico\Jobs\RecalcularGradosDia;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Meteorologia\Services\ImportadorMeteorologico;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportarDatosMeteorologicos implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /** AEMET limita peticiones por minuto: espaciar los reintentos. */
    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [120, 600];

    public int $timeout = 300;

    /** El candado de unicidad caduca solo por si el trabajo se pierde sin terminar. */
    public int $uniqueFor = 3600;

    public function __construct(
        public readonly EstacionMeteorologica $estacion,
        public readonly Carbon $desde,
        public readonly Carbon $hasta,
    ) {}

    /** Varios clics en «importar» no deben duplicar el trabajo mientras uno siga en cola. */
    public function uniqueId(): string
    {
        return $this->estacion->id.':'.$this->desde->toDateString().':'.$this->hasta->toDateString();
    }

    public function handle(ImportadorMeteorologico $importador): void
    {
        $importador->importarDesdeAemet($this->estacion, $this->desde, $this->hasta);

        for ($anio = $this->desde->year; $anio <= $this->hasta->year; $anio++) {
            RecalcularGradosDia::paraEstacion($this->estacion->id, $anio);
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::error("Importación AEMET fallida para la estación {$this->estacion->codigo_externo} ({$this->desde->toDateString()} – {$this->hasta->toDateString()}): ".$e?->getMessage());
    }
}
