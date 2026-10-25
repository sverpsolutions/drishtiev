<?php
// FILE: application/views/payments/receive.php
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Record Customer Payment</h5>
        <small class="text-muted"><a href="<?= base_url('payments') ?>">Payments</a> / Receive</small>
    </div>
    <a href="<?= base_url('payments') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if (validation_errors()): ?>
    <div class="alert alert-danger"><?= validation_errors('<div>','</div>') ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:680px;">
    <div class="card-header bg-success text-white py-2 font-weight-bold" style="font-size:.85rem;">
        ↓ Receive Payment from Customer
    </div>
    <div class="card-body">
        <?= form_open('payments/receive') ?>

        <div class="form-group">
            <label>Customer <span class="text-danger">*</span></label>
            <select name="customer_id" id="customer_id" class="form-control" required onchange="loadInvoices(this.value)">
                <option value=""><?= $customers ? '-- Select Customer (' . count($customers) . ' with dues) --' : '-- No customer has any due --' ?></option>
                <?php foreach ($customers as $cid => $cname): ?>
                <option value="<?= $cid ?>" <?= set_value('customer_id') == $cid ? 'selected':'' ?>><?= htmlspecialchars($cname) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="d-block">Knock-off Mode</label>
            <div class="btn-group btn-group-toggle w-100" role="group">
                <label class="btn btn-outline-success flex-fill mb-0" id="lblBill">
                    <input type="radio" name="knockoff_mode" value="bill" checked onchange="setMode('bill')"> Bill-wise
                </label>
                <label class="btn btn-outline-success flex-fill mb-0" id="lblAuto">
                    <input type="radio" name="knockoff_mode" value="auto" onchange="setMode('auto')"> Auto Knock-off
                </label>
            </div>
            <small class="form-text text-muted">Auto Knock-off applies the amount received across this customer's oldest unpaid bills first, splitting across bills automatically.</small>
        </div>

        <div class="form-group" id="invoiceField">
            <label>Invoice <span class="text-danger">*</span></label>
            <select name="invoice_id" id="invoice_id" class="form-control" onchange="fillDue(this)">
                <option value="">-- Select Invoice --</option>
                <?php foreach ($invoices as $iid => $iname): ?>
                <option value="<?= $iid ?>" <?= set_value('invoice_id') == $iid ? 'selected':'' ?>><?= htmlspecialchars($iname) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" id="dueField">
            <label>Due Amount</label>
            <div class="form-control bg-light font-weight-bold text-danger" id="due_display">— Select invoice —</div>
        </div>

        <div class="form-group" id="autoBillsField" style="display:none;">
            <label>Outstanding Bills <small class="text-muted">(oldest first — payment applied top to bottom)</small></label>
            <div id="autoBillsList" class="border rounded p-2" style="max-height:180px;overflow-y:auto;font-size:.85rem;">
                <span class="text-muted">Select a customer to load outstanding bills…</span>
            </div>
            <div class="d-flex justify-content-between font-weight-bold mt-1" style="font-size:.85rem;">
                <span>Total Due:</span>
                <span class="text-danger" id="autoTotalDue">₹ 0.00</span>
            </div>
        </div>

        <div class="form-group">
            <label>Amount Receiving (₹) <span class="text-danger">*</span></label>
            <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                <input type="number" step="0.01" name="amount" id="amount" class="form-control"
                       value="<?= set_value('amount', '0.00') ?>" min="0.01" required oninput="previewSplit()">
            </div>
            <small class="form-text text-muted" id="splitPreview"></small>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Payment Mode <span class="text-danger">*</span></label>
                    <select name="payment_mode" class="form-control">
                        <?php foreach(['cash'=>'Cash','upi'=>'UPI','neft'=>'NEFT/Bank','cheque'=>'Cheque'] as $k=>$v): ?>
                        <option value="<?= $k ?>" <?= set_value('payment_mode') === $k ? 'selected':'' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Payment Date <span class="text-danger">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="<?= set_value('payment_date', date('Y-m-d')) ?>" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Transaction / Cheque Ref No</label>
            <input type="text" name="reference_no" class="form-control" value="<?= set_value('reference_no') ?>" placeholder="Optional">
        </div>

        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" class="form-control" rows="2"><?= set_value('notes') ?></textarea>
        </div>

        <button type="submit" class="btn btn-success btn-block">Record Payment</button>
        <?= form_close() ?>
    </div>
</div>

<script>
let currentMode = 'bill';
let customerInvoices = []; // oldest-first list for the currently selected customer

function setMode(mode) {
    currentMode = mode;
    document.getElementById('invoiceField').style.display    = mode === 'bill' ? '' : 'none';
    document.getElementById('dueField').style.display        = mode === 'bill' ? '' : 'none';
    document.getElementById('autoBillsField').style.display  = mode === 'auto' ? '' : 'none';
    document.getElementById('invoice_id').required = (mode === 'bill');
    document.getElementById('lblBill').classList.toggle('active', mode === 'bill');
    document.getElementById('lblAuto').classList.toggle('active', mode === 'auto');
    if (mode === 'auto') previewSplit();
}

function loadInvoices(cid) {
    if (!cid) return;
    fetch('<?= base_url("payments/get_invoices_by_customer/") ?>' + cid, {headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(r=>r.json())
        .then(data => {
            // Bill-wise dropdown (as-is)
            const sel = document.getElementById('invoice_id');
            sel.innerHTML = '<option value="">-- Select Invoice --</option>';
            data.forEach(inv => {
                sel.insertAdjacentHTML('beforeend',
                    `<option value="${inv.id}" data-due="${inv.due_amount}">
                        ${inv.invoice_no} — ₹ ${parseFloat(inv.due_amount).toFixed(2)} due (${inv.invoice_date})
                    </option>`);
            });
            document.getElementById('due_display').textContent = '—';

            // Auto knock-off preview list — oldest first
            customerInvoices = data.slice().sort((a, b) => new Date(a.invoice_date) - new Date(b.invoice_date));
            renderAutoBills();
            if (currentMode === 'auto') previewSplit();
        });
}

function renderAutoBills() {
    const box = document.getElementById('autoBillsList');
    const totalDue = customerInvoices.reduce((s, inv) => s + parseFloat(inv.due_amount || 0), 0);
    document.getElementById('autoTotalDue').textContent = '₹ ' + totalDue.toFixed(2);
    if (!customerInvoices.length) {
        box.innerHTML = '<span class="text-muted">No outstanding bills for this customer.</span>';
        return;
    }
    box.innerHTML = customerInvoices.map(inv =>
        `<div class="d-flex justify-content-between border-bottom py-1" data-inv-id="${inv.id}">
            <span>${inv.invoice_no} <small class="text-muted">(${inv.invoice_date})</small></span>
            <span class="font-weight-bold" data-due-cell>₹ ${parseFloat(inv.due_amount).toFixed(2)}</span>
        </div>`
    ).join('');
}

function previewSplit() {
    if (currentMode !== 'auto') return;
    let remaining = parseFloat(document.getElementById('amount').value) || 0;
    const rows = document.querySelectorAll('#autoBillsList [data-inv-id]');
    let note = [];
    rows.forEach(row => {
        const invId = row.dataset.invId;
        const inv = customerInvoices.find(i => String(i.id) === String(invId));
        const due = parseFloat(inv.due_amount) || 0;
        const applied = Math.max(0, Math.min(remaining, due));
        row.querySelector('[data-due-cell]').innerHTML = applied > 0
            ? `<span class="text-success">₹ ${applied.toFixed(2)}</span> <span class="text-muted">of ₹${due.toFixed(2)}</span>`
            : `₹ ${due.toFixed(2)}`;
        if (applied > 0) note.push(inv.invoice_no + ': ₹' + applied.toFixed(2));
        remaining -= applied;
    });
    const totalDue = customerInvoices.reduce((s, inv) => s + parseFloat(inv.due_amount || 0), 0);
    let msg = note.length ? 'Will apply → ' + note.join(', ') + '.' : '';
    if (remaining > 0.004 && customerInvoices.length) {
        msg += ' Amount exceeds total due (₹' + totalDue.toFixed(2) + ') — it will be capped.';
    }
    document.getElementById('splitPreview').textContent = msg;
}

function fillDue(sel) {
    const opt = sel.options[sel.selectedIndex];
    const due = parseFloat(opt.dataset.due) || 0;
    document.getElementById('due_display').textContent = '₹ ' + due.toFixed(2);
    document.getElementById('amount').value = due.toFixed(2);
}

setMode('bill');
</script>
