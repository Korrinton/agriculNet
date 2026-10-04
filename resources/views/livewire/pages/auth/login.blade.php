<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-7">
        <h2 class="text-2xl font-bold text-stone-900">Iniciar sesión</h2>
        <p class="mt-1 text-sm text-stone-500">Accede a tu cuenta de agriculNet</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input wire:model="form.email" id="email" class="block mt-1.5 w-full" type="email"
                name="email" required autofocus autocomplete="username" placeholder="tu@email.com" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-1.5" />
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <x-input-label for="password" value="Contraseña" />
                @if (Route::has('password.request'))
                    <a class="text-xs text-green-700 hover:text-green-800 font-medium transition duration-150"
                        href="{{ route('password.request') }}" wire:navigate>
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>
            <x-text-input wire:model="form.password" id="password" class="block w-full"
                type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-1.5" />
        </div>

        <div class="flex items-center gap-2">
            <input wire:model="form.remember" id="remember" type="checkbox"
                class="h-4 w-4 rounded border-stone-300 text-green-600 shadow-sm focus:ring-green-500">
            <label for="remember" class="text-sm text-stone-600 select-none cursor-pointer">Recordarme</label>
        </div>

        <x-primary-button class="w-full mt-2" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login">Entrar</span>
            <span wire:loading wire:target="login" class="inline-flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 8 2 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Entrando...
            </span>
        </x-primary-button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-stone-500">
            ¿No tienes cuenta?
            <a class="font-medium text-green-700 hover:text-green-800 transition duration-150"
                href="{{ route('register') }}" wire:navigate>
                Crear cuenta nueva
            </a>
        </p>
    @endif
</div>
