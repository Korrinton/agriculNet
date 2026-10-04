<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('vinedo.parcelas.show', $parcela) }}" wire:navigate
                class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Anotar observación fenológica</h2>
                <p class="text-xs text-gray-400 mt-0.5">
                    @if($parcela->poligono && $parcela->parcela_sigpac)
                        Pol. {{ $parcela->poligono }} · Par. {{ $parcela->parcela_sigpac }}
                    @else
                        {{ $parcela->nombre }}
                    @endif
                    — {{ $parcela->finca->paraje ?: $parcela->finca->provincia_nombre }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Estado anterior --}}
            @if($ultimoRegistro)
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Última observación registrada</p>
                        <p class="text-sm font-medium text-gray-800">
                            @if($ultimoRegistro->estado?->codigo_bbch)
                                <span class="font-mono text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded mr-1">
                                    BBCH {{ $ultimoRegistro->estado->codigo_bbch }}
                                </span>
                            @endif
                            {{ $ultimoRegistro->estado?->nombre ?? '—' }}
                        </p>
                    </div>
                    <span class="text-xs text-gray-400 font-mono">
                        {{ $ultimoRegistro->fecha_observacion->format('d/m/Y') }}
                    </span>
                </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">

                @if($estados->isEmpty())
                    <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm mb-6">
                        No hay estados fenológicos configurados. Pide al administrador que cargue la escala BBCH.
                    </div>
                @endif

                <form method="POST" action="{{ route('fenologia.store', $parcela) }}">
                    @csrf

                    <div class="space-y-5">

                        {{-- Estado BBCH --}}
                        <div>
                            <x-input-label for="estado_fenologico_id" value="Estado fenológico (escala BBCH) *" />
                            <select id="estado_fenologico_id" name="estado_fenologico_id" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                {{ $estados->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Selecciona el estado actual…</option>
                                @foreach($estados as $estado)
                                    <option value="{{ $estado->id }}"
                                        {{ old('estado_fenologico_id') == $estado->id ? 'selected' : '' }}>
                                        @if($estado->codigo_bbch)
                                            BBCH {{ $estado->codigo_bbch }} —
                                        @endif
                                        {{ $estado->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('estado_fenologico_id')" class="mt-1" />

                            {{-- Descripción del estado seleccionado --}}
                            <div id="estado-desc" class="hidden mt-2 p-3 bg-gray-50 rounded-lg text-xs text-gray-600">
                                <span id="desc-text"></span>
                            </div>
                        </div>

                        {{-- Fecha --}}
                        <div>
                            <x-input-label for="fecha_observacion" value="Fecha de observación *" />
                            <x-text-input id="fecha_observacion" name="fecha_observacion" type="date"
                                class="mt-1 block w-full"
                                value="{{ old('fecha_observacion', now()->toDateString()) }}"
                                max="{{ now()->toDateString() }}"
                                required />
                            <x-input-error :messages="$errors->get('fecha_observacion')" class="mt-1" />
                        </div>

                        {{-- Observaciones libres --}}
                        <div>
                            <x-input-label for="observaciones" value="Observaciones" />
                            <textarea id="observaciones" name="observaciones" rows="4"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                placeholder="Notas sobre el estado de la viña, incidencias, condiciones del campo…">{{ old('observaciones') }}</textarea>
                            <x-input-error :messages="$errors->get('observaciones')" class="mt-1" />
                        </div>

                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('vinedo.parcelas.show', $parcela) }}" wire:navigate
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">
                            Cancelar
                        </a>
                        <x-primary-button>Guardar observación</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const estados = @json($estados->keyBy('id'));
        const select  = document.getElementById('estado_fenologico_id');
        const box     = document.getElementById('estado-desc');
        const text    = document.getElementById('desc-text');

        function update() {
            const e = estados[select.value];
            if (!e || !e.descripcion) { box.classList.add('hidden'); return; }
            text.textContent = e.descripcion;
            box.classList.remove('hidden');
        }

        select.addEventListener('change', update);
        update();
    })();
    </script>
</x-app-layout>
