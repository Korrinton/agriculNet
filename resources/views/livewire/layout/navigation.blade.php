<?php

use App\Livewire\Actions\Logout;
use App\Modules\Alertas\Services\AlertaService;
use Livewire\Volt\Component;

new class extends Component
{
    public function with(AlertaService $alertas): array
    {
        return [
            'alertasNoLeidas' => auth()->check() ? $alertas->contarNoLeidas(auth()->user()) : 0,
        ];
    }

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-green-900 border-b border-green-950/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">

                {{-- Logo --}}
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 group">
                        <x-application-logo class="h-7 w-auto text-green-300 group-hover:text-green-200 transition duration-150" />
                        <span class="text-white font-bold text-base tracking-tight hidden sm:block">agriculNet</span>
                    </a>
                </div>

                {{-- Navigation Links --}}
                <div class="hidden space-x-0.5 sm:ms-8 sm:flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition duration-150
                            {{ request()->routeIs('dashboard') ? 'text-white bg-green-800' : 'text-green-300 hover:text-white hover:bg-green-800/60' }}">
                        Inicio
                    </a>

                    {{-- Viñedo dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-md transition duration-150
                                {{ request()->routeIs('vinedo.*') ? 'text-white bg-green-800' : 'text-green-300 hover:text-white hover:bg-green-800/60' }}">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            Viñedo
                            <svg class="h-3 w-3 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute left-0 mt-1 w-44 bg-white rounded-lg shadow-lg ring-1 ring-black/5 py-1 z-50 origin-top-left">
                            <a href="{{ route('vinedo.fincas.index') }}" wire:navigate
                                class="flex items-center gap-2 px-4 py-2 text-sm text-stone-700 hover:bg-stone-50 hover:text-green-700 transition duration-100">
                                <svg class="h-3.5 w-3.5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                Fincas
                            </a>
                        </div>
                    </div>

                    <a href="{{ route('fenologia.index') }}" wire:navigate
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition duration-150
                            {{ request()->routeIs('fenologia.*') ? 'text-white bg-green-800' : 'text-green-300 hover:text-white hover:bg-green-800/60' }}">
                        Fenología
                    </a>

                    <a href="{{ route('meteorologia.index') }}" wire:navigate
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition duration-150
                            {{ request()->routeIs('meteorologia.*') ? 'text-white bg-green-800' : 'text-green-300 hover:text-white hover:bg-green-800/60' }}">
                        Meteorología
                    </a>

                    <a href="{{ route('tratamientos.index') }}" wire:navigate
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition duration-150
                            {{ request()->routeIs('tratamientos.*') ? 'text-white bg-green-800' : 'text-green-300 hover:text-white hover:bg-green-800/60' }}">
                        Tratamientos
                    </a>

                    <a href="{{ route('riegos.index') }}" wire:navigate
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition duration-150
                            {{ request()->routeIs('riegos.*') ? 'text-white bg-green-800' : 'text-green-300 hover:text-white hover:bg-green-800/60' }}">
                        Riegos
                    </a>

                    <a href="{{ route('costes.index') }}" wire:navigate
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition duration-150
                            {{ request()->routeIs('costes.*') ? 'text-white bg-green-800' : 'text-green-300 hover:text-white hover:bg-green-800/60' }}">
                        Costes
                    </a>

                    <a href="{{ route('cuaderno.index') }}" wire:navigate
                        class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md transition duration-150
                            {{ request()->routeIs('cuaderno.*') ? 'text-white bg-green-800' : 'text-green-300 hover:text-white hover:bg-green-800/60' }}">
                        Cuaderno
                    </a>

                    <a href="{{ route('alertas.index') }}" wire:navigate
                        class="relative inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-md transition duration-150
                            {{ request()->routeIs('alertas.*') ? 'text-white bg-green-800' : 'text-green-300 hover:text-white hover:bg-green-800/60' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        Alertas
                        @if($alertasNoLeidas > 0)
                            <span class="min-w-[1.25rem] h-5 px-1 inline-flex items-center justify-center rounded-full bg-rose-500 text-white text-[11px] font-semibold leading-none">
                                {{ $alertasNoLeidas > 99 ? '99+' : $alertasNoLeidas }}
                            </span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- User Dropdown --}}
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-green-200 hover:text-white hover:bg-green-800/60 transition duration-150 focus:outline-none">
                            <div class="h-7 w-7 rounded-full bg-green-700 flex items-center justify-center text-xs font-semibold text-white"
                                 x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                                 x-text="name.charAt(0).toUpperCase()"
                                 x-on:profile-updated.window="name = $event.detail.name">
                            </div>
                            <span class="text-sm font-medium"
                                  x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                                  x-text="name"
                                  x-on:profile-updated.window="name = $event.detail.name">
                            </span>
                            <svg class="h-3.5 w-3.5 fill-current opacity-70" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            Perfil
                        </x-dropdown-link>
                        @if(auth()->user()->is_admin)
                            <x-dropdown-link :href="route('admin.panel')" wire:navigate>
                                Administración
                            </x-dropdown-link>
                        @endif
                        <div class="border-t border-stone-100 my-1"></div>
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link class="text-rose-600 hover:text-rose-700">
                                Cerrar sesión
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Hamburger --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open" class="inline-flex items-center justify-center p-2 rounded-md text-green-300 hover:text-white hover:bg-green-800 transition duration-150">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Responsive menu --}}
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden bg-green-950/90 backdrop-blur-sm">
        <div class="pt-2 pb-3 space-y-0.5 px-3">
            <a href="{{ route('dashboard') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'text-white bg-green-800' : 'text-green-300 hover:text-white hover:bg-green-800/60' }} transition duration-100">
               Inicio
            </a>
            <a href="{{ route('vinedo.fincas.index') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium text-green-300 hover:text-white hover:bg-green-800/60 transition duration-100">
               Viñedo — Fincas
            </a>
            <a href="{{ route('fenologia.index') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium text-green-300 hover:text-white hover:bg-green-800/60 transition duration-100">Fenología</a>
            <a href="{{ route('meteorologia.index') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium text-green-300 hover:text-white hover:bg-green-800/60 transition duration-100">Meteorología</a>
            <a href="{{ route('tratamientos.index') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium text-green-300 hover:text-white hover:bg-green-800/60 transition duration-100">Tratamientos</a>
            <a href="{{ route('riegos.index') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium text-green-300 hover:text-white hover:bg-green-800/60 transition duration-100">Riegos</a>
            <a href="{{ route('costes.index') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium text-green-300 hover:text-white hover:bg-green-800/60 transition duration-100">Costes</a>
            <a href="{{ route('cuaderno.index') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium text-green-300 hover:text-white hover:bg-green-800/60 transition duration-100">Cuaderno Digital</a>
            <a href="{{ route('alertas.index') }}" wire:navigate
               class="block px-3 py-2 rounded-md text-sm font-medium text-green-300 hover:text-white hover:bg-green-800/60 transition duration-100">
                Alertas
                @if($alertasNoLeidas > 0)
                    <span class="ml-1 text-xs font-semibold text-rose-300">({{ $alertasNoLeidas }})</span>
                @endif
            </a>
        </div>
        <div class="pt-3 pb-3 border-t border-green-800/60">
            <div class="px-4 mb-2">
                <div class="text-sm font-semibold text-white"
                     x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                     x-text="name"
                     x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="text-xs text-green-400 mt-0.5">{{ auth()->user()->email }}</div>
            </div>
            <div class="space-y-0.5 px-3">
                <a href="{{ route('profile') }}" wire:navigate
                   class="block px-3 py-2 rounded-md text-sm font-medium text-green-300 hover:text-white hover:bg-green-800/60 transition duration-100">Perfil</a>
                @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.panel') }}" wire:navigate
                       class="block px-3 py-2 rounded-md text-sm font-medium text-green-300 hover:text-white hover:bg-green-800/60 transition duration-100">Administración</a>
                @endif
                <button wire:click="logout" class="w-full text-start">
                    <span class="block px-3 py-2 rounded-md text-sm font-medium text-rose-400 hover:text-rose-300 hover:bg-green-800/60 transition duration-100">Cerrar sesión</span>
                </button>
            </div>
        </div>
    </div>
</nav>
