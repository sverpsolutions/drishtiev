<?php // FILE: application/views/stock_transfers/transfer_in_view.php
$tri_badges = ['in_transit' => 'primary', 'received' => 'success', 'cancelled' => 'danger'];
$tri_labels = ['in_transit' => 'In Transit', 'received' => 'Received', 'cancelled' => 'Cancelled'];
?>
<div class="d-flex justify-content-between align-items-start mb-3 no-print">
    <div>
        <h5 class="font-weight-bold mb-0">Transfer In</h5>
        <small class="text-muted"><a href="<?= base_url('stock_transfers/transfer_in') ?>">Transfer In</a> / <?= htmlspecialchars($tri->tri_no) ?></small>
    </div>
    <div>
        <a href="<?= base_url('stock_transfers/print_transfer_in/' . $tri->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary mr-1">
            <i class="fas fa-print mr-1"></i> Print
        </a>
        <a href="<?= base_url('stock_transfers/transfer_in') ?>" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success no-print"><?= $this->session->flashdata('success') ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger no-print"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body border-bottom">
        <div class="row">
            <div class="col-md-6 mb-2">
                <h5 class="font-weight-bold mb-2">TRANSFER IN</h5>
                <p class="mb-1"><strong>Transfer In No.:</strong> <?= htmlspecialchars($tri->tri_no) ?></p>
                <p class="mb-1"><strong>Transfer Out No.:</strong>
                    <a href="<?= base_url('stock_transfers/view/' . $tri->transfer_id) ?>" class="font-weight-bold"><?= htmlspecialchars($tri->transfer_no) ?></a>
                </p>
            </div>
            <div class="col-md-6 text-md-right">
                <p class="mb-1"><strong>Transfer Date:</strong> <?= date('d M Y', strtotime($tri->transfer_date)) ?></p>
                <p class="mb-1"><strong>Status:</strong>
                    <span class="badge badge-<?= $tri_badges[$tri->status] ?? 'secondary' ?>"><?= $tri_labels[$tri->status] ?? ucfirst($tri->status) ?></span>
                </p>
                <?php if ($tri->received_by_name): ?>
                <p class="mb-0 small text-muted">Received by <?= htmlspecialchars($tri->received_by_name) ?> on <?= date('d M Y H:i', strtotime($tri->received_at)) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card-body border-bottom">
        <div class="row">
            <div class="col-5">
                <h6 class="text-muted text-uppercase small font-weight-bold mb-1">From Location</h6>
                <p class="font-weight-bold mb-0"><?= htmlspecialchars($tri->from_location_name) ?><?= $tri->from_location_code ? ' <small class="text-muted">(' . htmlspecialchars($tri->from_location_code) . ')</small>' : '' ?></p>
            </div>
            <div class="col-2 text-center d-flex align-items-center justify-content-center">
                <i class="fas fa-arrow-right fa-2x text-success"></i>
            </div>
            <div class="col-5">
                <h6 class="text-muted text-uppercase small font-weight-bold mb-1">To Location</h6>
                <p class="font-weight-bold mb-0"><?= htmlspecialchars($tri->to_location_name) ?><?= $tri->to_location_code ? ' <small class="text-muted">(' . htmlspecialchars($tri->to_location_code) . ')</small>' : '' ?></p>
            </div>
        </div>
        <?php if (!empty($tri->reason)): ?><div class="mt-2"><strong>Reason:</strong> <?= htmlspecialchars($tri->reason) ?></div><?php endif; ?>
        <?php if (!empty($tri->notes)): ?><div><strong>Notes:</strong> <?= htmlspecialchars($tri->notes) ?></div><?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Item Code</th>
                    <th>Barcode</th>
                    <th>Unit</th>
                    <th class="text-right">MRP</th>
                    <th class="text-right">Qty Sent</th>
                    <th class="text-right">Cost</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
            <?php $tot_qty = 0; foreach ($items as $i => $item): $q = (float)($item->approved_qty ?? $item->requested_qty); $tot_qty += $q; ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($item->product_name) ?></td>
                    <td><small><?= htmlspecialchars($item->item_code ?? '') ?></small></td>
                    <td><small><?= htmlspecialchars($item->ean_code ?? '') ?></small></td>
                    <td><?= htmlspecialchars($item->unit) ?></td>
                    <td class="text-right"><?= isset($item->mrp) ? '&#8377;' . number_format((float)$item->mrp, 2) : '—' ?></td>
                    <td class="text-right font-weight-bold"><?= number_format($q, 3) ?></td>
                    <td class="text-right">&#8377;<?= number_format((float)$item->rate, 2) ?></td>
                    <td><small><?= htmlspecialchars($item->remarks ?? '') ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-active">
                    <td colspan="6" class="text-right font-weight-bold">Total Qty:</td>
                    <td class="text-right font-weight-bold"><?= number_format($tot_qty, 3) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php if (!empty($can_receive)): ?>
<div class="card shadow-sm border-0 mb-3 no-print">
    <div class="card-body d-flex flex-wrap align-items-center">
        <form method="POST" action="<?= base_url('stock_transfers/receive/' . $tri->id) ?>" class="mr-3 mb-2">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <button type="submit" class="btn btn-success"
                    onclick="if(!confirm('Receive <?= htmlspecialchars(addslashes($tri->tri_no)) ?>? The quantities above will be added to <?= htmlspecialchars(addslashes($tri->to_location_name)) ?> stock.')) return false; this.disabled=true; this.form.submit();">
                <i class="fas fa-check-double mr-1"></i> Receive &amp; Add Stock
            </button>
        </form>
        <small class="text-muted mb-2">Stock was already deducted from <?= htmlspecialchars($tri->from_location_name) ?> when the Transfer Out was approved.</small>
    </div>
</div>
<?php elseif ($tri->status === 'in_transit'): ?>
<div class="alert alert-info no-print">In transit — waiting for <?= htmlspecialchars($tri->to_location_name) ?> to receive.</div>
<?php endif; ?>
