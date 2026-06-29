<?php

namespace App\Modules\Vinedo\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Variedad;
use App\Modules\Vinedo\Services\SigpacService;
use Illuminate\Http\Request;

class FincaWebController extends Controller
{
    private array $fincaRules = [
        'provincia_cod' => 'required|integer|min:1|max:52',
        'municipio_cod' => 'required|integer|min:1|max:999',
        'paraje'        => 'nullable|string|max:255',
    ];

    private array $parcelaRules = [
        'parcelas'                       => 'required|array|min:1',
        'parcelas.*.nombre'              => 'nullable|string|max:255',
        'parcelas.*.uso'                 => 'required|string|max:100',
        'parcelas.*.superficie_ha'       => 'required|numeric|min:0.0001',
        'parcelas.*.variedad_id'         => 'nullable|integer|exists:variedades,id',
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
        $validated = $request->validate(array_merge($this->fincaRules, $this->parcelaRules));

        $finca = Finca::create([
            'user_id'       => auth()->id(),
            'provincia_cod' => $validated['provincia_cod'],
            'municipio_cod' => $validated['municipio_cod'],
            'paraje'        => $validated['paraje'] ?? null,
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

    public function show(Finca $finca, SigpacService $sigpac)
    {
        $this->authorize('view', $finca);
        $finca->load(['parcelas.variedad', 'parcelas.finca']);

        $parcelasConSigpac = $finca->parcelas
            ->filter(fn($p) => $sigpac->tieneReferenciaSigpac($p))
            ->map(fn($p) => [
                'id'         => $p->id,
                'nombre'     => 'Pol. ' . $p->poligono . ' / Par. ' . $p->parcela_sigpac,
                'sigpacUrl'  => route('vinedo.parcelas.sigpac', $p),
                'externoUrl' => $sigpac->getSigpacUrl($p),
            ])->values();

        return view('vinedo.fincas.show', [
            'finca'             => $finca,
            'parcelasConSigpac' => $parcelasConSigpac,
        ]);
    }

    public function edit(Finca $finca)
    {
        $this->authorize('update', $finca);

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
