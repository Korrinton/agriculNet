@php
    use App\Modules\Admin\Http\Controllers\UsuarioAdminController;
    use App\Modules\Admin\Services\MetricasUso;

    $activoDesde = now()->subDays(MetricasUso::DIAS_ACTIVO);
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin._cabecera', ['titulo' => 'Usuarios'])
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <form method="GET" action="{{ route('admin.usuarios.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="search" name="q" value="{{ $buscar }}" placeholder="Buscar por nombre o email…"
                    class="w-64 text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                <select name="filtro" data-autoenviar
                    class="text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    @foreach(UsuarioAdminController::FILTROS as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected($filtro === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Buscar</button>
                <span class="ml-auto text-xs text-gray-400">{{ $usuarios->total() }} {{ $usuarios->total() === 1 ? 'usuario' : 'usuarios' }}</span>
            </form>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-400 bg-gray-50 border-b border-gray-100">
                                <th class="px-4 py-2 text-left font-medium">Usuario</th>
                                <th class="px-4 py-2 text-left font-medium">Alta</th>
                                <th class="px-4 py-2 text-left font-medium">Último acceso</th>
                                <th class="px-4 py-2 text-right font-medium">Fincas</th>
                                <th class="px-4 py-2 text-right font-medium">Hectáreas</th>
                                <th class="px-4 py-2 text-right font-medium">Tratamientos</th>
                                <th class="px-4 py-2 text-left font-medium">Estado</th>
                                <th class="px-4 py-2"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($usuarios as $u)
                                <tr class="{{ $u->estaBloqueado() ? 'bg-gray-50/60' : '' }}">
                                    <td class="px-4 py-2">
                                        <p class="font-medium text-gray-800">{{ $u->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $u->email }}</p>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $u->created_at?->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap {{ $u->ultimo_acceso_at && $u->ultimo_acceso_at->gte($activoDesde) ? 'text-gray-600' : 'text-gray-400' }}">
                                        {{ $u->ultimo_acceso_at ? $u->ultimo_acceso_at->locale('es')->diffForHumans() : 'Nunca' }}
                                    </td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ $u->fincas_count }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ number_format((float) $u->hectareas, 1, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ $u->tratamientos }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        @if($u->is_admin)
                                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 ring-1 ring-amber-200">Administrador</span>
                                        @endif
                                        @if($u->estaBloqueado())
                                            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-50 text-red-700 ring-1 ring-red-200" title="Desde el {{ $u->bloqueado_at->format('d/m/Y H:i') }}">Bloqueado</span>
                                        @elseif(!$u->is_admin)
                                            <span class="text-xs text-gray-400">Activo</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-right whitespace-nowrap">
                                        @unless($u->is_admin || $u->is(auth()->user()))
                                            <div x-data="{ borrar: false }" class="inline-flex items-center gap-2">
                                                <template x-if="!borrar"><span class="inline-flex items-center gap-2">
                                                @if($u->estaBloqueado())
                                                    <form method="POST" action="{{ route('admin.usuarios.desbloquear', $u) }}">
                                                        @csrf
                                                        <button class="text-xs font-medium text-green-700 hover:text-green-900">Desbloquear</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.usuarios.bloquear', $u) }}"
                                                        data-confirmar="¿Bloquear a {{ $u->name }}? No podrá entrar hasta que lo desbloquees.">
                                                        @csrf
                                                        <button class="text-xs font-medium text-amber-700 hover:text-amber-900">Bloquear</button>
                                                    </form>
                                                @endif
                                                <button type="button" x-on:click="borrar = true" class="text-xs font-medium text-red-600 hover:text-red-800">Borrar</button>
                                                </span></template>

                                                {{-- Borrar exige escribir el email: es irreversible y borra todas sus fincas --}}
                                                <form x-show="borrar" x-cloak method="POST" action="{{ route('admin.usuarios.destroy', $u) }}"
                                                    class="inline-flex items-center gap-2">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input name="confirmacion" autocomplete="off" required aria-label="Escribe {{ $u->email }} para confirmar el borrado"
                                                        placeholder="Escribe su email" title="Se borrarán la cuenta y todas sus fincas y registros"
                                                        class="w-48 py-1 text-xs border-red-300 rounded-md focus:ring-red-500 focus:border-red-500">
                                                    <button class="text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-md px-2.5 py-1.5">Borrar cuenta</button>
                                                    <button type="button" x-on:click="borrar = false" class="text-xs text-gray-500">Cancelar</button>
                                                </form>
                                            </div>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No hay usuarios con ese filtro.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <x-input-error :messages="$errors->get('confirmacion')" />

            {{ $usuarios->links() }}

            <p class="text-xs text-gray-400">
                Los administradores se nombran desde la consola con <code class="font-mono">php artisan usuarios:admin email</code>
                (<code class="font-mono">--quitar</code> para retirar el acceso). Cada bloqueo y borrado queda anotado en el log.
            </p>
        </div>
    </div>
</x-app-layout>
