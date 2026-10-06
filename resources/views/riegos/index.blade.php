@php
    use App\Modules\Riegos\Models\Riego;

    $num = fn ($v, $d = 0) => $v === null ? '—' : number_format((float) $v, $d, ',', '.');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Riegos</h2>

            @if($finca)
                <div class="flex flex-wrap items-center gap-2">
                    <form method="GET" action="{{ route('riegos.index') }}" class="flex items-center gap-2">
                        <select name="finca" data-autoenviar class="text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            @foreach($fincas as $f)
                                <option value="{{ $f->id }}" {{ $f->id === $finca->id ? 'selected' : '' }}>{{ $f->paraje ?: $f->provincia_nombre }} ({{ $f->codigo_ine }})</option>
                            @endforeach
                        </select>
                        <select name="anio" data-autoenviar class="text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            @foreach($campanas as $a)
                                <option value="{{ $a }}" {{ $a === $anio ? 'selected' : '' }}>Campaña {{ $a }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if($finca->parcelas->contains(fn ($p) => $p->esRegable()))
                        <a href="{{ route('riegos.create', $finca) }}" wire:navigate
                            class="px-3 py-2 text-sm text-white bg-green-700 rounded-lg hover:bg-green-800 transition">+ Anotar riego</a>
                    @endif
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">


            @if(! $finca)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <p class="font-medium text-gray-500 mb-1">Sin fincas registradas</p>
                    <a href="{{ route('vinedo.fincas.create') }}" wire:navigate class="text-sm text-green-600 hover:text-green-800 font-medium">Crear la primera finca →</a>
                </div>
            @else
                {{-- Resumen por parcela --}}
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-semibold text-gray-800">Agua aplicada en {{ $anio }}</h3>
                        <p class="text-xs text-gray-500">
                            @if($lluvia)
                                Lluvia en {{ $finca->estacion->nombre }}: <span class="font-semibold text-sky-700">{{ $num($lluvia['mm'], 1) }} mm</span>
                                <span class="text-gray-400">({{ $lluvia['dias'] }} días con datos)</span>
                            @else
                                <span class="text-gray-400">Vincula una estación meteorológica a la finca para comparar con la lluvia.</span>
                            @endif
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-400 bg-gray-50 border-b border-gray-100">
                                    <th class="px-4 py-2 text-left font-medium">Parcela</th>
                                    <th class="px-4 py-2 text-left font-medium">Uso</th>
                                    <th class="px-4 py-2 text-right font-medium">Riegos</th>
                                    <th class="px-4 py-2 text-right font-medium">Volumen (m³)</th>
                                    <th class="px-4 py-2 text-right font-medium">m³/ha</th>
                                    <th class="px-4 py-2 text-right font-medium">mm</th>
                                    <th class="px-4 py-2 text-left font-medium">Último riego</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($resumen as $fila)
                                    @php $p = $fila['parcela']; @endphp
                                    <tr class="{{ $p->esRegable() ? '' : 'text-gray-400' }}">
                                        <td class="px-4 py-2"><a href="{{ route('vinedo.parcelas.show', $p) }}" wire:navigate class="font-medium {{ $p->esRegable() ? 'text-gray-800' : 'text-gray-400' }} hover:text-green-700">{{ $p->etiqueta }}</a></td>
                                        <td class="px-4 py-2">{{ $p->uso }}</td>
                                        @if($p->esRegable())
                                            <td class="px-4 py-2 text-right font-mono">{{ $fila['riegos'] }}</td>
                                            <td class="px-4 py-2 text-right font-mono">{{ $num($fila['m3']) }}</td>
                                            <td class="px-4 py-2 text-right font-mono">{{ $num($fila['m3_ha']) }}</td>
                                            <td class="px-4 py-2 text-right font-mono font-semibold text-sky-700">{{ $num($fila['mm'], 1) }}</td>
                                            <td class="px-4 py-2 text-gray-600">{{ $fila['ultimo']?->format('d/m/Y') ?? '—' }}</td>
                                        @else
                                            <td colspan="5" class="px-4 py-2 text-xs italic">Secano: no se riega</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- Listado de riegos --}}
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">Riegos de la campaña <span class="text-xs font-normal text-gray-400">({{ $riegos->count() }})</span></h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-400 bg-gray-50 border-b border-gray-100">
                                    <th class="px-4 py-2 text-left font-medium">Fecha</th>
                                    <th class="px-4 py-2 text-left font-medium">Parcela</th>
                                    <th class="px-4 py-2 text-right font-medium">m³</th>
                                    <th class="px-4 py-2 text-right font-medium">Sup. ha</th>
                                    <th class="px-4 py-2 text-right font-medium">mm</th>
                                    <th class="px-4 py-2 text-right font-medium">Horas</th>
                                    <th class="px-4 py-2 text-left font-medium">Sistema</th>
                                    <th class="px-4 py-2 text-left font-medium">Origen</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($riegos as $r)
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-600 whitespace-nowrap">{{ $r->fecha->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2 text-gray-800 whitespace-nowrap">{{ $r->parcela->etiqueta }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ $num($r->volumen_m3) }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ $num($r->superficie_ha, 2) }}</td>
                                        <td class="px-4 py-2 text-right font-mono text-sky-700">{{ $num($r->dosis_mm, 1) }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ $num($r->duracion_horas, 1) }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ Riego::SISTEMAS[$r->sistema] ?? $r->sistema }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ Riego::ORIGENES[$r->origen] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <form method="POST" action="{{ route('riegos.destroy', $r) }}" data-confirmar="¿Eliminar este riego?">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-gray-300 hover:text-red-500" title="Eliminar">✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="px-4 py-6 text-center text-gray-400">
                                        Sin riegos en {{ $anio }}.
                                        @if(! $finca->parcelas->contains(fn ($p) => $p->esRegable()))
                                            Todas las parcelas de esta finca son de secano.
                                        @endif
                                    </td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <p class="text-xs text-gray-400">1 mm equivale a 10 m³/ha (un litro por metro cuadrado). Los riegos se incluyen en el cuaderno de explotación.</p>
            @endif
        </div>
    </div>
</x-app-layout>
