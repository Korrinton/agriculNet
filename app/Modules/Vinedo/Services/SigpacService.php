<?php

namespace App\Modules\Vinedo\Services;

use App\Modules\Vinedo\Models\Parcela;
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
            'f'         => 'json',
        ];

        if ($parcela->recinto) {
            $params['recinto'] = (int) $parcela->recinto;
        }

        try {
            $response = Http::timeout(10)
                ->get('https://sigpac-hubcloud.es/ogcapi/collections/recintos/items', $params);

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data['features'])) {
                    return $data;
                }
            }
        } catch (\Exception) {
        }

        return null;
    }
}
