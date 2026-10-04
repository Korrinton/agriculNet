<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('vinedo.parcelas.show', $parcela) }}" wire:navigate
                class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo tratamiento</h2>
                <p class="text-xs text-gray-400 mt-0.5">
                    @if($parcela->poligono && $parcela->parcela_sigpac)
                        Pol. {{ $parcela->poligono }} · Par. {{ $parcela->parcela_sigpac }}
                    @else
                        {{ $parcela->nombre }}
                    @endif
                    — {{ $parcela->finca->paraje ?: $parcela->finca->provincia_nombre }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">

                @if($productos->isEmpty())
                    <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm mb-6">
                        No hay productos fitosanitarios en la base de datos. Pide al administrador que los cargue antes de registrar tratamientos.
                    </div>
                @endif

                <form method="POST" action="{{ route('tratamientos.store', $parcela) }}">
                    @csrf

                    <div class="space-y-5">

                        {{-- Producto --}}
                        <div>
                            <x-input-label for="producto_id" value="Producto fitosanitario *" />
                            <select id="producto_id" name="producto_id" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                {{ $productos->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Selecciona un producto…</option>
                                @foreach($productos as $producto)
                                    <option value="{{ $producto->id }}"
                                        data-dosis="{{ $producto->dosis_max_l_ha }}"
                                        data-plazo="{{ $producto->plazo_seguridad_dias }}"
                                        {{ old('producto_id') == $producto->id ? 'selected' : '' }}>
                                        {{ $producto->nombre }}
                                        @if($producto->numero_registro) (Reg. {{ $producto->numero_registro }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('producto_id')" class="mt-1" />

                            {{-- Info del producto seleccionado --}}
                            <div id="producto-info" class="hidden mt-2 p-3 bg-gray-50 rounded-lg text-xs text-gray-600 space-y-1">
                                <p>Ingrediente activo: <span id="info-ingrediente" class="font-medium text-gray-800"></span></p>
                                <p>Dosis máx.: <span id="info-dosis" class="font-medium text-gray-800"></span> l/ha</p>
                                <p>Plazo de seguridad: <span id="info-plazo" class="font-medium text-gray-800"></span> días</p>
                            </div>
                        </div>

                        {{-- Fecha + Dosis --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="fecha" value="Fecha de aplicación *" />
                                <x-text-input id="fecha" name="fecha" type="date"
                                    class="mt-1 block w-full"
                                    value="{{ old('fecha', now()->toDateString()) }}"
                                    max="{{ now()->toDateString() }}"
                                    required />
                                <x-input-error :messages="$errors->get('fecha')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="dosis_l_ha" value="Dosis aplicada (l/ha) *" />
                                <div class="mt-1 relative">
                                    <x-text-input id="dosis_l_ha" name="dosis_l_ha" type="number"
                                        step="0.001" min="0.001"
                                        class="block w-full pr-12"
                                        value="{{ old('dosis_l_ha') }}"
                                        required placeholder="0.000" />
                                    <span class="absolute inset-y-0 right-3 flex items-center text-sm text-gray-400">l/ha</span>
                                </div>
                                <x-input-error :messages="$errors->get('dosis_l_ha')" class="mt-1" />
                            </div>
                        </div>

                        {{-- Superficie tratada --}}
                        <div>
                            <x-input-label for="superficie_tratada_ha" value="Superficie tratada (ha)" />
                            <div class="mt-1 relative">
                                <x-text-input id="superficie_tratada_ha" name="superficie_tratada_ha" type="number"
                                    step="0.0001" min="0.0001" max="{{ $parcela->superficie_ha }}"
                                    class="block w-full pr-10"
                                    value="{{ old('superficie_tratada_ha') }}"
                                    placeholder="{{ number_format($parcela->superficie_ha, 2, '.', '') }}" />
                                <span class="absolute inset-y-0 right-3 flex items-center text-sm text-gray-400">ha</span>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-400">Déjalo vacío si se trató toda la parcela ({{ number_format($parcela->superficie_ha, 2, ',', '.') }} ha).</p>
                            <x-input-error :messages="$errors->get('superficie_tratada_ha')" class="mt-1" />
                        </div>

                        {{-- Motivo / plaga --}}
                        <div>
                            <x-input-label for="motivo" value="Motivo / plaga / enfermedad" />
                            <textarea id="motivo" name="motivo" rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                placeholder="Ej: Oídio, Mildiu, Araña roja…">{{ old('motivo') }}</textarea>
                            <x-input-error :messages="$errors->get('motivo')" class="mt-1" />
                        </div>

                        {{-- Datos del cuaderno de explotación --}}
                        <div class="pt-4 border-t border-gray-100">
                            <p class="text-sm font-semibold text-gray-700">Aplicación</p>
                            <p class="text-xs text-gray-400 mb-3">Obligatorios en el registro de tratamientos del cuaderno de explotación.</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="aplicador_nombre" value="Aplicador" />
                                    <x-text-input id="aplicador_nombre" name="aplicador_nombre" type="text" class="mt-1 block w-full"
                                        value="{{ old('aplicador_nombre', $ultimo?->aplicador_nombre) }}" />
                                    <x-input-error :messages="$errors->get('aplicador_nombre')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="aplicador_ropo" value="Nº ROPO del aplicador" />
                                    <x-text-input id="aplicador_ropo" name="aplicador_ropo" type="text" class="mt-1 block w-full font-mono"
                                        value="{{ old('aplicador_ropo', $ultimo?->aplicador_ropo) }}" />
                                    <x-input-error :messages="$errors->get('aplicador_ropo')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="equipo_roma" value="Nº ROMA del equipo" />
                                    <x-text-input id="equipo_roma" name="equipo_roma" type="text" class="mt-1 block w-full font-mono"
                                        value="{{ old('equipo_roma', $ultimo?->equipo_roma) }}" />
                                    <x-input-error :messages="$errors->get('equipo_roma')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="eficacia" value="Eficacia" />
                                    <select id="eficacia" name="eficacia"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                        <option value="">Sin valorar todavía</option>
                                        @foreach(\App\Modules\Tratamientos\Models\Tratamiento::EFICACIAS as $valor => $etiqueta)
                                            <option value="{{ $valor }}" {{ old('eficacia') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('eficacia')" class="mt-1" />
                                </div>
                            </div>
                            @if($ultimo?->aplicador_ropo)
                                <p class="mt-2 text-xs text-gray-400">Aplicador y equipo rellenados con los del último tratamiento de la finca.</p>
                            @endif
                        </div>

                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('vinedo.parcelas.show', $parcela) }}" wire:navigate
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">
                            Cancelar
                        </a>
                        <x-primary-button>Registrar tratamiento</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const productos = @json($productos->keyBy('id'));

        const select   = document.getElementById('producto_id');
        const infoBox  = document.getElementById('producto-info');
        const infoIng  = document.getElementById('info-ingrediente');
        const infoDosis = document.getElementById('info-dosis');
        const infoPlazo = document.getElementById('info-plazo');

        function updateInfo() {
            const id = select.value;
            const p  = productos[id];
            if (!p) { infoBox.classList.add('hidden'); return; }
            infoIng.textContent   = p.ingrediente_activo  ?? '—';
            infoDosis.textContent = p.dosis_max_l_ha      ?? '—';
            infoPlazo.textContent = p.plazo_seguridad_dias ?? '—';
            infoBox.classList.remove('hidden');
        }

        select.addEventListener('change', updateInfo);
        updateInfo();
    })();
    </script>
</x-app-layout>
