@props(['tipo' => 'nubes'])

{{-- Iconos del estado del cielo (App\Services\PanelInicio::iconoCielo), trazo 1.75 como el resto --}}
<svg {{ $attributes->merge(['class' => 'h-6 w-6']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($tipo)
        @case('sol')
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4L6 18M18 6l1.4-1.4"/>
            @break
        @case('sol-nubes')
            <path d="M8.5 3v1.5M3.6 5.1l1 1M2 10h1.5M13.4 5.1l-1 1"/>
            <path d="M5.6 11.6A3.5 3.5 0 0 1 11.7 8"/>
            <path d="M17.5 20H9a4 4 0 1 1 .9-7.9A5 5 0 0 1 19.4 14 3 3 0 0 1 17.5 20Z"/>
            @break
        @case('lluvia')
            <path d="M17.5 15H8a4 4 0 1 1 .9-7.9A5 5 0 0 1 18.4 9 3 3 0 0 1 17.5 15Z"/>
            <path d="M9 18l-1 2.5M13 18l-1 2.5M17 18l-1 2.5"/>
            @break
        @case('tormenta')
            <path d="M17.5 14H8a4 4 0 1 1 .9-7.9A5 5 0 0 1 18.4 8 3 3 0 0 1 17.5 14Z"/>
            <path d="M12.5 15.5l-2 3.5h3l-2 3.5"/>
            @break
        @case('nieve')
            <path d="M17.5 14H8a4 4 0 1 1 .9-7.9A5 5 0 0 1 18.4 8 3 3 0 0 1 17.5 14Z"/>
            <path d="M8 18v.01M12 17v.01M16 18v.01M10 21v.01M14 21v.01" stroke-width="2.5"/>
            @break
        @case('niebla')
            <path d="M17.5 12H8a4 4 0 1 1 .9-7.9A5 5 0 0 1 18.4 6 3 3 0 0 1 17.5 12Z"/>
            <path d="M4 16h14M7 19.5h12"/>
            @break
        @case('desconocido')
            <circle cx="12" cy="12" r="8" stroke-dasharray="2 3"/>
            @break
        @default
            <path d="M17.5 18H8a5 5 0 1 1 1.1-9.9A6 6 0 0 1 20.4 11 3.5 3.5 0 0 1 17.5 18Z"/>
    @endswitch
</svg>
