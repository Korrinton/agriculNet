<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Perfil
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.alertas-correo-form />
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <section class="max-w-xl space-y-4">
                    <header>
                        <h2 class="text-lg font-medium text-gray-900">Descargar mis datos</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Todo lo que guardas en {{ config('app.name') }}: tu cuenta, fincas, parcelas y todos sus registros
                            (tratamientos, costes, riegos, cosechas, fertilizaciones, observaciones, alertas y productos propios).
                            Se descarga un .zip con un fichero JSON y una hoja CSV por tabla.
                        </p>
                    </header>
                    <a href="{{ route('profile.datos') }}" data-sin-espera
                        class="pulsable inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Descargar mis datos
                    </a>
                    <p class="text-xs text-gray-400">
                        Para la inspección, el cuaderno de explotación oficial se descarga por finca y campaña desde
                        <a href="{{ route('cuaderno.index') }}" wire:navigate class="underline hover:text-gray-600">Cuaderno</a>.
                    </p>
                </section>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
