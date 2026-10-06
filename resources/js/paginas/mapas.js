import { datosDe, enPagina } from './iniciar.js';

/*
 * Mapas de recintos SIGPAC (ficha de la finca y de la parcela). Leaflet se descarga solo en
 * estas páginas (import dinámico) y desde la propia aplicación, no desde un CDN externo.
 * Las geometrías se piden al servidor, que hace de intermediario con SIGPAC.
 */
const ORTOFOTO_PNOA = 'https://www.ign.es/wmts/pnoa-ma?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=OI.OrthoimageCoverage'
    + '&STYLE=default&TILEMATRIXSET=GoogleMapsCompatible&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}&FORMAT=image%2Fpng';
const MAPA_OSM = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
const CENTRO_ESPANA = [39.5, -3.0];

async function cargarLeaflet() {
    const [{ default: L }] = await Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]);
    return L;
}

function recinto(L, geojson, opacidad) {
    return L.geoJSON(geojson, { style: { color: '#16a34a', weight: 2.5, fillColor: '#4ade80', fillOpacity: opacidad } });
}

async function geometria(url) {
    const respuesta = await fetch(url, { credentials: 'same-origin' });
    if (!respuesta.ok) throw new Error('HTTP ' + respuesta.status);
    const geojson = await respuesta.json();
    if (!geojson || geojson.error) throw new Error('sin geometría');
    return geojson;
}

// Ficha de la finca: todas sus parcelas con referencia SIGPAC, con ortofoto o mapa
enPagina('[data-pagina="mapa-finca"]', async (raiz) => {
    const { parcelas } = datosDe(raiz);
    const L = await cargarLeaflet();

    const capas = {
        pnoa: L.tileLayer(ORTOFOTO_PNOA, { attribution: '© IGN España', maxZoom: 20 }),
        osm: L.tileLayer(MAPA_OSM, { attribution: '© OpenStreetMap', maxZoom: 19 }),
    };
    const mapa = L.map(raiz.querySelector('#sigpac-map'), { center: CENTRO_ESPANA, zoom: 8, layers: [capas.pnoa] });

    raiz.querySelectorAll('[data-capa]').forEach((boton) => boton.addEventListener('click', () => {
        Object.entries(capas).forEach(([nombre, capa]) => {
            const elegida = nombre === boton.dataset.capa;
            elegida ? mapa.addLayer(capa) : mapa.removeLayer(capa);
            raiz.querySelector(`[data-capa="${nombre}"]`)?.classList.toggle('bg-gray-100', elegida);
        });
    }));

    if (!parcelas.length) return;

    const cargando = raiz.querySelector('#map-loading');
    cargando.classList.remove('hidden');
    const limites = [];
    let fallidas = 0;

    await Promise.all(parcelas.map(async (p) => {
        try {
            const capa = recinto(L, await geometria(p.sigpacUrl), 0.35).addTo(mapa);
            capa.bindTooltip(p.nombre, { permanent: false, direction: 'top' });
            if (capa.getBounds().isValid()) limites.push(capa.getBounds());
        } catch (error) {
            fallidas++;
            console.warn(`SIGPAC: no se pudo cargar «${p.nombre}»`, error);
        }
    }));

    cargando.classList.add('hidden');
    if (limites.length) {
        mapa.fitBounds(limites.reduce((total, b) => total.extend(b)), { padding: [40, 40], maxZoom: 18 });
    }
    if (fallidas) {
        console.warn(`SIGPAC: ${fallidas} de ${parcelas.length} parcela(s) sin geometría.`);
    }
});

// Ficha de la parcela: su recinto sobre la ortofoto, sin controles
enPagina('[data-pagina="mapa-parcela"]', async (raiz) => {
    const { apiUrl } = datosDe(raiz);
    const L = await cargarLeaflet();
    const mapa = L.map(raiz.querySelector('#mini-map'), {
        center: CENTRO_ESPANA, zoom: 13, zoomControl: false, attributionControl: false,
        layers: [L.tileLayer(ORTOFOTO_PNOA, { maxZoom: 20 })],
    });

    try {
        const capa = recinto(L, await geometria(apiUrl), 0.4).addTo(mapa);
        if (capa.getBounds().isValid()) mapa.fitBounds(capa.getBounds(), { padding: [20, 20], maxZoom: 18 });
    } catch {
        // Sin geometría en SIGPAC: queda la ortofoto
    }
});
