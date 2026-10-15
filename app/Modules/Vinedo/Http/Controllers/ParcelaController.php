<?php

namespace App\Modules\Vinedo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vinedo\Http\Requests\GuardarParcelaRequest;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Services\VinedoService;
use Illuminate\Http\JsonResponse;

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

    public function store(GuardarParcelaRequest $request, Finca $finca): JsonResponse
    {
        $this->authorize('update', $finca);

        $data = $request->validated();
        $data['agregado'] = $data['agregado'] ?? 0;
        $data['nombre'] = Parcela::nombrePorDefecto($data['nombre'] ?? null, $data['parcela_sigpac'] ?? null);

        $parcela = $this->service->crearParcela($finca, $data);

        return response()->json($parcela->load('variedad'), 201);
    }

    public function show(Finca $finca, Parcela $parcela): JsonResponse
    {
        $this->authorize('view', $finca);

        return response()->json($parcela->load('variedad'));
    }

    public function update(GuardarParcelaRequest $request, Finca $finca, Parcela $parcela): JsonResponse
    {
        $this->authorize('update', $finca);

        $data = $request->validated();
        $data['agregado'] = $data['agregado'] ?? 0;
        $data['nombre'] = Parcela::nombrePorDefecto($data['nombre'] ?? null, $data['parcela_sigpac'] ?? null, $parcela->nombre ?: 'Parcela');

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
