<div class="space-y-6">

    {{-- Referencia SIGPAC --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
            <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            Referencia SIGPAC
            <span class="text-xs font-normal text-gray-400">(provincia {{ str_pad($finca->provincia_cod, 2, '0', STR_PAD_LEFT) }} / municipio {{ str_pad($finca->municipio_cod, 3, '0', STR_PAD_LEFT) }})</span>
        </h3>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

            <div>
                <x-input-label for="poligono" value="Polígono" />
                <x-text-input id="poligono" name="poligono" type="number" min="1"
                    class="mt-1 block w-full font-mono"
                    value="{{ old('poligono', $parcela->poligono ?? '') }}" placeholder="12" />
                <x-input-error :messages="$errors->get('poligono')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="parcela_sigpac" value="Nº Parcela" />
                <x-text-input id="parcela_sigpac" name="parcela_sigpac" type="number" min="1"
                    class="mt-1 block w-full font-mono"
                    value="{{ old('parcela_sigpac', $parcela->parcela_sigpac ?? '') }}" placeholder="126" />
                <x-input-error :messages="$errors->get('parcela_sigpac')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="recinto" value="Recinto" />
                <x-text-input id="recinto" name="recinto" type="number" min="1"
                    class="mt-1 block w-full font-mono"
                    value="{{ old('recinto', $parcela->recinto ?? '') }}" placeholder="1" />
                <x-input-error :messages="$errors->get('recinto')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="agregado" value="Agregado" />
                <x-text-input id="agregado" name="agregado" type="number" min="0" max="99"
                    class="mt-1 block w-full font-mono"
                    value="{{ old('agregado', $parcela->agregado ?? 0) }}" placeholder="0" />
                <p class="mt-0.5 text-xs text-gray-400">Normalmente 0</p>
                <x-input-error :messages="$errors->get('agregado')" class="mt-1" />
            </div>

        </div>
    </div>

    <hr class="border-gray-100">

    {{-- Datos de la parcela --}}
    <div>
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Datos de la parcela</h3>

        @php
            $usos = ['Secano','Viña en espaldera','Viña en vaso','Olivar','Pistachos'];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div>
                <x-input-label for="uso" value="Uso *" />
                <select id="uso" name="uso" required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Selecciona...</option>
                    @foreach($usos as $u)
                        <option value="{{ $u }}" {{ old('uso', $parcela->uso ?? '') === $u ? 'selected' : '' }}>{{ $u }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('uso')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="superficie_ha" value="Superficie *" />
                <div class="mt-1 relative">
                    <x-text-input id="superficie_ha" name="superficie_ha" type="number" step="0.01" min="0.01"
                        class="block w-full pr-10 font-mono"
                        value="{{ old('superficie_ha', isset($parcela) ? $parcela->superficie_ha : '') }}"
                        required placeholder="0.00" />
                    <span class="absolute inset-y-0 right-3 flex items-center text-sm text-gray-400">ha</span>
                </div>
                <x-input-error :messages="$errors->get('superficie_ha')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="variedad_id" value="Variedad" />
                <select id="variedad_id" name="variedad_id"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">Sin variedad especificada</option>
                    @foreach($variedades as $variedad)
                        <option value="{{ $variedad->id }}"
                            {{ old('variedad_id', $parcela->variedad_id ?? '') == $variedad->id ? 'selected' : '' }}>
                            {{ $variedad->nombre }}
                            @if($variedad->tipo) ({{ $variedad->tipo }}) @endif
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('variedad_id')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="año_plantacion" value="Año de plantación" />
                <x-text-input id="año_plantacion" name="año_plantacion" type="number" min="1900" max="2099"
                    class="mt-1 block w-full font-mono"
                    value="{{ old('año_plantacion', $parcela->año_plantacion ?? '') }}" placeholder="2005" />
                <x-input-error :messages="$errors->get('año_plantacion')" class="mt-1" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="sistema_conduccion" value="Sistema de conducción" />
                <x-text-input id="sistema_conduccion" name="sistema_conduccion" type="text"
                    class="mt-1 block w-full"
                    value="{{ old('sistema_conduccion', $parcela->sistema_conduccion ?? '') }}"
                    placeholder="Ej: Vaso, Espaldera, Lira…" />
                <x-input-error :messages="$errors->get('sistema_conduccion')" class="mt-1" />
            </div>

        </div>
    </div>

</div>
