<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Meteorología</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">



            @if($fincas->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                    <svg class="h-12 w-12 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                    </svg>
                    <p class="font-medium text-gray-500">Sin fincas registradas</p>
                    <p class="text-sm mt-1">
                        <a href="{{ route('vinedo.fincas.create') }}" wire:navigate class="text-green-600 hover:underline">Crea tu primera finca</a>
                        para empezar a registrar datos meteorológicos.
                    </p>
                </div>
            @else
                @foreach($fincas as $finca)
                    @php
                        $datos  = $finca->estacion?->datos ?? collect();
                        $ultimo = $datos->last();
                        $gddAcum = $datos->sum(fn($d) => max(0, (($d->temp_max + $d->temp_min) / 2) - 10));
                        $lluviaAcum = $datos->sum('precipitacion_mm');
                    @endphp

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

                        {{-- Cabecera de la finca --}}
                        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('vinedo.fincas.show', $finca) }}" wire:navigate
                                    class="font-semibold text-gray-800 hover:text-green-700 transition">
                                    {{ $finca->paraje ?: $finca->provincia_nombre }}
                                </a>
                                <span class="text-xs text-gray-400">{{ $finca->provincia_nombre }}</span>

                                @if($finca->estacion)
                                    <span class="inline-flex items-center text-xs px-2 py-0.5 rounded-full
                                        {{ $finca->estacion->fuente === 'aemet' ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $finca->estacion->fuente === 'aemet' ? 'AEMET' : 'Manual' }}
                                        · {{ $finca->estacion->nombre }}
                                    </span>
                                @else
                                    <span class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Sin estación</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-2">
                                @if($finca->estacion?->fuente === 'aemet')
                                    <form method="POST" action="{{ route('meteorologia.datos.importar', $finca) }}">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            Importar
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('vinedo.fincas.show', $finca) }}" wire:navigate
                                    class="text-xs text-gray-400 hover:text-gray-600">Ver finca →</a>
                            </div>
                        </div>

                        {{-- Previsión AEMET del municipio --}}
                        @php $prevision = $predicciones->get("{$finca->provincia_cod}-{$finca->municipio_cod}", collect()); @endphp
                        @if($prevision->isNotEmpty())
                            <div class="px-5 py-3 border-b border-gray-100 bg-sky-50/40">
                                <p class="text-xs text-gray-400 mb-2">
                                    Previsión AEMET · municipio {{ $finca->codigo_ine }}
                                    @if($prevision->first()->elaborado_at)
                                        · actualizada {{ $prevision->first()->elaborado_at->timezone('Europe/Madrid')->format('d/m H:i') }}
                                    @endif
                                </p>
                                <div class="grid grid-cols-4 sm:grid-cols-7 gap-2">
                                    @foreach($prevision->take(7) as $p)
                                        @php $helada = $p->temp_min !== null && (float) $p->temp_min <= \App\Modules\Alertas\Services\GeneradorAlertas::PREVISION_HELADA_TEMP_MIN; @endphp
                                        <div class="rounded-lg px-2 py-2 text-center text-xs {{ $helada ? 'bg-blue-100 ring-1 ring-blue-300' : 'bg-white ring-1 ring-gray-100' }}"
                                            title="{{ $p->estado_cielo }}">
                                            <p class="font-medium text-gray-700 capitalize">{{ $p->fecha->locale('es')->isoFormat('ddd D') }}</p>
                                            <p class="mt-1">
                                                <span class="text-red-500">{{ $p->temp_max !== null ? number_format($p->temp_max, 0) . '°' : '—' }}</span>
                                                <span class="text-gray-300">/</span>
                                                <span class="{{ $helada ? 'text-blue-700 font-semibold' : 'text-blue-500' }}">{{ $p->temp_min !== null ? number_format($p->temp_min, 0) . '°' : '—' }}</span>
                                            </p>
                                            <p class="mt-0.5 {{ ($p->prob_precipitacion ?? 0) >= 50 ? 'text-sky-700 font-medium' : 'text-gray-400' }}">
                                                {{ $p->prob_precipitacion !== null ? $p->prob_precipitacion . '% lluvia' : '' }}
                                            </p>
                                            @if($helada)
                                                <p class="mt-0.5 text-[10px] font-semibold text-blue-700 uppercase tracking-wide">Helada</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($datos->isEmpty())
                            <div class="px-5 py-6 text-center text-sm text-gray-400">
                                Sin datos meteorológicos.
                                @if(! $finca->estacion)
                                    <a href="{{ route('vinedo.fincas.show', $finca) }}" wire:navigate
                                        class="text-green-600 hover:underline ml-1">Vincular estación →</a>
                                @elseif($finca->estacion->fuente === 'aemet')
                                    Pulsa «Importar» para descargar datos de AEMET.
                                @endif
                            </div>
                        @else
                            {{-- Resumen en KPIs --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-y sm:divide-y-0 divide-gray-100">
                                <div class="px-5 py-4">
                                    <p class="text-xs text-gray-400 mb-1">Último dato</p>
                                    <p class="text-sm font-semibold text-gray-800">{{ $ultimo->fecha->format('d/m/Y') }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        <span class="text-red-500">{{ number_format($ultimo->temp_max, 1) }}°</span>
                                        /
                                        <span class="text-blue-500">{{ number_format($ultimo->temp_min, 1) }}°</span>
                                    </p>
                                </div>
                                <div class="px-5 py-4">
                                    <p class="text-xs text-gray-400 mb-1">GDD acumulados (30 d.)</p>
                                    <p class="text-sm font-semibold text-amber-700">{{ number_format($gddAcum, 1) }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">base 10 °C</p>
                                </div>
                                <div class="px-5 py-4">
                                    <p class="text-xs text-gray-400 mb-1">Lluvia acumulada (30 d.)</p>
                                    <p class="text-sm font-semibold text-blue-700">{{ number_format($lluviaAcum, 1) }} mm</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $datos->count() }} días con datos</p>
                                </div>
                                <div class="px-5 py-4">
                                    <p class="text-xs text-gray-400 mb-1">T. máx. del período</p>
                                    <p class="text-sm font-semibold text-red-600">{{ number_format($datos->max('temp_max'), 1) }}°</p>
                                    <p class="text-xs text-gray-400 mt-0.5">mín: {{ number_format($datos->min('temp_min'), 1) }}°</p>
                                </div>
                            </div>

                            {{-- Tabla de los últimos 10 días --}}
                            <div class="overflow-x-auto border-t border-gray-100">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-gray-400 border-b border-gray-100 bg-gray-50">
                                            <th class="px-4 py-2 text-left font-medium">Fecha</th>
                                            <th class="px-3 py-2 text-right font-medium">T. Máx</th>
                                            <th class="px-3 py-2 text-right font-medium">T. Mín</th>
                                            <th class="px-3 py-2 text-right font-medium">GDD</th>
                                            <th class="px-3 py-2 text-right font-medium">Lluvia</th>
                                            <th class="px-3 py-2 text-right font-medium">Humedad</th>
                                            <th class="px-3 py-2 text-right font-medium">Viento</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($datos->sortByDesc('fecha')->take(10) as $dato)
                                            @php $gdd = max(0, (($dato->temp_max + $dato->temp_min) / 2) - 10); @endphp
                                            <tr class="hover:bg-gray-50 transition">
                                                <td class="px-4 py-2 text-gray-700 font-medium">{{ $dato->fecha->format('d/m/Y') }}</td>
                                                <td class="px-3 py-2 text-right text-red-600">{{ number_format($dato->temp_max, 1) }}°</td>
                                                <td class="px-3 py-2 text-right text-blue-600">{{ number_format($dato->temp_min, 1) }}°</td>
                                                <td class="px-3 py-2 text-right text-amber-600 font-medium">{{ number_format($gdd, 1) }}</td>
                                                <td class="px-3 py-2 text-right text-gray-600">
                                                    {{ $dato->precipitacion_mm !== null ? number_format($dato->precipitacion_mm, 1) . ' mm' : '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-right text-gray-600">
                                                    {{ $dato->humedad_pct !== null ? $dato->humedad_pct . '%' : '—' }}
                                                </td>
                                                <td class="px-3 py-2 text-right text-gray-600">
                                                    {{ $dato->viento_kmh !== null ? number_format($dato->viento_kmh, 0) . ' km/h' : '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if($datos->count() > 10)
                                    <div class="px-4 py-2 text-xs text-gray-400 text-right border-t border-gray-50">
                                        Mostrando 10 de {{ $datos->count() }} días ·
                                        <a href="{{ route('vinedo.fincas.show', $finca) }}" wire:navigate class="text-green-600 hover:underline">Ver todos</a>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif

        </div>
    </div>
</x-app-layout>
