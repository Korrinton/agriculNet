@php
    $estados = [
        'ok'           => ['Correcta', 'bg-green-50 text-green-800 ring-green-200'],
        'error'        => ['Con errores', 'bg-red-50 text-red-700 ring-red-200'],
        'en_curso'     => ['En curso', 'bg-sky-50 text-sky-700 ring-sky-200'],
        'interrumpida' => ['Interrumpida', 'bg-amber-50 text-amber-800 ring-amber-200'],
    ];
    $duracion = function (?int $s) {
        if ($s === null) return '—';
        return $s < 60 ? "{$s} s" : intdiv($s, 60) . ' min ' . ($s % 60) . ' s';
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin._cabecera', ['titulo' => 'Tareas programadas'])
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if($sinClave)
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                    <strong>AEMET_API_KEY</strong> no está configurada: las tareas de AEMET fallarán hasta que se añada al <code class="font-mono">.env</code>.
                </div>
            @endif

            @foreach($tareas as $t)
                @php
                    $ultima = $t['ultima'];
                    $estado = $ultima?->estado();
                @endphp
                <section class="bg-white rounded-xl shadow-sm border {{ in_array($estado, ['error', 'interrumpida'], true) ? 'border-red-200' : 'border-gray-100' }} p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold text-gray-800">
                                {{ $t['nombre'] }}
                                <code class="ml-1 text-xs font-normal text-gray-400">{{ $t['comando'] }}</code>
                            </h3>
                            <p class="text-sm text-gray-500 mt-0.5">{{ $t['descripcion'] }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $t['horario'] }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.tareas.lanzar', $t['comando']) }}">
                            @csrf
                            <button class="pulsable px-3 py-1.5 text-sm font-medium text-green-800 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100"
                                @disabled($estado === 'en_curso')>
                                Ejecutar ahora
                            </button>
                        </form>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                        @if($ultima)
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full ring-1 {{ $estados[$estado][1] }}">{{ $estados[$estado][0] }}</span>
                            <span class="text-gray-600">Última: {{ $ultima->inicio->timezone('Europe/Madrid')->format('d/m/Y H:i') }}</span>
                            <span class="text-gray-400">Duración: {{ $duracion($ultima->duracionSegundos()) }}</span>
                            @if($estado !== 'ok' && $t['ultimoExito'])
                                <span class="text-gray-400">Última correcta: {{ \Carbon\Carbon::parse($t['ultimoExito'])->timezone('Europe/Madrid')->format('d/m/Y H:i') }}</span>
                            @endif
                        @else
                            <span class="text-gray-400">Sin ejecuciones registradas todavía.</span>
                        @endif
                    </div>

                    @if($t['ejecuciones']->isNotEmpty())
                        <details class="mt-3 group">
                            <summary class="cursor-pointer text-xs text-gray-500 hover:text-gray-700 select-none">Historial y salida</summary>
                            <ul class="aparece mt-2 divide-y divide-gray-100 border border-gray-100 rounded-lg text-xs">
                                @foreach($t['ejecuciones'] as $e)
                                    <li class="px-3 py-2">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <span class="font-medium {{ in_array($e->estado(), ['error', 'interrumpida'], true) ? 'text-red-700' : 'text-gray-700' }}">{{ $estados[$e->estado()][0] }}</span>
                                            <span class="text-gray-500">{{ $e->inicio->timezone('Europe/Madrid')->format('d/m/Y H:i') }}</span>
                                            <span class="text-gray-400">{{ $duracion($e->duracionSegundos()) }}</span>
                                            <span class="text-gray-400">{{ $e->user ? 'Lanzada por ' . $e->user->name : 'Programada' }}</span>
                                        </div>
                                        @if($e->salida)
                                            <pre class="mt-1.5 max-h-48 overflow-auto whitespace-pre-wrap rounded bg-gray-50 p-2 font-mono text-[11px] text-gray-600">{{ $e->salida }}</pre>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </section>
            @endforeach

            <p class="text-xs text-gray-400">
                «Ejecutar ahora» la manda a la cola: la ejecuta el contenedor <code class="font-mono">queue-worker</code> y su salida aparece aquí al terminar.
                La salida de las ejecuciones programadas va al log del contenedor <code class="font-mono">scheduler</code>.
            </p>
        </div>
    </div>
</x-app-layout>
