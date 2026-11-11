/**
 * Nobat service worker — deliberately conservative.
 *
 * Only immutable, content-hashed assets (Vite build output, app icons and
 * the web manifest) are served cache-first. Pages and Inertia JSON responses
 * always go to the network so booking data is never stale, which keeps the
 * demo on the Render free tier predictable.
 */
const CACHE = 'nobat-static-v1';
const PRECACHE = ['/icons/pwa-192.png', '/icons/pwa-512.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const keys = await caches.keys();
            await Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key)));
            await self.clients.claim();
        })(),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    const cacheable =
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/') ||
        url.pathname === '/manifest.webmanifest';

    if (!cacheable) {
        return; // network as usual
    }

    event.respondWith(
        caches.open(CACHE).then(async (cache) => {
            const cached = await cache.match(request);

            if (cached) {
                return cached;
            }

            const response = await fetch(request);

            if (response.ok) {
                cache.put(request, response.clone());
            }

            return response;
        }),
    );
});
