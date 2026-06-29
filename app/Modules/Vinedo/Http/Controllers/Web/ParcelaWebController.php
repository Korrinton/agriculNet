<?php

namespace App\Modules\Vinedo\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use Illuminate\Http\Request;

class ParcelaWebController extends Controller
{
    private array $rules = [
        'nombre'             => 'nullable|string|max:255',
        'uso'                => 'required|string|max:100',
        'superficie_ha'      => 'required|numeric|min:0.0001',
        'variedad_id'        => 'nullable|integer|exists:variedades,id',
        'año_plantacion'     => 'nullable|integer|min:1900|max:2099',
        'sistema_conduccion' => 'nullable|string|max:100',
        'agregado'           => 'nullable|integer|min:0|max:99',
        'poligono'           => 'nullable|integer|min:1',
        'parcela_sigpac'     => 'nullable|integer|min:1',
        'recinto'            => 'nullable|integer|min:1',
    ];

    public function create(Finca $finca)
    {
        $this->authorize('update', $finca);

        return view('vinedo.parcelas.create', [
            'finca'      => $finca,
            'variedades' => Variedad::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request, Finca $finca)
    {
        $this->authorize('update', $finca);

        $validated = $request->validate($this->rules);
        $validated['finca_id'] = $finca->id;
        $validated['agregado'] = $validated['agregado'] ?? 0;
        $validated['nombre'] = ($validated['nombre'] ?? null)
            ?: ($validated['parcela_sigpac'] ?? null ? 'Parcela ' . $validated['parcela_sigpac'] : 'Parcela');

        Parcela::create($validated);

        return redirect()->route('vinedo.fincas.show', $finca)
            ->with('success', 'Parcela añadida correctamente.');
    }

    public function edit(Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        return view('vinedo.parcelas.edit', [
            'parcela'    => $parcela,
            'finca'      => $parcela->finca,
            'variedades' => Variedad::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        $validated = $request->validate($this->rules);
        $validated['agregado'] = $validated['agregado'] ?? 0;
        $validated['nombre'] = ($validated['nombre'] ?? null)
            ?: ($validated['parcela_sigpac'] ?? null ? 'Parcela ' . $validated['parcela_sigpac'] : ($parcela->nombre ?: 'Parcela'));

        $parcela->update($validated);

        return redirect()->route('vinedo.fincas.show', $parcela->finca)
            ->with('success', 'Parcela actualizada correctamente.');
    }

    public function destroy(Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        $finca = $parcela->finca;
        $parcela->delete();

        return redirect()->route('vinedo.fincas.show', $finca)
            ->with('success', 'Parcela eliminada correctamente.');
    }
}
