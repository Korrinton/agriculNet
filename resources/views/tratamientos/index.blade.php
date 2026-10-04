<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tratamientos fitosanitarios</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if($tratamientos->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <svg class="h-12 w-12 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <p class="font-medium text-gray-500 mb-1">Sin tratamientos registrados</p>
                    <p class="text-sm text-gray-400">Accede a una parcela y usa el botón «Nuevo tratamiento» para registrar el primero.</p>
                    <a href="{{ route('vinedo.fincas.index') }}" wire:navigate
                        class="mt-4 inline-block text-sm text-green-600 hover:text-green-800 font-medium">
                        Ir a mis fincas →
                    </a>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-400 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 py-3 text-left font-medium">Fecha</th>
                                    <th class="px-4 py-3 text-left font-medium">Parcela</th>
                                    <th class="px-4 py-3 text-left font-medium">Producto</th>
                                    <th class="px-4 py-3 text-left font-medium">Motivo</th>
                                    <th class="px-4 py-3 text-right font-medium">Dosis (l/ha)</th>
                                    <th class="px-4 py-3 text-right font-medium">Plazo seg.</th>
                                    <th class="px-3 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($tratamientos as $t)
                                    @php $fin = $t->fechaFinalPlazoSeguridad(); @endphp
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 font-mono text-gray-600 whitespace-nowrap">
                                            {{ $t->fecha->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('vinedo.parcelas.show', $t->parcela) }}" wire:navigate
                                                class="text-gray-800 hover:text-green-700 font-medium">
                                                @if($t->parcela->poligono && $t->parcela->parcela_sigpac)
                                                    Pol. {{ $t->parcela->poligono }} · Par. {{ $t->parcela->parcela_sigpac }}
                                                @else
                                                    {{ $t->parcela->nombre }}
                                                @endif
                                            </a>
                                            <p class="text-xs text-gray-400">
                                                {{ $t->parcela->finca->paraje ?: $t->parcela->finca->provincia_nombre }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <p class="text-gray-800">{{ $t->producto?->nombre ?? '—' }}</p>
                                            @if($t->producto?->ingrediente_activo)
                                                <p class="text-xs text-gray-400">{{ $t->producto->ingrediente_activo }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 max-w-xs truncate">
                                            {{ $t->motivo ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-gray-700">
                                            {{ number_format($t->dosis_l_ha, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            @if($fin)
                                                <span class="text-xs {{ $fin->isFuture() ? 'text-amber-600 font-medium' : 'text-gray-400' }}">
                                                    {{ $fin->format('d/m/Y') }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <form method="POST" action="{{ route('tratamientos.destroy', $t) }}"
                                                onsubmit="return confirm('¿Eliminar este tratamiento?')">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="text-gray-300 hover:text-red-500 transition" title="Eliminar">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($tratamientos->hasPages())
                        <div class="px-4 py-3 border-t border-gray-100">
                            {{ $tratamientos->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
