@php use App\Modules\CuadernoDigital\Models\Cosecha; @endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('cuaderno.index', ['finca' => $finca->id]) }}" wire:navigate class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Anotar cosecha</h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $finca->paraje ?: $finca->provincia_nombre }} · cuaderno de explotación</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('cosechas.store', $finca) }}" data-pagina="cosecha" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="parcela_id" value="Parcela *" />
                        <select id="parcela_id" name="parcela_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            <option value="">Selecciona…</option>
                            @foreach($finca->parcelas as $p)
                                <option value="{{ $p->id }}" data-producto="{{ Cosecha::PRODUCTO_POR_CULTIVO[$p->cultivo()] ?? '' }}" {{ old('parcela_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->etiqueta }} — {{ $p->uso }}{{ $p->variedad ? ' · ' . $p->variedad->nombre : '' }}
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
                        <x-input-label for="producto" value="Producto *" />
                        <x-text-input id="producto" name="producto" type="text" class="mt-1 block w-full" required value="{{ old('producto') }}" placeholder="Uva, aceituna, grano…" />
                        <x-input-error :messages="$errors->get('producto')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="cantidad_kg" value="Cantidad (kg) *" />
                        <x-text-input id="cantidad_kg" name="cantidad_kg" type="number" step="0.01" min="0.01" class="mt-1 block w-full font-mono" required value="{{ old('cantidad_kg') }}" />
                        <x-input-error :messages="$errors->get('cantidad_kg')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="superficie_ha" value="Superficie (ha)" />
                        <x-text-input id="superficie_ha" name="superficie_ha" type="number" step="0.0001" min="0.0001" class="mt-1 block w-full font-mono" value="{{ old('superficie_ha') }}" placeholder="Toda la parcela" />
                        <x-input-error :messages="$errors->get('superficie_ha')" class="mt-1" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="destino" value="Destino / comprador" />
                        <x-text-input id="destino" name="destino" type="text" class="mt-1 block w-full" value="{{ old('destino') }}" placeholder="Cooperativa, bodega, almazara…" />
                    </div>
                    <div>
                        <x-input-label for="destinatario_nif" value="NIF del destinatario" />
                        <x-text-input id="destinatario_nif" name="destinatario_nif" type="text" class="mt-1 block w-full font-mono uppercase" value="{{ old('destinatario_nif') }}" />
                    </div>
                    <div>
                        <x-input-label for="albaran" value="Nº albarán / ticket" />
                        <x-text-input id="albaran" name="albaran" type="text" class="mt-1 block w-full font-mono" value="{{ old('albaran') }}" />
                    </div>
                </div>

                <div>
                    <x-input-label for="observaciones" value="Observaciones" />
                    <x-text-input id="observaciones" name="observaciones" type="text" class="mt-1 block w-full" value="{{ old('observaciones') }}" placeholder="Grado, calidad, incidencias…" />
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('cuaderno.index', ['finca' => $finca->id]) }}" wire:navigate class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
                    <x-primary-button>Anotar cosecha</x-primary-button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
