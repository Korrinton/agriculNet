<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('vinedo.fincas.show', $finca) }}" wire:navigate class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Nueva parcela — {{ $finca->paraje ?: $finca->provincia_nombre }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <form method="POST" action="{{ route('vinedo.parcelas.store', $finca) }}">
                    @csrf

                    @php $parcela = new \App\Modules\Vinedo\Models\Parcela(); @endphp
                    @include('vinedo.parcelas._form')

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('vinedo.fincas.show', $finca) }}" wire:navigate
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">
                            Cancelar
                        </a>
                        <x-primary-button>Guardar parcela</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
