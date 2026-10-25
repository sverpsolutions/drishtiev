<?php // Sales Returns / Credit Note - Create ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-arrow-return-left text-danger mr-2"></i>New Credit Note</h4>
        <small class="text-muted">Sales Return from Customer — Appears in GSTR-1 Section 9B</small>
    </div>
    <a href="<?= base_url('sales_returns') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left mr-1"></i>Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle mr-2"></i><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<form method="post" action="<?= base_url('sales_returns/save') ?>" id="cnForm">

<!-- Header -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-danger text-white"><i class="bi bi-file-earmark-minus mr-2"></i>Credit Note Details</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Credit Note No <span class="text-danger">*</span></label>
                <input type="text" name="return_number" class="form-control" value="<?= htmlspecialchars($cn_number) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Return Date <span class="text-danger">*</span></label>
                <input type="date" name="return_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Customer <span class="text-danger">*</span></label>
                <select name="customer_id" id="customer_id" class="form-control" required onchange="loadCustomerInvoices(this.value)">
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c->id ?>"><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Against Invoice (Optional)</label>
                <select name="invoice_id" id="invoice_id" class="form-control" onchange="loadInvoiceItems(this.value)">
                    <option value="">-- Select Invoice --</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Reason for Return</label>
                <input type="text" name="reason" class="form-control" placeholder="e.g. Defective goods...">
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Supply Type</label>
                <select name="is_interstate" id="is_interstate" class="form-control" onchange="recalcAll()">
                    <option value="0">Intrastate (CGST + SGST)</option>
                    <option value="1">Interstate (IGST)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Refund Method</label>
                <select name="refund_method" id="refund_method" class="form-control">
                    <option value="balance">Adjust Outstanding Balance</option>
                    <option value="cash">Refund in Cash</option>
                    <option value="wallet">Credit to Customer Wallet</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label font-weight-bold">Notes</label>
                <input type="text" name="notes" class="form-control" placeholder="Additional notes...">
            </div>
        </div>
    </div>
</div>

<!-- Barcode / Smart Search -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-danger text-white"><i class="bi bi-upc-scan"></i></span>
                    <input type="text" id="barcodeInput" class="form-control" placeholder="Scan barcode or type item name / code...">
                    <button type="button" class="btn btn-danger btn-sm" onclick="lookupBarcode()">Add</button>
                </div>
            </div>
            <div class="col-auto"><span id="barcodeStatus" class="small"></span></div>
            <div class="col text-right"><span class="badge badge-danger" id="rowCountBadge">0 items</span></div>
        </div>
    </div>
</div>

<!-- Items Table -->
<div class="card shadow-sm mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul mr-1"></i>Return Items</span>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="addRow()"><i class="bi bi-plus"></i> Add Row</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0" id="itemsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:220px">Product</th>
                        <th style="width:100px">HSN</th>
                        <th style="width:70px">Qty</th>
                        <th style="width:60px">Unit</th>
                        <th style="width:90px">Rate (₹)</th>
                        <th style="width:70px">Disc%</th>
                        <th style="width:90px">Taxable</th>
                        <th style="width:60px">GST%</th>
                        <th style="width:80px">GST Amt</th>
                        <th style="width:90px">Total</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="itemsBody"></tbody>
                <tfoot>
                    <tr class="table-light font-weight-bold">
                        <td colspan="6" class="text-right">Totals:</td>
                        <td id="ft_taxable" class="text-right">0.00</td>
                        <td></td>
                        <td id="ft_gst" class="text-right">0.00</td>
                        <td id="ft_total" class="text-right text-danger">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- GST Breakup + Submit -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-percent mr-1"></i>GST Breakup</div>
            <div class="card-body">
                <table class="table table-sm mb-0" id="gstBreakup">
                    <thead class="table-light"><tr><th>Rate</th><th>Taxable</th><th>CGST</th><th>SGST</th><th>IGST</th></tr></thead>
                    <tbody id="gstBreakupBody"><tr><td colspan="5" class="text-muted text-center small">Add items above</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white"><i class="bi bi-receipt mr-1"></i>Credit Note Summary</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Subtotal</td><td class="text-right font-weight-bold" id="s_subtotal">₹0.00</td></tr>
                    <tr><td>Discount</td><td class="text-right" id="s_discount">₹0.00</td></tr>
                    <tr><td>Taxable Amount</td><td class="text-right" id="s_taxable">₹0.00</td></tr>
                    <tr><td>CGST</td><td class="text-right" id="s_cgst">₹0.00</td></tr>
                    <tr><td>SGST</td><td class="text-right" id="s_sgst">₹0.00</td></tr>
                    <tr><td>IGST</td><td class="text-right" id="s_igst">₹0.00</td></tr>
                    <tr class="table-danger font-weight-bold"><td>Credit Note Total</td><td class="text-right fs-5" id="s_total">₹0.00</td></tr>
                </table>
                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-danger btn-lg"><i class="bi bi-check-lg mr-2"></i>Save Credit Note</button>
                </div>
            </div>
        </div>
    </div>
</div>

</form>

<script>
const PRODUCTS = <?= json_encode(array_map(function($p) {
    return ['id'=>(int)$p->id,'name'=>$p->name,'item_code'=>$p->item_code??'','hsn_code'=>$p->hsn_code??'','unit'=>$p->unit,'selling_price'=>(float)$p->selling_price,'gst_percent'=>(float)$p->gst_percent];
}, $products)) ?>;

let rowCount = 0;

// ── Customer invoice lookup ───────────────────────────────
function loadCustomerInvoices(cid) {
    const sel = document.getElementById('invoice_id');
    sel.innerHTML = '<option value="">-- Select Invoice --</option>';
    if (!cid) return;
    fetch('<?= base_url("sales_returns/get_invoices_by_customer/") ?>' + cid, {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(r=>r.json()).then(rows => {
            rows.forEach(inv => {
                const o = document.createElement('option');
                o.value = inv.id;
                o.text = inv.invoice_no + ' — ' + inv.invoice_date + ' — ₹' + parseFloat(inv.total_amount).toFixed(2);
                o.dataset.id = inv.id;
                sel.appendChild(o);
            });
        });
}

function loadInvoiceItems(inv_id) {
    if (!inv_id) return;
    fetch('<?= base_url("sales_returns/get_invoice_ajax/") ?>' + inv_id, {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(r=>r.json()).then(data => {
            if (!data.success) return;
            document.getElementById('is_interstate').value = data.invoice.is_interstate ? '1' : '0';
            document.getElementById('itemsBody').innerHTML = '';
            rowCount = 0;
            data.invoice.items.forEach(item => {
                addRow();
                const rid = rowCount;
                const row = document.getElementById('row_' + rid);
                // Set product select
                const sel = row.querySelector('[name^="items"][name$="[product_id]"]');
                for (let opt of sel.options) {
                    if (parseInt(opt.value) === parseInt(item.product_id)) { opt.selected = true; break; }
                }
                row.querySelector('[name$="[product_name]"]').value = item.product_name;
                row.querySelector('[name$="[hsn_code]"]').value    = item.hsn_code || '';
                row.querySelector('[name$="[qty]"]').value         = item.qty;
                row.querySelector('[name$="[unit]"]').value        = item.unit;
                row.querySelector('[name$="[rate]"]').value        = parseFloat(item.rate).toFixed(2);
                row.querySelector('[name$="[gst_percent]"]').value = parseFloat(item.gst_percent).toFixed(2);
                calcRow(rid);
            });
            recalcAll();
        });
}

// ── Barcode scan ──────────────────────────────────────────
document.getElementById('barcodeInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); lookupBarcode(); }
});

// Billing-style product match: every typed word must appear (any order) — "maggi noodle" finds "MAGGI AATA NOODLE"
window.pmatch = window.pmatch || function (q) { var w = String(q || '').toLowerCase().split(/\s+/).filter(Boolean); var t = Array.prototype.slice.call(arguments, 1).map(function (x) { return String(x == null ? '' : x).toLowerCase(); }).join(' | '); return w.every(function (x) { return t.indexOf(x) !== -1; }); };
function lookupBarcode() {
    const val = document.getElementById('barcodeInput').value.trim();
    if (!val) return;

    // Try item_code numeric match first
    let found = PRODUCTS.find(p => p.item_code == val);
    if (!found) found = PRODUCTS.find(p => pmatch(val, p.name));

    if (found) {
        addRowFromProduct(found);
        document.getElementById('barcodeInput').value = '';
        document.getElementById('barcodeStatus').innerHTML = '<span class="text-success">✓ ' + found.name + ' added</span>';
        setTimeout(() => document.getElementById('barcodeStatus').innerHTML = '', 2000);
        return;
    }

    // Server lookup
    fetch('<?= base_url("billing/get_by_barcode_ajax") ?>', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
        body: 'barcode=' + encodeURIComponent(val)
    }).then(r=>r.json()).then(d => {
        if (d.success) {
            addRowFromProduct(d.product);
            document.getElementById('barcodeInput').value = '';
            document.getElementById('barcodeStatus').innerHTML = '<span class="text-success">✓ ' + d.product.name + '</span>';
        } else {
            document.getElementById('barcodeStatus').innerHTML = '<span class="text-danger">✗ Not found</span>';
        }
        setTimeout(() => document.getElementById('barcodeStatus').innerHTML = '', 2500);
    });
}

function addRowFromProduct(p) {
    addRow();
    const row = document.getElementById('row_' + rowCount);
    const sel = row.querySelector('select[name$="[product_id]"]');
    for (let opt of sel.options) {
        if (parseInt(opt.value) === parseInt(p.id)) { opt.selected = true; break; }
    }
    row.querySelector('[name$="[product_name]"]').value = p.name;
    row.querySelector('[name$="[hsn_code]"]').value    = p.hsn_code || '';
    row.querySelector('[name$="[unit]"]').value        = p.unit || 'PCS';
    row.querySelector('[name$="[rate]"]').value        = parseFloat(p.selling_price || 0).toFixed(2);
    row.querySelector('[name$="[gst_percent]"]').value = parseFloat(p.gst_percent || 0).toFixed(2);
    row.querySelector('[name$="[qty]"]').focus();
    calcRow(rowCount);
}

// ── Add blank row ─────────────────────────────────────────
function addRow() {
    rowCount++;
    const rid = rowCount;
    const prodOpts = PRODUCTS.map(p =>
        `<option value="${p.id}" data-price="${p.selling_price}" data-gst="${p.gst_percent}" data-hsn="${p.hsn_code}" data-unit="${p.unit}" data-code="${p.item_code||''}">${p.name}</option>`
    ).join('');

    const html = `<tr id="row_${rid}">
        <td>
            <select name="items[${rid}][product_id]" class="form-control form-control-sm" onchange="fillProduct(this,${rid})">
                <option value="">-- Select --</option>${prodOpts}
            </select>
            <input type="hidden" name="items[${rid}][product_name]" id="pname_${rid}">
        </td>
        <td><input type="text"   name="items[${rid}][hsn_code]"        class="form-control form-control-sm" id="hsn_${rid}"></td>
        <td><input type="number" name="items[${rid}][qty]"             class="form-control form-control-sm" id="qty_${rid}" value="1" min="0.001" step="0.001" oninput="calcRow(${rid})"></td>
        <td><input type="text"   name="items[${rid}][unit]"            class="form-control form-control-sm" id="unit_${rid}" value="PCS" style="width:55px"></td>
        <td><input type="number" name="items[${rid}][rate]"            class="form-control form-control-sm" id="rate_${rid}" value="0" step="0.01" oninput="calcRow(${rid})"></td>
        <td><input type="number" name="items[${rid}][discount_percent]" class="form-control form-control-sm" id="disc_${rid}" value="0" step="0.01" oninput="calcRow(${rid})"></td>
        <td><input type="number" name="items[${rid}][taxable_amt]"     class="form-control form-control-sm" id="taxable_${rid}" value="0" readonly></td>
        <td><input type="number" name="items[${rid}][gst_percent]"     class="form-control form-control-sm" id="gst_${rid}" value="0" step="0.01" oninput="calcRow(${rid})"></td>
        <td><input type="number" name="items[${rid}][gst_amount]"      class="form-control form-control-sm" id="gst_amt_${rid}" value="0" readonly></td>
        <td><input type="number" name="items[${rid}][total]"           class="form-control form-control-sm" id="total_${rid}" value="0" readonly></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(${rid})"><i class="bi bi-trash"></i></button></td>
    </tr>`;
    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', html);
    updateRowCount();
}

function fillProduct(sel, rid) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt.value) return;
    document.getElementById('pname_'   + rid).value = opt.text;
    document.getElementById('hsn_'     + rid).value = opt.dataset.hsn || '';
    document.getElementById('unit_'    + rid).value = opt.dataset.unit || 'PCS';
    document.getElementById('rate_'    + rid).value = parseFloat(opt.dataset.price||0).toFixed(2);
    document.getElementById('gst_'     + rid).value = parseFloat(opt.dataset.gst||0).toFixed(2);
    calcRow(rid);
}

function calcRow(rid) {
    const qty     = parseFloat(document.getElementById('qty_'     + rid)?.value || 0);
    const rate    = parseFloat(document.getElementById('rate_'    + rid)?.value || 0);
    const disc    = parseFloat(document.getElementById('disc_'    + rid)?.value || 0);
    const gst_pct = parseFloat(document.getElementById('gst_'     + rid)?.value || 0);
    const taxable = parseFloat((qty * rate * (1 - disc/100)).toFixed(2));
    const gst_amt = parseFloat((taxable * gst_pct / 100).toFixed(2));
    const total   = parseFloat((taxable + gst_amt).toFixed(2));
    document.getElementById('taxable_' + rid).value = taxable.toFixed(2);
    document.getElementById('gst_amt_' + rid).value = gst_amt.toFixed(2);
    document.getElementById('total_'   + rid).value = total.toFixed(2);
    recalcAll();
}

function removeRow(rid) {
    const r = document.getElementById('row_' + rid);
    if (r) { r.remove(); recalcAll(); updateRowCount(); }
}

function updateRowCount() {
    const n = document.querySelectorAll('#itemsBody tr').length;
    document.getElementById('rowCountBadge').textContent = n + ' item' + (n!==1?'s':'');
}

function recalcAll() {
    let subtotal=0, taxable=0, cgst=0, sgst=0, igst=0, gstAmt=0;
    const inter = document.getElementById('is_interstate').value === '1';
    const gstMap = {};

    document.querySelectorAll('#itemsBody tr').forEach(row => {
        const rid = row.id.replace('row_','');
        const qty  = parseFloat(document.getElementById('qty_'     +rid)?.value||0);
        const rate = parseFloat(document.getElementById('rate_'    +rid)?.value||0);
        const disc = parseFloat(document.getElementById('disc_'    +rid)?.value||0);
        const gp   = parseFloat(document.getElementById('gst_'     +rid)?.value||0);
        const tx   = parseFloat((qty*rate*(1-disc/100)).toFixed(2));
        const ga   = parseFloat((tx*gp/100).toFixed(2));
        subtotal  += qty*rate;
        taxable   += tx;
        if (inter) igst += ga; else { cgst += ga/2; sgst += ga/2; }
        gstAmt    += ga;
        if (gp > 0) {
            if (!gstMap[gp]) gstMap[gp] = {taxable:0,cgst:0,sgst:0,igst:0};
            gstMap[gp].taxable += tx;
            if (inter) gstMap[gp].igst += ga; else { gstMap[gp].cgst += ga/2; gstMap[gp].sgst += ga/2; }
        }
    });

    const discount = subtotal - taxable;
    const total    = taxable + gstAmt;

    document.getElementById('ft_taxable').textContent = taxable.toFixed(2);
    document.getElementById('ft_gst').textContent     = gstAmt.toFixed(2);
    document.getElementById('ft_total').textContent   = total.toFixed(2);
    document.getElementById('s_subtotal').textContent = '₹'+subtotal.toFixed(2);
    document.getElementById('s_discount').textContent = '₹'+discount.toFixed(2);
    document.getElementById('s_taxable').textContent  = '₹'+taxable.toFixed(2);
    document.getElementById('s_cgst').textContent     = '₹'+cgst.toFixed(2);
    document.getElementById('s_sgst').textContent     = '₹'+sgst.toFixed(2);
    document.getElementById('s_igst').textContent     = '₹'+igst.toFixed(2);
    document.getElementById('s_total').textContent    = '₹'+total.toFixed(2);

    // GST breakup table
    let bhtml = '';
    Object.entries(gstMap).sort().forEach(([rate, v]) => {
        bhtml += `<tr><td>${rate}%</td><td>₹${v.taxable.toFixed(2)}</td><td>₹${v.cgst.toFixed(2)}</td><td>₹${v.sgst.toFixed(2)}</td><td>₹${v.igst.toFixed(2)}</td></tr>`;
    });
    document.getElementById('gstBreakupBody').innerHTML = bhtml || '<tr><td colspan="5" class="text-muted text-center small">No items</td></tr>';
}

// Validation
document.getElementById('cnForm').addEventListener('submit', function(e) {
    if (document.querySelectorAll('#itemsBody tr').length === 0) {
        e.preventDefault();
        alert('Please add at least one item to the credit note.');
    }
});

// Start with one blank row
addRow();
document.getElementById('barcodeInput').focus();
</script>
