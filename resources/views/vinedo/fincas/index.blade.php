<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mis Fincas</h2>
            <a href="{{ route('vinedo.fincas.create') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva finca
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if($fincas->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <svg class="h-12 w-12 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    <p class="text-gray-500 mb-4">No tienes fincas registradas todavía.</p>
                    <a href="{{ route('vinedo.fincas.create') }}" wire:navigate
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                        Registrar primera finca
                    </a>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Provincia</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Municipio</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Paraje</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Hectáreas</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">Parcelas</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($fincas as $finca)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-5 py-3">
                                        <p class="text-sm font-medium text-gray-800">{{ $finca->provincia_nombre }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ str_pad($finca->provincia_cod, 2, '0', STR_PAD_LEFT) }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-sm font-mono text-gray-700">
                                        {{ str_pad($finca->municipio_cod, 3, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-gray-600">{{ $finca->paraje ?: '—' }}</td>
                                    <td class="px-5 py-3 text-sm text-right font-mono font-medium text-gray-700">
                                        {{ number_format($finca->parcelas_sum_superficie_ha ?? 0, 2) }}
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="text-sm text-gray-500">{{ $finca->parcelas_count }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('vinedo.fincas.show', $finca) }}" wire:navigate
                                                class="text-sm text-green-600 hover:text-green-800 font-medium">Ver</a>
                                            <a href="{{ route('vinedo.fincas.edit', $finca) }}" wire:navigate
                                                class="text-sm text-gray-500 hover:text-gray-800 font-medium">Modificar</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t border-gray-100">
                            <tr>
                                <td colspan="3" class="px-5 py-2 text-xs text-gray-400">
                                    {{ $fincas->count() }} {{ $fincas->count() === 1 ? 'finca' : 'fincas' }}
                                </td>
                                <td class="px-5 py-2 text-right text-xs font-semibold text-gray-600 font-mono">
                                    {{ number_format($fincas->sum('parcelas_sum_superficie_ha'), 2) }} ha
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
