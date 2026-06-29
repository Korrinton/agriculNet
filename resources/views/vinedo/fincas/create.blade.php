<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('vinedo.fincas.index') }}" wire:navigate class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nueva finca</h2>
        </div>
    </x-slot>

    @php
        $usos = ['Secano','Viña en espaldera','Viña en vaso','Olivar','Pistachos'];
        $oldParcelas = old('parcelas', [['parcela_sigpac'=>'','uso'=>'','superficie_ha'=>'']]);
    @endphp

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('vinedo.fincas.store') }}">
                @csrf

                {{-- Ubicación --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
                    @php $finca = new \App\Modules\Vinedo\Models\Finca(); @endphp
                    @include('vinedo.fincas._form')
                </div>

                {{-- Parcelas --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            Parcelas
                        </h3>
                        <span id="parcelas-count-label" class="text-xs text-gray-400"></span>
                    </div>

                    @if($errors->has('parcelas'))
                        <div class="mb-3 p-3 bg-red-50 border border-red-200 text-sm text-red-600 rounded-lg">
                            {{ $errors->first('parcelas') }}
                        </div>
                    @endif

                    <div id="parcelas-list" class="space-y-4">
                        @foreach($oldParcelas as $idx => $p)
                        <div class="parcela-card border border-gray-200 rounded-lg p-4 relative">
                            <button type="button" onclick="removeParcela(this)"
                                class="remove-btn absolute top-3 right-3 text-gray-300 hover:text-red-500 transition"
                                title="Eliminar parcela">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            <p class="parcela-title text-xs font-semibold text-gray-400 uppercase mb-3">Parcela {{ $idx + 1 }}</p>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                                <div>
                                    <x-input-label value="Polígono" />
                                    <x-text-input name="parcelas[{{ $idx }}][poligono]" type="number" min="1"
                                        placeholder="12" class="mt-1 block w-full font-mono"
                                        value="{{ $p['poligono'] ?? '' }}" />
                                    <x-input-error :messages="$errors->get('parcelas.'.$idx.'.poligono')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label value="Nº Parcela" />
                                    <x-text-input name="parcelas[{{ $idx }}][parcela_sigpac]" type="number" min="1"
                                        placeholder="126" class="mt-1 block w-full font-mono"
                                        value="{{ $p['parcela_sigpac'] ?? '' }}" />
                                    <x-input-error :messages="$errors->get('parcelas.'.$idx.'.parcela_sigpac')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label value="Recinto" />
                                    <x-text-input name="parcelas[{{ $idx }}][recinto]" type="number" min="1"
                                        placeholder="1" class="mt-1 block w-full font-mono"
                                        value="{{ $p['recinto'] ?? '' }}" />
                                </div>
                                <div>
                                    <x-input-label value="Superficie *" />
                                    <div class="mt-1 relative">
                                        <x-text-input name="parcelas[{{ $idx }}][superficie_ha]" type="number"
                                            step="0.01" min="0.01" required placeholder="0.00"
                                            class="block w-full pr-8 font-mono"
                                            value="{{ $p['superficie_ha'] ?? '' }}" />
                                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">ha</span>
                                    </div>
                                    <x-input-error :messages="$errors->get('parcelas.'.$idx.'.superficie_ha')" class="mt-1" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <x-input-label value="Uso *" />
                                <select name="parcelas[{{ $idx }}][uso]" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    <option value="">Selecciona...</option>
                                    @foreach($usos as $u)
                                        <option value="{{ $u }}" {{ ($p['uso'] ?? '') === $u ? 'selected' : '' }}>{{ $u }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('parcelas.'.$idx.'.uso')" class="mt-1" />
                            </div>

                            <details class="group">
                                <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600 select-none list-none flex items-center gap-1 mb-2">
                                    <svg class="h-3 w-3 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    Más datos agronómicos
                                </summary>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">Agregado</label>
                                        <x-text-input name="parcelas[{{ $idx }}][agregado]" type="number" min="0" max="99"
                                            placeholder="0" class="mt-1 block w-full font-mono"
                                            value="{{ $p['agregado'] ?? '0' }}" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">Variedad</label>
                                        <select name="parcelas[{{ $idx }}][variedad_id]"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                            <option value="">—</option>
                                            @foreach($variedades as $v)
                                                <option value="{{ $v->id }}" {{ ($p['variedad_id'] ?? '') == $v->id ? 'selected' : '' }}>
                                                    {{ $v->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600">Año plantación</label>
                                        <x-text-input name="parcelas[{{ $idx }}][año_plantacion]" type="number"
                                            min="1900" max="2099" placeholder="2005" class="mt-1 block w-full font-mono"
                                            value="{{ $p['año_plantacion'] ?? '' }}" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600">Sistema de conducción</label>
                                        <x-text-input name="parcelas[{{ $idx }}][sistema_conduccion]" type="text"
                                            placeholder="Ej: Vaso, Espaldera…" class="mt-1 block w-full"
                                            value="{{ $p['sistema_conduccion'] ?? '' }}" />
                                    </div>
                                </div>
                            </details>
                        </div>
                        @endforeach
                    </div>

                    <button type="button" onclick="addParcela()"
                        class="mt-4 w-full flex items-center justify-center gap-2 px-4 py-2.5 border border-dashed border-gray-300 rounded-lg text-sm text-gray-500 hover:border-green-400 hover:text-green-600 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Añadir otra parcela
                    </button>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('vinedo.fincas.index') }}" wire:navigate
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">
                        Cancelar
                    </a>
                    <x-primary-button>Guardar finca</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    {{-- Template para parcelas añadidas dinámicamente --}}
    <template id="parcela-template">
        <div class="parcela-card border border-gray-200 rounded-lg p-4 relative">
            <button type="button" onclick="removeParcela(this)"
                class="remove-btn absolute top-3 right-3 text-gray-300 hover:text-red-500 transition"
                title="Eliminar parcela">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <p class="parcela-title text-xs font-semibold text-gray-400 uppercase mb-3">Parcela ?</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Polígono</label>
                    <input type="number" name="parcelas[IDX][poligono]" min="1" placeholder="12"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nº Parcela</label>
                    <input type="number" name="parcelas[IDX][parcela_sigpac]" min="1" placeholder="126"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Recinto</label>
                    <input type="number" name="parcelas[IDX][recinto]" min="1" placeholder="1"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Superficie *</label>
                    <div class="mt-1 relative">
                        <input type="number" name="parcelas[IDX][superficie_ha]" step="0.01" min="0.01" required
                            placeholder="0.00"
                            class="block w-full pr-8 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono" />
                        <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">ha</span>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700">Uso *</label>
                <select name="parcelas[IDX][uso]" required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Selecciona...</option>
                    @foreach($usos as $u)
                        <option value="{{ $u }}">{{ $u }}</option>
                    @endforeach
                </select>
            </div>
            <details class="group">
                <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600 select-none list-none flex items-center gap-1 mb-2">
                    <svg class="h-3 w-3 transition-transform group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                    Más datos agronómicos
                </summary>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Agregado</label>
                        <input type="number" name="parcelas[IDX][agregado]" min="0" max="99" value="0"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm font-mono" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Variedad</label>
                        <select name="parcelas[IDX][variedad_id]"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">—</option>
                            @foreach($variedades as $v)
                                <option value="{{ $v->id }}">{{ $v->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600">Año plantación</label>
                        <input type="number" name="parcelas[IDX][año_plantacion]" min="1900" max="2099" placeholder="2005"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm font-mono" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600">Sistema de conducción</label>
                        <input type="text" name="parcelas[IDX][sistema_conduccion]" placeholder="Ej: Vaso, Espaldera…"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" />
                    </div>
                </div>
            </details>
        </div>
    </template>

    <script>
        let parcelaIdx = {{ count($oldParcelas) }};

        function updateUI() {
            const cards = document.querySelectorAll('#parcelas-list .parcela-card');
            cards.forEach((card, i) => {
                const title = card.querySelector('.parcela-title');
                if (title) title.textContent = 'Parcela ' + (i + 1);
                const btn = card.querySelector('.remove-btn');
                if (btn) btn.classList.toggle('hidden', cards.length === 1);
            });
            const n = cards.length;
            document.getElementById('parcelas-count-label').textContent = n + (n === 1 ? ' parcela' : ' parcelas');
        }

        function addParcela() {
            const tpl = document.getElementById('parcela-template').content.cloneNode(true);
            tpl.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace(/IDX/g, parcelaIdx);
            });
            document.getElementById('parcelas-list').appendChild(tpl);
            parcelaIdx++;
            updateUI();
        }

        function removeParcela(btn) {
            const cards = document.querySelectorAll('#parcelas-list .parcela-card');
            if (cards.length > 1) {
                btn.closest('.parcela-card').remove();
                updateUI();
            }
        }

        updateUI();
    </script>
</x-app-layout>
