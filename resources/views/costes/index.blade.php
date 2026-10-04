<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Costes</h2>
            <form method="GET" action="{{ route('costes.index') }}" class="flex items-center gap-2">
                <select name="año" onchange="this.form.submit()"
                    class="text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    @foreach($años as $a)
                        <option value="{{ $a }}" {{ $a == $año ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                    @if($años->doesntContain($año))
                        <option value="{{ $año }}" selected>{{ $año }}</option>
                    @endif
                </select>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Resumen por categoría --}}
            @if($resumenPorCategoria->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($resumenPorCategoria as $categoria => $total)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                            <p class="text-xs text-gray-400 truncate">{{ $categoria ?? 'Sin categoría' }}</p>
                            <p class="text-lg font-semibold text-gray-800 mt-1">{{ number_format($total, 2) }} €</p>
                        </div>
                    @endforeach
                    <div class="bg-green-50 rounded-xl border border-green-100 p-4">
                        <p class="text-xs text-green-600">Total {{ $año }}</p>
                        <p class="text-lg font-semibold text-green-800 mt-1">{{ number_format($totalAño, 2) }} €</p>
                    </div>
                </div>
            @endif

            {{-- Listado --}}
            @if($costes->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <svg class="h-12 w-12 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="font-medium text-gray-500 mb-1">Sin costes en {{ $año }}</p>
                    <p class="text-sm text-gray-400">Accede a una parcela y usa el botón «Nuevo coste» para registrar el primero.</p>
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
                                    <th class="px-4 py-3 text-left font-medium">Categoría</th>
                                    <th class="px-4 py-3 text-left font-medium">Descripción</th>
                                    <th class="px-4 py-3 text-right font-medium">Importe</th>
                                    <th class="px-4 py-3 text-right font-medium">€/ha</th>
                                    <th class="px-3 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($costes as $c)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 font-mono text-gray-600 whitespace-nowrap">
                                            {{ $c->fecha->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('vinedo.parcelas.show', $c->parcela) }}" wire:navigate
                                                class="text-gray-800 hover:text-green-700 font-medium">
                                                @if($c->parcela->poligono && $c->parcela->parcela_sigpac)
                                                    Pol. {{ $c->parcela->poligono }} · Par. {{ $c->parcela->parcela_sigpac }}
                                                @else
                                                    {{ $c->parcela->nombre }}
                                                @endif
                                            </a>
                                            <p class="text-xs text-gray-400">
                                                {{ $c->parcela->finca->paraje ?: $c->parcela->finca->provincia_nombre }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">
                                            {{ $c->categoria?->nombre ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 max-w-xs truncate">
                                            {{ $c->descripcion ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                            {{ number_format($c->importe, 2) }} €
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-500 text-xs">
                                            @if($c->parcela->superficie_ha > 0)
                                                {{ number_format($c->importe / $c->parcela->superficie_ha, 2) }}
                                            @else —
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <form method="POST" action="{{ route('costes.destroy', $c) }}"
                                                onsubmit="return confirm('¿Eliminar este coste?')">
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

                    @if($costes->hasPages())
                        <div class="px-4 py-3 border-t border-gray-100">
                            {{ $costes->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
