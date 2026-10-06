<?php

use App\Modules\Alertas\Models\Alerta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $alertas_por_correo = 'todas';

    public function mount(): void
    {
        $this->alertas_por_correo = Auth::user()->alertas_por_correo ?? 'todas';
    }

    public function guardar(): void
    {
        $this->validate(['alertas_por_correo' => ['required', Rule::in(array_keys(Alerta::PREFERENCIAS_CORREO))]]);

        Auth::user()->forceFill(['alertas_por_correo' => $this->alertas_por_correo])->save();

        $this->dispatch('alertas-correo-guardadas');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Alertas por correo</h2>
        <p class="mt-1 text-sm text-gray-600">
            Cada mañana, después de revisar el tiempo, te enviamos un correo con las alertas nuevas de tus fincas
            (heladas, riesgo de mildiu, plazos de seguridad…) a <strong>{{ auth()->user()->email }}</strong>.
            Solo si hay alguna nueva: los días sin alertas no llega nada.
        </p>
    </header>

    <form wire:submit="guardar" class="mt-6 space-y-4">
        <fieldset class="space-y-2">
            <legend class="sr-only">Qué alertas recibir por correo</legend>
            @foreach(Alerta::PREFERENCIAS_CORREO as $clave => $preferencia)
                <label class="flex items-start gap-2.5 text-sm text-gray-700 cursor-pointer">
                    <input type="radio" wire:model="alertas_por_correo" value="{{ $clave }}"
                        class="mt-0.5 border-gray-300 text-green-600 focus:ring-green-500">
                    <span>
                        {{ $preferencia['etiqueta'] }}
                        <span class="block text-xs text-gray-400">
                            @switch($clave)
                                @case('todas') Incluye los avisos informativos, como el fin de un plazo de seguridad. @break
                                @case('avisos') Heladas, riesgo de mildiu y plazos de seguridad en curso. @break
                                @case('criticas') Heladas fuertes y dosis por encima de la autorizada. @break
                                @default Las alertas solo se verán en la aplicación.
                            @endswitch
                        </span>
                    </span>
                </label>
            @endforeach
        </fieldset>
        <x-input-error :messages="$errors->get('alertas_por_correo')" />

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar</x-primary-button>
            <x-action-message class="me-3" on="alertas-correo-guardadas">Guardado.</x-action-message>
        </div>
    </form>
</section>
