/**
 * offline-sync.js — Offline-First POS Sync Engine
 * - Monitors connectivity
 * - Shows status badge (🟢/🔴/🟡)
 * - Downloads and maintains local data cache (IndexedDB)
 * - Uploads queued offline invoices when connectivity returns
 * - Intercepts billing AJAX calls for offline fallback
 */
(function(global) {
    'use strict';

    // ─── Configuration ────────────────────────────────────────────────────────
    const BASE_URL     = global.APP_BASE_URL || '/';
    const SYNC_INTERVAL_ONLINE  = 30000;  // poll connectivity every 30s
    const SYNC_INTERVAL_OFFLINE = 10000;  // re-check more aggressively when offline
    const DELTA_INTERVAL        = 300000; // refresh data every 5 min when online
    const MAX_RETRY             = 5;

    // ─── State ────────────────────────────────────────────────────────────────
    let _online       = navigator.onLine;
    let _syncing      = false;
    let _lastDelta    = 0;
    let _badgeEl      = null;
    let _syncTimer    = null;
    let _pendingCount = 0;

    // ─── Badge UI ─────────────────────────────────────────────────────────────
    function _getBadge() {
        if (!_badgeEl) _badgeEl = document.getElementById('pos-sync-badge');
        return _badgeEl;
    }

    function _updateBadge(state, pendingCount) {
        const el = _getBadge();
        if (!el) return;

        el.classList.remove('badge-online','badge-offline','badge-syncing');

        if (state === 'online') {
            el.classList.add('badge-online');
            el.innerHTML = '🟢 Online';
            if (pendingCount > 0) el.innerHTML += ` <span class="badge badge-warning ml-1">${pendingCount} pending</span>`;
        } else if (state === 'syncing') {
            el.classList.add('badge-syncing');
            el.innerHTML = '🟡 Syncing... <span class="spinner-border spinner-border-sm" role="status"></span>';
        } else {
            el.classList.add('badge-offline');
            el.innerHTML = '🔴 Offline — Working Locally';
        }
    }

    // ─── Connectivity Detection ──────────────────────────────────────────────
    async function _checkConnectivity() {
        if (!navigator.onLine) return false;
        try {
            // Light-weight probe — use a cache-busted tiny endpoint
            const res = await fetch(BASE_URL + 'offline_sync/status_json?_=' + Date.now(), {
                method: 'GET',
                cache:  'no-store',
                signal: AbortSignal.timeout(5000),
            });
            return res.ok;
        } catch {
            return false;
        }
    }

    async function _onConnectivityChange(isOnline) {
        const wasOnline = _online;
        _online = isOnline;

        _updateBadge(isOnline ? 'online' : 'offline', _pendingCount);

        if (isOnline && !wasOnline) {
            // Just came online — trigger full sync
            console.info('[OfflineSync] Connectivity restored. Starting sync...');
            await _runSync();
            // Also refresh delta data
            await _refreshDelta();
        }
    }

    // ─── Data Download (Initial + Delta) ─────────────────────────────────────
    async function downloadAllData() {
        try {
            const res  = await fetch(BASE_URL + 'offline_sync/get_data', {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);

            const d = json.data;
            await Promise.all([
                PosDB.putBulk('products',  d.products  || []),
                PosDB.putBulk('customers', d.customers || []),
                PosDB.putBulk('plu_list',  d.plu_list  || []),
            ]);
            await PosDB.setMeta('last_full_sync', d.synced_at);
            await PosDB.setMeta('last_sync_ts',   d.synced_at);
            await PosDB.setMeta('company',         JSON.stringify(d.company || {}));
            await PosDB.setMeta('settings',        JSON.stringify(d.settings || {}));
            await PosDB.setMeta('location',        JSON.stringify(d.location || {}));
            await PosDB.setMeta('auth',            JSON.stringify(d.auth    || {}));
            _lastDelta = d.synced_at || Math.floor(Date.now() / 1000);

            console.info('[OfflineSync] Full data downloaded.', {
                products:  d.products?.length,
                customers: d.customers?.length,
                plu_list:  d.plu_list?.length,
            });
        } catch (err) {
            console.warn('[OfflineSync] Full data download failed:', err.message);
        }
    }

    async function _refreshDelta() {
        if (!_lastDelta) {
            await downloadAllData();
            return;
        }
        try {
            const url  = BASE_URL + 'offline_sync/get_delta?since=' + _lastDelta + '&_=' + Date.now();
            const res  = await fetch(url, {
                cache: 'no-store',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const json = await res.json();
            if (!json.success) return;

            const d = json.data;
            if (d.products  && d.products.length)  await PosDB.putBulk('products',  d.products);
            if (d.customers && d.customers.length)  await PosDB.putBulk('customers', d.customers);
            if (d.plu_list  && d.plu_list.length)   await PosDB.putBulk('plu_list',  d.plu_list);

            _lastDelta = d.synced_at || Math.floor(Date.now() / 1000);
            await PosDB.setMeta('last_sync_ts', _lastDelta);

            if ((d.products?.length || 0) + (d.customers?.length || 0) + (d.plu_list?.length || 0) > 0) {
                console.info('[OfflineSync] Delta applied:', {
                    products: d.products?.length, customers: d.customers?.length, plu: d.plu_list?.length,
                });
            }
        } catch (err) {
            console.warn('[OfflineSync] Delta refresh failed:', err.message);
        }
    }

    // ─── Sync Queue ──────────────────────────────────────────────────────────
    async function _runSync() {
        if (_syncing || !_online) return;
        _syncing = true;
        _updateBadge('syncing', _pendingCount);

        try {
            const pending = await PosDB.getPendingInvoices();
            _pendingCount = pending.length;

            if (!pending.length) {
                _syncing = false;
                _updateBadge('online', 0);
                return;
            }

            let synced = 0, failed = 0;

            for (const inv of pending) {
                if ((inv.retry_count || 0) >= MAX_RETRY) {
                    failed++;
                    continue;
                }
                try {
                    const res = await fetch(BASE_URL + 'offline_sync/push_invoice', {
                        method:  'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(inv),
                    });

                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();

                    if (json.success) {
                        await PosDB.markInvoiceSynced(inv.offline_ref, json.cloud_ref);
                        synced++;
                        _pendingCount = Math.max(0, _pendingCount - 1);
                        _updateBadge('syncing', _pendingCount);
                        // Dispatch event for UI notification
                        document.dispatchEvent(new CustomEvent('pos:invoice_synced', {
                            detail: { offline_ref: inv.offline_ref, cloud_ref: json.cloud_ref }
                        }));
                    } else {
                        await PosDB.markInvoiceFailed(inv.offline_ref, json.message || 'Unknown error');
                        failed++;
                    }
                } catch (err) {
                    await PosDB.markInvoiceFailed(inv.offline_ref, err.message);
                    failed++;
                    if (!_online) break; // lost connectivity mid-sync, abort
                }
            }

            // Sync queued offline customers
            const offlineCusts = await PosDB.getAll('offline_customers');
            for (const c of offlineCusts) {
                if (c.synced) continue;
                try {
                    const res = await fetch(BASE_URL + 'offline_sync/push_customer', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify(c),
                    });
                    if (res.ok) {
                        const json = await res.json();
                        if (json.success) {
                            c.synced = true;
                            c.cloud_id = json.customer_id;
                            await PosDB.put('offline_customers', c);
                        }
                    }
                } catch { /* will retry */ }
            }

            console.info(`[OfflineSync] Sync complete. Synced: ${synced}, Failed: ${failed}`);

            const remaining = await PosDB.getPendingInvoices();
            _pendingCount = remaining.length;
            _updateBadge('online', _pendingCount);

            // Notify dashboard if open
            document.dispatchEvent(new CustomEvent('pos:sync_complete', {
                detail: { synced, failed, pending: _pendingCount }
            }));

        } catch (err) {
            console.error('[OfflineSync] Sync error:', err);
        } finally {
            _syncing = false;
        }
    }

    // ─── Offline Invoice Number Generator ────────────────────────────────────
    async function generateOfflineRef() {
        const location = await PosDB.getMeta('location');
        const loc = location ? JSON.parse(location) : {};
        const code = (loc.location_code || 'STORE').replace(/\s/g,'').toUpperCase().slice(0, 8);

        let counter = parseInt(await PosDB.getMeta('offline_counter') || '0', 10);
        counter++;
        await PosDB.setMeta('offline_counter', String(counter));

        return `OFF-${code}-${String(counter).padStart(6, '0')}`;
    }

    // ─── AJAX Barcode Intercept ──────────────────────────────────────────────
    // Wraps the billing page's barcode AJAX to use local cache when offline
    function patchBarcodeAjax() {
        if (typeof global._originalBarcodeSearch !== 'undefined') return; // already patched

        const origFetch = global.fetch;

        global.fetch = function(input, init) {
            const url = typeof input === 'string' ? input : input.url || '';

            // Only intercept barcode AJAX when offline
            if (!_online && url.includes('get_by_barcode_ajax')) {
                const body = init && init.body ? init.body : '';
                const match = body.match(/barcode=([^&]+)/);
                const barcode = match ? decodeURIComponent(match[1]) : '';

                return PosDB.lookupBarcode(barcode).then(results => {
                    if (results.length === 0) {
                        return _fakeResponse({ success: false, message: 'Not found (offline)' });
                    } else if (results.length === 1) {
                        return _fakeResponse({ success: true, mode: 'single', plu: results[0] });
                    } else {
                        return _fakeResponse({ success: true, mode: 'multiple', plus: results });
                    }
                });
            }

            // Intercept scheme evaluation when offline — return empty
            if (!_online && url.includes('schemes/evaluate_ajax')) {
                return _fakeResponse({ success: true, schemes: {
                    discount_breakup: [], free_items: [], credit_notes: [],
                    schemes_applied: [], total_discount: 0,
                }});
            }

            // Intercept customer phone lookup when offline — search local IDB
            if (!_online && url.includes('get_customer_by_phone_ajax')) {
                const body = init && init.body ? init.body : '';
                const match = body.match(/phone=([^&]+)/);
                const phone = match ? decodeURIComponent(match[1]).replace(/\D/g,'').slice(-10) : '';

                return PosDB.getAll('customers').then(custs => {
                    const found = custs.find(c => {
                        const p = String(c.phone || '').replace(/\D/g,'').slice(-10);
                        return p === phone;
                    });
                    if (found) return _fakeResponse({ success: true, customer: found });
                    return _fakeResponse({ success: false });
                });
            }

            // Intercept offline invoice sync submission
            if (!_online && url.includes('billing/create') && init && init.method === 'POST') {
                // This should not reach here — we intercept at form submit level.
                // But if it does, reject gracefully.
                return _fakeResponse({ success: false, offline: true });
            }

            return origFetch.apply(this, arguments);
        };

        global._originalBarcodeSearch = true;
    }

    function _fakeResponse(data) {
        return Promise.resolve(new Response(JSON.stringify(data), {
            status: 200,
            headers: { 'Content-Type': 'application/json' },
        }));
    }

    // ─── Periodic Polling Loop ────────────────────────────────────────────────
    async function _tick() {
        const isOnline = await _checkConnectivity();

        if (isOnline !== _online) {
            await _onConnectivityChange(isOnline);
        } else if (isOnline) {
            // Already online — refresh delta periodically
            const now = Math.floor(Date.now() / 1000);
            if (now - _lastDelta > DELTA_INTERVAL / 1000) {
                await _refreshDelta();
            }
            // Re-check pending count
            const pending = await PosDB.getPendingInvoices();
            _pendingCount = pending.length;
            if (_pendingCount > 0 && !_syncing) await _runSync();
            _updateBadge('online', _pendingCount);
        } else {
            _updateBadge('offline', _pendingCount);
        }

        const interval = isOnline ? SYNC_INTERVAL_ONLINE : SYNC_INTERVAL_OFFLINE;
        _syncTimer = setTimeout(_tick, interval);
    }

    // ─── Public API ──────────────────────────────────────────────────────────
    const OfflineSync = {

        init: async function() {
            // Init IDB
            await PosDB.open();

            // Restore last sync timestamp
            const ts = await PosDB.getMeta('last_sync_ts');
            _lastDelta = ts ? parseInt(ts, 10) : 0;

            // Check current pending
            const pending = await PosDB.getPendingInvoices();
            _pendingCount = pending.length;

            // Initial connectivity check
            _online = await _checkConnectivity();
            _updateBadge(_online ? 'online' : 'offline', _pendingCount);

            // Patch fetch for offline AJAX interception
            patchBarcodeAjax();

            // Listen to browser online/offline events as quick signals
            window.addEventListener('online',  () => _checkConnectivity().then(_onConnectivityChange));
            window.addEventListener('offline', () => _onConnectivityChange(false));

            // Start tick loop
            _syncTimer = setTimeout(_tick, 3000);

            // If online, kick off initial data download if we have no cached data
            if (_online) {
                const prodCount = await PosDB.count('products');
                if (prodCount === 0) {
                    console.info('[OfflineSync] No cached data. Downloading...');
                    await downloadAllData();
                } else if (!_lastDelta) {
                    await _refreshDelta();
                }
                // Run any pending syncs
                if (_pendingCount > 0) await _runSync();
            }

            console.info('[OfflineSync] Init complete. Online:', _online, 'Pending:', _pendingCount);
        },

        isOnline:  () => _online,
        isPending: () => _pendingCount > 0,
        pendingCount: () => _pendingCount,

        generateOfflineRef,

        // Force a full data refresh (call on settings change)
        forceRefresh: downloadAllData,

        // Force sync now
        syncNow: () => _runSync(),

        // Save an offline invoice to queue
        queueInvoice: async function(invoiceData) {
            invoiceData.sync_status = 'pending';
            invoiceData.retry_count = 0;
            invoiceData.queued_at   = new Date().toISOString();
            await PosDB.saveOfflineInvoice(invoiceData);
            _pendingCount++;
            _updateBadge('offline', _pendingCount);
        },

        // Save an offline customer
        queueCustomer: async function(custData) {
            custData.temp_id   = 'TEMP-' + Date.now();
            custData.synced    = false;
            custData.queued_at = new Date().toISOString();
            await PosDB.put('offline_customers', custData);
            return custData.temp_id;
        },

        // Get pending invoice count
        getPendingCount: async function() {
            const p = await PosDB.getPendingInvoices();
            return p.length;
        },

        // Retry failed items
        retryFailed: async function() {
            const all = await PosDB.getAll('offline_invoices');
            for (const inv of all) {
                if (inv.sync_status === 'failed' && (inv.retry_count || 0) < MAX_RETRY) {
                    inv.sync_status = 'pending';
                    await PosDB.put('offline_invoices', inv);
                }
            }
            await _runSync();
        },

        // Update product/customer arrays in the billing page from IDB
        refreshBillingArrays: async function() {
            const products  = await PosDB.getAll('products');
            const customers = await PosDB.getAll('customers');
            if (typeof global.PRODUCTS !== 'undefined' && products.length) {
                // Merge IDB products into page array (update prices etc.)
                const map = {};
                products.forEach(p => { map[p.id] = p; });
                for (let i = 0; i < global.PRODUCTS.length; i++) {
                    const fresh = map[global.PRODUCTS[i].id];
                    if (fresh) {
                        global.PRODUCTS[i].selling_price = parseFloat(fresh.selling_price);
                        global.PRODUCTS[i].stock_qty     = parseFloat(fresh.stock_qty || 0);
                        global.PRODUCTS[i].mrp           = parseFloat(fresh.mrp || 0);
                    }
                }
            }
            if (typeof global.CUSTOMERS !== 'undefined' && customers.length) {
                const map = {};
                customers.forEach(c => { map[c.id] = c; });
                for (let i = 0; i < global.CUSTOMERS.length; i++) {
                    const fresh = map[global.CUSTOMERS[i].id];
                    if (fresh) {
                        global.CUSTOMERS[i].balance        = parseFloat(fresh.balance || 0);
                        global.CUSTOMERS[i].wallet_balance = parseFloat(fresh.wallet_balance || 0);
                    }
                }
            }
        },
    };

    global.OfflineSync = OfflineSync;

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => OfflineSync.init());
    } else {
        OfflineSync.init();
    }

})(window);
