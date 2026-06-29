<?php

namespace App\Modules\Vinedo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Services\VinedoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FincaController extends Controller
{
    public function __construct(private readonly VinedoService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->fincasDeUsuario($request->user())
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:255',
            'municipio'    => 'required|string|max:255',
            'provincia'    => 'required|string|max:255',
            'superficie_ha'=> 'required|numeric|min:0.01',
            'latitud'      => 'nullable|numeric|between:-90,90',
            'longitud'     => 'nullable|numeric|between:-180,180',
        ]);

        $finca = $this->service->crearFinca($request->user(), $data);

        return response()->json($finca, 201);
    }

    public function show(Finca $finca): JsonResponse
    {
        $this->authorize('view', $finca);

        return response()->json($finca->load('parcelas.variedad'));
    }

    public function update(Request $request, Finca $finca): JsonResponse
    {
        $this->authorize('update', $finca);

        $data = $request->validate([
            'nombre'        => 'sometimes|string|max:255',
            'municipio'     => 'sometimes|string|max:255',
            'provincia'     => 'sometimes|string|max:255',
            'superficie_ha' => 'sometimes|numeric|min:0.01',
            'latitud'       => 'nullable|numeric|between:-90,90',
            'longitud'      => 'nullable|numeric|between:-180,180',
        ]);

        $finca->update($data);

        return response()->json($finca);
    }

    public function destroy(Finca $finca): JsonResponse
    {
        $this->authorize('delete', $finca);
        $finca->delete();

        return response()->json(null, 204);
    }
}
