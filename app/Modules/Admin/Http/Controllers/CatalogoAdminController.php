<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Meteorologia\Models\DatoMeteorologico;
use App\Modules\Meteorologia\Models\EstacionMeteorologica;
use App\Modules\Tratamientos\Models\ProductoFitosanitario;
use Illuminate\Http\Request;

/**
 * Catálogos que llegan de fuentes externas (AEMET, Registro del MAPA): se consultan aquí y se
 * actualizan con sus tareas, no a mano.
 */
class CatalogoAdminController extends Controller
{
    public function estaciones(Request $request)
    {
        $fuente = in_array($request->query('fuente'), ['aemet', 'manual'], true) ? $request->query('fuente') : null;
        $soloEnUso = $request->boolean('en_uso', true);
        $buscar = trim((string) $request->query('q', ''));

        $estaciones = EstacionMeteorologica::query()
            ->withCount(['fincas', 'datos'])
            ->addSelect(['ultimo_dato' => DatoMeteorologico::selectRaw('max(fecha)')->whereColumn('estacion_id', 'estaciones_meteorologicas.id')])
            ->when($fuente, fn ($q) => $q->where('fuente', $fuente))
            ->when($soloEnUso, fn ($q) => $q->whereHas('fincas'))
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereRaw('nombre ilike ?', ["%{$buscar}%"])->orWhere('codigo_externo', $buscar)))
            ->orderByDesc('fincas_count')->orderBy('nombre')
            ->paginate(30)->withQueryString();

        return view('admin.catalogos.estaciones', [
            'estaciones' => $estaciones,
            'fuente'     => $fuente,
            'soloEnUso'  => $soloEnUso,
            'buscar'     => $buscar,
            'totales'    => EstacionMeteorologica::selectRaw('fuente, count(*) as total')->groupBy('fuente')->pluck('total', 'fuente'),
        ]);
    }

    public function fitosanitarios(Request $request)
    {
        $buscar = trim((string) $request->query('q', ''));
        $filtro = in_array($request->query('filtro'), ['vigentes', 'cancelados', 'propios', 'sin_ficha'], true) ? $request->query('filtro') : 'vigentes';

        $productos = ProductoFitosanitario::query()
            ->withCount(['tratamientos', 'plazosSeguridad'])
            ->with('user:id,email')
            ->when($filtro === 'vigentes', fn ($q) => $q->whereNull('user_id')->where('vigente', true))
            ->when($filtro === 'cancelados', fn ($q) => $q->whereNull('user_id')->where('vigente', false))
            ->when($filtro === 'propios', fn ($q) => $q->whereNotNull('user_id'))
            ->when($filtro === 'sin_ficha', fn ($q) => $q->whereNull('user_id')->where('vigente', true)->whereNull('ficha_leida_at'))
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereRaw('nombre ilike ?', ["%{$buscar}%"])
                ->orWhereRaw('numero_registro ilike ?', ["%{$buscar}%"])
                ->orWhereRaw('ingrediente_activo ilike ?', ["%{$buscar}%"])))
            ->orderByDesc('tratamientos_count')->orderBy('nombre')
            ->paginate(30)->withQueryString();

        $registro = ProductoFitosanitario::whereNull('user_id');

        return view('admin.catalogos.fitosanitarios', [
            'productos' => $productos,
            'buscar'    => $buscar,
            'filtro'    => $filtro,
            'resumen'   => [
                'vigentes'   => (clone $registro)->where('vigente', true)->count(),
                'cancelados' => (clone $registro)->where('vigente', false)->count(),
                'conFicha'   => (clone $registro)->where('vigente', true)->whereNotNull('ficha_leida_at')->count(),
                'propios'    => ProductoFitosanitario::whereNotNull('user_id')->count(),
                'actualizado' => (clone $registro)->max('updated_at'),
            ],
        ]);
    }
}
