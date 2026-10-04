<?php

namespace App\Modules\Costes\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Costes\Models\CategoriaCoste;
use App\Modules\Costes\Models\Coste;
use App\Modules\Costes\Services\CosteService;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\Request;

class CosteWebController extends Controller
{
    public function __construct(private readonly CosteService $service) {}

    public function index(Request $request)
    {
        $año = $request->integer('año', now()->year);

        $costes = Coste::whereHas('parcela.finca', fn($q) => $q->where('user_id', auth()->id()))
            ->with(['parcela.finca', 'categoria'])
            ->whereYear('fecha', $año)
            ->latest('fecha')
            ->paginate(30)
            ->withQueryString();

        $resumenPorCategoria = Coste::whereHas('parcela.finca', fn($q) => $q->where('user_id', auth()->id()))
            ->with('categoria')
            ->whereYear('fecha', $año)
            ->get()
            ->groupBy('categoria.nombre')
            ->map(fn($g) => $g->sum('importe'))
            ->sortDesc();

        $totalAño = $resumenPorCategoria->sum();

        $años = Coste::whereHas('parcela.finca', fn($q) => $q->where('user_id', auth()->id()))
            ->selectRaw('EXTRACT(YEAR FROM fecha)::integer AS año')
            ->distinct()
            ->orderByDesc('año')
            ->pluck('año');

        return view('costes.index', compact('costes', 'resumenPorCategoria', 'totalAño', 'año', 'años'));
    }

    public function create(Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        $categorias = CategoriaCoste::orderBy('nombre')->get();

        return view('costes.create', compact('parcela', 'categorias'));
    }

    public function store(Request $request, Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        $validated = $request->validate([
            'categoria_id' => 'required|exists:categoria_costes,id',
            'fecha'        => 'required|date|before_or_equal:today',
            'importe'      => 'required|numeric|min:0.01',
            'descripcion'  => 'nullable|string|max:500',
        ]);

        $parcela->costes()->create(array_merge($validated, ['user_id' => $request->user()->id]));

        return redirect()->route('vinedo.parcelas.show', $parcela)
            ->with('success', 'Coste registrado correctamente.');
    }

    public function destroy(Coste $coste)
    {
        $this->authorize('update', $coste->parcela->finca);

        $parcela = $coste->parcela;
        $coste->delete();

        return redirect()->route('vinedo.parcelas.show', $parcela)
            ->with('success', 'Coste eliminado.');
    }
}
