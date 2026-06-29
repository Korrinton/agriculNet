<?php

namespace App\Modules\Meteorologia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstacionMeteorologicaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(EstacionMeteorologica::paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre'         => 'required|string|max:255',
            'latitud'        => 'required|numeric|between:-90,90',
            'longitud'       => 'required|numeric|between:-180,180',
            'fuente'         => 'required|in:manual,aemet,api',
            'codigo_externo' => 'nullable|string|max:50',
        ]);

        return response()->json(EstacionMeteorologica::create($data), 201);
    }

    public function show(EstacionMeteorologica $estacion): JsonResponse
    {
        return response()->json($estacion->load('datos'));
    }
}
