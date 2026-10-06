@php
    use App\Modules\Admin\Http\Controllers\VariedadAdminController;
    use App\Modules\Vinedo\Models\Variedad;

    $editando = $variedad->exists;
    $cultivo = old('cultivo', $variedad->cultivo);
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin._cabecera', ['titulo' => $editando ? "Editar «{$variedad->nombre}»" : 'Nueva variedad'])
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ $editando ? route('admin.variedades.update', $variedad) : route('admin.variedades.store') }}"
                x-data="{ cultivo: @js($cultivo) }"
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                @csrf
                @if($editando) @method('PUT') @endif

                <div>
                    <x-input-label for="cultivo" value="Cultivo *" />
                    <select id="cultivo" name="cultivo" x-model="cultivo"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                        @foreach(Variedad::CULTIVOS as $clave => $info)
                            <option value="{{ $clave }}" @selected($cultivo === $clave)>{{ $info['nombre'] }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('cultivo')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="nombre" value="Nombre *" />
                    <x-text-input id="nombre" name="nombre" class="mt-1 block w-full" required maxlength="255"
                        value="{{ old('nombre', $variedad->nombre) }}" />
                    <p x-show="cultivo === 'herbaceo'" x-cloak class="mt-1 text-xs text-amber-700">
                        En secano el nombre indica el cultivo: con los ya existentes (Trigo blando, Cebada, Girasol…) se filtran los
                        productos autorizados y se pone el código EPPO. Un nombre nuevo no tendrá esos datos.
                    </p>
                    <x-input-error :messages="$errors->get('nombre')" class="mt-1" />
                </div>

                <div x-show="cultivo === 'vid'" x-cloak class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="tipo" value="Color de la uva" />
                        <select id="tipo" name="tipo"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            @foreach(VariedadAdminController::TIPOS as $clave => $etiqueta)
                                <option value="{{ $clave }}" @selected(old('tipo', $variedad->tipo ?? 'tinta') === $clave)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="precocidad" value="Época de maduración" />
                        <select id="precocidad" name="precocidad"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                            <option value="">Sin indicar</option>
                            @foreach(Variedad::PRECOCIDADES as $clave => $etiqueta)
                                <option value="{{ $clave }}" @selected(old('precocidad', $variedad->precocidad) === $clave)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">Adelanta o retrasa el calendario fenológico de referencia.</p>
                    </div>
                </div>

                <div>
                    <x-input-label for="descripcion" value="Descripción" />
                    <textarea id="descripcion" name="descripcion" rows="3" maxlength="1000"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">{{ old('descripcion', $variedad->descripcion) }}</textarea>
                    <x-input-error :messages="$errors->get('descripcion')" class="mt-1" />
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.variedades.index') }}" wire:navigate class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</a>
                    <x-primary-button>{{ $editando ? 'Guardar' : 'Añadir' }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
