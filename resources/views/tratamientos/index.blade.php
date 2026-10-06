<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Tratamientos fitosanitarios</h2>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('tratamientos.productos.index') }}" wire:navigate
                    class="px-3 py-2 text-sm text-green-700 border border-green-200 rounded-lg hover:bg-green-50 transition">Catálogo de productos</a>
                @if($fincas->pluck('parcelas')->flatten()->isNotEmpty())
                    <select id="nuevo-tratamiento" data-ir-a-valor
                        class="text-sm text-white bg-green-700 border-green-700 rounded-lg hover:bg-green-800 focus:ring-green-500 focus:border-green-500">
                        <option value="">+ Nuevo tratamiento en…</option>
                        @foreach($fincas as $f)
                            @if($f->parcelas->isNotEmpty())
                                <optgroup label="{{ $f->paraje ?: $f->provincia_nombre }}" class="text-gray-800 bg-white">
                                    @if($f->parcelas->count() > 1)
                                        <option value="{{ route('tratamientos.finca.create', $f) }}">Toda la finca ({{ $f->parcelas->count() }} parcelas)</option>
                                    @endif
                                    @foreach($f->parcelas as $p)
                                        <option value="{{ route('tratamientos.create', $p) }}">{{ $p->etiqueta }} — {{ $p->uso }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">


            @if($tratamientos->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <svg class="h-12 w-12 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <p class="font-medium text-gray-500 mb-1">Sin tratamientos registrados</p>
                    <p class="text-sm text-gray-400">Elige la parcela en «+ Nuevo tratamiento en…», arriba a la derecha, o usa «+ Nuevo» en la ficha de la parcela.</p>
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
                                    <th class="px-4 py-3 text-right font-medium">Dosis</th>
                                    <th class="px-4 py-3 text-right font-medium">Plazo seg.</th>
                                    <th class="px-4 py-3 text-right font-medium">Coste</th>
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
                                            {{ number_format($t->dosis_l_ha, 2) }} <span class="text-xs text-gray-400">{{ $t->unidadDosis() }}</span>
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
                                        <td class="px-4 py-3 text-right font-mono text-gray-700 whitespace-nowrap">
                                            @if($t->coste)
                                                {{ number_format((float) $t->coste->importe, 2, ',', '.') }} €
                                            @else
                                                <span class="text-xs text-gray-300">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('tratamientos.edit', $t) }}" wire:navigate
                                                class="inline-block mr-2 text-gray-300 hover:text-green-600 transition" title="Editar">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.232-6.232a2.5 2.5 0 113.536 3.536L12.536 16.536 9 17l.464-3.536z"/>
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('tratamientos.destroy', $t) }}" class="inline"
                                                data-confirmar="¿Eliminar este tratamiento?">
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
