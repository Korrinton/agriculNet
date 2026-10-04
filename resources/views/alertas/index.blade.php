@php
    $estilos = [
        'critical' => ['borde' => 'border-l-red-500',   'badge' => 'bg-red-50 text-red-700 ring-red-200',     'icono' => 'text-red-500'],
        'warning'  => ['borde' => 'border-l-amber-400', 'badge' => 'bg-amber-50 text-amber-700 ring-amber-200', 'icono' => 'text-amber-500'],
        'info'     => ['borde' => 'border-l-sky-400',   'badge' => 'bg-sky-50 text-sky-700 ring-sky-200',     'icono' => 'text-sky-500'],
    ];
    $tipos = [
        'plazo_seguridad'     => 'Plazo de seguridad',
        'fin_plazo_seguridad' => 'Fin de plazo de seguridad',
        'dosis_excedida'      => 'Dosis excedida',
        'helada'              => 'Helada',
        'prevision_helada'    => 'Previsión de helada',
        'riesgo_mildiu'       => 'Riesgo de mildiu',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Alertas
                @if($noLeidas > 0)
                    <span class="ml-2 align-middle text-xs font-medium px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">
                        {{ $noLeidas }} sin leer
                    </span>
                @endif
            </h2>
            @if($noLeidas > 0)
                <form method="POST" action="{{ route('alertas.leer-todas') }}">
                    @csrf
                    <button type="submit"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        Marcar todas como leídas
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if(session('success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filtros --}}
            <form method="GET" action="{{ route('alertas.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-lg bg-gray-100 p-0.5 text-sm">
                    <a href="{{ route('alertas.index', array_filter(['nivel' => $nivel])) }}" wire:navigate
                        class="px-3 py-1.5 rounded-md transition {{ $soloNoLeidas ? 'bg-white shadow-sm text-gray-800 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                        Sin leer
                    </a>
                    <a href="{{ route('alertas.index', array_filter(['estado' => 'todas', 'nivel' => $nivel])) }}" wire:navigate
                        class="px-3 py-1.5 rounded-md transition {{ !$soloNoLeidas ? 'bg-white shadow-sm text-gray-800 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                        Todas
                    </a>
                </div>
                @unless($soloNoLeidas)
                    <input type="hidden" name="estado" value="todas">
                @endunless
                <select name="nivel" onchange="this.form.submit()"
                    class="text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="">Todos los niveles</option>
                    @foreach($niveles as $valor => $etiqueta)
                        <option value="{{ $valor }}" {{ $nivel === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </form>

            @if($alertas->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <svg class="h-12 w-12 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if($soloNoLeidas)
                        <p class="font-medium text-gray-500 mb-1">No tienes alertas pendientes</p>
                        <p class="text-sm text-gray-400">Aquí aparecerán los plazos de seguridad y avisos de dosis al registrar tratamientos.</p>
                    @else
                        <p class="font-medium text-gray-500">No hay alertas que coincidan con el filtro</p>
                    @endif
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden divide-y divide-gray-50">
                    @foreach($alertas as $alerta)
                        @php $e = $estilos[$alerta->nivel] ?? $estilos['info']; @endphp
                        <div class="flex items-start gap-4 px-4 py-4 border-l-4 {{ $e['borde'] }} {{ $alerta->leida ? 'bg-gray-50/60' : '' }}">
                            <svg class="h-5 w-5 shrink-0 mt-0.5 {{ $alerta->leida ? 'text-gray-300' : $e['icono'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="text-[11px] font-medium px-1.5 py-0.5 rounded ring-1 {{ $e['badge'] }}">
                                        {{ $niveles[$alerta->nivel] ?? $alerta->nivel }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ $tipos[$alerta->tipo] ?? str_replace('_', ' ', ucfirst($alerta->tipo)) }}</span>
                                    <span class="text-xs text-gray-300">·</span>
                                    <span class="text-xs text-gray-400" title="{{ $alerta->created_at->format('d/m/Y H:i') }}">
                                        {{ $alerta->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="text-sm {{ $alerta->leida ? 'text-gray-500' : 'text-gray-800' }}">{{ $alerta->mensaje }}</p>
                                @if($alerta->parcela)
                                    <a href="{{ route('vinedo.parcelas.show', $alerta->parcela) }}" wire:navigate
                                        class="inline-block mt-1 text-xs text-green-600 hover:text-green-800">
                                        @if($alerta->parcela->poligono && $alerta->parcela->parcela_sigpac)
                                            Pol. {{ $alerta->parcela->poligono }} · Par. {{ $alerta->parcela->parcela_sigpac }}
                                        @else
                                            {{ $alerta->parcela->nombre }}
                                        @endif
                                        — {{ $alerta->parcela->finca->paraje ?: $alerta->parcela->finca->provincia_nombre }}
                                    </a>
                                @endif
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                @unless($alerta->leida)
                                    <form method="POST" action="{{ route('alertas.leer', $alerta) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                            class="p-1.5 rounded text-gray-300 hover:text-green-600 hover:bg-green-50 transition" title="Marcar como leída">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('alertas.destroy', $alerta) }}"
                                    onsubmit="return confirm('¿Eliminar esta alerta?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-1.5 rounded text-gray-300 hover:text-red-500 hover:bg-red-50 transition" title="Eliminar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach

                    @if($alertas->hasPages())
                        <div class="px-4 py-3">
                            {{ $alertas->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
