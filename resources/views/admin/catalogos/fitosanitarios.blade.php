@php
    $filtros = ['vigentes' => 'Vigentes', 'sin_ficha' => 'Vigentes sin ficha leída', 'cancelados' => 'Cancelados', 'propios' => 'Propios de usuarios'];
    $n = fn ($v) => number_format((int) $v, 0, ',', '.');
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin._cabecera', ['titulo' => 'Productos fitosanitarios'])
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Vigentes en el Registro</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $n($resumen['vigentes']) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $resumen['actualizado'] ? 'Actualizado el ' . \Carbon\Carbon::parse($resumen['actualizado'])->format('d/m/Y') : 'Sin importar todavía' }}
                    </p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Con plazos de la ficha leídos</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $n($resumen['conFicha']) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $resumen['vigentes'] ? round($resumen['conFicha'] / $resumen['vigentes'] * 100) : 0 }} % de los vigentes
                    </p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Cancelados</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $n($resumen['cancelados']) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Se conservan por los tratamientos antiguos</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-xs text-gray-500">Propios de usuarios</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $n($resumen['propios']) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">Productos que no estaban en el catálogo</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.fitosanitarios.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="search" name="q" value="{{ $buscar }}" placeholder="Nombre, nº de registro o materia activa…"
                    class="w-72 text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                <select name="filtro" data-autoenviar class="text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    @foreach($filtros as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected($filtro === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Buscar</button>
                <span class="ml-auto text-xs text-gray-400">{{ $n($productos->total()) }} productos · los más usados primero</span>
            </form>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 bg-gray-50 border-b border-gray-100">
                                <th class="px-4 py-2 text-left font-medium">Producto</th>
                                <th class="px-4 py-2 text-left font-medium">Nº registro</th>
                                <th class="px-4 py-2 text-left font-medium">Cultivos del catálogo</th>
                                <th class="px-4 py-2 text-right font-medium">Plazos leídos</th>
                                <th class="px-4 py-2 text-right font-medium">Tratamientos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($productos as $p)
                                <tr>
                                    <td class="px-4 py-2">
                                        <span class="font-medium text-gray-800">{{ $p->nombre }}</span>
                                        @unless($p->vigente || $p->user_id)<span class="ml-1 text-xs text-red-600">cancelado</span>@endunless
                                        <p class="text-xs text-gray-500 truncate max-w-md">{{ $p->ingrediente_activo }}</p>
                                        @if($p->user)<p class="text-xs text-gray-400">De {{ $p->user->email }}</p>@endif
                                    </td>
                                    <td class="px-4 py-2 font-mono text-xs text-gray-600 whitespace-nowrap">
                                        @if($p->urlFichaMapa())
                                            <a href="{{ $p->urlFichaMapa() }}" target="_blank" rel="noopener" class="hover:text-green-700 underline decoration-dotted">{{ $p->numero_registro }}</a>
                                        @else
                                            {{ $p->numero_registro ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-xs text-gray-500">{{ $p->cultivos ? implode(', ', $p->cultivos) : '—' }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums {{ $p->ficha_leida_at ? 'text-gray-600' : 'text-gray-400' }}">
                                        {{ $p->ficha_leida_at ? $p->plazos_seguridad_count : 'sin leer' }}
                                    </td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ $n($p->tratamientos_count) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No hay productos con ese filtro.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $productos->links() }}

            <p class="text-xs text-gray-400">
                El catálogo es una copia del Registro de Productos Fitosanitarios del MAPA: no se edita aquí, se actualiza con las tareas
                «Registro de fitosanitarios» y «Fichas de fitosanitarios».
            </p>
        </div>
    </div>
</x-app-layout>
