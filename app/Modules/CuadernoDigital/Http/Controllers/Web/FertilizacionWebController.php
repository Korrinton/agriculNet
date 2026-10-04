<?php

namespace App\Modules\CuadernoDigital\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\CuadernoDigital\Models\Fertilizacion;
use App\Modules\Vinedo\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FertilizacionWebController extends Controller
{
    public function create(Finca $finca)
    {
        $this->authorize('update', $finca);

        return view('cuaderno.fertilizacion', ['finca' => $finca->load('parcelas')]);
    }

    public function store(Request $request, Finca $finca)
    {
        $this->authorize('update', $finca);

        $validated = $request->validate([
            'parcela_id'    => ['required', Rule::exists('parcelas', 'id')->where('finca_id', $finca->id)->whereNull('deleted_at')],
            'fecha'         => 'required|date|before_or_equal:today',
            'tipo'          => ['required', Rule::in(array_keys(Fertilizacion::TIPOS))],
            'producto'      => 'required|string|max:255',
            'riqueza_n'     => 'nullable|numeric|between:0,100',
            'riqueza_p'     => 'nullable|numeric|between:0,100',
            'riqueza_k'     => 'nullable|numeric|between:0,100',
            'dosis'         => 'required|numeric|gt:0',
            'unidad'        => ['required', Rule::in(Fertilizacion::UNIDADES)],
            'superficie_ha' => 'nullable|numeric|gt:0',
            'metodo'        => ['nullable', Rule::in(array_keys(Fertilizacion::METODOS))],
            'observaciones' => 'nullable|string|max:500',
        ]);

        $parcela = $finca->parcelas()->findOrFail($validated['parcela_id']);
        // Sin superficie indicada se entiende que se abonó la parcela entera
        $validated['superficie_ha'] ??= $parcela->superficie_ha;

        Fertilizacion::create($validated + ['user_id' => $request->user()->id]);

        return redirect()->route('cuaderno.index', ['finca' => $finca->id, 'anio' => substr($validated['fecha'], 0, 4)])
            ->with('success', 'Fertilización anotada en el cuaderno.');
    }

    public function destroy(Fertilizacion $fertilizacion)
    {
        $finca = $fertilizacion->parcela->finca;
        $this->authorize('update', $finca);

        $anio = $fertilizacion->fecha->year;
        $fertilizacion->delete();

        return redirect()->route('cuaderno.index', ['finca' => $finca->id, 'anio' => $anio])
            ->with('success', 'Fertilización eliminada.');
    }
}
