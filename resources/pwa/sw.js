const VERSION = '__VERSION__';
const STATIC_CACHE = 'hefam-static-' + VERSION;
const PAGE_CACHE = 'hefam-pages';
const OFFLINE_URL = '__OFFLINE_URL__';
const OFFLINE_PAGES = __OFFLINE_PAGES__;
const CDN_HOSTS = ['cdn.jsdelivr.net', 'fonts.googleapis.com', 'fonts.gstatic.com'];
const PAGE_TIMEOUT_MS = 8000;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll([OFFLINE_URL, '__ICON_URL__']))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((key) => key.startsWith('hefam-static-') && key !== STATIC_CACHE)
                .map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data === 'clear-pages') {
        event.waitUntil(caches.delete(PAGE_CACHE));
    }
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    const sameOrigin = url.origin === self.location.origin;

    if (request.mode === 'navigate') {
        if (sameOrigin && OFFLINE_PAGES.includes(url.pathname)) {
            event.respondWith(networkFirstPage(request));
        } else {
            event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        }
        return;
    }

    if (CDN_HOSTS.includes(url.hostname) || (sameOrigin && url.pathname.startsWith('/pwa/'))) {
        event.respondWith(staleWhileRevalidate(request));
    }
});

function fetchWithTimeout(request, ms) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('timeout')), ms);
        fetch(request).then((response) => {
            clearTimeout(timer);
            resolve(response);
        }, (error) => {
            clearTimeout(timer);
            reject(error);
        });
    });
}

async function networkFirstPage(request) {
    const cache = await caches.open(PAGE_CACHE);

    try {
        const response = await fetchWithTimeout(request, PAGE_TIMEOUT_MS);
        if (response.ok && !response.redirected) {
            await cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = (await cache.match(request)) || (await cache.match(request, { ignoreSearch: true }));
        if (!cached) {
            return caches.match(OFFLINE_URL);
        }
        const html = (await cached.text()).replace('<body', '<body data-from-cache="1"');
        return new Response(html, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
    }
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);
    const network = fetch(request)
        .then((response) => {
            if (response.ok || response.type === 'opaque') {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => cached);

    return cached || network;
}
