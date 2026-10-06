<x-app-layout>
    <x-slot name="header">
        @include('admin._cabecera', ['titulo' => 'Estaciones meteorológicas'])
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <p class="text-sm text-gray-500">
                {{ number_format($totales['aemet'] ?? 0, 0, ',', '.') }} estaciones de AEMET (se actualizan con la tarea «Estaciones AEMET»)
                y {{ $totales['manual'] ?? 0 }} manuales, cada una de la finca que la creó.
            </p>

            <form method="GET" action="{{ route('admin.estaciones.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="search" name="q" value="{{ $buscar }}" placeholder="Nombre o indicativo…"
                    class="w-56 text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                <select name="fuente" data-autoenviar class="text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="">AEMET y manuales</option>
                    <option value="aemet" @selected($fuente === 'aemet')>Solo AEMET</option>
                    <option value="manual" @selected($fuente === 'manual')>Solo manuales</option>
                </select>
                <input type="hidden" name="en_uso" value="0">
                <label class="flex items-center gap-1.5 text-sm text-gray-600">
                    <input type="checkbox" name="en_uso" value="1" @checked($soloEnUso) data-autoenviar
                        class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                    Solo las vinculadas a alguna finca
                </label>
                <button type="submit" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Buscar</button>
            </form>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 bg-gray-50 border-b border-gray-100">
                                <th class="px-4 py-2 text-left font-medium">Estación</th>
                                <th class="px-4 py-2 text-left font-medium">Fuente</th>
                                <th class="px-4 py-2 text-right font-medium">Fincas</th>
                                <th class="px-4 py-2 text-right font-medium">Días con datos</th>
                                <th class="px-4 py-2 text-left font-medium">Último dato</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($estaciones as $e)
                                @php
                                    // AEMET publica con unos 4 días de retraso: más de una semana sin datos en una estación en uso es un problema
                                    $retrasada = $e->fincas_count > 0 && $e->fuente === 'aemet'
                                        && (!$e->ultimo_dato || \Carbon\Carbon::parse($e->ultimo_dato)->lt(now()->subDays(7)));
                                @endphp
                                <tr>
                                    <td class="px-4 py-2">
                                        <span class="font-medium text-gray-800">{{ $e->nombre }}</span>
                                        @if($e->codigo_externo)<span class="ml-1 text-xs font-mono text-gray-400">{{ $e->codigo_externo }}</span>@endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-600">{{ $e->fuente === 'aemet' ? 'AEMET' : 'Manual' }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ $e->fincas_count }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ number_format($e->datos_count, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap {{ $retrasada ? 'text-amber-700 font-medium' : 'text-gray-600' }}">
                                        {{ $e->ultimo_dato ? \Carbon\Carbon::parse($e->ultimo_dato)->format('d/m/Y') : 'Sin datos' }}
                                        @if($retrasada)<span class="text-xs font-normal">· sin datos recientes</span>@endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">No hay estaciones con ese filtro.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{ $estaciones->links() }}
        </div>
    </div>
</x-app-layout>
