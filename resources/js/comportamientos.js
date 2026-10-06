/*
 * Comportamientos comunes declarados con atributos data-* en las vistas. Sustituyen a los
 * manejadores en línea (onclick, onchange, onsubmit…), que la Content-Security-Policy bloquea.
 * Se escucha en document, así que valen también para lo que llega con wire:navigate.
 *
 *   data-confirmar="¿Seguro?"   en un <form>: pide confirmación antes de enviarlo
 *   data-autoenviar             en un <select> o checkbox: envía su formulario al cambiar
 *   data-ir-a-valor             en un <select>: navega a la URL de la opción elegida
 *   data-imprimir               en un botón: abre el diálogo de impresión
 *   data-alternar="#id"         en un botón: muestra u oculta (clase hidden) el elemento
 *   data-ocultar="#id"          en un botón: oculta el elemento
 */

// En captura, antes del aviso de «enviando» de app.js: si se cancela, el botón no se bloquea
document.addEventListener('submit', (evento) => {
    const formulario = evento.target;
    const mensaje = formulario instanceof HTMLFormElement ? formulario.dataset.confirmar : undefined;
    if (mensaje && !window.confirm(mensaje)) {
        evento.preventDefault();
        evento.stopImmediatePropagation();
    }
}, true);

document.addEventListener('change', (evento) => {
    const campo = evento.target;
    if (!(campo instanceof HTMLElement)) return;

    if (campo.hasAttribute('data-autoenviar') && campo.form) {
        campo.form.requestSubmit();
    }
    if (campo.hasAttribute('data-ir-a-valor') && campo.value) {
        window.location.assign(campo.value);
    }
});

document.addEventListener('click', (evento) => {
    const boton = evento.target instanceof Element ? evento.target.closest('[data-imprimir], [data-alternar], [data-ocultar]') : null;
    if (!boton) return;

    if (boton.hasAttribute('data-imprimir')) {
        window.print();
    }
    if (boton.dataset.alternar) {
        document.querySelector(boton.dataset.alternar)?.classList.toggle('hidden');
    }
    if (boton.dataset.ocultar) {
        document.querySelector(boton.dataset.ocultar)?.classList.add('hidden');
    }
});
