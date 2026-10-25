<?php $r = $return; $s = $settings; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 text-warning"><i class="bi bi-file-earmark-plus mr-2"></i><?= htmlspecialchars($r->return_number) ?></h4>
        <small class="text-muted">Debit Note | <?= date('d F Y', strtotime($r->return_date)) ?> | <?= htmlspecialchars($r->supplier_name) ?></small>
    </div>
    <div class="d-flex">
        <a href="<?= base_url('purchase_returns/print_debit_note/'.$r->id) ?>" class="btn btn-outline-secondary btn-sm" target="_blank"><i class="bi bi-printer mr-1"></i>Print</a>
        <a href="<?= base_url('purchase_returns') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-list mr-1"></i>All Debit Notes</a>
    </div>
</div>

<?php if ($r->status === 'cancelled'): ?>
    <div class="alert alert-secondary"><i class="bi bi-x-circle mr-2"></i>This debit note has been <strong>cancelled</strong>. Stock reversed.</div>
<?php else: ?>
    <div class="alert alert-success"><i class="bi bi-check-circle mr-2"></i>Debit Note <strong><?= htmlspecialchars($r->return_number) ?></strong> confirmed. Stock reduced for returned items.</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-6">
                <h5 class="text-warning font-weight-bold"><?= htmlspecialchars($s['company_name'] ?? 'SV ERP Solutions') ?></h5>
                <div class="text-muted small"><?= nl2br(htmlspecialchars($s['company_address'] ?? '')) ?></div>
                <div class="small">GSTIN: <strong><?= htmlspecialchars($s['gstin'] ?? '') ?></strong></div>
            </div>
            <div class="col-6 text-right">
                <div class="p-3 bg-warning text-dark rounded d-inline-block text-left">
                    <div class="fs-5 font-weight-bold">DEBIT NOTE</div>
                    <div class="small">No: <strong><?= htmlspecialchars($r->return_number) ?></strong></div>
                    <div class="small">Date: <?= date('d/m/Y', strtotime($r->return_date)) ?></div>
                    <?php if ($r->original_purchase_no): ?>
                        <div class="small">Against: <strong><?= htmlspecialchars($r->original_purchase_no) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-6">
                <div class="border rounded p-3 bg-light">
                    <div class="font-weight-bold text-muted small mb-1">SUPPLIER:</div>
                    <div class="font-weight-bold"><?= htmlspecialchars($r->supplier_name) ?></div>
                    <?php if (!empty($r->supplier_address)): ?><div class="small"><?= htmlspecialchars($r->supplier_address) ?></div><?php endif; ?>
                    <?php if (!empty($r->supplier_gst)): ?><div class="small">GSTIN: <strong><?= htmlspecialchars($r->supplier_gst) ?></strong></div><?php endif; ?>
                    <div class="small">State: <?= htmlspecialchars($r->supplier_state ?? '') ?></div>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-3 text-center">
                    <div class="text-muted small">Taxable Amount</div><div class="font-weight-bold">₹<?= number_format($r->taxable_amount,2) ?></div>
                    <div class="text-muted small mt-2">Total GST</div><div class="font-weight-bold">₹<?= number_format($r->total_gst,2) ?></div>
                    <div class="border-top mt-2 pt-2"><div class="text-muted small">Debit Note Total</div>
                    <div class="fs-4 font-weight-bold text-warning">₹<?= number_format($r->total_amount,2) ?></div></div>
                </div>
            </div>
        </div>

        <?php if ($r->reason): ?>
            <div class="alert alert-warning py-2"><strong>Return Reason:</strong> <?= htmlspecialchars($r->reason) ?></div>
        <?php endif; ?>

        <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm">
                <thead class="table-warning">
                    <tr><th>#</th><th>Product</th><th>HSN</th><th class="text-center">Qty</th><th class="text-right">Rate</th><th class="text-right">Taxable</th>
                    <?php if ($r->is_interstate): ?><th class="text-right">IGST</th><?php else: ?><th class="text-right">CGST</th><th class="text-right">SGST</th><?php endif; ?>
                    <th class="text-right">Total</th></tr>
                </thead>
                <tbody>
                <?php foreach ($r->items as $i => $item): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($item->product_name) ?></td>
                        <td><?= htmlspecialchars($item->hsn_code) ?></td>
                        <td class="text-center"><?= rtrim(rtrim($item->qty,'0'),'.') ?> <?= htmlspecialchars($item->unit) ?></td>
                        <td class="text-right">₹<?= number_format($item->rate,2) ?></td>
                        <td class="text-right">₹<?= number_format($item->taxable_amt,2) ?></td>
                        <?php if ($r->is_interstate): ?>
                            <td class="text-right">₹<?= number_format($item->igst_amount,2) ?><small class="text-muted">(<?= $item->igst_percent ?>%)</small></td>
                        <?php else: ?>
                            <td class="text-right">₹<?= number_format($item->cgst_amount,2) ?><small class="text-muted">(<?= $item->cgst_percent ?>%)</small></td>
                            <td class="text-right">₹<?= number_format($item->sgst_amount,2) ?><small class="text-muted">(<?= $item->sgst_percent ?>%)</small></td>
                        <?php endif; ?>
                        <td class="text-right font-weight-bold">₹<?= number_format($item->total,2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light font-weight-bold">
                    <tr>
                        <td colspan="5" class="text-right">Totals:</td>
                        <td class="text-right">₹<?= number_format($r->taxable_amount,2) ?></td>
                        <?php if ($r->is_interstate): ?>
                            <td class="text-right">₹<?= number_format($r->igst_amount,2) ?></td>
                        <?php else: ?>
                            <td class="text-right">₹<?= number_format($r->cgst_amount,2) ?></td>
                            <td class="text-right">₹<?= number_format($r->sgst_amount,2) ?></td>
                        <?php endif; ?>
                        <td class="text-right text-warning">₹<?= number_format($r->total_amount,2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($r->status !== 'cancelled'): ?>
        <div class="d-flex mt-3 border-top pt-3">
            <a href="<?= base_url('purchase_returns/print_debit_note/'.$r->id) ?>" class="btn btn-outline-dark" target="_blank"><i class="bi bi-printer mr-1"></i>Print Debit Note</a>
            <a href="#" onclick="confirmCancel(<?= $r->id ?>)" class="btn btn-outline-danger ms-auto"><i class="bi bi-x-circle mr-1"></i>Cancel</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
function confirmCancel(id) {
    if (confirm('Cancel this Debit Note?\n\nStock will be restored back. Are you sure?')) {
        window.location = '<?= base_url("purchase_returns/cancel/") ?>' + id;
    }
}
</script>
