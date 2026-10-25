<?php // FILE: application/views/payments/pay_supplier.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Pay Supplier</h5>
        <small class="text-muted"><a href="<?= base_url('payments') ?>">Payments</a> / Pay Supplier</small>
    </div>
    <a href="<?= base_url('payments') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if (validation_errors()): ?>
    <div class="alert alert-danger"><?= validation_errors('<div>','</div>') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:680px;">
    <div class="card-header bg-warning text-dark py-2 font-weight-bold" style="font-size:.85rem;">
        ↑ Pay to Supplier
    </div>
    <div class="card-body">
        <?= form_open('payments/pay_supplier') ?>

        <div class="form-group">
            <label>Supplier <span class="text-danger">*</span></label>
            <select name="supplier_id" id="supplier_id" class="form-control" required onchange="loadPurchases(this.value); checkGstFlag(this)">
                <option value="">-- Select Supplier --</option>
                <?php foreach (($suppliers_gst ?? []) as $s): $flagged = ($s->gst_compliance_status ?? 'ok') === 'flagged'; ?>
                <option value="<?= $s->id ?>" data-flagged="<?= $flagged ? '1':'0' ?>" data-note="<?= htmlspecialchars($s->gst_compliance_note ?? '') ?>">
                    <?= $flagged ? '⚠ ' : '' ?><?= htmlspecialchars($s->name) ?><?= $flagged ? ' (GST NOT FILED)' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div id="gstFlagWarning" class="alert alert-danger py-2 mt-2 mb-0" style="display:none;font-size:.85rem;">
                <i class="fas fa-exclamation-triangle mr-1"></i> <strong>This supplier is flagged GST non-compliant.</strong>
                <div id="gstFlagNote" class="mt-1"></div>
            </div>
        </div>

        <div class="form-group">
            <label class="d-block">Knock-off Mode</label>
            <div class="btn-group btn-group-toggle w-100" role="group">
                <label class="btn btn-outline-warning flex-fill mb-0" id="lblBillP">
                    <input type="radio" name="knockoff_mode" value="bill" checked onchange="setPMode('bill')"> Bill-wise
                </label>
                <label class="btn btn-outline-warning flex-fill mb-0" id="lblAutoP">
                    <input type="radio" name="knockoff_mode" value="auto" onchange="setPMode('auto')"> Auto Knock-off
                </label>
            </div>
            <small class="form-text text-muted">Auto Knock-off applies the amount paid across this supplier's oldest unpaid bills first, splitting across bills automatically.</small>
        </div>

        <div class="form-group" id="purchaseField">
            <label>Purchase Bill <span class="text-danger">*</span></label>
            <select name="purchase_id" id="purchase_id" class="form-control" onchange="fillPurchaseDue(this)">
                <option value="">-- Select Purchase --</option>
                <?php foreach ($purchases as $pid => $plabel): ?>
                <option value="<?= $pid ?>"><?= htmlspecialchars($plabel) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" id="pdueField">
            <label>Due Amount</label>
            <div class="form-control bg-light font-weight-bold text-warning" id="pdue_display">— Select purchase —</div>
        </div>

        <div class="form-group" id="autoPurchasesField" style="display:none;">
            <label>Outstanding Bills <small class="text-muted">(oldest first — payment applied top to bottom)</small></label>
            <div id="autoPurchasesList" class="border rounded p-2" style="max-height:180px;overflow-y:auto;font-size:.85rem;">
                <span class="text-muted">Select a supplier to load outstanding bills…</span>
            </div>
            <div class="d-flex justify-content-between font-weight-bold mt-1" style="font-size:.85rem;">
                <span>Total Due:</span>
                <span class="text-warning" id="autoPTotalDue">₹ 0.00</span>
            </div>
        </div>

        <div class="form-group">
            <label>Amount Paying (₹) <span class="text-danger">*</span></label>
            <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                <input type="number" step="0.01" name="amount" id="pamount" class="form-control"
                       value="0.00" min="0.01" required oninput="previewPSplit()">
            </div>
            <small class="form-text text-muted" id="psplitPreview"></small>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Payment Mode <span class="text-danger">*</span></label>
                    <select name="payment_mode" class="form-control" onchange="toggleBankField(this.value)">
                        <?php foreach(['cash'=>'Cash','neft'=>'NEFT/RTGS','upi'=>'UPI','cheque'=>'Cheque'] as $k=>$v): ?>
                        <option value="<?= $k ?>"><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Cheque / Transaction Ref No</label>
            <input type="text" name="reference_no" class="form-control" placeholder="Optional — cheque number for cheque payments">
        </div>

        <div class="form-group" id="bankAccountField" style="display:none;">
            <label>Bank Account</label>
            <select name="bank_account_id" id="bank_account_id" class="form-control">
                <option value="">-- Select Bank Account --</option>
                <?php foreach (($bank_accounts ?? []) as $ba): ?>
                <option value="<?= $ba->id ?>"><?= htmlspecialchars($ba->account_name) ?><?= $ba->bank_name ? ' — ' . htmlspecialchars($ba->bank_name) : '' ?></option>
                <?php endforeach; ?>
            </select>
            <small class="form-text text-muted">Needed to print this cheque — the field layout you design is remembered per bank account.</small>
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>

        <button type="submit" class="btn btn-warning btn-block font-weight-bold">Record Supplier Payment</button>
        <?= form_close() ?>
    </div>
</div>

<script>
let currentPMode = 'bill';
let supplierPurchases = []; // oldest-first list for the currently selected supplier

function setPMode(mode) {
    currentPMode = mode;
    document.getElementById('purchaseField').style.display      = mode === 'bill' ? '' : 'none';
    document.getElementById('pdueField').style.display           = mode === 'bill' ? '' : 'none';
    document.getElementById('autoPurchasesField').style.display  = mode === 'auto' ? '' : 'none';
    document.getElementById('purchase_id').required = (mode === 'bill');
    document.getElementById('lblBillP').classList.toggle('active', mode === 'bill');
    document.getElementById('lblAutoP').classList.toggle('active', mode === 'auto');
    if (mode === 'auto') previewPSplit();
}

function loadPurchases(sid) {
    if (!sid) return;
    fetch('<?= base_url("payments/get_purchases_by_supplier/") ?>' + sid, {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(r=>r.json())
        .then(data => {
            const sel = document.getElementById('purchase_id');
            sel.innerHTML = '<option value="">-- Select Purchase --</option>';
            data.forEach(p => {
                sel.insertAdjacentHTML('beforeend',
                    `<option value="${p.id}" data-due="${p.due_amount}">
                        ${p.purchase_no} — ₹ ${parseFloat(p.due_amount).toFixed(2)} due (${p.invoice_date})
                    </option>`);
            });
            document.getElementById('pdue_display').textContent = '—';

            supplierPurchases = data.slice().sort((a, b) => new Date(a.invoice_date) - new Date(b.invoice_date));
            renderAutoPurchases();
            if (currentPMode === 'auto') previewPSplit();
        });
}

function renderAutoPurchases() {
    const box = document.getElementById('autoPurchasesList');
    const totalDue = supplierPurchases.reduce((s, p) => s + parseFloat(p.due_amount || 0), 0);
    document.getElementById('autoPTotalDue').textContent = '₹ ' + totalDue.toFixed(2);
    if (!supplierPurchases.length) {
        box.innerHTML = '<span class="text-muted">No outstanding bills for this supplier.</span>';
        return;
    }
    box.innerHTML = supplierPurchases.map(p =>
        `<div class="d-flex justify-content-between border-bottom py-1" data-pur-id="${p.id}">
            <span>${p.purchase_no} <small class="text-muted">(${p.invoice_date})</small></span>
            <span class="font-weight-bold" data-due-cell>₹ ${parseFloat(p.due_amount).toFixed(2)}</span>
        </div>`
    ).join('');
}

function previewPSplit() {
    if (currentPMode !== 'auto') return;
    let remaining = parseFloat(document.getElementById('pamount').value) || 0;
    const rows = document.querySelectorAll('#autoPurchasesList [data-pur-id]');
    let note = [];
    rows.forEach(row => {
        const purId = row.dataset.purId;
        const p = supplierPurchases.find(x => String(x.id) === String(purId));
        const due = parseFloat(p.due_amount) || 0;
        const applied = Math.max(0, Math.min(remaining, due));
        row.querySelector('[data-due-cell]').innerHTML = applied > 0
            ? `<span class="text-success">₹ ${applied.toFixed(2)}</span> <span class="text-muted">of ₹${due.toFixed(2)}</span>`
            : `₹ ${due.toFixed(2)}`;
        if (applied > 0) note.push(p.purchase_no + ': ₹' + applied.toFixed(2));
        remaining -= applied;
    });
    const totalDue = supplierPurchases.reduce((s, p) => s + parseFloat(p.due_amount || 0), 0);
    let msg = note.length ? 'Will apply → ' + note.join(', ') + '.' : '';
    if (remaining > 0.004 && supplierPurchases.length) {
        msg += ' Amount exceeds total due (₹' + totalDue.toFixed(2) + ') — it will be capped.';
    }
    document.getElementById('psplitPreview').textContent = msg;
}

function fillPurchaseDue(sel) {
    const opt = sel.options[sel.selectedIndex];
    const due = parseFloat(opt.dataset.due) || 0;
    document.getElementById('pdue_display').textContent = '₹ ' + due.toFixed(2);
    document.getElementById('pamount').value = due.toFixed(2);
}

function checkGstFlag(sel) {
    const opt = sel.options[sel.selectedIndex];
    const warn = document.getElementById('gstFlagWarning');
    if (opt && opt.dataset.flagged === '1') {
        document.getElementById('gstFlagNote').textContent = opt.dataset.note || '';
        warn.style.display = '';
    } else {
        warn.style.display = 'none';
    }
}

function toggleBankField(mode) {
    document.getElementById('bankAccountField').style.display = mode === 'cheque' ? '' : 'none';
}

setPMode('bill');
</script>
