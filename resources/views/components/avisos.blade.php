@php
    // Mensajes de la última acción: los de éxito se cierran solos, los de error se quedan hasta cerrarlos
    $avisos = collect([
        ['tipo' => 'exito', 'texto' => session('success')],
        ['tipo' => 'error', 'texto' => session('error')],
    ])->filter(fn ($a) => filled($a['texto']))->values();
@endphp

@if($avisos->isNotEmpty())
    <div class="pointer-events-none fixed inset-x-0 top-[4.5rem] z-50 flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6 lg:px-8"
        aria-live="polite" role="status">
        @foreach($avisos as $aviso)
            @php $error = $aviso['tipo'] === 'error'; @endphp
            <div x-data="{ visible: true, saliendo: false, cerrar() { this.saliendo = true; setTimeout(() => this.visible = false, 180) } }"
                x-show="visible"
                :class="saliendo ? 'aviso-sale' : 'aviso-entra'"
                class="aviso aviso-entra pointer-events-auto relative w-full max-w-sm overflow-hidden rounded-xl bg-white shadow-[0_10px_30px_-12px_rgb(20_83_45/0.35)] ring-1 {{ $error ? 'ring-red-200' : 'ring-green-200' }}"
                style="--duracion: 6s">
                <div class="flex items-start gap-3 p-4 pr-10">
                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full {{ $error ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-700' }}">
                        @if($error)
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 8v5m0 3.5v.01"/></svg>
                        @else
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                        @endif
                    </span>
                    <p class="text-sm leading-snug {{ $error ? 'text-red-800' : 'text-stone-800' }}">{{ $aviso['texto'] }}</p>
                </div>
                <button type="button" @click="cerrar()"
                    class="absolute right-2 top-2 rounded-md p-1.5 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
                    aria-label="Cerrar aviso">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                @unless($error)
                    {{-- Al acabar la barra se cierra; pasar el ratón por encima la pausa --}}
                    <span class="aviso-tiempo absolute inset-x-0 bottom-0 h-0.5 bg-green-500/70" aria-hidden="true" @animationend="cerrar()"></span>
                @endunless
            </div>
        @endforeach
    </div>
@endif
