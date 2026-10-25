<?php
$level_map = [1=>'Store Manager',2=>'Purchase Manager',3=>'Finance Head',4=>'Director'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-check-circle text-warning mr-2"></i>Approve PO — <?= htmlspecialchars($po->po_number) ?></h5>
    <a href="<?= base_url('purchase_orders/view/'.$po->id) ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-3" style="font-size:.85rem;">
            <table class="table table-sm mb-0">
                <tr><td class="text-muted w-50">PO Number</td><td><strong><?= htmlspecialchars($po->po_number) ?></strong></td></tr>
                <tr><td class="text-muted">Supplier</td><td><?= htmlspecialchars($po->supplier_name) ?></td></tr>
                <tr><td class="text-muted">Buyer</td><td><?= htmlspecialchars($po->buyer_name??'-') ?></td></tr>
                <tr><td class="text-muted">Net Total</td><td class="font-weight-bold text-success">₹<?= number_format($po->net_total,2) ?></td></tr>
                <tr><td class="text-muted">Approval Level</td><td><span class="badge badge-warning"><?= $level_map[$po->approval_level]??'Level '.$po->approval_level ?></span></td></tr>
                <tr><td class="text-muted">Items</td><td><?= count($items) ?></td></tr>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <?php if (!empty($approval_log)): ?>
        <div class="card border-0 shadow-sm p-3">
            <div class="font-weight-bold mb-2">Approval History</div>
            <?php foreach ($approval_log as $al): ?>
            <div class="d-flex align-items-start mb-1" style="font-size:.8rem;">
                <span class="badge badge-<?= $al->action==='approved'?'success':($al->action==='rejected'?'danger':'warning') ?> mr-2 mt-1"><?= ucfirst($al->action) ?></span>
                <div>Level <?= $al->level ?> — <?= htmlspecialchars($al->approver_name??'Pending') ?>
                <?php if ($al->remarks): ?><div class="text-muted"><?= htmlspecialchars($al->remarks) ?></div><?php endif; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 font-weight-bold">Items</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:.8rem;">
                <thead class="thead-light"><tr><th>#</th><th>Product</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead>
                <tbody>
                <?php $i=1; foreach ($items as $item): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($item->product_name) ?> <small class="text-muted"><?= $item->item_code ?></small></td>
                    <td><?= number_format($item->ordered_qty,2) ?> <?= $item->unit ?></td>
                    <td>₹<?= number_format($item->rate,2) ?></td>
                    <td>₹<?= number_format($item->amount,2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= form_open('purchase_orders/do_approve/' . $po->id) ?>
<div class="card border-0 shadow-sm mb-3 p-3">
    <div class="form-group">
        <label class="font-weight-bold">Remarks (Optional)</label>
        <textarea name="remarks" class="form-control" rows="2" placeholder="Add note for approval/rejection..."></textarea>
    </div>
    <div class="d-flex gap-3">
        <button type="submit" name="action" value="approve" class="btn btn-success mr-3" onclick="return confirm('Approve this PO?')">
            <i class="fas fa-check mr-1"></i> Approve PO
        </button>
        <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('Reject this PO?')">
            <i class="fas fa-times mr-1"></i> Reject PO
        </button>
    </div>
</div>
<?= form_close() ?>

<!-- Approval via Email / WhatsApp -->
<div class="card border-warning shadow-sm mb-3">
    <div class="card-header bg-warning text-dark font-weight-bold"><i class="fas fa-paper-plane mr-2"></i>Send Approval Request</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <form method="post" action="<?= base_url('purchase_orders/send_approval_email/'.$po->id) ?>">
                    <div class="form-group">
                        <label class="font-weight-bold">Send Approval Request via Email</label>
                        <input type="email" name="approval_email" class="form-control" placeholder="approver@company.com (leave blank for default manager)">
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fas fa-envelope mr-1"></i>Send Email Approval</button>
                </form>
            </div>
            <div class="col-md-6">
                <form method="post" action="<?= base_url('purchase_orders/whatsapp_approval/'.$po->id) ?>">
                    <div class="form-group">
                        <label class="font-weight-bold">Send Approval Link via WhatsApp</label>
                        <input type="tel" name="wa_phone" class="form-control" placeholder="10-digit mobile number">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm" style="background:#25D366;border-color:#25D366;"><i class="fab fa-whatsapp mr-1"></i>Send WhatsApp Approval</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Approval History (full) -->
<?php if (!empty($approval_log)): ?>
<div class="card shadow-sm">
    <div class="card-header font-weight-bold">Approval Audit Trail</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="thead-light"><tr><th>Action</th><th>By</th><th>Remarks</th><th>Time</th></tr></thead>
            <tbody>
            <?php foreach ($approval_log as $al): ?>
            <tr>
                <td><span class="badge badge-<?= $al->action==='approved'?'success':($al->action==='rejected'?'danger':($al->action==='email_sent'||$al->action==='whatsapp_sent'?'info':'warning')) ?>"><?= ucfirst(str_replace('_',' ',$al->action)) ?></span></td>
                <td><?= htmlspecialchars($al->approver_name ?? $al->user_name ?? '—') ?></td>
                <td><?= htmlspecialchars($al->remarks ?? '') ?></td>
                <td><?= isset($al->created_at) ? date('d-M-Y H:i', strtotime($al->created_at)) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
