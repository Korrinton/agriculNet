import { datosDe, enPagina } from './iniciar.js';

/*
 * Formulario de tratamiento (una parcela, varias de una finca o corrección): información del
 * producto, plazo de seguridad propuesto, coste estimado, parcelas autorizadas para el producto
 * y aviso de inspección ITEAF caducada.
 */
enPagina('[data-pagina="tratamiento"]', (raiz) => {
    const { productos, plazosAnteriores, urlFicha, cultivosClasificados, haParcela, cultivoParcela, editando } = datosDe(raiz);
    const $ = (selector) => raiz.querySelector(selector);

    const select = $('#producto_id');
    const buscar = $('#producto-buscar');
    const infoBox = $('#producto-info');
    const plazo = $('#plazo_seguridad_dias');
    const ayudaPlazo = $('#plazo-ayuda');
    const ayudaPlazoBase = ayudaPlazo.textContent;
    const opciones = Array.from(select.options).slice(1);
    const precio = $('#precio_unitario');
    const dosis = $('#dosis_l_ha');
    const superficie = $('#superficie_tratada_ha');
    const filas = Array.from(raiz.querySelectorAll('.parcela-fila'));
    const todas = $('#parcelas-todas');
    const euros = (v) => v.toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });
    const numero = (v) => v.toLocaleString('es-ES', { maximumFractionDigits: 2 });

    function fila(id, valor) {
        $('#' + id + '-fila').classList.toggle('hidden', valor === null || valor === undefined || valor === '');
    }

    function actualizarInfo(cambioDeProducto) {
        const p = productos[select.value];
        if (!p) { infoBox.classList.add('hidden'); actualizarParcelas(); actualizarCoste(); return; }

        $('#info-ingrediente').textContent = p.ingrediente_activo ?? '—';
        $('#info-titular').textContent = p.titular ?? '';
        $('#info-dosis').textContent = p.dosis_max_l_ha ?? '';
        $('#info-caducidad').textContent = p.fecha_caducidad ? p.fecha_caducidad.substring(0, 10).split('-').reverse().join('/') : '';
        $('#info-ficha').href = p.mapa_id ? urlFicha + p.mapa_id : '#';
        fila('info-titular', p.titular);
        fila('info-dosis', p.dosis_max_l_ha);
        fila('info-caducidad', p.fecha_caducidad);
        fila('info-ficha', p.mapa_id);
        infoBox.classList.remove('hidden');
        actualizarParcelas();
        raiz.querySelectorAll('.unidad-dosis').forEach((e) => { e.textContent = (p.unidad || 'l') + '/ha'; });
        raiz.querySelectorAll('.unidad-precio').forEach((e) => { e.textContent = '€/' + (p.unidad || 'l'); });

        if (cambioDeProducto) {
            proponerPlazo();
        }
        // Y el precio que el usuario anotó para este producto
        if (cambioDeProducto || precio.value === '') {
            precio.value = p.precio ?? '';
        }
        actualizarCoste();
    }

    // Plazo propuesto: el oficial de la ficha para los cultivos tratados (el más largo si son varios),
    // si no, el que se anotó la última vez con el producto y, si no, el genérico del producto
    function cultivosTratados() {
        if (filas.length) {
            return filas.filter((f) => f.querySelector('input').checked).map((f) => f.dataset.cultivo).filter(Boolean);
        }
        return cultivoParcela ? [cultivoParcela] : [];
    }

    function proponerPlazo() {
        const p = productos[select.value];
        if (!p) return;
        const oficiales = cultivosTratados().map((c) => p.plazos?.[c]).filter((d) => d !== undefined && d !== null);
        let propuesto;
        let ayuda = ayudaPlazoBase;
        if (oficiales.length) {
            propuesto = Math.max(...oficiales);
            ayuda = 'Plazo oficial según la ficha del MAPA para este cultivo' + (propuesto === 0 ? ' (no procede).' : '.');
        } else if (plazosAnteriores[p.id] !== undefined) {
            propuesto = plazosAnteriores[p.id];
            ayuda = 'Propuesto el que anotaste la última vez con este producto. ' + ayudaPlazoBase;
        } else {
            propuesto = p.plazo_seguridad_dias ?? '';
        }
        plazo.value = propuesto;
        plazo.dataset.auto = '1';
        ayudaPlazo.textContent = ayuda;
    }

    plazo.addEventListener('input', () => { plazo.dataset.auto = ''; });

    // Coste estimado: dosis × superficie tratada × precio
    function haTratadas() {
        if (filas.length) {
            return filas.filter((f) => f.querySelector('input').checked).reduce((suma, f) => suma + parseFloat(f.dataset.ha || 0), 0);
        }
        return parseFloat(superficie?.value) || haParcela || 0;
    }

    function actualizarCoste() {
        const d = parseFloat(dosis.value);
        const pr = parseFloat(precio.value);
        const ha = haTratadas();
        const caja = $('#coste-estimado');
        if (!(d > 0) || !(pr > 0) || !(ha > 0)) { caja.classList.add('hidden'); return; }
        $('#coste-importe').textContent = euros(d * ha * pr);
        $('#coste-detalle').textContent = '(' + numero(d * ha) + ' ' + (productos[select.value]?.unidad || 'l') + ' en '
            + numero(ha) + ' ha · ' + euros(d * pr) + '/ha)';
        caja.classList.remove('hidden');
    }

    [precio, dosis, superficie].filter(Boolean).forEach((e) => e.addEventListener('input', actualizarCoste));

    // Parcelas de la finca: se bloquean las de cultivos para los que el producto no está autorizado
    function autorizada(p, cultivo) {
        return !p || !Array.isArray(p.cultivos) || !cultivosClasificados.includes(cultivo) || p.cultivos.includes(cultivo);
    }

    function actualizarTotales() {
        const activas = filas.map((f) => f.querySelector('input')).filter((c) => !c.disabled);
        const ha = filas.filter((f) => f.querySelector('input').checked).reduce((suma, f) => suma + parseFloat(f.dataset.ha || 0), 0);
        raiz.querySelectorAll('.parcelas-ha').forEach((e) => { e.textContent = numero(ha); });
        todas.checked = activas.length > 0 && activas.every((c) => c.checked);
        actualizarCoste();
        if (plazo.dataset.auto === '1') proponerPlazo();
    }

    function actualizarParcelas() {
        if (!filas.length) return;
        const p = productos[select.value];
        filas.forEach((f) => {
            const check = f.querySelector('input');
            const ok = autorizada(p, f.dataset.cultivo);
            if (!ok && !check.disabled) { check.dataset.estaba = check.checked ? '1' : ''; check.checked = false; }
            if (ok && check.disabled) { check.checked = check.dataset.estaba === '1'; }
            check.disabled = !ok;
            f.classList.toggle('opacity-60', !ok);
            f.querySelector('.parcela-aviso').classList.toggle('hidden', ok);
        });
        if (filas.some((f) => f.querySelector('input').disabled)) {
            $('#parcelas-elegir').click();
        }
        actualizarTotales();
    }

    if (filas.length) {
        filas.forEach((f) => f.querySelector('input').addEventListener('change', actualizarTotales));
        $('#parcelas-elegir').addEventListener('click', () => {
            $('#parcelas-resumen').classList.add('hidden');
            $('#parcelas-lista').classList.remove('hidden');
            $('#parcelas-todas-label').classList.remove('hidden');
        });
        todas.addEventListener('change', () => {
            filas.map((f) => f.querySelector('input')).filter((c) => !c.disabled).forEach((c) => { c.checked = todas.checked; });
            actualizarTotales();
        });
    }

    if (buscar) {
        buscar.addEventListener('input', () => {
            const texto = buscar.value.trim().toLowerCase();
            opciones.forEach((o) => { o.hidden = texto !== '' && !o.dataset.buscar.includes(texto) && !o.selected; });
        });
    }

    // Inspección del equipo: vale unos años desde su fecha; se avisa si no cubría el día del tratamiento
    const inspeccion = $('#equipo_inspeccion_fecha');
    const fechaTratamiento = $('#fecha');
    function comprobarInspeccion() {
        let caducada = false;
        if (inspeccion.value && fechaTratamiento.value) {
            const limite = new Date(inspeccion.value);
            limite.setFullYear(limite.getFullYear() + parseInt(inspeccion.dataset.vigencia, 10));
            caducada = limite < new Date(fechaTratamiento.value);
        }
        $('#inspeccion-caducada').classList.toggle('hidden', !caducada);
    }
    [inspeccion, fechaTratamiento].forEach((e) => e.addEventListener('input', comprobarInspeccion));
    comprobarInspeccion();

    select.addEventListener('change', () => actualizarInfo(true));
    actualizarInfo(!editando && plazo.value === '');
});
