@php use App\Modules\Alertas\Models\Alerta; @endphp
<x-mail::message>
# Hola, {{ $user->name }}

{{ $total === 1 ? 'Tienes una alerta nueva' : "Tienes {$total} alertas nuevas" }} en tus fincas:

@foreach($porFinca as $finca => $alertas)
## {{ $finca }}

@foreach($alertas as $alerta)
- **{{ Alerta::NIVELES[$alerta['nivel']] }}** ({{ $alerta['fecha'] }}): {{ $alerta['mensaje'] }}
@endforeach

@endforeach
<x-mail::button :url="route('alertas.index')">
Ver las alertas
</x-mail::button>

Un saludo,<br>
{{ config('app.name') }}

<x-mail::subcopy>
Recibes este correo porque tienes activadas las alertas por correo. Puedes elegir cuáles recibir en tu
[perfil]({{ route('profile') }}) o [dejar de recibirlas]({{ $urlBaja }}).
</x-mail::subcopy>
</x-mail::message>
