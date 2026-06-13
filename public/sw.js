const CACHE_VERSION = 'wing-pos-v3';
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

// ── Background Sync: replay queued offline sales when connectivity returns ──
const SYNC_TAG = 'sync-pending-sales';

self.addEventListener('sync', event => {
    if (event.tag === SYNC_TAG) {
        event.waitUntil(syncPendingSales());
    }
});

async function syncPendingSales() {
    const db    = await openOfflineDB();
    const sales = await getAllPending(db);
    if (!sales.length) return;

    console.log(`[SW] Syncing ${sales.length} pending sale(s)...`);

    for (const sale of sales) {
        try {
            const res = await fetch('/sales/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':  sale.csrf_token,
                    'Accept':        'application/json',
                },
                body: JSON.stringify(sale.payload),
            });

            const json = await res.json();

            if (res.ok && json.success) {
                await deletePending(db, sale.id);
                console.log(`[SW] Synced sale id=${sale.id} → ${json.receipt_number}`);

                const clients = await self.clients.matchAll();
                clients.forEach(client => client.postMessage({
                    type:          'SALE_SYNCED',
                    localId:       sale.id,
                    receiptNumber: json.receipt_number,
                }));
            } else {
                console.warn(`[SW] Sync rejected for id=${sale.id}:`, json.message);
            }
        } catch (err) {
            console.error(`[SW] Sync failed for id=${sale.id}:`, err);
            // Left in the queue; will retry on the next sync event.
        }
    }
}

// ── IndexedDB helpers (must match public/js/offline-pos.js) ────────────────
function openOfflineDB() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open('wingpos_offline', 1);
        req.onupgradeneeded = e => {
            const store = e.target.result.createObjectStore('pending_sales', { keyPath: 'id', autoIncrement: true });
            store.createIndex('saved_at', 'saved_at');
        };
        req.onsuccess = e => resolve(e.target.result);
        req.onerror   = e => reject(e.target.error);
    });
}

function getAllPending(db) {
    return new Promise((resolve, reject) => {
        const req = db.transaction('pending_sales', 'readonly').objectStore('pending_sales').getAll();
        req.onsuccess = () => resolve(req.result);
        req.onerror   = () => reject(req.error);
    });
}

function deletePending(db, id) {
    return new Promise((resolve, reject) => {
        const req = db.transaction('pending_sales', 'readwrite').objectStore('pending_sales').delete(id);
        req.onsuccess = () => resolve();
        req.onerror   = () => reject(req.error);
    });
}
