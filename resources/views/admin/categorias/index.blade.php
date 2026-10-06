@php use App\Modules\Costes\Models\CategoriaCoste; @endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin._cabecera', ['titulo' => 'Categorías de coste'])
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <form method="POST" action="{{ route('admin.categorias.store') }}"
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-wrap items-end gap-3">
                @csrf
                <div class="flex-1 min-w-[12rem]">
                    <x-input-label for="nombre" value="Nueva categoría" />
                    <x-text-input id="nombre" name="nombre" class="mt-1 block w-full" required maxlength="255" value="{{ old('nombre') }}" placeholder="Ej: Análisis de suelo" />
                </div>
                <div>
                    <x-input-label for="tipo" value="Tipo" />
                    <select id="tipo" name="tipo" class="mt-1 block border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                        @foreach(CategoriaCoste::TIPOS as $clave => $etiqueta)
                            <option value="{{ $clave }}" @selected(old('tipo', 'otros') === $clave)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <x-primary-button>Añadir</x-primary-button>
                <x-input-error :messages="$errors->get('nombre')" class="w-full" />
            </form>

            @foreach(CategoriaCoste::TIPOS as $tipo => $etiquetaTipo)
                @php $lista = $categorias->get($tipo, collect()); @endphp
                @continue($lista->isEmpty())
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <h3 class="px-5 py-3 border-b border-gray-100 font-semibold text-gray-800">{{ $etiquetaTipo }}</h3>
                    <ul class="divide-y divide-gray-50">
                        @foreach($lista as $c)
                            <li x-data="{ editar: false }" class="px-5 py-2 text-sm">
                                <div x-show="!editar" class="flex items-center gap-3">
                                    <span class="flex-1 font-medium text-gray-800">{{ $c->nombre }}</span>
                                    @if($c->nombre === $protegida)
                                        <span class="text-xs text-gray-400" title="La usan los costes de los tratamientos">automática</span>
                                    @endif
                                    <span class="text-gray-500 tabular-nums">{{ $c->costes_count }} {{ $c->costes_count === 1 ? 'gasto' : 'gastos' }}</span>
                                    <button type="button" x-on:click="editar = true" class="text-xs font-medium text-green-700 hover:text-green-900">Editar</button>
                                    @if($c->costes_count === 0 && $c->nombre !== $protegida)
                                        <form method="POST" action="{{ route('admin.categorias.destroy', $c) }}" data-confirmar="¿Borrar «{{ $c->nombre }}»?">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-xs font-medium text-red-600 hover:text-red-800">Borrar</button>
                                        </form>
                                    @endif
                                </div>
                                <form x-show="editar" x-cloak method="POST" action="{{ route('admin.categorias.update', $c) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input name="nombre" value="{{ $c->nombre }}" required maxlength="255" @readonly($c->nombre === $protegida)
                                        class="flex-1 min-w-[12rem] py-1 text-sm border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500 read-only:bg-gray-50">
                                    <select name="tipo" class="py-1 text-sm border-gray-300 rounded-md focus:ring-green-500 focus:border-green-500">
                                        @foreach(CategoriaCoste::TIPOS as $clave => $etiqueta)
                                            <option value="{{ $clave }}" @selected($c->tipo === $clave)>{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    <button class="text-xs font-medium text-white bg-green-700 hover:bg-green-800 rounded-md px-3 py-1.5">Guardar</button>
                                    <button type="button" x-on:click="editar = false" class="text-xs text-gray-500">Cancelar</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
