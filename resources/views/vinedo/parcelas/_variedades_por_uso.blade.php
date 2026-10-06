{{--
    Limita el selector de variedad al cultivo del uso elegido (viña → vid, olivar → olivo,
    pistachos → pistacho, secano → herbáceos). Cada parcela del formulario va en un
    contenedor [data-parcela-form] con un select [data-uso], un select [data-variedad] y,
    opcionalmente, su etiqueta [data-variedad-label]. El servidor valida lo mismo.
    El código está en resources/js/paginas/fincas.js.
--}}
@php
    $catalogoVariedades = [
        'variedades'      => $variedades->map(fn ($v) => ['id' => $v->id, 'etiqueta' => $v->etiqueta, 'cultivo' => $v->cultivo])->values(),
        'cultivoPorUso'   => \App\Modules\Vinedo\Models\Parcela::CULTIVO_POR_USO,
        'campoPorCultivo' => collect(\App\Modules\Vinedo\Models\Variedad::CULTIVOS)->map(fn ($c) => $c['campo']),
    ];
@endphp
<div hidden data-variedades-por-uso>
    <script type="application/json" data-datos>@json($catalogoVariedades)</script>
</div>
