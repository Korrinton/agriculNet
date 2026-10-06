{{-- Cabecera del backoffice: título y menú de secciones. Uso: @include('admin._cabecera', ['titulo' => '…']) --}}
@php
    $secciones = [
        'admin.panel'                => ['Panel', 'admin.panel'],
        'admin.usuarios.index'       => ['Usuarios', 'admin.usuarios.*'],
        'admin.tareas.index'         => ['Tareas', 'admin.tareas.*'],
        'admin.variedades.index'     => ['Variedades', 'admin.variedades.*'],
        'admin.categorias.index'     => ['Categorías de coste', 'admin.categorias.*'],
        'admin.estaciones.index'     => ['Estaciones', 'admin.estaciones.*'],
        'admin.fitosanitarios.index' => ['Fitosanitarios', 'admin.fitosanitarios.*'],
    ];
@endphp
<div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <span class="text-xs font-semibold uppercase tracking-wider text-amber-700 bg-amber-50 border border-amber-200 rounded px-1.5 py-0.5 mr-2 align-middle">Administración</span>
            {{ $titulo }}
        </h2>
        {{ $acciones ?? '' }}
    </div>
    <nav class="-mb-5 flex gap-1 overflow-x-auto text-sm" aria-label="Secciones del backoffice">
        @foreach($secciones as $ruta => [$etiqueta, $patron])
            <a href="{{ route($ruta) }}" wire:navigate
                class="whitespace-nowrap px-3 py-2 border-b-2 transition
                    {{ request()->routeIs($patron) ? 'border-green-600 text-green-800 font-medium' : 'border-transparent text-gray-500 hover:text-gray-800 hover:border-gray-300' }}"
                @if(request()->routeIs($patron)) aria-current="page" @endif>
                {{ $etiqueta }}
            </a>
        @endforeach
    </nav>
</div>
