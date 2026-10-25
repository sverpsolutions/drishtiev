<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-credit-card mr-1 text-danger"></i> Credit Sales Report</h5>
        <small class="text-muted">Pending credit invoices with outstanding balance</small>
    </div>
    <button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fas fa-print mr-1"></i> Print</button>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <?= form_open('reports/credit_sales', ['method' => 'GET']) ?>
        <div class="form-row align-items-end">
            <div class="col-md-3">
                <label class="small font-weight-bold">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $date_from ?>">
            </div>
            <div class="col-md-3">
                <label class="small font-weight-bold">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $date_to ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-primary btn-block">Apply</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<?php
$total_due = array_sum(array_map(fn($r) => max(0, (float)$r->due_amount), $invoices));
$overdue_count = 0;
foreach ($invoices as $inv) {
    if (!empty($inv->credit_due_date) && strtotime($inv->credit_due_date) < time()) $overdue_count++;
}
?>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="small text-muted">Total Pending Bills</div>
            <h4 class="font-weight-bold text-warning mb-0"><?= count($invoices) ?></h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="small text-muted">Total Outstanding</div>
            <h4 class="font-weight-bold text-danger mb-0">&#8377; <?= number_format($total_due, 2) ?></h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="small text-muted">Overdue Bills</div>
            <h4 class="font-weight-bold text-danger mb-0"><?= $overdue_count ?></h4>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.83rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Due</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($invoices)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No pending credit invoices in this period.</td></tr>
                <?php else: ?>
                <?php foreach ($invoices as $inv): ?>
                <?php $is_overdue = !empty($inv->credit_due_date) && strtotime($inv->credit_due_date) < time(); ?>
                <tr class="<?= $is_overdue ? 'table-danger' : '' ?>">
                    <td><a href="<?= base_url('billing/invoice/'.$inv->id) ?>"><code><?= $inv->invoice_no ?></code></a></td>
                    <td>
                        <div class="font-weight-bold"><?= htmlspecialchars($inv->customer_name) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($inv->customer_phone ?? '') ?></div>
                    </td>
                    <td><?= date('d M Y', strtotime($inv->invoice_date)) ?></td>
                    <td class="text-right">&#8377; <?= number_format($inv->total_amount, 2) ?></td>
                    <td class="text-right text-success">&#8377; <?= number_format($inv->paid_amount, 2) ?></td>
                    <td class="text-right font-weight-bold text-danger">&#8377; <?= number_format($inv->due_amount, 2) ?></td>
                    <td>
                        <?php if (!empty($inv->credit_due_date)): ?>
                        <?= date('d M Y', strtotime($inv->credit_due_date)) ?>
                        <?php if ($is_overdue): ?><br><span class="badge badge-danger">Overdue</span><?php endif; ?>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-<?= $inv->status === 'partial' ? 'warning' : 'danger' ?>"><?= ucfirst($inv->status) ?></span></td>
                    <td><a href="<?= base_url('billing/invoice/'.$inv->id) ?>" class="btn btn-xs btn-outline-secondary py-0 px-1">View</a></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($invoices)): ?>
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td colspan="5" class="text-right">Total Outstanding:</td>
                        <td class="text-right text-danger">&#8377; <?= number_format($total_due, 2) ?></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
