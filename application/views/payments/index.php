<?php // FILE: application/views/payments/index.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Payments</h5>
        <small class="text-muted">Customer receipts &amp; supplier payments</small>
    </div>
    <div>
        <a href="<?= base_url('payments/receive') ?>" class="btn btn-success btn-sm">+ Receive Payment</a>
        <a href="<?= base_url('payments/pay_supplier') ?>" class="btn btn-warning btn-sm ml-1">+ Pay Supplier</a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<!-- Summary cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1" style="font-size:.8rem;">Total Received</p>
                <h4 class="text-success font-weight-bold">₹ <?= number_format($total_in, 2) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1" style="font-size:.8rem;">Total Paid Out</p>
                <h4 class="text-danger font-weight-bold">₹ <?= number_format($total_out, 2) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <p class="text-muted mb-1" style="font-size:.8rem;">Net Position</p>
                <h4 class="font-weight-bold <?= ($total_in - $total_out) >= 0 ? 'text-success':'text-danger' ?>">
                    ₹ <?= number_format($total_in - $total_out, 2) ?>
                </h4>
            </div>
        </div>
    </div>
</div>

<!-- Filter bar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row">
            <div class="col-md-5">
                <input type="text" id="paySearch" class="form-control form-control-sm" placeholder="Search payment no, party...">
            </div>
            <div class="col-md-2">
                <select id="typeFilter" class="form-control form-control-sm">
                    <option value="">All Types</option>
                    <option value="received">Received</option>
                    <option value="paid">Paid Out</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="payTable" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Payment #</th>
                        <th>Type</th>
                        <th>Party</th>
                        <th>Ref</th>
                        <th>Mode</th>
                        <th>Ref No</th>
                        <th>Date</th>
                        <th class="text-right">Amount</th>
                        <th>By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $p): ?>
                    <tr data-type="<?= $p->type ?>">
                        <td class="font-weight-bold"><?= htmlspecialchars($p->payment_no) ?></td>
                        <td>
                            <span class="badge badge-<?= $p->type === 'received' ? 'success':'warning' ?>">
                                <?= $p->type === 'received' ? '↓ Received' : '↑ Paid Out' ?>
                            </span>
                        </td>
                        <td><?= ucfirst($p->party_type) ?> #<?= $p->party_id ?></td>
                        <td><small><?= ucfirst($p->ref_type) ?> #<?= $p->ref_id ?></small></td>
                        <td><?= ucfirst($p->payment_mode) ?></td>
                        <td><?= htmlspecialchars($p->reference_no ?? '—') ?></td>
                        <td><?= date('d M Y', strtotime($p->payment_date)) ?></td>
                        <td class="text-right font-weight-bold <?= $p->type==='received'?'text-success':'text-danger' ?>">
                            <?= $p->type==='received' ? '+' : '-' ?> ₹ <?= number_format($p->amount, 2) ?>
                        </td>
                        <td><?= htmlspecialchars($p->created_by_name) ?></td>
                        <td>
                            <?php if ($p->type === 'paid' && $p->payment_mode === 'cheque'): ?>
                            <a href="<?= base_url('payments/print_cheque/' . $p->id) ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Print cheque">🖨</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No payments recorded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.getElementById('paySearch').addEventListener('input', filterPay);
document.getElementById('typeFilter').addEventListener('change', filterPay);
function filterPay() {
    const q = document.getElementById('paySearch').value.toLowerCase();
    const t = document.getElementById('typeFilter').value;
    document.querySelectorAll('#payTable tbody tr').forEach(r => {
        r.style.display = (r.textContent.toLowerCase().includes(q) && (!t || r.dataset.type === t)) ? '' : 'none';
    });
}
</script>
