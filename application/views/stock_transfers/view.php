<?php // FILE: application/views/stock_transfers/view.php
$t = $transfer;
$has_suggestions = false;
if (!empty($items)) {
    foreach ($items as $_it) {
        if (!empty($_it->prev_day_sales_qty) || !empty($_it->suggested_qty)) {
            $has_suggestions = true;
            break;
        }
    }
}
$status_badges = [
    'draft'     => 'secondary',
    'pending'   => 'warning',
    'approved'  => 'info',
    'rejected'  => 'danger',
    'completed' => 'success',
    'in_transit'=> 'primary',
];
$status_label = function ($st) { return $st === 'in_transit' ? 'In Transit' : ucfirst($st); };
?>
<div class="d-flex justify-content-between align-items-start mb-3 no-print">
    <div>
        <h5 class="font-weight-bold mb-0">Transfer Out</h5>
        <small class="text-muted"><a href="<?= base_url('stock_transfers') ?>">Stock Transfers</a> / <?= htmlspecialchars($t->transfer_no) ?></small>
    </div>
    <div>
        <?php if (!empty($can_edit)): ?>
        <a href="<?= base_url('stock_transfers/edit/' . $t->id) ?>" class="btn btn-sm btn-outline-primary mr-1">
            <i class="fas fa-edit mr-1"></i> Edit
        </a>
        <?php endif; ?>
        <a href="<?= base_url('stock_transfers/print_transfer/' . $t->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary mr-1">
            <i class="fas fa-print mr-1"></i> Print
        </a>
        <a href="<?= base_url('stock_transfers') ?>" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success no-print"><?= $this->session->flashdata('success') ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger no-print"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-3" id="transferVoucher">
    <!-- Voucher Header -->
    <div class="card-body border-bottom">
        <div class="row">
            <div class="col-6">
                <div class="d-print-block">
                    <h4 class="font-weight-bold text-primary"><?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?></h4>
                    <small class="text-muted"><?= htmlspecialchars($company['address'] ?? '') ?></small>
                </div>
            </div>
            <div class="col-6 text-right">
                <h5 class="font-weight-bold">TRANSFER OUT</h5>
                <p class="mb-1"><strong>Transfer Out No.:</strong> <?= htmlspecialchars($t->transfer_no) ?></p>
                <p class="mb-1"><strong>Transfer In No.:</strong>
                    <?php if (!empty($t->tri_id)): ?>
                        <a href="<?= base_url('stock_transfers/transfer_in_view/' . $t->tri_id) ?>" class="font-weight-bold"><?= htmlspecialchars($t->tri_no) ?></a>
                    <?php else: ?>
                        <span class="text-muted small">Created on approval</span>
                    <?php endif; ?>
                </p>
                <p class="mb-1"><strong>Transfer Date:</strong> <?= date('d M Y', strtotime($t->requested_at)) ?></p>
                <p class="mb-0">
                    <strong>Status:</strong>
                    <span class="badge badge-<?= $status_badges[$t->status] ?? 'secondary' ?>"><?= $status_label($t->status) ?></span>
                </p>
            </div>
        </div>
    </div>

    <!-- From / To -->
    <div class="card-body border-bottom">
        <div class="row">
            <div class="col-md-5">
                <h6 class="text-muted text-uppercase small font-weight-bold mb-1">From Location</h6>
                <p class="font-weight-bold mb-0"><?= htmlspecialchars($t->from_location_name) ?><?= !empty($t->from_location_code) ? ' <small class="text-muted">(' . htmlspecialchars($t->from_location_code) . ')</small>' : '' ?></p>
                <small class="text-muted"><?= ucfirst($t->from_location_type ?? '') ?></small>
            </div>
            <div class="col-md-2 text-center d-flex align-items-center justify-content-center">
                <i class="fas fa-arrow-right fa-2x text-primary"></i>
            </div>
            <div class="col-md-5">
                <h6 class="text-muted text-uppercase small font-weight-bold mb-1">To Location</h6>
                <p class="font-weight-bold mb-0"><?= htmlspecialchars($t->to_location_name) ?><?= !empty($t->to_location_code) ? ' <small class="text-muted">(' . htmlspecialchars($t->to_location_code) . ')</small>' : '' ?></p>
                <small class="text-muted"><?= ucfirst($t->to_location_type ?? '') ?></small>
            </div>
        </div>
        <?php if ($t->reason): ?>
            <div class="mt-2"><strong>Reason:</strong> <?= htmlspecialchars($t->reason) ?></div>
        <?php endif; ?>
        <?php if ($t->notes): ?>
            <div><strong>Notes:</strong> <?= htmlspecialchars($t->notes) ?></div>
        <?php endif; ?>
    </div>

    <!-- Items Table -->
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Item Code</th>
                    <th>EAN Code</th>
                    <th>HSN</th>
                    <th>Unit</th>
                    <?php if ($has_suggestions): ?>
                    <th class="text-right">Prev Day Sales</th>
                    <th class="text-right">Suggested Qty</th>
                    <?php endif; ?>
                    <th class="text-right">Requested Qty</th>
                    <th class="text-right">Approved Qty</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Value</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
            <?php $total_value = 0; ?>
            <?php foreach ($items as $i => $item): ?>
                <?php $aqty = $item->approved_qty ?? $item->requested_qty; $val = $aqty * $item->rate; $total_value += $val; ?>
                <tr>
                    <td><?= $i+1 ?></td>
                    <td><?= htmlspecialchars($item->product_name) ?></td>
                    <td><small><?= htmlspecialchars($item->item_code ?? '') ?></small></td>
                    <td><small><?= htmlspecialchars($item->ean_code ?? '') ?></small></td>
                    <td><small><?= htmlspecialchars($item->hsn_code) ?></small></td>
                    <td><?= htmlspecialchars($item->unit) ?></td>
                    <?php if ($has_suggestions): ?>
                    <td class="text-right text-info"><?= !empty($item->prev_day_sales_qty) ? number_format((float)$item->prev_day_sales_qty, 3) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-right"><?= !empty($item->suggested_qty) ? number_format((float)$item->suggested_qty, 3) : '<span class="text-muted">—</span>' ?></td>
                    <?php endif; ?>
                    <td class="text-right"><?= number_format($item->requested_qty, 3) ?></td>
                    <td class="text-right">
                        <?= $item->approved_qty !== null ? number_format($item->approved_qty, 3) : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td class="text-right">&#8377;<?= number_format($item->rate, 2) ?></td>
                    <td class="text-right">&#8377;<?= number_format($val, 2) ?></td>
                    <td><small><?= htmlspecialchars($item->remarks ?? '') ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-active">
                    <td colspan="<?= $has_suggestions ? 12 : 10 ?>" class="text-right font-weight-bold">Total Value:</td>
                    <td class="text-right font-weight-bold">&#8377;<?= number_format($total_value, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Audit Trail -->
    <div class="card-body border-top">
        <div class="row text-center small">
            <div class="col-md-4">
                <strong>Requested By</strong><br>
                <?= htmlspecialchars($t->requested_by_name) ?><br>
                <span class="text-muted"><?= date('d M Y H:i', strtotime($t->requested_at)) ?></span>
            </div>
            <div class="col-md-4">
                <strong>Approved By</strong><br>
                <?= $t->approved_by_name ? htmlspecialchars($t->approved_by_name) : '<span class="text-muted">—</span>' ?><br>
                <?= $t->approved_at ? '<span class="text-muted">' . date('d M Y H:i', strtotime($t->approved_at)) . '</span>' : '' ?>
            </div>
            <div class="col-md-4">
                <strong><?= !empty($t->tri_id) ? 'Received By' : 'Completed By' ?></strong><br>
                <?= $t->completed_by_name ? htmlspecialchars($t->completed_by_name) : '<span class="text-muted">—</span>' ?><br>
                <?= $t->completed_at ? '<span class="text-muted">' . date('d M Y H:i', strtotime($t->completed_at)) . '</span>' : '' ?>
            </div>
        </div>
        <?php if ($t->status === 'rejected'): ?>
            <div class="alert alert-danger mt-3 mb-0">
                <strong>Rejected by <?= htmlspecialchars($t->rejected_by_name) ?>:</strong> <?= htmlspecialchars($t->rejection_reason) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Action Buttons (no-print) -->
<div class="no-print">
<?php if (!empty($t->tri_id) && $t->status === 'in_transit'): ?>
    <div class="alert alert-primary d-flex flex-wrap justify-content-between align-items-center">
        <span><i class="fas fa-truck mr-1"></i> Stock has left <strong><?= htmlspecialchars($t->from_location_name) ?></strong> and is in transit.
        It is added to <strong><?= htmlspecialchars($t->to_location_name) ?></strong> when Transfer In <strong><?= htmlspecialchars($t->tri_no) ?></strong> is received.</span>
        <a href="<?= base_url('stock_transfers/transfer_in_view/' . $t->tri_id) ?>" class="btn btn-sm btn-primary mt-1 mt-md-0">Open Transfer In</a>
    </div>
<?php endif; ?>
<?php if ($t->status === 'pending' && $is_admin): ?>
    <!-- Approve Form -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white"><strong>Approve Transfer</strong> — adjust quantities if needed</div>
        <form method="POST" action="<?= base_url('stock_transfers/approve/' . $t->id) ?>">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th>Product</th><th>Requested</th><th>Approved Qty</th><th>Remarks</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item->product_name) ?></td>
                            <td><?= number_format($item->requested_qty, 3) ?></td>
                            <td>
                                <input type="number" name="approved_qty[<?= $item->id ?>]"
                                       class="form-control form-control-sm" style="width:120px"
                                       value="<?= $item->requested_qty ?>" min="0" step="0.001">
                            </td>
                            <td><small class="text-muted"><?= htmlspecialchars($item->remarks ?? '') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-body">
                <button type="submit" class="btn btn-success mr-2" onclick="if(!confirm('Approve & dispatch? Stock leaves <?= htmlspecialchars(addslashes($t->from_location_name)) ?> now and Transfer In is created for <?= htmlspecialchars(addslashes($t->to_location_name)) ?>.')) return false; this.disabled=true; this.form.submit();">
                    <i class="fas fa-truck mr-1"></i> Approve &amp; Dispatch
                </button>
                <small class="text-muted">Stock is added at the destination when they receive the Transfer In.</small>
            </div>
        </form>
        <!-- Reject Form -->
        <form method="POST" action="<?= base_url('stock_transfers/reject/' . $t->id) ?>"
              class="border-top card-body d-flex align-items-center gap-2">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <input type="text" name="rejection_reason" class="form-control form-control-sm mr-2"
                   placeholder="Rejection reason" style="max-width:300px;">
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this transfer?')">
                <i class="fas fa-times mr-1"></i> Reject
            </button>
        </form>
    </div>
<?php endif; ?>

<?php if ($t->status === 'approved' && $is_admin): ?>
    <form method="POST" action="<?= base_url('stock_transfers/complete/' . $t->id) ?>">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
        <button type="submit" class="btn btn-primary" onclick="return confirm('Complete transfer and move stock now?')">
            <i class="fas fa-check-double mr-1"></i> Complete Transfer &amp; Move Stock
        </button>
    </form>
<?php endif; ?>
</div>

<style>
@media print {
    .no-print { display:none !important; }
    .card { border: 1px solid #ddd !important; }
}
.gap-2 { gap: 0.5rem; }
</style>
