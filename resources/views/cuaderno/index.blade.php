@php
    use App\Modules\CuadernoDigital\Models\Fertilizacion;
    use App\Modules\CuadernoDigital\Services\CuadernoCampana;
    use App\Modules\Tratamientos\Models\Tratamiento;

    $num = fn ($v, $d = 2) => $v === null ? '—' : number_format((float) $v, $d, ',', '.');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Cuaderno de explotación</h2>

            @if($datos)
                <div class="flex flex-wrap items-center gap-2">
                    <form method="GET" action="{{ route('cuaderno.index') }}" class="flex items-center gap-2">
                        <select name="finca" onchange="this.form.submit()"
                            class="text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            @foreach($fincas as $f)
                                <option value="{{ $f->id }}" {{ $f->id === $datos['finca']->id ? 'selected' : '' }}>
                                    {{ $f->paraje ?: $f->provincia_nombre }} ({{ $f->codigo_ine }})
                                </option>
                            @endforeach
                        </select>
                        <select name="anio" onchange="this.form.submit()"
                            class="text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            @foreach($campanas as $a)
                                <option value="{{ $a }}" {{ $a === $datos['anio'] ? 'selected' : '' }}>Campaña {{ $a }}</option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('cuaderno.imprimir', [$datos['finca'], $datos['anio']]) }}" target="_blank"
                        class="px-3 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        Imprimir / PDF
                    </a>
                    <a href="{{ route('cuaderno.excel', [$datos['finca'], $datos['anio']]) }}"
                        class="px-3 py-2 text-sm text-white bg-green-700 rounded-lg hover:bg-green-800 transition">
                        Descargar Excel
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">{{ session('success') }}</div>
            @endif

            @if(! $datos)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <p class="font-medium text-gray-500 mb-1">Sin fincas registradas</p>
                    <a href="{{ route('vinedo.fincas.create') }}" wire:navigate class="text-sm text-green-600 hover:text-green-800 font-medium">Crear la primera finca →</a>
                </div>
            @else
                @php $finca = $datos['finca']; @endphp

                {{-- Pendiente para completar el cuaderno --}}
                @if($datos['avisos'])
                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <p class="text-sm font-semibold text-amber-800 mb-1">Para que el cuaderno esté completo:</p>
                        <ul class="text-sm text-amber-800 list-disc ml-5 space-y-0.5">
                            @foreach($datos['avisos'] as $aviso)
                                <li>{{ $aviso }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- 1. Datos generales y parcelas --}}
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">1. Datos generales y parcelas</h3>
                        <a href="{{ route('vinedo.fincas.edit', $finca) }}" wire:navigate class="text-xs text-green-600 hover:text-green-800 font-medium">Editar titular y finca →</a>
                    </div>
                    <dl class="px-5 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm border-b border-gray-50">
                        <div><dt class="text-xs text-gray-400">Titular</dt><dd class="text-gray-800">{{ $finca->titular_nombre ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-400">NIF</dt><dd class="text-gray-800 font-mono">{{ $finca->titular_nif ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-400">Nº REA</dt><dd class="text-gray-800 font-mono">{{ $finca->rea_numero ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-gray-400">Ubicación</dt><dd class="text-gray-800">{{ $finca->provincia_nombre }} · mun. {{ $finca->codigo_ine }}{{ $finca->paraje ? ' · ' . $finca->paraje : '' }}</dd></div>
                    </dl>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-400 bg-gray-50 border-b border-gray-100">
                                    <th class="px-4 py-2 text-left font-medium">Parcela</th>
                                    <th class="px-4 py-2 text-left font-medium">Ref. SIGPAC</th>
                                    <th class="px-4 py-2 text-left font-medium">Uso</th>
                                    <th class="px-4 py-2 text-left font-medium">Variedad / cultivo</th>
                                    <th class="px-4 py-2 text-right font-medium">Superficie (ha)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($datos['parcelas'] as $p)
                                    <tr>
                                        <td class="px-4 py-2"><a href="{{ route('vinedo.parcelas.show', $p) }}" wire:navigate class="text-gray-800 hover:text-green-700 font-medium">{{ $p->etiqueta }}</a></td>
                                        <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $p->referencia_sigpac ?? '—' }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $p->uso }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $p->variedad?->nombre ?? '—' }}</td>
                                        <td class="px-4 py-2 text-right font-mono text-gray-700">{{ $num($p->superficie_ha, 4) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">La finca no tiene parcelas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- 2. Tratamientos fitosanitarios --}}
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                        <h3 class="font-semibold text-gray-800">2. Tratamientos fitosanitarios <span class="text-xs font-normal text-gray-400">({{ $datos['tratamientos']->count() }})</span></h3>
                        @if($datos['parcelas']->isNotEmpty())
                            <select onchange="if (this.value) window.location = this.value"
                                class="text-xs border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                                <option value="">+ Nuevo tratamiento en…</option>
                                @foreach($datos['parcelas'] as $p)
                                    <option value="{{ route('tratamientos.create', $p) }}">{{ $p->etiqueta }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-400 bg-gray-50 border-b border-gray-100">
                                    <th class="px-4 py-2 text-left font-medium">Fecha</th>
                                    <th class="px-4 py-2 text-left font-medium">Parcela</th>
                                    <th class="px-4 py-2 text-left font-medium">Problema</th>
                                    <th class="px-4 py-2 text-left font-medium">Producto (nº reg.)</th>
                                    <th class="px-4 py-2 text-right font-medium">Dosis l/ha</th>
                                    <th class="px-4 py-2 text-right font-medium">Sup. ha</th>
                                    <th class="px-4 py-2 text-left font-medium">Aplicador (ROPO)</th>
                                    <th class="px-4 py-2 text-left font-medium">Equipo ROMA</th>
                                    <th class="px-4 py-2 text-left font-medium">Eficacia</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($datos['tratamientos'] as $t)
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-600 whitespace-nowrap">{{ $t->fecha->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2 text-gray-800 whitespace-nowrap">{{ $t->parcela->etiqueta }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $t->motivo ?: '—' }}</td>
                                        <td class="px-4 py-2 text-gray-800">{{ $t->producto?->nombre ?? '—' }} <span class="text-xs text-gray-400 font-mono">{{ $t->producto?->numero_registro }}</span></td>
                                        <td class="px-4 py-2 text-right font-mono">{{ $num($t->dosis_l_ha, 3) }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ $num($t->superficie_tratada_ha ?? $t->parcela->superficie_ha, 2) }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $t->aplicador_nombre ?: '—' }} <span class="text-xs font-mono {{ $t->aplicador_ropo ? 'text-gray-400' : 'text-amber-600' }}">{{ $t->aplicador_ropo ?: 'sin ROPO' }}</span></td>
                                        <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $t->equipo_roma ?: '—' }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ Tratamiento::EFICACIAS[$t->eficacia] ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="px-4 py-6 text-center text-gray-400">Sin tratamientos en {{ $datos['anio'] }}.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- 3. Fertilización --}}
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-800">3. Fertilización <span class="text-xs font-normal text-gray-400">({{ $datos['fertilizaciones']->count() }})</span></h3>
                            <p class="text-xs text-gray-400">Obligatorio desde 2026: anotar cada aplicación en el plazo de un mes.</p>
                        </div>
                        @if($datos['parcelas']->isNotEmpty())
                            <a href="{{ route('fertilizaciones.create', $finca) }}" wire:navigate
                                class="px-3 py-1.5 text-xs font-medium text-white bg-green-700 rounded-lg hover:bg-green-800">+ Anotar fertilización</a>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-400 bg-gray-50 border-b border-gray-100">
                                    <th class="px-4 py-2 text-left font-medium">Fecha</th>
                                    <th class="px-4 py-2 text-left font-medium">Parcela</th>
                                    <th class="px-4 py-2 text-left font-medium">Tipo</th>
                                    <th class="px-4 py-2 text-left font-medium">Producto</th>
                                    <th class="px-4 py-2 text-left font-medium">N-P-K</th>
                                    <th class="px-4 py-2 text-right font-medium">Dosis</th>
                                    <th class="px-4 py-2 text-right font-medium">Sup. ha</th>
                                    <th class="px-4 py-2 text-left font-medium">Método</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($datos['fertilizaciones'] as $f)
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-600 whitespace-nowrap">{{ $f->fecha->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2 text-gray-800 whitespace-nowrap">{{ $f->parcela->etiqueta }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ Fertilizacion::TIPOS[$f->tipo] ?? $f->tipo }}</td>
                                        <td class="px-4 py-2 text-gray-800">{{ $f->producto }}</td>
                                        <td class="px-4 py-2 font-mono text-gray-600">{{ $f->npk ?? '—' }}</td>
                                        <td class="px-4 py-2 text-right font-mono whitespace-nowrap">{{ $num($f->dosis, 1) }} {{ $f->unidad }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ $num($f->superficie_ha, 2) }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ Fertilizacion::METODOS[$f->metodo] ?? '—' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <form method="POST" action="{{ route('fertilizaciones.destroy', $f) }}" onsubmit="return confirm('¿Eliminar esta fertilización?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-gray-300 hover:text-red-500" title="Eliminar">✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="px-4 py-6 text-center text-gray-400">Sin fertilizaciones en {{ $datos['anio'] }}.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- 4. Cosecha --}}
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">4. Cosecha <span class="text-xs font-normal text-gray-400">({{ $datos['cosechas']->count() }})</span></h3>
                        @if($datos['parcelas']->isNotEmpty())
                            <a href="{{ route('cosechas.create', $finca) }}" wire:navigate
                                class="px-3 py-1.5 text-xs font-medium text-white bg-green-700 rounded-lg hover:bg-green-800">+ Anotar cosecha</a>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-400 bg-gray-50 border-b border-gray-100">
                                    <th class="px-4 py-2 text-left font-medium">Fecha</th>
                                    <th class="px-4 py-2 text-left font-medium">Parcela</th>
                                    <th class="px-4 py-2 text-left font-medium">Producto</th>
                                    <th class="px-4 py-2 text-right font-medium">Kg</th>
                                    <th class="px-4 py-2 text-right font-medium">kg/ha</th>
                                    <th class="px-4 py-2 text-left font-medium">Destino</th>
                                    <th class="px-4 py-2 text-left font-medium">Albarán</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($datos['cosechas'] as $c)
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-600 whitespace-nowrap">{{ $c->fecha->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2 text-gray-800 whitespace-nowrap">{{ $c->parcela->etiqueta }}</td>
                                        <td class="px-4 py-2 text-gray-800">{{ $c->producto }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ $num($c->cantidad_kg, 0) }}</td>
                                        <td class="px-4 py-2 text-right font-mono text-gray-500">{{ $num($c->rendimiento_kg_ha, 0) }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $c->destino ?: '—' }} <span class="text-xs text-gray-400 font-mono">{{ $c->destinatario_nif }}</span></td>
                                        <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $c->albaran ?: '—' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <form method="POST" action="{{ route('cosechas.destroy', $c) }}" onsubmit="return confirm('¿Eliminar esta cosecha?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-gray-300 hover:text-red-500" title="Eliminar">✕</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">Sin cosechas en {{ $datos['anio'] }}.</td></tr>
                                @endforelse
                            </tbody>
                            @if($datos['cosechas']->isNotEmpty())
                                <tfoot>
                                    @foreach($datos['cosechas']->groupBy('producto') as $producto => $lista)
                                        <tr class="bg-gray-50 text-gray-700">
                                            <td colspan="3" class="px-4 py-2 text-right text-xs font-medium">Total {{ mb_strtolower($producto) }}</td>
                                            <td class="px-4 py-2 text-right font-mono font-semibold">{{ $num($lista->sum('cantidad_kg'), 0) }}</td>
                                            <td colspan="4"></td>
                                        </tr>
                                    @endforeach
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </section>

                {{-- 5. Riego --}}
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">5. Riego <span class="text-xs font-normal text-gray-400">({{ $datos['riegos']->count() }})</span></h3>
                        @if($datos['parcelas']->contains(fn ($p) => $p->esRegable()))
                            <a href="{{ route('riegos.create', $finca) }}" wire:navigate
                                class="px-3 py-1.5 text-xs font-medium text-white bg-green-700 rounded-lg hover:bg-green-800">+ Anotar riego</a>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-400 bg-gray-50 border-b border-gray-100">
                                    <th class="px-4 py-2 text-left font-medium">Fecha</th>
                                    <th class="px-4 py-2 text-left font-medium">Parcela</th>
                                    <th class="px-4 py-2 text-right font-medium">m³</th>
                                    <th class="px-4 py-2 text-right font-medium">Sup. ha</th>
                                    <th class="px-4 py-2 text-right font-medium">m³/ha</th>
                                    <th class="px-4 py-2 text-left font-medium">Sistema</th>
                                    <th class="px-4 py-2 text-left font-medium">Origen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($datos['riegos'] as $r)
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-gray-600 whitespace-nowrap">{{ $r->fecha->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2 text-gray-800 whitespace-nowrap">{{ $r->parcela->etiqueta }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ $num($r->volumen_m3, 0) }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ $num($r->superficie_ha, 2) }}</td>
                                        <td class="px-4 py-2 text-right font-mono">{{ $num($r->dosis_m3_ha, 0) }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ \App\Modules\Riegos\Models\Riego::SISTEMAS[$r->sistema] ?? $r->sistema }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ \App\Modules\Riegos\Models\Riego::ORIGENES[$r->origen] ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Sin riegos en {{ $datos['anio'] }}.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <p class="text-xs text-gray-400">
                    El envío electrónico a SIEX (MAPA) solo pueden hacerlo las
                    entidades habilitadas; con el Excel o la versión impresa puedes llevar el cuaderno al día y entregarlo a quien
                    lo presente, o usar la aplicación oficial de tu comunidad autónoma.
                </p>
            @endif
        </div>
    </div>
</x-app-layout>
