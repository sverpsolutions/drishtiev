
<div class="container-fluid mt-3">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-edit text-warning"></i> Edit Invoice: <?= $invoice->invoice_no ?></h4>
    <div>
        <a href="<?= base_url('billing/invoice/'.$invoice->id) ?>" class="btn btn-sm btn-outline-secondary">← Cancel</a>
        <a href="<?= base_url('billing/audit_log/'.$invoice->id) ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-history"></i> Audit Log</a>
    </div>
</div>

<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i> <b>Caution:</b> Editing this invoice will reverse old stock and apply new stock. All changes are logged in the audit trail.
</div>

<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<form method="post" action="<?= base_url('billing/edit/'.$invoice->id) ?>" id="editBillForm">
<input type="hidden" name="delete_pin" id="deletePin" value="">

<!-- Customer / Invoice Info -->
<div class="card shadow-sm mb-3">
<div class="card-header bg-warning text-dark py-2"><b>Invoice Information (Read-only fields)</b></div>
<div class="card-body">
<div class="form-row">
    <div class="col-md-2">
        <label>Invoice No</label>
        <input type="text" class="form-control form-control-sm bg-light" value="<?= $invoice->invoice_no ?>" readonly>
    </div>
    <div class="col-md-3">
        <label>Customer</label>
        <input type="text" class="form-control form-control-sm bg-light" value="<?= htmlspecialchars($invoice->customer_name ?? '') ?>" readonly>
    </div>
    <div class="col-md-2">
        <label>Invoice Date</label>
        <input type="text" class="form-control form-control-sm bg-light" value="<?= date('d-M-Y', strtotime($invoice->invoice_date)) ?>" readonly>
    </div>
    <div class="col-md-1">
        <label>Old Total</label>
        <input type="text" class="form-control form-control-sm bg-light font-weight-bold text-danger" value="₹<?= number_format($invoice->total_amount,2) ?>" readonly style="font-size: 10px; padding: 4px;">
    </div>
    <div class="col-md-2">
        <label>Invoice Discount (₹)</label>
        <input type="number" name="invoice_discount" class="form-control form-control-sm" value="<?= $invoice->invoice_discount ?? 0 ?>" min="0" step="0.01" onchange="calcFooter()">
    </div>
    <div class="col-md-2">
        <div class="custom-control custom-checkbox" style="margin-top: 26px;">
            <input type="checkbox" name="is_delivery" id="is_delivery" class="custom-control-input" value="1" <?= $invoice->is_delivery ? 'checked' : '' ?>>
            <label class="custom-control-label font-weight-bold text-dark small" for="is_delivery">🚚 Delivery</label>
        </div>
    </div>
</div>
</div>
</div>

<!-- Items -->
<div class="card shadow-sm mb-3">
<div class="card-header bg-warning text-dark py-2 d-flex justify-content-between">
    <b>Line Items</b>
    <small>Edit quantities/rates. Stock will be recalculated automatically.</small>
</div>
<div class="card-body p-2">

<!-- Smart Search -->
<div class="form-row mb-2">
    <div class="col-md-5">
        <div class="input-group input-group-sm">
            <input type="text" id="smartSearch" class="form-control" placeholder="🔍 Search and add item..." autocomplete="off">
            <div class="input-group-append">
                <button type="button" class="btn btn-warning" onclick="triggerSearch()">Add</button>
            </div>
        </div>
        <div id="searchDropdown" class="list-group position-absolute w-50" style="z-index:9999;max-height:220px;overflow-y:auto;display:none;"></div>
    </div>
</div>

<div class="table-responsive">
<table class="table table-bordered table-sm" id="itemsTable">
<thead class="thead-light">
<tr>
    <th>#</th><th>Product</th><th>Qty</th><th>Rate</th><th>Disc%</th>
    <?php if (!empty($settings['enable_gst'])): ?>
    <th>GST%</th>
    <?php endif; ?>
    <th class="text-right">Total</th><th>Del</th>
</tr>
</thead>
<tbody id="itemsBody"></tbody>
<tfoot>
<tr><td colspan="<?= !empty($settings['enable_gst']) ? 6 : 5 ?>" class="text-right"><b>Subtotal:</b></td><td class="text-right" id="footSubtotal">0.00</td><td></td></tr>
<tr class="table-danger"><td colspan="<?= !empty($settings['enable_gst']) ? 6 : 5 ?>" class="text-right h5"><b>New Total:</b></td><td class="text-right h5 font-weight-bold" id="footTotal">₹0.00</td><td></td></tr>
</tfoot>
</table>
</div>
<button type="button" class="btn btn-sm btn-outline-warning" onclick="addRow()"><i class="fas fa-plus"></i> Add Row</button>
</div>
</div>

<!-- Payment -->
<div class="card shadow-sm mb-3">
<div class="card-header bg-warning text-dark py-2 d-flex justify-content-between">
    <b>Payment</b>
    <small>Split across multiple modes if needed, e.g. part Cash + part UPI.</small>
</div>
<div class="card-body p-2">
<table class="table table-sm mb-1" id="payTable">
    <thead class="thead-light"><tr><th>Mode</th><th>Amount (₹)</th><th></th></tr></thead>
    <tbody id="payBody"></tbody>
</table>
<button type="button" class="btn btn-sm btn-outline-warning mb-2" onclick="addPayRow()"><i class="fas fa-plus"></i> Add Payment Row</button>
<div class="d-flex justify-content-between align-items-center p-2 rounded" style="background:#fff8e1;font-size:.84rem;">
    <span class="font-weight-bold">Total Paid:</span>
    <span class="font-weight-bold" id="payTotal">₹0.00</span>
</div>
<div class="d-flex justify-content-between align-items-center p-2 rounded mt-1" style="background:#ffebee;font-size:.84rem;">
    <span class="font-weight-bold">Remaining (Due/Credit):</span>
    <span class="font-weight-bold text-danger" id="payRemain">₹0.00</span>
</div>
<div class="mt-2">
    <label class="small font-weight-bold mb-1">Payment Mode Change Reason <small class="text-muted font-weight-normal">(only required if you change the payment mode above)</small></label>
    <input type="text" name="pm_change_reason" class="form-control form-control-sm" placeholder="e.g. Customer paid by UPI instead of Cash">
</div>
</div>
</div>

<!-- Edit Reason + Submit -->
<div class="card shadow-sm mb-3">
<div class="card-body">
<div class="form-row">
    <div class="col-md-6">
        <label>Notes</label>
        <textarea name="notes" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($invoice->notes ?? '') ?></textarea>
    </div>
    <div class="col-md-4">
        <label>Reason for Edit <span class="text-danger">*</span></label>
        <input type="text" name="edit_reason" class="form-control form-control-sm" required placeholder="Required: e.g. Customer returned 2 items, Rate correction...">
    </div>
    <div class="col-md-2 text-right pt-4">
        <button type="submit" class="btn btn-warning" onclick="return confirm('Confirm invoice edit? Stock will be reversed and re-applied.')">
            <i class="fas fa-save"></i> Save Changes
        </button>
    </div>
</div>
</div>
</div>

</form>
</div>

<script>
var rowCount = 0;
var invDisc  = 0;
var enableGst = <?= !empty($settings['enable_gst']) ? 'true' : 'false' ?>;

// Pre-fill existing items
var existingItems = <?= json_encode(array_map(function($i){ return [
    'id'           => $i->product_id,
    'product_name' => $i->product_name ?? ($i->description ?? ''),
    'rate'         => $i->rate,
    'unit'         => $i->unit,
    'gst_percent'  => $i->gst_percent,
    'qty'          => $i->qty,
    'discount'     => $i->discount,
]; }, $items)) ?>;

// Pre-fill existing payment(s) — split rows if this was already a multi-payment
// invoice, otherwise a single row from the invoice's current mode/paid_amount.
var existingPaySplits = <?= json_encode(!empty($pay_splits) ? array_map(function($s){
    return ['mode' => $s->payment_mode, 'amount' => (float)$s->amount];
}, $pay_splits) : (($invoice->paid_amount > 0) ? [[
    'mode' => ($invoice->payment_mode === 'split' ? 'cash' : $invoice->payment_mode),
    'amount' => (float)$invoice->paid_amount,
]] : [])) ?>;
var payRowCount = 0;
var _editPayModes = <?= json_encode(array_values(array_map(function($m) { return ['code' => $m->code, 'name' => $m->name]; }, isset($payment_modes) ? $payment_modes : []))) ?>;

function addRow(p, existDisc) {
    rowCount++;
    var i = rowCount;
    p = p || {}; existDisc = existDisc !== undefined ? existDisc : 0;
    var row = `<tr id="row_${i}">
        <td>${i}</td>
        <td>
            <input type="hidden" name="product_id[]" id="pid_${i}" value="${p.id||''}">
            <input type="text" name="description[]" id="desc_${i}" class="form-control form-control-sm" value="${p.product_name||''}" required>
        </td>
        <td><input type="number" name="qty[]" id="qty_${i}" class="form-control form-control-sm" value="${p.qty||1}" min="0.001" step="0.001" onchange="calcRow(${i})"></td>
        <td><input type="number" name="rate[]" id="rate_${i}" class="form-control form-control-sm" value="${p.rate||0}" step="0.01" onchange="calcRow(${i})"></td>
        <td><input type="number" name="item_discount[]" class="form-control form-control-sm disc-pct" value="${existDisc}" min="0" max="100" step="0.01" onchange="calcRow(${i})"></td>
        ${enableGst ? `
        <td><input type="number" name="gst_percent[]" class="form-control form-control-sm gst-pct" value="${p.gst_percent||0}" min="0" max="40" step="0.01" onchange="calcRow(${i})"></td>
        ` : `
        <td style="display:none;"><input type="hidden" name="gst_percent[]" class="gst-pct" value="0"></td>
        `}
        <td class="text-right"><input type="text" id="total_${i}" name="line_total[]" class="form-control form-control-sm bg-light font-weight-bold" readonly value="0.00"></td>
        <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow(${i})"><i class="fas fa-trash"></i></button></td>
    </tr>`;
    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', row);
    calcRow(i);
}

// Removing a billed item (or lowering its qty) needs the PIN — the server checks
// it on save; here we only collect it so the row can be taken off the screen.
var originalQty = {};
existingItems.forEach(function (p) { if (p.id) originalQty[p.id] = (originalQty[p.id] || 0) + (parseFloat(p.qty) || 0); });
function askDeletePin(msg) {
    if (document.getElementById('deletePin').value) return true;
    var pin = prompt(msg + ' Enter PIN:');
    if (pin === null || pin.trim() === '') return false;
    document.getElementById('deletePin').value = pin.trim();
    return true;
}
function removeRow(i) {
    var pid = document.getElementById('pid_' + i)?.value;
    if (pid && originalQty[pid] && !askDeletePin('Deleting a billed item needs the PIN.')) return;
    document.getElementById('row_'+i)?.remove(); calcFooter();
}
document.getElementById('editBillForm').addEventListener('submit', function (e) {
    var now = {};
    document.querySelectorAll('#itemsBody input[name="product_id[]"]').forEach(function (el) {
        var i = el.id.replace('pid_', '');
        if (el.value) now[el.value] = (now[el.value] || 0) + (parseFloat(document.getElementById('qty_' + i)?.value) || 0);
    });
    var cut = Object.keys(originalQty).some(function (pid) { return (now[pid] || 0) < originalQty[pid] - 0.0001; });
    if (cut && !askDeletePin('Items were removed or their quantity reduced — this needs the PIN.')) { e.preventDefault(); }
});

function calcRow(i) {
    var qty  = parseFloat(document.getElementById('qty_'+i)?.value)||0;
    var rate = parseFloat(document.getElementById('rate_'+i)?.value)||0;
    var disc = parseFloat(document.querySelector('#row_'+i+' .disc-pct')?.value)||0;
    // Rate is GST-inclusive: (gross - discAmt) IS the final amount charged.
    var gross=qty*rate, discAmt=gross*(disc/100), total=gross-discAmt;
    document.getElementById('total_'+i).value=total.toFixed(2);
    calcFooter();
}

function calcFooter() {
    var sum = 0;
    document.querySelectorAll('[id^="total_"]').forEach(t=>sum+=parseFloat(t.value)||0);
    invDisc = parseFloat(document.querySelector('[name="invoice_discount"]')?.value)||0;
    var net = sum - invDisc;
    document.getElementById('footSubtotal').textContent = sum.toFixed(2);
    document.getElementById('footTotal').textContent = '₹'+net.toFixed(2);
    calcPayFooter();
}

// ── Payment rows (single mode = one row; split payment = 2+ rows) ──
function addPayRow(mode, amount) {
    payRowCount++;
    var i = payRowCount;
    mode = mode || 'cash';
    amount = amount !== undefined ? amount : '';
    var opts = '';
    if (_editPayModes && _editPayModes.length) {
        _editPayModes.forEach(function(m) { opts += '<option value="'+m.code+'"'+(mode===m.code?' selected':'')+'>'+m.name+'</option>'; });
    } else {
        ['cash','upi','neft','cheque','card','credit'].forEach(function(c) { opts += '<option value="'+c+'"'+(mode===c?' selected':'')+'>'+c.charAt(0).toUpperCase()+c.slice(1)+'</option>'; });
    }
    var row = `<tr id="payrow_${i}">
        <td>
            <select name="pay_mode[]" id="paymode_${i}" class="form-control form-control-sm" onchange="calcPayFooter()">
                ${opts}
            </select>
        </td>
        <td><input type="number" name="pay_amount[]" id="payamt_${i}" class="form-control form-control-sm" value="${amount}" min="0" step="0.01" oninput="calcPayFooter()"></td>
        <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removePayRow(${i})"><i class="fas fa-trash"></i></button></td>
    </tr>`;
    document.getElementById('payBody').insertAdjacentHTML('beforeend', row);
    calcPayFooter();
}

function removePayRow(i) { document.getElementById('payrow_'+i)?.remove(); calcPayFooter(); }

function calcPayFooter() {
    var paid = 0;
    document.querySelectorAll('#payBody tr').forEach(function(row) {
        var modeSel = row.querySelector('select[name="pay_mode[]"]');
        var amtInp  = row.querySelector('input[name="pay_amount[]"]');
        if (!modeSel || !amtInp) return;
        if (modeSel.value !== 'credit') paid += parseFloat(amtInp.value) || 0;
    });
    var net = parseFloat((document.getElementById('footTotal').textContent || '').replace('₹','')) || 0;
    document.getElementById('payTotal').textContent = '₹' + paid.toFixed(2);
    var remain = net - paid;
    var remainEl = document.getElementById('payRemain');
    remainEl.textContent = '₹' + Math.abs(remain).toFixed(2) + (remain > 0 ? ' Due' : remain < 0 ? ' Excess' : '');
    remainEl.className = 'font-weight-bold ' + (remain > 0 ? 'text-danger' : 'text-success');
}

// Smart search — same product search as billing (products/search_ajax: name words
// in any order, item code, barcode). It used to call billing/get_product_ajax,
// which only answers a product ID, so every search came back empty.
var _searchTimer = null, _searchSeq = 0;
function searchProducts(q, cb) {
    var seq = ++_searchSeq;
    fetch('<?= base_url("products/search_ajax") ?>?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (seq === _searchSeq) cb((d && d.products) ? d.products : []); })
        .catch(function () { if (seq === _searchSeq) cb([]); });
}
function pickProduct(p) {
    addRow({ id: p.id, product_name: p.name, rate: parseFloat(p.selling_price) || 0, unit: p.unit, gst_percent: parseFloat(p.gst_percent) || 0 });
    var box = document.getElementById('smartSearch');
    box.value = ''; document.getElementById('searchDropdown').style.display = 'none'; box.focus();
}
function exactMatch(items, q) {
    q = q.toLowerCase();
    return items.find(function (p) { return (p.barcode && p.barcode.toLowerCase() === q) || (p.item_code && String(p.item_code).toLowerCase() === q); });
}
document.getElementById('smartSearch').addEventListener('input', function(){
    var q = this.value.trim();
    clearTimeout(_searchTimer);
    if (q.length < 1) { document.getElementById('searchDropdown').style.display = 'none'; return; }
    _searchTimer = setTimeout(function () { searchProducts(q, showDropdown); }, 250);
});
document.getElementById('smartSearch').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); triggerSearch(); }   // don't submit the edit form
});
function triggerSearch() {
    var q = document.getElementById('smartSearch').value.trim();
    if (!q) return;
    clearTimeout(_searchTimer);
    searchProducts(q, function (items) {
        var hit = exactMatch(items, q) || (items.length === 1 ? items[0] : null);   // scanned barcode / item code
        if (hit) pickProduct(hit); else showDropdown(items);
    });
}
function showDropdown(items){
    var dd = document.getElementById('searchDropdown'); dd.innerHTML = '';
    if (!items.length) {
        dd.innerHTML = '<div class="list-group-item py-1 px-2 small text-muted">No product found</div>';
        dd.style.display = 'block'; return;
    }
    items.slice(0, 30).forEach(function (p) {
        var a = document.createElement('a'); a.href = '#'; a.className = 'list-group-item list-group-item-action py-1 px-2 small';
        var name = document.createElement('strong'); name.textContent = p.name;
        var meta = document.createElement('span'); meta.className = 'text-muted ml-1';
        meta.textContent = '[' + (p.item_code || '') + '] ₹' + (parseFloat(p.selling_price) || 0).toFixed(2) + ' · Stock ' + (parseFloat(p.stock_qty) || 0);
        a.appendChild(name); a.appendChild(meta);
        a.onclick = function (e) { e.preventDefault(); pickProduct(p); };
        dd.appendChild(a);
    });
    dd.style.display = 'block';
}
document.addEventListener('click',function(e){if(!e.target.closest('#smartSearch')&&!e.target.closest('#searchDropdown'))document.getElementById('searchDropdown').style.display='none';});

window.onload = function() {
    existingItems.forEach(p => addRow(p, p.discount||0));
    if (existingPaySplits.length) {
        existingPaySplits.forEach(s => addPayRow(s.mode, s.amount));
    } else {
        addPayRow('cash', '');
    }
};
</script>

