<?php

namespace App\Modules\CalendarioFenologico\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CalendarioFenologico\Models\EstadoFenologico;
use App\Modules\CalendarioFenologico\Models\RegistroFenologico;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistroFenologicoController extends Controller
{
    public function index(Parcela $parcela): JsonResponse
    {
        return response()->json(
            $parcela->registrosFenologicos()->with('estado')->latest('fecha_observacion')->paginate(30)
        );
    }

    public function store(Request $request, Parcela $parcela): JsonResponse
    {
        $data = $request->validate([
            'estado_fenologico_id' => 'required|exists:estados_fenologicos,id',
            'fecha_observacion'    => 'required|date',
            'observaciones'        => 'nullable|string|max:1000',
        ]);

        $registro = $parcela->registrosFenologicos()->create(
            array_merge($data, ['user_id' => $request->user()->id])
        );

        return response()->json($registro->load('estado'), 201);
    }

    public function show(Parcela $parcela, RegistroFenologico $registro): JsonResponse
    {
        return response()->json($registro->load('estado', 'user'));
    }

    public function estados(): JsonResponse
    {
        return response()->json(EstadoFenologico::orderBy('orden')->get());
    }
}
