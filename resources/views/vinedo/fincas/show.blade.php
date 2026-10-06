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
                    data-confirmar="¿Eliminar esta finca y todas sus parcelas? Esta acción no se puede deshacer.">
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

                    {{-- Tratamientos: lo habitual es tratar la finca entera de una vez --}}
                    @if($finca->parcelas->isNotEmpty())
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                            <h3 class="font-semibold text-gray-800 text-sm mb-3">Tratamiento fitosanitario</h3>
                            <div class="flex flex-col gap-2">
                                <a href="{{ route('tratamientos.finca.create', $finca) }}" wire:navigate
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800 transition">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Tratar toda la finca
                                </a>
                                <a href="{{ route('tratamientos.finca.create', [$finca, 'elegir' => 1]) }}" wire:navigate
                                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-green-700 border border-green-200 rounded-lg hover:bg-green-50 transition">
                                    Elegir parcelas
                                </a>
                            </div>
                            <p class="mt-2 text-xs text-gray-400">Se anota el tratamiento en cada parcela, como exige el cuaderno de explotación.</p>
                        </div>
                    @endif

                    {{-- Gastos: los generales se anotan una vez para la finca, sin repartirlos entre las parcelas --}}
                    @php
                        $generales = $costesAño->whereNull('parcela_id');
                        $deParcelas = $costesAño->whereNotNull('parcela_id');
                        $eur = fn ($v) => number_format((float) $v, 2, ',', '.') . ' €';
                    @endphp
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold text-gray-800 text-sm">Gastos {{ now()->year }}</h3>
                            <a href="{{ route('costes.index', ['finca' => $finca->id]) }}" wire:navigate
                                class="text-xs text-gray-500 hover:text-gray-800">Ver todos</a>
                        </div>
                        <dl class="space-y-1.5 text-sm mb-3">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Generales de la finca</dt>
                                <dd class="font-medium text-gray-800">{{ $eur($generales->sum('importe')) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">De las parcelas</dt>
                                <dd class="font-medium text-gray-800">{{ $eur($deParcelas->sum('importe')) }}</dd>
                            </div>
                            <div class="flex justify-between border-t border-gray-50 pt-1.5">
                                <dt class="text-gray-700 font-medium">Total</dt>
                                <dd class="font-semibold text-gray-900">{{ $eur($costesAño->sum('importe')) }}</dd>
                            </div>
                        </dl>
                        @if($generales->isNotEmpty())
                            <ul class="divide-y divide-gray-50 border-t border-gray-100 mb-3">
                                @foreach($generales->take(5) as $c)
                                    <li class="py-2 flex items-center justify-between gap-2 text-xs">
                                        <div class="min-w-0">
                                            <p class="text-gray-800 truncate">{{ $c->descripcion ?: ($c->categoria?->nombre ?? 'Gasto') }}</p>
                                            <p class="text-gray-400">{{ $c->fecha->format('d/m/Y') }} · {{ $c->categoria?->nombre }}</p>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <span class="font-medium text-gray-700">{{ $eur($c->importe) }}</span>
                                            <form method="POST" action="{{ route('costes.destroy', $c) }}" data-confirmar="¿Eliminar este gasto?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-gray-300 hover:text-red-500" title="Eliminar">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('costes.finca.create', $finca) }}" wire:navigate
                            class="flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium text-green-700 border border-green-200 rounded-lg hover:bg-green-50 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Añadir gasto de la finca
                        </a>
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
                                                <a href="{{ route('tratamientos.create', $parcela) }}" wire:navigate
                                                    class="text-xs text-green-600 hover:text-green-800 font-medium">+ Tratamiento</a>
                                                <a href="{{ route('vinedo.parcelas.show', $parcela) }}" wire:navigate
                                                    class="text-xs text-gray-600 hover:text-gray-900 font-medium">Ver</a>
                                                <a href="{{ route('vinedo.parcelas.edit', $parcela) }}" wire:navigate
                                                    class="px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition">Editar parcela</a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Columna derecha: mapa SIGPAC + meteorología --}}
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden" data-pagina="mapa-finca">
                        <script type="application/json" data-datos>@json(['parcelas' => $parcelasConSigpac])</script>
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
                                <button type="button" data-capa="pnoa" id="btn-pnoa"
                                    class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 transition">Foto aérea</button>
                                <button type="button" data-capa="osm" id="btn-osm"
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

                    {{-- Sección Meteorología --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">

                        {{-- Cabecera con estado de la estación vinculada --}}
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-gray-800 text-sm">Meteorología · últimos 30 días</h3>
                                <div class="flex items-center gap-2">
                                    @if($finca->estacion)
                                        {{-- Importar desde AEMET --}}
                                        @if($finca->estacion->fuente === 'aemet')
                                            <form method="POST" action="{{ route('meteorologia.datos.importar', $finca) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                    </svg>
                                                    Importar AEMET
                                                </button>
                                            </form>
                                        @endif
                                        {{-- Añadir manual (los datos AEMET son compartidos y no se editan) --}}
                                        @unless($finca->estacion->fuente === 'aemet')
                                            <button type="button" data-alternar="#form-meteo"
                                                class="inline-flex items-center gap-1 text-xs text-green-600 hover:text-green-800 font-medium">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Manual
                                            </button>
                                        @endunless
                                    @endif
                                </div>
                            </div>

                            {{-- Estación vinculada --}}
                            @if($finca->estacion)
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
                                            {{ $finca->estacion->fuente === 'aemet' ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $finca->estacion->fuente === 'aemet' ? 'AEMET' : 'Manual' }}
                                        </span>
                                        <span class="text-xs text-gray-600">{{ $finca->estacion->nombre }}</span>
                                        @if($finca->estacion->codigo_externo)
                                            <span class="text-xs text-gray-400 font-mono">{{ $finca->estacion->codigo_externo }}</span>
                                        @endif
                                        @if($distanciaEstacion !== null)
                                            <span class="text-xs text-gray-400">· a {{ number_format($distanciaEstacion, 0, ',', '.') }} km</span>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('meteorologia.datos.desvincular', $finca) }}"
                                        data-confirmar="¿Desvincular la estación?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-gray-400 hover:text-red-500">Desvincular</button>
                                    </form>
                                </div>
                            @else
                                {{-- Sin estación: mostrar selector AEMET o aviso --}}
                                @if($estacionesAemet->isNotEmpty())
                                    <form method="POST" action="{{ route('meteorologia.datos.vincular', $finca) }}"
                                        class="mt-2 flex items-center gap-2">
                                        @csrf
                                        @php $porDistancia = $estacionesAemet->first()?->distancia_km !== null; @endphp
                                        <select name="estacion_id" required
                                            class="flex-1 min-w-0 text-xs border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">
                                                {{ $porDistancia ? 'Selecciona estación AEMET (más cercanas primero)…' : "Selecciona estación AEMET ({$finca->provincia_nombre})…" }}
                                            </option>
                                            @foreach($estacionesAemet as $est)
                                                <option value="{{ $est->id }}">
                                                    @if($porDistancia)
                                                        {{ number_format($est->distancia_km, 0, ',', '.') }} km · {{ $est->nombre }}
                                                        @if((int) $est->provincia_cod !== $finca->provincia_cod)
                                                            ({{ \App\Modules\Vinedo\Models\Finca::getProvincias()[$est->provincia_cod] ?? 'otra provincia' }})
                                                        @endif
                                                    @else
                                                        {{ $est->nombre }} ({{ $est->codigo_externo }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit"
                                            class="px-3 py-1.5 text-xs text-white bg-blue-600 rounded-lg hover:bg-blue-700 whitespace-nowrap">
                                            Vincular
                                        </button>
                                    </form>
                                    <p class="mt-1.5 text-[11px] text-gray-400">
                                        @if($porDistancia)
                                            Distancias desde {{ $finca->coordenadas_origen === 'parcela' ? 'la ubicación SIGPAC de sus parcelas' : 'el polígono catastral de sus parcelas (aproximada)' }}.
                                        @else
                                            No se ha podido ubicar la finca en SIGPAC: revisa la referencia de sus parcelas para ver las estaciones más cercanas.
                                        @endif
                                    </p>
                                @else
                                    <p class="mt-2 text-xs text-gray-400">
                                        Sin estaciones AEMET disponibles para {{ $finca->provincia_nombre }}.
                                        Ejecuta <code class="font-mono bg-gray-100 px-1 rounded">php artisan aemet:importar-estaciones</code> para cargarlas.
                                    </p>
                                    <div class="mt-2">
                                        <button type="button" data-alternar="#form-meteo"
                                            class="text-xs text-green-600 hover:text-green-800 font-medium">
                                            + Añadir dato manual
                                        </button>
                                    </div>
                                @endif
                            @endif
                        </div>

                        {{-- Formulario entrada manual (colapsable) --}}
                        <div id="form-meteo" class="hidden border-b border-gray-100 p-4 bg-gray-50">
                            <p class="text-xs text-gray-500 mb-3 font-medium">Entrada manual de dato diario</p>
                            <form method="POST" action="{{ route('meteorologia.datos.store', $finca) }}">
                                @csrf
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                                    <div class="col-span-2 sm:col-span-1">
                                        <label class="block text-xs text-gray-500 mb-1">Fecha</label>
                                        <input type="date" name="fecha" required
                                            value="{{ old('fecha', now()->toDateString()) }}"
                                            max="{{ now()->toDateString() }}"
                                            class="w-full text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">T. Máx (°C)</label>
                                        <input type="number" name="temp_max" step="0.1" required value="{{ old('temp_max') }}"
                                            class="w-full text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">T. Mín (°C)</label>
                                        <input type="number" name="temp_min" step="0.1" required value="{{ old('temp_min') }}"
                                            class="w-full text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Lluvia (mm)</label>
                                        <input type="number" name="precipitacion_mm" step="0.1" min="0" value="{{ old('precipitacion_mm', 0) }}"
                                            class="w-full text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Humedad (%)</label>
                                        <input type="number" name="humedad_pct" min="0" max="100" value="{{ old('humedad_pct') }}"
                                            class="w-full text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Viento (km/h)</label>
                                        <input type="number" name="viento_kmh" step="0.1" min="0" value="{{ old('viento_kmh') }}"
                                            class="w-full text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                                    </div>
                                </div>
                                @error('temp_min') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                                <div class="mt-3 flex justify-end gap-2">
                                    <button type="button" data-ocultar="#form-meteo"
                                        class="px-3 py-1.5 text-xs text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                                        Cancelar
                                    </button>
                                    <button type="submit"
                                        class="px-3 py-1.5 text-xs text-white bg-green-600 rounded-lg hover:bg-green-700">
                                        Guardar
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- Tabla de datos --}}
                        @if($datosMeteoro->isEmpty())
                            <div class="p-8 text-center">
                                <p class="text-gray-400 text-sm">Sin datos meteorológicos para los últimos 30 días.</p>
                                @if($finca->estacion?->fuente === 'aemet')
                                    <p class="text-xs text-gray-400 mt-1">Pulsa «Importar AEMET» para descargar los datos de la estación.</p>
                                @endif
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs">
                                    <thead>
                                        <tr class="text-gray-400 border-b border-gray-100">
                                            <th class="px-4 py-2 text-left font-medium">Fecha</th>
                                            <th class="px-3 py-2 text-right font-medium">T. Máx</th>
                                            <th class="px-3 py-2 text-right font-medium">T. Mín</th>
                                            <th class="px-3 py-2 text-right font-medium">GDD</th>
                                            <th class="px-3 py-2 text-right font-medium">Lluvia</th>
                                            <th class="px-3 py-2 text-right font-medium">Hum.</th>
                                            <th class="px-3 py-2 text-right font-medium">Viento</th>
                                            <th class="px-2 py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($datosMeteoro->sortByDesc('fecha') as $dato)
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
                                                <td class="px-2 py-2 text-right">
                                                    @unless($finca->estacion?->fuente === 'aemet')
                                                        <form method="POST"
                                                            action="{{ route('meteorologia.datos.destroy', [$finca, $dato]) }}"
                                                            data-confirmar="¿Eliminar este dato?">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="text-gray-300 hover:text-red-500 transition" title="Eliminar">
                                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @endunless
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="border-t border-gray-100 bg-gray-50">
                                        <tr class="text-gray-500">
                                            <td class="px-4 py-2 text-xs font-medium">Acumulado 30 d.</td>
                                            <td colspan="2" class="px-3 py-2"></td>
                                            <td class="px-3 py-2 text-right text-xs font-semibold text-amber-700">
                                                {{ number_format($datosMeteoro->sum(fn($d) => max(0, (($d->temp_max + $d->temp_min) / 2) - 10)), 1) }} GDD
                                            </td>
                                            <td class="px-3 py-2 text-right text-xs font-semibold text-gray-700">
                                                {{ number_format($datosMeteoro->sum('precipitacion_mm'), 1) }} mm
                                            </td>
                                            <td colspan="3" class="px-3 py-2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    </div>

                </div>

            </div>
        </div>
    </div>


</x-app-layout>
