
<div class="container-fluid mt-3">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4><i class="fas fa-file-alt text-primary"></i> New Estimate / Quotation</h4>
    <a href="<?= base_url('estimates') ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<form method="post" action="<?= base_url('estimates/create') ?>" id="estimateForm">

<!-- Customer Info -->
<div class="card shadow-sm mb-3">
<div class="card-header bg-primary text-white py-2"><b>Customer Information</b></div>
<div class="card-body">
<div class="form-row">
    <div class="col-md-3">
        <label>Select Customer (Optional)</label>
        <select name="customer_id" id="customer_id" class="form-control form-control-sm" onchange="fillCustomer(this)">
            <option value="">-- Walk-in Customer --</option>
            <?php foreach ($customers as $c): ?>
            <option value="<?= $c->id ?>" data-name="<?= htmlspecialchars($c->name) ?>" data-phone="<?= $c->phone ?>"><?= htmlspecialchars($c->name) ?> (<?= $c->phone ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label>Customer Name <span class="text-danger">*</span></label>
        <input type="text" name="customer_name" id="customer_name" class="form-control form-control-sm" required placeholder="Enter name">
    </div>
    <div class="col-md-2">
        <label>Mobile</label>
        <input type="text" name="customer_mobile" id="customer_mobile" class="form-control form-control-sm" placeholder="Mobile number">
    </div>
    <div class="col-md-2">
        <label>Estimate Date <span class="text-danger">*</span></label>
        <input type="date" name="estimate_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
    </div>
    <div class="col-md-2">
        <label>Valid Until</label>
        <input type="date" name="valid_until" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
    </div>
</div>
</div>
</div>

<!-- Smart Item Entry -->
<div class="card shadow-sm mb-3">
<div class="card-header bg-success text-white py-2 d-flex justify-content-between">
    <b>Items</b>
    <small class="text-white-50">Tip: Type item code or name to search | Tab/Enter to navigate</small>
</div>
<div class="card-body p-2">

<!-- Smart Search + Barcode Scan Bar -->
<div class="form-row mb-2 align-items-end">
    <div class="col-md-5">
        <label class="small font-weight-bold mb-1">🔍 Search by Name / Item Code</label>
        <div class="input-group input-group-sm">
            <input type="text" id="smartSearch" class="form-control" placeholder="Type item code or name..." autocomplete="off">
            <div class="input-group-append">
                <button type="button" class="btn btn-success btn-sm" onclick="triggerSearch()"><i class="fas fa-search"></i> Search</button>
            </div>
        </div>
        <div id="searchDropdown" class="list-group position-absolute" style="z-index:9999; max-height:250px; overflow-y:auto; display:none; width:360px;"></div>
    </div>
    <div class="col-md-4">
        <label class="small font-weight-bold mb-1">📷 Barcode Scanner</label>
        <div class="input-group input-group-sm">
            <div class="input-group-prepend">
                <span class="input-group-text bg-warning border-warning"><i class="fas fa-barcode"></i></span>
            </div>
            <input type="text" id="barcodeInput" class="form-control border-warning"
                   placeholder="Scan barcode & press Enter..." autocomplete="off"
                   style="font-family:monospace;letter-spacing:1px;">
        </div>
        <small id="barcodeStatus" class="text-muted"></small>
    </div>
    <div class="col-md-3 text-right">
        <button type="button" class="btn btn-sm btn-outline-success" onclick="addRow()">
            <i class="fas fa-plus"></i> Add Row
        </button>
    </div>
</div>

<div class="table-responsive">
<table class="table table-bordered table-sm" id="itemsTable">
<thead class="thead-light">
<tr>
    <th width="30">#</th>
    <th width="90">Code</th>
    <th>Description</th>
    <th width="80">Qty</th>
    <th width="60">Unit</th>
    <th width="100">Rate</th>
    <th width="70">Disc%</th>
    <?php if (!empty($settings['enable_gst'])): ?>
    <th width="70">Tax%</th>
    <th width="90">Tax Amt</th>
    <?php endif; ?>
    <th width="100">Total</th>
    <th width="60">Stock</th>
    <th width="40">Del</th>
</tr>
</thead>
<tbody id="itemsBody">
<!-- rows added dynamically -->
</tbody>
<tfoot>
<tr>
    <td colspan="<?= !empty($settings['enable_gst']) ? 9 : 7 ?>" class="text-right"><b>Subtotal:</b></td>
    <td class="text-right font-weight-bold" id="footSubtotal">0.00</td>
    <td colspan="2"></td>
</tr>
<tr class="table-warning">
    <td colspan="<?= !empty($settings['enable_gst']) ? 9 : 7 ?>" class="text-right"><b>Grand Total:</b></td>
    <td class="text-right font-weight-bold text-danger h5" id="footTotal">₹0.00</td>
    <td colspan="2"></td>
</tr>
</tfoot>
</table>
</div>

<button type="button" class="btn btn-sm btn-outline-success" onclick="addRow()">
    <i class="fas fa-plus"></i> Add Row
</button>
</div>
</div>

<!-- Notes + Submit -->
<div class="card shadow-sm mb-3">
<div class="card-body">
<div class="form-row">
    <div class="col-md-8">
        <label>Notes / Remarks</label>
        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Optional notes for customer..."></textarea>
    </div>
    <div class="col-md-4 text-right pt-3">
        <div class="h4 text-danger font-weight-bold">Total: ₹<span id="grandTotalDisplay">0.00</span></div>
        <button type="submit" class="btn btn-primary btn-lg mt-2">
            <i class="fas fa-save"></i> Save Estimate
        </button>
    </div>
</div>
</div>
</div>

</form>
</div>

<script>
var enableGst = <?= !empty($settings['enable_gst']) ? 'true' : 'false' ?>;
var enableStockCheck = <?= !empty($settings['enable_estimate_stock_check']) ? 'true' : 'false' ?>;
var blockIfNoStock  = <?= !empty($settings['block_estimate_if_no_stock']) ? 'true' : 'false' ?>;
var rowCount = 0;

function fillCustomer(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('customer_name').value   = opt.dataset.name  || '';
    document.getElementById('customer_mobile').value = opt.dataset.phone || '';
}

function addRow(product) {
    rowCount++;
    var i = rowCount;
    var p = product || {};
    var taxCols = enableGst ? `
        <td><input type="number" name="tax_percent[]" class="form-control form-control-sm tax-pct" value="${p.gst_percent||0}" min="0" max="40" step="0.01" onchange="calcRow(${i})"></td>
        <td><input type="text" name="tax_amount_disp[]" class="form-control form-control-sm bg-light" id="tax_amt_${i}" readonly value="0.00"></td>` : '';

    var stockBadge = p.stock_qty !== undefined ? getStockBadge(p.stock_qty, 1, i) : '<span id="stock_badge_'+i+'">-</span>';

    var row = `<tr id="row_${i}" data-row="${i}">
        <td>${i}</td>
        <td><input type="text" id="code_${i}" class="form-control form-control-sm" value="${p.item_code||''}" placeholder="Code" onblur="searchByCode(${i})"></td>
        <td>
            <input type="hidden" name="product_id[]" id="pid_${i}" value="${p.id||''}">
            <input type="text" name="description[]" id="desc_${i}" class="form-control form-control-sm" value="${p.product_name||''}" required placeholder="Item name">
        </td>
        <td><input type="number" name="qty[]" id="qty_${i}" class="form-control form-control-sm" value="${p.qty||1}" min="0.001" step="0.001" onchange="calcRow(${i}); checkStock(${i})"></td>
        <td><input type="text" name="unit[]" id="unit_${i}" class="form-control form-control-sm" value="${p.unit||'PCS'}" readonly></td>
        <td><input type="number" name="rate[]" id="rate_${i}" class="form-control form-control-sm" value="${p.rate||0}" min="0" step="0.01" onchange="calcRow(${i})"></td>
        <td><input type="number" name="item_discount[]" class="form-control form-control-sm disc-pct" value="0" min="0" max="100" step="0.01" onchange="calcRow(${i})"></td>
        ${taxCols}
        <td><input type="text" id="total_${i}" name="line_total[]" class="form-control form-control-sm bg-light font-weight-bold" readonly value="0.00"></td>
        <td id="stock_cell_${i}">${stockBadge}</td>
        <td><button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow(${i})"><i class="fas fa-trash"></i></button></td>
    </tr>`;

    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', row);
    calcRow(i);

    if (p.id) checkStock(i);

    // Focus qty
    setTimeout(() => { var q = document.getElementById('qty_'+i); if(q) q.focus(); }, 50);
}

function removeRow(i) {
    var row = document.getElementById('row_'+i);
    if (row) row.remove();
    calcFooter();
}

function calcRow(i) {
    var qty   = parseFloat(document.getElementById('qty_'+i)?.value)   || 0;
    var rate  = parseFloat(document.getElementById('rate_'+i)?.value)  || 0;
    var disc  = parseFloat(document.querySelector('#row_'+i+' .disc-pct')?.value) || 0;
    var tax   = enableGst ? (parseFloat(document.querySelector('#row_'+i+' .tax-pct')?.value) || 0) : 0;

    // Rate is GST-inclusive: (gross - discAmt) IS the final quoted amount.
    var gross   = qty * rate;
    var discAmt = gross * (disc / 100);
    var total   = gross - discAmt;
    var taxable = tax > 0 ? total / (1 + tax / 100) : total;
    var taxAmt  = total - taxable;

    if (enableGst) document.getElementById('tax_amt_'+i).value = taxAmt.toFixed(2);
    document.getElementById('total_'+i).value = total.toFixed(2);
    calcFooter();
}

function calcFooter() {
    var totals = document.querySelectorAll('[id^="total_"]');
    var sum = 0;
    totals.forEach(t => sum += parseFloat(t.value)||0);
    document.getElementById('footSubtotal').textContent = sum.toFixed(2);
    document.getElementById('footTotal').textContent = '₹'+sum.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = sum.toFixed(2);
}

function getStockBadge(stock, qty, i) {
    var cls = stock >= qty ? 'success' : (stock > 0 ? 'warning' : 'danger');
    var icon = stock < qty ? '⚠' : '✓';
    return `<span class="badge badge-${cls}" id="stock_badge_${i}">${icon} ${stock}</span>`;
}

function checkStock(i) {
    if (!enableStockCheck) return;
    var pid = document.getElementById('pid_'+i)?.value;
    var qty = parseFloat(document.getElementById('qty_'+i)?.value) || 1;
    if (!pid) return;

    fetch('<?= base_url("estimates/ajax_stock_check") ?>', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'product_id='+pid+'&qty='+qty
    })
    .then(r => r.json())
    .then(d => {
        var cell = document.getElementById('stock_badge_'+i);
        if (!cell) return;
        if (d.ok) {
            cell.className = 'badge badge-success';
            cell.textContent = '✓ '+d.available;
        } else {
            cell.className = 'badge badge-danger';
            cell.textContent = '⚠ '+d.available;
        }
    });
}

// Smart search
var searchTimeout;
document.getElementById('smartSearch').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    var q = this.value.trim();
    if (q.length < 1) { document.getElementById('searchDropdown').style.display='none'; return; }
    searchTimeout = setTimeout(() => doSearch(q), 200);
});

document.getElementById('smartSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); triggerSearch(); }
});

function triggerSearch() {
    var q = document.getElementById('smartSearch').value.trim();
    if (q) doSearch(q, true);
}

function doSearch(q, autoSelect) {
    fetch('<?= base_url("estimates/ajax_search_product") ?>?q=' + encodeURIComponent(q))
    .then(r => r.json())
    .then(items => {
        if (autoSelect && items.length === 1) {
            addRow(items[0]);
            document.getElementById('smartSearch').value = '';
            document.getElementById('searchDropdown').style.display='none';
            return;
        }
        var dd = document.getElementById('searchDropdown');
        dd.innerHTML = '';
        if (items.length === 0) { dd.style.display='none'; return; }
        items.forEach(p => {
            var a = document.createElement('a');
            a.href = '#';
            a.className = 'list-group-item list-group-item-action py-1 px-2 small';
            a.innerHTML = `<b>[${p.item_code||'?'}]</b> ${p.product_name} &mdash; ₹${p.rate} &mdash; <span class="${p.stock_qty>0?'text-success':'text-danger'}">Stock: ${p.stock_qty}</span>`;
            a.onclick = function(e) {
                e.preventDefault();
                addRow(p);
                document.getElementById('smartSearch').value = '';
                dd.style.display = 'none';
            };
            dd.appendChild(a);
        });
        dd.style.display = 'block';
    });
}

function searchByCode(i) {
    var code = document.getElementById('code_'+i)?.value.trim();
    if (!code) return;
    fetch('<?= base_url("estimates/ajax_search_product") ?>?q=' + encodeURIComponent(code))
    .then(r => r.json())
    .then(items => {
        if (items.length > 0) {
            var p = items[0];
            document.getElementById('pid_'+i).value  = p.id;
            document.getElementById('desc_'+i).value = p.product_name;
            document.getElementById('rate_'+i).value = p.rate;
            document.getElementById('unit_'+i).value = p.unit;
            if (enableGst) {
                var taxInput = document.querySelector('#row_'+i+' .tax-pct');
                if (taxInput) taxInput.value = p.gst_percent||0;
            }
            calcRow(i);
            checkStock(i);
        }
    });
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('#smartSearch') && !e.target.closest('#searchDropdown')) {
        document.getElementById('searchDropdown').style.display='none';
    }
});

// Add first row on load
addRow();

/* ══════════════════════════════════════════════
   BARCODE SCANNER — Estimates Form
══════════════════════════════════════════════ */
var barcodeInput  = document.getElementById('barcodeInput');
var barcodeStatus = document.getElementById('barcodeStatus');

barcodeInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        var barcode = this.value.trim();
        if (barcode.length > 0) lookupBarcodeEstimate(barcode);
    }
});

function lookupBarcodeEstimate(barcode) {
    barcodeStatus.innerHTML = '⏳ Searching...';
    barcodeStatus.className = 'text-muted small';
    // Use the billing barcode endpoint which returns product info
    fetch('<?= base_url("billing/get_by_barcode_ajax") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'barcode=' + encodeURIComponent(barcode)
    })
    .then(r => r.json())
    .then(data => {
        barcodeInput.value = '';
        if (data.success && data.product) {
            var p = data.product;
            barcodeStatus.innerHTML = '✓ Found: <strong>' + p.name + '</strong> — added!';
            barcodeStatus.className = 'text-success small';
            // Build estimate-compatible product object
            addRow({
                id:           p.id,
                item_code:    p.item_code || '',
                product_name: p.name,
                rate:         parseFloat(p.selling_price) || 0,
                unit:         p.unit || 'PCS',
                stock_qty:    p.stock_qty || 0,
                gst_percent:  parseFloat(p.gst_percent) || 0,
            });
        } else {
            barcodeStatus.innerHTML = '✗ Not found: ' + barcode;
            barcodeStatus.className = 'text-danger small';
            barcodeInput.focus();
        }
    })
    .catch(function() {
        barcodeStatus.innerHTML = '✗ Error during lookup.';
        barcodeStatus.className = 'text-danger small';
    });
}
</script>

