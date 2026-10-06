import { datosDe, enPagina } from './iniciar.js';

const numero = (valor, decimales) => valor.toLocaleString('es-ES', { maximumFractionDigits: decimales });

// Nuevo gasto: €/ha orientativo de la parcela elegida o de toda la finca
enPagina('[data-pagina="coste"]', (raiz) => {
    const destino = raiz.querySelector('#parcela_id');
    const importe = raiz.querySelector('#importe');
    const salida = raiz.querySelector('#coste-ha');

    function actualizar() {
        const euros = parseFloat(importe.value);
        const ha = parseFloat(destino.selectedOptions[0]?.dataset.ha || 0);
        salida.textContent = isNaN(euros) || euros <= 0 || !(ha > 0)
            ? ''
            : numero(euros / ha, 2) + ' €/ha' + (destino.value ? '' : ' de la finca');
    }
    importe.addEventListener('input', actualizar);
    destino.addEventListener('change', actualizar);
    actualizar();
});

// Cosecha: propone el producto según el cultivo de la parcela si aún no se ha escrito nada
enPagina('[data-pagina="cosecha"]', (raiz) => {
    const parcela = raiz.querySelector('#parcela_id');
    const producto = raiz.querySelector('#producto');
    parcela.addEventListener('change', () => {
        const sugerido = parcela.selectedOptions[0]?.dataset.producto;
        if (sugerido && !producto.value) producto.value = sugerido;
    });
});

// Observación fenológica: descripción del estado elegido
enPagina('[data-pagina="observacion"]', (raiz) => {
    const { estados } = datosDe(raiz);
    const select = raiz.querySelector('#estado_fenologico_id');
    const caja = raiz.querySelector('#estado-desc');
    const texto = raiz.querySelector('#desc-text');

    function actualizar() {
        const estado = estados[select.value];
        if (!estado || !estado.descripcion) { caja.classList.add('hidden'); return; }
        texto.textContent = estado.descripcion;
        caja.classList.remove('hidden');
    }
    select.addEventListener('change', actualizar);
    actualizar();
});

// Riego: dosis resultante mientras se escribe (1 mm = 10 m³/ha)
enPagina('[data-pagina="riego"]', (raiz) => {
    const parcela = raiz.querySelector('#parcela_id');
    const volumen = raiz.querySelector('#volumen_m3');
    const superficie = raiz.querySelector('#superficie_ha');
    const salida = raiz.querySelector('#dosis');

    function actualizar() {
        const ha = parseFloat(superficie.value) || parseFloat(parcela.selectedOptions[0]?.dataset.superficie);
        const m3 = parseFloat(volumen.value);
        salida.textContent = ha > 0 && m3 > 0
            ? `Dosis: ${numero(m3 / ha, 0)} m³/ha · ${numero(m3 / ha / 10, 1)} mm`
            : '';
    }
    [parcela, volumen, superficie].forEach((campo) => campo.addEventListener('input', actualizar));
    parcela.addEventListener('change', actualizar);
    actualizar();
});
