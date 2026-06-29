<?php

namespace App\Modules\CuadernoDigital\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CuadernoDigital\Jobs\GenerarExportacionCUE;
use App\Modules\CuadernoDigital\Models\CuadernoEntrada;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CuadernoDigitalController extends Controller
{
    public function index(Parcela $parcela): JsonResponse
    {
        return response()->json(
            CuadernoEntrada::where('parcela_id', $parcela->id)
                ->with('user')
                ->latest('fecha')
                ->paginate(30)
        );
    }

    public function exportarCUE(Request $request, Finca $finca): JsonResponse
    {
        $data = $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ]);

        GenerarExportacionCUE::dispatch(
            $finca,
            \Carbon\Carbon::parse($data['desde']),
            \Carbon\Carbon::parse($data['hasta']),
        );

        return response()->json(['message' => 'Exportación CUE en proceso. Recibirás una notificación cuando esté lista.']);
    }
}
