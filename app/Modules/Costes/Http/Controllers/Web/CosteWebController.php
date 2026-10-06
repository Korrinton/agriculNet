<?php

namespace App\Modules\Costes\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Costes\Models\CategoriaCoste;
use App\Modules\Costes\Models\Coste;
use App\Modules\Costes\Services\CosteService;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CosteWebController extends Controller
{
    public function __construct(private readonly CosteService $service) {}

    public function index(Request $request)
    {
        $año = $request->integer('año', now()->year);
        $fincas = Finca::where('user_id', auth()->id())->orderBy('paraje')->get();
        $fincaId = $fincas->contains('id', $request->integer('finca')) ? $request->integer('finca') : null;

        $base = fn () => Coste::whereHas('finca', fn ($q) => $q->where('user_id', auth()->id()))
            ->when($fincaId, fn ($q) => $q->where('finca_id', $fincaId))
            ->whereYear('fecha', $año);

        $costes = $base()
            ->with(['finca', 'parcela', 'categoria'])
            ->latest('fecha')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $todos = $base()->with('categoria')->get();
        $resumenPorCategoria = $todos->groupBy('categoria.nombre')->map(fn ($g) => $g->sum('importe'))->sortDesc();
        $totalAño = $resumenPorCategoria->sum();
        $totalGenerales = $todos->whereNull('parcela_id')->sum('importe');

        $años = Coste::whereHas('finca', fn ($q) => $q->where('user_id', auth()->id()))
            ->selectRaw('EXTRACT(YEAR FROM fecha)::integer AS año')
            ->distinct()
            ->orderByDesc('año')
            ->pluck('año');

        return view('costes.index', compact('costes', 'resumenPorCategoria', 'totalAño', 'totalGenerales', 'año', 'años', 'fincas', 'fincaId'));
    }

    /** Gasto desde la ficha de una parcela: el formulario de la finca con esa parcela elegida. */
    public function create(Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        return $this->formulario($parcela->finca, $parcela);
    }

    /** Se mantiene para enlaces antiguos: equivale a guardar en la finca con la parcela elegida. */
    public function store(Request $request, Parcela $parcela)
    {
        $request->merge(['parcela_id' => $parcela->id]);

        return $this->storeFinca($request, $parcela->finca);
    }

    public function createFinca(Finca $finca)
    {
        $this->authorize('update', $finca);

        return $this->formulario($finca, null);
    }

    public function storeFinca(Request $request, Finca $finca)
    {
        $this->authorize('update', $finca);

        $validated = $request->validate([
            'parcela_id'   => ['nullable', 'integer', Rule::in($finca->parcelas()->pluck('id')->all())],
            'categoria_id' => 'required|exists:categoria_costes,id',
            'fecha'        => 'required|date|before_or_equal:today',
            'importe'      => 'required|numeric|min:0.01',
            'descripcion'  => 'nullable|string|max:500',
        ], [
            'parcela_id.in' => 'Esa parcela no es de esta finca.',
        ]);

        $coste = $finca->costes()->create($validated + ['user_id' => $request->user()->id]);

        return $coste->parcela_id
            ? redirect()->route('vinedo.parcelas.show', $coste->parcela_id)->with('success', 'Coste registrado correctamente.')
            : redirect()->route('vinedo.fincas.show', $finca)->with('success', 'Gasto de la finca registrado.');
    }

    public function destroy(Coste $coste)
    {
        $this->authorize('update', $coste->finca);

        $coste->delete();

        return back()->with('success', 'Coste eliminado.');
    }

    private function formulario(Finca $finca, ?Parcela $parcela)
    {
        return view('costes.create', [
            'finca'      => $finca,
            'parcela'    => $parcela,
            'parcelas'   => $finca->parcelas()->orderBy('id')->get(),
            'categorias' => CategoriaCoste::orderBy('nombre')->get(),
        ]);
    }
}
