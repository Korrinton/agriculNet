<?php

namespace App\Modules\Vinedo\Services;

use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SigpacService
{
    public function tieneReferenciaSigpac(Parcela $parcela): bool
    {
        $finca = $parcela->finca;
        return $finca->provincia_cod
            && $finca->municipio_cod
            && $parcela->poligono
            && $parcela->parcela_sigpac;
    }

    private function params(Parcela $parcela): array
    {
        $finca = $parcela->finca;
        return [
            'prov' => str_pad((int) $finca->provincia_cod, 2, '0', STR_PAD_LEFT),
            'mun'  => str_pad((int) $finca->municipio_cod, 3, '0', STR_PAD_LEFT),
            'agr'  => (int) ($parcela->agregado ?? 0),
            'pol'  => (int) $parcela->poligono,
            'par'  => (int) $parcela->parcela_sigpac,
            'rec'  => (int) ($parcela->recinto ?? 0),
        ];
    }

    public function getSigpacUrl(Parcela $parcela): ?string
    {
        if (! $this->tieneReferenciaSigpac($parcela)) {
            return null;
        }

        ['prov' => $prov, 'mun' => $mun, 'agr' => $agr, 'pol' => $pol, 'par' => $par, 'rec' => $rec] = $this->params($parcela);

        return "https://sigpac.mapa.es/fega/visor/#prov={$prov}&mun={$mun}&agr={$agr}&pol={$pol}&par={$par}&rec={$rec}";
    }

    /**
     * GeoJSON (lon/lat, CRS84) de los recintos de la parcela. Se cachea 30 días por
     * referencia SIGPAC, así que si se corrige la referencia se consulta la nueva.
     */
    public function getRecintoGeoJson(Parcela $parcela): ?array
    {
        if (! $this->tieneReferenciaSigpac($parcela)) {
            return null;
        }

        $finca = $parcela->finca;

        $params = [
            'provincia' => (int) $finca->provincia_cod,
            'municipio' => (int) $finca->municipio_cod,
            'poligono'  => (int) $parcela->poligono,
            'parcela'   => (int) $parcela->parcela_sigpac,
        ];

        if ($parcela->recinto) {
            $params['recinto'] = (int) $parcela->recinto;
        }

        return $this->consultarRecintos($params, 10);
    }

    /**
     * Ubicación aproximada de la finca a partir de SIGPAC: centro del primer recinto
     * de sus parcelas que exista y, si ninguno existe (referencia mal escrita, recinto
     * dado de baja...), un recinto cualquiera del mismo polígono catastral (1-3 km).
     *
     * @return array{latitud: float, longitud: float, origen: string}|null
     */
    public function ubicarFinca(Finca $finca, int $maxIntentos = 2): ?array
    {
        $parcelas = $finca->parcelas
            ->each(fn (Parcela $p) => $p->setRelation('finca', $finca))
            ->filter(fn (Parcela $p) => $this->tieneReferenciaSigpac($p))
            ->values();

        foreach ($parcelas->take($maxIntentos) as $parcela) {
            if ($centro = $this->centro($this->getRecintoGeoJson($parcela))) {
                return $centro + ['origen' => 'parcela'];
            }
        }

        foreach ($parcelas->pluck('poligono')->unique()->take($maxIntentos) as $poligono) {
            $geojson = $this->consultarRecintos([
                'provincia' => (int) $finca->provincia_cod,
                'municipio' => (int) $finca->municipio_cod,
                'poligono'  => (int) $poligono,
                'limit'     => 1,
            ], 10);

            if ($centro = $this->centro($geojson)) {
                return $centro + ['origen' => 'poligono'];
            }
        }

        return null;
    }

    /** Centro aproximado (media de los vértices) de todas las geometrías del GeoJSON. */
    private function centro(?array $geojson): ?array
    {
        $vertices = [];
        foreach ($geojson['features'] ?? [] as $feature) {
            array_walk_recursive($feature['geometry']['coordinates'], function ($v) use (&$vertices) {
                $vertices[] = $v;
            });
        }

        // array_walk_recursive aplana [lon, lat, lon, lat...]
        $n = intdiv(count($vertices), 2);
        if ($n === 0) {
            return null;
        }

        $lon = $lat = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $lon += $vertices[2 * $i];
            $lat += $vertices[2 * $i + 1];
        }

        return ['latitud' => $lat / $n, 'longitud' => $lon / $n];
    }

    private function consultarRecintos(array $params, int $timeout): ?array
    {
        ksort($params);
        $cacheKey = 'sigpac.recintos.' . md5(http_build_query($params));

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            $response = Http::timeout($timeout)
                ->get('https://sigpac-hubcloud.es/ogcapi/collections/recintos/items', $params + ['f' => 'json']);

            if ($response->successful() && ! empty($response->json('features'))) {
                $data = $response->json();
                Cache::put($cacheKey, $data, now()->addDays(30));

                return $data;
            }
        } catch (\Exception) {
        }

        return null;
    }
}
