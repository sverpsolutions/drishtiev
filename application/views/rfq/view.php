
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-envelope-open-text text-primary mr-2"></i><?= htmlspecialchars($rfq->rfq_number) ?></h5>
    <div>
        <?php if ($rfq->response_count > 0): ?><a href="<?= base_url('rfq/compare/'.$rfq->id) ?>" class="btn btn-sm btn-outline-success mr-2"><i class="fas fa-balance-scale"></i> Compare Quotes</a><?php endif; ?>
        <a href="<?= base_url('rfq') ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-6"><div class="card border-0 shadow-sm p-3" style="font-size:.85rem;">
        <table class="table table-sm mb-0">
            <tr><td class="text-muted">RFQ Number</td><td><strong><?= htmlspecialchars($rfq->rfq_number) ?></strong></td></tr>
            <tr><td class="text-muted">Title</td><td><?= htmlspecialchars($rfq->title??'-') ?></td></tr>
            <tr><td class="text-muted">Created By</td><td><?= htmlspecialchars($rfq->buyer_name??'-') ?></td></tr>
            <tr><td class="text-muted">Deadline</td><td><?= $rfq->deadline ? date('d M Y',strtotime($rfq->deadline)) : '-' ?></td></tr>
            <tr><td class="text-muted">Status</td><td><span class="badge badge-info"><?= ucfirst($rfq->status) ?></span></td></tr>
            <tr><td class="text-muted">Responses</td><td><?= $rfq->response_count ?>/<?= count($suppliers) ?></td></tr>
        </table>
    </div></div>
    <div class="col-md-6"><div class="card border-0 shadow-sm p-3" style="font-size:.85rem;">
        <div class="font-weight-bold mb-2">Suppliers Invited</div>
        <?php foreach ($suppliers as $s): ?>
        <div class="d-flex justify-content-between align-items-center border-bottom py-1">
            <span><?= htmlspecialchars($s->supplier_name) ?></span>
            <span class="badge badge-<?= $s->status==='responded'?'success':'warning' ?>"><?= ucfirst($s->status) ?></span>
        </div>
        <?php endforeach; ?>
    </div></div>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-header py-2 font-weight-bold">Items Required</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:.8rem;">
            <thead class="thead-light"><tr><th>#</th><th>Product</th><th>Qty Required</th><th>Unit</th><th>Target Rate</th><th>Notes</th></tr></thead>
            <tbody>
            <?php $i=1; foreach ($items as $item): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($item->product_name) ?></td>
                <td><?= number_format($item->qty_required,2) ?></td>
                <td><?= htmlspecialchars($item->unit??'PCS') ?></td>
                <td><?= $item->target_rate ? '₹'.number_format($item->target_rate,2) : '-' ?></td>
                <td><?= htmlspecialchars($item->item_notes??'-') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
