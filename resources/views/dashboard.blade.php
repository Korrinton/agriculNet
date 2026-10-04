<x-app-layout>

    @php
        $hora = now()->hour;
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 20 ? 'Buenas tardes' : 'Buenas noches');
        $nombre = Str::of(auth()->user()->name)->explode(' ')->first();
    @endphp

    {{-- Hero --}}
    <div class="bg-white border-b border-stone-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-stone-900">{{ $saludo }}, {{ $nombre }}</h1>
                    <p class="text-stone-400 text-sm mt-1">
                        {{ ucfirst(\Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY')) }}
                    </p>
                </div>
                <a href="{{ route('vinedo.fincas.create') }}" wire:navigate
                   class="shrink-0 hidden sm:inline-flex items-center gap-2 px-4 py-2 bg-green-700 text-white text-sm font-semibold rounded-lg hover:bg-green-800 active:bg-green-900 transition duration-150">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva finca
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        {{-- Módulos --}}
        <div>
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-widest mb-4">Módulos</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">

                {{-- Viñedo --}}
                <a href="{{ route('vinedo.fincas.index') }}" wire:navigate
                    class="group bg-white rounded-xl border border-stone-200 p-5 hover:border-green-300 hover:shadow-sm transition duration-150">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 bg-green-50 rounded-lg ring-1 ring-green-100 shrink-0">
                            <svg class="h-5 w-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-stone-800 group-hover:text-green-700 transition duration-150 text-sm">Viñedo</p>
                                <svg class="h-3.5 w-3.5 text-stone-300 group-hover:text-green-400 transition duration-150 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <p class="text-xs text-stone-400 mt-0.5">Fincas y parcelas</p>
                        </div>
                    </div>
                </a>

                {{-- Fenología --}}
                <a href="{{ route('fenologia.index') }}" wire:navigate
                    class="group bg-white rounded-xl border border-stone-200 p-5 hover:border-emerald-300 hover:shadow-sm transition duration-150">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 bg-emerald-50 rounded-lg ring-1 ring-emerald-100 shrink-0">
                            <svg class="h-5 w-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-stone-800 group-hover:text-emerald-700 transition duration-150 text-sm">Fenología</p>
                                <svg class="h-3.5 w-3.5 text-stone-300 group-hover:text-emerald-400 transition duration-150 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <p class="text-xs text-stone-400 mt-0.5">Estados BBCH y grados día</p>
                        </div>
                    </div>
                </a>

                {{-- Meteorología --}}
                <a href="{{ route('meteorologia.index') }}" wire:navigate
                    class="group bg-white rounded-xl border border-stone-200 p-5 hover:border-sky-300 hover:shadow-sm transition duration-150">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 bg-sky-50 rounded-lg ring-1 ring-sky-100 shrink-0">
                            <svg class="h-5 w-5 text-sky-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-stone-800 group-hover:text-sky-700 transition duration-150 text-sm">Meteorología</p>
                                <svg class="h-3.5 w-3.5 text-stone-300 group-hover:text-sky-400 transition duration-150 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <p class="text-xs text-stone-400 mt-0.5">Datos de estaciones AEMET</p>
                        </div>
                    </div>
                </a>

                {{-- Tratamientos --}}
                <a href="{{ route('tratamientos.index') }}" wire:navigate
                    class="group bg-white rounded-xl border border-stone-200 p-5 hover:border-amber-300 hover:shadow-sm transition duration-150">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 bg-amber-50 rounded-lg ring-1 ring-amber-100 shrink-0">
                            <svg class="h-5 w-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-stone-800 group-hover:text-amber-700 transition duration-150 text-sm">Tratamientos</p>
                                <svg class="h-3.5 w-3.5 text-stone-300 group-hover:text-amber-400 transition duration-150 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <p class="text-xs text-stone-400 mt-0.5">Fitosanitarios y plazos</p>
                        </div>
                    </div>
                </a>

                {{-- Riegos --}}
                <a href="{{ route('riegos.index') }}" wire:navigate
                    class="group bg-white rounded-xl border border-stone-200 p-5 hover:border-cyan-300 hover:shadow-sm transition duration-150">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 bg-cyan-50 rounded-lg ring-1 ring-cyan-100 shrink-0">
                            <svg class="h-5 w-5 text-cyan-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3c-3 4.5-6 7.8-6 11a6 6 0 0012 0c0-3.2-3-6.5-6-11z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-stone-800 group-hover:text-cyan-700 transition duration-150 text-sm">Riegos</p>
                                <svg class="h-3.5 w-3.5 text-stone-300 group-hover:text-cyan-400 transition duration-150 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <p class="text-xs text-stone-400 mt-0.5">Agua aplicada por parcela</p>
                        </div>
                    </div>
                </a>

                {{-- Costes --}}
                <a href="{{ route('costes.index') }}" wire:navigate
                    class="group bg-white rounded-xl border border-stone-200 p-5 hover:border-yellow-300 hover:shadow-sm transition duration-150">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 bg-yellow-50 rounded-lg ring-1 ring-yellow-100 shrink-0">
                            <svg class="h-5 w-5 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-stone-800 group-hover:text-yellow-700 transition duration-150 text-sm">Costes</p>
                                <svg class="h-3.5 w-3.5 text-stone-300 group-hover:text-yellow-400 transition duration-150 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <p class="text-xs text-stone-400 mt-0.5">Control económico</p>
                        </div>
                    </div>
                </a>

                {{-- Cuaderno Digital --}}
                <a href="{{ route('cuaderno.index') }}" wire:navigate
                    class="group bg-white rounded-xl border border-stone-200 p-5 hover:border-teal-300 hover:shadow-sm transition duration-150">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 bg-teal-50 rounded-lg ring-1 ring-teal-100 shrink-0">
                            <svg class="h-5 w-5 text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-stone-800 group-hover:text-teal-700 transition duration-150 text-sm">Cuaderno Digital</p>
                                <svg class="h-3.5 w-3.5 text-stone-300 group-hover:text-teal-400 transition duration-150 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <p class="text-xs text-stone-400 mt-0.5">Tratamientos, fertilización y cosecha</p>
                        </div>
                    </div>
                </a>

                {{-- Alertas --}}
                <a href="{{ route('alertas.index') }}" wire:navigate
                    class="group bg-white rounded-xl border border-stone-200 p-5 hover:border-rose-300 hover:shadow-sm transition duration-150">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2.5 bg-rose-50 rounded-lg ring-1 ring-rose-100 shrink-0">
                            <svg class="h-5 w-5 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold text-stone-800 group-hover:text-rose-700 transition duration-150 text-sm">Alertas</p>
                                <svg class="h-3.5 w-3.5 text-stone-300 group-hover:text-rose-400 transition duration-150 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                            <p class="text-xs text-stone-400 mt-0.5">Avisos y notificaciones</p>
                        </div>
                    </div>
                </a>

            </div>
        </div>

        {{-- Acciones rápidas --}}
        <div class="bg-white rounded-xl border border-stone-200 p-6">
            <p class="text-xs font-semibold text-stone-400 uppercase tracking-widest mb-4">Acciones rápidas</p>
            <div class="flex flex-wrap gap-2.5">
                <a href="{{ route('vinedo.fincas.create') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-green-700 text-white text-sm font-medium rounded-lg hover:bg-green-800 transition duration-150">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva finca
                </a>
                <a href="{{ route('tratamientos.index') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-amber-50 text-amber-700 text-sm font-medium rounded-lg border border-amber-200 hover:bg-amber-100 transition duration-150">
                    Registrar tratamiento
                </a>
                <a href="{{ route('costes.index') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-yellow-50 text-yellow-700 text-sm font-medium rounded-lg border border-yellow-200 hover:bg-yellow-100 transition duration-150">
                    Añadir coste
                </a>
                <a href="{{ route('cuaderno.index') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-3.5 py-2 bg-teal-50 text-teal-700 text-sm font-medium rounded-lg border border-teal-200 hover:bg-teal-100 transition duration-150">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Exportar CUE
                </a>
            </div>
        </div>

    </div>
</x-app-layout>
