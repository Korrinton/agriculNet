<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Variedades y cultivos que se eligen en las parcelas. */
class VariedadAdminController extends Controller
{
    public const TIPOS = ['tinta' => 'Tinta', 'blanca' => 'Blanca', 'rosada' => 'Rosada'];

    public function index()
    {
        $variedades = Variedad::withCount(['parcelas' => fn ($q) => $q->withTrashed()])
            ->orderBy('nombre')->get()->groupBy('cultivo');

        return view('admin.variedades.index', compact('variedades'));
    }

    public function create(Request $request)
    {
        return view('admin.variedades.form', [
            'variedad' => new Variedad(['cultivo' => $request->query('cultivo', 'vid')]),
        ]);
    }

    public function store(Request $request)
    {
        $variedad = Variedad::create($this->validar($request));

        return redirect()->route('admin.variedades.index')->with('success', "Variedad «{$variedad->nombre}» añadida.");
    }

    public function edit(Variedad $variedad)
    {
        return view('admin.variedades.form', compact('variedad'));
    }

    public function update(Request $request, Variedad $variedad)
    {
        $variedad->update($this->validar($request, $variedad));

        return redirect()->route('admin.variedades.index')->with('success', "Variedad «{$variedad->nombre}» guardada.");
    }

    public function destroy(Variedad $variedad)
    {
        // Las parcelas guardan la variedad: borrarla las dejaría sin ella (y sin cultivo si son de secano)
        $enUso = Parcela::withTrashed()->where('variedad_id', $variedad->id)->count();
        if ($enUso > 0) {
            return back()->with('error', "No se puede borrar «{$variedad->nombre}»: la usan {$enUso} " . ($enUso === 1 ? 'parcela' : 'parcelas') . '.');
        }

        $variedad->delete();

        return back()->with('success', "Variedad «{$variedad->nombre}» borrada.");
    }

    private function validar(Request $request, ?Variedad $variedad = null): array
    {
        $datos = $request->validate([
            'cultivo'     => ['required', Rule::in(array_keys(Variedad::CULTIVOS))],
            'nombre'      => ['required', 'string', 'max:255', Rule::unique('variedades')
                ->where('cultivo', $request->input('cultivo'))->ignore($variedad?->id)],
            'tipo'        => ['nullable', Rule::in(array_keys(self::TIPOS))],
            'precocidad'  => ['nullable', Rule::in(array_keys(Variedad::PRECOCIDADES))],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ], ['nombre.unique' => 'Ya hay una variedad con ese nombre en este cultivo.']);

        // Color de la uva y época de maduración solo tienen sentido en la vid
        if ($datos['cultivo'] !== 'vid') {
            $datos['tipo'] = 'tinta';
            $datos['precocidad'] = null;
        }
        $datos['tipo'] ??= 'tinta';

        return $datos;
    }
}
