<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Calendario fenológico</h2>

            {{-- Selector de campaña --}}
            <div class="inline-flex items-center gap-1 text-sm">
                @if($campana['anterior'])
                    <a href="{{ route('fenologia.index', ['anio' => $campana['anterior']]) }}" wire:navigate
                        class="px-2 py-1 rounded-md text-gray-500 hover:bg-gray-100" title="Campaña {{ $campana['anterior'] }}">◀ {{ $campana['anterior'] }}</a>
                @endif
                <span class="px-3 py-1 rounded-md bg-green-700 text-white font-semibold">Campaña {{ $campana['anio'] }}</span>
                @if($campana['siguiente'])
                    <a href="{{ route('fenologia.index', ['anio' => $campana['siguiente']]) }}" wire:navigate
                        class="px-2 py-1 rounded-md text-gray-500 hover:bg-gray-100" title="Campaña {{ $campana['siguiente'] }}">{{ $campana['siguiente'] }} ▶</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if($otrasParcelas > 0)
                <div class="p-3 bg-stone-50 border border-stone-200 text-stone-600 rounded-lg text-sm">
                    El calendario usa la escala BBCH de la vid, así que solo muestra parcelas de viña.
                    {{ $otrasParcelas }} {{ $otrasParcelas === 1 ? 'parcela tuya' : 'parcelas tuyas' }} de secano, olivar o pistacho no
                    {{ $otrasParcelas === 1 ? 'aparece' : 'aparecen' }} aquí.
                </div>
            @endif

            @if($fincas->isNotEmpty() && $fincas->flatMap->parcelas->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <p class="font-medium text-gray-500 mb-1">No tienes parcelas de viña</p>
                    <p class="text-sm text-gray-400">Las parcelas con uso «Viña en espaldera» o «Viña en vaso» aparecerán aquí con su calendario de campaña.</p>
                </div>
            @endif

            {{-- Línea de campaña por finca / parcela --}}
            @forelse($fincas as $finca)
                @if($finca->parcelas->isNotEmpty())
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700">
                                {{ $finca->paraje ?: $finca->provincia_nombre }}
                                <span class="text-xs font-normal text-gray-400 ml-1">
                                    {{ $finca->parcelas->count() }} {{ $finca->parcelas->count() === 1 ? 'parcela' : 'parcelas' }}
                                </span>
                            </h3>
                        </div>

                        <div class="overflow-x-auto">
                            <div style="min-width: 760px">
                                {{-- Cabecera de meses --}}
                                <div class="flex border-b border-gray-100">
                                    <div class="w-60 shrink-0"></div>
                                    <div class="relative flex-1 h-7 mr-4">
                                        @foreach($campana['meses'] as $mes)
                                            <div class="absolute top-0 bottom-0 flex items-center justify-center text-[11px] font-medium text-gray-400 border-l border-gray-100"
                                                style="left: {{ $mes['izquierda'] }}%; width: {{ $mes['ancho'] }}%">{{ $mes['nombre'] }}</div>
                                        @endforeach
                                        @if($campana['hoy'] !== null)
                                            <div class="absolute top-0 -translate-x-1/2 text-[10px] font-semibold text-red-500 bg-white px-1"
                                                style="left: {{ $campana['hoy'] }}%">hoy</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="divide-y divide-gray-50">
                                    @foreach($finca->parcelas as $parcela)
                                        @php
                                            $ultimo = $parcela->registrosFenologicos->first();
                                            $fila = $campana['filas'][$parcela->id];
                                        @endphp
                                        <div class="flex items-center py-2.5">
                                            {{-- Parcela y estado actual --}}
                                            <div class="w-60 shrink-0 px-4 min-w-0">
                                                <div class="flex items-center justify-between gap-2">
                                                    <a href="{{ route('vinedo.parcelas.show', $parcela) }}" wire:navigate
                                                        class="text-sm font-medium text-gray-800 hover:text-green-700 truncate">
                                                        @if($parcela->poligono && $parcela->parcela_sigpac)
                                                            Pol. {{ $parcela->poligono }} · Par. {{ $parcela->parcela_sigpac }}
                                                        @else
                                                            {{ $parcela->nombre }}
                                                        @endif
                                                    </a>
                                                    <a href="{{ route('fenologia.create', $parcela) }}" wire:navigate
                                                        class="shrink-0 text-xs text-green-600 hover:text-green-800 font-medium" title="Anotar observación">
                                                        + Anotar
                                                    </a>
                                                </div>
                                                <p class="text-xs text-gray-400 mt-0.5 truncate">
                                                    @if($ultimo)
                                                        <span class="font-mono text-green-700">BBCH {{ $ultimo->estado?->codigo_bbch }}</span>
                                                        · {{ $ultimo->fecha_observacion->format('d/m/Y') }}
                                                    @else
                                                        Sin observaciones
                                                    @endif
                                                    @php $comparacion = $fila['referencia']['comparacion']; @endphp
                                                    @if($comparacion)
                                                        <span title="{{ $comparacion['titulo'] }}" class="ml-1 px-1.5 py-px rounded font-medium
                                                            {{ $comparacion['estado'] === 'adelantada' ? 'bg-amber-50 text-amber-700' : ($comparacion['estado'] === 'retrasada' ? 'bg-sky-50 text-sky-700' : 'bg-green-50 text-green-700') }}">
                                                            {{ ['adelantada' => 'Adelantada', 'retrasada' => 'Retrasada', 'en_fecha' => 'En fecha'][$comparacion['estado']] }}
                                                        </span>
                                                    @endif
                                                </p>
                                                <p class="text-[11px] mt-0.5 truncate {{ $fila['referencia']['generica'] ? 'text-amber-600' : 'text-gray-400' }}">
                                                    @if($fila['referencia']['generica'])
                                                        <a href="{{ route('vinedo.parcelas.edit', $parcela) }}" wire:navigate class="hover:underline"
                                                            title="Asigna la variedad para ajustar el calendario de referencia a su ciclo">
                                                            Ref. genérica · asigna la variedad →
                                                        </a>
                                                    @else
                                                        Ref. {{ $fila['referencia']['variedad'] }} · maduración {{ mb_strtolower($fila['referencia']['precocidad']) }}
                                                    @endif
                                                </p>
                                            </div>

                                            {{-- Línea del año --}}
                                            <div class="relative flex-1 mr-4">
                                                {{-- Banda de fases --}}
                                                <div class="relative h-7 rounded bg-gray-50 overflow-hidden">
                                                    @foreach($campana['meses'] as $mes)
                                                        <div class="absolute top-0 bottom-0 border-l border-gray-100" style="left: {{ $mes['izquierda'] }}%"></div>
                                                    @endforeach

                                                    @if($campana['hoy'] !== null)
                                                        {{-- Lo que queda de campaña --}}
                                                        <div class="absolute top-0 bottom-0 right-0"
                                                            style="left: {{ $campana['hoy'] }}%; background: repeating-linear-gradient(135deg, transparent 0 6px, rgba(0,0,0,.035) 6px 12px)"></div>
                                                    @endif

                                                    @foreach($fila['bandas'] as $banda)
                                                        <div class="absolute top-0 bottom-0 flex items-center overflow-hidden border-r border-white/70"
                                                            style="left: {{ $banda['izquierda'] }}%; width: {{ $banda['ancho'] }}%; background: {{ $banda['color'] }}; color: {{ $banda['texto'] }}{{ $banda['arrastrada'] ? '; opacity: .55' : '' }}"
                                                            title="{{ $banda['titulo'] }}">
                                                            @if($banda['ancho'] >= 6)
                                                                <span class="px-1.5 text-[11px] font-medium truncate">{{ $banda['etiqueta'] }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach

                                                    @if(empty($fila['bandas']))
                                                        <div class="absolute inset-0 flex items-center justify-center text-[11px] text-gray-400">
                                                            Sin observaciones en {{ $campana['anio'] }}
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Referencia: lo habitual para la variedad --}}
                                                <div class="relative h-2 mt-1 rounded-sm overflow-hidden" title="Calendario de referencia">
                                                    @foreach($fila['referencia']['bandas'] as $ref)
                                                        <div class="absolute top-0 bottom-0" title="{{ $ref['titulo'] }}"
                                                            style="left: {{ $ref['izquierda'] }}%; width: {{ $ref['ancho'] }}%; background: {{ $ref['color'] }}; opacity: .45"></div>
                                                    @endforeach
                                                </div>
                                                <div class="relative h-3">
                                                    @php $v = $fila['referencia']['vendimia']; @endphp
                                                    <div class="absolute top-0 h-2 border-x-2 border-b-2 border-violet-800 rounded-b-sm"
                                                        style="left: {{ $v['izquierda'] }}%; width: {{ $v['ancho'] }}%" title="{{ $v['titulo'] }}"></div>
                                                </div>

                                                {{-- Tratamientos y heladas --}}
                                                @if($fila['tratamientos'] || $fila['heladas'])
                                                    <div class="relative h-4 mt-0.5">
                                                        @foreach($fila['tratamientos'] as $t)
                                                            <span class="absolute top-1 -translate-x-1/2 h-2 w-2 rounded-full bg-sky-600 ring-2 ring-white"
                                                                style="left: {{ $t['pos'] }}%" title="{{ $t['titulo'] }}"></span>
                                                        @endforeach
                                                        @foreach($fila['heladas'] as $h)
                                                            <span class="absolute -top-0.5 -translate-x-1/2 text-[12px] leading-none text-blue-600 {{ $h['prevista'] ? 'opacity-50' : '' }}"
                                                                style="left: {{ $h['pos'] }}%" title="{{ $h['titulo'] }}">❄</span>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                @if($campana['hoy'] !== null)
                                                    <div class="absolute -top-1 -bottom-1 w-px bg-red-500/70 pointer-events-none" style="left: {{ $campana['hoy'] }}%"></div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <svg class="h-12 w-12 mx-auto mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="font-medium text-gray-500 mb-1">Sin fincas registradas</p>
                    <a href="{{ route('vinedo.fincas.index') }}" wire:navigate
                        class="mt-2 inline-block text-sm text-green-600 hover:text-green-800 font-medium">
                        Ir a mis fincas →
                    </a>
                </div>
            @endforelse

            {{-- Leyenda --}}
            @if($fincas->flatMap->parcelas->isNotEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 px-4 py-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-gray-600">
                    @foreach($fases as $fase)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="h-3 w-5 rounded-sm" style="background: {{ $fase['color'] }}"></span>
                            {{ $fase['nombre'] }}
                            <span class="text-gray-400 font-mono">{{ sprintf('%02d', $fase['bbch'][0]) }}–{{ sprintf('%02d', $fase['bbch'][1]) }}</span>
                        </span>
                    @endforeach
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-sky-600"></span> Tratamiento</span>
                    <span class="inline-flex items-center gap-1.5"><span class="text-blue-600">❄</span> Helada <span class="text-gray-400">(tenue: prevista)</span></span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-3 w-px bg-red-500"></span> Hoy</span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-5 rounded-sm" style="background: linear-gradient(90deg, {{ $fases['floracion']['color'] }}, {{ $fases['maduracion']['color'] }}); opacity: .45"></span>
                        Referencia de la variedad
                    </span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-5 border-x-2 border-b-2 border-violet-800 rounded-b-sm"></span> Vendimia habitual</span>
                    <span class="w-full text-gray-400">
                        La banda superior es lo observado: cada observación pinta su fase hasta la siguiente. Debajo, lo habitual en la zona interior peninsular
                        según la época de maduración de la variedad (orientativo: cada campaña se adelanta o retrasa según el tiempo).
                        Pasa el ratón por las bandas para ver el detalle.
                    </span>
                </div>
            @endif

            {{-- Historial reciente --}}
            @if($registros->isNotEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-700">Historial de observaciones</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-400 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 py-3 text-left font-medium">Fecha</th>
                                    <th class="px-4 py-3 text-left font-medium">Parcela</th>
                                    <th class="px-4 py-3 text-left font-medium">Estado BBCH</th>
                                    <th class="px-4 py-3 text-left font-medium">Observaciones</th>
                                    <th class="px-3 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($registros as $r)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-3 font-mono text-gray-600 whitespace-nowrap">
                                            {{ $r->fecha_observacion->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('vinedo.parcelas.show', $r->parcela) }}" wire:navigate
                                                class="text-gray-800 hover:text-green-700 font-medium">
                                                @if($r->parcela->poligono && $r->parcela->parcela_sigpac)
                                                    Pol. {{ $r->parcela->poligono }} · Par. {{ $r->parcela->parcela_sigpac }}
                                                @else
                                                    {{ $r->parcela->nombre }}
                                                @endif
                                            </a>
                                            <p class="text-xs text-gray-400">
                                                {{ $r->parcela->finca->paraje ?: $r->parcela->finca->provincia_nombre }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($r->estado?->codigo_bbch)
                                                <span class="font-mono text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded mr-1">
                                                    {{ $r->estado->codigo_bbch }}
                                                </span>
                                            @endif
                                            <span class="text-gray-700">{{ $r->estado?->nombre ?? '—' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 max-w-xs truncate">
                                            {{ $r->observaciones ?? '—' }}
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <form method="POST" action="{{ route('fenologia.destroy', $r) }}"
                                                onsubmit="return confirm('¿Eliminar esta observación?')">
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
                    @if($registros->hasPages())
                        <div class="px-4 py-3 border-t border-gray-100">
                            {{ $registros->links() }}
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
