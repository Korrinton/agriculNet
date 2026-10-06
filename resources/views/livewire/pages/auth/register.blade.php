<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-7 entra" style="--i: 1">
        <h2 class="text-2xl font-bold text-stone-900">Crear cuenta</h2>
        <p class="mt-1 text-sm text-stone-500">Empieza a gestionar tu viñedo hoy</p>
    </div>

    <form wire:submit="register" class="space-y-5">
        <div class="entra" style="--i: 2">
            <x-input-label for="name" value="Nombre completo" />
            <x-text-input wire:model="name" id="name" class="block mt-1.5 w-full" type="text"
                name="name" required autofocus autocomplete="name" placeholder="Tu nombre" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <div class="entra" style="--i: 3">
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input wire:model="email" id="email" class="block mt-1.5 w-full" type="email"
                name="email" required autocomplete="username" placeholder="tu@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div class="entra" style="--i: 4">
            <x-input-label for="password" value="Contraseña" />
            <x-password-input wire:model="password" id="password" class="block mt-1.5 w-full" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div class="entra" style="--i: 5">
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-password-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1.5 w-full" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <x-primary-button class="entra w-full mt-2 hover:-translate-y-px hover:shadow-md hover:shadow-green-900/20" style="--i: 6" wire:loading.attr="disabled" wire:target="register">
            <span wire:loading.remove wire:target="register">Crear cuenta</span>
            <span wire:loading wire:target="register" class="inline-flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 8 2 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Creando cuenta...
            </span>
        </x-primary-button>
    </form>

    <p class="entra mt-6 text-center text-sm text-stone-500" style="--i: 7">
        ¿Ya tienes cuenta?
        <a class="font-medium text-green-700 hover:text-green-800 transition duration-150"
            href="{{ route('login') }}" wire:navigate>
            Iniciar sesión
        </a>
    </p>
</div>
