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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo coste</h2>
                <p class="text-xs text-gray-400 mt-0.5">
                    @if($parcela->poligono && $parcela->parcela_sigpac)
                        Pol. {{ $parcela->poligono }} · Par. {{ $parcela->parcela_sigpac }}
                    @else
                        {{ $parcela->nombre }}
                    @endif
                    — {{ $parcela->finca->paraje ?: $parcela->finca->provincia_nombre }}
                    · {{ number_format($parcela->superficie_ha, 4) }} ha
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">

                @if($categorias->isEmpty())
                    <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm mb-6">
                        No hay categorías de coste configuradas. Pide al administrador que las cargue antes de registrar costes.
                    </div>
                @endif

                <form method="POST" action="{{ route('costes.store', $parcela) }}">
                    @csrf

                    <div class="space-y-5">

                        {{-- Categoría --}}
                        <div>
                            <x-input-label for="categoria_id" value="Categoría *" />
                            <select id="categoria_id" name="categoria_id" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                {{ $categorias->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Selecciona una categoría…</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('categoria_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->nombre }}
                                        @if($cat->tipo) ({{ $cat->tipo }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('categoria_id')" class="mt-1" />
                        </div>

                        {{-- Fecha + Importe --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="fecha" value="Fecha *" />
                                <x-text-input id="fecha" name="fecha" type="date"
                                    class="mt-1 block w-full"
                                    value="{{ old('fecha', now()->toDateString()) }}"
                                    max="{{ now()->toDateString() }}"
                                    required />
                                <x-input-error :messages="$errors->get('fecha')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="importe" value="Importe *" />
                                <div class="mt-1 relative">
                                    <x-text-input id="importe" name="importe" type="number"
                                        step="0.01" min="0.01"
                                        class="block w-full pr-8"
                                        value="{{ old('importe') }}"
                                        required placeholder="0.00" />
                                    <span class="absolute inset-y-0 right-3 flex items-center text-sm text-gray-400">€</span>
                                </div>
                                @if($parcela->superficie_ha > 0)
                                    <p id="coste-ha" class="mt-0.5 text-xs text-gray-400"></p>
                                @endif
                                <x-input-error :messages="$errors->get('importe')" class="mt-1" />
                            </div>
                        </div>

                        {{-- Descripción --}}
                        <div>
                            <x-input-label for="descripcion" value="Descripción" />
                            <textarea id="descripcion" name="descripcion" rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                placeholder="Ej: Compra de postes para espaldera, mano de obra poda…">{{ old('descripcion') }}</textarea>
                            <x-input-error :messages="$errors->get('descripcion')" class="mt-1" />
                        </div>

                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('vinedo.parcelas.show', $parcela) }}" wire:navigate
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">
                            Cancelar
                        </a>
                        <x-primary-button>Guardar coste</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($parcela->superficie_ha > 0)
    <script>
    (function () {
        const sup  = {{ $parcela->superficie_ha }};
        const input = document.getElementById('importe');
        const label = document.getElementById('coste-ha');

        function update() {
            const v = parseFloat(input.value);
            label.textContent = isNaN(v) || v <= 0
                ? ''
                : (v / sup).toFixed(2) + ' €/ha';
        }
        input.addEventListener('input', update);
    })();
    </script>
    @endif
</x-app-layout>
