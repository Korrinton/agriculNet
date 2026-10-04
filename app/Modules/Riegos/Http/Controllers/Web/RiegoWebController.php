<?php

namespace App\Modules\Riegos\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Riegos\Models\Riego;
use App\Modules\Vinedo\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RiegoWebController extends Controller
{
    public function index(Request $request)
    {
        $fincas = Finca::where('user_id', auth()->id())->orderBy('provincia_cod')->orderBy('municipio_cod')->get();

        if ($fincas->isEmpty()) {
            return view('riegos.index', ['fincas' => $fincas, 'finca' => null]);
        }

        $finca = $fincas->firstWhere('id', (int) $request->query('finca')) ?? $fincas->first();
        $finca->load(['parcelas.variedad', 'estacion']);
        $parcelaIds = $finca->parcelas->pluck('id');

        $actual = now('Europe/Madrid')->year;
        $campanas = Riego::whereIn('parcela_id', $parcelaIds)->pluck('fecha')
            ->map(fn ($f) => $f->year)->push($actual)->unique()->sortDesc()->values()->all();
        $anio = (int) $request->query('anio', $actual);
        $anio = in_array($anio, $campanas, true) ? $anio : $actual;

        $riegos = Riego::with('parcela.finca')
            ->whereIn('parcela_id', $parcelaIds)
            ->whereYear('fecha', $anio)
            ->orderByDesc('fecha')->orderByDesc('id')
            ->get();

        // Agua de lluvia registrada en la estación de la finca en la misma campaña
        $lluvia = null;
        if ($finca->estacion) {
            $datos = DatoMeteorologico::where('estacion_id', $finca->estacion->id)->whereYear('fecha', $anio);
            $lluvia = ['mm' => (float) (clone $datos)->sum('precipitacion_mm'), 'dias' => $datos->count()];
        }

        $resumen = $finca->parcelas->map(function ($parcela) use ($riegos) {
            $deParcela = $riegos->where('parcela_id', $parcela->id);
            $m3 = (float) $deParcela->sum('volumen_m3');
            $superficie = (float) $parcela->superficie_ha;

            return [
                'parcela' => $parcela,
                'riegos'  => $deParcela->count(),
                'm3'      => $m3,
                'm3_ha'   => $superficie > 0 ? $m3 / $superficie : null,
                'mm'      => $superficie > 0 ? $m3 / $superficie / 10 : null,
                'ultimo'  => $deParcela->max('fecha'),
            ];
        });

        return view('riegos.index', compact('fincas', 'finca', 'campanas', 'anio', 'riegos', 'resumen', 'lluvia'));
    }

    public function create(Finca $finca)
    {
        $this->authorize('update', $finca);

        $parcelas = $finca->parcelas()->regable()->with('variedad')->get();

        // Se proponen el sistema y el origen del último riego de la finca
        $ultimo = Riego::whereIn('parcela_id', $finca->parcelas()->pluck('id'))->latest('fecha')->latest('id')->first();

        return view('riegos.create', compact('finca', 'parcelas', 'ultimo'));
    }

    public function store(Request $request, Finca $finca)
    {
        $this->authorize('update', $finca);

        $validated = $request->validate([
            'parcela_id'     => [
                'required',
                Rule::exists('parcelas', 'id')->where('finca_id', $finca->id)->whereNull('deleted_at')->where(fn ($q) => $q->where('uso', '!=', 'Secano')),
            ],
            'fecha'          => 'required|date|before_or_equal:today',
            'volumen_m3'     => 'required|numeric|gt:0',
            'superficie_ha'  => 'nullable|numeric|gt:0',
            'duracion_horas' => 'nullable|numeric|gt:0|max:240',
            'sistema'        => ['required', Rule::in(array_keys(Riego::SISTEMAS))],
            'origen'         => ['nullable', Rule::in(array_keys(Riego::ORIGENES))],
            'observaciones'  => 'nullable|string|max:500',
        ], [
            'parcela_id.exists' => 'Elige una parcela de regadío de esta finca (las de secano no se riegan).',
        ]);

        $parcela = $finca->parcelas()->findOrFail($validated['parcela_id']);
        // Sin superficie indicada se entiende que se regó la parcela entera
        $validated['superficie_ha'] ??= $parcela->superficie_ha;

        Riego::create($validated + ['user_id' => $request->user()->id]);

        return redirect()->route('riegos.index', ['finca' => $finca->id, 'anio' => substr($validated['fecha'], 0, 4)])
            ->with('success', 'Riego anotado.');
    }

    public function destroy(Riego $riego)
    {
        $finca = $riego->parcela->finca;
        $this->authorize('update', $finca);

        $anio = $riego->fecha->year;
        $riego->delete();

        return redirect()->route('riegos.index', ['finca' => $finca->id, 'anio' => $anio])
            ->with('success', 'Riego eliminado.');
    }
}
