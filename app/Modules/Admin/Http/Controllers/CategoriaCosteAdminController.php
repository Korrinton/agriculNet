<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Costes\Models\CategoriaCoste;
use App\Modules\Tratamientos\Services\TratamientoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Categorías en las que se clasifican los gastos. */
class CategoriaCosteAdminController extends Controller
{
    public function index()
    {
        $categorias = CategoriaCoste::withCount('costes')->orderBy('tipo')->orderBy('nombre')->get()->groupBy('tipo');

        return view('admin.categorias.index', [
            'categorias' => $categorias,
            'protegida'  => TratamientoService::CATEGORIA_COSTE,
        ]);
    }

    public function store(Request $request)
    {
        $categoria = CategoriaCoste::create($this->validar($request));

        return back()->with('success', "Categoría «{$categoria->nombre}» añadida.");
    }

    public function update(Request $request, CategoriaCoste $categoria)
    {
        $datos = $this->validar($request, $categoria);
        // Los tratamientos buscan su categoría por el nombre: no se puede renombrar
        if ($categoria->nombre === TratamientoService::CATEGORIA_COSTE) {
            $datos['nombre'] = $categoria->nombre;
        }
        $categoria->update($datos);

        return back()->with('success', "Categoría «{$categoria->nombre}» guardada.");
    }

    public function destroy(CategoriaCoste $categoria)
    {
        if ($categoria->nombre === TratamientoService::CATEGORIA_COSTE) {
            return back()->with('error', 'Esa categoría la usan los costes de los tratamientos: no se puede borrar.');
        }
        $enUso = $categoria->costes()->count();
        if ($enUso > 0) {
            return back()->with('error', "No se puede borrar «{$categoria->nombre}»: tiene {$enUso} " . ($enUso === 1 ? 'gasto' : 'gastos') . '.');
        }

        $categoria->delete();

        return back()->with('success', "Categoría «{$categoria->nombre}» borrada.");
    }

    private function validar(Request $request, ?CategoriaCoste $categoria = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('categoria_costes')->ignore($categoria?->id)],
            'tipo'   => ['required', Rule::in(array_keys(CategoriaCoste::TIPOS))],
        ], ['nombre.unique' => 'Ya hay una categoría con ese nombre.']);
    }
}
