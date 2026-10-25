<?php // Sales Return - Credit Note View ?>
<?php $r = $return; $s = $settings; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 text-danger"><i class="bi bi-file-earmark-minus mr-2"></i><?= htmlspecialchars($r->return_number) ?></h4>
        <small class="text-muted">Credit Note | <?= date('d F Y', strtotime($r->return_date)) ?> | <?= htmlspecialchars($r->customer_name) ?></small>
    </div>
    <div class="d-flex">
        <?php if ($r->status !== 'cancelled'): ?>
            <?php
                $waPhone = preg_replace('/[^0-9]/', '', $r->customer_phone ?? '');
                $waCC    = preg_replace('/[^0-9]/', '', $s['whatsapp_country'] ?? '91');
                $waMsg   = urlencode("Dear " . $r->customer_name . ",\nYour Credit Note *" . $r->return_number . "* dated " . date('d/m/Y', strtotime($r->return_date)) . " for ₹" . number_format($r->total_amount, 2) . " has been issued.\nReason: " . $r->reason . "\n\nThank you.");
            ?>
            <?php if ($waPhone): ?>
                <a href="https://wa.me/<?= $waCC . $waPhone ?>?text=<?= $waMsg ?>" target="_blank" class="btn btn-success btn-sm"><i class="bi bi-whatsapp mr-1"></i>WhatsApp</a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="<?= base_url('sales_returns/print_credit_note/'.$r->id) ?>" class="btn btn-outline-secondary btn-sm" target="_blank"><i class="bi bi-printer mr-1"></i>Print</a>
        <a href="<?= base_url('sales_returns') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-list mr-1"></i>All Credit Notes</a>
    </div>
</div>

<!-- Status Banner -->
<?php if ($r->status === 'cancelled'): ?>
    <div class="alert alert-secondary"><i class="bi bi-x-circle mr-2"></i>This credit note has been <strong>cancelled</strong>. Stock and balances have been reversed.</div>
<?php else: ?>
    <div class="alert alert-success"><i class="bi bi-check-circle mr-2"></i>Credit Note <strong><?= htmlspecialchars($r->return_number) ?></strong> is confirmed and reflected in GSTR-1 Section 9B.</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">

        <!-- Company + Customer Header -->
        <div class="row mb-4">
            <div class="col-6">
                <h5 class="text-danger font-weight-bold"><?= htmlspecialchars($s['company_name'] ?? 'SV ERP Solutions') ?></h5>
                <div class="text-muted small"><?= nl2br(htmlspecialchars($s['company_address'] ?? '')) ?></div>
                <div class="small">GSTIN: <strong><?= htmlspecialchars($s['gstin'] ?? '') ?></strong></div>
                <div class="small">Phone: <?= htmlspecialchars($s['company_phone'] ?? '') ?></div>
            </div>
            <div class="col-6 text-right">
                <div class="p-3 bg-danger text-white rounded d-inline-block text-left">
                    <div class="fs-5 font-weight-bold">CREDIT NOTE</div>
                    <div class="small">No: <strong><?= htmlspecialchars($r->return_number) ?></strong></div>
                    <div class="small">Date: <?= date('d/m/Y', strtotime($r->return_date)) ?></div>
                    <?php if ($r->original_invoice_no): ?>
                        <div class="small">Against Invoice: <strong><?= htmlspecialchars($r->original_invoice_no) ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Customer Details -->
        <div class="row mb-3">
            <div class="col-6">
                <div class="border rounded p-3 bg-light">
                    <div class="font-weight-bold text-muted small mb-1">BILL TO:</div>
                    <div class="font-weight-bold"><?= htmlspecialchars($r->customer_name) ?></div>
                    <div class="small"><?= htmlspecialchars($r->customer_address ?? '') ?><?= $r->customer_city ? ', '.htmlspecialchars($r->customer_city) : '' ?></div>
                    <div class="small">State: <?= htmlspecialchars($r->customer_state ?? '') ?></div>
                    <?php if ($r->customer_gst): ?>
                        <div class="small">GSTIN: <strong><?= htmlspecialchars($r->customer_gst) ?></strong></div>
                    <?php endif; ?>
                    <?php if ($r->customer_phone): ?>
                        <div class="small">Ph: <?= htmlspecialchars($r->customer_phone) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-3">
                    <div class="row g-2 text-center">
                        <div class="col-6"><div class="text-muted small">Taxable Amt</div><div class="font-weight-bold">₹<?= number_format($r->taxable_amount, 2) ?></div></div>
                        <div class="col-6"><div class="text-muted small">Total GST</div><div class="font-weight-bold">₹<?= number_format($r->total_gst, 2) ?></div></div>
                        <div class="col-6 border-top pt-2">
                            <div class="text-muted small">Refund Method</div>
                            <span class="badge <?= ($r->refund_method ?? 'balance') === 'wallet' ? 'badge-info' : (($r->refund_method ?? 'balance') === 'cash' ? 'badge-success' : 'badge-warning') ?>">
                                <?= ($r->refund_method ?? 'balance') === 'wallet' ? 'Credit to Wallet' : (($r->refund_method ?? 'balance') === 'cash' ? 'Refunded in Cash' : 'Adjust Outstanding') ?>
                            </span>
                        </div>
                        <div class="col-6 border-top pt-2"><div class="text-muted small">CN Amount</div><div class="font-weight-bold text-danger" style="font-size: 1.1rem;">₹<?= number_format($r->total_amount, 2) ?></div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reason -->
        <?php if ($r->reason): ?>
            <div class="alert alert-warning py-2"><strong>Return Reason:</strong> <?= htmlspecialchars($r->reason) ?></div>
        <?php endif; ?>

        <!-- Items Table -->
        <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm">
                <thead class="table-danger">
                    <tr>
                        <th>#</th><th>Item</th><th>HSN</th><th class="text-center">Qty</th>
                        <th class="text-right">Rate</th><th class="text-right">Taxable</th>
                        <?php if ($r->is_interstate): ?>
                            <th class="text-right">IGST</th>
                        <?php else: ?>
                            <th class="text-right">CGST</th><th class="text-right">SGST</th>
                        <?php endif; ?>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($r->items as $i => $item): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($item->product_name) ?></td>
                        <td><?= htmlspecialchars($item->hsn_code) ?></td>
                        <td class="text-center"><?= rtrim(rtrim($item->qty, '0'), '.') ?> <?= htmlspecialchars($item->unit) ?></td>
                        <td class="text-right">₹<?= number_format($item->rate, 2) ?></td>
                        <td class="text-right">₹<?= number_format($item->taxable_amt, 2) ?></td>
                        <?php if ($r->is_interstate): ?>
                            <td class="text-right">₹<?= number_format($item->igst_amount, 2) ?> <small class="text-muted">(<?= $item->igst_percent ?>%)</small></td>
                        <?php else: ?>
                            <td class="text-right">₹<?= number_format($item->cgst_amount, 2) ?> <small class="text-muted">(<?= $item->cgst_percent ?>%)</small></td>
                            <td class="text-right">₹<?= number_format($item->sgst_amount, 2) ?> <small class="text-muted">(<?= $item->sgst_percent ?>%)</small></td>
                        <?php endif; ?>
                        <td class="text-right font-weight-bold">₹<?= number_format($item->total, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light font-weight-bold">
                    <tr>
                        <td colspan="5" class="text-right">Totals:</td>
                        <td class="text-right">₹<?= number_format($r->taxable_amount, 2) ?></td>
                        <?php if ($r->is_interstate): ?>
                            <td class="text-right">₹<?= number_format($r->igst_amount, 2) ?></td>
                        <?php else: ?>
                            <td class="text-right">₹<?= number_format($r->cgst_amount, 2) ?></td>
                            <td class="text-right">₹<?= number_format($r->sgst_amount, 2) ?></td>
                        <?php endif; ?>
                        <td class="text-right text-danger">₹<?= number_format($r->total_amount, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($r->notes): ?>
            <div class="text-muted small"><strong>Notes:</strong> <?= htmlspecialchars($r->notes) ?></div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <?php if ($r->status !== 'cancelled'): ?>
        <div class="d-flex mt-3 border-top pt-3">
            <a href="<?= base_url('sales_returns/print_credit_note/'.$r->id) ?>" class="btn btn-outline-dark" target="_blank"><i class="bi bi-printer mr-1"></i>Print Credit Note</a>
            <a href="#" onclick="confirmCancel(<?= $r->id ?>)" class="btn btn-outline-danger ms-auto"><i class="bi bi-x-circle mr-1"></i>Cancel Credit Note</a>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
function confirmCancel(id) {
    if (confirm('Cancel this Credit Note?\n\nThis will:\n• Reverse stock (goods deducted from inventory)\n• Remove from GSTR-1\n• Reverse customer balance adjustment\n\nAre you sure?')) {
        window.location = '<?= base_url("sales_returns/cancel/") ?>' + id;
    }
}
</script>
