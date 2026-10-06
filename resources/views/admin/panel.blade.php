@php
    $n = fn ($v, $d = 0) => number_format((float) $v, $d, ',', '.');
    $maxCultivo = max(1, (float) $m['porCultivo']->max());
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin._cabecera', ['titulo' => 'Panel'])
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if($tareasConProblemas->isNotEmpty())
                <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-800">
                    <svg class="h-5 w-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <p>
                        <strong>Tareas con problemas:</strong>
                        {{ $tareasConProblemas->pluck('nombre')->join(', ', ' y ') }}.
                        <a href="{{ route('admin.tareas.index') }}" wire:navigate class="font-medium underline">Ver tareas</a>
                    </p>
                </div>
            @endif

            {{-- Usuarios --}}
            <section>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Usuarios</h3>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach([
                        ['Total', $m['usuarios']['total'], null],
                        ['Activos', $m['usuarios']['activos'], 'han entrado en los últimos 30 días'],
                        ['Nuevos', $m['usuarios']['nuevos'], 'altas en los últimos 30 días'],
                        ['Bloqueados', $m['usuarios']['bloqueados'], null],
                    ] as [$etiqueta, $valor, $nota])
                        <a href="{{ route('admin.usuarios.index', ['filtro' => match ($etiqueta) { 'Activos' => 'activos', 'Bloqueados' => 'bloqueados', default => 'todos' }]) }}" wire:navigate
                            class="pulsable block bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:border-green-200">
                            <p class="text-xs text-gray-500">{{ $etiqueta }}</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $n($valor) }}</p>
                            @if($nota)<p class="text-xs text-gray-400 mt-0.5">{{ $nota }}</p>@endif
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Explotaciones --}}
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="grid grid-cols-3 lg:grid-cols-1 gap-4">
                    @foreach([['Fincas', $n($m['explotaciones']['fincas'])], ['Parcelas', $n($m['explotaciones']['parcelas'])], ['Hectáreas', $n($m['explotaciones']['hectareas'], 1)]] as [$etiqueta, $valor])
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                            <p class="text-xs text-gray-500">{{ $etiqueta }}</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $valor }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="text-sm font-semibold text-gray-700">Superficie por cultivo</h3>
                    @if($m['porCultivo']->isEmpty())
                        <p class="mt-4 text-sm text-gray-400">Todavía no hay parcelas.</p>
                    @else
                        <dl class="mt-4 space-y-3">
                            @foreach($m['porCultivo'] as $cultivo => $ha)
                                <div class="grid grid-cols-[8rem_1fr_auto] items-center gap-3 text-sm">
                                    <dt class="text-gray-600 truncate">{{ $cultivo }}</dt>
                                    <dd class="h-3 bg-gray-100 rounded-r-[4px]" title="{{ $cultivo }}: {{ $n($ha, 2) }} ha">
                                        <div class="h-full bg-green-600 rounded-r-[4px]" style="width: {{ max(1, round($ha / $maxCultivo * 100, 1)) }}%"></div>
                                    </dd>
                                    <dd class="text-gray-700 tabular-nums text-right">{{ $n($ha, 1) }} ha</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </div>
            </section>

            {{-- Actividad --}}
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @include('admin._barras-mes', ['titulo' => 'Tratamientos registrados por mes', 'serie' => $m['tratamientosPorMes'], 'unidad' => 'tratamientos'])
                @include('admin._barras-mes', ['titulo' => 'Altas de usuarios por mes', 'serie' => $m['altasPorMes'], 'unidad' => 'altas'])
            </section>

            <section>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Registros de {{ $m['anio'] }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                    @foreach($m['registrosAnio'] as $etiqueta => $valor)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                            <p class="text-xs text-gray-500">{{ $etiqueta }}</p>
                            <p class="mt-1 text-xl font-semibold text-gray-900 tabular-nums">{{ $n($valor) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <p class="text-xs text-gray-400">
                El backoffice muestra cifras agregadas y datos de las cuentas, no el contenido de las explotaciones de cada usuario.
            </p>
        </div>
    </div>
</x-app-layout>
