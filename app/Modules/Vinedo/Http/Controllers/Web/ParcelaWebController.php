<?php

namespace App\Modules\Vinedo\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Vinedo\Http\Requests\GuardarParcelaRequest;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use App\Modules\Vinedo\Services\SigpacService;

class ParcelaWebController extends Controller
{
    public function show(Parcela $parcela, SigpacService $sigpac)
    {
        $this->authorize('view', $parcela->finca);

        $parcela->load([
            'finca',
            'variedad',
            'tratamientos' => fn ($q) => $q->with('producto')->latest('fecha')->limit(5),
            'costes' => fn ($q) => $q->with('categoria')->latest('fecha')->limit(5),
            'registrosFenologicos' => fn ($q) => $q->with('estado')->latest('fecha_observacion')->limit(5),
            'alertas' => fn ($q) => $q->where('leida', false)->latest()->limit(5),
        ]);

        $sigpacData = null;
        if ($sigpac->tieneReferenciaSigpac($parcela)) {
            $sigpacData = [
                'externoUrl' => $sigpac->getSigpacUrl($parcela),
                'apiUrl' => route('vinedo.parcelas.sigpac', $parcela),
            ];
        }

        return view('vinedo.parcelas.show', compact('parcela', 'sigpacData'));
    }

    public function create(Finca $finca)
    {
        $this->authorize('update', $finca);

        return view('vinedo.parcelas.create', [
            'finca' => $finca,
            'variedades' => Variedad::orderBy('nombre')->get(),
        ]);
    }

    public function store(GuardarParcelaRequest $request, Finca $finca)
    {
        $this->authorize('update', $finca);

        $validated = $request->validated();
        $validated['finca_id'] = $finca->id;
        $validated['agregado'] = $validated['agregado'] ?? 0;
        $validated['nombre'] = Parcela::nombrePorDefecto($validated['nombre'] ?? null, $validated['parcela_sigpac'] ?? null);

        Parcela::create($validated);

        return redirect()->route('vinedo.fincas.show', $finca)
            ->with('success', 'Parcela añadida correctamente.');
    }

    public function edit(Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        return view('vinedo.parcelas.edit', [
            'parcela' => $parcela,
            'finca' => $parcela->finca,
            'variedades' => Variedad::orderBy('nombre')->get(),
        ]);
    }

    public function update(GuardarParcelaRequest $request, Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        $validated = $request->validated();
        $validated['agregado'] = $validated['agregado'] ?? 0;
        $validated['nombre'] = Parcela::nombrePorDefecto($validated['nombre'] ?? null, $validated['parcela_sigpac'] ?? null, $parcela->nombre ?: 'Parcela');

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
