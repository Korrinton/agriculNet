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
        data-confirmar="¿Eliminar esta finca y todas sus parcelas? Esta acción no se puede deshacer.">
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

            {{-- Parcelas: el uso, la variedad y la superficie se editan en cada parcela --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700">Parcelas</h3>
                        <p class="text-xs text-gray-400 mt-0.5">El uso (tipo de cultivo), la variedad y la superficie se cambian en cada parcela.</p>
                    </div>
                    <a href="{{ route('vinedo.parcelas.create', $finca) }}" wire:navigate
                        class="shrink-0 text-xs text-green-600 hover:text-green-800 font-medium">+ Añadir parcela</a>
                </div>

                @forelse($finca->parcelas as $parcela)
                    <div class="px-6 py-3 flex items-center justify-between gap-4 border-b border-gray-50 last:border-b-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                @if($parcela->poligono && $parcela->parcela_sigpac)
                                    Pol. {{ $parcela->poligono }} · Par. {{ $parcela->parcela_sigpac }}
                                    @if($parcela->recinto) · Rec. {{ $parcela->recinto }} @endif
                                @else
                                    {{ $parcela->nombre }}
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5 truncate">
                                <span class="font-medium text-gray-700">{{ $parcela->uso ?? 'Sin uso' }}</span>
                                · {{ $parcela->variedad?->nombre ?? 'Sin variedad' }}
                                · {{ number_format($parcela->superficie_ha, 2, ',', '.') }} ha
                            </p>
                        </div>
                        <a href="{{ route('vinedo.parcelas.edit', $parcela) }}" wire:navigate
                            class="shrink-0 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                            Editar parcela
                        </a>
                    </div>
                @empty
                    <p class="px-6 py-6 text-sm text-gray-400 text-center">Esta finca no tiene parcelas.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
