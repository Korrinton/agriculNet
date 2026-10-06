<?php

namespace App\Modules\Meteorologia\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Las estaciones AEMET son compartidas y las da de alta el importador; las manuales solo las
 * ve quien tiene una finca vinculada a ellas.
 */
class EstacionMeteorologicaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->visibles($request->user())->orderBy('nombre')->paginate(15));
    }

    public function show(Request $request, int $estacion): JsonResponse
    {
        return response()->json(
            $this->visibles($request->user())->with(['datos' => fn ($q) => $q->latest('fecha')->limit(60)])->findOrFail($estacion)
        );
    }

    private function visibles(User $user): Builder
    {
        return EstacionMeteorologica::query()->where(fn ($q) => $q
            ->where('fuente', 'aemet')
            ->orWhereHas('fincas', fn ($f) => $f->where('user_id', $user->id)));
    }
}
