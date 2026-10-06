@php
    use App\Modules\Admin\Http\Controllers\VariedadAdminController;
    use App\Modules\Vinedo\Models\Variedad;
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('admin._cabecera', ['titulo' => 'Variedades y cultivos'])
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm text-gray-500">
                Lo que se elige como variedad (o cultivo, en secano) al crear una parcela. Solo se pueden borrar las que no usa ninguna parcela.
            </p>

            @foreach(Variedad::CULTIVOS as $cultivo => $info)
                @php $lista = $variedades->get($cultivo, collect()); @endphp
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-5 py-3 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-800">{{ $info['nombre'] }} <span class="text-sm font-normal text-gray-400">· {{ $lista->count() }}</span></h3>
                        <a href="{{ route('admin.variedades.create', ['cultivo' => $cultivo]) }}" wire:navigate
                            class="text-sm font-medium text-green-700 hover:text-green-900">+ Añadir {{ mb_strtolower($info['campo']) }}</a>
                    </div>
                    @if($lista->isEmpty())
                        <p class="px-5 py-4 text-sm text-gray-400">Sin variedades.</p>
                    @else
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-gray-50">
                                @foreach($lista as $v)
                                    <tr>
                                        <td class="px-5 py-2 font-medium text-gray-800">{{ $v->nombre }}</td>
                                        <td class="px-5 py-2 text-gray-500">
                                            @if($cultivo === 'vid')
                                                {{ VariedadAdminController::TIPOS[$v->tipo] ?? '' }}{{ $v->precocidad ? ' · maduración ' . mb_strtolower(Variedad::PRECOCIDADES[$v->precocidad]) : '' }}
                                            @endif
                                        </td>
                                        <td class="px-5 py-2 text-right text-gray-500 tabular-nums">{{ $v->parcelas_count }} {{ $v->parcelas_count === 1 ? 'parcela' : 'parcelas' }}</td>
                                        <td class="px-5 py-2 text-right whitespace-nowrap">
                                            <a href="{{ route('admin.variedades.edit', $v) }}" wire:navigate class="text-xs font-medium text-green-700 hover:text-green-900">Editar</a>
                                            @if($v->parcelas_count === 0)
                                                <form method="POST" action="{{ route('admin.variedades.destroy', $v) }}" class="inline ml-3"
                                                    data-confirmar="¿Borrar «{{ $v->nombre }}»?">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-xs font-medium text-red-600 hover:text-red-800">Borrar</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
