<?php $editing = !empty($quotation); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><?= $editing ? 'Edit Quotation #'.htmlspecialchars($quotation->quotation_no) : 'New Army Quotation' ?></h5>
        <small class="text-muted"><a href="<?= base_url('army_quotations') ?>">Army Quotations</a> / <?= $editing ? 'Edit' : 'New' ?></small>
    </div>
    <a href="<?= base_url('army_quotations') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<?= form_open($editing ? 'army_quotations/edit/'.$quotation->id : 'army_quotations/create', ['id' => 'aqForm']) ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 font-weight-bold">Quotation Details</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group">
                <label class="small font-weight-bold">Company <span class="text-danger">*</span></label>
                <select name="company_id" class="form-control" required>
                    <option value="">Select Company</option>
                    <?php foreach ($companies as $c): ?>
                    <option value="<?= $c->id ?>" <?= ($quotation->company_id ?? '')==$c->id?'selected':'' ?>><?= htmlspecialchars($c->company_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 form-group">
                <label class="small font-weight-bold">Date</label>
                <input type="date" name="quotation_date" class="form-control" value="<?= $quotation->quotation_date ?? date('Y-m-d') ?>">
            </div>
            <div class="col-md-3 form-group">
                <label class="small font-weight-bold">Valid Till</label>
                <input type="date" name="valid_till" class="form-control" value="<?= $quotation->valid_till ?? '' ?>">
            </div>
            <div class="col-md-3 form-group">
                <label class="small font-weight-bold">Prepared By</label>
                <input type="text" name="prepared_by" class="form-control" value="<?= htmlspecialchars($quotation->prepared_by ?? $this->session->userdata('name') ?? '') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 form-group">
                <label class="small font-weight-bold">Customer Name <span class="text-danger">*</span></label>
                <input type="text" name="customer_name" class="form-control" required value="<?= htmlspecialchars($quotation->customer_name ?? '') ?>">
            </div>
            <div class="col-md-4 form-group">
                <label class="small font-weight-bold">Contact Person</label>
                <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($quotation->contact_person ?? '') ?>">
            </div>
            <div class="col-md-4 form-group">
                <label class="small font-weight-bold">Mobile</label>
                <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($quotation->mobile ?? '') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-8 form-group">
                <label class="small font-weight-bold">Address</label>
                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($quotation->address ?? '') ?></textarea>
            </div>
            <div class="col-md-4 form-group">
                <label class="small font-weight-bold">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($quotation->email ?? '') ?>">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 form-group">
                <label class="small font-weight-bold">Subject</label>
                <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($quotation->subject ?? '') ?>">
            </div>
            <div class="col-md-6 form-group">
                <label class="small font-weight-bold">Reference</label>
                <input type="text" name="reference" class="form-control" value="<?= htmlspecialchars($quotation->reference ?? '') ?>">
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold">Quotation Items</span>
        <button type="button" class="btn btn-sm btn-dark" id="addRowBtn">+ Add Row</button>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" id="itemsTable">
            <thead class="thead-light">
                <tr>
                    <th style="width:5%;">Sr.No</th>
                    <th>Particulars</th>
                    <th style="width:9%;">Qty</th>
                    <th style="width:11%;">Unit</th>
                    <th style="width:14%;">Rate</th>
                    <th style="width:14%;" class="text-right">Amount</th>
                    <th style="width:4%;"></th>
                </tr>
            </thead>
            <tbody id="itemsBody">
                <?php
                $rows = !empty($items) ? $items : [(object)['particulars'=>'','qty'=>'','unit'=>'Nos','rate'=>'','amount'=>0]];
                foreach ($rows as $it):
                ?>
                <tr class="item-row">
                    <td class="sr-no text-center">1</td>
                    <td><input type="text" name="particulars[]" class="form-control form-control-sm" value="<?= htmlspecialchars($it->particulars) ?>"></td>
                    <td><input type="number" step="0.01" name="qty[]" class="form-control form-control-sm qty-input" value="<?= $it->qty ?>"></td>
                    <td><input type="text" name="unit[]" class="form-control form-control-sm" placeholder="Nos / Sq Ft" value="<?= htmlspecialchars($it->unit ?? 'Nos') ?>"></td>
                    <td><input type="number" step="0.01" name="rate[]" class="form-control form-control-sm rate-input" value="<?= $it->rate ?>"></td>
                    <td class="text-right amount-cell">₹ <?= number_format($it->amount ?? 0, 2) ?></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 font-weight-bold">Terms & Conditions</div>
            <div class="card-body">
                <textarea name="terms_text" class="form-control" rows="8"><?= htmlspecialchars($quotation->terms_text ?? $default_terms ?? '') ?></textarea>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 font-weight-bold">Totals</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><td>Sub Total</td><td class="text-right" id="subTotalDisplay">₹ 0.00</td></tr>
                    <tr>
                        <td style="width:50%;">
                            GST %
                            <input type="number" step="0.01" name="gst_percent" id="gstPercent" class="form-control form-control-sm d-inline-block" style="width:90px;"
                                   value="<?= $quotation->gst_percent ?? 18.00 ?>">
                        </td>
                        <td class="text-right" id="gstAmountDisplay">₹ 0.00</td>
                    </tr>
                    <tr class="font-weight-bold" style="font-size:1.05rem;border-top:2px solid #000;">
                        <td>Grand Total</td>
                        <td class="text-right" id="grandTotalDisplay">₹ 0.00</td>
                    </tr>
                </table>
            </div>
        </div>
        <button type="submit" class="btn btn-dark btn-block font-weight-bold py-2"><?= $editing ? 'Update Quotation' : 'Save Quotation' ?></button>
    </div>
</div>
<?= form_close() ?>

<script>
const itemsBody   = document.getElementById('itemsBody');
const addRowBtn    = document.getElementById('addRowBtn');
const gstPercentEl = document.getElementById('gstPercent');
const AQ_DRAFT_KEY = 'aq_draft_<?= $editing ? $quotation->id : 'new' ?>';

function rowTemplate() {
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
        <td class="sr-no text-center"></td>
        <td><input type="text" name="particulars[]" class="form-control form-control-sm"></td>
        <td><input type="number" step="0.01" name="qty[]" class="form-control form-control-sm qty-input"></td>
        <td><input type="text" name="unit[]" class="form-control form-control-sm" placeholder="Nos / Sq Ft" value="Nos"></td>
        <td><input type="number" step="0.01" name="rate[]" class="form-control form-control-sm rate-input"></td>
        <td class="text-right amount-cell">₹ 0.00</td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button></td>
    `;
    return tr;
}

function renumberRows() {
    itemsBody.querySelectorAll('tr.item-row').forEach((tr, i) => {
        tr.querySelector('.sr-no').textContent = i + 1;
    });
}

function recalcAll() {
    let subTotal = 0;
    itemsBody.querySelectorAll('tr.item-row').forEach(tr => {
        const qty  = parseFloat(tr.querySelector('.qty-input').value) || 0;
        const rate = parseFloat(tr.querySelector('.rate-input').value) || 0;
        const amt  = qty * rate;
        tr.querySelector('.amount-cell').textContent = '₹ ' + amt.toFixed(2);
        subTotal += amt;
    });
    const gstPct    = parseFloat(gstPercentEl.value) || 0;
    const gstAmount = subTotal * gstPct / 100;
    const grandTotal = subTotal + gstAmount;
    document.getElementById('subTotalDisplay').textContent   = '₹ ' + subTotal.toFixed(2);
    document.getElementById('gstAmountDisplay').textContent  = '₹ ' + gstAmount.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = '₹ ' + grandTotal.toFixed(2);
    saveDraft();
}

addRowBtn.addEventListener('click', function() {
    itemsBody.appendChild(rowTemplate());
    renumberRows();
});

itemsBody.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-row')) {
        const rows = itemsBody.querySelectorAll('tr.item-row');
        if (rows.length > 1) {
            e.target.closest('tr').remove();
            renumberRows();
            recalcAll();
        }
    }
});

itemsBody.addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-input') || e.target.classList.contains('rate-input')) {
        recalcAll();
    }
});
gstPercentEl.addEventListener('input', recalcAll);
renumberRows();
recalcAll();

// ── Draft autosave (localStorage) — new quotations only; edit forms
// already hold real saved data so they're never overwritten by a stale draft.
const aqForm = document.getElementById('aqForm');
const isNewQuotation = <?= $editing ? 'false' : 'true' ?>;

function collectHeaderFields() {
    const header = {};
    aqForm.querySelectorAll('input,textarea,select').forEach(el => {
        if (el.name && !el.name.endsWith('[]')) header[el.name] = el.value;
    });
    return header;
}

function collectItems() {
    const items = [];
    itemsBody.querySelectorAll('tr.item-row').forEach(tr => {
        items.push({
            particulars: tr.querySelector('[name="particulars[]"]').value,
            qty:  tr.querySelector('.qty-input').value,
            unit: tr.querySelector('[name="unit[]"]').value,
            rate: tr.querySelector('.rate-input').value,
        });
    });
    return items;
}

function saveDraft() {
    if (!isNewQuotation) return;
    try {
        localStorage.setItem(AQ_DRAFT_KEY, JSON.stringify({ header: collectHeaderFields(), items: collectItems() }));
    } catch (e) { /* ignore storage errors */ }
}

function restoreDraftIfPresent() {
    if (!isNewQuotation) return;
    let draft = null;
    try { draft = JSON.parse(localStorage.getItem(AQ_DRAFT_KEY) || 'null'); } catch (e) { return; }
    if (!draft) return;

    const customerField = aqForm.querySelector('[name="customer_name"]');
    if (customerField.value.trim() !== '') return; // form already has data, don't clobber it
    if (!confirm('A saved draft was found for this new quotation. Restore it?')) {
        try { localStorage.removeItem(AQ_DRAFT_KEY); } catch (e) {}
        return;
    }

    Object.entries(draft.header || {}).forEach(([name, value]) => {
        const el = aqForm.querySelector(`[name="${name}"]`);
        if (el) el.value = value;
    });

    if (Array.isArray(draft.items) && draft.items.length) {
        itemsBody.innerHTML = '';
        draft.items.forEach(it => {
            const tr = rowTemplate();
            tr.querySelector('[name="particulars[]"]').value = it.particulars || '';
            tr.querySelector('.qty-input').value  = it.qty  || '';
            tr.querySelector('[name="unit[]"]').value = it.unit || 'Nos';
            tr.querySelector('.rate-input').value = it.rate || '';
            itemsBody.appendChild(tr);
        });
        renumberRows();
        recalcAll();
    }
}

aqForm.addEventListener('input', saveDraft);
aqForm.addEventListener('submit', function() {
    try { localStorage.removeItem(AQ_DRAFT_KEY); } catch (e) {}
});
restoreDraftIfPresent();
</script>
