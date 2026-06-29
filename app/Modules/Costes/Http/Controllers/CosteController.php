<?php

namespace App\Modules\Costes\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Costes\Services\CosteService;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CosteController extends Controller
{
    public function __construct(private readonly CosteService $service) {}

    public function index(Parcela $parcela): JsonResponse
    {
        return response()->json(
            $parcela->costes()->with('categoria')->latest('fecha')->paginate(20)
        );
    }

    public function store(Request $request, Parcela $parcela): JsonResponse
    {
        $data = $request->validate([
            'categoria_id' => 'required|exists:categoria_costes,id',
            'fecha'        => 'required|date',
            'importe'      => 'required|numeric|min:0.01',
            'descripcion'  => 'nullable|string|max:500',
        ]);

        $coste = $parcela->costes()->create(
            array_merge($data, ['user_id' => $request->user()->id])
        );

        return response()->json($coste->load('categoria'), 201);
    }

    public function resumen(Request $request, Parcela $parcela): JsonResponse
    {
        $año = $request->integer('año', now()->year);

        return response()->json([
            'por_categoria'   => $this->service->costesTotalesPorCategoria($parcela, $año),
            'coste_por_ha'    => $this->service->costesPorHectarea($parcela, $año),
        ]);
    }
}
