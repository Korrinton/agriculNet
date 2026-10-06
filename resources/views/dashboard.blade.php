@php
    use App\Modules\CalendarioFenologico\Services\CalendarioCampana;

    $ahora = now('Europe/Madrid');
    $saludo = $ahora->hour < 6 ? 'Buenas noches' : ($ahora->hour < 13 ? 'Buenos días' : ($ahora->hour < 21 ? 'Buenas tardes' : 'Buenas noches'));
    $nombre = Str::of(auth()->user()->name)->explode(' ')->first();
    $eur = fn ($v) => number_format((float) $v, 2, ',', '.') . ' €';
    $num = fn ($v, $d = 0) => number_format((float) $v, $d, ',', '.');
    $nombreFinca = fn ($f) => ($f->paraje ?: $f->provincia_nombre);
    $estiloNivel = [
        'critical' => ['punto' => 'bg-red-500', 'texto' => 'text-red-700', 'fondo' => 'bg-red-50', 'nombre' => 'Crítica'],
        'warning'  => ['punto' => 'bg-amber-500', 'texto' => 'text-amber-800', 'fondo' => 'bg-amber-50', 'nombre' => 'Aviso'],
        'info'     => ['punto' => 'bg-sky-500', 'texto' => 'text-sky-800', 'fondo' => 'bg-sky-50', 'nombre' => 'Información'],
    ];
@endphp

<x-app-layout>
    {{-- ── Banda superior: saludo, campaña y el tiempo que viene en la finca ───────────────── --}}
    <section class="bg-green-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-10">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-balance">{{ $saludo }}, {{ $nombre }}</h1>
                    <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-green-200">
                        <span>{{ ucfirst($ahora->locale('es')->isoFormat('dddd, D [de] MMMM')) }}</span>
                        @if(!empty($campana['fase']))
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2.5 py-0.5 text-green-50">
                                <span class="h-2 w-2 rounded-full" style="background: {{ $campana['fase']['color'] }}"></span>
                                La viña está en {{ mb_strtolower($campana['fase']['nombre']) }}
                            </span>
                        @endif
                    </p>
                </div>

                @if($fincas->count() > 1)
                    {{-- Cambiar de finca sin perder el panel --}}
                    <nav class="flex max-w-full gap-1 overflow-x-auto rounded-xl bg-green-950/50 p-1" aria-label="Elegir finca">
                        @foreach($fincas as $f)
                            @php $activa = $finca && $f->id === $finca->id; @endphp
                            <a href="{{ route('dashboard', ['finca' => $f->id]) }}" wire:navigate
                                @if($activa) aria-current="page" @endif
                                class="pulsable shrink-0 rounded-lg px-3 py-1.5 text-sm {{ $activa ? 'bg-white text-green-900 shadow-sm font-semibold' : 'text-green-200 hover:bg-white/10 hover:text-white' }}">
                                {{ $nombreFinca($f) }}
                                <span class="cifra {{ $activa ? 'text-green-700' : 'text-green-400' }}">· {{ $num($f->parcelas->sum('superficie_ha'), 1) }} ha</span>
                            </a>
                        @endforeach
                    </nav>
                @endif
            </div>

            @if(!$finca)
                {{-- Primera visita: aún no hay fincas --}}
                <div class="mt-8 grid gap-6 rounded-2xl bg-green-950/40 p-6 sm:p-8 lg:grid-cols-[1.2fr_1fr] lg:items-center">
                    <div>
                        <h2 class="text-xl font-semibold">Da de alta tu primera finca</h2>
                        <p class="mt-2 max-w-prose text-green-100/90">Con la provincia, el municipio y las parcelas SIGPAC, el panel te mostrará aquí la previsión de AEMET de tu municipio, el riesgo de helada, los plazos de seguridad en curso y cómo va la campaña.</p>
                        <a href="{{ route('vinedo.fincas.create') }}" wire:navigate
                            class="pulsable mt-5 inline-flex items-center gap-2 rounded-lg bg-lime-300 px-4 py-2.5 text-sm font-semibold text-green-950 hover:bg-lime-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                            Crear finca
                        </a>
                    </div>
                    <ol class="space-y-3 text-sm text-green-100">
                        @foreach(['Crea la finca con su provincia y municipio.', 'Añade sus parcelas (polígono, parcela y recinto SIGPAC).', 'Vincula la estación AEMET más cercana para los datos del tiempo.'] as $i => $paso)
                            <li class="flex gap-3">
                                <span class="cifra flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold">{{ $i + 1 }}</span>
                                <span class="pt-0.5">{{ $paso }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @elseif(empty($prevision['dias']))
                <div class="mt-8 rounded-2xl bg-green-950/40 p-6 text-green-100">
                    <p class="font-medium text-white">Todavía no hay previsión para {{ $nombreFinca($finca) }}</p>
                    <p class="mt-1 text-sm">La previsión de AEMET se descarga cada mañana a las 6:30 para el municipio de cada finca.</p>
                    <a href="{{ route('meteorologia.index') }}" wire:navigate class="mt-3 inline-block text-sm font-medium text-lime-300 underline hover:text-lime-200">Ir a meteorología</a>
                </div>
            @else
                @php
                    $dias = $prevision['dias'];
                    $diasHelada = collect($dias)->where('helada', true);
                    $diasLluvia = collect($dias)->filter(fn ($d) => ($d['lluvia'] ?? 0) >= 50);
                    $lista = fn ($c) => $c->map(fn ($d) => $d['dia'] === 'Hoy' ? 'hoy' : mb_strtolower($d['dia']) . ' ' . $d['numero'])->join(', ', ' y ');
                    // Línea de 0 °C dentro de la escala (referencia para las heladas)
                    $cero = $prevision['min'] < 0 ? round((0 - $prevision['min']) / max(1, $prevision['max'] - $prevision['min']) * 100, 1) : null;
                @endphp
                <div class="mt-8">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 class="text-lg font-semibold">
                            @if($diasHelada->isNotEmpty())
                                <span class="text-sky-200">Riesgo de helada: {{ $lista($diasHelada) }}</span>
                            @elseif($diasLluvia->isNotEmpty())
                                Lluvia probable: {{ $lista($diasLluvia) }}
                            @else
                                Semana sin riesgo de helada
                            @endif
                        </h2>
                        <p class="text-xs text-green-300">Previsión AEMET para {{ $nombreFinca($finca) }} ({{ $finca->codigo_ine }})</p>
                    </div>

                    <ol class="mt-4 grid grid-cols-7 gap-1 sm:gap-2" aria-label="Previsión de 7 días">
                        @foreach($dias as $i => $d)
                            @php $calor = $d['max'] >= 35; @endphp
                            <li class="flex flex-col items-center rounded-xl px-0.5 py-3 sm:px-2 {{ $d['helada'] ? 'dia-helada bg-sky-300/15 ring-1 ring-sky-200/40' : ($d['dia'] === 'Hoy' ? 'bg-white/[0.07]' : '') }}"
                                title="{{ $d['cielo'] }}">
                                <span class="text-xs font-semibold {{ $d['dia'] === 'Hoy' ? 'text-white' : 'text-green-200' }}">{{ $d['dia'] }}</span>
                                <span class="cifra text-[11px] text-green-400">{{ $d['numero'] }}</span>
                                <x-icono-cielo :tipo="$d['icono']" class="mt-2 h-6 w-6 {{ $d['helada'] ? 'text-sky-200' : 'text-green-100' }}" />
                                <span class="cifra mt-2 text-sm font-semibold {{ $calor ? 'text-amber-300' : 'text-white' }}">{{ $d['max'] }}°</span>

                                {{-- Rango de temperaturas del día sobre la escala común de la semana --}}
                                <div class="relative mt-1.5 h-24 w-full sm:h-32" aria-hidden="true">
                                    @if($cero !== null)
                                        <span class="absolute inset-x-1 border-t border-dashed border-sky-200/50" style="bottom: {{ $cero }}%"></span>
                                    @endif
                                    <span class="barra-temp absolute left-1/2 w-2.5 -translate-x-1/2 rounded-full sm:w-3.5
                                        {{ $d['helada'] ? 'bg-gradient-to-t from-sky-200 to-sky-400' : ($calor ? 'bg-gradient-to-t from-lime-300 to-amber-400' : 'bg-gradient-to-t from-emerald-400 to-lime-300') }}"
                                        style="bottom: {{ $d['desde'] }}%; height: {{ $d['alto'] }}%; --i: {{ $i }}"></span>
                                </div>

                                <span class="cifra mt-1.5 text-sm {{ $d['helada'] ? 'font-semibold text-sky-200' : 'text-green-200' }}">{{ $d['min'] }}°</span>
                                @if($d['helada'])
                                    <span class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-sky-200">Helada</span>
                                @endif
                                @if($d['lluvia'] !== null)
                                    <span class="cifra mt-1.5 inline-flex items-center gap-0.5 text-[11px] {{ $d['lluvia'] >= 50 ? 'text-sky-200' : 'text-green-400' }}">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3s-6 6.7-6 11a6 6 0 0 0 12 0c0-4.3-6-11-6-11Z" opacity="{{ max(0.35, $d['lluvia'] / 100) }}"/></svg>
                                        {{ $d['lluvia'] }}%
                                    </span>
                                @endif
                                <span class="sr-only">{{ $d['cielo'] }}: máxima {{ $d['max'] }}°, mínima {{ $d['min'] }}°{{ $d['helada'] ? ', riesgo de helada' : '' }}{{ $d['lluvia'] !== null ? ', probabilidad de lluvia ' . $d['lluvia'] . '%' : '' }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </section>

    @if($finca)
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid gap-6 lg:grid-cols-5">

            {{-- ── Columna principal: lo que requiere atención y lo último anotado ────────────── --}}
            <div class="space-y-6 lg:col-span-3">
                <section class="rounded-2xl bg-white ring-1 ring-stone-200/80 shadow-[0_1px_2px_rgb(28_25_23/0.04)]" aria-labelledby="atencion">
                    <div class="flex items-center justify-between px-5 pt-5">
                        <h2 id="atencion" class="text-base font-semibold text-stone-900">Requiere atención</h2>
                        @if($alertasTotal > 0)
                            <a href="{{ route('alertas.index') }}" wire:navigate class="text-sm font-medium text-green-700 hover:text-green-900">
                                {{ $alertasTotal === 1 ? 'Ver la alerta' : 'Ver las ' . $alertasTotal . ' alertas' }}
                            </a>
                        @endif
                    </div>

                    @if($alertas->isEmpty() && $plazos->isEmpty())
                        <div class="flex items-start gap-3 px-5 pb-6 pt-4">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-green-50 text-green-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                            </span>
                            <div>
                                <p class="font-medium text-stone-800">Todo en orden</p>
                                <p class="mt-0.5 text-sm text-stone-500">No hay alertas sin leer ni parcelas en plazo de seguridad: se puede cosechar en todas.</p>
                            </div>
                        </div>
                    @else
                        <ul class="mt-3 divide-y divide-stone-100">
                            @foreach($alertas as $a)
                                @php $e = $estiloNivel[$a->nivel] ?? $estiloNivel['info']; @endphp
                                <li>
                                    <a href="{{ route('alertas.index') }}" wire:navigate class="group flex gap-3 px-5 py-3.5 transition-colors hover:bg-stone-50">
                                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $e['punto'] }}" aria-hidden="true"></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm text-stone-800 group-hover:text-stone-950">{{ $a->mensaje }}</p>
                                            <p class="mt-0.5 text-xs text-stone-500">
                                                <span class="{{ $e['texto'] }} font-medium">{{ $e['nombre'] }}</span>
                                                · {{ $a->parcela?->etiqueta ?? 'Finca' }}
                                                · {{ $a->created_at->locale('es')->diffForHumans() }}
                                            </p>
                                        </div>
                                    </a>
                                </li>
                            @endforeach

                            @foreach($plazos as $p)
                                @php $t = $p['tratamiento']; @endphp
                                <li class="px-5 py-3.5">
                                    <div class="flex items-baseline justify-between gap-3">
                                        <p class="text-sm text-stone-800">
                                            <span class="font-medium">No cosechar {{ $t->parcela->etiqueta }}</span>
                                            <span class="text-stone-500">hasta el {{ $p['fin']->format('d/m') }}</span>
                                        </p>
                                        <span class="cifra shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                            {{ $p['quedan'] === 0 ? 'termina hoy' : ($p['quedan'] === 1 ? 'queda 1 día' : 'quedan ' . $p['quedan'] . ' días') }}
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-stone-500">Plazo de seguridad de {{ $t->producto?->nombre }} ({{ $p['plazo'] }} días), aplicado el {{ $t->fecha->format('d/m') }}</p>
                                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-stone-100" role="progressbar" aria-valuenow="{{ $p['avance'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Plazo transcurrido">
                                        <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-green-500" style="width: {{ $p['avance'] }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="rounded-2xl bg-white ring-1 ring-stone-200/80 shadow-[0_1px_2px_rgb(28_25_23/0.04)]" aria-labelledby="actividad">
                    <h2 id="actividad" class="px-5 pt-5 text-base font-semibold text-stone-900">Lo último anotado</h2>
                    @if($actividad->isEmpty())
                        <p class="px-5 pb-6 pt-2 text-sm text-stone-500">
                            Aquí verás tus tratamientos, gastos, riegos y observaciones según los vayas anotando.
                            <a href="{{ route('tratamientos.finca.create', $finca) }}" wire:navigate class="font-medium text-green-700 underline hover:text-green-900">Registra el primer tratamiento</a>.
                        </p>
                    @else
                        @php
                            $iconos = [
                                'tratamiento' => ['bg-green-50 text-green-700', 'M9 3h6M10 3v5.5L5.5 17a2.5 2.5 0 0 0 2.2 3.5h8.6a2.5 2.5 0 0 0 2.2-3.5L14 8.5V3'],
                                'gasto'       => ['bg-amber-50 text-amber-700', 'M14.5 8.5A3.5 3.5 0 0 0 8 10c0 4 7 2 7 6a3.5 3.5 0 0 1-6.5 1.5M12 5v2m0 10v2'],
                                'riego'       => ['bg-sky-50 text-sky-700', 'M12 3.5s-6 6.4-6 10.5a6 6 0 0 0 12 0c0-4.1-6-10.5-6-10.5Z'],
                                'fenologia'   => ['bg-violet-50 text-violet-700', 'M12 21V10m0 0c0-4 3-6.5 7-6.5 0 4-3 6.5-7 6.5Zm0 3c0-3-2.5-5-6-5 0 3 2.5 5 6 5Z'],
                            ];
                        @endphp
                        <ol class="mt-2 pb-2">
                            @foreach($actividad as $e)
                                @php [$color, $trazo] = $iconos[$e['tipo']]; @endphp
                                <li>
                                    <a href="{{ $e['url'] }}" wire:navigate class="flex items-center gap-3 px-5 py-2.5 transition-colors hover:bg-stone-50">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $color }}">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $trazo }}"/></svg>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-stone-800">{{ $e['titulo'] }}</p>
                                            <p class="truncate text-xs text-stone-500">{{ $e['detalle'] }}</p>
                                        </div>
                                        <time class="cifra shrink-0 text-xs text-stone-400" datetime="{{ $e['fecha']->toDateString() }}">{{ $e['fecha']->format('d/m') }}</time>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>
            </div>

            {{-- ── Columna lateral: la campaña y lo más usado ─────────────────────────────────── --}}
            <aside class="space-y-6 lg:col-span-2">
                <section class="rounded-2xl bg-white p-5 ring-1 ring-stone-200/80 shadow-[0_1px_2px_rgb(28_25_23/0.04)]" aria-labelledby="campana">
                    <div class="flex items-baseline justify-between gap-2">
                        <h2 id="campana" class="text-base font-semibold text-stone-900">Campaña {{ $cifras['anio'] }}</h2>
                        <span class="cifra text-xs text-stone-500">{{ $num($superficie, 2) }} ha en {{ $fincas->count() === 1 ? '1 finca' : $fincas->count() . ' fincas' }}</span>
                    </div>

                    @if($campana && $campana['fase'])
                        <div class="mt-4">
                            <p class="text-sm text-stone-600">
                                <span class="font-semibold text-stone-900">{{ $campana['fase']['nombre'] }}</span>
                                @if($campana['observado'])
                                    · observada el {{ $campana['observado']->fecha_observacion->format('d/m') }} (BBCH {{ $campana['observado']->estado?->codigo_bbch }})
                                @else
                                    · estimada para {{ $campana['variedad'] }}
                                @endif
                            </p>
                            {{-- El año de la viña: fases esperadas y dónde estamos hoy --}}
                            <div class="relative mt-3 pt-5">
                                @if($campana['hoy'] !== null)
                                    <span class="absolute top-0 -translate-x-1/2 text-[10px] font-semibold uppercase tracking-wide text-stone-700" style="left: {{ $campana['hoy'] }}%">Hoy</span>
                                @endif
                                <div class="relative flex h-3 overflow-hidden rounded-full ring-1 ring-black/5">
                                    @foreach($campana['bandas'] as $b)
                                        <span class="h-full" style="width: {{ $b['ancho'] }}%; background: {{ $b['color'] }}" title="{{ $b['titulo'] }}"></span>
                                    @endforeach
                                </div>
                                @if($campana['hoy'] !== null)
                                    <span class="absolute bottom-[-3px] top-4 w-0.5 -translate-x-1/2 rounded-full bg-stone-900" style="left: {{ $campana['hoy'] }}%" aria-hidden="true"></span>
                                @endif
                                <div class="mt-1.5 flex justify-between text-[10px] text-stone-400" aria-hidden="true">
                                    @foreach($campana['meses'] as $i => $m)
                                        @if($i % 2 === 0)<span>{{ $m['nombre'] }}</span>@endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <dl class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-stone-200/70 ring-1 ring-stone-200/70">
                        <div class="bg-white p-3.5">
                            <dt class="text-xs text-stone-500">Tratamientos</dt>
                            <dd class="cifra mt-1 text-lg font-semibold text-stone-900">{{ $cifras['tratamientos'] }}</dd>
                        </div>
                        <div class="bg-white p-3.5">
                            <dt class="text-xs text-stone-500">Gastos</dt>
                            <dd class="cifra mt-1 text-lg font-semibold text-stone-900">{{ $eur($cifras['gastos']) }}</dd>
                            @if($cifras['generales'] > 0)
                                <dd class="cifra text-[11px] text-stone-500">{{ $eur($cifras['generales']) }} generales</dd>
                            @endif
                        </div>
                        <div class="bg-white p-3.5">
                            <dt class="text-xs text-stone-500">Agua de riego</dt>
                            <dd class="cifra mt-1 text-lg font-semibold text-stone-900">{{ $num($cifras['agua']) }} m³</dd>
                        </div>
                        <div class="bg-white p-3.5">
                            <dt class="text-xs text-stone-500">Grados-día desde abril</dt>
                            <dd class="cifra mt-1 text-lg font-semibold text-stone-900">{{ $cifras['gdd'] !== null ? $num($cifras['gdd']) : '—' }}</dd>
                            @if($cifras['gdd'] === null)
                                <dd class="text-[11px] text-stone-500">Sin estación o sin viña</dd>
                            @endif
                        </div>
                    </dl>
                </section>

                <section class="rounded-2xl bg-white p-5 ring-1 ring-stone-200/80 shadow-[0_1px_2px_rgb(28_25_23/0.04)]" aria-labelledby="anotar">
                    <h2 id="anotar" class="text-base font-semibold text-stone-900">Anotar en {{ $nombreFinca($finca) }}</h2>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <a href="{{ route('tratamientos.finca.create', $finca) }}" wire:navigate
                            class="pulsable col-span-2 flex items-center justify-center gap-2 rounded-lg bg-green-700 px-3 py-2.5 text-sm font-semibold text-white hover:bg-green-800">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                            Tratamiento
                        </a>
                        <a href="{{ route('costes.finca.create', $finca) }}" wire:navigate
                            class="pulsable rounded-lg px-3 py-2 text-center text-sm font-medium text-stone-700 ring-1 ring-stone-200 hover:bg-stone-50 hover:text-stone-900">Gasto</a>
                        @if($finca->parcelas->contains(fn ($p) => $p->esRegable()))
                            <a href="{{ route('riegos.create', $finca) }}" wire:navigate
                                class="pulsable rounded-lg px-3 py-2 text-center text-sm font-medium text-stone-700 ring-1 ring-stone-200 hover:bg-stone-50 hover:text-stone-900">Riego</a>
                        @endif
                        <a href="{{ route('fenologia.index') }}" wire:navigate
                            class="pulsable rounded-lg px-3 py-2 text-center text-sm font-medium text-stone-700 ring-1 ring-stone-200 hover:bg-stone-50 hover:text-stone-900">Fenología</a>
                        <a href="{{ route('cuaderno.index', ['finca' => $finca->id]) }}" wire:navigate
                            class="pulsable rounded-lg px-3 py-2 text-center text-sm font-medium text-stone-700 ring-1 ring-stone-200 hover:bg-stone-50 hover:text-stone-900">Cuaderno</a>
                    </div>
                </section>
            </aside>
        </div>
    @endif
</x-app-layout>
