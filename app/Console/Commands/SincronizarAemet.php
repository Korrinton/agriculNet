<?php

namespace App\Console\Commands;

use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Meteorologia\Services\ImportadorMeteorologico;
use App\Modules\Vinedo\Models\Finca;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SincronizarAemet extends Command
{
    protected $signature   = 'aemet:sincronizar {--dias=10 : Días hacia atrás de datos observados a descargar}';
    protected $description = 'Descarga la predicción de los municipios con fincas y los datos observados de sus estaciones AEMET';

    public function handle(ImportadorMeteorologico $importador): int
    {
        if (!config('aemet.api_key')) {
            $this->error('AEMET_API_KEY no configurada en .env');
            return self::FAILURE;
        }

        $errores = 0;

        // ── Predicción por municipio ───────────────────────────────────────
        $municipios = Finca::whereNotNull('provincia_cod')->whereNotNull('municipio_cod')
            ->distinct()->get(['provincia_cod', 'municipio_cod']);

        $dias = 0;
        foreach ($municipios as $m) {
            try {
                $dias += $importador->importarPrediccion($m->provincia_cod, $m->municipio_cod);
            } catch (Throwable $e) {
                $errores++;
                $this->reportar("Predicción {$m->codigo_ine}", $e);
            }
        }

        // ── Datos observados por estación ─────────────────────────────────
        // AEMET publica los diarios con varios días de retraso: se repite una ventana
        // y updateOrCreate actualiza los días que ya existían.
        $estaciones = EstacionMeteorologica::where('fuente', 'aemet')->whereHas('fincas')->get();

        $registros = 0;
        foreach ($estaciones as $estacion) {
            try {
                $registros += $importador->importarDesdeAemet(
                    $estacion,
                    now()->subDays((int) $this->option('dias')),
                    now(),
                );
            } catch (Throwable $e) {
                $errores++;
                $this->reportar("Estación {$estacion->codigo_externo}", $e);
            }
        }

        $this->table(
            ['Municipios', 'Días de predicción', 'Estaciones', 'Datos observados', 'Errores'],
            [[$municipios->count(), $dias, $estaciones->count(), $registros, $errores]]
        );

        return $errores > 0 && $dias + $registros === 0 ? self::FAILURE : self::SUCCESS;
    }

    private function reportar(string $origen, Throwable $e): void
    {
        $this->warn("{$origen}: {$e->getMessage()}");
        Log::warning("aemet:sincronizar — {$origen}: {$e->getMessage()}");
    }
}
