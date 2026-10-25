/**
 * offline-db.js — IndexedDB wrapper for Sandal House Offline POS
 * Stores products, customers, PLU list, queued invoices, and sync metadata.
 */
(function(global) {
    'use strict';

    const DB_NAME    = 'SandalHousePOS';
    const DB_VERSION = 2;

    const STORES = {
        products:          { keyPath: 'id' },
        customers:         { keyPath: 'id' },
        plu_list:          { keyPath: 'id' },
        offline_invoices:  { keyPath: 'offline_ref' },
        offline_customers: { keyPath: 'temp_id' },
        sync_meta:         { keyPath: 'key' },
    };

    let _db = null;

    function open() {
        return new Promise((resolve, reject) => {
            if (_db) { resolve(_db); return; }

            const req = indexedDB.open(DB_NAME, DB_VERSION);

            req.onupgradeneeded = function(e) {
                const db = e.target.result;
                Object.entries(STORES).forEach(([name, opts]) => {
                    if (!db.objectStoreNames.contains(name)) {
                        db.createObjectStore(name, opts);
                    }
                });
                // Index for plu_list by barcode
                if (!db.objectStoreNames.contains('plu_list')) return;
                const tx   = e.target.transaction;
                const pluS = tx.objectStore('plu_list');
                if (!pluS.indexNames.contains('barcode')) {
                    pluS.createIndex('barcode', 'barcode', { unique: false });
                }
                if (!pluS.indexNames.contains('plu_code')) {
                    pluS.createIndex('plu_code', 'plu_code', { unique: false });
                }
            };

            req.onsuccess = function(e) {
                _db = e.target.result;
                resolve(_db);
            };

            req.onerror = function(e) {
                reject(e.target.error);
            };
        });
    }

    // Generic put (upsert)
    function put(storeName, record) {
        return open().then(db => new Promise((resolve, reject) => {
            const tx  = db.transaction(storeName, 'readwrite');
            const req = tx.objectStore(storeName).put(record);
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        }));
    }

    // Bulk put (replaces all records in store)
    function putBulk(storeName, records) {
        if (!records || !records.length) return Promise.resolve(0);
        return open().then(db => new Promise((resolve, reject) => {
            const tx    = db.transaction(storeName, 'readwrite');
            const store = tx.objectStore(storeName);
            let count = 0;
            records.forEach(rec => { store.put(rec); count++; });
            tx.oncomplete = () => resolve(count);
            tx.onerror    = () => reject(tx.error);
        }));
    }

    // Get single record by key
    function get(storeName, key) {
        return open().then(db => new Promise((resolve, reject) => {
            const tx  = db.transaction(storeName, 'readonly');
            const req = tx.objectStore(storeName).get(key);
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        }));
    }

    // Get all records from a store
    function getAll(storeName) {
        return open().then(db => new Promise((resolve, reject) => {
            const tx  = db.transaction(storeName, 'readonly');
            const req = tx.objectStore(storeName).getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        }));
    }

    // Delete record by key
    function del(storeName, key) {
        return open().then(db => new Promise((resolve, reject) => {
            const tx  = db.transaction(storeName, 'readwrite');
            const req = tx.objectStore(storeName).delete(key);
            req.onsuccess = () => resolve();
            req.onerror   = () => reject(req.error);
        }));
    }

    // Clear entire store
    function clear(storeName) {
        return open().then(db => new Promise((resolve, reject) => {
            const tx  = db.transaction(storeName, 'readwrite');
            const req = tx.objectStore(storeName).clear();
            req.onsuccess = () => resolve();
            req.onerror   = () => reject(req.error);
        }));
    }

    // Count records in store
    function count(storeName) {
        return open().then(db => new Promise((resolve, reject) => {
            const tx  = db.transaction(storeName, 'readonly');
            const req = tx.objectStore(storeName).count();
            req.onsuccess = () => resolve(req.result);
            req.onerror   = () => reject(req.error);
        }));
    }

    // Get/set metadata (key-value pairs in sync_meta store)
    function getMeta(key) {
        return get('sync_meta', key).then(r => r ? r.value : null);
    }
    function setMeta(key, value) {
        return put('sync_meta', { key, value });
    }

    // ── PLU lookup by barcode or plu_code ──────────────────────────────────
    function lookupBarcode(query) {
        query = String(query).trim();
        return open().then(db => new Promise((resolve, reject) => {
            const tx    = db.transaction('plu_list', 'readonly');
            const store = tx.objectStore('plu_list');
            const results = [];

            store.openCursor().onsuccess = function(e) {
                const cursor = e.target.result;
                if (!cursor) { resolve(results); return; }
                const r = cursor.value;
                if (r.barcode === query || r.plu_code === query) results.push(r);
                cursor.continue();
            };
            tx.onerror = () => reject(tx.error);
        }));
    }

    // ── Offline invoice queue ──────────────────────────────────────────────
    function getOfflineInvoices() {
        return getAll('offline_invoices');
    }

    function getPendingInvoices() {
        return getAll('offline_invoices').then(all =>
            all.filter(inv => inv.sync_status === 'pending' || inv.sync_status === 'failed')
        );
    }

    function saveOfflineInvoice(invoice) {
        return put('offline_invoices', invoice);
    }

    function markInvoiceSynced(offline_ref, cloud_ref) {
        return get('offline_invoices', offline_ref).then(inv => {
            if (!inv) return;
            inv.sync_status = 'synced';
            inv.cloud_ref   = cloud_ref;
            inv.synced_at   = new Date().toISOString();
            return put('offline_invoices', inv);
        });
    }

    function markInvoiceFailed(offline_ref, error) {
        return get('offline_invoices', offline_ref).then(inv => {
            if (!inv) return;
            inv.sync_status  = 'failed';
            inv.last_error   = error;
            inv.retry_count  = (inv.retry_count || 0) + 1;
            return put('offline_invoices', inv);
        });
    }

    // Expose public API
    global.PosDB = {
        open, put, putBulk, get, getAll, del, clear, count,
        getMeta, setMeta,
        lookupBarcode,
        getOfflineInvoices, getPendingInvoices,
        saveOfflineInvoice, markInvoiceSynced, markInvoiceFailed,
    };

})(window);
