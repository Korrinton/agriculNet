@props(['disabled' => false])

{{-- Campo de contraseña con botón para mostrarla u ocultarla. La clase va al contenedor. --}}
<div x-data="{ visible: false }" {{ $attributes->only('class')->merge(['class' => 'relative']) }}>
    <input type="password" x-bind:type="visible ? 'text' : 'password'" @disabled($disabled)
        {{ $attributes->except(['class', 'type'])->merge(['class' => 'block w-full pr-11 border-stone-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm bg-white text-stone-900 placeholder-stone-400 disabled:bg-stone-50 disabled:text-stone-500']) }}>

    <button type="button" x-on:click="visible = !visible"
        x-bind:aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
        x-bind:title="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'"
        aria-label="Mostrar contraseña"
        class="ojo-contrasena absolute inset-y-0 right-0 flex items-center px-3 rounded-r-lg text-stone-400 hover:text-green-700 focus:outline-none focus-visible:text-green-700 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-green-500 transition-colors duration-150">
        {{-- Ojo: mientras la contraseña se ve, una línea lo tacha (clic = volver a ocultarla) --}}
        <svg class="h-5 w-5" x-bind:class="visible && 'ojo-visible'" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5s8.57 3 9.96 7.18a1 1 0 0 1 0 .64C20.58 16.49 16.64 19.5 12 19.5s-8.57-3-9.96-7.18Z" />
            <circle cx="12" cy="12" r="3" class="ojo-pupila" />
            <path class="ojo-tachado" pathLength="1"
                stroke-linecap="round" d="M3.5 3.5l17 17" />
        </svg>
    </button>
</div>
