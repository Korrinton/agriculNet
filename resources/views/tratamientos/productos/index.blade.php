@php
    use App\Modules\Tratamientos\Services\ImportadorFitosanitarios;

    $etiquetaCultivo = fn ($c) => ImportadorFitosanitarios::NOMBRES_CULTIVO[$c] ?? ucfirst($c);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a href="{{ route('tratamientos.index') }}" wire:navigate class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Catálogo de productos fitosanitarios</h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        @if($actualizado)
                            Registro Oficial del MAPA, actualizado el {{ \Carbon\Carbon::parse($actualizado)->format('d/m/Y') }}
                        @else
                            Registro Oficial del MAPA pendiente de importar
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('tratamientos.productos.create') }}" wire:navigate
                class="px-3 py-2 text-sm text-white bg-green-700 rounded-lg hover:bg-green-800 transition">+ Producto propio</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">


            {{-- Filtros --}}
            <form method="GET" action="{{ route('tratamientos.productos.index') }}"
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[14rem]">
                    <x-input-label for="q" value="Buscar" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full text-sm"
                        value="{{ $filtros['q'] ?? '' }}" placeholder="Nombre, nº de registro, materia activa o titular" />
                </div>
                <div>
                    <x-input-label for="cultivo" value="Autorizado en" />
                    <select id="cultivo" name="cultivo" data-autoenviar
                        class="mt-1 block text-sm border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Cualquier cultivo</option>
                        @foreach(array_keys(ImportadorFitosanitarios::CULTIVOS_REGISTRO) as $c)
                            <option value="{{ $c }}" {{ ($filtros['cultivo'] ?? '') === $c ? 'selected' : '' }}>{{ $etiquetaCultivo($c) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="origen" value="Origen" />
                    <select id="origen" name="origen" data-autoenviar
                        class="mt-1 block text-sm border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Todos</option>
                        <option value="registro" {{ ($filtros['origen'] ?? '') === 'registro' ? 'selected' : '' }}>Registro del MAPA</option>
                        <option value="propios" {{ ($filtros['origen'] ?? '') === 'propios' ? 'selected' : '' }}>Mis productos</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600 pb-2">
                    <input type="checkbox" name="cancelados" value="1" data-autoenviar
                        class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                        {{ !empty($filtros['cancelados']) ? 'checked' : '' }}>
                    Incluir cancelados
                </label>
                <x-primary-button class="mb-0.5">Buscar</x-primary-button>
            </form>

            @if($productos->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <p class="font-medium text-gray-500 mb-1">Ningún producto coincide con la búsqueda</p>
                    @if(!$actualizado)
                        <p class="text-sm text-gray-400">El registro oficial se carga con <code class="font-mono">php artisan fitosanitarios:importar</code> (y cada lunes de forma automática).</p>
                    @endif
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 py-2 border-b border-gray-100 text-xs text-gray-400">
                        {{ number_format($productos->total(), 0, ',', '.') }} {{ $productos->total() === 1 ? 'producto' : 'productos' }}
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-400 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 py-3 text-left font-medium">Producto</th>
                                    <th class="px-4 py-3 text-left font-medium">Nº registro</th>
                                    <th class="px-4 py-3 text-left font-medium">Formulado</th>
                                    <th class="px-4 py-3 text-left font-medium" title="Con el plazo de seguridad oficial en días (NP: no procede)">Autorizado en · plazo</th>
                                    <th class="px-4 py-3 text-left font-medium">Estado</th>
                                    <th class="px-4 py-3 text-left font-medium">Mi precio</th>
                                    <th class="px-3 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($productos as $p)
                                    <tr class="hover:bg-gray-50 transition align-top">
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-gray-800">
                                                {{ $p->nombre }}
                                                @if($p->esPropio())
                                                    <span class="ml-1 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide rounded bg-sky-50 text-sky-700">Propio</span>
                                                @endif
                                            </p>
                                            @if($p->titular)
                                                <p class="text-xs text-gray-400">{{ $p->titular }}</p>
                                            @endif
                                            @if($p->esPropio() && ($p->plazo_seguridad_dias !== null || $p->dosis_max_l_ha))
                                                <p class="text-xs text-gray-500">
                                                    @if($p->dosis_max_l_ha) Dosis máx. {{ number_format((float) $p->dosis_max_l_ha, 2, ',', '.') }} {{ $p->unidad }}/ha @endif
                                                    @if($p->plazo_seguridad_dias !== null) · Plazo de seguridad {{ $p->plazo_seguridad_dias }} días @endif
                                                </p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-mono text-gray-600 whitespace-nowrap">{{ $p->numero_registro ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-600 max-w-xs">{{ $p->ingrediente_activo ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            @if($p->cultivos === null)
                                                <span class="text-xs text-gray-300">—</span>
                                            @elseif(empty($p->cultivos))
                                                <span class="text-xs text-gray-400">Otros cultivos</span>
                                            @else
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($p->cultivos as $c)
                                                        @php $ps = $p->plazoOficialPara($c); @endphp
                                                        <span class="px-1.5 py-0.5 text-xs rounded bg-green-50 text-green-700"
                                                            @if($ps !== null) title="Plazo de seguridad oficial: {{ $ps ? $ps . ' días' : 'no procede' }}" @endif>
                                                            {{ $etiquetaCultivo($c) }}@if($ps !== null)<span class="text-green-600/70"> · {{ $ps ? $ps . ' d' : 'NP' }}</span>@endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($p->esPropio())
                                                <span class="text-xs text-gray-400">—</span>
                                            @elseif(!$p->vigente)
                                                <span class="text-xs font-medium text-red-600">Cancelado</span>
                                            @else
                                                <span class="text-xs text-green-700">Vigente</span>
                                                @if($p->fecha_caducidad)
                                                    <p class="text-xs text-gray-400">hasta {{ $p->fecha_caducidad->format('d/m/Y') }}</p>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <form method="POST" action="{{ route('tratamientos.productos.precio', $p) }}" class="flex items-center gap-1">
                                                @csrf @method('PUT')
                                                <div class="relative">
                                                    <input type="number" name="precio" step="0.01" min="0" value="{{ $p->precio }}" placeholder="—"
                                                        aria-label="Precio de {{ $p->nombre }} en euros por {{ $p->unidad === 'kg' ? 'kilo' : 'litro' }}"
                                                        class="w-24 pr-8 py-1 text-sm text-right border-gray-300 rounded-md focus:border-green-500 focus:ring-green-500">
                                                    <span class="absolute inset-y-0 right-2 flex items-center text-xs text-gray-400">€/{{ $p->unidad }}</span>
                                                </div>
                                                <button type="submit" class="text-xs text-green-700 hover:text-green-900 font-medium">Guardar</button>
                                            </form>
                                        </td>
                                        <td class="px-3 py-3 text-right whitespace-nowrap">
                                            @if($p->urlFichaMapa())
                                                <a href="{{ $p->urlFichaMapa() }}" target="_blank" rel="noopener"
                                                    class="text-xs text-green-700 hover:text-green-900 font-medium"
                                                    title="Usos autorizados, dosis y plazos de seguridad">Ficha MAPA ↗</a>
                                            @endif
                                            @can('update', $p)
                                                <a href="{{ route('tratamientos.productos.edit', $p) }}" wire:navigate
                                                    class="text-xs text-gray-500 hover:text-gray-800 font-medium">Editar</a>
                                                @if($p->tratamientos_count === 0)
                                                    <form method="POST" action="{{ route('tratamientos.productos.destroy', $p) }}" class="inline"
                                                        data-confirmar="¿Eliminar este producto de tu catálogo?">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="ml-2 text-xs text-gray-400 hover:text-red-600">Eliminar</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($productos->hasPages())
                        <div class="px-4 py-3 border-t border-gray-100">
                            {{ $productos->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
