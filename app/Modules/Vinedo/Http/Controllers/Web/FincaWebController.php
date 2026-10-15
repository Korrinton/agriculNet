<?php

namespace App\Modules\Vinedo\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Meteorologia\Services\EstacionesCercanas;
use App\Modules\Meteorologia\Services\ImportadorMeteorologico;
use App\Modules\Vinedo\Http\Requests\CrearFincaRequest;
use App\Modules\Vinedo\Http\Requests\GuardarFincaRequest;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use App\Modules\Vinedo\Services\SigpacService;

class FincaWebController extends Controller
{
    public function index()
    {
        $fincas = Finca::where('user_id', auth()->id())
            ->withCount('parcelas')
            ->withSum('parcelas', 'superficie_ha')
            ->orderBy('provincia_cod')
            ->orderBy('municipio_cod')
            ->get();

        return view('vinedo.fincas.index', compact('fincas'));
    }

    public function create()
    {
        return view('vinedo.fincas.create', [
            'provincias' => Finca::getProvincias(),
            'variedades' => Variedad::orderBy('nombre')->get(),
        ]);
    }

    public function store(CrearFincaRequest $request)
    {
        $validated = $request->validated();

        $finca = Finca::create([
            'user_id' => auth()->id(),
            'provincia_cod' => $validated['provincia_cod'],
            'municipio_cod' => $validated['municipio_cod'],
            'paraje' => $validated['paraje'] ?? null,
            'titular_nombre' => $validated['titular_nombre'] ?? null,
            'titular_nif' => $validated['titular_nif'] ?? null,
            'rea_numero' => $validated['rea_numero'] ?? null,
        ]);

        foreach ($validated['parcelas'] as $i => $p) {
            $finca->parcelas()->create([
                'nombre' => Parcela::nombrePorDefecto($p['nombre'] ?? null, $p['parcela_sigpac'] ?? null, 'Parcela '.($i + 1)),
                'uso' => $p['uso'],
                'superficie_ha' => $p['superficie_ha'],
                'variedad_id' => $p['variedad_id'] ?? null,
                'año_plantacion' => $p['año_plantacion'] ?? null,
                'sistema_conduccion' => $p['sistema_conduccion'] ?? null,
                'agregado' => $p['agregado'] ?? 0,
                'poligono' => $p['poligono'] ?? null,
                'parcela_sigpac' => $p['parcela_sigpac'] ?? null,
                'recinto' => $p['recinto'] ?? null,
            ]);
        }

        return redirect()->route('vinedo.fincas.show', $finca)
            ->with('success', 'Finca y parcelas registradas correctamente.');
    }

    public function show(Finca $finca, SigpacService $sigpac, ImportadorMeteorologico $meteo, EstacionesCercanas $cercanas)
    {
        $this->authorize('view', $finca);
        $finca->load(['parcelas.variedad', 'parcelas.finca', 'estacion']);

        $parcelasConSigpac = $finca->parcelas
            ->filter(fn ($p) => $sigpac->tieneReferenciaSigpac($p))
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => 'Pol. '.$p->poligono.' / Par. '.$p->parcela_sigpac,
                'sigpacUrl' => route('vinedo.parcelas.sigpac', $p),
                'externoUrl' => $sigpac->getSigpacUrl($p),
            ])->values();

        $datosMeteoro = $finca->estacion
            ? $meteo->ultimosDias($finca->estacion, 30)
            : collect();

        // La primera visita ubica la finca con SIGPAC; después queda guardada
        $cercanas->ubicar($finca);
        $estacionesAemet = $finca->estacion ? collect() : $cercanas->para($finca);

        // Gastos del año: los generales de la finca (sin parcela) y los imputados a sus parcelas
        $costesAño = $finca->costes()
            ->whereBetween('fecha', [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()])
            ->with('categoria')->latest('fecha')->latest('id')->get();

        return view('vinedo.fincas.show', [
            'finca' => $finca,
            'costesAño' => $costesAño,
            'parcelasConSigpac' => $parcelasConSigpac,
            'datosMeteoro' => $datosMeteoro,
            'estacionesAemet' => $estacionesAemet,
            'distanciaEstacion' => $cercanas->distanciaA($finca, $finca->estacion),
        ]);
    }

    public function edit(Finca $finca)
    {
        $this->authorize('update', $finca);
        $finca->load('parcelas.variedad');

        return view('vinedo.fincas.edit', [
            'finca' => $finca,
            'provincias' => Finca::getProvincias(),
        ]);
    }

    public function update(GuardarFincaRequest $request, Finca $finca)
    {
        $this->authorize('update', $finca);

        $finca->update($request->validated());

        return redirect()->route('vinedo.fincas.show', $finca)
            ->with('success', 'Finca actualizada correctamente.');
    }

    public function destroy(Finca $finca)
    {
        $this->authorize('delete', $finca);
        $finca->delete();

        return redirect()->route('vinedo.fincas.index')
            ->with('success', 'Finca eliminada correctamente.');
    }
}
