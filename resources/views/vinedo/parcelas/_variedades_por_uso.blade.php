{{--
    Limita el selector de variedad al cultivo del uso elegido (viña → vid, olivar → olivo,
    pistachos → pistacho, secano → herbáceos). Cada parcela del formulario va en un
    contenedor [data-parcela-form] con un select [data-uso], un select [data-variedad] y,
    opcionalmente, su etiqueta [data-variedad-label]. El servidor valida lo mismo.
--}}
<script>
(function () {
    const variedades = @json($variedades->map(fn ($v) => ['id' => $v->id, 'etiqueta' => $v->etiqueta, 'cultivo' => $v->cultivo])->values());
    const cultivoPorUso = @json(\App\Modules\Vinedo\Models\Parcela::CULTIVO_POR_USO);
    const campoPorCultivo = @json(collect(\App\Modules\Vinedo\Models\Variedad::CULTIVOS)->map(fn ($c) => $c['campo']));

    function filtrar(contenedor) {
        const uso = contenedor?.querySelector('[data-uso]');
        const select = contenedor?.querySelector('[data-variedad]');
        if (!uso || !select) return;

        const cultivo = cultivoPorUso[uso.value] || null;
        const actual = select.value;
        const campo = cultivo ? campoPorCultivo[cultivo] : 'Variedad';

        select.innerHTML = '';
        select.add(new Option(cultivo ? (select.dataset.vacio || '—') : 'Elige primero el uso', ''));
        variedades
            .filter(v => v.cultivo === cultivo)
            .forEach(v => select.add(new Option(v.etiqueta, v.id, false, String(v.id) === actual)));
        select.disabled = !cultivo;

        const etiqueta = contenedor.querySelector('[data-variedad-label]');
        if (etiqueta) etiqueta.textContent = campo;
    }

    window.filtrarVariedades = filtrar;
    document.querySelectorAll('[data-parcela-form]').forEach(filtrar);

    // Una sola vez aunque se navegue entre páginas con wire:navigate
    if (!window.__filtroVariedadesActivo) {
        window.__filtroVariedadesActivo = true;
        document.addEventListener('change', e => {
            if (e.target.matches('[data-uso]')) window.filtrarVariedades(e.target.closest('[data-parcela-form]'));
        });
    }
})();
</script>
