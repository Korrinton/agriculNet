<?php

namespace App\Modules\Alertas\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Alertas\Models\Alerta;
use App\Modules\Alertas\Services\AlertaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertaController extends Controller
{
    public function __construct(private readonly AlertaService $service) {}

    public function index(Request $request): JsonResponse
    {
        $soloNoLeidas = $request->boolean('no_leidas');

        return response()->json(
            $this->service->alertasDeUsuario($request->user(), $soloNoLeidas)
        );
    }

    public function show(Alerta $alerta): JsonResponse
    {
        return response()->json($alerta->load('parcela'));
    }

    public function marcarLeida(Alerta $alerta): JsonResponse
    {
        $alerta->marcarLeida();

        return response()->json($alerta);
    }

    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        $total = $this->service->marcarTodasLeidas($request->user());

        return response()->json(['marcadas' => $total]);
    }
}
