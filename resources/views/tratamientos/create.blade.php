@php
    use App\Modules\CalendarioFenologico\Models\EstadoFenologico;
    use App\Modules\CuadernoDigital\Services\CuadernoCampana;
    use App\Modules\Tratamientos\Models\ProductoFitosanitario;
    use App\Modules\Tratamientos\Models\Tratamiento;
    use App\Modules\Tratamientos\Services\ImportadorFitosanitarios;

    // Tres modos: una parcela ($parcela), varias parcelas de una finca a la vez ($finca + $parcelas)
    // o la corrección de un tratamiento ya registrado ($tratamiento)
    $tratamiento ??= null;
    $editando = $tratamiento !== null;
    $deFinca = $finca !== null;
    // Valor de un campo: lo recién enviado, el del tratamiento que se corrige o el propuesto
    $valor = fn (string $campo, $propuesto = null) => old($campo, $editando ? $tratamiento->$campo : $propuesto);
    $fincaDelForm = $deFinca ? $finca : $parcela->finca;
    $volver = $deFinca ? route('vinedo.fincas.show', $finca) : route('vinedo.parcelas.show', $parcela);
    $origenProducto = $deFinca ? ['finca_id' => $finca->id] : ['parcela_id' => $parcela->id];
    $cultivosClasificados = array_keys(ImportadorFitosanitarios::CULTIVOS_REGISTRO);
    // En la finca, por defecto se tratan todas las parcelas; «elegir» abre la lista para marcarlas una a una
    $elegir = $deFinca && (($elegir ?? false) || old('parcelas') !== null || $errors->has('parcelas') || $errors->has('parcelas.*'));
    // Fechas (el modelo las da como Carbon) en el formato de <input type="date">
    $fechaValor = fn (string $campo, $propuesto = null) => old($campo, ($editando ? $tratamiento->$campo : $propuesto)?->toDateString());
    $parcelasForm = $deFinca ? $parcelas : collect([$parcela]);
    $hayVina = $parcelasForm->contains(fn ($p) => $p->esVina());
    $estadosBbch = $hayVina ? EstadoFenologico::orderBy('orden')->get(['codigo_bbch', 'nombre']) : collect();
    $eppo = $deFinca ? null : ($editando ? $tratamiento->codigoEppo() : ImportadorFitosanitarios::codigoEppoDe($parcela));
    $bbchObservado ??= null;
    // Para resources/js/paginas/tratamiento.js
    $datosPagina = [
        'productos'            => $productos->keyBy('id'),
        'plazosAnteriores'     => $plazosAnteriores,
        'urlFicha'             => ProductoFitosanitario::URL_FICHA_MAPA,
        'cultivosClasificados' => $cultivosClasificados,
        'haParcela'            => $parcela ? (float) $parcela->superficie_ha : null,
        'cultivoParcela'       => $parcela ? ImportadorFitosanitarios::cultivoRegistroDe($parcela) : null,
        'editando'             => $editando,
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ $volver }}" wire:navigate
                class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editando ? 'Editar tratamiento' : ($deFinca ? 'Tratar la finca' : 'Nuevo tratamiento') }}</h2>
                <p class="text-xs text-gray-400 mt-0.5">
                    @if($deFinca)
                        {{ $parcelas->count() }} {{ $parcelas->count() === 1 ? 'parcela' : 'parcelas' }}
                    @elseif($parcela->poligono && $parcela->parcela_sigpac)
                        Pol. {{ $parcela->poligono }} · Par. {{ $parcela->parcela_sigpac }}
                    @else
                        {{ $parcela->nombre }}
                    @endif
                    — {{ $fincaDelForm->paraje ?: $fincaDelForm->provincia_nombre }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">


                @if($productos->isEmpty())
                    <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm mb-6">
                        El catálogo no tiene productos para {{ $deFinca ? 'los cultivos de esta finca' : 'este cultivo' }}. Se carga del Registro de Productos Fitosanitarios
                        del MAPA con <code class="font-mono">php artisan fitosanitarios:importar</code>; mientras tanto puedes
                        <a href="{{ route('tratamientos.productos.create', $origenProducto) }}" wire:navigate class="font-medium underline">añadir un producto propio</a>.
                    </div>
                @endif

                <form method="POST" action="{{ $editando ? route('tratamientos.update', $tratamiento) : ($deFinca ? route('tratamientos.finca.store', $finca) : route('tratamientos.store', $parcela)) }}" data-pagina="tratamiento">
                    <script type="application/json" data-datos>@json($datosPagina)</script>
                    @csrf
                    @if($editando) @method('PUT') @endif

                    <div class="space-y-5">

                        {{-- Producto --}}
                        <div>
                            <div class="flex items-baseline justify-between gap-2">
                                <x-input-label for="producto_id" value="Producto fitosanitario *" />
                                <a href="{{ route('tratamientos.productos.create', $origenProducto) }}" wire:navigate
                                    class="text-xs text-green-600 hover:text-green-800">¿No está? Añadir producto propio</a>
                            </div>
                            @if($productos->count() > 15)
                                <input id="producto-buscar" type="search" autocomplete="off"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                    placeholder="Buscar por nombre, nº de registro o materia activa…" />
                            @endif
                            <select id="producto_id" name="producto_id" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                {{ $productos->isEmpty() ? 'disabled' : '' }}>
                                <option value="">Selecciona un producto…</option>
                                @foreach($productos as $producto)
                                    <option value="{{ $producto->id }}"
                                        data-buscar="{{ mb_strtolower($producto->nombre . ' ' . $producto->numero_registro . ' ' . $producto->ingrediente_activo) }}"
                                        {{ $productoElegido == $producto->id ? 'selected' : '' }}>
                                        {{ $producto->nombre }}
                                        @if($producto->numero_registro) (Reg. {{ $producto->numero_registro }}) @endif
                                        @if($producto->esPropio()) · propio @endif
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-0.5 text-xs text-gray-400">
                                @if($deFinca)
                                    Aparecen los productos que el Registro del MAPA autoriza en alguno de los cultivos de la finca, y los tuyos.
                                @elseif($cultivoParcela = ImportadorFitosanitarios::cultivoRegistroDe($parcela))
                                    Solo aparecen los productos que el Registro del MAPA autoriza para {{ mb_strtolower(ImportadorFitosanitarios::NOMBRES_CULTIVO[$cultivoParcela]) }}, y los tuyos.
                                @else
                                    Comprueba en la etiqueta que el producto está autorizado para este cultivo.
                                @endif
                            </p>
                            <x-input-error :messages="$errors->get('producto_id')" class="mt-1" />

                            {{-- Info del producto seleccionado --}}
                            <div id="producto-info" class="hidden mt-2 p-3 bg-gray-50 rounded-lg text-xs text-gray-600 space-y-1">
                                <p>Formulado: <span id="info-ingrediente" class="font-medium text-gray-800"></span></p>
                                <p id="info-titular-fila">Titular: <span id="info-titular" class="font-medium text-gray-800"></span></p>
                                <p id="info-dosis-fila">Dosis máx.: <span id="info-dosis" class="font-medium text-gray-800"></span> <span class="unidad-dosis">l/ha</span></p>
                                <p id="info-caducidad-fila">Autorizado hasta: <span id="info-caducidad" class="font-medium text-gray-800"></span></p>
                                <p id="info-ficha-fila">
                                    <a id="info-ficha" href="#" target="_blank" rel="noopener" class="text-green-700 hover:text-green-900 font-medium">
                                        Ficha oficial del MAPA (usos, dosis y plazos de seguridad) ↗
                                    </a>
                                </p>
                            </div>
                        </div>

                        {{-- Fecha, hora, dosis y estadio del cultivo --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="fecha" value="Fecha de aplicación *" />
                                <x-text-input id="fecha" name="fecha" type="date"
                                    class="mt-1 block w-full"
                                    value="{{ $editando ? old('fecha', $tratamiento->fecha->toDateString()) : old('fecha', now()->toDateString()) }}"
                                    max="{{ now()->toDateString() }}"
                                    required />
                                <x-input-error :messages="$errors->get('fecha')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="hora_inicio" value="Hora de inicio" />
                                <x-text-input id="hora_inicio" name="hora_inicio" type="time" class="mt-1 block w-full"
                                    value="{{ old('hora_inicio', $editando ? $tratamiento->horaInicio() : null) }}" />
                                <x-input-error :messages="$errors->get('hora_inicio')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="dosis_l_ha" value="Dosis aplicada (por ha) *" />
                                <div class="mt-1 relative">
                                    <x-text-input id="dosis_l_ha" name="dosis_l_ha" type="number"
                                        step="0.001" min="0.001"
                                        class="block w-full pr-12"
                                        value="{{ $valor('dosis_l_ha') }}"
                                        required placeholder="0.000" />
                                    <span class="unidad-dosis absolute inset-y-0 right-3 flex items-center text-sm text-gray-400">l/ha</span>
                                </div>
                                <x-input-error :messages="$errors->get('dosis_l_ha')" class="mt-1" />
                            </div>

                            <div>
                                <x-input-label for="bbch" value="Estadio del cultivo (BBCH)" />
                                <x-text-input id="bbch" name="bbch" type="text" inputmode="numeric" maxlength="2" pattern="[0-9]{2}"
                                    list="{{ $estadosBbch->isNotEmpty() ? 'estados-bbch' : '' }}" class="mt-1 block w-full font-mono"
                                    value="{{ $valor('bbch', $bbchObservado) }}" placeholder="{{ $deFinca ? 'El de cada parcela' : 'Ej: 57' }}" />
                                @if($estadosBbch->isNotEmpty())
                                    <datalist id="estados-bbch">
                                        @foreach($estadosBbch as $estado)
                                            <option value="{{ $estado->codigo_bbch }}">{{ $estado->nombre }}</option>
                                        @endforeach
                                    </datalist>
                                @endif
                                <x-input-error :messages="$errors->get('bbch')" class="mt-1" />
                            </div>
                        </div>
                        <p class="-mt-3 text-xs text-gray-400">
                            @if($deFinca)
                                Si dejas el estadio vacío, en cada parcela se anota el de su última observación fenológica (de las 3 semanas anteriores).
                            @elseif(!$editando && $bbchObservado)
                                Estadio tomado de la última observación fenológica de la parcela.
                            @else
                                Código de dos cifras de la escala BBCH: lo pide el registro de tratamientos.
                            @endif
                            @if($eppo)
                                Cultivo: {{ CuadernoCampana::cultivoDe($editando ? $tratamiento->parcela : $parcela) }} (código EPPO <span class="font-mono">{{ $eppo }}</span>).
                            @endif
                        </p>

                        @if($deFinca)
                        {{-- Parcelas tratadas --}}
                        <div>
                            <div class="flex items-baseline justify-between gap-2">
                                <x-input-label value="Parcelas tratadas *" />
                                <label id="parcelas-todas-label" class="{{ $elegir ? '' : 'hidden' }} flex items-center gap-1.5 text-xs text-gray-500">
                                    <input id="parcelas-todas" type="checkbox" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                    Todas
                                </label>
                            </div>
                            @php $marcadas = collect(old('parcelas', $elegir ? [] : $parcelas->pluck('id')->all()))->map(fn ($id) => (int) $id); @endphp
                            <div id="parcelas-resumen" class="{{ $elegir ? 'hidden' : '' }} mt-1 flex items-center justify-between gap-3 px-3 py-2 bg-green-50 border border-green-200 rounded-md text-sm">
                                <span class="text-green-800">
                                    <span class="font-medium">Todas las parcelas de la finca</span>
                                    ({{ $parcelas->count() }}, <span class="parcelas-ha">0</span> ha)
                                </span>
                                <button type="button" id="parcelas-elegir" class="text-xs font-medium text-green-700 hover:text-green-900 underline">Elegir parcela a parcela</button>
                            </div>
                            <div id="parcelas-lista" class="{{ $elegir ? '' : 'hidden' }} mt-1 border border-gray-200 rounded-md divide-y divide-gray-100 max-h-72 overflow-y-auto">
                                @foreach($parcelas as $p)
                                    <label class="parcela-fila flex items-center gap-3 px-3 py-2 text-sm cursor-pointer hover:bg-gray-50"
                                        data-cultivo="{{ ImportadorFitosanitarios::cultivoRegistroDe($p) }}" data-ha="{{ $p->superficie_ha }}">
                                        <input type="checkbox" name="parcelas[]" value="{{ $p->id }}"
                                            class="parcela-check rounded border-gray-300 text-green-600 focus:ring-green-500"
                                            {{ $marcadas->contains($p->id) ? 'checked' : '' }}>
                                        <span class="flex-1 min-w-0">
                                            <span class="font-medium text-gray-800">{{ $p->etiqueta }}</span>
                                            <span class="text-xs text-gray-500">· {{ $p->uso ?? 'Sin uso' }}{{ $p->variedad ? ' · ' . $p->variedad->nombre : '' }}</span>
                                            <span class="parcela-aviso hidden block text-xs text-red-600">El producto no está autorizado para este cultivo</span>
                                        </span>
                                        <span class="text-xs text-gray-400 whitespace-nowrap">{{ number_format($p->superficie_ha, 2, ',', '.') }} ha</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-0.5 text-xs text-gray-400">
                                Se registra un tratamiento en cada parcela marcada, sobre toda su superficie
                                (<span class="parcelas-ha">0</span> ha en total). Para tratar solo parte de una parcela, regístralo desde la propia parcela.
                            </p>
                            <x-input-error :messages="$errors->get('parcelas')" class="mt-1" />
                            <x-input-error :messages="$errors->get('parcelas.*')" class="mt-1" />
                        </div>
                        @else
                        {{-- Superficie tratada --}}
                        <div>
                            <x-input-label for="superficie_tratada_ha" value="Superficie tratada (ha)" />
                            <div class="mt-1 relative">
                                <x-text-input id="superficie_tratada_ha" name="superficie_tratada_ha" type="number"
                                    step="0.0001" min="0.0001" max="{{ $parcela->superficie_ha }}"
                                    class="block w-full pr-10"
                                    value="{{ $valor('superficie_tratada_ha') }}"
                                    placeholder="{{ number_format($parcela->superficie_ha, 2, '.', '') }}" />
                                <span class="absolute inset-y-0 right-3 flex items-center text-sm text-gray-400">ha</span>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-400">Déjalo vacío si se trató toda la parcela ({{ number_format($parcela->superficie_ha, 2, ',', '.') }} ha).</p>
                            <x-input-error :messages="$errors->get('superficie_tratada_ha')" class="mt-1" />
                        </div>

                        @endif

                        {{-- Plazo de seguridad --}}
                        <div>
                            <x-input-label for="plazo_seguridad_dias" value="Plazo de seguridad (días)" />
                            <div class="mt-1 relative">
                                <x-text-input id="plazo_seguridad_dias" name="plazo_seguridad_dias" type="number"
                                    step="1" min="0" max="255"
                                    class="block w-full pr-12"
                                    value="{{ $valor('plazo_seguridad_dias') }}" />
                                <span class="absolute inset-y-0 right-3 flex items-center text-sm text-gray-400">días</span>
                            </div>
                            <p id="plazo-ayuda" class="mt-0.5 text-xs text-gray-400">Según la etiqueta para este cultivo y plaga. Con él se avisa de cuándo se puede cosechar.</p>
                            <x-input-error :messages="$errors->get('plazo_seguridad_dias')" class="mt-1" />
                        </div>

                        {{-- Precio y coste --}}
                        <div>
                            <x-input-label for="precio_unitario" value="Precio del producto" />
                            <div class="mt-1 relative">
                                <x-text-input id="precio_unitario" name="precio_unitario" type="number"
                                    step="0.01" min="0" max="99999"
                                    class="block w-full pr-12"
                                    value="{{ $valor('precio_unitario') }}" placeholder="0,00" />
                                <span class="unidad-precio absolute inset-y-0 right-3 flex items-center text-sm text-gray-400">€/l</span>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-400">
                                Por litro o kilo, según el producto. Se recuerda para la próxima vez y se anota el coste en
                                <a href="{{ route('costes.index') }}" wire:navigate class="underline hover:text-gray-600">Costes</a>.
                            </p>
                            <p id="coste-estimado" class="hidden mt-1 text-sm text-gray-700">
                                Coste del producto: <span id="coste-importe" class="font-semibold"></span>
                                <span id="coste-detalle" class="text-xs text-gray-400"></span>
                            </p>
                            <x-input-error :messages="$errors->get('precio_unitario')" class="mt-1" />
                        </div>

                        {{-- Problema y justificación --}}
                        <div>
                            <x-input-label for="motivo" value="Problema fitosanitario (plaga, enfermedad o mala hierba)" />
                            <textarea id="motivo" name="motivo" rows="2"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm"
                                placeholder="Ej: Oídio, Mildiu, Araña roja…">{{ $valor('motivo') }}</textarea>
                            <x-input-error :messages="$errors->get('motivo')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="justificacion" value="Justificación del tratamiento" />
                            <x-text-input id="justificacion" name="justificacion" type="text" list="justificaciones" maxlength="500"
                                class="mt-1 block w-full" value="{{ $valor('justificacion') }}" placeholder="Por qué se trató: elige una o escríbela" />
                            <datalist id="justificaciones">
                                @foreach(Tratamiento::JUSTIFICACIONES as $justificacion)
                                    <option value="{{ $justificacion }}"></option>
                                @endforeach
                            </datalist>
                            <x-input-error :messages="$errors->get('justificacion')" class="mt-1" />
                        </div>

                        {{-- Datos del cuaderno de explotación --}}
                        <div class="pt-4 border-t border-gray-100">
                            <p class="text-sm font-semibold text-gray-700">Aplicación</p>
                            <p class="text-xs text-gray-400 mb-3">Obligatorios en el registro de tratamientos del cuaderno de explotación.</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="aplicador_nombre" value="Aplicador" />
                                    <x-text-input id="aplicador_nombre" name="aplicador_nombre" type="text" class="mt-1 block w-full"
                                        value="{{ $valor('aplicador_nombre', $ultimo?->aplicador_nombre) }}" />
                                    <x-input-error :messages="$errors->get('aplicador_nombre')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="aplicador_nif" value="NIF del aplicador" />
                                    <x-text-input id="aplicador_nif" name="aplicador_nif" type="text" class="mt-1 block w-full font-mono uppercase"
                                        value="{{ $valor('aplicador_nif', $ultimo?->aplicador_nif) }}" />
                                    <x-input-error :messages="$errors->get('aplicador_nif')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="aplicador_ropo" value="Nº ROPO del aplicador" />
                                    <x-text-input id="aplicador_ropo" name="aplicador_ropo" type="text" class="mt-1 block w-full font-mono"
                                        value="{{ $valor('aplicador_ropo', $ultimo?->aplicador_ropo) }}" />
                                    <x-input-error :messages="$errors->get('aplicador_ropo')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="equipo_roma" value="Equipo: nº ROMA o REGANIP" />
                                    <x-text-input id="equipo_roma" name="equipo_roma" type="text" class="mt-1 block w-full font-mono"
                                        value="{{ $valor('equipo_roma', $ultimo?->equipo_roma) }}" />
                                    <x-input-error :messages="$errors->get('equipo_roma')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="equipo_inspeccion_fecha" value="Última inspección ITEAF del equipo" />
                                    <x-text-input id="equipo_inspeccion_fecha" name="equipo_inspeccion_fecha" type="date" class="mt-1 block w-full"
                                        max="{{ now()->toDateString() }}"
                                        data-vigencia="{{ Tratamiento::VIGENCIA_INSPECCION_EQUIPO_ANIOS }}"
                                        value="{{ $fechaValor('equipo_inspeccion_fecha', $ultimo?->equipo_inspeccion_fecha) }}" />
                                    <p id="inspeccion-caducada" class="hidden mt-1 text-xs text-amber-700">
                                        La inspección tenía más de {{ Tratamiento::VIGENCIA_INSPECCION_EQUIPO_ANIOS }} años el día del tratamiento.
                                    </p>
                                    <x-input-error :messages="$errors->get('equipo_inspeccion_fecha')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="eficacia" value="Eficacia" />
                                    <select id="eficacia" name="eficacia"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                        <option value="">Sin valorar todavía</option>
                                        @foreach(Tratamiento::EFICACIAS as $clave => $etiqueta)
                                            <option value="{{ $clave }}" {{ $valor('eficacia') === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('eficacia')" class="mt-1" />
                                </div>
                            </div>
                            @if(!$editando && $ultimo?->aplicador_ropo)
                                <p class="mt-2 text-xs text-gray-400">Aplicador, equipo y asesor rellenados con los del último tratamiento de la finca.</p>
                            @endif
                        </div>

                        {{-- Asesor de gestión integrada de plagas --}}
                        <div class="pt-4 border-t border-gray-100">
                            <p class="text-sm font-semibold text-gray-700">Asesor</p>
                            <p class="text-xs text-gray-400 mb-3">
                                Quien valida el tratamiento según la gestión integrada de plagas. Obligatorio salvo en las explotaciones exentas de asesoramiento.
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="asesor_nombre" value="Nombre del asesor" />
                                    <x-text-input id="asesor_nombre" name="asesor_nombre" type="text" class="mt-1 block w-full"
                                        value="{{ $valor('asesor_nombre', $ultimo?->asesor_nombre) }}" />
                                    <x-input-error :messages="$errors->get('asesor_nombre')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="asesor_nif" value="NIF del asesor" />
                                    <x-text-input id="asesor_nif" name="asesor_nif" type="text" class="mt-1 block w-full font-mono uppercase"
                                        value="{{ $valor('asesor_nif', $ultimo?->asesor_nif) }}" />
                                    <x-input-error :messages="$errors->get('asesor_nif')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="asesor_ropo" value="Nº ROPO del asesor" />
                                    <x-text-input id="asesor_ropo" name="asesor_ropo" type="text" class="mt-1 block w-full font-mono"
                                        value="{{ $valor('asesor_ropo', $ultimo?->asesor_ropo) }}" />
                                    <x-input-error :messages="$errors->get('asesor_ropo')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label for="asesor_fecha_validacion" value="Fecha de validación" />
                                    <x-text-input id="asesor_fecha_validacion" name="asesor_fecha_validacion" type="date" class="mt-1 block w-full"
                                        max="{{ now()->toDateString() }}" value="{{ $fechaValor('asesor_fecha_validacion') }}" />
                                    <x-input-error :messages="$errors->get('asesor_fecha_validacion')" class="mt-1" />
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ $volver }}" wire:navigate
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">
                            Cancelar
                        </a>
                        <x-primary-button>{{ $editando ? 'Guardar cambios' : ($deFinca ? 'Registrar en las parcelas marcadas' : 'Registrar tratamiento') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-app-layout>
