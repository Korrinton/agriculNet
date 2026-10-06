<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Jobs\LanzarTarea;
use App\Modules\Admin\Services\Tareas;
use Illuminate\Http\Request;

class TareaAdminController extends Controller
{
    public function index(Tareas $tareas)
    {
        return view('admin.tareas.index', [
            'tareas'   => $tareas->resumen(),
            'sinClave' => ! config('aemet.api_key'),
        ]);
    }

    public function lanzar(Request $request, string $comando)
    {
        abort_unless(isset(Tareas::CATALOGO[$comando]), 404);

        LanzarTarea::dispatch($comando, $request->user()->id);

        return back()->with('success', '«' . Tareas::CATALOGO[$comando]['nombre'] . '» se está ejecutando en segundo plano. Recarga la página para ver el resultado.');
    }
}
