<?php

namespace App\Modules\Tratamientos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tratamientos\Http\Requests\GuardarTratamientoRequest;
use App\Modules\Tratamientos\Services\TratamientoService;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\JsonResponse;

class TratamientoController extends Controller
{
    public function __construct(private readonly TratamientoService $service) {}

    public function index(Parcela $parcela): JsonResponse
    {
        $this->authorize('view', $parcela->finca);

        return response()->json(
            $parcela->tratamientos()->with('producto')->latest('fecha')->paginate(20)
        );
    }

    public function store(GuardarTratamientoRequest $request, Parcela $parcela): JsonResponse
    {
        $this->authorize('update', $parcela->finca);

        $tratamiento = $this->service->registrar($parcela, $request->user(), $request->validated());

        return response()->json($tratamiento->load('producto'), 201);
    }

    public function show(Parcela $parcela, int $tratamiento): JsonResponse
    {
        $this->authorize('view', $parcela->finca);

        return response()->json(
            $parcela->tratamientos()->with('producto', 'user')->findOrFail($tratamiento)
        );
    }
}
