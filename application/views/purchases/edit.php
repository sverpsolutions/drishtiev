<?php
$flash_e = $this->session->flashdata('error');
?>
<style>
/* ── Purchases desktop-style form ── */
body.sb-layout .container-fluid { padding-top: 54px; }
.pur-wrap { font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; padding: 10px; background: #eef2f5; min-height: calc(100vh - 60px); }
.pur-wrap input, .pur-wrap select, .pur-wrap button, .pur-wrap textarea { font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; height: 24px; padding: 2px 6px; border: 1px solid #ccc; border-radius: 2px; }
.pur-wrap textarea { height: 45px; resize: none; }
.pur-wrap input:focus, .pur-wrap select:focus { outline: none; border-color: #3b82f6; }
.pur-wrap input[readonly] { background: #f0f0f0; color: #333; }

/* Top Header Block */
.hdr-block { background: #fff; border: 1px solid #ccc; padding: 8px; display: flex; flex-direction: column; gap: 6px; margin-bottom: 5px; border-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
.hdr-row { display: flex; gap: 15px; align-items: center; }
.hdr-field { display: flex; align-items: center; gap: 4px; }
.hdr-field label { width: 90px; text-align: right; color: #555; margin: 0; }
.hdr-field input, .hdr-field select { width: 140px; }
.hdr-field.wide input, .hdr-field.wide select { width: 450px; }

.tabs-bar { border-bottom: 1px solid #0055a5; display: flex; gap: 2px; margin-bottom: 10px; padding-left: 2px; }
.title-tab { background: #0055a5; border: 1px solid #0055a5; border-bottom: none; padding: 4px 15px; font-weight: bold; font-size: 13px; color: #fff; border-top-left-radius: 4px; border-top-right-radius: 4px; }

/* Scan Bar */
.scan-bar { display: flex; align-items: center; gap: 10px; margin: 10px 0; }
.scan-label { font-size: 16px; font-weight: bold; color: #333; margin-right: 4px; }
.scan-input-wrap { border: 1px solid #aaa; background: #fff; display: flex; padding: 2px; flex: 1; max-width: 630px; border-radius: 2px; }
.scan-input { width: 100%; border: none !important; background: transparent !important; height: 26px !important; font-size: 14px !important; outline: none; }
.btn-grad { background: #0055a5; color: #fff; border: 1px solid #004080; border-radius: 2px; cursor: pointer; }
.btn-grad:hover { background: #0066cc; }
.scan-btn { padding: 0 15px; height: 30px !important; font-weight: bold; }

/* Search Dropdown */
#purSearchDrop { z-index:9999; max-height:280px; overflow-y:auto; display:none; position:absolute; top:32px; left:0; width:100%; background:#fff; border:1px solid #ccc; box-shadow: 2px 2px 5px rgba(0,0,0,.2); }
#purSearchDrop .psd-item { padding:6px 8px; cursor:pointer; border-bottom:1px solid #eee; display:flex; align-items:center; gap:10px; }
#purSearchDrop .psd-item:hover, #purSearchDrop .psd-item.active { background:#e6f0fa; }
#purSearchDrop .psd-item .psi-name { font-weight:bold; font-size:12px; color:#1e40af; }
#purSearchDrop .psd-item .psi-code { font-size:11px; color:#666; }
#purSearchDrop .psd-item .psi-meta { font-size:11px; color:#333; margin-left:auto; text-align:right; white-space:nowrap; }

/* Items Table */
.tbl-container { overflow-x: auto; border: 1px solid #aaa; background: #fff; height: calc(100vh - 400px); min-height: 250px; margin-top: 8px; border-radius: 2px; }
.items-tbl { width: max-content; min-width: 100%; border-collapse: collapse; white-space: nowrap; table-layout: fixed; }
.items-tbl thead th { background: #0055a5; color: #fff; font-weight: 600; border-right: 1px solid #004080; border-bottom: 2px solid #003366; padding: 6px 4px; position: sticky; top: 0; z-index: 10; text-align: center; }
.items-tbl tbody td { border-right: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 4px; }
.items-tbl input, .items-tbl select { width: 100%; border: none !important; background: transparent; text-align: right; font-size:12px; height: 22px; }
.items-tbl input.txt-left { text-align: left; }
.items-tbl select { text-align: left; appearance: none; padding-right: 10px; }
.items-tbl td:focus-within { background: #e6f0fa; outline: 1px solid #3b82f6; outline-offset: -1px; }

/* Totals Box */
.totals-grid { display: grid; grid-template-columns: 120px 100px 120px 100px; gap: 4px 10px; align-items: center; }
.totals-grid label { text-align: right; color: #555; margin: 0; font-size: 11px; font-weight: 500; }
.totals-grid input { border: 1px solid #ccc; text-align: right; background: #f9f9f9; font-weight: bold; color: #333; width: 100%; box-sizing: border-box; }
.totals-grid input.grand-total { font-size: 14px; color: #16a34a; height: 26px; }
.totals-grid input.due-amount { color: #dc2626; font-size: 12px; }

/* Footer Buttons */
.footer-btns { display: flex; justify-content: flex-end; gap: 6px; margin-top: 15px; border-top: 1px solid #ccc; padding-top: 10px; }
.footer-btns button { background: #0055a5; color: #fff; border: 1px solid #004080; padding: 4px 16px; cursor: pointer; height: 32px !important; font-size: 13px; border-radius: 2px; font-weight: bold; }
.footer-btns button:hover { background: #0066cc; }

/* Bottom Section */
.bottom-wrap { display: flex; justify-content: space-between; margin-top: 10px; }
.bottom-left { display: flex; flex-direction: column; gap: 6px; width: 420px; }
.bottom-left-row { display: flex; gap: 4px; align-items: flex-start; }
.bottom-left-row label { width: 85px; color: #555; margin: 0; font-weight: 500; }
.bottom-left-row input, .bottom-left-row select, .bottom-left-row textarea { flex: 1; border: 1px solid #ccc; background: #fff; }

.qty-box-wrap { display: flex; gap: 15px; margin-top: 10px; align-items: flex-end; }
.qty-box { width: 90px; height: 45px; background: #fff; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; color: #0055a5; border-radius: 2px; }

input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
</style>

<div class="pur-wrap">
<?= form_open('purchases/edit/' . $purchase->id, ['id' => 'purchaseForm']) ?>
<input type="hidden" name="action" id="formAction" value="update">

<?php if ($flash_e): ?>
<div style="background: #fee2e2; color: #991b1b; padding: 6px; border: 1px solid #f87171; margin-bottom: 10px; border-radius: 2px;">
    <?= $flash_e ?>
</div>
<?php endif; ?>

<div class="tabs-bar">
    <div class="title-tab">Purchase Entry</div>
</div>
<div class="hdr-block">
    <!-- Row 1 -->
    <div class="hdr-row">
        <div class="hdr-field wide">
            <label>Supplier <span style="color:red">*</span></label>
            <?php $full_suppliers_map = []; foreach (($full_suppliers ?? []) as $fs) { $full_suppliers_map[$fs->id] = $fs; } ?>
            <select name="supplier_id" id="supplierSelect" onchange="updateSupplierDetails()" required>
                <option value="">-- Select Supplier --</option>
                <?php foreach ($suppliers as $sid => $sname): ?>
                <?php
                    $fs = $full_suppliers_map[$sid] ?? null;
                    $tip_parts = [];
                    if ($fs && !empty($fs->gst_number)) $tip_parts[] = 'GST: ' . $fs->gst_number;
                    if ($fs) {
                        $addr_bits = array_filter([$fs->address ?? '', $fs->city ?? '', $fs->state ?? '']);
                        if ($addr_bits) $tip_parts[] = implode(', ', $addr_bits);
                    }
                    $tip = htmlspecialchars(implode(' | ', $tip_parts));
                ?>
                <option value="<?= $sid ?>"<?= $tip ? ' title="'.$tip.'"' : '' ?> <?= $purchase->supplier_id == $sid ? 'selected' : '' ?>><?= htmlspecialchars($sname) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="hdr-field">
            <label>GSTIN</label>
            <input type="text" id="supp_gst" readonly style="width:160px;">
        </div>
        <div class="hdr-field">
            <label>Address</label>
            <input type="text" id="supp_address" readonly style="width:250px; font-size: 11px;">
        </div>
    </div>
    <!-- Row 2 -->
    <div class="hdr-row">
        <div class="hdr-field">
            <label>Payment Mode</label>
            <select name="payment_mode">
                <option value="credit" <?= $purchase->payment_mode === 'credit' ? 'selected' : '' ?>>Credit</option>
                <option value="cash" <?= $purchase->payment_mode === 'cash' ? 'selected' : '' ?>>Cash</option>
                <option value="neft" <?= $purchase->payment_mode === 'neft' ? 'selected' : '' ?>>NEFT/RTGS</option>
                <option value="upi" <?= $purchase->payment_mode === 'upi' ? 'selected' : '' ?>>UPI</option>
                <option value="cheque" <?= $purchase->payment_mode === 'cheque' ? 'selected' : '' ?>>Cheque</option>
            </select>
        </div>
        <div class="hdr-field">
            <label>Invoice No.</label>
            <input type="text" name="invoice_no" placeholder="INV-XXX" value="<?= htmlspecialchars($purchase->invoice_no ?? '') ?>">
        </div>
        <div class="hdr-field">
            <label>Invoice Date <span style="color:red">*</span></label>
            <input type="date" name="invoice_date" value="<?= $purchase->invoice_date ?>" required>
        </div>
    </div>
</div>

<!-- Scan Bar -->
<div class="scan-bar">
    <div class="scan-label">Scan</div>
    <div style="position:relative; flex:1; max-width:630px;">
        <div class="scan-input-wrap">
            <input type="text" class="scan-input" id="purSearch" autocomplete="off" placeholder="Type product name, item code or scan barcode...">
        </div>
        <div id="purSearchDrop"></div>
        <small id="purSearchStatus" style="position:absolute; right:5px; top:4px; font-size:10px; color:#666;"></small>
    </div>
    <button type="button" class="btn-grad scan-btn" onclick="triggerPurSearch()">Search</button>
</div>

<!-- Items Table -->
<div class="tbl-container">
    <table class="items-tbl" id="itemsTable">
        <thead>
            <tr>
                <th style="width:20px;"></th>
                <th style="width:30px;">SI</th>
                <th style="width:200px;">Product</th>
                <th style="width:100px;">Item Code</th>
                <th style="width:100px;">EAN Code</th>
                <th style="width:70px;">Basic Cost</th>
                <th style="width:80px;">Discount</th>
                <th style="width:80px;">HSN</th>
                <th style="width:50px;">Tax %</th>
                <th style="width:70px;">Cost Price</th>
                <th style="width:70px;">MRP</th>
                <th style="width:70px;">SP</th>
                <th style="width:60px;">Mgn(CP)</th>
                <th style="width:60px;">Mgn(MRP)</th>
                <th style="width:60px;">Mgn(SP)</th>
                <th style="width:65px;" title="Old Quantity on Hand">Old QOH</th>
                <th style="width:70px;" title="Last Purchase Date">LP Date</th>
                <th style="width:65px;" title="Last Purchase Qty">Last Pur.</th>
                <th style="width:65px;" title="Sales since last purchase">Sales/LP</th>
                <th style="width:60px;">Qty</th>
                <th style="width:80px;">Basic Total</th>
                <th style="width:70px;">Tax Total</th>
                <th style="width:80px;">Cost Value</th>
                <th style="width:80px;">MRP Value</th>
                <th style="width:80px;">Total ₹</th>
                <th style="width:auto;"></th>
            </tr>
        </thead>
        <tbody id="itemsBody"></tbody>
    </table>
</div>

<!-- Bottom Section -->
<div class="bottom-wrap">
    
    <div class="bottom-left">
        <div class="bottom-left-row">
            <label>Notes / Remarks</label>
            <textarea name="notes" placeholder="Optional notes..."><?= htmlspecialchars($purchase->notes ?? '') ?></textarea>
        </div>
        
        <div class="qty-box-wrap">
            <div style="display:flex; flex-direction:column;">
                <label style="color:#555;font-weight:bold;margin-bottom:2px;">Total Qty</label>
                <div class="qty-box" id="rowBadge">0</div>
            </div>
        </div>
    </div>
    
    <div class="totals-grid">
        <label>Total Basic</label>
        <input type="text" id="sum_basic" value="0.00" readonly>
        
        <label>Item Discounts</label>
        <input type="text" id="sum_item_disc" value="0.00" readonly style="color:#dc2626;">
        
        <label>Total Taxable</label>
        <input type="text" id="sum_taxable" value="0.00" readonly>
        
        <label>Total GST</label>
        <input type="text" id="sum_gst" value="0.00" readonly>
        
        <label>Additional Discount</label>
        <input type="number" step="0.01" name="discount" id="discount" value="<?= $purchase->discount ?>" min="0" oninput="recalcTotals()" style="background:#fff;">
        
        <label>Grand Total</label>
        <input type="text" id="sum_total" class="grand-total" value="0.00" readonly>
        
        <label>Amount Paid</label>
        <input type="number" step="0.01" name="paid_amount" id="paid_amount" value="<?= $purchase->paid_amount ?>" min="0" oninput="recalcTotals()" style="background:#fff;">
        
        <label>Amount Due</label>
        <input type="text" id="sum_due" class="due-amount" value="0.00" readonly>
    </div>
</div>

<div class="footer-btns">
    <button type="button" onclick="submitForm('draft')" style="background:#6b7280; border-color:#4b5563;">Save Draft</button>
    <button type="button" onclick="submitForm('update')">Save Purchase &amp; Update Stock</button>
    <button type="button" onclick="window.location='<?= base_url('purchases') ?>'">Close</button>
</div>

<?= form_close() ?>
</div>

<!-- Garment Size x Colour Purchase Matrix Modal -->
<div id="garmentMatrixModal" class="modal" tabindex="-1" role="dialog" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.7); z-index:99999; overflow-y:auto;">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:960px; margin:30px auto;">
        <div class="modal-content border-0 shadow-lg" style="border-radius:10px; overflow:hidden; background:#fff;">
            <div class="modal-header text-white py-3 px-4 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="d-flex align-items-center">
                    <span class="mr-3" style="font-size:1.6rem;">👗</span>
                    <div>
                        <h5 class="modal-title font-weight-bold mb-0 text-white" id="gmmProductTitle">Product Name</h5>
                        <small class="text-white-50" id="gmmProductSubtitle">Style: - | Category: -</small>
                    </div>
                </div>
                <button type="button" class="close text-white" onclick="closeGarmentMatrix()" style="opacity:0.85; font-size:1.8rem; line-height:1; border:none; background:none; cursor:pointer;">&times;</button>
            </div>
            
            <div class="modal-body p-3 bg-light">
                <!-- Top Price & Quick Fill Card -->
                <div class="card p-3 mb-3 bg-white border" style="border-radius:8px;">
                    <div class="row align-items-center">
                        <div class="col-md-2 mb-2 mb-md-0">
                            <label class="small text-muted font-weight-bold mb-1 d-block">Basic Cost (₹)</label>
                            <input type="number" step="0.01" id="gmmBasicCost" class="form-control form-control-sm font-weight-bold text-primary" oninput="gmmRecalcTotals()">
                        </div>
                        <div class="col-md-2 mb-2 mb-md-0">
                            <label class="small text-muted font-weight-bold mb-1 d-block">MRP (₹)</label>
                            <input type="number" step="0.01" id="gmmMrp" class="form-control form-control-sm font-weight-bold" oninput="gmmRecalcTotals()">
                        </div>
                        <div class="col-md-2 mb-2 mb-md-0">
                            <label class="small text-muted font-weight-bold mb-1 d-block">Selling Price (₹)</label>
                            <input type="number" step="0.01" id="gmmSp" class="form-control form-control-sm font-weight-bold" oninput="gmmRecalcTotals()">
                        </div>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <label class="small text-muted font-weight-bold mb-1 d-block">Bulk Fill Size Qty</label>
                            <div class="input-group input-group-sm">
                                <input type="number" id="gmmBulkQty" class="form-control text-center font-weight-bold" placeholder="Qty" min="1" value="5">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-primary btn-sm px-2" onclick="gmmApplyBulkQty()"><i class="fas fa-bolt mr-1"></i>Fill All</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 text-right">
                            <label class="small text-muted font-weight-bold mb-1 d-block">&nbsp;</label>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="gmmClearQuantities()"><i class="fas fa-eraser mr-1"></i>Clear Grid</button>
                        </div>
                    </div>
                </div>

                <!-- Matrix Grid Container -->
                <div class="table-responsive bg-white border rounded shadow-xs" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-bordered table-sm mb-0 text-center" id="gmmMatrixTable">
                        <thead class="bg-dark text-white small" id="gmmThead">
                            <!-- Populated dynamically -->
                        </thead>
                        <tbody id="gmmTbody">
                            <!-- Populated dynamically -->
                        </tbody>
                        <tfoot class="bg-light font-weight-bold small" id="gmmTfoot">
                            <!-- Populated dynamically -->
                        </tfoot>
                    </table>
                </div>

                <div id="gmmNoVariantsAlert" class="alert alert-warning my-2" style="display:none;">
                    <i class="fas fa-exclamation-triangle mr-1"></i> No size/colour variants found for this style. Please generate variants in Item Master first.
                </div>
            </div>

            <div class="modal-footer bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="text-muted small mr-2">Total Pieces to Add:</span>
                    <span class="badge badge-success px-3 py-1 font-weight-bold" style="font-size:1.05rem;" id="gmmGrandTotalBadge">0 pcs</span>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm px-3 mr-2" onclick="closeGarmentMatrix()">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 font-weight-bold shadow-sm" id="btnGmmAddItems" onclick="gmmSubmitMatrix()" disabled>
                        <i class="fas fa-plus-circle mr-1"></i> Add Items to Purchase Table
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const fullSuppliers = <?= json_encode($full_suppliers ?? []) ?>;
const hsnMasterList = <?= json_encode($hsn_master_list ?? []) ?>;
let hsnOptionsHtml = '<option value="">-</option>';
hsnMasterList.forEach(h => {
    hsnOptionsHtml += `<option value="${h.id}" data-gst="${h.igst_percent}">${h.hsn_code}</option>`;
});

function updateSupplierDetails() {
    const selId = document.getElementById('supplierSelect').value;
    const gstInp = document.getElementById('supp_gst');
    const addrInp = document.getElementById('supp_address');
    
    gstInp.value = '';
    addrInp.value = '';
    
    if (selId && fullSuppliers && fullSuppliers.length) {
        const supp = fullSuppliers.find(s => s.id == selId);
        if (supp) {
            gstInp.value = supp.gst_number || '';
            const addrParts = [];
            if (supp.address) addrParts.push(supp.address);
            if (supp.city) addrParts.push(supp.city);
            if (supp.state) addrParts.push(supp.state);
            addrInp.value = addrParts.join(', ');
        }
    }
}

let rowCount = 0;
let searchIdx = -1;
let searchTimer = null;

const purSearchInput  = document.getElementById('purSearch');
const purSearchDrop   = document.getElementById('purSearchDrop');
const purSearchStatus = document.getElementById('purSearchStatus');

/* ── Search bar events ── */
purSearchInput.addEventListener('input', function() {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 1) { hideDrop(); return; }
    searchTimer = setTimeout(() => fetchProducts(q), 220);
});

purSearchInput.addEventListener('keydown', function(e) {
    const items = purSearchDrop.querySelectorAll('.psd-item');
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        searchIdx = Math.min(searchIdx + 1, items.length - 1);
        highlightDrop(items);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        searchIdx = Math.max(searchIdx - 1, 0);
        highlightDrop(items);
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const active = purSearchDrop.querySelector('.psd-item.active');
        if (active) active.click();
        else if (items.length === 1) items[0].click();
        else triggerPurSearch();
    } else if (e.key === 'Escape') {
        hideDrop();
        purSearchInput.placeholder = 'Type product name, item code or scan barcode...';
    }
});

document.addEventListener('click', function(e) {
    if (!purSearchDrop.contains(e.target) && e.target !== purSearchInput) hideDrop();
});

// Prevent form submission on enter key
document.getElementById('purchaseForm').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        if (e.target.name === 'qty[]') {
            purSearchInput.focus();
        }
    }
});

function highlightDrop(items) {
    items.forEach((el, i) => el.classList.toggle('active', i === searchIdx));
    if (items[searchIdx]) items[searchIdx].scrollIntoView({ block:'nearest' });
}

function hideDrop() {
    purSearchDrop.style.display = 'none';
    searchIdx = -1;
}

function triggerPurSearch() {
    const q = purSearchInput.value.trim();
    if (q) fetchProducts(q);
}

function fetchProducts(q) {
    purSearchStatus.textContent = 'Searching…';
    fetch('<?= base_url('products/search_ajax') ?>?q=' + encodeURIComponent(q) + '&limit=12')
        .then(r => r.json())
        .then(data => {
            const products = data.products || data;
            if (!products.length) {
                purSearchDrop.innerHTML = '<div class="psd-item text-muted">No products found</div>';
                purSearchDrop.style.display = 'block';
                purSearchStatus.textContent = '';
                return;
            }
            searchIdx = -1;
            purSearchDrop.innerHTML = products.map(p => {
                const stock = parseFloat(p.stock_qty || 0);
                const stockColor = stock <= 0 ? '#ef4444' : stock < 5 ? '#f59e0b' : '#16a34a';
                const isGarment = String(p.is_garment || '0') === '1' || (p.variant_count && parseInt(p.variant_count) > 0);
                return `<div class="psd-item d-flex justify-content-between align-items-center" data-product='${JSON.stringify(p).replace(/'/g,"&#39;")}'>
                    <div style="flex:1;">
                        <div class="psi-name font-weight-bold">
                            ${p.name}
                            ${isGarment ? `<span class="badge badge-info" style="font-size:10px; margin-left:4px;"><i class="fas fa-tshirt mr-1"></i>Garment</span>` : ''}
                        </div>
                        <div class="psi-code text-muted" style="font-size:11px;">
                            ${p.item_code || ''} ${p.barcode ? '· ' + p.barcode : ''}
                            ${p.style_code ? `· Style: <strong class="text-secondary">${p.style_code}</strong>` : ''}
                        </div>
                    </div>
                    <div class="psi-meta text-right" style="margin-right:10px; font-size:11px;">
                        <span style="color:${stockColor};font-weight:600;">Stock: ${stock}</span><br>
                        Pur: ₹${parseFloat(p.purchase_price||0).toFixed(2)} &nbsp; MRP: ₹${parseFloat(p.mrp||0).toFixed(2)}<br>
                        GST: ${p.gst_percent||0}%
                    </div>
                    ${isGarment ? `
                    <div>
                        <button type="button" class="btn btn-sm btn-primary py-1 px-2 font-weight-bold btn-open-matrix" data-pid="${p.id}" style="font-size:11px; white-space:nowrap;">
                            <i class="fas fa-th mr-1"></i> Open Matrix
                        </button>
                    </div>` : ''}
                </div>`;
            }).join('');
            purSearchDrop.style.display = 'block';
            purSearchStatus.textContent = products.length + ' result(s)';
            purSearchDrop.querySelectorAll('.psd-item').forEach(el => {
                el.addEventListener('click', function(e) {
                    if (e.target.closest('.btn-open-matrix')) {
                        const pid = e.target.closest('.btn-open-matrix').dataset.pid;
                        hideDrop();
                        purSearchInput.value = '';
                        purSearchStatus.textContent = '';
                        openGarmentMatrix(pid);
                        return;
                    }
                    const p = JSON.parse(this.dataset.product);
                    selectProduct(p);
                });
            });
        })
        .catch(() => { purSearchStatus.textContent = 'Search error'; });
}

function selectProduct(p) {
    hideDrop();
    purSearchInput.value = '';
    purSearchStatus.textContent = '';
    
    if (String(p.is_garment || '0') === '1' || (p.variant_count && parseInt(p.variant_count) > 0)) {
        openGarmentMatrix(p.id);
    } else {
        addRow(p);
    }
}

function updateSiNos() {
    const rows = document.querySelectorAll('#itemsBody tr');
    rows.forEach((r, idx) => {
        const td = r.querySelector('.si-no');
        if(td) td.textContent = idx + 1;
    });
}

function hsnChanged(rid) {
    const hsnSel = document.getElementById('hsn_' + rid);
    const gstSel = document.getElementById('gst_' + rid);
    if (!hsnSel || !gstSel) return;
    
    if (hsnSel.selectedIndex > 0) {
        const gstVal = hsnSel.options[hsnSel.selectedIndex].getAttribute('data-gst');
        if (gstVal !== null) {
            let found = false;
            for (let i = 0; i < gstSel.options.length; i++) {
                if (parseFloat(gstSel.options[i].value) === parseFloat(gstVal)) {
                    gstSel.selectedIndex = i;
                    found = true; break;
                }
            }
            if(!found) {
                const opt = document.createElement('option');
                opt.value = gstVal;
                opt.text = gstVal + '%';
                gstSel.add(opt);
                gstSel.value = gstVal;
            }
            calcRow(rid);
        }
    }
}

function addRow(p) {
    if (!p) return;
    rowCount++;
    const rid = rowCount;
    const gstOpts = [0,3,5,12,18,40].map(g=>`<option value="${g}">${g}%</option>`).join('');
    
    let pid = p.id;
    let name = p.name;
    let code = p.item_code||'';
    let hsnId = p.hsn_id || '';
    let cost = parseFloat(p.purchase_price || p.price || 0).toFixed(2);
    let mrp = parseFloat(p.mrp||0).toFixed(2);
    let sp = parseFloat(p.selling_price||0).toFixed(2);
    let gst = parseFloat(p.gst_percent||0).toFixed(2);
    let stock = parseFloat(p.stock_qty||0).toFixed(2);
    let initQty = parseFloat(p.qty || 1);
    let barcode = p.barcode || p.ean_code || p.pcs_barcode || '';

    const row = `
    <tr id="row_${rid}">
        <td style="text-align:center;color:#dc2626;cursor:pointer;font-weight:bold;" onclick="removeRow(${rid})">x</td>
        <td class="si-no text-center" style="font-weight:bold;"></td>
        <td>
            <input type="hidden" name="product_id[]" id="pid_${rid}" value="${pid}">
            <input type="hidden" name="variant_id[]" id="vid_${rid}" value="${p.variant_id || ''}">
            <input type="hidden" name="variant_sku[]" id="vsku_${rid}" value="${(p.variant_sku || '').replace(/"/g, '&quot;')}">
            <input type="hidden" name="size_name[]" id="vsiz_${rid}" value="${(p.size_name || '').replace(/"/g, '&quot;')}">
            <input type="hidden" name="colour_name[]" id="vcol_${rid}" value="${(p.colour_name || '').replace(/"/g, '&quot;')}">
            <input type="hidden" name="barcode[]" value="${barcode.replace(/"/g, '&quot;')}">
            <div id="pname_${rid}" class="txt-left" style="padding:2px 4px; overflow:hidden; text-overflow:ellipsis; font-weight:bold; color:#1e40af;">
                ${name}
                ${p.size_name ? `<span class="badge badge-info" style="font-size:10px; margin-left:4px; font-weight:600;"><i class="fas fa-tag mr-1"></i>${p.colour_name || ''} / ${p.size_name}</span>` : ''}
                ${(String(p.is_garment||'0') === '1' || p.variant_id) ? `<button type="button" class="btn btn-outline-primary btn-sm py-0 px-1 ml-1" style="font-size:9px;" onclick="openGarmentMatrix(${pid})" title="Open Size Matrix"><i class="fas fa-th mr-1"></i>Matrix</button>` : ''}
            </div>
            <div id="pcode_${rid}" class="txt-left" style="padding:0 4px; font-size:10px; color:#666;">
                ${code} ${p.variant_sku ? `· SKU: ${p.variant_sku}` : ''}
            </div>
        </td>
        <td><input type="text" value="${p.item_code||'-'}" readonly style="color:#555;"></td>
        <td><input type="text" value="${barcode || '-'}" readonly style="color:#555; font-weight:600;"></td>
        <td>
            <input type="number" name="price[]" id="price_${rid}" value="${cost}" step="0.01" oninput="calcRow(${rid})">
        </td>
        <td>
            <div style="display:flex; border:1px solid #7f9db9; background:#fff;">
                <input type="number" step="0.01" name="disc_val[]" id="disc_${rid}" value="0" oninput="calcRow(${rid})" style="flex:1; border:none!important; outline:none; height:16px;">
                <select name="disc_type[]" id="dtype_${rid}" style="width:30px; border:none!important; background:#f0f0f0; border-left:1px solid #7f9db9!important; height:16px;" onchange="calcRow(${rid})">
                    <option value="%">%</option>
                    <option value="₹">₹</option>
                </select>
            </div>
            <input type="hidden" name="discount_percent[]" id="dpercent_${rid}" value="0">
            <input type="hidden" name="discount_amount[]"  id="damount_${rid}"  value="0">
        </td>
        <td>
            <select name="hsn_id[]" id="hsn_${rid}" onchange="hsnChanged(${rid})">
                ${hsnOptionsHtml}
            </select>
        </td>
        <td>
            <select name="gst_percent[]" id="gst_${rid}" onchange="calcRow(${rid})" style="text-align:right;">
                ${gstOpts}
            </select>
        </td>
        <td><input type="number" id="cp_${rid}" value="${cost}" readonly></td>
        <td>
            <input type="number" step="0.01" name="mrp_visible[]" id="mrp_vis_${rid}" value="${mrp}" oninput="document.getElementById('mrp_${rid}').value=this.value; calcRow(${rid})">
            <input type="hidden" name="mrp[]" id="mrp_${rid}" value="${mrp}">
        </td>
        <td>
            <input type="number" step="0.01" name="sp_visible[]" id="sp_vis_${rid}" value="${sp}" oninput="document.getElementById('sp_${rid}').value=this.value; calcRow(${rid})">
            <input type="hidden" name="selling_price[]" id="sp_${rid}" value="${sp}">
        </td>
        <td><input type="text" id="margin_cp_${rid}" style="color:#16a34a;font-weight:bold;" readonly></td>
        <td><input type="text" id="margin_mrp_${rid}" style="color:#16a34a;font-weight:bold;" readonly></td>
        <td><input type="text" id="margin_sp_${rid}" style="color:#16a34a;font-weight:bold;" readonly></td>
        <td><input type="text" id="qoh_${rid}" value="${stock}" readonly style="color:#555;"></td>
        <td><input type="text" id="lp_date_${rid}" value="-" readonly style="color:#0284c7;font-size:9px;"></td>
        <td><input type="text" id="lp_qty_${rid}" value="-" readonly style="color:#0284c7;"></td>
        <td><input type="text" id="sales_lp_${rid}" value="-" readonly style="color:#ea580c;"></td>
        <td>
            <input type="number" name="qty[]" id="qty_${rid}" value="1" min="0.01" step="0.01" oninput="calcRow(${rid})">
        </td>
        <td><input type="text" id="basic_total_${rid}" readonly></td>
        <td><input type="text" name="gst_amount[]" id="gstamt_${rid}" readonly></td>
        <td><input type="text" id="cost_value_${rid}" readonly></td>
        <td><input type="text" id="mrp_value_${rid}" readonly></td>
        <td>
            <input type="text" name="total_amount[]" id="total_${rid}" readonly style="font-weight:bold;">
            <input type="hidden" name="taxable_amount[]" id="taxable_${rid}" value="0">
            <input type="hidden" name="batch_no[]" id="batchno_${rid}" value="">
        </td>
        <td></td>
    </tr>`;
    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', row);
    
    // Set HSN and GST
    const hsnSel = document.getElementById('hsn_' + rid);
    if (hsnId) hsnSel.value = hsnId;
    
    const gstSel = document.getElementById('gst_' + rid);
    let gstFound = false;
    for (let i = 0; i < gstSel.options.length; i++) {
        if (parseFloat(gstSel.options[i].value) === parseFloat(gst)) { 
            gstSel.selectedIndex = i; gstFound = true; break; 
        }
    }
    if(!gstFound) {
        const opt = document.createElement('option');
        opt.value = gst; opt.text = gst + '%';
        gstSel.add(opt); gstSel.value = gst;
    }

    calcRow(rid);
    updateSiNos();

    setTimeout(() => {
        const q = document.getElementById('qty_' + rid);
        if (q) { q.focus(); q.select(); }
    }, 80);
    
    // Fetch History Data via AJAX
    fetch('<?= base_url('purchases/get_product_history_ajax') ?>?product_id=' + pid)
    .then(r => r.json())
    .then(data => {
        document.getElementById('lp_date_' + rid).value = data.last_purchase_date || '-';
        document.getElementById('lp_qty_' + rid).value = data.last_purchase_qty > 0 ? data.last_purchase_qty : '-';
        document.getElementById('sales_lp_' + rid).value = data.sales_since_lp > 0 ? data.sales_since_lp : '-';
    }).catch(console.error);
}

function calcRow(rid) {
    const qty   = parseFloat(document.getElementById('qty_'   + rid).value) || 0;
    const rate = parseFloat(document.getElementById('price_' + rid).value) || 0; 
    const mrp = parseFloat(document.getElementById('mrp_vis_' + rid).value) || 0;
    const sp = parseFloat(document.getElementById('sp_vis_' + rid).value) || 0;
    
    const discVal  = parseFloat(document.getElementById('disc_'  + rid).value) || 0;
    const discType = document.getElementById('dtype_' + rid).value;
    
    let unitDisc = 0;
    if (discType === '%') {
        unitDisc = (rate * Math.min(discVal, 100)) / 100;
    } else {
        unitDisc = Math.min(discVal, rate);
    }
    
    const cp_base = rate - unitDisc; 
    const gstPct = parseFloat(document.getElementById('gst_' + rid).value) || 0;
    const unitGst = cp_base * (gstPct / 100);
    
    const cp = cp_base + unitGst;
    
    const basicTotal = qty * rate;
    const lineDisc = qty * unitDisc;
    const costValue = qty * cp_base; 
    const taxTotal = qty * unitGst;
    const mrpValue = qty * mrp;
    const totalAmt = costValue + taxTotal;
    
    let cpMargin = 0;
    if (cp > 0) cpMargin = ((sp - cp) / cp) * 100;
    
    let mrpMargin = 0;
    if (mrp > 0) mrpMargin = ((mrp - cp) / mrp) * 100;
    
    let spMargin = 0;
    if (sp > 0) spMargin = ((sp - cp) / sp) * 100;
    
    document.getElementById('margin_cp_' + rid).value = cpMargin.toFixed(2) + '%';
    document.getElementById('margin_mrp_' + rid).value = mrpMargin.toFixed(2) + '%';
    document.getElementById('margin_sp_' + rid).value = spMargin.toFixed(2) + '%';
    
    document.getElementById('cp_' + rid).value = cp.toFixed(2);
    document.getElementById('basic_total_' + rid).value = basicTotal.toFixed(2);
    document.getElementById('gstamt_' + rid).value = taxTotal.toFixed(2);
    document.getElementById('cost_value_' + rid).value = costValue.toFixed(2);
    document.getElementById('mrp_value_' + rid).value = mrpValue.toFixed(2);
    document.getElementById('total_' + rid).value = totalAmt.toFixed(2);
    
    document.getElementById('taxable_' + rid).value = costValue.toFixed(2);
    document.getElementById('damount_' + rid).value = lineDisc.toFixed(2);
    
    let discPct = rate > 0 ? (unitDisc / rate) * 100 : 0;
    document.getElementById('dpercent_' + rid).value = discPct.toFixed(4);
    
    recalcTotals();
}

function removeRow(rid) {
    const r = document.getElementById('row_' + rid);
    if (r) r.remove();
    updateSiNos();
    recalcTotals();
}

function updateRowBadge() {
    let q = 0;
    document.querySelectorAll('input[name="qty[]"]').forEach(i => q += parseFloat(i.value) || 0);
    document.getElementById('rowBadge').textContent = q.toFixed(0);
}

function recalcTotals() {
    let tBasic = 0, tDisc = 0, tTaxable = 0, tGst = 0, tTotal = 0;
    document.querySelectorAll('#itemsBody tr').forEach(function(row) {
        const rid = row.id.replace('row_', '');
        tBasic   += parseFloat(document.getElementById('basic_total_'   + rid)?.value) || 0;
        tDisc    += parseFloat(document.getElementById('damount_' + rid)?.value) || 0;
        tTaxable += parseFloat(document.getElementById('taxable_' + rid)?.value) || 0;
        tGst     += parseFloat(document.getElementById('gstamt_'  + rid)?.value) || 0;
        tTotal   += parseFloat(document.getElementById('total_'   + rid)?.value) || 0;
    });

    const addDisc     = parseFloat(document.getElementById('discount').value) || 0;
    const grandTotal  = tTotal - addDisc;
    const paid        = parseFloat(document.getElementById('paid_amount').value) || 0;
    const due         = grandTotal - paid;

    document.getElementById('sum_basic').value      = tBasic.toFixed(2);
    document.getElementById('sum_item_disc').value  = tDisc.toFixed(2);
    document.getElementById('sum_taxable').value    = tTaxable.toFixed(2);
    document.getElementById('sum_gst').value        = tGst.toFixed(2);
    document.getElementById('sum_total').value      = grandTotal.toFixed(2);
    
    document.getElementById('sum_due').value        = due.toFixed(2);
    document.getElementById('sum_due').style.color  = due > 0 ? '#dc2626' : '#16a34a';
    
    updateRowBadge();
}

function submitForm(action) {
    document.getElementById('formAction').value = action;
    const rows = document.querySelectorAll('#itemsBody tr');
    if(rows.length === 0) {
        alert('Please add at least one item.');
        return;
    }
    document.getElementById('purchaseForm').submit();
}

// ─── Garment Size x Colour Matrix Purchase Engine ───────────────────────
let gmmCurrentData = null;

function openGarmentMatrix(productId) {
    if (!productId) return;
    
    const modal = document.getElementById('garmentMatrixModal');
    modal.style.display = 'block';
    document.getElementById('gmmProductTitle').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Loading Garment Matrix...';
    document.getElementById('gmmProductSubtitle').textContent = 'Please wait while sizes and colours are retrieved...';
    document.getElementById('gmmThead').innerHTML = '';
    document.getElementById('gmmTbody').innerHTML = '<tr><td colspan="10" class="py-4 text-center text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i></td></tr>';
    document.getElementById('gmmTfoot').innerHTML = '';
    document.getElementById('btnGmmAddItems').disabled = true;
    document.getElementById('gmmGrandTotalBadge').textContent = '0 pcs';
    document.getElementById('gmmNoVariantsAlert').style.display = 'none';

    fetch('<?= base_url("purchases/get_variants_matrix_ajax") ?>?product_id=' + productId)
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status + ' ' + r.statusText);
            return r.json();
        })
        .then(res => {
            if (!res.success) {
                alert(res.message || 'Error loading garment matrix.');
                closeGarmentMatrix();
                return;
            }
            gmmCurrentData = res;
            renderGarmentMatrix(res);
        })
        .catch(err => {
            console.error('Matrix load error:', err);
            alert('Error loading matrix: ' + (err.message || 'Server connection error.'));
            closeGarmentMatrix();
        });
}

function closeGarmentMatrix() {
    const modal = document.getElementById('garmentMatrixModal');
    if (modal) modal.style.display = 'none';
    if (purSearchInput) purSearchInput.focus();
}

function renderGarmentMatrix(data) {
    const p = data.product;
    const sizes = data.sizes;
    const colours = data.colours;
    const matrix = data.matrix;

    document.getElementById('gmmProductTitle').textContent = p.name;
    document.getElementById('gmmProductSubtitle').textContent = 
        'Style: ' + (p.style_code || p.item_code) + 
        (p.garment_category ? ' | Category: ' + p.garment_category : '') + 
        (p.gender ? ' | ' + p.gender : '') +
        (p.hsn_code ? ' | HSN: ' + p.hsn_code + ' (' + p.gst_percent + '%)' : '');

    document.getElementById('gmmBasicCost').value = parseFloat(p.basic_cost || 0).toFixed(2);
    document.getElementById('gmmMrp').value = parseFloat(p.mrp || 0).toFixed(2);
    document.getElementById('gmmSp').value = parseFloat(p.selling_price || 0).toFixed(2);

    if (!sizes.length || !colours.length || !data.count) {
        document.getElementById('gmmThead').innerHTML = '';
        document.getElementById('gmmTbody').innerHTML = '';
        document.getElementById('gmmTfoot').innerHTML = '';
        document.getElementById('gmmNoVariantsAlert').style.display = 'block';
        return;
    }
    document.getElementById('gmmNoVariantsAlert').style.display = 'none';

    let theadHtml = '<tr><th style="width: 140px; text-align: left; padding-left: 12px;">Colour / Swatch</th>';
    sizes.forEach(s => {
        theadHtml += `<th style="min-width: 80px;" class="text-center">${s.name} ${s.measurement ? `<br><small class="text-white-50" style="font-size:9px;">${s.measurement}</small>` : ''}</th>`;
    });
    theadHtml += '<th style="width: 100px;" class="text-center bg-secondary">Colour Total</th></tr>';
    document.getElementById('gmmThead').innerHTML = theadHtml;

    let tbodyHtml = '';
    colours.forEach(c => {
        tbodyHtml += `<tr data-colour="${c.name}">
            <td style="text-align: left; vertical-align: middle; padding-left: 12px; font-weight: 600;">
                <span class="d-inline-block rounded-circle mr-2 border" style="width: 14px; height: 14px; background: ${c.hex || '#808080'}; vertical-align: middle;"></span>
                ${c.name}
            </td>`;
        
        sizes.forEach(s => {
            const v = matrix[c.name] && matrix[c.name][s.name];
            if (v) {
                tbodyHtml += `<td class="p-1" style="vertical-align: middle;">
                    <input type="number" min="0" step="1" 
                           class="form-control form-control-sm text-center font-weight-bold gmm-qty-input"
                           data-col="${c.name}" data-siz="${s.name}" data-var-id="${v.id}"
                           data-sku="${v.variant_sku}" data-barcode="${v.barcode}" data-stock="${v.stock_qty}"
                           placeholder="0" style="height: 32px; font-size: 14px;">
                    <div class="text-muted small mt-1" style="font-size: 10px;">Stk: <strong class="${v.stock_qty > 0 ? 'text-success' : 'text-danger'}">${v.stock_qty}</strong></div>
                </td>`;
            } else {
                tbodyHtml += `<td class="bg-light text-muted" style="vertical-align: middle; font-size: 11px;">—</td>`;
            }
        });

        tbodyHtml += `<td style="vertical-align: middle;" class="text-center bg-light font-weight-bold text-primary gmm-row-total">0 pcs</td></tr>`;
    });
    document.getElementById('gmmTbody').innerHTML = tbodyHtml;

    let tfootHtml = '<tr><td style="text-align: left; padding-left: 12px;">Size Totals:</td>';
    sizes.forEach(s => {
        tfootHtml += `<td class="text-center gmm-col-total" data-siz="${s.name}">0 pcs</td>`;
    });
    tfootHtml += '<td class="text-center font-weight-bold text-success bg-white" id="gmmFooterGrandTotal">0 pcs</td></tr>';
    document.getElementById('gmmTfoot').innerHTML = tfootHtml;

    document.querySelectorAll('.gmm-qty-input').forEach(inp => {
        inp.addEventListener('input', gmmRecalcTotals);
        inp.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const all = Array.from(document.querySelectorAll('.gmm-qty-input'));
                const idx = all.indexOf(this);
                if (idx >= 0 && idx < all.length - 1) {
                    all[idx + 1].focus();
                    all[idx + 1].select();
                } else {
                    document.getElementById('btnGmmAddItems').focus();
                }
            }
        });
    });

    gmmRecalcTotals();

    setTimeout(() => {
        const first = document.querySelector('.gmm-qty-input');
        if (first) { first.focus(); first.select(); }
    }, 150);
}

function gmmRecalcTotals() {
    let grandTotal = 0;

    document.querySelectorAll('#gmmTbody tr').forEach(tr => {
        let rowSum = 0;
        tr.querySelectorAll('.gmm-qty-input').forEach(inp => {
            const val = parseFloat(inp.value) || 0;
            if (val > 0) rowSum += val;
        });
        const rTot = tr.querySelector('.gmm-row-total');
        if (rTot) rTot.textContent = rowSum + ' pcs';
    });

    if (gmmCurrentData && gmmCurrentData.sizes) {
        gmmCurrentData.sizes.forEach(s => {
            let colSum = 0;
            document.querySelectorAll(`.gmm-qty-input[data-siz="${s.name}"]`).forEach(inp => {
                const val = parseFloat(inp.value) || 0;
                if (val > 0) colSum += val;
            });
            const cTot = document.querySelector(`.gmm-col-total[data-siz="${s.name}"]`);
            if (cTot) cTot.textContent = colSum + ' pcs';
            grandTotal += colSum;
        });
    }

    const ft = document.getElementById('gmmFooterGrandTotal');
    if (ft) ft.textContent = grandTotal + ' pcs';

    const bd = document.getElementById('gmmGrandTotalBadge');
    if (bd) bd.textContent = grandTotal + ' pcs';

    const btn = document.getElementById('btnGmmAddItems');
    if (btn) btn.disabled = grandTotal <= 0;
}

function gmmApplyBulkQty() {
    const bulk = parseFloat(document.getElementById('gmmBulkQty').value) || 0;
    if (bulk < 0) return;
    document.querySelectorAll('.gmm-qty-input').forEach(inp => {
        inp.value = bulk > 0 ? bulk : '';
    });
    gmmRecalcTotals();
}

function gmmClearQuantities() {
    document.querySelectorAll('.gmm-qty-input').forEach(inp => {
        inp.value = '';
    });
    gmmRecalcTotals();
}

function gmmSubmitMatrix() {
    if (!gmmCurrentData || !gmmCurrentData.product) return;

    const p = gmmCurrentData.product;
    const cost = parseFloat(document.getElementById('gmmBasicCost').value) || p.basic_cost;
    const mrp = parseFloat(document.getElementById('gmmMrp').value) || p.mrp;
    const sp = parseFloat(document.getElementById('gmmSp').value) || p.selling_price;

    let addedCount = 0;
    document.querySelectorAll('.gmm-qty-input').forEach(inp => {
        const qty = parseFloat(inp.value) || 0;
        if (qty > 0) {
            const vid = inp.getAttribute('data-var-id');
            const sku = inp.getAttribute('data-sku');
            const barcode = inp.getAttribute('data-barcode');
            const col = inp.getAttribute('data-col');
            const siz = inp.getAttribute('data-siz');
            const stk = inp.getAttribute('data-stock');

            addRow({
                id: p.id,
                name: p.name,
                item_code: p.item_code,
                barcode: barcode || p.item_code,
                variant_id: vid,
                variant_sku: sku,
                size_name: siz,
                colour_name: col,
                purchase_price: cost,
                mrp: mrp,
                selling_price: sp,
                gst_percent: p.gst_percent,
                hsn_id: p.hsn_id,
                stock_qty: stk,
                qty: qty
            });
            addedCount++;
        }
    });

    closeGarmentMatrix();

    if (addedCount > 0) {
        if (purSearchStatus) {
            purSearchStatus.innerHTML = `<span class="text-success font-weight-bold"><i class="fas fa-check-circle"></i> Added ${addedCount} variant row(s) to purchase.</span>`;
            setTimeout(() => { purSearchStatus.textContent = ''; }, 4000);
        }
    }
}

// Focus the search bar on page load
setTimeout(() => purSearchInput.focus(), 300);

// Pre-fill Edit Data
window.addEventListener('DOMContentLoaded', () => {
    updateSupplierDetails();
    const purchaseItems = <?= json_encode($items ?? []) ?>;
    const allProducts = <?= json_encode($products ?? []) ?>;
    
    purchaseItems.forEach(item => {
        const prod = allProducts.find(p => p.id == item.product_id);
        let pObj = {
            id: item.product_id,
            name: item.product_name,
            item_code: prod ? prod.item_code : '',
            barcode: item.variant_sku ? (item.variant_barcode || (prod ? prod.barcode : '')) : (prod ? prod.barcode : ''),
            variant_id: item.variant_id || '',
            variant_sku: item.variant_sku || '',
            size_name: item.size_name || '',
            colour_name: item.colour_name || '',
            is_garment: (item.variant_id || (prod && prod.is_garment)) ? 1 : 0,
            hsn_id: prod ? prod.hsn_id : '',
            purchase_price: item.price,
            mrp: prod ? parseFloat(prod.mrp) || 0 : 0,
            selling_price: prod ? parseFloat(prod.selling_price) || 0 : 0,
            gst_percent: item.gst_percent,
            stock_qty: prod ? prod.stock_qty : 0
        };
        
        addRow(pObj);
        
        const rid = rowCount;
        document.getElementById('qty_' + rid).value = item.qty;
        
        if (parseFloat(item.discount_percent) > 0) {
            document.getElementById('dtype_' + rid).value = '%';
            document.getElementById('disc_' + rid).value = parseFloat(item.discount_percent);
        } else if (parseFloat(item.discount_amount) > 0) {
            document.getElementById('dtype_' + rid).value = '₹';
            document.getElementById('disc_' + rid).value = parseFloat(item.discount_amount);
        }
        
        calcRow(rid);
    });
});
</script>
