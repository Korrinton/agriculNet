{{--
    Barras de una sola serie por mes (últimos 12). Parámetros: $titulo, $serie (mes, etiqueta, total), $unidad.
    Un solo tono: la serie la nombra el título, sin leyenda. Se rotulan solo el máximo y el último mes;
    el resto se lee al pasar el ratón o en la tabla para lectores de pantalla.
--}}
@php
    $maximo = max(1, ...array_column($serie, 'total'));
    $indiceMax = array_search(max(array_column($serie, 'total')), array_column($serie, 'total'));
    $ultimo = count($serie) - 1;
    $total = array_sum(array_column($serie, 'total'));
@endphp
<section class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-baseline justify-between gap-3">
        <h3 class="text-sm font-semibold text-gray-700">{{ $titulo }}</h3>
        <p class="text-xs text-gray-400">{{ number_format($total, 0, ',', '.') }} {{ $unidad }} en 12 meses</p>
    </div>

    <div class="mt-6 flex items-end gap-[2px] h-36 border-b border-gray-200" aria-hidden="true">
        @foreach($serie as $i => $punto)
            <div class="group relative flex-1 h-full flex items-end">
                {{-- Zona de hover de toda la columna, más grande que la barra --}}
                <div class="w-full rounded-t-[4px] bg-green-600 group-hover:bg-green-700 transition-colors"
                    style="height: {{ $punto['total'] > 0 ? max(2, round($punto['total'] / $maximo * 100, 1)) : 0 }}%"></div>
                @if($punto['total'] > 0 && ($i === $indiceMax || $i === $ultimo))
                    <span class="absolute left-1/2 -translate-x-1/2 text-[11px] font-medium text-gray-600 tabular-nums group-hover:hidden"
                        style="bottom: calc({{ round($punto['total'] / $maximo * 100, 1) }}% + 2px)">{{ number_format($punto['total'], 0, ',', '.') }}</span>
                @endif
                <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block z-10
                    whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white shadow">
                    {{ $punto['etiqueta'] }}: <strong class="tabular-nums">{{ number_format($punto['total'], 0, ',', '.') }}</strong> {{ $unidad }}
                </span>
            </div>
        @endforeach
    </div>
    <div class="mt-1 flex gap-[2px] text-[10px] text-gray-400" aria-hidden="true">
        @foreach($serie as $i => $punto)
            <span class="flex-1 text-center truncate">{{ $i % 2 === $ultimo % 2 ? $punto['etiqueta'] : '' }}</span>
        @endforeach
    </div>

    <table class="sr-only">
        <caption>{{ $titulo }}</caption>
        <thead><tr><th>Mes</th><th>{{ ucfirst($unidad) }}</th></tr></thead>
        <tbody>
            @foreach($serie as $punto)
                <tr><td>{{ $punto['etiqueta'] }}</td><td>{{ $punto['total'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
</section>
