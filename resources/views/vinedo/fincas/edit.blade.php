<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('vinedo.fincas.show', $finca) }}" wire:navigate class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Editar finca — {{ $finca->paraje ?: $finca->provincia_nombre }}
            </h2>
        </div>
    </x-slot>

    {{-- Formulario de eliminación (fuera del formulario de edición) --}}
    <form id="form-delete" method="POST" action="{{ route('vinedo.fincas.destroy', $finca) }}"
        onsubmit="return confirm('¿Eliminar esta finca y todas sus parcelas? Esta acción no se puede deshacer.')">
        @csrf
        @method('DELETE')
    </form>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <form method="POST" action="{{ route('vinedo.fincas.update', $finca) }}">
                    @csrf
                    @method('PUT')

                    @include('vinedo.fincas._form')

                    <div class="mt-6 flex items-center justify-between">
                        <button type="submit" form="form-delete"
                            class="px-4 py-2 text-sm text-red-600 hover:text-red-800 transition">
                            Eliminar finca
                        </button>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('vinedo.fincas.show', $finca) }}" wire:navigate
                                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">
                                Cancelar
                            </a>
                            <x-primary-button>Guardar cambios</x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
