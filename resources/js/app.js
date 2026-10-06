/*
 * Todo el JavaScript de la aplicación vive en resources/js: las vistas no llevan <script> ni
 * manejadores en línea (onclick…), que la Content-Security-Policy bloquea. Las vistas declaran
 * lo que necesitan con atributos data-* (comportamientos.js) o con una raíz data-pagina (paginas/).
 */
import './comportamientos.js';
import './paginas/formularios.js';
import './paginas/fincas.js';
import './paginas/mapas.js';
import './paginas/tratamiento.js';
import './paginas/iniciar.js';

/*
 * Al enviar un formulario (salvo búsquedas GET), su botón muestra que se está guardando y no
 * admite un segundo clic: evita registrar dos veces el mismo tratamiento o gasto.
 */
document.addEventListener('submit', (evento) => {
    const formulario = evento.target;
    if (!(formulario instanceof HTMLFormElement) || evento.defaultPrevented) return;
    if ((formulario.method || 'get').toLowerCase() === 'get' || formulario.hasAttribute('data-sin-espera')) return;

    const boton = evento.submitter ?? formulario.querySelector('button[type="submit"], button:not([type])');
    if (!boton || boton.hasAttribute('data-enviando')) return;

    // Se marca tras el envío para no anular el valor del botón que se envía
    requestAnimationFrame(() => {
        boton.setAttribute('data-enviando', '');
        boton.setAttribute('aria-busy', 'true');
        boton.setAttribute('aria-disabled', 'true');
    });
});

// Volver con «atrás» restaura la página de la caché: los botones no deben seguir bloqueados
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-enviando]').forEach((boton) => {
        boton.removeAttribute('data-enviando');
        boton.removeAttribute('aria-busy');
        boton.removeAttribute('aria-disabled');
    });
});

/*
 * Parra de las pantallas de acceso: crece (tallo, sarmientos, hojas, uvas), se queda un rato y
 * se recoge en orden inverso (uvas primero, tallo al final), en bucle. Cada pieza lleva en
 * data-inicio/data-duracion cuándo y cuánto tarda en crecer; la recogida es su espejo.
 */
const PARRA_PAUSA_LLENA = 4000;
const PARRA_PAUSA_VACIA = 1500;

function animarParra() {
    const piezas = [...document.querySelectorAll('[data-parra]')];
    if (!piezas.length || typeof Element.prototype.animate !== 'function') return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const tramos = piezas.map((pieza) => ({
        pieza,
        inicio: Number(pieza.dataset.inicio) || 0,
        duracion: Number(pieza.dataset.duracion) || 800,
    }));
    const crecido = Math.max(...tramos.map((t) => t.inicio + t.duracion));
    const ciclo = 2 * crecido + PARRA_PAUSA_LLENA + PARRA_PAUSA_VACIA;

    for (const { pieza, inicio, duracion } of tramos) {
        if (pieza.getAnimations().some((a) => a.id === 'parra')) continue;

        const recoge = crecido + PARRA_PAUSA_LLENA + (crecido - inicio - duracion);
        const recogeDuracion = duracion * 0.8;
        const opacidad = pieza.getAttribute('opacity') ?? '1';
        const [oculta, visible] = pieza.dataset.parra === 'trazo'
            ? [{ strokeDashoffset: 1 }, { strokeDashoffset: 0 }]
            : [{ transform: 'scale(0)', opacity: 0 }, { transform: 'scale(1)', opacity: opacidad }];
        const brote = pieza.dataset.parra === 'trazo'
            ? 'cubic-bezier(0.45, 0, 0.2, 1)'
            : 'cubic-bezier(0.34, 1.56, 0.64, 1)';

        pieza.animate([
            { offset: 0, ...oculta },
            { offset: inicio / ciclo, ...oculta, easing: brote },
            { offset: (inicio + duracion) / ciclo, ...visible },
            { offset: recoge / ciclo, ...visible, easing: 'cubic-bezier(0.55, 0, 0.75, 0.2)' },
            { offset: (recoge + recogeDuracion) / ciclo, ...oculta },
            { offset: 1, ...oculta },
        ], { duration: ciclo, iterations: Infinity, id: 'parra' });
    }
}

// livewire:navigated también salta en la carga inicial y al cambiar entre login y registro
document.addEventListener('livewire:navigated', animarParra);
document.addEventListener('DOMContentLoaded', animarParra);
