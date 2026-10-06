/*
 * Código propio de cada página. Cada módulo se registra con el selector de su elemento raíz y se
 * ejecuta cuando ese elemento está en la página: al cargarla y tras cada navegación con
 * wire:navigate (que cambia el contenido sin recargar). Cada raíz se inicia una sola vez.
 */
const paginas = [];

export function enPagina(selector, iniciar) {
    paginas.push({ selector, iniciar });
}

/**
 * Datos que la vista pasa al script en un <script type="application/json" data-datos>:
 * el navegador no lo ejecuta, así que la Content-Security-Policy lo permite.
 */
export function datosDe(raiz) {
    const bloque = raiz.querySelector('script[type="application/json"][data-datos]');
    return bloque ? JSON.parse(bloque.textContent) : {};
}

function iniciarPaginas() {
    for (const { selector, iniciar } of paginas) {
        document.querySelectorAll(selector).forEach((raiz) => {
            if (raiz.dataset.iniciado) return;
            raiz.dataset.iniciado = '1';
            iniciar(raiz);
        });
    }
}

// livewire:navigated salta también en la carga inicial; DOMContentLoaded por si Livewire no está
document.addEventListener('livewire:navigated', iniciarPaginas);
document.addEventListener('DOMContentLoaded', iniciarPaginas);
