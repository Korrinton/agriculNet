<?php

namespace App\Modules\Tratamientos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Services\TratamientoService;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function store(Request $request, Parcela $parcela): JsonResponse
    {
        $this->authorize('update', $parcela->finca);

        $data = $request->validate([
            'producto_id' => ['required', ProductoFitosanitario::reglaVisiblePara($request->user())],
            'fecha'       => 'required|date',
            'dosis_l_ha'  => 'required|numeric|min:0.001',
            'plazo_seguridad_dias' => 'nullable|integer|min:0|max:255',
            'precio_unitario'      => 'nullable|numeric|min:0|max:99999',
            'motivo'      => 'nullable|string|max:500',
            'hora_inicio'             => 'nullable|date_format:H:i',
            'bbch'                    => ['nullable', 'regex:/^\d{2}$/'],
            'justificacion'           => 'nullable|string|max:500',
            'superficie_tratada_ha'   => 'nullable|numeric|gt:0|lte:' . $parcela->superficie_ha,
            'aplicador_nombre'        => 'nullable|string|max:255',
            'aplicador_nif'           => 'nullable|string|max:20',
            'aplicador_ropo'          => 'nullable|string|max:50',
            'equipo_roma'             => 'nullable|string|max:50',
            'equipo_inspeccion_fecha' => 'nullable|date',
            'asesor_nombre'           => 'nullable|string|max:255',
            'asesor_nif'              => 'nullable|string|max:20',
            'asesor_ropo'             => 'nullable|string|max:50',
            'asesor_fecha_validacion' => 'nullable|date',
        ]);

        $tratamiento = $this->service->registrar($parcela, $request->user(), $data);

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
