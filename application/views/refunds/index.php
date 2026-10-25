<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-undo mr-1 text-warning"></i> Refunds & Returns</h5>
        <small class="text-muted">All processed refunds and stock returns</small>
    </div>
    <a href="<?= base_url('refunds/create') ?>" class="btn btn-warning btn-sm font-weight-bold"><i class="fas fa-plus mr-1"></i> New Refund</a>
</div>

<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?= $this->session->flashdata('success') ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div><?php endif; ?>

<!-- Search Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <?= form_open('refunds', ['method' => 'GET', 'id' => 'filterForm']) ?>
        <div class="form-row align-items-end">
            <div class="col-md-3">
                <label class="small font-weight-bold">Invoice No</label>
                <input type="text" name="invoice_no" class="form-control form-control-sm" placeholder="e.g. INV-2026-0001" value="<?= htmlspecialchars($filters['invoice_no'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="small font-weight-bold">Customer Phone</label>
                <input type="text" name="customer_phone" class="form-control form-control-sm" placeholder="Mobile number" value="<?= htmlspecialchars($filters['customer_phone'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small font-weight-bold">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small font-weight-bold">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary btn-block"><i class="fas fa-search mr-1"></i> Search</button>
            </div>
        </div>
        <?= form_close() ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.84rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Refund #</th>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th class="text-right">Amount</th>
                        <th>Mode</th>
                        <th>Reason</th>
                        <th>By</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($refunds)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No refunds found.</td></tr>
                <?php else: ?>
                <?php foreach ($refunds as $r): ?>
                <tr>
                    <td><code><?= htmlspecialchars($r->refund_no) ?></code></td>
                    <td><a href="<?= base_url('billing/invoice/'.'' ) ?>"><?= htmlspecialchars($r->invoice_no) ?></a></td>
                    <td>
                        <div class="font-weight-bold"><?= htmlspecialchars($r->customer_name ?? '—') ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($r->customer_phone ?? '') ?></div>
                    </td>
                    <td><span class="badge badge-<?= $r->refund_type === 'full' ? 'danger' : 'warning' ?>"><?= ucfirst($r->refund_type) ?></span></td>
                    <td class="text-right font-weight-bold text-danger">&#8377; <?= number_format($r->refund_amount, 2) ?></td>
                    <td><span class="badge badge-secondary"><?= strtoupper($r->refund_mode) ?></span></td>
                    <td><?= htmlspecialchars(substr($r->reason, 0, 40)) ?><?= strlen($r->reason) > 40 ? '…' : '' ?></td>
                    <td><?= htmlspecialchars($r->created_by_name) ?></td>
                    <td><?= date('d M Y', strtotime($r->created_at)) ?><br><small class="text-muted"><?= date('H:i', strtotime($r->created_at)) ?></small></td>
                    <td><a href="<?= base_url('refunds/view/'.$r->id) ?>" class="btn btn-xs btn-outline-secondary py-0 px-2">View</a></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($refunds)): ?>
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td colspan="4" class="text-right pr-3">Total Refunded:</td>
                        <td class="text-right text-danger">&#8377; <?= number_format(array_sum(array_column(array_map(fn($r) => ['a' => (float)$r->refund_amount], $refunds), 'a')), 2) ?></td>
                        <td colspan="5"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
