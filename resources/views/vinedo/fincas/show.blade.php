<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('vinedo.fincas.index') }}" wire:navigate class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $finca->paraje ?: $finca->provincia_nombre }}
                </h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('vinedo.fincas.edit', $finca) }}" wire:navigate
                    class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                    Modificar finca
                </a>
                <form method="POST" action="{{ route('vinedo.fincas.destroy', $finca) }}"
                    onsubmit="return confirm('¿Eliminar esta finca y todas sus parcelas? Esta acción no se puede deshacer.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="px-4 py-2 text-sm text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition">
                        Eliminar
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Columna izquierda: ficha + parcelas --}}
                <div class="space-y-6">

                    {{-- Ficha de la finca --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Datos de la finca</h3>
                        <dl class="space-y-2.5">
                            <div class="flex justify-between">
                                <dt class="text-xs text-gray-400">Provincia</dt>
                                <dd class="text-sm font-medium text-gray-800">
                                    {{ $finca->provincia_nombre }}
                                    <span class="text-xs text-gray-400 font-mono ml-1">({{ str_pad($finca->provincia_cod, 2, '0', STR_PAD_LEFT) }})</span>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-xs text-gray-400">Municipio</dt>
                                <dd class="text-sm font-medium text-gray-800 font-mono">
                                    {{ str_pad($finca->municipio_cod, 3, '0', STR_PAD_LEFT) }}
                                </dd>
                            </div>
                            @if($finca->paraje)
                            <div class="flex justify-between">
                                <dt class="text-xs text-gray-400">Paraje</dt>
                                <dd class="text-sm font-medium text-gray-800">{{ $finca->paraje }}</dd>
                            </div>
                            @endif
                            <div class="border-t border-gray-50 pt-2 flex justify-between">
                                <dt class="text-xs text-gray-400">Superficie total</dt>
                                <dd class="text-sm font-semibold text-gray-800">
                                    {{ number_format($finca->parcelas->sum('superficie_ha'), 2) }} ha
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-xs text-gray-400">Parcelas</dt>
                                <dd class="text-sm text-gray-700">{{ $finca->parcelas->count() }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Parcelas --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800 text-sm">
                                Parcelas ({{ $finca->parcelas->count() }})
                            </h3>
                            <a href="{{ route('vinedo.parcelas.create', $finca) }}" wire:navigate
                                class="inline-flex items-center gap-1 text-xs text-green-600 hover:text-green-800 font-medium">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Añadir
                            </a>
                        </div>

                        @if($finca->parcelas->isEmpty())
                            <div class="p-8 text-center">
                                <p class="text-gray-400 text-sm mb-3">Sin parcelas registradas.</p>
                                <a href="{{ route('vinedo.parcelas.create', $finca) }}" wire:navigate
                                    class="text-xs text-green-600 hover:text-green-800 font-medium">
                                    Añadir primera parcela
                                </a>
                            </div>
                        @else
                            @php $sigpacLinks = $parcelasConSigpac->keyBy('id'); @endphp
                            <div class="divide-y divide-gray-50">
                                @foreach($finca->parcelas as $parcela)
                                    <div class="px-4 py-3">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                @if($parcela->poligono && $parcela->parcela_sigpac)
                                                    <p class="text-sm font-medium text-gray-800 font-mono">
                                                        Pol. {{ $parcela->poligono }} · Par. {{ $parcela->parcela_sigpac }}
                                                        @if($parcela->recinto) · Rec. {{ $parcela->recinto }} @endif
                                                    </p>
                                                @else
                                                    <p class="text-sm font-medium text-gray-800">{{ $parcela->nombre }}</p>
                                                @endif
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    {{ $parcela->uso ?? '—' }} ·
                                                    {{ $parcela->variedad?->nombre ?? 'Sin variedad' }} ·
                                                    {{ number_format($parcela->superficie_ha, 2) }} ha
                                                </p>
                                            </div>
                                            <div class="flex items-center gap-2 ml-2 shrink-0">
                                                @if(isset($sigpacLinks[$parcela->id]))
                                                    <a href="{{ $sigpacLinks[$parcela->id]['externoUrl'] }}"
                                                        target="_blank" rel="noopener"
                                                        class="text-xs text-blue-500 hover:text-blue-700"
                                                        title="Ver en visor SIGPAC oficial">
                                                        SIGPAC ↗
                                                    </a>
                                                @endif
                                                <a href="{{ route('vinedo.parcelas.edit', $parcela) }}" wire:navigate
                                                    class="text-xs text-gray-400 hover:text-gray-700">Editar</a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Columna derecha: mapa SIGPAC --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold text-gray-800 text-sm">Visor SIGPAC</h3>
                                @if($parcelasConSigpac->isNotEmpty())
                                    <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">
                                        {{ $parcelasConSigpac->count() }} {{ $parcelasConSigpac->count() === 1 ? 'parcela' : 'parcelas' }} geolocalizadas
                                    </span>
                                @else
                                    <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded">Sin referencias SIGPAC</span>
                                @endif
                            </div>
                            <div class="flex gap-1 text-xs text-gray-400">
                                <button onclick="setLayer('pnoa')" id="btn-pnoa"
                                    class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 transition">Foto aérea</button>
                                <button onclick="setLayer('osm')" id="btn-osm"
                                    class="px-2 py-1 rounded hover:bg-gray-100 transition">Mapa</button>
                            </div>
                        </div>

                        <div class="relative">
                            <div id="sigpac-map" class="w-full" style="height: 480px;"></div>
                            <div id="map-loading" class="absolute inset-0 bg-white/70 flex items-center justify-center hidden">
                                <span class="text-sm text-gray-500">Cargando geometría SIGPAC…</span>
                            </div>
                        </div>

                        @if($parcelasConSigpac->isEmpty())
                            <div class="p-3 bg-yellow-50 border-t border-yellow-100 text-xs text-yellow-700">
                                Añade parcelas con polígono y número de parcela SIGPAC para verlas en el mapa.
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        const parcelasConSigpac = @json($parcelasConSigpac);

        const pnoaLayer = L.tileLayer(
            'https://www.ign.es/wmts/pnoa-ma?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=OI.OrthoimageCoverage&STYLE=default&TILEMATRIXSET=GoogleMapsCompatible&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}&FORMAT=image%2Fpng',
            { attribution: '© IGN España', maxZoom: 20 }
        );
        const osmLayer = L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            { attribution: '© OpenStreetMap', maxZoom: 19 }
        );
        const map = L.map('sigpac-map', { center: [39.5, -3.0], zoom: 8, layers: [pnoaLayer] });

        function setLayer(name) {
            if (name === 'pnoa') {
                map.removeLayer(osmLayer); map.addLayer(pnoaLayer);
                document.getElementById('btn-pnoa').classList.add('bg-gray-100');
                document.getElementById('btn-osm').classList.remove('bg-gray-100');
            } else {
                map.removeLayer(pnoaLayer); map.addLayer(osmLayer);
                document.getElementById('btn-osm').classList.add('bg-gray-100');
                document.getElementById('btn-pnoa').classList.remove('bg-gray-100');
            }
        }

        if (parcelasConSigpac.length > 0) {
            const loading = document.getElementById('map-loading');
            loading.classList.remove('hidden');

            const allBounds = [];
            let failedCount = 0;

            const promises = parcelasConSigpac.map(p =>
                fetch(p.sigpacUrl, { credentials: 'same-origin' })
                    .then(r => {
                        if (!r.ok) throw new Error('HTTP ' + r.status);
                        return r.json();
                    })
                    .then(geojson => {
                        if (!geojson || geojson.error) throw new Error('sin geometría');
                        const layer = L.geoJSON(geojson, {
                            style: { color: '#16a34a', weight: 2.5, fillColor: '#4ade80', fillOpacity: 0.35 }
                        }).addTo(map);
                        layer.bindTooltip(p.nombre, { permanent: false, direction: 'top' });
                        const bounds = layer.getBounds();
                        if (bounds.isValid()) allBounds.push(bounds);
                    })
                    .catch(err => {
                        failedCount++;
                        console.warn('SIGPAC: no se pudo cargar "' + p.nombre + '"', err);
                    })
            );

            Promise.all(promises).then(() => {
                loading.classList.add('hidden');
                if (allBounds.length > 0) {
                    const combined = allBounds.reduce((acc, b) => acc.extend(b));
                    map.fitBounds(combined, { padding: [40, 40], maxZoom: 18 });
                }
                if (failedCount > 0) {
                    console.warn('SIGPAC: ' + failedCount + ' de ' + parcelasConSigpac.length + ' parcela(s) sin geometría en la API.');
                }
            });
        }
    </script>
</x-app-layout>
