<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-truck-loading text-success mr-2"></i>Create GRN — Select PO</h5>
    <div>
        <a href="<?= base_url('grn/create_direct') ?>" class="btn btn-sm btn-primary mr-2"><i class="fas fa-bolt"></i> Create GRN without PO</a>
        <a href="<?= base_url('grn') ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.82rem;">
                <thead class="thead-dark"><tr><th>PO #</th><th>Supplier</th><th>Expected Delivery</th><th>Net Total</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php $all = array_merge($approved_pos ?? []); ?>
                <?php if (empty($all)): ?><tr><td colspan="6" class="text-center text-muted py-4">No approved POs available for GRN.</td></tr><?php endif; ?>
                <?php foreach ($all as $po): ?>
                <tr>
                    <td><?= htmlspecialchars($po->po_number) ?></td>
                    <td><?= htmlspecialchars($po->supplier_name??'-') ?></td>
                    <td><?= $po->expected_delivery ? date('d M Y',strtotime($po->expected_delivery)) : '-' ?></td>
                    <td>₹<?= number_format($po->net_total,2) ?></td>
                    <td><span class="badge badge-success"><?= ucfirst($po->status) ?></span></td>
                    <td><a href="<?= base_url('grn/create/'.$po->id) ?>" class="btn btn-sm btn-success"><i class="fas fa-truck-loading"></i> Create GRN</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
