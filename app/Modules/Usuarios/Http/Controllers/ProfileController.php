<?php

namespace App\Modules\Usuarios\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->perfil($request));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $request->user()->update($data);

        return response()->json($this->perfil($request));
    }

    /** Solo los datos del perfil: nada de campos internos (is_admin, bloqueado_at…). */
    private function perfil(Request $request): array
    {
        return $request->user()->only(['id', 'name', 'email', 'alertas_por_correo', 'created_at']);
    }
}
