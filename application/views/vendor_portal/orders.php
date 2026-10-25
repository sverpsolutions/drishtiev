
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.82rem;">
                <thead class="thead-dark"><tr><th>PO #</th><th>Date</th><th>Expected Delivery</th><th>Items</th><th>Net Total</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (empty($pos)): ?><tr><td colspan="7" class="text-center text-muted py-4">No purchase orders found.</td></tr><?php endif; ?>
                <?php foreach ($pos as $po): ?>
                <?php $sc=['approved'=>'success','vendor_confirmed'=>'info','dispatched'=>'warning','received'=>'dark','rejected'=>'danger'][$po->status]??'secondary'; ?>
                <tr>
                    <td><a href="<?= base_url('vendor-portal/view_po/'.$po->id) ?>" class="font-weight-bold"><?= htmlspecialchars($po->po_number) ?></a></td>
                    <td><?= date('d M Y',strtotime($po->created_at)) ?></td>
                    <td><?= $po->expected_delivery ? date('d M Y',strtotime($po->expected_delivery)) : '-' ?></td>
                    <td><?= $po->item_count ?></td>
                    <td>₹<?= number_format($po->net_total,2) ?></td>
                    <td><span class="badge badge-<?= $sc ?>"><?= ucfirst(str_replace('_',' ',$po->status)) ?></span></td>
                    <td><a href="<?= base_url('vendor-portal/view_po/'.$po->id) ?>" class="btn btn-xs btn-outline-primary">View</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>.btn-xs{padding:2px 6px;font-size:.72rem;}</style>
