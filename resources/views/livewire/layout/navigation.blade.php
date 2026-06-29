<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-green-800 border-b border-green-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2">
                        <svg class="h-8 w-8 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
                        </svg>
                        <span class="text-white font-bold text-lg tracking-tight hidden sm:block">Programa Agrario</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-1 sm:-my-px sm:ms-6 sm:flex items-center">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate
                        class="!text-green-200 hover:!text-white !border-transparent hover:!border-green-300 data-[active]:!border-green-300 data-[active]:!text-white">
                        Inicio
                    </x-nav-link>

                    {{-- Viñedo dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false"
                            class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-green-200 hover:text-white transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            Viñedo
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="open" x-transition
                            class="absolute left-0 mt-1 w-44 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="{{ route('vinedo.fincas.index') }}" wire:navigate
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50">Fincas</a>
                        </div>
                    </div>

                    <x-nav-link :href="route('fenologia.index')" :active="request()->routeIs('fenologia.*')" wire:navigate
                        class="!text-green-200 hover:!text-white !border-transparent hover:!border-green-300">
                        Fenología
                    </x-nav-link>

                    <x-nav-link :href="route('meteorologia.index')" :active="request()->routeIs('meteorologia.*')" wire:navigate
                        class="!text-green-200 hover:!text-white !border-transparent hover:!border-green-300">
                        Meteorología
                    </x-nav-link>

                    <x-nav-link :href="route('tratamientos.index')" :active="request()->routeIs('tratamientos.*')" wire:navigate
                        class="!text-green-200 hover:!text-white !border-transparent hover:!border-green-300">
                        Tratamientos
                    </x-nav-link>

                    <x-nav-link :href="route('costes.index')" :active="request()->routeIs('costes.*')" wire:navigate
                        class="!text-green-200 hover:!text-white !border-transparent hover:!border-green-300">
                        Costes
                    </x-nav-link>

                    <x-nav-link :href="route('cuaderno.index')" :active="request()->routeIs('cuaderno.*')" wire:navigate
                        class="!text-green-200 hover:!text-white !border-transparent hover:!border-green-300">
                        Cuaderno
                    </x-nav-link>

                    <a href="{{ route('alertas.index') }}" wire:navigate
                        class="relative inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-green-200 hover:text-white transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        Alertas
                    </a>
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-green-200 hover:text-white focus:outline-none transition">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                            <svg class="ms-1 h-4 w-4 fill-current" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>Perfil</x-dropdown-link>
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>Cerrar sesión</x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open" class="inline-flex items-center justify-center p-2 rounded-md text-green-300 hover:text-white hover:bg-green-700 transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive menu -->
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden bg-green-900">
        <div class="pt-2 pb-3 space-y-1 px-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>Inicio</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('vinedo.fincas.index')" wire:navigate>Viñedo — Fincas</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('fenologia.index')" wire:navigate>Fenología</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('meteorologia.index')" wire:navigate>Meteorología</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tratamientos.index')" wire:navigate>Tratamientos</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('costes.index')" wire:navigate>Costes</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('cuaderno.index')" wire:navigate>Cuaderno Digital</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('alertas.index')" wire:navigate>Alertas</x-responsive-nav-link>
        </div>
        <div class="pt-4 pb-1 border-t border-green-700">
            <div class="px-4">
                <div class="font-medium text-base text-green-200" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-green-400">{{ auth()->user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1 px-2">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>Perfil</x-responsive-nav-link>
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>Cerrar sesión</x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
