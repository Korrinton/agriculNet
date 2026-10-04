<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('vinedo.fincas.show', $parcela->finca) }}" wire:navigate
                    class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        @if($parcela->poligono && $parcela->parcela_sigpac)
                            Pol. {{ $parcela->poligono }} · Par. {{ $parcela->parcela_sigpac }}
                            @if($parcela->recinto) · Rec. {{ $parcela->recinto }} @endif
                        @else
                            {{ $parcela->nombre }}
                        @endif
                    </h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $parcela->finca->paraje ?: $parcela->finca->provincia_nombre }}
                    </p>
                </div>
            </div>
            <a href="{{ route('vinedo.parcelas.edit', $parcela) }}" wire:navigate
                class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                Editar parcela
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Alertas activas --}}
            @if($parcela->alertas->isNotEmpty())
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-3">
                    <svg class="h-5 w-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div class="text-sm text-amber-800 space-y-1 flex-1">
                        @foreach($parcela->alertas as $alerta)
                            <p>{{ $alerta->mensaje }}</p>
                        @endforeach
                    </div>
                    <a href="{{ route('alertas.index') }}" wire:navigate
                        class="text-xs font-medium text-amber-700 hover:text-amber-900 whitespace-nowrap">
                        Ver alertas →
                    </a>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Columna izquierda: ficha --}}
                <div class="space-y-6">

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Ficha de la parcela</h3>
                        <dl class="space-y-2.5">
                            @if($parcela->poligono && $parcela->parcela_sigpac)
                                <div class="flex justify-between">
                                    <dt class="text-xs text-gray-400">Referencia SIGPAC</dt>
                                    <dd class="text-sm font-medium text-gray-800 font-mono text-right">
                                        {{ str_pad($parcela->finca->provincia_cod, 2, '0', STR_PAD_LEFT) }}
                                        -{{ str_pad($parcela->finca->municipio_cod, 3, '0', STR_PAD_LEFT) }}
                                        -{{ $parcela->agregado ?? 0 }}
                                        -{{ $parcela->poligono }}
                                        -{{ $parcela->parcela_sigpac }}
                                        @if($parcela->recinto)-{{ $parcela->recinto }}@endif
                                    </dd>
                                </div>
                            @endif

                            <div class="flex justify-between">
                                <dt class="text-xs text-gray-400">Uso</dt>
                                <dd class="text-sm text-gray-700">{{ $parcela->uso ?? '—' }}</dd>
                            </div>

                            <div class="flex justify-between">
                                <dt class="text-xs text-gray-400">Superficie</dt>
                                <dd class="text-sm font-semibold text-gray-800">
                                    {{ number_format($parcela->superficie_ha, 4) }} ha
                                </dd>
                            </div>

                            <div class="flex justify-between">
                                <dt class="text-xs text-gray-400">Variedad</dt>
                                <dd class="text-sm text-gray-700">{{ $parcela->variedad?->nombre ?? '—' }}</dd>
                            </div>

                            @if($parcela->año_plantacion)
                                <div class="flex justify-between">
                                    <dt class="text-xs text-gray-400">Año plantación</dt>
                                    <dd class="text-sm text-gray-700">{{ $parcela->año_plantacion }}</dd>
                                </div>
                            @endif

                            @if($parcela->sistema_conduccion)
                                <div class="flex justify-between">
                                    <dt class="text-xs text-gray-400">Conducción</dt>
                                    <dd class="text-sm text-gray-700">{{ $parcela->sistema_conduccion }}</dd>
                                </div>
                            @endif

                            @if($sigpacData)
                                <div class="pt-2 border-t border-gray-50">
                                    <a href="{{ $sigpacData['externoUrl'] }}" target="_blank" rel="noopener"
                                        class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium">
                                        Ver en visor SIGPAC oficial
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Mini-mapa SIGPAC --}}
                    @if($sigpacData)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div id="mini-map" class="w-full" style="height: 220px;"></div>
                        </div>
                    @endif

                </div>

                {{-- Columna derecha: actividad reciente --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- Tratamientos --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800 text-sm">Tratamientos fitosanitarios</h3>
                            <a href="{{ route('tratamientos.create', $parcela) }}" wire:navigate
                                class="inline-flex items-center gap-1 text-xs text-green-600 hover:text-green-800 font-medium">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Nuevo
                            </a>
                        </div>
                        @if($parcela->tratamientos->isEmpty())
                            <div class="p-6 text-center">
                                <p class="text-gray-400 text-sm mb-2">Sin tratamientos registrados.</p>
                                <a href="{{ route('tratamientos.create', $parcela) }}" wire:navigate
                                    class="text-xs text-green-600 hover:text-green-800 font-medium">
                                    Registrar primero
                                </a>
                            </div>
                        @else
                            <div class="divide-y divide-gray-50">
                                @foreach($parcela->tratamientos as $t)
                                    <div class="px-4 py-3 flex items-center justify-between">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-800 truncate">{{ $t->producto?->nombre ?? '—' }}</p>
                                            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $t->motivo ?? '—' }}</p>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0 ml-4">
                                            <div class="text-right">
                                                <p class="text-xs font-mono text-gray-600">{{ $t->fecha->format('d/m/Y') }}</p>
                                                @if($t->dosis_l_ha)
                                                    <p class="text-xs text-gray-400">{{ number_format($t->dosis_l_ha, 2) }} l/ha</p>
                                                @endif
                                            </div>
                                            <form method="POST" action="{{ route('tratamientos.destroy', $t) }}"
                                                onsubmit="return confirm('¿Eliminar este tratamiento?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-gray-300 hover:text-red-500 transition" title="Eliminar">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Costes --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800 text-sm">Costes</h3>
                            <a href="{{ route('costes.create', $parcela) }}" wire:navigate
                                class="inline-flex items-center gap-1 text-xs text-green-600 hover:text-green-800 font-medium">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Nuevo
                            </a>
                        </div>
                        @if($parcela->costes->isEmpty())
                            <div class="p-6 text-center">
                                <p class="text-gray-400 text-sm mb-2">Sin costes registrados.</p>
                                <a href="{{ route('costes.create', $parcela) }}" wire:navigate
                                    class="text-xs text-green-600 hover:text-green-800 font-medium">
                                    Registrar primero
                                </a>
                            </div>
                        @else
                            <div class="divide-y divide-gray-50">
                                @foreach($parcela->costes as $c)
                                    <div class="px-4 py-3 flex items-center justify-between">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-800 truncate">{{ $c->descripcion ?? '—' }}</p>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $c->categoria?->nombre ?? '—' }}</p>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0 ml-4">
                                            <div class="text-right">
                                                <p class="text-sm font-semibold text-gray-800">{{ number_format($c->importe, 2) }} €</p>
                                                <p class="text-xs text-gray-400">{{ $c->fecha->format('d/m/Y') }}</p>
                                            </div>
                                            <form method="POST" action="{{ route('costes.destroy', $c) }}"
                                                onsubmit="return confirm('¿Eliminar este coste?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-gray-300 hover:text-red-500 transition" title="Eliminar">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Fenología (escala BBCH de la vid: solo parcelas de viña) --}}
                    @if($parcela->esVina())
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800 text-sm">Calendario fenológico</h3>
                            <a href="{{ route('fenologia.create', $parcela) }}" wire:navigate
                                class="inline-flex items-center gap-1 text-xs text-green-600 hover:text-green-800 font-medium">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Anotar
                            </a>
                        </div>
                        @if($parcela->registrosFenologicos->isEmpty())
                            <div class="p-6 text-center">
                                <p class="text-gray-400 text-sm mb-2">Sin observaciones fenológicas.</p>
                                <a href="{{ route('fenologia.create', $parcela) }}" wire:navigate
                                    class="text-xs text-green-600 hover:text-green-800 font-medium">
                                    Anotar primera observación
                                </a>
                            </div>
                        @else
                            <div class="divide-y divide-gray-50">
                                @foreach($parcela->registrosFenologicos as $r)
                                    <div class="px-4 py-3 flex items-center justify-between">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-800">
                                                @if($r->estado?->codigo_bbch)
                                                    <span class="font-mono text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded mr-1">
                                                        BBCH {{ $r->estado->codigo_bbch }}
                                                    </span>
                                                @endif
                                                {{ $r->estado?->nombre ?? '—' }}
                                            </p>
                                            @if($r->observaciones)
                                                <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $r->observaciones }}</p>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0 ml-4">
                                            <p class="text-xs font-mono text-gray-500">
                                                {{ $r->fecha_observacion->format('d/m/Y') }}
                                            </p>
                                            <form method="POST" action="{{ route('fenologia.destroy', $r) }}"
                                                onsubmit="return confirm('¿Eliminar esta observación?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-gray-300 hover:text-red-500 transition" title="Eliminar">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    @if($sigpacData)
        @assets
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        @endassets

        <script>
        (function () {
            const apiUrl  = @json($sigpacData['apiUrl']);
            const container = document.getElementById('mini-map');

            const pnoaLayer = L.tileLayer(
                'https://www.ign.es/wmts/pnoa-ma?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=OI.OrthoimageCoverage&STYLE=default&TILEMATRIXSET=GoogleMapsCompatible&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}&FORMAT=image%2Fpng',
                { attribution: '© IGN España', maxZoom: 20 }
            );
            const map = L.map(container, { center: [39.5, -3.0], zoom: 13, layers: [pnoaLayer], zoomControl: false, attributionControl: false });

            fetch(apiUrl, { credentials: 'same-origin' })
                .then(r => r.ok ? r.json() : null)
                .then(geojson => {
                    if (!geojson || geojson.error) return;
                    const layer = L.geoJSON(geojson, {
                        style: { color: '#16a34a', weight: 2.5, fillColor: '#4ade80', fillOpacity: 0.4 }
                    }).addTo(map);
                    const bounds = layer.getBounds();
                    if (bounds.isValid()) map.fitBounds(bounds, { padding: [20, 20], maxZoom: 18 });
                })
                .catch(() => {});
        })();
        </script>
    @endif
</x-app-layout>
