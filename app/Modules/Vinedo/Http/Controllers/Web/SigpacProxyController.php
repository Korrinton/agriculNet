<?php

namespace App\Modules\Vinedo\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Services\SigpacService;
use Illuminate\Http\JsonResponse;

class SigpacProxyController extends Controller
{
    public function recinto(Parcela $parcela, SigpacService $sigpac): JsonResponse
    {
        $this->authorize('view', $parcela->finca);

        // SigpacService cachea la geometría por referencia SIGPAC
        $geojson = $sigpac->getRecintoGeoJson($parcela);

        if (! $geojson) {
            return response()->json(['error' => 'No se pudo obtener la geometría del SIGPAC'], 404);
        }

        return response()->json($geojson);
    }
}
