<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-stone-900 leading-tight">Mis Fincas</h2>
                <p class="text-sm text-stone-400 mt-0.5">Gestiona tus explotaciones vitivinícolas</p>
            </div>
            <a href="{{ route('vinedo.fincas.create') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-700 text-white text-sm font-semibold rounded-lg hover:bg-green-800 active:bg-green-900 transition duration-150">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva finca
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-5 flex items-center gap-3 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if($fincas->isEmpty())
                <div class="bg-white rounded-xl border border-stone-200 p-16 text-center">
                    <div class="mx-auto w-16 h-16 bg-stone-100 rounded-2xl flex items-center justify-center mb-5">
                        <svg class="h-8 w-8 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                    </div>
                    <p class="font-semibold text-stone-700 mb-1">Sin fincas registradas</p>
                    <p class="text-sm text-stone-400 mb-6 max-w-xs mx-auto">Registra tu primera explotación vitivinícola para empezar a gestionar tus parcelas.</p>
                    <a href="{{ route('vinedo.fincas.create') }}" wire:navigate
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-700 text-white text-sm font-semibold rounded-lg hover:bg-green-800 transition duration-150">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Registrar primera finca
                    </a>
                </div>
            @else
                <div class="bg-white rounded-xl border border-stone-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-stone-100">
                        <thead class="bg-stone-50/70">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-stone-400 uppercase tracking-wider">Provincia</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-stone-400 uppercase tracking-wider">Municipio</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-stone-400 uppercase tracking-wider">Paraje</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold text-stone-400 uppercase tracking-wider">Hectáreas</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold text-stone-400 uppercase tracking-wider">Parcelas</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-50">
                            @foreach($fincas as $finca)
                                <tr class="hover:bg-stone-50/60 transition duration-100">
                                    <td class="px-5 py-3.5">
                                        <p class="text-sm font-medium text-stone-800">{{ $finca->provincia_nombre }}</p>
                                        <p class="text-xs text-stone-400 font-mono mt-0.5">{{ str_pad($finca->provincia_cod, 2, '0', STR_PAD_LEFT) }}</p>
                                    </td>
                                    <td class="px-5 py-3.5 text-sm font-mono text-stone-600">
                                        {{ str_pad($finca->municipio_cod, 3, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="px-5 py-3.5 text-sm text-stone-500">{{ $finca->paraje ?: '—' }}</td>
                                    <td class="px-5 py-3.5 text-sm text-right font-mono font-semibold text-stone-700">
                                        {{ number_format($finca->parcelas_sum_superficie_ha ?? 0, 2) }}
                                        <span class="font-normal text-stone-400 text-xs ml-0.5">ha</span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="inline-flex items-center justify-center h-6 min-w-6 px-1.5 bg-stone-100 rounded-md text-xs font-semibold text-stone-600">
                                            {{ $finca->parcelas_count }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('vinedo.fincas.show', $finca) }}" wire:navigate
                                                class="text-sm text-green-700 hover:text-green-900 font-medium transition duration-100">Ver</a>
                                            <a href="{{ route('vinedo.fincas.edit', $finca) }}" wire:navigate
                                                class="text-sm text-stone-400 hover:text-stone-700 font-medium transition duration-100">Editar</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-stone-50/70 border-t border-stone-100">
                            <tr>
                                <td colspan="3" class="px-5 py-2.5 text-xs text-stone-400">
                                    {{ $fincas->count() }} {{ $fincas->count() === 1 ? 'finca' : 'fincas' }}
                                </td>
                                <td class="px-5 py-2.5 text-right text-xs font-semibold text-stone-600 font-mono">
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
