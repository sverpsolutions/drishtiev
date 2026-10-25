<?php // FILE: application/views/accounting/journal_create.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">New Journal Entry</h5>
        <small class="text-muted">Manual posting — total debits must equal total credits</small>
    </div>
    <a href="<?= base_url('accounting/journal_entries') ?>" class="btn btn-outline-dark btn-sm">Back to Journal Entries</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<form method="post" id="jeForm">
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="form-row">
            <div class="col-md-3 form-group">
                <label class="font-weight-bold">Date</label>
                <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-9 form-group">
                <label class="font-weight-bold">Narration</label>
                <input type="text" name="narration" class="form-control" placeholder="What is this entry for?">
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th style="width:35%;">Account</th>
                        <th>Narration</th>
                        <th class="text-right" style="width:15%;">Debit</th>
                        <th class="text-right" style="width:15%;">Credit</th>
                        <th style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody id="jeRows"></tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-right font-weight-bold">Totals</td>
                        <td class="text-right font-weight-bold" id="totDebit">0.00</td>
                        <td class="text-right font-weight-bold" id="totCredit">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="p-3 border-top">
            <button type="button" class="btn btn-outline-dark btn-sm" onclick="addRow()">+ Add Line</button>
            <span id="balanceMsg" class="ml-3 font-weight-bold"></span>
        </div>
    </div>
</div>

<div class="mt-3">
    <button type="submit" class="btn btn-dark" id="postBtn" disabled>Post Journal Entry</button>
</div>
</form>

<script>
const ACCOUNTS = <?= json_encode(array_map(fn($a) => ['id' => $a->id, 'label' => $a->account_code . ' — ' . $a->account_name], $accounts)) ?>;
let rowCount = 0;

function addRow() {
    rowCount++;
    const tr = document.createElement('tr');
    tr.id = 'row_' + rowCount;
    let opts = '<option value="">Select account...</option>';
    ACCOUNTS.forEach(a => { opts += `<option value="${a.id}">${a.label}</option>`; });
    tr.innerHTML = `
        <td><select name="account_id[]" class="form-control form-control-sm">${opts}</select></td>
        <td><input type="text" name="line_narration[]" class="form-control form-control-sm"></td>
        <td><input type="text" name="debit[]" class="form-control form-control-sm text-right je-debit" value="0.00" onfocus="this.select()" oninput="recalc()"></td>
        <td><input type="text" name="credit[]" class="form-control form-control-sm text-right je-credit" value="0.00" onfocus="this.select()" oninput="recalc()"></td>
        <td class="text-center"><button type="button" class="btn btn-link text-danger p-0" onclick="document.getElementById('row_${rowCount}').remove(); recalc();">&times;</button></td>
    `;
    document.getElementById('jeRows').appendChild(tr);
}

function recalc() {
    let d = 0, c = 0;
    document.querySelectorAll('.je-debit').forEach(el => d += parseFloat(el.value) || 0);
    document.querySelectorAll('.je-credit').forEach(el => c += parseFloat(el.value) || 0);
    document.getElementById('totDebit').textContent = d.toFixed(2);
    document.getElementById('totCredit').textContent = c.toFixed(2);
    const balanced = Math.abs(d - c) < 0.01 && d > 0;
    const msg = document.getElementById('balanceMsg');
    if (d === 0 && c === 0) {
        msg.textContent = '';
    } else if (balanced) {
        msg.textContent = '✓ Balanced';
        msg.className = 'ml-3 font-weight-bold text-success';
    } else {
        msg.textContent = '✗ Not balanced — debit ' + d.toFixed(2) + ' vs credit ' + c.toFixed(2);
        msg.className = 'ml-3 font-weight-bold text-danger';
    }
    document.getElementById('postBtn').disabled = !balanced;
}

addRow();
addRow();
</script>
