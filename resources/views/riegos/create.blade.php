@php use App\Modules\Riegos\Models\Riego; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('riegos.index', ['finca' => $finca->id]) }}" wire:navigate class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Anotar riego</h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $finca->paraje ?: $finca->provincia_nombre }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if($parcelas->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                    <p class="font-medium text-gray-600 mb-1">No hay parcelas de regadío en esta finca</p>
                    <p class="text-sm text-gray-400">Todas son de secano. Si alguna se riega, cambia su uso en «Editar parcela».</p>
                </div>
            @else
                <form method="POST" action="{{ route('riegos.store', $finca) }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="parcela_id" value="Parcela *" />
                            <select id="parcela_id" name="parcela_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="">Selecciona…</option>
                                @foreach($parcelas as $p)
                                    <option value="{{ $p->id }}" data-superficie="{{ (float) $p->superficie_ha }}" {{ old('parcela_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->etiqueta }} — {{ $p->uso }} ({{ number_format($p->superficie_ha, 2, ',', '.') }} ha)
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('parcela_id')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="fecha" value="Fecha *" />
                            <x-text-input id="fecha" name="fecha" type="date" class="mt-1 block w-full" required
                                value="{{ old('fecha', now()->toDateString()) }}" max="{{ now()->toDateString() }}" />
                            <x-input-error :messages="$errors->get('fecha')" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="volumen_m3" value="Volumen (m³) *" />
                            <x-text-input id="volumen_m3" name="volumen_m3" type="number" step="0.01" min="0.01" class="mt-1 block w-full font-mono" required value="{{ old('volumen_m3') }}" />
                            <x-input-error :messages="$errors->get('volumen_m3')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="superficie_ha" value="Superficie regada (ha)" />
                            <x-text-input id="superficie_ha" name="superficie_ha" type="number" step="0.0001" min="0.0001" class="mt-1 block w-full font-mono" value="{{ old('superficie_ha') }}" placeholder="Toda la parcela" />
                            <x-input-error :messages="$errors->get('superficie_ha')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="duracion_horas" value="Duración (horas)" />
                            <x-text-input id="duracion_horas" name="duracion_horas" type="number" step="0.25" min="0.25" class="mt-1 block w-full font-mono" value="{{ old('duracion_horas') }}" />
                            <x-input-error :messages="$errors->get('duracion_horas')" class="mt-1" />
                        </div>
                    </div>
                    <p id="dosis" class="-mt-2 text-xs text-sky-700"></p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="sistema" value="Sistema de riego *" />
                            <select id="sistema" name="sistema" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                @foreach(Riego::SISTEMAS as $valor => $etiqueta)
                                    <option value="{{ $valor }}" {{ old('sistema', $ultimo?->sistema ?? 'goteo') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="origen" value="Origen del agua" />
                            <select id="origen" name="origen" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                <option value="">—</option>
                                @foreach(Riego::ORIGENES as $valor => $etiqueta)
                                    <option value="{{ $valor }}" {{ old('origen', $ultimo?->origen) === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="observaciones" value="Observaciones" />
                        <x-text-input id="observaciones" name="observaciones" type="text" class="mt-1 block w-full" value="{{ old('observaciones') }}" placeholder="Lectura de contador, incidencias…" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <a href="{{ route('riegos.index', ['finca' => $finca->id]) }}" wire:navigate class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
                        <x-primary-button>Anotar riego</x-primary-button>
                    </div>
                </form>

                <script>
                (function () {
                    // Muestra la dosis resultante mientras se escribe (1 mm = 10 m³/ha)
                    const parcela = document.getElementById('parcela_id');
                    const volumen = document.getElementById('volumen_m3');
                    const superficie = document.getElementById('superficie_ha');
                    const salida = document.getElementById('dosis');

                    function actualizar() {
                        const ha = parseFloat(superficie.value) || parseFloat(parcela.selectedOptions[0]?.dataset.superficie);
                        const m3 = parseFloat(volumen.value);
                        salida.textContent = ha > 0 && m3 > 0
                            ? `Dosis: ${(m3 / ha).toLocaleString('es-ES', {maximumFractionDigits: 0})} m³/ha · ${(m3 / ha / 10).toLocaleString('es-ES', {maximumFractionDigits: 1})} mm`
                            : '';
                    }

                    [parcela, volumen, superficie].forEach(el => el.addEventListener('input', actualizar));
                    parcela.addEventListener('change', actualizar);
                    actualizar();
                })();
                </script>
            @endif
        </div>
    </div>
</x-app-layout>
