/*
 * Service Worker - Defensa Civil Ayacucho · Empadronamiento (PWA)
 *
 *  - Páginas HTML (navegación): Network-First, guarda la última copia buena para abrir sin señal.
 *  - Assets / iconos / CDN (Bootstrap, Bootstrap Icons, fuentes): Cache-First.
 *  - NUNCA intercepta peticiones que no sean GET (POST /captura, /login, /logout...)
 *    ni las llamadas a /captura/consultar-dni/: pasan directo a la red, sin caché.
 *    (La cola offline y la sincronización viven en el cliente y no se alteran.)
 */
const VERSION = 'v2';
const CACHE_PAGES = 'dc-paginas-' + VERSION;
const CACHE_ASSETS = 'dc-assets-' + VERSION;
const CACHES_ACTUALES = [CACHE_PAGES, CACHE_ASSETS];

const NETWORK_TIMEOUT_MS = 5000; // señal débil: si hay copia, se usa tras este tiempo

const PAGINAS_PRECACHE = ['/login', '/captura'];

const ASSETS_PRECACHE = [
    '/manifest.json',
    '/icons/icon-192.svg',
    '/icons/icon-512.svg',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2?dd67030699838ea613ee6dbda90effa6',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff?dd67030699838ea613ee6dbda90effa6',
];

const HOSTS_ASSETS_EXTERNOS = [
    'cdn.jsdelivr.net',
    'fonts.googleapis.com',
    'fonts.gstatic.com',
];

const RUTAS_SIN_CACHE = ['/captura/consultar-dni/'];

/* ---------- Ciclo de vida ---------- */
self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const assets = await caches.open(CACHE_ASSETS);
        const paginas = await caches.open(CACHE_PAGES);

        // Best-effort: un recurso que falle (p. ej. /captura sin sesión) no aborta la instalación.
        await Promise.allSettled(ASSETS_PRECACHE.map(async (url) => {
            const res = await fetch(new Request(url, { mode: 'no-cors', credentials: 'omit' }));
            if (esCacheable(res)) await assets.put(url, res);
        }));
        await Promise.allSettled(PAGINAS_PRECACHE.map(async (url) => {
            const res = await fetch(url, { credentials: 'same-origin' });
            // Una redirección (p. ej. /captura -> /login) se guarda bajo su URL final, no bajo la solicitada.
            if (res.ok && esHtml(res)) await paginas.put(res.redirected ? res.url : url, res);
        }));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const nombres = await caches.keys();
        await Promise.all(
            nombres
                .filter((n) => n.startsWith('dc-') && !CACHES_ACTUALES.includes(n))
                .map((n) => caches.delete(n))
        );
        await self.clients.claim();
    })());
});

/* ---------- Interceptación ---------- */
self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Regla crítica: jamás tocar POST/PUT/DELETE... (cola offline, login, logout, sincronización).
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.protocol !== 'http:' && url.protocol !== 'https:') return;

    // Regla crítica: consulta RENIEC siempre directa a la red.
    if (url.origin === self.location.origin && RUTAS_SIN_CACHE.some((p) => url.pathname.startsWith(p))) return;

    const mismoOrigen = url.origin === self.location.origin;

    if (request.mode === 'navigate' || (mismoOrigen && aceptaHtml(request))) {
        if (mismoOrigen) event.respondWith(networkFirstPagina(request));
        return;
    }

    if (esAsset(url, mismoOrigen)) {
        event.respondWith(cacheFirstAsset(request));
    }
    // Cualquier otra petición GET (XHR/JSON, etc.) pasa sin interceptar.
});

/* ---------- Estrategias ---------- */
async function networkFirstPagina(request) {
    const cache = await caches.open(CACHE_PAGES);
    const clave = new URL(request.url).pathname;
    const enCache = await cache.match(clave);

    try {
        const res = await fetchConTimeout(request, enCache ? NETWORK_TIMEOUT_MS : 0);
        if (res.ok && esHtml(res)) {
            // Si hubo redirección, la copia pertenece a la URL final (evita guardar /login bajo /captura).
            const destino = res.redirected ? new URL(res.url).pathname : clave;
            cache.put(destino, res.clone());
        }
        return res;
    } catch (_) {
        if (enCache) return enCache;
        const respaldo = (await cache.match('/captura')) || (await cache.match('/login'));
        if (respaldo) return respaldo;
        return new Response(
            '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
            '<title>Sin conexión</title></head><body style="font-family:system-ui,sans-serif;background:#0f172a;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center;padding:1rem">' +
            '<div><h1>Sin conexión</h1><p>Abre la app una vez con internet para poder usarla sin señal.</p></div></body></html>',
            { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
    }
}

async function cacheFirstAsset(request) {
    const cache = await caches.open(CACHE_ASSETS);
    const enCache = await cache.match(request, { ignoreVary: true });
    if (enCache) return enCache;

    try {
        const res = await fetch(request);
        if (esCacheable(res)) cache.put(request, res.clone());
        return res;
    } catch (err) {
        const alterna = await cache.match(request.url, { ignoreVary: true });
        if (alterna) return alterna;
        throw err;
    }
}

/* ---------- Utilidades ---------- */
function fetchConTimeout(request, ms) {
    if (!ms) return fetch(request);
    return new Promise((resolve, reject) => {
        const t = setTimeout(() => reject(new Error('timeout')), ms);
        fetch(request).then(
            (r) => { clearTimeout(t); resolve(r); },
            (e) => { clearTimeout(t); reject(e); }
        );
    });
}

function aceptaHtml(request) {
    return (request.headers.get('accept') || '').includes('text/html');
}

function esHtml(res) {
    return (res.headers.get('content-type') || '').includes('text/html');
}

// Respuestas válidas u opacas (CDN sin CORS) se pueden guardar; errores y parciales (206) no.
function esCacheable(res) {
    return res && (res.ok || res.type === 'opaque') && res.status !== 206;
}

function esAsset(url, mismoOrigen) {
    if (!mismoOrigen) return HOSTS_ASSETS_EXTERNOS.includes(url.hostname);
    return (
        url.pathname.startsWith('/icons/') ||
        url.pathname.startsWith('/build/') ||
        url.pathname === '/manifest.json' ||
        /\.(?:css|js|svg|png|jpe?g|gif|webp|ico|woff2?|ttf)$/i.test(url.pathname)
    );
}
