<?php

namespace App\Modules\Costes\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Costes\Http\Requests\GuardarCosteRequest;
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
        $fincas = Finca::where('user_id', auth()->id())->orderBy('paraje')->get();
        $fincaId = $fincas->contains('id', $request->integer('finca')) ? $request->integer('finca') : null;

        $base = fn () => Coste::whereHas('finca', fn ($q) => $q->where('user_id', auth()->id()))
            ->when($fincaId, fn ($q) => $q->where('finca_id', $fincaId))
            ->whereBetween('fecha', ["{$año}-01-01", "{$año}-12-31"]);

        $costes = $base()
            ->with(['finca', 'parcela', 'categoria'])
            ->latest('fecha')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $resumenPorCategoria = $base()
            ->join('categoria_costes', 'categoria_costes.id', '=', 'costes.categoria_id')
            ->groupBy('categoria_costes.nombre')
            ->orderByRaw('SUM(costes.importe) DESC')
            ->selectRaw('categoria_costes.nombre, SUM(costes.importe) AS total')
            ->pluck('total', 'nombre')
            ->map(fn ($total) => (float) $total);
        $totalAño = $resumenPorCategoria->sum();
        $totalGenerales = (float) $base()->whereNull('parcela_id')->sum('importe');

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

        $validated = $request->validate(GuardarCosteRequest::reglas($finca), [
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
            'finca' => $finca,
            'parcela' => $parcela,
            'parcelas' => $finca->parcelas()->orderBy('id')->get(),
            'categorias' => CategoriaCoste::orderBy('nombre')->get(),
        ]);
    }
}
