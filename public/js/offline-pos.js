/**
 * Wing POS — Offline Manager
 * Place at: public/js/offline-pos.js
 * Included globally via layouts/app.blade.php
 */

window.OfflinePOS = (() => {
    const DB_NAME    = 'wingpos_offline';
    const STORE_NAME = 'pending_sales';
    const SYNC_TAG   = 'sync-pending-sales';

    let db = null;

    // ── Open IndexedDB ────────────────────────────────────────────────────
    function openDB() {
        if (db) return Promise.resolve(db);

        return new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, 1);

            req.onupgradeneeded = e => {
                const store = e.target.result.createObjectStore(STORE_NAME, {
                    keyPath: 'id', autoIncrement: true,
                });
                store.createIndex('saved_at', 'saved_at');
            };

            req.onsuccess = e => { db = e.target.result; resolve(db); };
            req.onerror   = e => reject(e.target.error);
        });
    }

    // ── Save a sale locally ───────────────────────────────────────────────
    async function saveSale(payload) {
        const database = await openDB();
        const csrf     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        return new Promise((resolve, reject) => {
            const record = {
                payload,
                csrf_token: csrf,
                saved_at:   new Date().toISOString(),
            };

            const req = database
                .transaction(STORE_NAME, 'readwrite')
                .objectStore(STORE_NAME)
                .add(record);

            req.onsuccess = () => resolve(req.result); // returns the new local id
            req.onerror   = () => reject(req.error);
        });
    }

    // ── Count pending sales ───────────────────────────────────────────────
    async function countPending() {
        const database = await openDB();
        return new Promise((resolve, reject) => {
            const req = database
                .transaction(STORE_NAME, 'readonly')
                .objectStore(STORE_NAME)
                .count();
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    }

    // ── Get all pending sales ─────────────────────────────────────────────
    async function getAllPending() {
        const database = await openDB();
        return new Promise((resolve, reject) => {
            const req = database
                .transaction(STORE_NAME, 'readonly')
                .objectStore(STORE_NAME)
                .getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        });
    }

    // ── Trigger background sync ───────────────────────────────────────────
    async function requestSync() {
        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            const reg = await navigator.serviceWorker.ready;
            await reg.sync.register(SYNC_TAG);
            console.log('[OfflinePOS] Background sync registered');
        } else {
            // Fallback: sync immediately if background sync not supported
            await syncNow();
        }
    }

    // ── Manual sync (fallback for browsers without BackgroundSync) ─────────
    let isSyncing = false; // guard against concurrent runs double-posting a sale
    async function syncNow() {
        if (isSyncing) return;
        isSyncing = true;
        try {
        const pending = await getAllPending();
        if (!pending.length) return;

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        for (const sale of pending) {
            try {
                const res = await fetch('/sales/sync', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN':  sale.csrf_token || csrf,
                        'Accept':        'application/json',
                    },
                    body: JSON.stringify(sale.payload),
                });

                const json = await res.json();

                if (res.ok && json.success) {
                    const database = await openDB();
                    database.transaction(STORE_NAME, 'readwrite')
                            .objectStore(STORE_NAME)
                            .delete(sale.id);

                    console.log(`[OfflinePOS] Synced → ${json.receipt_number}`);
                    updateBadge();
                }
            } catch (err) {
                console.warn('[OfflinePOS] Manual sync failed for id=', sale.id, err);
            }
        }
        } finally {
            isSyncing = false;
        }
    }

    // ── Update the pending-sales badge in the sidebar ─────────────────────
    async function updateBadge() {
        const count  = await countPending();
        const badge  = document.getElementById('pendingSalesBadge');
        const topBar = document.getElementById('pendingTopBadge');

        if (badge) {
            badge.textContent = count > 0 ? count : '';
            badge.style.display = count > 0 ? 'inline-flex' : 'none';
        }

        if (topBar) {
            if (count > 0) {
                topBar.style.display = 'inline-flex';
                topBar.innerHTML = `<i class="bi bi-clock-history"></i> ${count} pending`;
            } else {
                topBar.style.display = 'none';
            }
        }
    }

    // ── Listen for SW sync confirmation messages ───────────────────────────
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', event => {
            if (event.data?.type === 'SALE_SYNCED') {
                updateBadge();
                showSyncToast(event.data.receiptNumber);
            }
        });
    }

    // ── Show a toast when a sale is auto-synced ───────────────────────────
    function showSyncToast(receiptNumber) {
        const toast = document.createElement('div');
        toast.className = 'wingpos-toast';
        toast.innerHTML = `
            <i class="bi bi-check-circle-fill text-success me-2"></i>
            Offline sale synced: <strong>${receiptNumber}</strong>
        `;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 50);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    }

    // ── Auto-sync when back online ─────────────────────────────────────────
    window.addEventListener('online', async () => {
        updateBadge();
        const count = await countPending();
        if (count > 0) {
            showSyncToast(`Syncing ${count} offline sale(s)...`);
            await requestSync();
        }
    });

    // ── Init: update badge on page load ───────────────────────────────────
    document.addEventListener('DOMContentLoaded', updateBadge);

    // Public API
    return { saveSale, countPending, getAllPending, requestSync, syncNow, updateBadge };
})();
