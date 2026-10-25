<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-undo mr-1 text-warning"></i> New Refund / Return</h5>
        <small class="text-muted"><a href="<?= base_url('refunds') ?>">Refunds</a> / Create</small>
    </div>
    <a href="<?= base_url('refunds') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?= $this->session->flashdata('success') ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div><?php endif; ?>

<!-- Step 1: Search -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;"><i class="fas fa-search mr-1"></i> Search Invoice</div>
    <div class="card-body">
        <?= form_open('refunds/create', ['method' => 'POST', 'id' => 'searchForm']) ?>
        <div class="form-row align-items-end">
            <div class="col-md-4">
                <label class="small font-weight-bold">Bill / Invoice Number</label>
                <input type="text" name="search_invoice_no" class="form-control form-control-sm" placeholder="e.g. INV-2026-0001"
                       value="<?= htmlspecialchars($this->input->post('search_invoice_no') ?: ($this->input->get('invoice_no') ?: '')) ?>" autocomplete="off" autofocus>
            </div>
            <div class="col-md-4">
                <label class="small font-weight-bold">OR Customer Phone</label>
                <input type="text" name="search_phone" class="form-control form-control-sm" placeholder="10-digit mobile" value="<?= htmlspecialchars($this->input->post('search_phone') ?: '') ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-warning btn-block font-weight-bold"><i class="fas fa-search mr-1"></i> Find Invoice</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<?php if (!empty($invoice)): ?>

<!-- Invoice Found — Show Details -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 font-weight-bold" style="background:#fff3cd;font-size:.85rem;">
        <i class="fas fa-file-invoice mr-1 text-warning"></i> Invoice: <?= $invoice->invoice_no ?>
        <span class="badge badge-<?= $invoice->status === 'paid' ? 'success' : ($invoice->status === 'cancelled' ? 'danger' : 'warning') ?> ml-1"><?= strtoupper($invoice->status) ?></span>
    </div>
    <div class="card-body" style="font-size:.84rem;">
        <div class="row">
            <div class="col-md-4">
                <p class="mb-1"><strong>Customer:</strong> <?= htmlspecialchars($invoice->customer_name) ?></p>
                <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($invoice->customer_phone ?? '—') ?></p>
                <p class="mb-1"><strong>Date:</strong> <?= date('d M Y', strtotime($invoice->invoice_date)) ?></p>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>Total Amount:</strong> &#8377; <?= number_format($invoice->total_amount, 2) ?></p>
                <p class="mb-1"><strong>Paid:</strong> &#8377; <?= number_format($invoice->paid_amount, 2) ?></p>
                <p class="mb-1"><strong>Due:</strong> &#8377; <?= number_format(max(0, $invoice->due_amount), 2) ?></p>
            </div>
            <div class="col-md-4">
                <?php $already_refunded = $total_refunded ?? 0; ?>
                <p class="mb-1"><strong>Already Refunded:</strong> <span class="text-danger">&#8377; <?= number_format($already_refunded, 2) ?></span></p>
                <p class="mb-1"><strong>Max Refundable:</strong> <span class="text-success font-weight-bold">&#8377; <?= number_format(max(0, $invoice->total_amount - $already_refunded), 2) ?></span></p>
            </div>
        </div>
        <?php if (!empty($prior_refunds)): ?>
        <div class="alert alert-warning py-1 mb-0 mt-2" style="font-size:.8rem;">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <?= count($prior_refunds) ?> prior refund(s) exist for this invoice.
            <?php foreach ($prior_refunds as $pr): ?>
            <a href="<?= base_url('refunds/view/'.$pr->id) ?>" class="badge badge-warning ml-1"><?= $pr->refund_no ?> — ₹<?= number_format($pr->refund_amount,2) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Step 2: Process Refund Form -->
<?php if ($invoice->status !== 'cancelled' && ($invoice->total_amount - ($total_refunded ?? 0)) > 0): ?>

<?= form_open('refunds/create', ['id' => 'refundForm']) ?>
<input type="hidden" name="invoice_id" value="<?= $invoice->id ?>">

<div class="row">
    <div class="col-md-7">
        <!-- Item Selection -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Select Items to Return</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.83rem;">
                    <thead class="thead-light">
                        <tr>
                            <th><input type="checkbox" id="selAll" onchange="toggleAllItems(this)"></th>
                            <th>Product</th>
                            <th class="text-center">Orig Qty</th>
                            <th class="text-center">Return Qty</th>
                            <th class="text-right">Rate</th>
                            <th class="text-right">Refund Amt</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><input type="checkbox" class="item-chk" name="ref_item_id[]" value="<?= $item->id ?>" data-serial="<?= !empty($item->maintain_serial) ? 1 : 0 ?>" onchange="calcRefundTotal()"></td>
                        <td><?= htmlspecialchars($item->product_name) ?>
                            <?php if (!empty($item->maintain_serial)): ?>
                            <span class="badge badge-warning ml-1" style="font-size:.65rem;">SERIAL</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $item->qty ?> <?= $item->unit ?></td>
                        <td class="text-center" style="width:100px;">
                            <input type="number" name="ref_qty[]" class="form-control form-control-sm ref-qty text-center"
                                   value="<?= $item->qty ?>" min="0.01" max="<?= $item->qty ?>" step="0.01"
                                   data-orig-qty="<?= $item->qty ?>"
                                   data-total="<?= $item->total ?>"
                                   oninput="calcRefundTotal()" disabled>
                        </td>
                        <td class="text-right">&#8377; <?= number_format($item->rate, 2) ?></td>
                        <td class="text-right font-weight-bold item-refund-amt">&#8377; 0.00</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Refund Details</div>
            <div class="card-body">
                <div class="form-group mb-2">
                    <label class="small font-weight-bold">Refund Type <span class="text-danger">*</span></label>
                    <select name="refund_type" id="refundType" class="form-control form-control-sm" onchange="onRefundTypeChange()">
                        <option value="partial">Partial Refund (select items above)</option>
                        <option value="full">Full Refund (entire invoice)</option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <label class="small font-weight-bold">Refund Mode <span class="text-danger">*</span></label>
                    <select name="refund_mode" class="form-control form-control-sm">
                        <option value="cash">💵 Cash</option>
                        <option value="upi">📱 UPI</option>
                        <option value="neft">🏦 NEFT/Bank Transfer</option>
                        <option value="cheque">📝 Cheque</option>
                        <option value="card">💳 Card Reversal</option>
                        <option value="credit_note">📋 Credit Note (adjust outstanding)</option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <label class="small font-weight-bold">Reason for Refund <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control form-control-sm" rows="2" required placeholder="Describe the reason for return/refund..."></textarea>
                </div>
                <div class="form-group mb-2">
                    <label class="small font-weight-bold">Additional Notes</label>
                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Optional notes..."></textarea>
                </div>

                <div class="border rounded p-2 text-center mb-3" style="background:#ffebee;">
                    <div class="small text-muted font-weight-bold">Refund Amount</div>
                    <h3 class="font-weight-bold text-danger mb-0" id="refundTotalDisplay">₹ 0.00</h3>
                </div>

                <!-- Serialized units must be identified individually so the
                     right physical motor is taken back off the customer's
                     warranty. Hidden entirely when nothing on the bill is
                     serialized, so ordinary refunds look unchanged. -->
                <div id="refundSerialBox" style="display:none;" class="border rounded p-2 mb-3">
                    <div class="small font-weight-bold mb-1">
                        <i class="fas fa-barcode mr-1"></i> Serial Numbers Being Returned
                        <span class="badge badge-warning ml-1" id="refSerialCount">0</span>
                    </div>
                    <input type="hidden" name="return_serial_json" id="returnSerialJson" value="">
                    <input type="text" id="refSerialInput" class="form-control form-control-sm"
                           style="font-family:monospace;" autocomplete="off"
                           placeholder="Scan the serial being returned, then Enter">
                    <div id="refSerialMsg" class="small mt-1"></div>
                    <div id="refSerialList" class="mt-1" style="max-height:110px;overflow:auto;"></div>
                </div>

                <button type="submit" class="btn btn-danger btn-block font-weight-bold" onclick="return confirmRefund()">
                    <i class="fas fa-undo mr-1"></i> Process Refund & Restore Stock
                </button>
            </div>
        </div>
    </div>
</div>

<?= form_close() ?>

<?php else: ?>
<div class="alert alert-danger">
    <strong>Refund not possible:</strong>
    <?= $invoice->status === 'cancelled' ? 'Invoice is already cancelled.' : 'Full amount has already been refunded.' ?>
</div>
<?php endif; ?>

<script>
const INVOICE_TOTAL = <?= $invoice->total_amount ?>;
const ALREADY_REFUNDED = <?= $total_refunded ?? 0 ?>;

function toggleAllItems(masterChk) {
    document.querySelectorAll('.item-chk').forEach(function(chk) {
        chk.checked = masterChk.checked;
        const row = chk.closest('tr');
        const qtyInput = row.querySelector('.ref-qty');
        if (qtyInput) qtyInput.disabled = !masterChk.checked;
    });
    calcRefundTotal();
}

function onRefundTypeChange() {
    const isFull = document.getElementById('refundType').value === 'full';
    document.querySelectorAll('.item-chk').forEach(chk => {
        chk.checked = isFull;
        const row = chk.closest('tr');
        const qtyInput = row.querySelector('.ref-qty');
        if (qtyInput) {
            qtyInput.disabled = isFull;
            if (isFull) qtyInput.value = qtyInput.dataset.origQty;
        }
    });
    if (isFull) {
        const maxRef = Math.max(0, INVOICE_TOTAL - ALREADY_REFUNDED);
        document.getElementById('refundTotalDisplay').textContent = '₹ ' + maxRef.toFixed(2);
    } else {
        calcRefundTotal();
    }
    document.getElementById('selAll').checked = isFull;
}

document.querySelectorAll('.item-chk').forEach(function(chk) {
    chk.addEventListener('change', function() {
        const row = this.closest('tr');
        const qtyInput = row.querySelector('.ref-qty');
        if (qtyInput) qtyInput.disabled = !this.checked;
        calcRefundTotal();
    });
});

function calcRefundTotal() {
    let total = 0;
    const rows = document.querySelectorAll('#refundForm tbody tr');
    rows.forEach(function(row, idx) {
        const chk = row.querySelector('.item-chk');
        const qtyInput = row.querySelector('.ref-qty');
        const amtCell = row.querySelector('.item-refund-amt');
        if (chk && chk.checked && qtyInput && amtCell) {
            const origQty  = parseFloat(qtyInput.dataset.origQty) || 0;
            const origTotal = parseFloat(qtyInput.dataset.total) || 0;
            const retQty   = parseFloat(qtyInput.value) || 0;
            const refAmt   = origQty > 0 ? (origTotal * retQty / origQty) : 0;
            amtCell.textContent = '₹ ' + refAmt.toFixed(2);
            total += refAmt;
        } else if (amtCell) {
            amtCell.textContent = '₹ 0.00';
        }
    });
    document.getElementById('refundTotalDisplay').textContent = '₹ ' + total.toFixed(2);
}

function confirmRefund() {
    const type = document.getElementById('refundType').value;
    const amtText = document.getElementById('refundTotalDisplay').textContent;
    const isFull = (type === 'full');
    if (!isFull) {
        const anyChecked = Array.from(document.querySelectorAll('.item-chk')).some(c => c.checked);
        if (!anyChecked) {
            alert('Select at least one item to refund.');
            return false;
        }
    }
    return confirm((isFull ? 'Full refund' : 'Partial refund') + ' of ' + amtText + '?\n\nThis will restore stock and cannot be undone.');
}
</script>

<?php else: ?>
<div class="alert alert-info">Use the search above to find an invoice by bill number or customer phone number.</div>
<?php endif; ?>

<script>
// Sales-return serial scan. The box only appears when the invoice actually
// contains serialized units; the server re-checks every serial against what
// went out on this invoice.
(function () {
    var box   = document.getElementById('refundSerialBox');
    if (!box) return;
    var input = document.getElementById('refSerialInput'),
        field = document.getElementById('returnSerialJson'),
        msg   = document.getElementById('refSerialMsg'),
        list  = document.getElementById('refSerialList'),
        count = document.getElementById('refSerialCount'),
        picked = [];

    function esc(s){return String(s).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];});}

    function render() {
        field.value = JSON.stringify(picked);
        count.textContent = picked.length;
        list.innerHTML = picked.map(function (s, i) {
            return '<span class="badge badge-secondary mr-1 mb-1" style="font-family:monospace;font-size:.75rem;">'
                 + esc(s) + ' <a href="#" onclick="return refDrop(' + i + ')" style="color:#fff;">&times;</a></span>';
        }).join('');
    }
    window.refDrop = function (i) { picked.splice(i, 1); render(); input.focus(); return false; };

    input.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var sn = (this.value || '').trim().toUpperCase();
        this.value = '';
        if (!sn) return;
        if (picked.indexOf(sn) !== -1) { msg.innerHTML = '<span class="text-danger">Already scanned.</span>'; return; }

        var fd = new FormData();
        fd.append('serial', sn);
        fd.append('enforce_location', '0');   // it is coming back from the customer, not sitting in a branch
        msg.innerHTML = '<span class="text-muted">Checking ' + esc(sn) + '…</span>';
        fetch('<?= base_url('serials/lookup_ajax') ?>', {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(function (r) { return r.json(); })
            .then(function (res) {
                var s = res && res.serial;
                // A sold unit fails validate_outbound (it wants IN_STOCK), which
                // is exactly the unit we expect here — so accept on SOLD and let
                // the server do the invoice-match check.
                if (!s) { msg.innerHTML = '<span class="text-danger">' + esc((res && res.error) || 'Serial not found.') + '</span>'; return; }
                if (s.status !== 'SOLD') {
                    msg.innerHTML = '<span class="text-danger">' + esc(s.serial_no) + ' is not currently sold (status: ' + esc(s.status) + ').</span>';
                    return;
                }
                picked.push(s.serial_no);
                msg.innerHTML = '<span class="text-success">' + esc(s.product_name || '') + ' &middot; invoice ' + esc(s.invoice_no || '—') + '</span>';
                render();
            })
            .catch(function () { msg.innerHTML = '<span class="text-danger">Could not reach the server.</span>'; });
    });

    // Show the box only if at least one line on this bill is serialized.
    var serialised = document.querySelectorAll('.item-chk[data-serial="1"]');
    if (serialised.length) box.style.display = 'block';
    render();
})();
</script>
