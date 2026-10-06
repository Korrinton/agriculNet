<x-guest-layout>
    <div class="entra" style="--i: 1">
        <h2 class="text-2xl font-bold text-stone-900">Alertas por correo</h2>

        @if($hecho)
            <p class="mt-3 text-sm text-stone-600">
                Listo: ya no recibirás las alertas por correo en <strong>{{ $usuario->email }}</strong>.
                Seguirás viéndolas en la aplicación.
            </p>
            <p class="mt-3 text-sm text-stone-500">
                Si cambias de idea, puedes volver a activarlas en tu
                <a href="{{ route('profile') }}" class="font-medium text-green-700 hover:text-green-800">perfil</a>.
            </p>
        @else
            <p class="mt-3 text-sm text-stone-600">
                ¿Dejar de recibir el resumen diario de alertas en <strong>{{ $usuario->email }}</strong>?
                Seguirás viéndolas en la aplicación.
            </p>
            <form method="POST" action="{{ $accion }}" class="mt-6">
                <x-primary-button class="w-full">Dejar de recibir las alertas</x-primary-button>
            </form>
        @endif
    </div>
</x-guest-layout>
