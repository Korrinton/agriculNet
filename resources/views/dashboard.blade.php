<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bienvenido, {{ auth()->user()->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Módulos principales --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

                {{-- Viñedo --}}
                <a href="{{ route('vinedo.fincas.index') }}" wire:navigate
                    class="group bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-green-200 transition">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-green-100 rounded-lg group-hover:bg-green-200 transition">
                            <svg class="h-6 w-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-green-700 transition">Viñedo</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Fincas y parcelas</p>
                        </div>
                    </div>
                </a>

                {{-- Fenología --}}
                <a href="{{ route('fenologia.index') }}" wire:navigate
                    class="group bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-lime-200 transition">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-lime-100 rounded-lg group-hover:bg-lime-200 transition">
                            <svg class="h-6 w-6 text-lime-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-lime-700 transition">Fenología</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Estados BBCH y grados día</p>
                        </div>
                    </div>
                </a>

                {{-- Meteorología --}}
                <a href="{{ route('meteorologia.index') }}" wire:navigate
                    class="group bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-sky-200 transition">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-sky-100 rounded-lg group-hover:bg-sky-200 transition">
                            <svg class="h-6 w-6 text-sky-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-sky-700 transition">Meteorología</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Datos de estaciones</p>
                        </div>
                    </div>
                </a>

                {{-- Tratamientos --}}
                <a href="{{ route('tratamientos.index') }}" wire:navigate
                    class="group bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-orange-200 transition">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-orange-100 rounded-lg group-hover:bg-orange-200 transition">
                            <svg class="h-6 w-6 text-orange-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-orange-700 transition">Tratamientos</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Fitosanitarios y plazos</p>
                        </div>
                    </div>
                </a>

                {{-- Costes --}}
                <a href="{{ route('costes.index') }}" wire:navigate
                    class="group bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-yellow-200 transition">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-yellow-100 rounded-lg group-hover:bg-yellow-200 transition">
                            <svg class="h-6 w-6 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-yellow-700 transition">Costes</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Control económico</p>
                        </div>
                    </div>
                </a>

                {{-- Cuaderno Digital --}}
                <a href="{{ route('cuaderno.index') }}" wire:navigate
                    class="group bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-purple-200 transition">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-purple-100 rounded-lg group-hover:bg-purple-200 transition">
                            <svg class="h-6 w-6 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-purple-700 transition">Cuaderno Digital</h3>
                            <p class="text-sm text-gray-500 mt-0.5">CUE — Exportación MAPA</p>
                        </div>
                    </div>
                </a>

                {{-- Alertas --}}
                <a href="{{ route('alertas.index') }}" wire:navigate
                    class="group bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-red-200 transition">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-red-100 rounded-lg group-hover:bg-red-200 transition">
                            <svg class="h-6 w-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 group-hover:text-red-700 transition">Alertas</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Avisos y notificaciones</p>
                        </div>
                    </div>
                </a>

            </div>

            {{-- Acceso rápido --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Acceso rápido</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('vinedo.fincas.create') }}" wire:navigate
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nueva finca
                    </a>
                    <a href="{{ route('tratamientos.index') }}" wire:navigate
                        class="inline-flex items-center gap-2 px-4 py-2 bg-orange-100 text-orange-700 text-sm font-medium rounded-lg hover:bg-orange-200 transition">
                        Registrar tratamiento
                    </a>
                    <a href="{{ route('costes.index') }}" wire:navigate
                        class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-100 text-yellow-700 text-sm font-medium rounded-lg hover:bg-yellow-200 transition">
                        Añadir coste
                    </a>
                    <a href="{{ route('cuaderno.index') }}" wire:navigate
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 text-purple-700 text-sm font-medium rounded-lg hover:bg-purple-200 transition">
                        Exportar CUE
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
