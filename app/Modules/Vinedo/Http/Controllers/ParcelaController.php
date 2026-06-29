<?php

namespace App\Modules\Vinedo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Services\VinedoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParcelaController extends Controller
{
    public function __construct(private readonly VinedoService $service) {}

    public function index(Finca $finca): JsonResponse
    {
        $this->authorize('view', $finca);

        return response()->json(
            $finca->parcelas()->with('variedad')->paginate(15)
        );
    }

    public function store(Request $request, Finca $finca): JsonResponse
    {
        $this->authorize('update', $finca);

        $data = $request->validate([
            'variedad_id'        => 'required|exists:variedades,id',
            'nombre'             => 'required|string|max:255',
            'superficie_ha'      => 'required|numeric|min:0.001',
            'año_plantacion'     => 'nullable|integer|min:1800|max:' . date('Y'),
            'sistema_conduccion' => 'nullable|string|max:100',
        ]);

        $parcela = $this->service->crearParcela($finca, $data);

        return response()->json($parcela->load('variedad'), 201);
    }

    public function show(Finca $finca, Parcela $parcela): JsonResponse
    {
        $this->authorize('view', $finca);

        return response()->json($parcela->load('variedad'));
    }

    public function update(Request $request, Finca $finca, Parcela $parcela): JsonResponse
    {
        $this->authorize('update', $finca);

        $data = $request->validate([
            'variedad_id'        => 'sometimes|exists:variedades,id',
            'nombre'             => 'sometimes|string|max:255',
            'superficie_ha'      => 'sometimes|numeric|min:0.001',
            'año_plantacion'     => 'nullable|integer|min:1800|max:' . date('Y'),
            'sistema_conduccion' => 'nullable|string|max:100',
        ]);

        $parcela->update($data);

        return response()->json($parcela->load('variedad'));
    }

    public function destroy(Finca $finca, Parcela $parcela): JsonResponse
    {
        $this->authorize('update', $finca);
        $parcela->delete();

        return response()->json(null, 204);
    }
}
