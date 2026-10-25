/**
 * Shared auto-save / draft-recovery engine for Purchase & GRN entry screens.
 * One generic module driven by a per-view config object — each view supplies
 * thin glue (field-name maps + its own addRow/recalc functions); this module
 * never assumes a specific DOM/ID scheme.
 *
 * Usage (at the bottom of a view, after its own addRow/calcRow/recalcTotals):
 *   EntryDraft.init({ entryType:'purchase', basePath:'purchases', ... });
 */
(function (global) {
    'use strict';

    function EntryDraftEngine(cfg) {
        this.cfg = Object.assign({
            autosaveIntervalMs: 8000,
            localDebounceMs: 400,
            heartbeatIntervalMs: 30000,
            statusPillSelector: '#draftStatusPill',
            poId: null,
        }, cfg);

        this.draftId = null;
        this.dirty = false;
        this.localDebounceTimer = null;
        this.form = document.querySelector(this.cfg.formSelector);
        this.itemsContainer = document.querySelector(this.cfg.itemsContainerSelector);
        this.storageKey = 'draft_snapshot_' + this.cfg.entryType + '_' + (this.cfg.poId || 'x');

        if (!this.form || !this.itemsContainer) return; // view not ready / misconfigured, fail quiet
        this._wireEvents();
        this._wireDuplicateGuard();

        // Explicit "Open" from the drafts list (?resume=<id>) skips the auto-popup
        // and loads that specific draft immediately.
        var resumeId = new URLSearchParams(window.location.search).get('resume');
        if (resumeId) {
            this._resumeFromServer(parseInt(resumeId, 10));
        } else {
            this._checkForRecovery();
        }
        this._startAutosaveLoop();
    }

    EntryDraftEngine.prototype._rows = function () {
        if (this.cfg.getRows) return this.cfg.getRows();
        return Array.prototype.slice.call(this.itemsContainer.children);
    };

    EntryDraftEngine.prototype._fieldVal = function (el, name) {
        var f = el.querySelector('[name="' + name + '"]');
        return f ? f.value : undefined;
    };

    EntryDraftEngine.prototype.snapshot = function () {
        var header = {};
        (this.cfg.headerFieldNames || []).forEach(function (name) {
            var el = this.form.querySelector('[name="' + name + '"]');
            if (el) header[name] = el.value;
        }, this);

        var items = [];
        var map = this.cfg.itemFieldMap || {};
        this._rows().forEach(function (row) {
            var item = {};
            Object.keys(map).forEach(function (canon) {
                var fname = map[canon];
                if (!fname) return;
                var v = this._fieldVal(row, fname);
                if (v !== undefined) item[canon] = v;
            }, this);
            if (item.product_id) items.push(item);
        }, this);

        return { header: header, items: items };
    };

    EntryDraftEngine.prototype._hasContent = function (snap) {
        if (snap.items && snap.items.length > 0) return true;
        var h = snap.header || {};
        var hasSupplier = h.supplier_id && String(h.supplier_id).trim() !== '';
        var hasInvoice = (h.invoice_no && String(h.invoice_no).trim() !== '') ||
                         (h.supplier_invoice_no && String(h.supplier_invoice_no).trim() !== '');
        return !!(hasSupplier || hasInvoice);
    };

    // ── Instant localStorage layer ──────────────────────────────────────────
    EntryDraftEngine.prototype._saveLocal = function () {
        var snap = this.snapshot();
        try {
            localStorage.setItem(this.storageKey, JSON.stringify({
                savedAt: Date.now(),
                draftId: this.draftId,
                data: snap,
            }));
        } catch (e) { /* storage full/unavailable — server autosave still covers us */ }
    };

    EntryDraftEngine.prototype._readLocal = function () {
        try {
            var raw = localStorage.getItem(this.storageKey);
            return raw ? JSON.parse(raw) : null;
        } catch (e) { return null; }
    };

    EntryDraftEngine.prototype._clearLocal = function () {
        try { localStorage.removeItem(this.storageKey); } catch (e) { /* ignore */ }
    };

    // ── Debounced server autosave ───────────────────────────────────────────
    EntryDraftEngine.prototype._serverSave = function () {
        var snap = this.snapshot();
        if (!this._hasContent(snap) && !this.draftId) return; // nothing worth saving yet

        if (snap.items && snap.items.length === 0 && this.draftId) {
            console.warn("GHOST DELETION DETECTED: Auto-saving empty item list for draft ID:", this.draftId);
        }

        this._setStatus('saving');
        var body = {
            draft_id: this.draftId,
            entry_type: this.cfg.entryType,
            po_id: this.cfg.poId,
            header: snap.header,
            items: snap.items,
        };
        var self = this;
        fetch(this.cfg.basePath + '/autosave_ajax', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body),
        })
            .then(function (r) { 
                if (r.status === 401 || r.status === 403) {
                    console.error("GHOST DELETION: Session expired or CSRF token invalid during autosave", r.status);
                }
                return r.json(); 
            })
            .then(function (res) {
                if (res.success) {
                    self.draftId = res.draft_id;
                    self._setStatus('saved', res.last_autosaved_at);
                } else {
                    console.error("GHOST DELETION: Autosave failed on server:", res.error);
                    self._setStatus('error');
                }
            })
            .catch(function (err) { 
                console.error("GHOST DELETION: Fetch error during autosave:", err);
                self._setStatus('offline'); 
            });
    };

    EntryDraftEngine.prototype._startAutosaveLoop = function () {
        var self = this;
        setInterval(function () {
            if (self.dirty) {
                self.dirty = false;
                self._serverSave();
            }
        }, this.cfg.autosaveIntervalMs);

        setInterval(function () {
            if (self.draftId && document.visibilityState === 'visible') {
                fetch(self.cfg.basePath + '/heartbeat_ajax/' + self.draftId, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                }).catch(function () { /* best-effort */ });
            }
        }, this.cfg.heartbeatIntervalMs);
    };

    EntryDraftEngine.prototype._wireEvents = function () {
        var self = this;
        var onChange = function () {
            self.dirty = true;
            clearTimeout(self.localDebounceTimer);
            self.localDebounceTimer = setTimeout(function () { self._saveLocal(); }, self.cfg.localDebounceMs);
        };
        this.form.addEventListener('input', onChange);
        this.form.addEventListener('change', onChange);

        window.addEventListener('online', function () { self._setStatus(self.draftId ? 'saved' : 'idle'); });
        window.addEventListener('offline', function () { self._setStatus('offline'); });
    };

    // ── Duplicate-scan guard ─────────────────────────────────────────────────
    EntryDraftEngine.prototype.findRowByProductId = function (pid) {
        var map = this.cfg.itemFieldMap || {};
        var pidField = map.product_id;
        var match = null;
        this._rows().some(function (row) {
            var v = this._fieldVal(row, pidField);
            if (v !== undefined && String(v) === String(pid)) { match = row; return true; }
            return false;
        }, this);
        return match;
    };

    EntryDraftEngine.prototype._bumpQty = function (row) {
        var map = this.cfg.itemFieldMap || {};
        var qtyField = map.qty || map.received_qty;
        if (!qtyField) return;
        var el = row.querySelector('[name="' + qtyField + '"]');
        if (!el) return;
        el.value = (parseFloat(el.value) || 0) + 1;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        this._toast('Item already in list — quantity increased.');
    };

    EntryDraftEngine.prototype._wireDuplicateGuard = function () {
        if (!this.cfg.enableDuplicateGuard) return;
        var self = this;
        (this.cfg.wrapAddFns || []).forEach(function (fnName) {
            var original = global[fnName];
            if (typeof original !== 'function') return;
            global[fnName] = function () {
                var pid = self.cfg.getProductIdFromArgs ? self.cfg.getProductIdFromArgs(arguments) : null;
                var existing = pid !== null ? self.findRowByProductId(pid) : null;
                if (existing) { self._bumpQty(existing); return; }
                return original.apply(this, arguments);
            };
        });
    };

    // ── Status pill ──────────────────────────────────────────────────────────
    EntryDraftEngine.prototype._setStatus = function (state, ts) {
        var pill = document.querySelector(this.cfg.statusPillSelector);
        if (!pill) return;
        var label = { idle: '', saving: 'Saving…', saved: 'Auto-Saved ✓', error: 'Save Failed', offline: 'Offline — will retry' }[state] || '';
        var color = { saving: '#6b7280', saved: '#16a34a', error: '#dc2626', offline: '#d97706' }[state] || '#6b7280';
        if (state === 'saved' && ts) {
            var t = new Date(ts.replace(' ', 'T'));
            label += ' ' + t.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        pill.textContent = label;
        pill.style.color = color;
    };

    EntryDraftEngine.prototype._toast = function (msg) {
        var el = document.createElement('div');
        el.textContent = msg;
        el.style.cssText = 'position:fixed;bottom:16px;right:16px;background:#111827;color:#fff;' +
            'padding:8px 14px;border-radius:4px;font-size:12px;z-index:99999;box-shadow:0 2px 8px rgba(0,0,0,.2);opacity:0;transition:opacity .2s;';
        document.body.appendChild(el);
        requestAnimationFrame(function () { el.style.opacity = '1'; });
        setTimeout(function () {
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 250);
        }, 2200);
    };

    // ── Recovery flow ────────────────────────────────────────────────────────
    EntryDraftEngine.prototype._checkForRecovery = function () {
        var self = this;
        var local = this._readLocal();
        var qs = this.cfg.poId ? ('?entry_type=' + this.cfg.entryType + '&po_id=' + this.cfg.poId)
                                : ('?entry_type=' + this.cfg.entryType);
        fetch(this.cfg.basePath + '/check_draft_ajax' + qs, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                var serverFound = res && res.found;
                if (serverFound && (!res.item_count || parseInt(res.item_count, 10) === 0) && (!res.invoice_no || !String(res.invoice_no).trim())) {
                    serverFound = false;
                }
                var localHasItems = local && local.data && local.data.items && local.data.items.length > 0;
                if (!serverFound && !localHasItems) return; // nothing to recover

                var serverTs = serverFound ? new Date(res.last_modified_at.replace(' ', 'T')).getTime() : 0;
                var localTs = local ? local.savedAt : 0;
                var preferLocal = local && localTs > serverTs;

                self._showRecoveryModal({
                    itemCount: preferLocal ? local.data.items.length : (serverFound ? res.item_count : 0),
                    when: preferLocal ? new Date(localTs) : (serverFound ? new Date(serverTs) : null),
                    onContinue: function () {
                        if (preferLocal) {
                            self._restoreSnapshot(local.data);
                            self.draftId = serverFound ? res.draft_id : null;
                            self.dirty = true; // push the recovered local edits back to the server promptly
                        } else if (serverFound) {
                            self._resumeFromServer(res.draft_id);
                        }
                    },
                    onDelete: function () {
                        self._clearLocal();
                        if (serverFound) {
                            fetch(self.cfg.basePath + '/delete_draft_ajax/' + res.draft_id, {
                                method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            });
                        }
                    },
                    onStartNew: function () {
                        self._clearLocal();
                    },
                });
            })
            .catch(function () { /* recovery check is best-effort */ });
    };

    EntryDraftEngine.prototype._resumeFromServer = function (draftId) {
        var self = this;
        fetch(this.cfg.basePath + '/resume_draft_ajax/' + draftId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) { self._toast(res.error || 'Could not resume draft.'); return; }
                self.draftId = draftId;
                self._restoreSnapshot({
                    header: self._draftHeaderToFormShape(res.draft),
                    items: res.items,
                });
                self._toast('Draft restored successfully.');
            })
            .catch(function () { self._toast('Could not resume draft (offline?).'); });
    };

    // header_json round-trips the exact client-submitted header dict verbatim
    // (server stores json_encode() of whatever was posted), so the default
    // restores it as-is; a view can override mapDraftHeaderToForm if needed.
    EntryDraftEngine.prototype._draftHeaderToFormShape = function (draftRow) {
        if (this.cfg.mapDraftHeaderToForm) return this.cfg.mapDraftHeaderToForm(draftRow);
        try { return JSON.parse(draftRow.header_json || '{}'); } catch (e) { return {}; }
    };

    EntryDraftEngine.prototype._restoreSnapshot = function (data) {
        var self = this;
        (data.header ? Object.keys(data.header) : []).forEach(function (name) {
            var el = this.form.querySelector('[name="' + name + '"]');
            if (el && data.header[name] !== undefined && data.header[name] !== null) {
                el.value = data.header[name];
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }, this);

        (data.items || []).forEach(function (item) { self.cfg.restoreItem(item); });
        if (this.cfg.recalc) this.cfg.recalc();
    };

    // ── Minimal dependency-free modal ───────────────────────────────────────
    EntryDraftEngine.prototype._showRecoveryModal = function (opts) {
        var overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99998;' +
            'display:flex;align-items:center;justify-content:center;font-family:"Segoe UI",Arial,sans-serif;';

        var whenText = opts.when ? opts.when.toLocaleString() : 'earlier';
        overlay.innerHTML =
            '<div style="background:#fff;border-radius:6px;padding:20px;max-width:380px;width:90%;box-shadow:0 8px 30px rgba(0,0,0,.25);">' +
            '<h3 style="margin:0 0 8px;font-size:15px;color:#111827;">Unfinished entry found</h3>' +
            '<p style="margin:0 0 16px;font-size:12.5px;color:#4b5563;">' +
            'An unsaved entry with ' + opts.itemCount + ' item(s) from ' + whenText + ' was found. Would you like to continue it?</p>' +
            '<div style="display:flex;gap:8px;justify-content:flex-end;">' +
            '<button data-act="delete" style="background:#fee2e2;color:#991b1b;border:1px solid #f87171;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:12px;">Delete</button>' +
            '<button data-act="new" style="background:#f3f4f6;color:#374151;border:1px solid #d1d5db;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:12px;">Start New</button>' +
            '<button data-act="continue" style="background:#0055a5;color:#fff;border:1px solid #004080;padding:6px 12px;border-radius:4px;cursor:pointer;font-size:12px;font-weight:bold;">Continue Draft</button>' +
            '</div></div>';

        document.body.appendChild(overlay);
        overlay.addEventListener('click', function (e) {
            var act = e.target.getAttribute('data-act');
            if (!act) return;
            if (act === 'continue') opts.onContinue();
            else if (act === 'delete') opts.onDelete();
            else if (act === 'new') opts.onStartNew();
            overlay.remove();
        });
    };

    global.EntryDraft = {
        init: function (cfg) { return new EntryDraftEngine(cfg); },
    };
})(window);
