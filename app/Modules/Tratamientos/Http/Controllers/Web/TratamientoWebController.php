<?php

namespace App\Modules\Tratamientos\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Tratamientos\Services\TratamientoService;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TratamientoWebController extends Controller
{
    public function __construct(private readonly TratamientoService $service) {}

    public function index()
    {
        $fincas = Finca::where('user_id', auth()->id())
            ->with(['parcelas.tratamientos.producto'])
            ->get();

        $tratamientos = Tratamiento::whereHas('parcela.finca', fn($q) => $q->where('user_id', auth()->id()))
            ->with(['parcela.finca', 'producto'])
            ->latest('fecha')
            ->paginate(30);

        return view('tratamientos.index', compact('tratamientos', 'fincas'));
    }

    public function create(Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        $productos = ProductoFitosanitario::orderBy('nombre')->get();

        // Normalmente aplica la misma persona con el mismo equipo: se proponen los últimos usados
        $ultimo = Tratamiento::whereHas('parcela', fn ($q) => $q->where('finca_id', $parcela->finca_id))
            ->whereNotNull('aplicador_ropo')->latest('fecha')->latest('id')->first();

        return view('tratamientos.create', compact('parcela', 'productos', 'ultimo'));
    }

    public function store(Request $request, Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        $validated = $request->validate([
            'producto_id' => 'required|exists:productos_fitosanitarios,id',
            'fecha'       => 'required|date|before_or_equal:today',
            'dosis_l_ha'  => 'required|numeric|min:0.001',
            'motivo'      => 'nullable|string|max:500',
            // Datos que exige el registro de tratamientos del cuaderno de explotación
            'superficie_tratada_ha' => 'nullable|numeric|gt:0|lte:' . $parcela->superficie_ha,
            'aplicador_nombre'      => 'nullable|string|max:255',
            'aplicador_ropo'        => 'nullable|string|max:50',
            'equipo_roma'           => 'nullable|string|max:50',
            'eficacia'              => ['nullable', Rule::in(array_keys(Tratamiento::EFICACIAS))],
        ]);

        $this->service->registrar($parcela, $request->user(), $validated);

        return redirect()->route('vinedo.parcelas.show', $parcela)
            ->with('success', 'Tratamiento registrado correctamente.');
    }

    public function destroy(Tratamiento $tratamiento)
    {
        $this->authorize('update', $tratamiento->parcela->finca);

        $parcela = $tratamiento->parcela;
        $tratamiento->delete();

        return redirect()->route('vinedo.parcelas.show', $parcela)
            ->with('success', 'Tratamiento eliminado.');
    }
}
