<?php
// ============================================================
// FILE: application/views/billing/barcode_billing.php
// Fast billing UI with PLU-aware barcode scanner support
// ============================================================
?>

<!-- PLU Selection Modal (keyboard-navigable) -->
<div class="modal fade" id="pluSelectModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2">
                <div>
                    <h6 class="modal-title font-weight-bold mb-0">
                        <i class="fas fa-tags text-warning mr-1"></i>
                        Select Price — <span id="pluModalTitle"></span>
                    </h6>
                    <small class="text-muted">Barcode: <code id="pluModalBarcode" class="text-warning"></code></small>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-0">
                <div class="p-2 bg-light border-bottom" style="font-size:.75rem;">
                    <i class="fas fa-keyboard text-muted mr-1"></i>
                    Use <kbd>&uarr;</kbd> <kbd>&darr;</kbd> to navigate, <kbd>Enter</kbd> to select, <kbd>Esc</kbd> to cancel
                </div>
                <div id="pluModalList" tabindex="0" style="max-height:350px;overflow-y:auto;outline:none;"></div>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel (Esc)</button>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0">Fast Billing — Barcode Mode</h5>
        <small class="text-muted">Scan barcodes or type product codes to add items instantly</small>
    </div>
    <div>
        <a href="<?= base_url('billing/create') ?>" class="btn btn-outline-secondary btn-sm">Manual Mode</a>
        <a href="<?= base_url('billing') ?>" class="btn btn-outline-secondary btn-sm ml-1">&larr; Back</a>
    </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<?= form_open('billing/create', ['id' => 'invoiceForm']) ?>
<input type="hidden" name="submit_token" value="<?= $submit_token ?? '' ?>">

<div class="row">
    <!-- LEFT: Scanner + Items -->
    <div class="col-md-8">

        <!-- Customer + Date row -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-5">
                        <label class="mb-0 mr-2" style="font-size:.82rem;font-weight:600;">Customer</label>
                        <select name="customer_id" id="customer_id" class="form-control form-control-sm d-inline-block"
                                style="width:auto;min-width:160px;" onchange="loadCustomer(this.value)" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($customers as $cid => $cname): ?>
                            <option value="<?= $cid ?>"><?= htmlspecialchars($cname) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="custom-control custom-checkbox d-inline-block ml-3">
                            <input type="checkbox" name="is_delivery" id="is_delivery" class="custom-control-input" value="1">
                            <label class="custom-control-label font-weight-bold text-dark small" for="is_delivery">🚚 Delivery</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="mb-0 mr-1" style="font-size:.82rem;font-weight:600;">Date</label>
                        <input type="date" name="invoice_date" class="form-control form-control-sm d-inline-block"
                               style="width:auto;" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <select name="invoice_type" id="invoice_type" class="form-control form-control-sm" onchange="loadCustomer(document.getElementById('customer_id').value)">
                            <option value="retail">Retail</option>
                            <option value="wholesale">Wholesale</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="payment_mode" class="form-control form-control-sm">
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="credit">Credit</option>
                            <option value="neft">NEFT</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- BARCODE SCANNER INPUT -->
        <div class="card border-0 shadow-sm mb-3" style="border:2px solid #28a745 !important;">
            <div class="card-body py-2">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-success text-white">
                            &#128247; Scan / Type
                        </span>
                    </div>
                    <input type="text"
                           id="barcode_input"
                           class="form-control form-control-lg"
                           placeholder="Scan barcode or type product name / code..."
                           autocomplete="off"
                           autofocus>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-success" onclick="lookupProduct()">Add</button>
                    </div>
                </div>
                <small class="text-muted mt-1 d-block">
                    &#128161; Barcode scanner acts as keyboard — just scan. Press Enter or click Add.
                    Partial product name search also works.
                </small>
            </div>
        </div>

        <!-- Live search dropdown result -->
        <div id="search_results" class="list-group mb-3" style="display:none;"></div>

        <!-- Items table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 d-flex justify-content-between font-weight-bold" style="font-size:.85rem;">
                <span>Items <span id="item_count" class="badge badge-dark ml-1">0</span></span>
                <button type="button" class="btn btn-sm btn-outline-danger py-0" onclick="clearAll()">Clear All</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0" id="itemsTable" style="font-size:.84rem;">
                        <thead class="thead-dark">
                            <tr>
                                <th style="width:25%">Product</th>
                                <th style="width:8%">Stock</th>
                                <th style="width:10%">Qty</th>
                                <th style="width:10%">MRP</th>
                                <th style="width:10%">Rate</th>
                                <?php if (!empty($settings['enable_gst'])): ?>
                                <th style="width:8%">GST%</th>
                                <?php endif; ?>
                                <th style="width:<?= !empty($settings['enable_gst']) ? '12%' : '20%' ?>">Total</th>
                                <th style="width:5%"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr id="empty_row">
                                <td colspan="<?= !empty($settings['enable_gst']) ? 8 : 7 ?>" class="text-center text-muted py-3" style="font-size:.82rem;">
                                    Scan or search a product above to begin
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- RIGHT: Totals + Checkout -->
    <div class="col-md-4">

        <!-- Customer info -->
        <div id="customer_panel" class="card border-0 shadow-sm mb-3" style="display:none;">
            <div class="card-body py-2" id="cust_info_body" style="font-size:.83rem;"></div>
        </div>

        <!-- Totals -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-dark text-white py-2 font-weight-bold" style="font-size:.85rem;">Bill Summary</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.87rem;">
                    <tr><td class="pl-3">Items</td><td class="text-right pr-3" id="s_items">0</td></tr>
                    <tr><td class="pl-3">Subtotal</td><td class="text-right pr-3" id="s_sub">&#8377; 0.00</td></tr>
                    <?php if (!empty($settings['enable_gst'])): ?>
                    <tr><td class="pl-3">Total GST</td><td class="text-right pr-3" id="s_gst">&#8377; 0.00</td></tr>
                    <?php endif; ?>
                    <tr class="font-weight-bold bg-light">
                        <td class="pl-3" style="font-size:1rem;">Total</td>
                        <td class="text-right pr-3" id="s_total" style="font-size:1.1rem;">&#8377; 0.00</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="form-group mb-2">
                    <label style="font-size:.83rem;font-weight:600;">Amount Received (&#8377;)</label>
                    <input type="number" step="0.01" name="paid_amount" id="paid_amount"
                           class="form-control" value="0" min="0" oninput="calcChange()">
                </div>
                <div class="d-flex justify-content-between" style="font-size:.85rem;">
                    <span>Change to return:</span>
                    <span id="change_due" class="font-weight-bold text-success">&#8377; 0.00</span>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-dark btn-block btn-lg mb-2" id="submit_btn" disabled onclick="if(this._clicked){event.preventDefault();return;}this._clicked=true;this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Saving…';this.disabled=true;">
            Generate Invoice
        </button>
        <button type="button" class="btn btn-outline-dark btn-block btn-sm" onclick="quickPrint()">
            &#128438; Generate &amp; Print
        </button>

    </div>
</div>

<?= form_close() ?>

<!-- Hidden inputs for notes -->
<input type="hidden" name="notes" value="Fast billing — barcode mode">

<script>
const PRODUCTS = <?= json_encode(array_map(function($p) {
    return [
        'id'            => $p->id,
        'name'          => $p->name,
        'brand'         => $p->brand ?? '',
        'selling_price' => $p->selling_price,
        'mrp'           => $p->mrp,
        'gst_percent'   => $p->gst_percent,
        'unit'          => $p->unit,
        'stock_qty'     => $p->stock_qty,
        'hsn_code'      => $p->hsn_code ?? '',
        'description'   => $p->description ?? '',
        'image1'        => $p->image1 ?? '',
    ];
}, $products)) ?>;

let addedProducts = {};  // product_id => {row, qty, ...}
let rowCount = 0;
let isInterstate = false;
const COMPANY_STATE = <?= json_encode(strtolower(trim($company['company_state'] ?? 'Punjab'))) ?>;
const enableGst = <?= !empty($settings['enable_gst']) ? 'true' : 'false' ?>;
const isCashier = <?= ($this->session->userdata('role') === 'cashier') ? 'true' : 'false' ?>;

// -------------------------------------------------------
// BARCODE / SEARCH lookup
// -------------------------------------------------------
const barcodeInput = document.getElementById('barcode_input');

barcodeInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); lookupProduct(); }
});

// Billing-style product match: every typed word must appear (any order) — "maggi noodle" finds "MAGGI AATA NOODLE"
window.pmatch = window.pmatch || function (q) { var w = String(q || '').toLowerCase().split(/\s+/).filter(Boolean); var t = Array.prototype.slice.call(arguments, 1).map(function (x) { return String(x == null ? '' : x).toLowerCase(); }).join(' | '); return w.every(function (x) { return t.indexOf(x) !== -1; }); };
barcodeInput.addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    if (q.length < 2) { hideResults(); return; }
    const matches = PRODUCTS.filter(p =>
        pmatch(q, p.name, p.brand)
    ).slice(0, 8);
    showResults(matches);
});

function showResults(matches) {
    const el = document.getElementById('search_results');
    if (!matches.length) { hideResults(); return; }
    el.innerHTML = matches.map(p => {
        const imgUrl = p.image1 ? '<?= base_url("assets/uploads/products/") ?>' + p.image1 : '';
        const imgHtml = imgUrl ? `<img src="${imgUrl}" style="width:28px; height:28px; object-fit:cover; border-radius:4px; margin-right:8px; border:1px solid #ddd;">` : `<div style="width:28px; height:28px; border-radius:4px; margin-right:8px; border:1px dashed #ccc; background:#f8f9fa; display:inline-block;"></div>`;
        return `
        <button type="button" class="list-group-item list-group-item-action py-1 px-2 d-flex align-items-center justify-content-between"
                onclick="addProductById(${p.id})" style="font-size:.84rem;">
            <div class="d-flex align-items-center">
                ${imgHtml}
                <div>
                    <strong>${p.name}</strong>
                    <span class="text-muted ml-2">${p.brand}</span>
                </div>
            </div>
            <span class="float-right">
                &#8377; ${parseFloat(p.selling_price).toFixed(2)}
                <span class="badge badge-${p.stock_qty > 10 ? 'success' : p.stock_qty > 0 ? 'warning' : 'danger'} ml-1">
                    ${p.stock_qty} ${p.unit}
                </span>
            </span>
        </button>`;
    }).join('');
    el.style.display = 'block';
}

function hideResults() {
    document.getElementById('search_results').style.display = 'none';
}

function lookupProduct() {
    const q = barcodeInput.value.trim();
    if (!q) return;

    fetch('<?= base_url("billing/get_by_barcode_ajax") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'barcode=' + encodeURIComponent(q) + '&<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>'
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            // fallback partial search if barcode not found in PLU or legacy
            const exact = PRODUCTS.find(p => p.id == q || p.name.toLowerCase() === q.toLowerCase());
            if (exact) { addProduct(patchLocalProduct(exact)); return; }
            const matches = PRODUCTS.filter(p => pmatch(q, p.name));
            if (matches.length === 1) { addProduct(patchLocalProduct(matches[0])); return; }
            showResults(matches);
            return;
        }

        try { new Audio('<?= base_url("assets/scan.mp3") ?>').play(); } catch(e) {}

        if (res.mode === 'single') {
            // Single PLU — auto-add
            addPluProduct(res.plu);
        } else if (res.mode === 'multiple') {
            // Multiple PLUs — show selection popup
            openPluSelectModal(res.item_name, res.barcode, res.plus);
        } else if (res.mode === 'legacy') {
            // Old product (not yet in PLU system)
            addProduct(res.product);
        }
    }).catch(e => console.error(e));
}

// Add a PLU-sourced product to the bill
function addPluProduct(plu) {
    hideResults();
    barcodeInput.value = '';
    barcodeInput.focus();

    const uniqueId = 'plu_' + plu.id;

    if (addedProducts[uniqueId]) {
        const row = addedProducts[uniqueId];
        const qtyInput = document.getElementById('qty_' + row.rowId);
        const newQty = parseInt(qtyInput.value) + 1;
        qtyInput.value = newQty;
        calcRow(row.rowId);
        const tr = document.getElementById('row_' + row.rowId);
        if (tr) { tr.style.background = '#fffacd'; setTimeout(() => tr.style.background = '', 600); }
        recalcTotals();
        return;
    }

    rowCount++;
    const rid = rowCount;
    const displayRate = plu.selling_rate || plu.mrp || 0;
    const gstVal = plu.gst_percent || 0;
    const stockBadge = plu.stock_qty <= 0
        ? `<span class="badge badge-danger">${plu.stock_qty} ${plu.unit || 'PCS'}</span>`
        : `<span class="badge badge-success">${plu.stock_qty} ${plu.unit || 'PCS'}</span>`;

    const gstOptions = [0,3,5,12,18,40].map(g =>
        `<option value="${g}" ${g == gstVal ? 'selected' : ''}>${g}%</option>`
    ).join('');

    const row = `<tr id="row_${rid}">
        <input type="hidden" name="product_id[]" value="${plu.product_id}">
        <input type="hidden" name="plu_id[]" value="${plu.id}">
        <input type="hidden" name="batch_no[]" value="${plu.batch_no || ''}">
        <input type="hidden" name="unit[]" value="${plu.unit || 'PCS'}">
        <input type="hidden" name="carton_size[]" value="1">
        <input type="hidden" name="scan_unit[]" value="PCS">
        <td style="font-size:.82rem;">
            <strong>${plu.name}</strong><br>
            <small class="text-muted">${plu.item_code || ''}</small>
            <span class="badge badge-info ml-1" style="font-size:.65rem;">${plu.plu_code || ''}</span>
            ${plu.batch_no ? `<span class="badge badge-secondary ml-1" style="font-size:.6rem;">Batch: ${plu.batch_no}</span>` : ''}
        </td>
        <td>${stockBadge}</td>
        <td>
            <div class="input-group input-group-sm" style="width:100px;">
                <input type="number" name="qty[]" id="qty_${rid}" class="form-control text-center px-1"
                       value="1" min="1" oninput="calcRow(${rid})">
                <div class="input-group-append"><span class="input-group-text px-1" style="font-size:10px;">PCS</span></div>
            </div>
        </td>
        <td class="align-middle text-muted text-center" style="font-size:12px;">&#8377; ${parseFloat(plu.mrp || 0).toFixed(2)}</td>
        <td>
            <input type="number" step="0.01" name="rate[]" id="rate_${rid}"
                   class="form-control form-control-sm"
                   value="${parseFloat(displayRate).toFixed(2)}"
                   oninput="calcRow(${rid})" style="width:80px;"
                   ${isCashier ? 'readonly' : ''}>
        </td>
        ${enableGst ? `<td>
            <select name="gst_percent[]" id="gst_${rid}" class="form-control form-control-sm" onchange="calcRow(${rid})">
                ${gstOptions}
            </select>
        </td>` : `<td style="display:none;"><input type="hidden" name="gst_percent[]" id="gst_${rid}" value="0"></td>`}
        <td id="total_${rid}" class="font-weight-bold align-middle">&#8377; 0.00</td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1"
                    onclick="removeRow(${rid}, '${uniqueId}')">&#10005;</button>
        </td>
    </tr>`;

    const emptyRow = document.getElementById('empty_row');
    if (emptyRow) emptyRow.remove();
    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', row);
    document.getElementById('itemsBody').lastElementChild.insertAdjacentHTML('beforeend', '<input type="hidden" name="item_discount[]" value="0">');
    addedProducts[uniqueId] = { rowId: rid };
    calcRow(rid);
    updateSubmitBtn();
}

// PLU Selection Modal (keyboard-navigable)
let pluModalItems = [];
let pluModalIdx = 0;

function openPluSelectModal(itemName, barcode, plus) {
    pluModalItems = plus;
    pluModalIdx   = 0;
    document.getElementById('pluModalTitle').textContent = itemName;
    document.getElementById('pluModalBarcode').textContent = barcode;
    renderPluModalList();
    $('#pluSelectModal').modal('show');
    setTimeout(() => document.getElementById('pluModalList').focus(), 300);
}

function renderPluModalList() {
    const el = document.getElementById('pluModalList');
    el.innerHTML = pluModalItems.map((p, i) => {
        const expStr = p.expiry_date ? ` | Exp: ${p.expiry_date}` : '';
        const batchStr = p.batch_no ? ` | Batch: ${p.batch_no}` : '';
        const stockClass = p.stock_qty <= 0 ? 'text-danger' : (p.stock_qty <= 5 ? 'text-warning' : 'text-success');
        return `<div class="plu-option p-2 border-bottom ${i === pluModalIdx ? 'bg-primary text-white' : ''}"
                     style="cursor:pointer;border-radius:4px;" id="plu_opt_${i}"
                     onclick="selectPluFromModal(${i})">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong style="font-size:1.05rem;">&#8377; ${parseFloat(p.selling_rate).toFixed(2)}</strong>
                    <span class="ml-2 small ${i === pluModalIdx ? 'text-white-50' : 'text-muted'}">MRP &#8377; ${parseFloat(p.mrp).toFixed(2)}</span>
                    <span class="badge badge-info ml-1" style="font-size:.65rem;">${p.plu_code}</span>
                    ${batchStr ? `<small class="ml-1 ${i === pluModalIdx ? 'text-white-50' : 'text-muted'}">${batchStr}</small>` : ''}
                    ${expStr ? `<small class="ml-1 ${i === pluModalIdx ? 'text-white-50' : 'text-warning'}">${expStr}</small>` : ''}
                </div>
                <div class="text-right">
                    <span class="badge badge-${p.stock_qty <= 0 ? 'danger' : 'success'} badge-pill" style="font-size:.8rem;">
                        Stock: ${p.stock_qty} ${p.unit || 'PCS'}
                    </span>
                </div>
            </div>
        </div>`;
    }).join('');
}

function selectPluFromModal(idx) {
    const selected = pluModalItems[idx];
    $('#pluSelectModal').modal('hide');
    fetch('<?= base_url("billing/get_plu_ajax/") ?>' + selected.id, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(plu => { if (plu && plu.id) addPluProduct(plu); })
    .catch(() => addPluProduct(selected));
}

// Keyboard navigation for PLU modal
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('pluSelectModal');
    if (!modal || !modal.classList.contains('show')) return;
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        pluModalIdx = Math.min(pluModalIdx + 1, pluModalItems.length - 1);
        renderPluModalList();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        pluModalIdx = Math.max(pluModalIdx - 1, 0);
        renderPluModalList();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        selectPluFromModal(pluModalIdx);
    } else if (e.key === 'Escape') {
        $('#pluSelectModal').modal('hide');
        barcodeInput.focus();
    }
});

function patchLocalProduct(p) {
    p.scan_unit = 'PCS';
    p.scan_multiplier = 1;
    return p;
}

function addProductById(pid) {
    const p = PRODUCTS.find(x => x.id == pid);
    if (p) addProduct(patchLocalProduct(p));
}

function addProduct(p) {
    hideResults();
    barcodeInput.value = '';
    barcodeInput.focus();

    const uniqueId = p.id + '_' + p.scan_unit;

    if (addedProducts[uniqueId]) {
        const row = addedProducts[uniqueId];
        const qtyInput = document.getElementById('qty_' + row.rowId);
        const newQty = parseInt(qtyInput.value) + 1;
        qtyInput.value = newQty;
        calcRow(row.rowId);
        const tr = document.getElementById('row_' + row.rowId);
        tr.style.background = '#fffacd';
        setTimeout(() => tr.style.background = '', 600);
        return;
    }

    rowCount++;
    document.getElementById('empty_row')?.remove();

    const gstOptions = [0,3,5,12,18,40].map(r =>
        `<option value="${r}" ${r == p.gst_percent ? 'selected' : ''}>${r}%</option>`
    ).join('');

    const cartonInfo = p.scan_unit === 'CRT' ? `<br><small class="text-primary font-weight-bold">(1 Carton = ${p.scan_multiplier} ${p.unit})</small>` : '';
    const displayRate = p.scan_unit === 'CRT' ? p.selling_price * p.scan_multiplier : p.selling_price;

    const imgUrl = p.image1 ? '<?= base_url("assets/uploads/products/") ?>' + p.image1 : '';
    const imgHtml = imgUrl ? `<img src="${imgUrl}" style="width:36px; height:36px; object-fit:cover; border-radius:4px; border:1px solid #ddd; margin-right:8px;">` : '';

    const row = `
    <tr id="row_${rowCount}" style="animation:fadeIn .3s ease;">
        <td>
            <div class="d-flex align-items-center">
                ${imgHtml}
                <div>
                    <div class="font-weight-bold" style="font-size:.85rem;">${p.name}</div>
                    ${p.brand ? `<small class="text-muted mr-1">${p.brand}</small>` : ''}
                    ${p.description ? `<div class="text-muted" style="font-size:0.75rem;line-height:1;margin-top:1px;">${p.description}</div>` : ''}
                    ${cartonInfo}
                </div>
            </div>
            <input type="hidden" name="product_id[]" value="${p.id}">
            <input type="hidden" name="multiplier[]" value="${p.scan_multiplier}">
            <input type="hidden" name="mrp[]" value="${p.mrp || 0}">
        </td>
        <td>
            <span class="badge badge-${p.stock_qty > 10 ? 'success' : 'warning'}">
                ${p.stock_qty}
            </span>
        </td>
        <td>
            <div class="input-group input-group-sm" style="width:100px;">
                <input type="number" name="qty[]" id="qty_${rowCount}"
                       class="form-control text-center px-1"
                       value="1" min="1" oninput="calcRow(${rowCount})">
                <div class="input-group-append"><span class="input-group-text px-1" style="font-size:10px;">${p.scan_unit}</span></div>
            </div>
        </td>
        <td class="align-middle text-muted text-center" style="font-size:12px;">&#8377; ${parseFloat((p.mrp || 0) * (p.scan_unit === 'CRT' ? p.scan_multiplier : 1)).toFixed(2)}</td>
        <td>
            <input type="number" step="0.01" name="rate[]" id="rate_${rowCount}"
                   class="form-control form-control-sm"
                   value="${parseFloat(displayRate).toFixed(2)}"
                   oninput="calcRow(${rowCount})"
                   style="width:80px;"
                   \${isCashier ? 'readonly' : ''}>
        </td>
        ${enableGst ? `
        <td>
            <select name="gst_percent[]" id="gst_${rowCount}"
                    class="form-control form-control-sm" onchange="calcRow(${rowCount})">
                \${gstOptions}
            </select>
        </td>
        ` : `
        <td style="display:none;"><input type="hidden" name="gst_percent[]" id="gst_${rowCount}" value="0"></td>
        `}
        <td id="total_${rowCount}" class="font-weight-bold align-middle">&#8377; 0.00</td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1"
                    onclick="removeRow(${rowCount}, '${uniqueId}')">✕</button>
        </td>
    </tr>`;

    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', row);
    addedProducts[uniqueId] = { rowId: rowCount };
    document.getElementById('itemsBody').lastElementChild.insertAdjacentHTML('beforeend', '<input type="hidden" name="item_discount[]" value="0">');
    
    calcRow(rowCount);
    updateSubmitBtn();
}

function calcRow(rid) {
    const qty  = parseFloat(document.getElementById('qty_'  + rid)?.value) || 0;
    const rate = parseFloat(document.getElementById('rate_' + rid)?.value) || 0;
    // Rate is GST-inclusive: rate*qty IS the final amount charged for this line.
    const total   = rate * qty;
    const el = document.getElementById('total_' + rid);
    if (el) el.textContent = '&#8377; ' + total.toFixed(2);
    recalcTotals();
}

function removeRow(rid, pid) {
    document.getElementById('row_' + rid)?.remove();
    delete addedProducts[pid];
    recalcTotals();
    updateSubmitBtn();
    if (document.querySelectorAll('#itemsBody tr').length === 0) {
        document.getElementById('itemsBody').innerHTML =
            '<tr id="empty_row"><td colspan="' + (enableGst ? 8 : 7) + '" class="text-center text-muted py-3" style="font-size:.82rem;">Scan or search a product above to begin</td></tr>';
    }
}

function clearAll() {
    if (!confirm('Clear all items?')) return;
    addedProducts = {};
    rowCount = 0;
    document.getElementById('itemsBody').innerHTML =
        '<tr id="empty_row"><td colspan="' + (enableGst ? 8 : 7) + '" class="text-center text-muted py-3" style="font-size:.82rem;">Scan or search a product above to begin</td></tr>';
    recalcTotals();
    updateSubmitBtn();
}

function recalcTotals() {
    let sub = 0, gstTotal = 0, count = 0;
    document.querySelectorAll('#itemsBody tr[id^=row_]').forEach(function(row) {
        const rid  = row.id.replace('row_','');
        const qty  = parseFloat(document.getElementById('qty_'  + rid)?.value) || 0;
        const rate = parseFloat(document.getElementById('rate_' + rid)?.value) || 0;
        const gst  = parseFloat(document.getElementById('gst_'  + rid)?.value) || 0;
        // Rate is GST-inclusive: lineTotal IS the final amount; taxable/GST
        // are extracted backwards out of it.
        const lineTotal   = rate * qty;
        const lineTaxable = gst > 0 ? lineTotal / (1 + gst / 100) : lineTotal;
        sub      += lineTaxable;
        gstTotal += (lineTotal - lineTaxable);
        count++;
    });
    const total = sub + gstTotal;
    document.getElementById('s_items').textContent = count;
    document.getElementById('item_count').textContent = count;
    document.getElementById('s_sub').textContent   = '&#8377; ' + sub.toFixed(2);
    const sGst = document.getElementById('s_gst');
    if (sGst) sGst.textContent = '&#8377; ' + gstTotal.toFixed(2);
    document.getElementById('s_total').textContent = '&#8377; ' + total.toFixed(2);
    calcChange();
}

function calcChange() {
    const totalText = document.getElementById('s_total').textContent.replace('₹','').replace(/,/g,'').trim();
    const total  = parseFloat(totalText) || 0;
    const paid   = parseFloat(document.getElementById('paid_amount').value) || 0;
    const change = paid - total;
    const el     = document.getElementById('change_due');
    el.textContent = '&#8377; ' + Math.max(0, change).toFixed(2);
    el.style.color  = change >= 0 ? '#28a745' : '#dc3545';
}

function updateSubmitBtn() {
    const hasItems = document.querySelectorAll('#itemsBody tr[id^=row_]').length > 0;
    document.getElementById('submit_btn').disabled = !hasItems;
}

function loadCustomer(cid) {
    if (!cid) { document.getElementById('customer_panel').style.display='none'; return; }
    fetch('<?= base_url("billing/get_customer_ajax/") ?>' + cid, {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(r=>r.json())
        .then(c => {
            const invoiceType = document.getElementById('invoice_type')?.value || 'retail';
            isInterstate = (invoiceType !== 'retail' && c.state && c.state.trim().toLowerCase() !== COMPANY_STATE);
            const bal = parseFloat(c.balance)||0, lim = parseFloat(c.credit_limit)||0;
            document.getElementById('cust_info_body').innerHTML =
                `<strong>${c.name}</strong> &middot; ${c.type}<br>` +
                (c.gst_number ? `<code>${c.gst_number}</code><br>` : '') +
                (lim > 0 ? `Credit: &#8377;${lim.toFixed(2)} | Due: <span class="${bal>0?'text-danger':'text-success'}">&#8377;${bal.toFixed(2)}</span>` : '');
            document.getElementById('customer_panel').style.display = 'block';
        });
}

function quickPrint() {
    document.getElementById('invoiceForm').setAttribute('target', '_blank');
    document.getElementById('invoiceForm').submit();
}

// Click outside to hide search
document.addEventListener('click', function(e) {
    if (!e.target.closest('#barcode_input') && !e.target.closest('#search_results')) hideResults();
});

// Fade-in animation for new rows
const style = document.createElement('style');
style.textContent = '@keyframes fadeIn { from { background:#fff9c4; } to { background:transparent; } }';
document.head.appendChild(style);
</script>
