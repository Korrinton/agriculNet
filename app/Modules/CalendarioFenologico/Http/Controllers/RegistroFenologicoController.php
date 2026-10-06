<?php

namespace App\Modules\CalendarioFenologico\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CalendarioFenologico\Models\EstadoFenologico;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RegistroFenologicoController extends Controller
{
    public function index(Parcela $parcela): JsonResponse
    {
        $this->authorize('view', $parcela->finca);

        return response()->json(
            $parcela->registrosFenologicos()->with('estado')->latest('fecha_observacion')->paginate(30)
        );
    }

    public function store(Request $request, Parcela $parcela): JsonResponse
    {
        $this->authorize('update', $parcela->finca);

        // El calendario BBCH es de la vid, igual que en la web
        if (!$parcela->esVina()) {
            throw ValidationException::withMessages(['parcela' => 'La fenología solo se anota en parcelas de viña.']);
        }

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

    public function show(Parcela $parcela, int $registro): JsonResponse
    {
        $this->authorize('view', $parcela->finca);

        return response()->json(
            $parcela->registrosFenologicos()->with('estado', 'user')->findOrFail($registro)
        );
    }

    public function estados(): JsonResponse
    {
        return response()->json(EstadoFenologico::orderBy('orden')->get());
    }
}
