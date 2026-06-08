const CACHE_VERSION = 'wing-pos-v2';
const POS_ROUTES = ['/pos', '/sales/create'];

// Install: activate immediately without waiting for old SW to finish
self.addEventListener('install', event => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_VERSION).then(cache =>
            // Pre-cache POS routes — best effort, won't fail install if not reachable
            Promise.allSettled(POS_ROUTES.map(url => cache.add(url)))
        )
    );
});

// Activate: delete all old caches then take control immediately
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(
                keys.filter(k => k !== CACHE_VERSION).map(k => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Only handle same-origin GET requests
    if (event.request.method !== 'GET') return;
    if (url.origin !== self.location.origin) return;

    // Never intercept API calls — they must reach the server
    if (url.pathname.startsWith('/api/')) return;

    // Vite build assets are content-hashed — safe to serve from cache forever
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                if (cached) return cached;
                return fetch(event.request).then(response => {
                    if (response.ok) {
                        caches.open(CACHE_VERSION).then(c => c.put(event.request, response.clone()));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Page navigations — network-first so content stays fresh, cache as fallback when offline
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    if (response.ok) {
                        caches.open(CACHE_VERSION).then(c => c.put(event.request, response.clone()));
                    }
                    return response;
                })
                .catch(() => caches.match(event.request))
        );
        return;
    }

    // Other static assets (images, icons) — network-first, cache fallback
    event.respondWith(
        fetch(event.request)
            .then(response => {
                if (response.ok) {
                    caches.open(CACHE_VERSION).then(c => c.put(event.request, response.clone()));
                }
                return response;
            })
            .catch(() => caches.match(event.request))
    );
});
