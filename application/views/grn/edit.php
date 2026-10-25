<?php
$flash_e = $this->session->flashdata('error');
$is_direct = empty($grn->po_id);
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
.status-tab { padding: 4px 15px; font-weight: bold; font-size: 12px; }

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
.margin-field { color: #16a34a; font-weight: bold; }

/* Bottom Section */
.bottom-wrap { display: flex; justify-content: space-between; margin-top: 10px; }
.bottom-left { display: flex; flex-direction: column; gap: 6px; width: 420px; }
.bottom-left-row { display: flex; gap: 4px; align-items: center; }
.bottom-left-row label { width: 85px; color: #555; margin: 0; font-weight: 500; }
.bottom-left-row input, .bottom-left-row select { flex: 1; border: 1px solid #ccc; background: #fff; }

.qty-box-wrap { display: flex; gap: 15px; margin-top: 10px; align-items: flex-end; }
.qty-box { width: 90px; height: 45px; background: #fff; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; color: #0055a5; border-radius: 2px; }

/* Totals Box */
.totals-grid { display: grid; grid-template-columns: 120px 100px 120px 100px; gap: 4px 10px; align-items: center; }
.totals-grid label { text-align: right; color: #555; margin: 0; font-size: 11px; font-weight: 500; }
.totals-grid input { border: 1px solid #ccc; text-align: right; background: #f9f9f9; font-weight: bold; color: #333; }
.totals-grid .grand-total input { font-size: 14px; font-weight: bold; height: 26px; color: #16a34a; }

/* Footer Buttons */
.footer-btns { display: flex; justify-content: flex-end; gap: 6px; margin-top: 15px; border-top: 1px solid #ccc; padding-top: 10px; }
.footer-btns button, .footer-btns a { background: #0055a5; color: #fff; border: 1px solid #004080; padding: 4px 16px; cursor: pointer; height: 32px !important; font-size: 13px; border-radius: 2px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; }
.footer-btns button:hover, .footer-btns a:hover { background: #0066cc; color: #fff; }
.footer-btns .btn-authorize { background: #16a34a; border-color: #15803d; }
.footer-btns .btn-authorize:hover { background: #15803d; }
.footer-btns .btn-close { background: #6b7280; border-color: #4b5563; }

input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
</style>

<div class="grn-wrap">
<?= form_open('grn/edit/' . $grn->id, ['id' => 'grnForm']) ?>
<input type="hidden" name="action" id="formAction" value="draft">

<?php if ($flash_e): ?>
<div style="background: #fee2e2; color: #991b1b; padding: 6px; border: 1px solid #f87171; margin-bottom: 10px; border-radius: 2px;">
    <?= $flash_e ?>
</div>
<?php endif; ?>

<?php if ($grn->status === 'accepted'): ?>
<div style="background: #fef9c3; color: #854d0e; padding: 6px; border: 1px solid #eab308; margin-bottom: 10px; border-radius: 2px;">
    This GRN was already Authorized and stock was updated. Saving changes here will reverse that stock and revert it to Pending until you Authorize again.
</div>
<?php endif; ?>

<div class="tabs-bar">
    <div class="title-tab">Edit Goods Receipt Note</div>
    <div class="status-tab" style="background:<?= $grn->status === 'accepted' ? '#16a34a' : '#f59e0b' ?>;color:#fff;"><?= ucfirst($grn->status) ?></div>
</div>
<div class="hdr-block">
    <!-- Row 1 -->
    <div class="hdr-row">
        <div class="hdr-field">
            <label>GRN No</label>
            <input type="text" value="<?= htmlspecialchars($grn->grn_number) ?>" readonly>
        </div>
        <div class="hdr-field">
            <label>GRN Date</label>
            <input type="date" name="received_date" value="<?= date('Y-m-d', strtotime($grn->received_date)) ?>">
        </div>
    </div>
    <!-- Row 2 -->
    <div class="hdr-row">
        <div class="hdr-field">
            <label>PO No</label>
            <input type="text" value="<?= htmlspecialchars($grn->po_number ?? '-') ?>" readonly>
        </div>
        <div class="hdr-field">
            <label>Invoice No.</label>
            <input type="text" name="supplier_invoice_no" value="<?= htmlspecialchars($grn->supplier_invoice_no ?? '') ?>" required>
        </div>
        <div class="hdr-field">
            <label>LR Number</label>
            <input type="text" name="lr_number" value="<?= htmlspecialchars($grn->lr_number ?? '') ?>">
        </div>
    </div>
    <!-- Row 3 -->
    <div class="hdr-row">
        <div class="hdr-field wide">
            <label>Supplier</label>
            <input type="text" value="<?= htmlspecialchars($grn->supplier_name) ?>" readonly>
        </div>
        <div class="hdr-field">
            <label>Invoice Date</label>
            <input type="date" name="supplier_invoice_date" value="<?= $grn->supplier_invoice_date ? date('Y-m-d', strtotime($grn->supplier_invoice_date)) : '' ?>">
        </div>
        <div class="hdr-field">
            <label>E-Way Bill</label>
            <input type="text" name="eway_bill" value="<?= htmlspecialchars($grn->eway_bill ?? '') ?>">
        </div>
    </div>
    <!-- Row 4 -->
    <div class="hdr-row">
        <div class="hdr-field">
            <label>Vehicle No</label>
            <input type="text" name="vehicle_no" value="<?= htmlspecialchars($grn->vehicle_no ?? '') ?>">
        </div>
        <div class="hdr-field">
            <label>Driver Name</label>
            <input type="text" name="driver_name" value="<?= htmlspecialchars($grn->driver_name ?? '') ?>">
        </div>
    </div>
</div>

<!-- Scan Bar -->
<div class="scan-bar">
    <div class="scan-label">Scan</div>
    <div style="position:relative; flex:1; max-width:630px;">
        <div class="scan-input-wrap">
            <input type="text" class="scan-input" id="grnSearch" autocomplete="off" placeholder="Scan barcode or search to add an item...">
        </div>
        <div id="grnSearchDropdown"></div>
    </div>
</div>

<!-- Items Table -->
<div class="tbl-container">
    <table class="items-tbl" id="grnItemsTable">
        <thead>
            <tr>
                <th style="width:20px;"></th>
                <th style="width:80px;">Code</th>
                <th style="width:280px;">Item Name</th>
                <th style="width:70px;">Rate</th>
                <th style="width:50px;">GST%</th>
                <th style="width:70px;">Rec. Qty</th>
                <th style="width:70px;">Acc. Qty</th>
                <th style="width:70px;">Rej. Qty</th>
                <th style="width:70px;">MRP</th>
                <th style="width:70px;">SP</th>
                <th style="width:90px;">Batch No</th>
                <th style="width:100px;">Expiry</th>
                <th style="width:80px;">Amount</th>
                <th style="width:auto;"></th>
            </tr>
        </thead>
        <tbody id="grnRows">
            <?php foreach ($items as $it): ?>
            <tr>
                <td style="text-align:center;color:#dc2626;cursor:pointer;font-weight:bold;" onclick="this.parentElement.remove();calcTotals();">x</td>
                <td><input type="text" class="txt-left" value="<?= htmlspecialchars($it->item_code ?? '') ?>" readonly></td>
                <td>
                    <input type="text" class="txt-left" value="<?= htmlspecialchars($it->product_name) ?>" readonly>
                    <input type="hidden" name="product_id[]" value="<?= $it->product_id ?>">
                    <input type="hidden" name="po_item_id[]" value="<?= $it->po_item_id ?? '' ?>">
                </td>
                <td><input type="number" name="rate[]" step="0.01" value="<?= number_format($it->rate, 2, '.', '') ?>" oninput="calcTotals()"></td>
                <td><input type="number" name="gst_pct[]" step="0.01" value="<?= number_format($it->gst_pct ?? 0, 2, '.', '') ?>" oninput="calcTotals()"></td>
                <td><input type="number" name="received_qty[]" step="0.01" value="<?= number_format($it->received_qty, 2, '.', '') ?>" oninput="calcTotals()"></td>
                <td><input type="number" name="accepted_qty[]" step="0.01" value="<?= number_format($it->accepted_qty, 2, '.', '') ?>" oninput="calcTotals()"></td>
                <td><input type="number" name="rejected_qty[]" step="0.01" value="<?= number_format($it->rejected_qty, 2, '.', '') ?>" oninput="calcTotals()"></td>
                <td><input type="number" name="mrp[]" class="row-mrp" value="<?= number_format($it->mrp ?? 0, 2, '.', '') ?>" oninput="calcTotals()"></td>
                <td><input type="number" name="sp[]" class="row-sp" value="<?= number_format($it->selling_price ?? 0, 2, '.', '') ?>"></td>
                <td><input type="text" class="txt-left" name="batch_no[]" value="<?= htmlspecialchars($it->batch_no ?? '') ?>"></td>
                <td><input type="date" name="expiry_date[]" value="<?= ($it->expiry_date && $it->expiry_date !== '0000-00-00') ? $it->expiry_date : '' ?>"></td>
                <td><input type="text" class="row-amt" value="0.00" readonly style="font-weight:bold;"></td>
                <td></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Bottom Section -->
<div class="bottom-wrap">
    <div class="bottom-left">
        <div class="qty-box-wrap">
            <div style="display:flex; flex-direction:column;">
                <label style="color:#555;font-weight:bold;margin-bottom:2px;">Total Qty</label>
                <div class="qty-box" id="dispTotalQty">0</div>
            </div>
        </div>
    </div>

    <div class="totals-grid">
        <label>Sub Total (Before GST)</label>
        <input type="text" id="totSubTotal" value="0.00" readonly>

        <label>GST Tax Amount</label>
        <input type="text" id="totGst" value="0.00" readonly>

        <label>GrandTotal</label>
        <div class="grand-total"><input type="text" id="totGrand" value="0.00" readonly></div>
    </div>
</div>

<div class="footer-btns">
    <a href="<?= base_url('grn/print_grn/'.$grn->id) ?>" target="_blank">Print</a>
    <button type="button" onclick="submitGrn('draft')">Save Draft</button>
    <?php if ($is_admin): ?>
    <button type="button" class="btn-authorize" onclick="submitGrn('update')">Save &amp; Authorize</button>
    <?php endif; ?>
    <button type="button" class="btn-close" onclick="window.location='<?= base_url('grn/view/'.$grn->id) ?>'">Close</button>
</div>

<?= form_close() ?>
</div>

<script>
const products = [
<?php foreach($products as $p): ?>
{id:<?= $p->id ?>, name:<?= json_encode($p->name) ?>, code:<?= json_encode($p->item_code) ?>, cost:<?= (float)$p->basic_cost ?>, mrp:<?= (float)$p->mrp ?>, sp:<?= (float)$p->selling_price ?>, gst:<?= (float)$p->gst_percent ?>},
<?php endforeach; ?>
];

document.getElementById('grnForm').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
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
            html += `<div class="psd-item" onclick="addRow(${p.id}, '${p.code||''}', '${p.name.replace(/'/g,"\\'").replace(/"/g,"&quot;")}', ${p.cost}, ${p.mrp}, ${p.sp}, ${p.gst})">
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

function addRow(pid, code, name, cost, mrp, sp, gst) {
    searchInp.value = '';
    searchDropdown.style.display = 'none';

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="text-align:center;color:#dc2626;cursor:pointer;font-weight:bold;" onclick="this.parentElement.remove();calcTotals();">x</td>
        <td><input type="text" class="txt-left" value="${code}" readonly></td>
        <td><input type="text" class="txt-left" value="${name}" readonly><input type="hidden" name="product_id[]" value="${pid}"><input type="hidden" name="po_item_id[]" value=""></td>
        <td><input type="number" name="rate[]" step="0.01" value="${cost.toFixed(2)}" oninput="calcTotals()"></td>
        <td><input type="number" name="gst_pct[]" step="0.01" value="${gst.toFixed(2)}" oninput="calcTotals()"></td>
        <td><input type="number" name="received_qty[]" step="0.01" value="1" oninput="calcTotals()"></td>
        <td><input type="number" name="accepted_qty[]" step="0.01" value="1" oninput="calcTotals()"></td>
        <td><input type="number" name="rejected_qty[]" step="0.01" value="0" oninput="calcTotals()"></td>
        <td><input type="number" name="mrp[]" class="row-mrp" value="${mrp.toFixed(2)}" oninput="calcTotals()"></td>
        <td><input type="number" name="sp[]" class="row-sp" value="${sp.toFixed(2)}"></td>
        <td><input type="text" class="txt-left" name="batch_no[]" value=""></td>
        <td><input type="date" name="expiry_date[]" value=""></td>
        <td><input type="text" class="row-amt" value="${cost.toFixed(2)}" readonly style="font-weight:bold;"></td>
        <td></td>
    `;
    document.getElementById('grnRows').appendChild(tr);
    calcTotals();
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
        const amtInp = r.querySelector('.row-amt');

        if(qtyInp && rateInp) {
            const q = parseFloat(qtyInp.value) || 0;
            const rt = parseFloat(rateInp.value) || 0;
            const gst = parseFloat(gstInp.value) || 0;

            const base = q * rt;
            const rowGst = base * (gst/100);
            const rowTot = base + rowGst;

            if(amtInp) amtInp.value = rowTot.toFixed(2);

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
    if (action === 'update' && !confirm('Authorize this GRN? Stock will be updated to match the edited quantities.')) {
        return;
    }
    document.getElementById('grnForm').submit();
}

window.addEventListener('DOMContentLoaded', function() {
    calcTotals();
});
</script>
