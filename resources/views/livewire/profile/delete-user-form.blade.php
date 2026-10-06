<?php

use App\Livewire\Actions\Logout;
use App\Modules\Usuarios\Services\BorradoCuenta;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    public function deleteUser(Logout $logout, BorradoCuenta $borrado): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        // La sesión se cierra antes: al cerrarla se renueva el token de «recordarme», que guardaría de nuevo al usuario
        $borrado->borrar(tap(Auth::user(), $logout(...)));

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">Eliminar cuenta</h2>
        <p class="mt-1 text-sm text-gray-600">
            Se borran para siempre tu cuenta, tus fincas y parcelas y todos sus registros: tratamientos, costes,
            riegos, cosechas, fertilizaciones y observaciones. De las copias de seguridad desaparecen a los 14 días, cuando se renuevan.
        </p>
        <p class="mt-2 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            La normativa obliga a conservar el registro de tratamientos al menos 3 años (RD 1311/2012).
            Antes de borrar la cuenta, <a href="{{ route('cuaderno.index') }}" wire:navigate class="font-medium underline">descarga el cuaderno de explotación</a>
            de cada finca y campaña. Puedes además <a href="{{ route('profile.datos') }}" class="font-medium underline">descargar todos tus datos</a>.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Eliminar cuenta</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                ¿Estás seguro de que quieres eliminar tu cuenta?
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Esta acción es irreversible: se borrarán tus fincas y todos sus registros. Introduce tu contraseña para confirmar.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Contraseña" class="sr-only" />
                <x-password-input wire:model="password" id="password" name="password"
                    class="mt-1 block w-3/4" placeholder="Contraseña" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Eliminar cuenta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
