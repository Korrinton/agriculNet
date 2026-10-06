{{-- Ubicación de la finca --}}
<div data-pagina="provincia">
    <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
        <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Ubicación
    </h3>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">

        <div>
            <x-input-label for="provincia_cod" value="Provincia *" />
            <div class="mt-1 flex gap-2 items-start">
                <div class="w-20 shrink-0">
                    <x-text-input id="provincia_cod" name="provincia_cod" type="number" min="1" max="52"
                        class="block w-full text-center font-mono"
                        value="{{ old('provincia_cod', $finca->provincia_cod ?? '') }}"
                        required placeholder="45" />
                </div>
                <div class="flex-1">
                    <select id="provincia_nombre"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">Nombre...</option>
                        @foreach($provincias as $cod => $nombre)
                            <option value="{{ $cod }}" {{ old('provincia_cod', $finca->provincia_cod ?? '') == $cod ? 'selected' : '' }}>
                                {{ $nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <x-input-error :messages="$errors->get('provincia_cod')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="municipio_cod" value="Municipio *" />
            <x-text-input id="municipio_cod" name="municipio_cod" type="number" min="1" max="999"
                class="mt-1 block w-full font-mono"
                value="{{ old('municipio_cod', $finca->municipio_cod ?? '') }}"
                required placeholder="165" />
            <p class="mt-0.5 text-xs text-gray-400">Código INE</p>
            <x-input-error :messages="$errors->get('municipio_cod')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="paraje" value="Paraje" />
            <x-text-input id="paraje" name="paraje" type="text" class="mt-1 block w-full"
                value="{{ old('paraje', $finca->paraje ?? '') }}" placeholder="Nombre del pago o paraje" />
            <x-input-error :messages="$errors->get('paraje')" class="mt-1" />
        </div>

    </div>
</div>

{{-- Titular de la explotación (portada del cuaderno de explotación) --}}
<div class="mt-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-1 flex items-center gap-2">
        <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        Titular de la explotación
    </h3>
    <p class="text-xs text-gray-400 mb-3">Opcional. Aparece en el cuaderno de explotación.</p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <x-input-label for="titular_nombre" value="Nombre o razón social" />
            <x-text-input id="titular_nombre" name="titular_nombre" type="text" class="mt-1 block w-full"
                value="{{ old('titular_nombre', $finca->titular_nombre ?? '') }}" />
            <x-input-error :messages="$errors->get('titular_nombre')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="titular_nif" value="NIF" />
            <x-text-input id="titular_nif" name="titular_nif" type="text" class="mt-1 block w-full font-mono uppercase"
                value="{{ old('titular_nif', $finca->titular_nif ?? '') }}" placeholder="12345678Z" />
            <x-input-error :messages="$errors->get('titular_nif')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="rea_numero" value="Nº REA" />
            <x-text-input id="rea_numero" name="rea_numero" type="text" class="mt-1 block w-full font-mono"
                value="{{ old('rea_numero', $finca->rea_numero ?? '') }}" />
            <p class="mt-0.5 text-xs text-gray-400">Registro de Explotaciones Agrícolas</p>
            <x-input-error :messages="$errors->get('rea_numero')" class="mt-1" />
        </div>
    </div>
</div>

