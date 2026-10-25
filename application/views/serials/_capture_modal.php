<?php
defined('BASEPATH') OR exit('No direct script access allowed');
// Reusable serial-number capture modal. Included once per page; any screen can
// drive it with SerialCapture.open({...}). Written to be usable with a barcode
// scanner and no mouse at all — focus returns to the scan box after every
// entry, and Enter is the only key the operator needs.
?>
<div class="modal fade" id="serialCaptureModal" tabindex="-1" role="dialog"
     data-backdrop="static" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title">
          <i class="fas fa-barcode mr-1 text-primary"></i> Serial Number Entry
        </h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <div class="modal-body pb-2">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <div class="font-weight-bold" id="scItemName" style="font-size:1.05rem;"></div>
            <small class="text-muted">Purchase Quantity: <span id="scQty">0</span></small>
          </div>
          <div class="text-right">
            <span class="badge badge-pill" id="scCounter"
                  style="font-size:1rem;padding:8px 14px;">0 / 0</span>
          </div>
        </div>

        <div class="progress mb-2" style="height:6px;">
          <div class="progress-bar" id="scProgress" role="progressbar" style="width:0%"></div>
        </div>

        <div class="form-group mb-2">
          <input type="text" id="scInput" class="form-control form-control-lg"
                 placeholder="Scan or type serial number, then press Enter"
                 autocomplete="off" spellcheck="false"
                 style="font-size:1.15rem;font-weight:600;letter-spacing:.04em;">
          <small class="text-muted">
            The cursor stays here after every scan — keep scanning without touching the mouse.
          </small>
        </div>

        <div id="scAlert" class="alert py-2 px-3 mb-2" style="display:none;font-size:.85rem;"></div>

        <!-- Outbound only: pick from what is actually in stock instead of
             scanning, for the desk cases (returns, transfers) where the units
             are not physically in front of the operator. -->
        <div id="scPickWrap" style="display:none;" class="mb-2">
            <button type="button" class="btn btn-outline-primary btn-sm" id="scPickBtn">
                <i class="fas fa-list mr-1"></i> Show units in stock
            </button>
            <div id="scPickList" class="mt-2" style="display:none;max-height:150px;overflow:auto;
                 border:1px solid #e2e8f0;border-radius:6px;padding:6px;"></div>
        </div>

        <div style="max-height:260px;overflow:auto;border:1px solid #e2e8f0;border-radius:6px;">
          <table class="table table-sm table-hover mb-0" style="font-size:.85rem;">
            <thead class="thead-light" style="position:sticky;top:0;z-index:1;">
              <tr><th style="width:60px;">#</th><th>Serial Number</th><th style="width:70px;"></th></tr>
            </thead>
            <tbody id="scList">
              <tr id="scEmptyRow"><td colspan="3" class="text-center text-muted py-3">
                No serial numbers entered yet.
              </td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer py-2 d-flex justify-content-between">
        <div>
          <button type="button" class="btn btn-outline-danger btn-sm" id="scClearBtn">Clear All</button>
        </div>
        <div>
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary btn-sm" id="scSaveBtn">
            <i class="fas fa-check mr-1"></i> Save Serial Numbers
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
window.SerialCapture = (function () {
    // mode 'inbound'  = purchase/GRN capture: the serial must NOT exist yet.
    // mode 'outbound' = billing/transfer:     the serial must exist, belong to
    //                   this item, be in stock and be at this location.
    var ctx = null;          // { mode, productId, productName, qty, serials[], onSave }
    var checkUrl  = '<?= base_url('serials/check_ajax') ?>';
    var lookupUrl = '<?= base_url('serials/lookup_ajax') ?>';
    var availUrl  = '<?= base_url('serials/available_ajax') ?>';

    function el(id) { return document.getElementById(id); }

    function alertBox(kind, msg) {
        var a = el('scAlert');
        if (!msg) { a.style.display = 'none'; return; }
        a.className = 'alert alert-' + kind + ' py-2 px-3 mb-2';
        a.style.display = 'block';
        a.innerHTML = msg;
    }

    function esc(s) {
        return String(s).replace(/[&<>"]/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
        });
    }

    function render() {
        var n = ctx.serials.length, q = ctx.qty;
        el('scCounter').textContent = n + ' / ' + q;
        el('scCounter').className = 'badge badge-pill ' +
            (n === q ? 'badge-success' : (n > q ? 'badge-danger' : 'badge-warning'));
        var pct = q > 0 ? Math.min(100, Math.round(n / q * 100)) : 0;
        var bar = el('scProgress');
        bar.style.width = pct + '%';
        bar.className = 'progress-bar ' + (n === q ? 'bg-success' : '');

        var tb = el('scList');
        if (!n) {
            tb.innerHTML = '<tr id="scEmptyRow"><td colspan="3" class="text-center text-muted py-3">'
                         + 'No serial numbers entered yet.</td></tr>';
            return;
        }
        var html = '';
        for (var i = n - 1; i >= 0; i--) {   // newest on top, so the operator sees the last scan
            html += '<tr><td class="text-muted">' + (i + 1) + '</td>'
                  + '<td style="font-family:monospace;font-weight:600;">' + esc(ctx.serials[i]) + '</td>'
                  + '<td><button type="button" class="btn btn-sm btn-link text-danger p-0"'
                  + ' onclick="SerialCapture.remove(' + i + ')">remove</button></td></tr>';
        }
        tb.innerHTML = html;

        if (n === q) alertBox('success', '<i class="fas fa-check-circle mr-1"></i> ' + q + ' / ' + q + ' Completed.');
    }

    function focusInput() {
        var i = el('scInput');
        if (i) { i.focus(); i.select(); }
    }

    function add(raw) {
        var sn = String(raw || '').trim().toUpperCase();
        if (!sn) { alertBox('danger', 'Blank serial number is not allowed.'); return; }

        if (ctx.serials.length >= ctx.qty) {
            alertBox('danger', 'Maximum ' + ctx.qty + ' serial numbers allowed for this item.');
            return;
        }
        if (ctx.serials.indexOf(sn) !== -1) {
            alertBox('danger', 'Duplicate Serial Number. This serial number has already been entered.');
            return;
        }

        if (ctx.mode === 'outbound') {
            // Selling: the unit must be proven sellable BEFORE it joins the list,
            // and the operator gets the item/warranty card the spec asks for.
            var fdo = new FormData();
            fdo.append('serial', sn);
            fdo.append('product_id', ctx.productId);
            fdo.append('enforce_location', ctx.enforceLocation ? '1' : '0');
            alertBox('info', 'Checking ' + esc(sn) + '…');
            fetch(lookupUrl, { method: 'POST', body: fdo, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!res || res.ok !== true) {
                        alertBox('danger', esc((res && res.error) || 'Serial number could not be verified.'));
                        focusInput();
                        return;
                    }
                    ctx.serials.push(sn);
                    var s = res.serial || {};
                    var w = s.warranty_days
                        ? '<br><strong>Warranty:</strong> ' + s.warranty_days + ' Days'
                        : '';
                    alertBox('success',
                        '<strong>' + esc(s.product_name || '') + '</strong><br>'
                      + '<strong>Serial:</strong> ' + esc(s.serial_no || sn)
                      + (s.purchase_date ? '<br><strong>Purchased:</strong> ' + esc(s.purchase_date) : '')
                      + (s.location_name ? '<br><strong>Location:</strong> ' + esc(s.location_name) : '')
                      + w + '<br><strong>Status:</strong> ' + esc(s.status || ''));
                    render(); focusInput();
                })
                .catch(function () {
                    alertBox('danger', 'Could not reach the server. Serialized items cannot be sold offline.');
                    focusInput();
                });
            return;
        }

        // Buying: optimistic add keeps scanning fast; the server check runs in
        // parallel and pulls the row back out if the serial already exists.
        ctx.serials.push(sn);
        alertBox('', '');
        render();
        focusInput();

        var fd = new FormData();
        fd.append('serial', sn);
        fetch(checkUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.ok === false) {
                    var i = ctx.serials.indexOf(sn);
                    if (i !== -1) ctx.serials.splice(i, 1);
                    alertBox('danger', esc(res.error || ('Serial Number ' + sn + ' already exists.')));
                    render(); focusInput();
                }
            })
            .catch(function () { /* offline/network: the server revalidates on save anyway */ });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var input = el('scInput');
        if (!input) return;

        input.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            add(this.value);
            this.value = '';
        });

        el('scClearBtn').addEventListener('click', function () {
            if (!ctx.serials.length) return;
            if (!confirm('Remove all ' + ctx.serials.length + ' entered serial numbers?')) return;
            ctx.serials = []; alertBox('', ''); render(); focusInput();
        });

        el('scSaveBtn').addEventListener('click', function () {
            if (ctx.serials.length !== ctx.qty) {
                alertBox('danger', 'Please enter all ' + ctx.qty + ' serial numbers before saving ('
                                 + ctx.serials.length + ' entered).');
                focusInput();
                return;
            }
            if (typeof ctx.onSave === 'function') ctx.onSave(ctx.serials.slice());
            $('#serialCaptureModal').modal('hide');
        });

        el('scPickBtn').addEventListener('click', function () {
            var box = el('scPickList');
            if (box.style.display === 'block') { box.style.display = 'none'; return; }
            box.style.display = 'block';
            box.innerHTML = '<span class="text-muted">Loading…</span>';
            var fd = new FormData();
            fd.append('product_id', ctx.productId);
            fetch(availUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    var rows = (res && res.rows) || [];
                    if (!rows.length) { box.innerHTML = '<span class="text-muted">No units in stock for this item.</span>'; return; }
                    box.innerHTML = rows.map(function (r) {
                        var picked = ctx.serials.indexOf(r.serial_no) !== -1;
                        return '<button type="button" class="btn btn-sm '
                             + (picked ? 'btn-success' : 'btn-outline-secondary')
                             + ' mr-1 mb-1" style="font-family:monospace;font-size:.78rem;"'
                             + ' onclick="SerialCapture.pick(\'' + esc(r.serial_no) + '\')"'
                             + (picked ? ' disabled' : '') + '>' + esc(r.serial_no) + '</button>';
                    }).join('');
                })
                .catch(function () { box.innerHTML = '<span class="text-danger">Could not load the list.</span>'; });
        });

        // Scanners fire fast; grabbing focus on shown avoids a dropped first scan.
        $('#serialCaptureModal').on('shown.bs.modal', focusInput);
    });

    return {
        open: function (opts) {
            ctx = {
                mode:        opts.mode || 'inbound',
                productId:   opts.productId,
                productName: opts.productName || '',
                qty:         parseInt(opts.qty, 10) || 0,
                serials:     (opts.serials || []).slice(),
                // Transfers are raised for a source branch the user may not be
                // standing in, so location enforcement is caller-controlled.
                enforceLocation: opts.enforceLocation !== false,
                onSave:      opts.onSave
            };
            el('scItemName').textContent = ctx.productName;
            el('scQty').textContent      = ctx.qty;
            el('scInput').value          = '';
            el('scPickWrap').style.display = (ctx.mode === 'outbound') ? 'block' : 'none';
            el('scPickList').style.display = 'none';
            el('scPickList').innerHTML     = '';
            alertBox('', '');
            render();
            $('#serialCaptureModal').modal('show');
        },
        // Clicking a unit in the in-stock list behaves exactly like scanning it.
        pick: function (sn) {
            var box = el('scPickList');
            add(sn);
            if (box.style.display === 'block') el('scPickBtn').click(), el('scPickBtn').click();
        },
        remove: function (i) {
            ctx.serials.splice(i, 1);
            alertBox('', '');
            render(); focusInput();
        }
    };
})();
</script>
