<?php

namespace App\Modules\Tratamientos\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\CalendarioFenologico\Models\EstadoFenologico;
use App\Modules\Tratamientos\Http\Requests\GuardarTratamientoRequest;
use App\Modules\Tratamientos\Http\Requests\TratarFincaRequest;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Models\Tratamiento;
use App\Modules\Tratamientos\Services\ImportadorFitosanitarios;
use App\Modules\Tratamientos\Services\TratamientoService;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class TratamientoWebController extends Controller
{
    public function __construct(private readonly TratamientoService $service) {}

    public function index()
    {
        $fincas = Finca::where('user_id', auth()->id())
            ->with('parcelas')
            ->get();

        $tratamientos = Tratamiento::whereHas('parcela.finca', fn ($q) => $q->where('user_id', auth()->id()))
            ->with(['parcela.finca', 'producto', 'coste'])
            ->latest('fecha')
            ->paginate(30);

        return view('tratamientos.index', compact('tratamientos', 'fincas'));
    }

    public function create(Request $request, Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        return view('tratamientos.create', $this->datosFormulario($request, $parcela->finca, collect([$parcela])) + [
            'parcela' => $parcela,
            'finca' => null,
        ]);
    }

    public function edit(Request $request, Tratamiento $tratamiento)
    {
        $parcela = $tratamiento->parcela;
        $this->authorize('update', $parcela->finca);

        $datos = $this->datosFormulario($request, $parcela->finca, collect([$parcela]));
        // El producto del tratamiento se ofrece aunque ya esté cancelado o no figure para el cultivo
        if (! $datos['productos']->contains('id', $tratamiento->producto_id)) {
            $actual = ProductoFitosanitario::whereKey($tratamiento->producto_id)->conPrecioDe($request->user())->with('plazosSeguridad')->first();
            $this->adjuntarPlazosOficiales(collect([$actual]));
            $datos['productos']->prepend($actual);
        }
        $datos['productoElegido'] = old('producto_id', $tratamiento->producto_id);

        return view('tratamientos.create', $datos + [
            'parcela' => $parcela,
            'finca' => null,
            'tratamiento' => $tratamiento,
        ]);
    }

    public function update(GuardarTratamientoRequest $request, Tratamiento $tratamiento)
    {
        $parcela = $tratamiento->parcela;
        $this->authorize('update', $parcela->finca);

        $this->service->actualizar($tratamiento, $request->user(), $request->validated() + ['superficie_tratada_ha' => null]);

        return redirect()->route('vinedo.parcelas.show', $parcela)
            ->with('success', 'Tratamiento actualizado.');
    }

    public function store(GuardarTratamientoRequest $request, Parcela $parcela)
    {
        $this->authorize('update', $parcela->finca);

        $this->service->registrar($parcela, $request->user(), $request->validated());

        return redirect()->route('vinedo.parcelas.show', $parcela)
            ->with('success', 'Tratamiento registrado correctamente.');
    }

    /** Una misma aplicación en varias parcelas de la finca (por defecto, todas). */
    public function createFinca(Request $request, Finca $finca)
    {
        $this->authorize('update', $finca);

        $parcelas = $finca->parcelas()->with('variedad')->orderBy('id')->get();
        if ($parcelas->isEmpty()) {
            return redirect()->route('vinedo.fincas.show', $finca)
                ->with('error', 'La finca no tiene parcelas que tratar.');
        }

        return view('tratamientos.create', $this->datosFormulario($request, $finca, $parcelas) + [
            'parcela' => null,
            'finca' => $finca,
            'parcelas' => $parcelas,
            'elegir' => $request->boolean('elegir'),
        ]);
    }

    public function storeFinca(TratarFincaRequest $request, Finca $finca)
    {
        $this->authorize('update', $finca);

        $validated = $request->validated();
        $parcelas = $finca->parcelas()->with('variedad')->whereIn('id', $validated['parcelas'])->orderBy('id')->get();

        $tratamientos = $this->service->registrarEnParcelas($parcelas, $request->user(), Arr::except($validated, 'parcelas'));

        return redirect()->route('vinedo.fincas.show', $finca)->with('success', $tratamientos->count() === 1
            ? 'Tratamiento registrado en 1 parcela.'
            : "Tratamiento registrado en {$tratamientos->count()} parcelas.");
    }

    public function destroy(Tratamiento $tratamiento)
    {
        $this->authorize('update', $tratamiento->parcela->finca);

        $parcela = $tratamiento->parcela;
        $tratamiento->delete();

        return redirect()->route('vinedo.parcelas.show', $parcela)
            ->with('success', 'Tratamiento eliminado.');
    }

    /** @param Collection<int, Parcela> $parcelas */
    private function datosFormulario(Request $request, Finca $finca, Collection $parcelas): array
    {
        // Productos vigentes autorizados para el cultivo de las parcelas, más los propios del usuario
        $productos = ProductoFitosanitario::visiblesPara($request->user())
            ->paraCultivos($parcelas->map(fn ($p) => ImportadorFitosanitarios::cultivoRegistroDe($p))->all())
            ->where('vigente', true)
            ->orderByRaw('user_id is null')
            ->orderBy('nombre')
            ->select(['id', 'user_id', 'mapa_id', 'nombre', 'numero_registro', 'ingrediente_activo', 'titular',
                'fecha_caducidad', 'cultivos', 'plazo_seguridad_dias', 'dosis_max_l_ha', 'unidad'])
            ->conPrecioDe($request->user())
            ->with('plazosSeguridad')
            ->get();

        // Normalmente aplica la misma persona con el mismo equipo: se proponen los últimos usados
        $ultimo = Tratamiento::whereHas('parcela', fn ($q) => $q->where('finca_id', $finca->id))
            ->whereNotNull('aplicador_ropo')->latest('fecha')->latest('id')->first();

        // Último plazo de seguridad anotado para cada producto en la finca, para proponerlo de nuevo
        $plazosAnteriores = Tratamiento::whereHas('parcela', fn ($q) => $q->where('finca_id', $finca->id))
            ->whereNotNull('plazo_seguridad_dias')
            ->orderBy('fecha')->orderBy('id')
            ->pluck('plazo_seguridad_dias', 'producto_id');

        $productoElegido = old('producto_id', $request->integer('producto') ?: null);
        $this->adjuntarPlazosOficiales($productos);

        // Estadio de la última observación fenológica (una parcela; en la finca se toma el de cada una al guardar)
        $bbchObservado = $parcelas->count() === 1 ? TratamientoService::bbchObservado($parcelas->first(), now()) : null;

        // Lista de estados BBCH para el desplegable, solo si alguna parcela es viña
        $estadosBbch = $parcelas->contains(fn (Parcela $p) => $p->esVina())
            ? EstadoFenologico::orderBy('orden')->get(['codigo_bbch', 'nombre'])
            : collect();

        return compact('productos', 'ultimo', 'plazosAnteriores', 'productoElegido', 'bbchObservado', 'estadosBbch');
    }

    /** Para el formulario: «plazos» = {cultivo: días} oficiales de cada producto (0 si no procede). */
    private function adjuntarPlazosOficiales(Collection $productos): void
    {
        $productos->each(function (ProductoFitosanitario $p) {
            $p->setAttribute('plazos', $p->plazosSeguridad->mapWithKeys(fn ($x) => [$x->cultivo => $x->dias ?? 0]));
            $p->unsetRelation('plazosSeguridad');
        });
    }
}
