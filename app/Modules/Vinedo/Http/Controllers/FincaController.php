<?php

namespace App\Modules\Vinedo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vinedo\Http\Requests\GuardarFincaRequest;
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

    public function store(GuardarFincaRequest $request): JsonResponse
    {
        $finca = $this->service->crearFinca($request->user(), $request->validated());

        return response()->json($finca, 201);
    }

    public function show(Finca $finca): JsonResponse
    {
        $this->authorize('view', $finca);

        return response()->json($finca->load('parcelas.variedad'));
    }

    public function update(GuardarFincaRequest $request, Finca $finca): JsonResponse
    {
        $this->authorize('update', $finca);

        $finca->update($request->validated());

        return response()->json($finca);
    }

    public function destroy(Finca $finca): JsonResponse
    {
        $this->authorize('delete', $finca);
        $finca->delete();

        return response()->json(null, 204);
    }
}
