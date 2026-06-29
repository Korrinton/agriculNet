<?php

namespace App\Modules\Meteorologia\Services;

use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ImportadorMeteorologico
{
    public function importarDesdeAemet(EstacionMeteorologica $estacion, Carbon $desde, Carbon $hasta): int
    {
        // TODO: integración con API de AEMET
        // https://opendata.aemet.es/opendata/api
        return 0;
    }

    public function importarManual(EstacionMeteorologica $estacion, array $filas): int
    {
        $importados = 0;
        foreach ($filas as $fila) {
            DatoMeteorologico::updateOrCreate(
                ['estacion_id' => $estacion->id, 'fecha' => $fila['fecha']],
                $fila
            );
            $importados++;
        }
        return $importados;
    }

    public function ultimosDias(EstacionMeteorologica $estacion, int $dias = 30): Collection
    {
        return $estacion->datos()
            ->whereDate('fecha', '>=', now()->subDays($dias))
            ->orderBy('fecha')
            ->get();
    }
}
