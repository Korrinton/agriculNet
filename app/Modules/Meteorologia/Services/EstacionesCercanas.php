<?php

namespace App\Modules\Meteorologia\Services;

use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Services\SigpacService;
use Illuminate\Support\Collection;

class EstacionesCercanas
{
    private const RADIO_TIERRA_KM = 6371.0;

    public function __construct(private readonly SigpacService $sigpac) {}

    /**
     * Estaciones AEMET ordenadas por distancia a la finca (de cualquier provincia),
     * con el atributo distancia_km. Si la finca no se puede ubicar, devuelve las de
     * su provincia por orden alfabético y distancia_km = null.
     */
    public function para(Finca $finca, int $limite = 15): Collection
    {
        if (!$this->ubicar($finca)) {
            return EstacionMeteorologica::where('fuente', 'aemet')
                ->where('provincia_cod', $finca->provincia_cod)
                ->orderBy('nombre')
                ->get()
                ->each(fn ($e) => $e->distancia_km = null);
        }

        return EstacionMeteorologica::where('fuente', 'aemet')
            // (0, 0) es "sin coordenadas" en el inventario
            ->where(fn ($q) => $q->where('latitud', '!=', 0)->orWhere('longitud', '!=', 0))
            ->get()
            ->each(fn ($e) => $e->distancia_km = $this->distanciaKm($finca, $e))
            ->sortBy('distancia_km')
            ->take($limite)
            ->values();
    }

    public function distanciaA(Finca $finca, ?EstacionMeteorologica $estacion): ?float
    {
        if (!$estacion || !$finca->tieneCoordenadas() || ((float) $estacion->latitud === 0.0 && (float) $estacion->longitud === 0.0)) {
            return null;
        }

        return $this->distanciaKm($finca, $estacion);
    }

    /**
     * Calcula y guarda la ubicación de la finca si aún no se ha intentado.
     * Un intento fallido se recuerda ('no_disponible') para no consultar SIGPAC en
     * cada visita; se vuelve a intentar cuando cambian sus parcelas o su municipio.
     */
    public function ubicar(Finca $finca): bool
    {
        if ($finca->tieneCoordenadas()) {
            return true;
        }

        if ($finca->coordenadas_origen === 'no_disponible') {
            return false;
        }

        $finca->loadMissing('parcelas');
        $ubicacion = $this->sigpac->ubicarFinca($finca);

        $finca->update($ubicacion
            ? ['latitud' => $ubicacion['latitud'], 'longitud' => $ubicacion['longitud'], 'coordenadas_origen' => $ubicacion['origen']]
            : ['coordenadas_origen' => 'no_disponible']);

        return $ubicacion !== null;
    }

    /** Distancia ortodrómica (fórmula del haversine). */
    private function distanciaKm(Finca $finca, EstacionMeteorologica $estacion): float
    {
        $lat1 = deg2rad($finca->latitud);
        $lat2 = deg2rad((float) $estacion->latitud);
        $dLat = $lat2 - $lat1;
        $dLon = deg2rad((float) $estacion->longitud - $finca->longitud);

        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;

        return 2 * self::RADIO_TIERRA_KM * asin(min(1.0, sqrt($a)));
    }
}
