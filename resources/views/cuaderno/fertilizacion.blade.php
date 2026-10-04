@php use App\Modules\CuadernoDigital\Models\Fertilizacion; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('cuaderno.index', ['finca' => $finca->id]) }}" wire:navigate class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Anotar fertilización</h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $finca->paraje ?: $finca->provincia_nombre }} · cuaderno de explotación</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('fertilizaciones.store', $finca) }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="parcela_id" value="Parcela *" />
                        <select id="parcela_id" name="parcela_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            <option value="">Selecciona…</option>
                            @foreach($finca->parcelas as $p)
                                <option value="{{ $p->id }}" data-superficie="{{ $p->superficie_ha }}" {{ old('parcela_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->etiqueta }} — {{ $p->uso }} ({{ number_format($p->superficie_ha, 2, ',', '.') }} ha)
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('parcela_id')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="fecha" value="Fecha de aplicación *" />
                        <x-text-input id="fecha" name="fecha" type="date" class="mt-1 block w-full" required
                            value="{{ old('fecha', now()->toDateString()) }}" max="{{ now()->toDateString() }}" />
                        <x-input-error :messages="$errors->get('fecha')" class="mt-1" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="tipo" value="Tipo de fertilizante *" />
                        <select id="tipo" name="tipo" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            @foreach(Fertilizacion::TIPOS as $valor => $etiqueta)
                                <option value="{{ $valor }}" {{ old('tipo', 'mineral') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('tipo')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="producto" value="Producto / nombre comercial *" />
                        <x-text-input id="producto" name="producto" type="text" class="mt-1 block w-full" required
                            value="{{ old('producto') }}" placeholder="Ej: Complejo 8-15-15, estiércol de oveja…" />
                        <x-input-error :messages="$errors->get('producto')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label value="Riqueza (%)" />
                    <div class="mt-1 grid grid-cols-3 gap-3">
                        @foreach(['riqueza_n' => 'N', 'riqueza_p' => 'P₂O₅', 'riqueza_k' => 'K₂O'] as $campo => $etiqueta)
                            <div class="relative">
                                <x-text-input name="{{ $campo }}" type="number" step="0.01" min="0" max="100" class="block w-full pr-14 font-mono"
                                    value="{{ old($campo) }}" placeholder="0" />
                                <span class="absolute inset-y-0 right-3 flex items-center text-xs text-gray-400">% {{ $etiqueta }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-0.5 text-xs text-gray-400">La que figura en el envase (p. ej. 8-15-15). En orgánicos, si la conoces.</p>
                    <x-input-error :messages="$errors->get('riqueza_n')" class="mt-1" />
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="col-span-1">
                        <x-input-label for="dosis" value="Dosis *" />
                        <x-text-input id="dosis" name="dosis" type="number" step="0.001" min="0.001" class="mt-1 block w-full font-mono" required value="{{ old('dosis') }}" />
                        <x-input-error :messages="$errors->get('dosis')" class="mt-1" />
                    </div>
                    <div class="col-span-1">
                        <x-input-label for="unidad" value="Unidad *" />
                        <select id="unidad" name="unidad" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            @foreach(Fertilizacion::UNIDADES as $u)
                                <option value="{{ $u }}" {{ old('unidad', 'kg/ha') === $u ? 'selected' : '' }}>{{ $u }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <x-input-label for="superficie_ha" value="Superficie abonada (ha)" />
                        <x-text-input id="superficie_ha" name="superficie_ha" type="number" step="0.0001" min="0.0001" class="mt-1 block w-full font-mono" value="{{ old('superficie_ha') }}" placeholder="Toda la parcela" />
                        <x-input-error :messages="$errors->get('superficie_ha')" class="mt-1" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="metodo" value="Método de aplicación" />
                        <select id="metodo" name="metodo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            <option value="">—</option>
                            @foreach(Fertilizacion::METODOS as $valor => $etiqueta)
                                <option value="{{ $valor }}" {{ old('metodo') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="observaciones" value="Observaciones" />
                        <x-text-input id="observaciones" name="observaciones" type="text" class="mt-1 block w-full" value="{{ old('observaciones') }}" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('cuaderno.index', ['finca' => $finca->id]) }}" wire:navigate class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
                    <x-primary-button>Anotar fertilización</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
