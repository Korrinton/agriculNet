<?php

namespace App\Modules\Tratamientos\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use App\Modules\Tratamientos\Services\ImportadorFitosanitarios;
use App\Modules\Vinedo\Models\Finca;
use App\Modules\Vinedo\Models\Parcela;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class ProductoFitosanitarioWebController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q'          => 'nullable|string|max:100',
            'cultivo'    => ['nullable', Rule::in(array_keys(ImportadorFitosanitarios::CULTIVOS_REGISTRO))],
            'origen'     => 'nullable|in:registro,propios',
            'cancelados' => 'nullable|boolean',
        ]);

        $productos = ProductoFitosanitario::visiblesPara($request->user())
            ->when($filtros['q'] ?? null, function ($query, $q) {
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $query->where(fn ($w) => $w->where('nombre', 'ilike', $like)
                    ->orWhere('numero_registro', 'ilike', $like)
                    ->orWhere('ingrediente_activo', 'ilike', $like)
                    ->orWhere('titular', 'ilike', $like));
            })
            ->when($filtros['cultivo'] ?? null, fn ($query, $cultivo) => $query->paraCultivo($cultivo))
            ->when(($filtros['origen'] ?? null) === 'registro', fn ($query) => $query->whereNull('user_id'))
            ->when(($filtros['origen'] ?? null) === 'propios', fn ($query) => $query->whereNotNull('user_id'))
            ->when(empty($filtros['cancelados']), fn ($query) => $query->where('vigente', true))
            ->conPrecioDe($request->user())
            ->with('plazosSeguridad')
            ->withCount('tratamientos')
            ->orderByRaw('user_id is null')   // los propios primero
            ->orderBy('nombre')
            ->paginate(30)
            ->withQueryString();

        $actualizado = ProductoFitosanitario::whereNotNull('mapa_id')->max('updated_at');

        return view('tratamientos.productos.index', compact('productos', 'filtros', 'actualizado'));
    }

    public function create(Request $request)
    {
        return view('tratamientos.productos.form', [
            'producto' => new ProductoFitosanitario(),
            'origen'   => $this->tratamientoDeOrigen($request),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $producto = ProductoFitosanitario::create(Arr::except($datos, 'precio') + ['user_id' => $request->user()->id]);
        $producto->fijarPrecio($request->user(), $datos['precio'] ?? null);

        // Dado de alta desde el formulario de un tratamiento: se vuelve a él con el producto elegido
        if ($origen = $this->tratamientoDeOrigen($request)) {
            return redirect()->to($origen['url'] . '?producto=' . $producto->id)
                ->with('success', "Producto «{$producto->nombre}» añadido a tu catálogo.");
        }

        return redirect()->route('tratamientos.productos.index', ['origen' => 'propios'])
            ->with('success', "Producto «{$producto->nombre}» añadido a tu catálogo.");
    }

    public function edit(Request $request, ProductoFitosanitario $producto)
    {
        $this->authorize('update', $producto);

        $producto->precio = $producto->precios()->where('user_id', $request->user()->id)->value('precio');

        return view('tratamientos.productos.form', ['producto' => $producto, 'origen' => null]);
    }

    public function update(Request $request, ProductoFitosanitario $producto)
    {
        $this->authorize('update', $producto);

        $datos = $this->validar($request);
        $producto->update(Arr::except($datos, 'precio'));
        $producto->fijarPrecio($request->user(), $datos['precio'] ?? null);

        return redirect()->route('tratamientos.productos.index', ['origen' => 'propios'])
            ->with('success', 'Producto actualizado.');
    }

    public function destroy(ProductoFitosanitario $producto)
    {
        $this->authorize('delete', $producto);

        if ($producto->tratamientos()->exists()) {
            return back()->with('error', 'No se puede eliminar un producto con tratamientos registrados.');
        }

        $producto->delete();

        return redirect()->route('tratamientos.productos.index', ['origen' => 'propios'])
            ->with('success', 'Producto eliminado.');
    }

    /** Precio que paga el usuario por un producto (del registro o propio); vacío lo borra. */
    public function precio(Request $request, ProductoFitosanitario $producto)
    {
        $this->authorize('fijarPrecio', $producto);

        $datos = $request->validate(['precio' => 'nullable|numeric|min:0|max:99999']);
        $producto->fijarPrecio($request->user(), $datos['precio'] ?? null);

        return back()->with('success', isset($datos['precio'])
            ? "Precio de «{$producto->nombre}» guardado."
            : "Precio de «{$producto->nombre}» borrado.");
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre'               => 'required|string|max:255',
            'numero_registro'      => 'nullable|string|max:50',
            'ingrediente_activo'   => 'nullable|string|max:255',
            'titular'              => 'nullable|string|max:255',
            'plazo_seguridad_dias' => 'nullable|integer|min:0|max:255',
            'dosis_max_l_ha'       => 'nullable|numeric|gt:0|max:999',
            'precio'               => 'nullable|numeric|min:0|max:99999',
            'unidad'               => ['sometimes', 'required', Rule::in(array_keys(ProductoFitosanitario::UNIDADES))],
        ]);
    }

    /**
     * Formulario de tratamiento (de una parcela o de la finca entera) desde el que se da de alta el
     * producto, para volver a él con el producto elegido.
     *
     * @return array{campo: string, id: int, url: string}|null
     */
    private function tratamientoDeOrigen(Request $request): ?array
    {
        if ($parcela = Parcela::with('finca')->find($request->integer('parcela_id') ?: null)) {
            return $request->user()->can('update', $parcela->finca)
                ? ['campo' => 'parcela_id', 'id' => $parcela->id, 'url' => route('tratamientos.create', $parcela)]
                : null;
        }

        if ($finca = Finca::find($request->integer('finca_id') ?: null)) {
            return $request->user()->can('update', $finca)
                ? ['campo' => 'finca_id', 'id' => $finca->id, 'url' => route('tratamientos.finca.create', $finca)]
                : null;
        }

        return null;
    }
}
