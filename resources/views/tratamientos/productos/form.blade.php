@php
    $editando = $producto->exists;
    $volver = $origen['url'] ?? route('tratamientos.productos.index', ['origen' => 'propios']);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ $volver }}" wire:navigate class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editando ? 'Editar producto' : 'Nuevo producto propio' }}</h2>
                <p class="text-xs text-gray-400 mt-0.5">Solo lo ves tú. Úsalo para productos que no aparezcan en el Registro del MAPA del catálogo.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <form method="POST" action="{{ $editando ? route('tratamientos.productos.update', $producto) : route('tratamientos.productos.store') }}">
                    @csrf
                    @if($editando) @method('PUT') @endif
                    @if($origen)
                        <input type="hidden" name="{{ $origen['campo'] }}" value="{{ $origen['id'] }}">
                    @endif

                    <div class="space-y-5">
                        <div>
                            <x-input-label for="nombre" value="Nombre comercial *" />
                            <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" required maxlength="255"
                                value="{{ old('nombre', $producto->nombre) }}" />
                            <x-input-error :messages="$errors->get('nombre')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="numero_registro" value="Nº de registro" />
                                <x-text-input id="numero_registro" name="numero_registro" type="text" class="mt-1 block w-full font-mono" maxlength="50"
                                    value="{{ old('numero_registro', $producto->numero_registro) }}" placeholder="ES-00000" />
                                <p class="mt-0.5 text-xs text-gray-400">Obligatorio en el cuaderno de explotación; viene en la etiqueta.</p>
                                <x-input-error :messages="$errors->get('numero_registro')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="titular" value="Titular" />
                                <x-text-input id="titular" name="titular" type="text" class="mt-1 block w-full" maxlength="255"
                                    value="{{ old('titular', $producto->titular) }}" />
                                <x-input-error :messages="$errors->get('titular')" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="ingrediente_activo" value="Materia activa / formulado" />
                            <x-text-input id="ingrediente_activo" name="ingrediente_activo" type="text" class="mt-1 block w-full" maxlength="255"
                                value="{{ old('ingrediente_activo', $producto->ingrediente_activo) }}" placeholder="Ej: AZUFRE 80% [WG] P/P" />
                            <x-input-error :messages="$errors->get('ingrediente_activo')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="unidad" value="Se dosifica y se compra en *" />
                            <select id="unidad" name="unidad" required
                                class="mt-1 block w-full sm:w-1/2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                @foreach(\App\Modules\Tratamientos\Models\ProductoFitosanitario::UNIDADES as $valor => $etiqueta)
                                    <option value="{{ $valor }}" {{ old('unidad', $producto->unidad ?? 'l') === $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('unidad')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="dosis_max_l_ha" value="Dosis máxima (por ha)" />
                                <x-text-input id="dosis_max_l_ha" name="dosis_max_l_ha" type="number" step="0.001" min="0.001" class="mt-1 block w-full"
                                    value="{{ old('dosis_max_l_ha', $producto->dosis_max_l_ha) }}" />
                                <p class="mt-0.5 text-xs text-gray-400">Si la superas al registrar un tratamiento se crea una alerta.</p>
                                <x-input-error :messages="$errors->get('dosis_max_l_ha')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="plazo_seguridad_dias" value="Plazo de seguridad (días)" />
                                <x-text-input id="plazo_seguridad_dias" name="plazo_seguridad_dias" type="number" step="1" min="0" max="255" class="mt-1 block w-full"
                                    value="{{ old('plazo_seguridad_dias', $producto->plazo_seguridad_dias) }}" />
                                <p class="mt-0.5 text-xs text-gray-400">Se propone al registrar un tratamiento.</p>
                                <x-input-error :messages="$errors->get('plazo_seguridad_dias')" class="mt-1" />
                            </div>
                        </div>

                        <div class="sm:w-1/2 sm:pr-2">
                            <x-input-label for="precio" value="Precio" />
                            <div class="mt-1 relative">
                                <x-text-input id="precio" name="precio" type="number" step="0.01" min="0" class="block w-full pr-12"
                                    value="{{ old('precio', $producto->precio) }}" placeholder="0,00" />
                                <span class="absolute inset-y-0 right-3 flex items-center text-sm text-gray-400">€</span>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-400">Por litro o kilo, según la unidad. Con él se anota el coste de cada tratamiento.</p>
                            <x-input-error :messages="$errors->get('precio')" class="mt-1" />
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ $volver }}" wire:navigate class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Cancelar</a>
                        <x-primary-button>{{ $editando ? 'Guardar cambios' : 'Añadir producto' }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
