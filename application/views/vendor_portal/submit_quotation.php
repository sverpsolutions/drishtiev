<?= form_open('vendor-portal/submit_quotation/'.$rfq->rfq_id) ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-dark text-white py-2">RFQ Details</div>
    <div class="card-body" style="font-size:.85rem;">
        <strong><?= htmlspecialchars($rfq->rfq_number) ?></strong>
        <?php if ($rfq->title): ?> — <?= htmlspecialchars($rfq->title) ?><?php endif; ?><br>
        <?php if ($rfq->notes): ?><div class="text-muted mt-1"><?= htmlspecialchars($rfq->notes) ?></div><?php endif; ?>
        <div class="mt-1">Deadline: <strong><?= $rfq->deadline ? date('d M Y',strtotime($rfq->deadline)) : '-' ?></strong></div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 font-weight-bold">Your Quotation</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:.82rem;">
            <thead class="thead-light"><tr><th>Product</th><th>Req. Qty</th><th>Unit</th><th>Your Rate (₹) <span class="text-danger">*</span></th><th>Delivery (days)</th><th>Validity (days)</th><th>Remarks</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><strong><?= htmlspecialchars($item->product_name) ?></strong>
                    <input type="hidden" name="rfq_item_id[]" value="<?= $item->id ?>">
                </td>
                <td><?= number_format($item->qty_required,2) ?></td>
                <td><?= htmlspecialchars($item->unit??'PCS') ?></td>
                <td><input name="rate[]" type="number" step="0.01" class="form-control form-control-sm" required></td>
                <td><input name="delivery_days[]" type="number" class="form-control form-control-sm" value="7"></td>
                <td><input name="validity_days[]" type="number" class="form-control form-control-sm" value="30"></td>
                <td><input name="remarks[]" class="form-control form-control-sm" placeholder="Quality, packing..."></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="form-group">
    <label class="font-weight-bold small">Additional Notes</label>
    <textarea name="overall_remarks" class="form-control" rows="2" placeholder="Payment terms, special conditions..."></textarea>
</div>
<button type="submit" class="btn btn-success btn-sm"><i class="fas fa-paper-plane mr-1"></i> Submit Quotation</button>
<a href="<?= base_url('vendor-portal/rfqs') ?>" class="btn btn-outline-secondary btn-sm ml-2">Cancel</a>
<?= form_close() ?>
