<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'agriculNet') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex">

            {{-- Left decorative panel --}}
            <div class="hidden lg:flex lg:w-5/12 xl:w-1/2 relative bg-green-950 flex-col justify-between p-12 overflow-hidden">

                {{-- Atmospheric glow --}}
                <div class="absolute top-1/3 right-0 w-96 h-96 bg-green-700 rounded-full blur-3xl opacity-20 pointer-events-none"></div>
                <div class="absolute bottom-1/4 -left-16 w-72 h-72 bg-green-800 rounded-full blur-3xl opacity-25 pointer-events-none"></div>

                {{-- Vine illustration --}}
                <svg class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 400 800" preserveAspectRatio="xMidYMid slice" fill="none">
                    <path d="M 60 800 C 80 700 40 600 70 500 C 100 400 150 370 130 270 C 110 170 160 110 180 40"
                          stroke="white" stroke-width="2" opacity="0.12" stroke-linecap="round"/>
                    <path d="M 90 580 C 50 560 20 545 8 518"
                          stroke="white" stroke-width="1.5" opacity="0.10" stroke-linecap="round"/>
                    <path d="M 8 518 C -6 496 7 474 26 486 C 45 498 36 522 8 518 Z" fill="white" opacity="0.07"/>
                    <path d="M 100 440 C 148 418 176 408 196 386"
                          stroke="white" stroke-width="1.5" opacity="0.10" stroke-linecap="round"/>
                    <path d="M 196 386 C 208 366 225 362 229 379 C 233 396 216 407 196 386 Z" fill="white" opacity="0.07"/>
                    <path d="M 125 330 C 80 316 54 308 38 290"
                          stroke="white" stroke-width="1.5" opacity="0.10" stroke-linecap="round"/>
                    <path d="M 38 290 C 26 270 37 250 56 261 C 75 272 68 294 38 290 Z" fill="white" opacity="0.07"/>
                    <circle cx="22" cy="500" r="8" fill="white" opacity="0.05"/>
                    <circle cx="15" cy="515" r="7" fill="white" opacity="0.045"/>
                    <circle cx="30" cy="515" r="7" fill="white" opacity="0.045"/>
                    <circle cx="22" cy="530" r="7" fill="white" opacity="0.04"/>
                    <circle cx="208" cy="368" r="7" fill="white" opacity="0.05"/>
                    <circle cx="201" cy="382" r="6" fill="white" opacity="0.045"/>
                    <circle cx="215" cy="382" r="6" fill="white" opacity="0.045"/>
                    <circle cx="52" cy="270" r="7" fill="white" opacity="0.05"/>
                    <circle cx="45" cy="284" r="6" fill="white" opacity="0.045"/>
                    <circle cx="59" cy="284" r="6" fill="white" opacity="0.045"/>
                    <circle cx="52" cy="298" r="6" fill="white" opacity="0.04"/>
                </svg>

                {{-- Logo --}}
                <div class="relative z-10 flex items-center gap-3">
                    <x-application-logo class="h-9 w-auto text-green-300" />
                    <span class="text-white font-bold text-xl tracking-tight">agriculNet</span>
                </div>

                {{-- Brand copy --}}
                <div class="relative z-10">
                    <p class="text-green-400 text-xs font-semibold uppercase tracking-widest mb-5">Programa Agrario · España</p>
                    <h1 class="text-4xl xl:text-5xl font-bold text-white leading-tight">
                        De la tierra<br>al cuaderno.
                    </h1>
                    <p class="mt-5 text-green-200/80 text-base leading-relaxed max-w-xs">
                        Registro fitosanitario, fenología y exportación CUE para viticultores españoles.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-900/60 rounded-full text-green-300 text-xs font-medium border border-green-800">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Cuaderno CUE
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-900/60 rounded-full text-green-300 text-xs font-medium border border-green-800">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            SIGPAC
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-900/60 rounded-full text-green-300 text-xs font-medium border border-green-800">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Fenología BBCH
                        </span>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="relative z-10 text-green-600 text-xs">
                    Normativa MAPA · Registro fitosanitario obligatorio
                </div>
            </div>

            {{-- Right form panel --}}
            <div class="flex-1 flex flex-col justify-center items-center px-6 py-12 bg-white">

                {{-- Mobile logo --}}
                <div class="lg:hidden mb-10 flex items-center gap-3">
                    <x-application-logo class="h-8 w-auto text-green-700" />
                    <span class="text-stone-900 font-bold text-xl tracking-tight">agriculNet</span>
                </div>

                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
