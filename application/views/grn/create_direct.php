<?php
$flash_e = $this->session->flashdata('error');
?>
<style>
/* ── GRN desktop-style form ── */
body.sb-layout .container-fluid { padding-top: 54px; }
.grn-wrap { font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; padding: 10px; background: #eef2f5; min-height: calc(100vh - 60px); }
.grn-wrap input, .grn-wrap select, .grn-wrap button { font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; height: 24px; padding: 2px 6px; border: 1px solid #ccc; border-radius: 2px; }
.grn-wrap input:focus, .grn-wrap select:focus { outline: none; border-color: #3b82f6; }
.grn-wrap input[readonly] { background: #f0f0f0; color: #333; }

/* Top Header Block */
.hdr-block { background: #fff; border: 1px solid #ccc; padding: 8px; display: flex; flex-direction: column; gap: 6px; margin-bottom: 5px; border-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
.hdr-row { display: flex; gap: 15px; align-items: center; }
.hdr-field { display: flex; align-items: center; gap: 4px; }
.hdr-field label { width: 75px; text-align: right; color: #555; margin: 0; font-weight: 500; }
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
#grnSearchDropdown { z-index:9999; max-height:280px; overflow-y:auto; display:none; position:absolute; top:32px; left:0; width:100%; background:#fff; border:1px solid #ccc; box-shadow: 2px 2px 5px rgba(0,0,0,.2); }
#grnSearchDropdown .psd-item { padding:6px 8px; cursor:pointer; border-bottom:1px solid #eee; display:flex; align-items:center; gap:10px; }
#grnSearchDropdown .psd-item:hover { background:#e6f0fa; }
#grnSearchDropdown .psd-item .psi-name { font-weight:bold; font-size:12px; color:#1e40af; }
#grnSearchDropdown .psd-item .psi-code { font-size:11px; color:#666; }

/* Items Table */
.tbl-container { overflow-x: auto; border: 1px solid #aaa; background: #fff; height: calc(100vh - 400px); min-height: 250px; margin-top: 8px; border-radius: 2px; }
.items-tbl { width: 100%; border-collapse: collapse; white-space: nowrap; table-layout: fixed; }
.items-tbl thead th { background: #0055a5; color: #fff; font-weight: 600; border-right: 1px solid #004080; border-bottom: 2px solid #003366; padding: 6px 4px; position: sticky; top: 0; z-index: 10; text-align: center; }
.items-tbl tbody td { border-right: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 4px; }
.items-tbl input, .items-tbl select { width: 100%; border: none !important; background: transparent; text-align: right; font-size:12px; height: 22px; }
.items-tbl input.txt-left { text-align: left; }
.items-tbl td:focus-within { background: #e6f0fa; outline: 1px solid #3b82f6; outline-offset: -1px; }
.items-tbl select { appearance: none; padding-right: 15px; }
.margin-field { color: #16a34a; font-weight: bold; }

/* Bottom Section */
.bottom-wrap { display: flex; justify-content: space-between; margin-top: 10px; }
.bottom-left { display: flex; flex-direction: column; gap: 6px; width: 420px; }
.bottom-left-row { display: flex; gap: 4px; align-items: center; }
.bottom-left-row label { width: 85px; color: #555; margin: 0; font-weight: 500; }
.bottom-left-row input, .bottom-left-row select { flex: 1; border: 1px solid #ccc; background: #fff; }

.qty-box-wrap { display: flex; gap: 15px; margin-top: 10px; align-items: flex-end; }
.qty-box { width: 90px; height: 45px; background: #fff; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; color: #0055a5; border-radius: 2px; }
.btn-blue { background: #0055a5; color: #fff; border: 1px solid #004080; border-radius: 2px; cursor: pointer; height: 28px !important; margin-bottom: 4px; width: 220px; font-weight: bold; }
.btn-blue:hover { background: #0066cc; }

/* Totals Box */
.totals-grid { display: grid; grid-template-columns: 120px 100px 120px 100px; gap: 4px 10px; align-items: center; }
.totals-grid label { text-align: right; color: #555; margin: 0; font-size: 11px; font-weight: 500; }
.totals-grid input { border: 1px solid #ccc; text-align: right; background: #f9f9f9; font-weight: bold; color: #333; }
.totals-grid .grand-total input { font-size: 14px; font-weight: bold; height: 26px; color: #16a34a; }

/* Footer Buttons */
.footer-btns { display: flex; justify-content: flex-end; gap: 6px; margin-top: 15px; border-top: 1px solid #ccc; padding-top: 10px; }
.footer-btns button { background: #0055a5; color: #fff; border: 1px solid #004080; padding: 4px 16px; cursor: pointer; height: 32px !important; font-size: 13px; border-radius: 2px; font-weight: bold; }
.footer-btns button:hover { background: #0066cc; }

input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }

/* PLU picker overlay */
#grnPluModalOverlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:10000; align-items:center; justify-content:center; }
.grn-plu-box { background:#fff; border-radius:4px; width:600px; max-width:92vw; max-height:70vh; display:flex; flex-direction:column; box-shadow:0 4px 20px rgba(0,0,0,.25); }
.grn-plu-header { background:#0055a5; color:#fff; padding:10px 14px; font-weight:bold; font-size:14px; border-radius:4px 4px 0 0; }
.grn-plu-hint { padding:8px 14px 0; font-size:11px; color:#666; }
.grn-plu-list { padding:10px 14px; overflow-y:auto; }
.grn-plu-row { padding:10px 12px; border-radius:6px; margin-bottom:6px; cursor:pointer; border:1px solid #e2e8f0; font-size:13px; }
.grn-plu-row:hover { background:#eff6ff; border-color:#93c5fd; }
.grn-plu-active { background:#dbeafe !important; border-color:#3b82f6 !important; }
</style>

<div class="grn-wrap">
<?= form_open('grn/create_direct', ['id' => 'grnForm']) ?>
<input type="hidden" name="action" id="formAction" value="draft">
<input type="hidden" name="draft_id" id="draftIdField" value="">
<div style="text-align:right; margin-bottom:2px;">
    <small id="draftStatusPill" style="font-size:11px; font-weight:600;"></small>
</div>

<?php if ($flash_e): ?>
<div style="background: #fee2e2; color: #991b1b; padding: 6px; border: 1px solid #f87171; margin-bottom: 10px; border-radius: 2px;">
    <?= $flash_e ?>
</div>
<?php endif; ?>

<div class="tabs-bar">
    <div class="title-tab">Goods Receipt Note</div>
</div>
<div class="hdr-block">
    <!-- Row 1 -->
    <div class="hdr-row">
        <div class="hdr-field">
            <label>GRN No</label>
            <input type="text" value="000" readonly>
        </div>
        <div class="hdr-field">
            <label>GRN Date</label>
            <input type="date" name="received_date" value="<?= date('Y-m-d') ?>">
        </div>
    </div>
    <!-- Row 2 -->
    <div class="hdr-row">
        <div class="hdr-field">
            <label>PO No</label>
            <input type="text" name="po_number" readonly>
        </div>
        <div class="hdr-field">
            <label>Invoice No.</label>
            <input type="text" name="supplier_invoice_no" required>
        </div>
        <div class="hdr-field">
            <label>Receipt No</label>
            <input type="text" name="receipt_no">
        </div>
    </div>
    <!-- Row 3 -->
    <div class="hdr-row">
        <div class="hdr-field wide">
            <label>Supplier <span style="color:red">*</span></label>
            <select name="supplier_id" id="supplierSelect" required onchange="updateSupplierInfo()">
                <option value="">-- Select Supplier --</option>
                <?php foreach ($suppliers as $s): ?>
                <option value="<?= $s->id ?>"
                    data-gstin="<?= htmlspecialchars($s->gstin ?? '') ?>">
                    <?= htmlspecialchars($s->name) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="hdr-field">
            <label>GSTIN No</label>
            <input type="text" id="supplierGstin" readonly>
        </div>
        <div class="hdr-field">
            <label>Invoice Date</label>
            <input type="date" name="supplier_invoice_date" value="<?= date('Y-m-d') ?>">
        </div>
    </div>
</div>

<!-- Scan Bar -->
<div class="scan-bar">
    <div class="scan-label">Scan</div>
    <div style="position:relative; flex:1; max-width:630px;">
        <div class="scan-input-wrap">
            <input type="text" class="scan-input" id="grnSearch" autocomplete="off" placeholder="Scan barcode or search...">
        </div>
        <div id="grnSearchDropdown"></div>
    </div>
    <button type="button" class="btn-grad scan-btn">Select File</button>
    <button type="button" class="btn-grad scan-btn">Template</button>
</div>

<!-- Items Table -->
<div class="tbl-container">
    <table class="items-tbl" id="grnItemsTable">
        <thead>
            <tr>
                <th style="width:20px;"></th>
                <th style="width:80px;">Code</th>
                <th style="width:300px;">Item Name</th>
                <th style="width:80px;">HSN</th>
                <th style="width:70px;">Basic Cost</th>
                <th style="width:60px;">Disc-1%</th>
                <th style="width:70px;">Disc-1 Amt</th>
                <th style="width:60px;">Disc-2%</th>
                <th style="width:50px;">Tax Per</th>
                <th style="width:70px;">CP</th>
                <th style="width:60px;">Rec. Qty</th>
                <th style="width:70px;">MRP</th>
                <th style="width:70px;">SP</th>
                <th style="width:70px;">Margin %</th>
                <th style="width:80px;">Amount</th>
                <th style="width:auto;"></th>
            </tr>
        </thead>
        <tbody id="grnRows">
        </tbody>
    </table>
</div>

<!-- Bottom Section -->
<div class="bottom-wrap">
    
    <div class="bottom-left">
        <div class="bottom-left-row">
            <label>Item Code</label>
            <div style="flex:1; position:relative;">
                <input type="text" readonly style="width:100%; background:#f0f0f0;">
                <span style="position:absolute; right:6px; top:4px; color:#d97706; font-size:10px;">▼</span>
            </div>
        </div>
        <div class="bottom-left-row">
            <label>Remarks</label>
            <input type="text" name="remarks">
        </div>
        
        <div class="qty-box-wrap">
            <div style="display:flex; flex-direction:column;">
                <label style="color:#555;font-weight:bold;margin-bottom:2px;">Total Qty</label>
                <div class="qty-box" id="dispTotalQty">0</div>
            </div>
            <div style="display:flex; flex-direction:column; margin-left: 20px;">
                <button type="button" class="btn-blue">Populate Pending PO Items</button>
                <button type="button" class="btn-blue">Download Scan Data</button>
            </div>
        </div>
        
        <div class="bottom-left-row" style="margin-top:6px;">
            <label>Keep Focus On</label>
            <div style="width:120px; position:relative;">
                <select style="width:100%; appearance:none; padding-right:15px;"><option></option></select>
                <span style="position:absolute; right:6px; top:4px; color:#d97706; font-size:10px; pointer-events:none;">▼</span>
            </div>
        </div>
    </div>
    
    <div class="totals-grid">
        <label>Sub Total (Before GST)</label>
        <input type="text" id="totSubTotal" value="0.00" readonly>
        
        <label>Discount Amount</label>
        <input type="text" value="0.00" readonly>
        
        <label>GST Tax Amount</label>
        <input type="text" id="totGst" value="0.00" readonly>
        
        <label>Discount Value</label>
        <input type="text" value="0.00" readonly>
        
        <label style="visibility:hidden;">-</label>
        <input type="text" style="visibility:hidden;">
        
        <label>TCS Amount</label>
        <input type="text" value="0.00" readonly>
        
        <label>Freight</label>
        <input type="text" value="0.00" readonly>
        
        <label>Round Off</label>
        <div style="display:flex; gap:2px;"><button type="button" style="width:18px;height:24px;padding:0;background:#e0e0e0;border:1px solid #ccc; cursor:pointer;">↕</button><input type="text" id="totRound" value="0.00" readonly style="flex:1;"></div>
        
        <label>Other Charges</label>
        <input type="text" value="0.00" readonly>
        
        <label>Tot.Deduction</label>
        <input type="text" value="0.00" readonly>
        
        <label style="text-align:left; padding-left:25px;"><input type="checkbox" style="vertical-align:middle;margin:0 4px 0 0;height:12px;"> Spread Discount</label>
        <div style="height:10px;"></div>
        
        <label>GrandTotal</label>
        <div class="grand-total"><input type="text" id="totGrand" value="0.00" readonly></div>
    </div>
</div>

<div class="footer-btns">
    <button type="button" onclick="submitGrn('draft')">Add</button>
    <button type="button">Modify</button>
    <button type="button">Delete</button>
    <button type="button">Print</button>
    <button type="button" onclick="submitGrn('update')">Authorize</button>
    <button type="button">Modify Auth GRN</button>
    <button type="button" onclick="window.location='<?= base_url('grn') ?>'">Close</button>
</div>

<?= form_close() ?>

<div id="grnPluModalOverlay">
    <div class="grn-plu-box">
        <div class="grn-plu-header">Select MRP / PLU</div>
        <div class="grn-plu-hint">Use ↑ ↓ then Enter to select, or click a row (Esc to cancel)</div>
        <div id="grnPluModalList" class="grn-plu-list"></div>
    </div>
</div>
</div>

<script>
const products = [
<?php foreach($products as $p): ?>
{id:<?= $p->id ?>, name:<?= json_encode($p->name) ?>, code:<?= json_encode($p->item_code) ?>, hsn:<?= json_encode($p->hsn_code ?? '') ?>, cost:<?= (float)$p->basic_cost ?>, mrp:<?= (float)$p->mrp ?>, sp:<?= (float)$p->selling_price ?>, gst:<?= (float)$p->gst_percent ?>},
<?php endforeach; ?>
];

function updateSupplierInfo() {
    const sel = document.getElementById('supplierSelect');
    if (!sel.value) {
        document.getElementById('supplierGstin').value = '';
        return;
    }
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('supplierGstin').value = opt.getAttribute('data-gstin');
}

document.getElementById('grnForm').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        if (e.target.name === 'received_qty[]') {
            document.getElementById('grnSearch').focus();
        }
    }
});

const searchInp = document.getElementById('grnSearch');
const searchDropdown = document.getElementById('grnSearchDropdown');

// Billing-style product match: every typed word must appear (any order) — "maggi noodle" finds "MAGGI AATA NOODLE"
window.pmatch = window.pmatch || function (q) { var w = String(q || '').toLowerCase().split(/\s+/).filter(Boolean); var t = Array.prototype.slice.call(arguments, 1).map(function (x) { return String(x == null ? '' : x).toLowerCase(); }).join(' | '); return w.every(function (x) { return t.indexOf(x) !== -1; }); };
searchInp.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    if(q.length < 2) { searchDropdown.style.display = 'none'; return; }
    let html = '';
    let count = 0;
    for(let p of products) {
        if(pmatch(q, p.name, p.code)) {
            html += `<div class="psd-item" onclick="addRow(${p.id}, '${p.code||''}', '${p.name.replace(/'/g,"\\'").replace(/"/g,"&quot;")}', '${p.hsn||''}', ${p.cost}, ${p.mrp}, ${p.sp}, ${p.gst})">
                        <div class="psi-name">${p.code||'N/A'} - ${p.name}</div>
                     </div>`;
            count++;
            if(count > 20) break;
        }
    }
    if(html) {
        searchDropdown.innerHTML = html;
        searchDropdown.style.display = 'block';
    } else {
        searchDropdown.style.display = 'none';
    }
});
document.addEventListener('click', e => {
    if(e.target !== searchInp) searchDropdown.style.display = 'none';
});

// Barcode/item-code scan → server PLU lookup (same flow as Billing): single match
// auto-adds the row pre-filled from that exact price point; multiple matches (e.g.
// two batches of the same item at different MRPs) open a picker instead of guessing.
searchInp.addEventListener('keydown', function(e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const q = this.value.trim();
    if (!q) return;

    fetch('<?= base_url("grn/get_by_barcode_ajax") ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'barcode=' + encodeURIComponent(q)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.mode === 'single') {
            const p = data.plu;
            addRow(p.product_id, p.item_code || '', p.item_name || '', p.hsn_code || '', p.purchase_rate, p.mrp, p.selling_rate, p.gst_percent);
        } else if (data.success && data.mode === 'multiple') {
            openGrnPluModal(data.plus);
        } else if (data.success && data.mode === 'legacy' && data.product) {
            const pr = data.product;
            addRow(pr.id, pr.item_code || '', pr.name || '', pr.hsn_code || '', pr.basic_cost, pr.mrp, pr.selling_price, pr.gst_percent);
        } else {
            // Barcode/item-code not found server-side — fall back to an exact local code match
            const localMatch = products.find(x => x.code && x.code.toLowerCase() === q.toLowerCase());
            if (localMatch) {
                addRow(localMatch.id, localMatch.code || '', localMatch.name, localMatch.hsn || '', localMatch.cost, localMatch.mrp, localMatch.sp, localMatch.gst);
            }
        }
    })
    .catch(() => {});
});

/* ── PLU picker (multi-MRP) — arrow keys + Enter, mirrors Billing's popup ── */
let _grnPluList = [], _grnPluIdx = 0;

function openGrnPluModal(plus) {
    _grnPluList = plus; _grnPluIdx = 0;
    renderGrnPluModal();
    document.getElementById('grnPluModalOverlay').style.display = 'flex';
}

function renderGrnPluModal() {
    const el = document.getElementById('grnPluModalList');
    el.innerHTML = _grnPluList.map((p, i) => {
        const cls = i === _grnPluIdx ? 'grn-plu-row grn-plu-active' : 'grn-plu-row';
        return `<div class="${cls}" onclick="grnPluPickClick(${i})">
            <span style="font-weight:bold;color:#0055a5;">${p.plu_code}</span>
            <span style="margin:0 8px;">${p.item_name || ''}</span>
            <span style="font-weight:bold;">MRP ₹${p.mrp}</span>
            <span style="margin-left:8px;">| Cost ₹${p.purchase_rate}</span>
            <span style="margin-left:8px;color:#666;">Stock: ${p.stock_qty}</span>
        </div>`;
    }).join('');
}

function grnPluPickClick(idx) { _grnPluIdx = idx; confirmGrnPluPick(); }

function confirmGrnPluPick() {
    document.getElementById('grnPluModalOverlay').style.display = 'none';
    const p = _grnPluList[_grnPluIdx];
    addRow(p.product_id, p.item_code || '', p.item_name || '', p.hsn_code || '', p.purchase_rate, p.mrp, p.selling_rate, p.gst_percent);
}

function closeGrnPluModal() {
    document.getElementById('grnPluModalOverlay').style.display = 'none';
    searchInp.focus();
}

document.addEventListener('keydown', function(e) {
    const overlay = document.getElementById('grnPluModalOverlay');
    if (!overlay || overlay.style.display !== 'flex') return;
    if (e.key === 'ArrowDown') { _grnPluIdx = Math.min(_grnPluIdx + 1, _grnPluList.length - 1); renderGrnPluModal(); e.preventDefault(); }
    else if (e.key === 'ArrowUp') { _grnPluIdx = Math.max(_grnPluIdx - 1, 0); renderGrnPluModal(); e.preventDefault(); }
    else if (e.key === 'Enter') { confirmGrnPluPick(); e.preventDefault(); }
    else if (e.key === 'Escape') { closeGrnPluModal(); e.preventDefault(); }
});

let rowIdx = 0;
function addRow(pid, code, name, hsn, cost, mrp, sp, gst) {
    searchInp.value = '';
    searchDropdown.style.display = 'none';

    cost = parseFloat(cost) || 0;
    mrp  = parseFloat(mrp) || 0;
    sp   = parseFloat(sp) || 0;
    gst  = parseFloat(gst) || 0;

    rowIdx++;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="text-align:center;color:#dc2626;cursor:pointer;font-weight:bold;" onclick="this.parentElement.remove();calcTotals();if(window.entryDraftEngine){window.entryDraftEngine.dirty=true;window.entryDraftEngine._saveLocal();}">x</td>
        <td><input type="text" class="txt-left" value="${code || ''}" readonly></td>
        <td><input type="text" class="txt-left" value="${name || ''}" readonly><input type="hidden" name="product_id[]" value="${pid}"></td>
        <td><input type="text" class="txt-left" value="${hsn || ''}" readonly></td>
        <td><input type="number" name="rate[]" step="0.01" value="${cost.toFixed(2)}" oninput="calcTotals()"></td>
        <td><input type="number" value="0.00" readonly></td>
        <td><input type="number" value="0.00" readonly></td>
        <td><input type="number" value="0.00" readonly></td>
        <td><input type="number" name="gst_pct[]" step="0.01" value="${gst.toFixed(2)}" oninput="calcTotals()"></td>
        <td><input type="number" class="row-cp" value="${cost.toFixed(2)}" readonly></td>
        <td><input type="number" name="received_qty[]" step="0.01" value="1" oninput="calcTotals()"></td>
        <td><input type="number" name="mrp[]" class="row-mrp" value="${mrp.toFixed(2)}" oninput="calcTotals()"></td>
        <td><input type="number" name="sp[]" class="row-sp" value="${sp.toFixed(2)}"></td>
        <td><input type="text" class="row-margin margin-field" value="0.00%" readonly></td>
        <td><input type="text" class="row-amt" value="${cost.toFixed(2)}" readonly style="font-weight:bold;"></td>
        <td></td>
    `;
    document.getElementById('grnRows').appendChild(tr);
    calcTotals();
    
    if (window.entryDraftEngine) {
        window.entryDraftEngine.dirty = true;
        window.entryDraftEngine._saveLocal();
    }
    
    // Focus the qty field of the newly added row
    setTimeout(() => {
        const qtyInp = tr.querySelector('input[name="received_qty[]"]');
        if (qtyInp) { qtyInp.focus(); qtyInp.select(); }
    }, 50);
}

function calcTotals() {
    let totQty = 0;
    let subTot = 0;
    let gstTot = 0;
    
    const rows = document.querySelectorAll('#grnRows tr');
    rows.forEach(r => {
        const qtyInp = r.querySelector('input[name="received_qty[]"]');
        const rateInp = r.querySelector('input[name="rate[]"]');
        const gstInp = r.querySelector('input[name="gst_pct[]"]');
        const cpInp = r.querySelector('.row-cp');
        const mrpInp = r.querySelector('.row-mrp');
        const marginInp = r.querySelector('.row-margin');
        const amtInp = r.querySelector('.row-amt');
        
        if(qtyInp && rateInp) {
            const q = parseFloat(qtyInp.value) || 0;
            const rt = parseFloat(rateInp.value) || 0;
            const gst = parseFloat(gstInp.value) || 0;
            const mrp = parseFloat(mrpInp.value) || 0;
            
            const base = q * rt;
            const rowGst = base * (gst/100);
            const rowTot = base + rowGst;
            
            if(cpInp) cpInp.value = rt.toFixed(2);
            if(amtInp) amtInp.value = rowTot.toFixed(2);
            
            if (rt > 0 && mrp > 0) {
                const marginPct = ((mrp - rt) / mrp) * 100;
                if(marginInp) marginInp.value = marginPct.toFixed(2) + "%";
            } else {
                if(marginInp) marginInp.value = "0.00%";
            }
            
            totQty += q;
            subTot += base;
            gstTot += rowGst;
        }
    });
    
    document.getElementById('dispTotalQty').innerText = totQty.toString();
    document.getElementById('totSubTotal').value = subTot.toFixed(2);
    document.getElementById('totGst').value = gstTot.toFixed(2);
    
    const grand = subTot + gstTot;
    document.getElementById('totGrand').value = grand.toFixed(2);
}

function submitGrn(action) {
    document.getElementById('formAction').value = action;
    const supplier = document.getElementById('supplierSelect').value;
    if(!supplier) {
        alert('Please select a supplier.');
        return;
    }
    const invNo = document.querySelector('input[name="supplier_invoice_no"]').value;
    if(!invNo) {
        alert('Supplier Invoice No is required.');
        return;
    }
    const rows = document.querySelectorAll('#grnRows tr');
    if(rows.length === 0) {
        alert('Please add at least one item.');
        return;
    }
    document.getElementById('draftIdField').value = (window.entryDraftEngine && window.entryDraftEngine.draftId) || '';
    document.getElementById('grnForm').submit();
}
</script>
<script src="<?= base_url('assets/js/entry-draft.js') ?>"></script>
<script>
function restoreGrnDirectDraftItem(item) {
    addRow(
        item.product_id,
        item.item_code || '',
        item.product_name || '',
        item.hsn_code || '',
        parseFloat(item.rate) || 0,
        parseFloat(item.mrp) || 0,
        parseFloat(item.selling_price || item.sp) || 0,
        parseFloat(item.gst_percent) || 0
    );
    const rows = document.querySelectorAll('#grnRows tr');
    const tr = rows[rows.length - 1];
    if (tr) {
        const qtyInp = tr.querySelector('input[name="received_qty[]"]');
        if (qtyInp) qtyInp.value = item.received_qty || item.qty || 1;
    }
    calcTotals();
}

window.entryDraftEngine = EntryDraft.init({
    entryType: 'grn_direct',
    basePath: '<?= base_url('grn') ?>',
    formSelector: '#grnForm',
    itemsContainerSelector: '#grnRows',
    headerFieldNames: ['supplier_id', 'received_date', 'supplier_invoice_no', 'supplier_invoice_date', 'receipt_no', 'remarks'],
    itemFieldMap: {
        product_id: 'product_id[]',
        rate: 'rate[]',
        gst_percent: 'gst_pct[]',
        received_qty: 'received_qty[]',
        mrp: 'mrp[]',
        selling_price: 'sp[]',
    },
    restoreItem: restoreGrnDirectDraftItem,
    recalc: calcTotals,
    enableDuplicateGuard: true,
    wrapAddFns: ['addRow'],
    getProductIdFromArgs: function (args) { return args[0]; },
});
</script>
