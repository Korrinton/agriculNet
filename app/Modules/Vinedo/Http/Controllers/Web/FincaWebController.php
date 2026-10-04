<?php

namespace App\Modules\Vinedo\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Meteorologia\Services\EstacionesCercanas;
use App\Modules\Meteorologia\Services\ImportadorMeteorologico;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use App\Modules\Vinedo\Models\Variedad;
use App\Modules\Vinedo\Rules\VariedadDelCultivo;
use App\Modules\Vinedo\Services\SigpacService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FincaWebController extends Controller
{
    private array $fincaRules = [
        'provincia_cod' => 'required|integer|min:1|max:52',
        'municipio_cod' => 'required|integer|min:1|max:999',
        'paraje'        => 'nullable|string|max:255',
        // Titular de la explotación, para el cuaderno de explotación
        'titular_nombre' => 'nullable|string|max:255',
        'titular_nif'    => 'nullable|string|max:20',
        'rea_numero'     => 'nullable|string|max:50',
    ];

    private array $parcelaRules = [
        'parcelas'                       => 'required|array|min:1',
        'parcelas.*.nombre'              => 'nullable|string|max:255',
        'parcelas.*.superficie_ha'       => 'required|numeric|min:0.0001',
        'parcelas.*.año_plantacion'      => 'nullable|integer|min:1900|max:2099',
        'parcelas.*.sistema_conduccion'  => 'nullable|string|max:100',
        'parcelas.*.agregado'            => 'nullable|integer|min:0|max:99',
        'parcelas.*.poligono'            => 'nullable|integer|min:1',
        'parcelas.*.parcela_sigpac'      => 'nullable|integer|min:1',
        'parcelas.*.recinto'             => 'nullable|integer|min:1',
    ];

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

    public function store(Request $request)
    {
        // Cada parcela valida su variedad contra el cultivo de su propio uso
        $porParcela = [];
        foreach (array_keys((array) $request->input('parcelas', [])) as $i) {
            $porParcela["parcelas.{$i}.uso"] = ['required', Rule::in(Parcela::usos())];
            $porParcela["parcelas.{$i}.variedad_id"] = [
                'nullable', 'integer', 'exists:variedades,id',
                new VariedadDelCultivo($request->input("parcelas.{$i}.uso")),
            ];
        }

        $validated = $request->validate(array_merge($this->fincaRules, $this->parcelaRules, $porParcela));

        $finca = Finca::create([
            'user_id'       => auth()->id(),
            'provincia_cod' => $validated['provincia_cod'],
            'municipio_cod' => $validated['municipio_cod'],
            'paraje'        => $validated['paraje'] ?? null,
            'titular_nombre' => $validated['titular_nombre'] ?? null,
            'titular_nif'    => $validated['titular_nif'] ?? null,
            'rea_numero'     => $validated['rea_numero'] ?? null,
        ]);

        foreach ($validated['parcelas'] as $i => $p) {
            $finca->parcelas()->create([
                'nombre'             => ($p['nombre'] ?? null) ?: (isset($p['parcela_sigpac']) ? 'Parcela ' . $p['parcela_sigpac'] : 'Parcela ' . ($i + 1)),
                'uso'                => $p['uso'],
                'superficie_ha'      => $p['superficie_ha'],
                'variedad_id'        => $p['variedad_id'] ?? null,
                'año_plantacion'     => $p['año_plantacion'] ?? null,
                'sistema_conduccion' => $p['sistema_conduccion'] ?? null,
                'agregado'           => $p['agregado'] ?? 0,
                'poligono'           => $p['poligono'] ?? null,
                'parcela_sigpac'     => $p['parcela_sigpac'] ?? null,
                'recinto'            => $p['recinto'] ?? null,
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
            ->filter(fn($p) => $sigpac->tieneReferenciaSigpac($p))
            ->map(fn($p) => [
                'id'         => $p->id,
                'nombre'     => 'Pol. ' . $p->poligono . ' / Par. ' . $p->parcela_sigpac,
                'sigpacUrl'  => route('vinedo.parcelas.sigpac', $p),
                'externoUrl' => $sigpac->getSigpacUrl($p),
            ])->values();

        $datosMeteoro = $finca->estacion
            ? $meteo->ultimosDias($finca->estacion, 30)
            : collect();

        // La primera visita ubica la finca con SIGPAC; después queda guardada
        $cercanas->ubicar($finca);
        $estacionesAemet = $finca->estacion ? collect() : $cercanas->para($finca);

        return view('vinedo.fincas.show', [
            'finca'             => $finca,
            'parcelasConSigpac' => $parcelasConSigpac,
            'datosMeteoro'      => $datosMeteoro,
            'estacionesAemet'   => $estacionesAemet,
            'distanciaEstacion' => $cercanas->distanciaA($finca, $finca->estacion),
        ]);
    }

    public function edit(Finca $finca)
    {
        $this->authorize('update', $finca);
        $finca->load('parcelas.variedad');

        return view('vinedo.fincas.edit', [
            'finca'      => $finca,
            'provincias' => Finca::getProvincias(),
        ]);
    }

    public function update(Request $request, Finca $finca)
    {
        $this->authorize('update', $finca);

        $validated = $request->validate($this->fincaRules);
        $finca->update($validated);

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
