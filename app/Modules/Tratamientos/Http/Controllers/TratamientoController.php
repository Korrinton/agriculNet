<?php

namespace App\Modules\Tratamientos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tratamientos\Services\TratamientoService;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TratamientoController extends Controller
{
    public function __construct(private readonly TratamientoService $service) {}

    public function index(Parcela $parcela): JsonResponse
    {
        return response()->json(
            $parcela->tratamientos()->with('producto')->latest('fecha')->paginate(20)
        );
    }

    public function store(Request $request, Parcela $parcela): JsonResponse
    {
        $data = $request->validate([
            'producto_id' => 'required|exists:productos_fitosanitarios,id',
            'fecha'       => 'required|date',
            'dosis_l_ha'  => 'required|numeric|min:0.001',
            'motivo'      => 'nullable|string|max:500',
        ]);

        $tratamiento = $this->service->registrar($parcela, $request->user(), $data);

        return response()->json($tratamiento->load('producto'), 201);
    }

    public function show(Parcela $parcela, int $tratamiento): JsonResponse
    {
        return response()->json(
            $parcela->tratamientos()->with('producto', 'user')->findOrFail($tratamiento)
        );
    }
}
