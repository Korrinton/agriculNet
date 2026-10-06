import { datosDe, enPagina } from './iniciar.js';

/*
 * Variedades según el uso de la parcela: cada [data-parcela-form] tiene un selector de uso
 * ([data-uso]) y uno de variedad ([data-variedad]) que solo ofrece las del cultivo de ese uso.
 * La raíz [data-variedades-por-uso] lleva el catálogo (vinedo/parcelas/_variedades_por_uso).
 */
let catalogo = null;

export function filtrarVariedades(contenedor) {
    const uso = contenedor?.querySelector('[data-uso]');
    const select = contenedor?.querySelector('[data-variedad]');
    if (!catalogo || !uso || !select) return;

    const cultivo = catalogo.cultivoPorUso[uso.value] || null;
    const actual = select.value;

    select.innerHTML = '';
    select.add(new Option(cultivo ? (select.dataset.vacio || '—') : 'Elige primero el uso', ''));
    catalogo.variedades
        .filter((v) => v.cultivo === cultivo)
        .forEach((v) => select.add(new Option(v.etiqueta, v.id, false, String(v.id) === actual)));
    select.disabled = !cultivo;

    const etiqueta = contenedor.querySelector('[data-variedad-label]');
    if (etiqueta) etiqueta.textContent = cultivo ? catalogo.campoPorCultivo[cultivo] : 'Variedad';
}

enPagina('[data-variedades-por-uso]', (raiz) => {
    catalogo = datosDe(raiz);
    document.querySelectorAll('[data-parcela-form]').forEach(filtrarVariedades);
});

document.addEventListener('change', (evento) => {
    if (evento.target instanceof Element && evento.target.matches('[data-uso]')) {
        filtrarVariedades(evento.target.closest('[data-parcela-form]'));
    }
});

// Provincia: el selector por nombre y el campo de código van sincronizados
enPagina('[data-pagina="provincia"]', (raiz) => {
    const nombre = raiz.querySelector('#provincia_nombre');
    const codigo = raiz.querySelector('#provincia_cod');
    nombre.addEventListener('change', () => { codigo.value = nombre.value; });
    codigo.addEventListener('input', () => { nombre.value = codigo.value; });
});

// Nueva finca: añadir y quitar parcelas del formulario (siempre queda al menos una)
enPagina('[data-pagina="nueva-finca"]', (raiz) => {
    const lista = raiz.querySelector('#parcelas-list');
    // La plantilla va fuera del formulario, al final de la vista
    const plantilla = document.getElementById('parcela-template');
    let siguiente = lista.querySelectorAll('.parcela-card').length;

    function actualizar() {
        const tarjetas = lista.querySelectorAll('.parcela-card');
        tarjetas.forEach((tarjeta, i) => {
            const titulo = tarjeta.querySelector('.parcela-title');
            if (titulo) titulo.textContent = 'Parcela ' + (i + 1);
            tarjeta.querySelector('.remove-btn')?.classList.toggle('hidden', tarjetas.length === 1);
        });
        const n = tarjetas.length;
        raiz.querySelector('#parcelas-count-label').textContent = n + (n === 1 ? ' parcela' : ' parcelas');
    }

    raiz.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-anadir-parcela], .remove-btn');
        if (!boton) return;

        if (boton.matches('[data-anadir-parcela]')) {
            const nueva = plantilla.content.cloneNode(true);
            nueva.querySelectorAll('[name]').forEach((campo) => { campo.name = campo.name.replace(/IDX/g, siguiente); });
            lista.appendChild(nueva);
            filtrarVariedades(lista.lastElementChild);
            siguiente++;
        } else if (lista.querySelectorAll('.parcela-card').length > 1) {
            boton.closest('.parcela-card').remove();
        }
        actualizar();
    });

    actualizar();
});
