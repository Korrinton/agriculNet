<?php

namespace App\Modules\CuadernoDigital\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\CuadernoDigital\Models\Cosecha;
use App\Modules\Vinedo\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CosechaWebController extends Controller
{
    public function create(Finca $finca)
    {
        $this->authorize('update', $finca);

        return view('cuaderno.cosecha', ['finca' => $finca->load('parcelas')]);
    }

    public function store(Request $request, Finca $finca)
    {
        $this->authorize('update', $finca);

        $validated = $request->validate([
            'parcela_id'       => ['required', Rule::exists('parcelas', 'id')->where('finca_id', $finca->id)->whereNull('deleted_at')],
            'fecha'            => 'required|date|before_or_equal:today',
            'producto'         => 'required|string|max:100',
            'cantidad_kg'      => 'required|numeric|gt:0',
            'superficie_ha'    => 'nullable|numeric|gt:0',
            'destino'          => 'nullable|string|max:255',
            'destinatario_nif' => 'nullable|string|max:20',
            'albaran'          => 'nullable|string|max:50',
            'observaciones'    => 'nullable|string|max:500',
        ]);

        Cosecha::create($validated + ['user_id' => $request->user()->id]);

        return redirect()->route('cuaderno.index', ['finca' => $finca->id, 'anio' => substr($validated['fecha'], 0, 4)])
            ->with('success', 'Cosecha anotada en el cuaderno.');
    }

    public function destroy(Cosecha $cosecha)
    {
        $finca = $cosecha->parcela->finca;
        $this->authorize('update', $finca);

        $anio = $cosecha->fecha->year;
        $cosecha->delete();

        return redirect()->route('cuaderno.index', ['finca' => $finca->id, 'anio' => $anio])
            ->with('success', 'Cosecha eliminada.');
    }
}
