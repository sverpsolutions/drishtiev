
<!-- Stats row -->
<div class="row mb-4">
    <div class="col-md-3 mb-3"><div class="card border-0 shadow-sm text-center py-3">
        <div class="h3 mb-0 text-warning"><?= $stats['pending_pos'] ?></div>
        <div class="small text-muted">Pending Action POs</div>
    </div></div>
    <div class="col-md-3 mb-3"><div class="card border-0 shadow-sm text-center py-3">
        <div class="h3 mb-0 text-success"><?= $stats['confirmed_pos'] ?></div>
        <div class="small text-muted">Confirmed POs</div>
    </div></div>
    <div class="col-md-3 mb-3"><div class="card border-0 shadow-sm text-center py-3">
        <div class="h3 mb-0 text-info"><?= $stats['open_rfqs'] ?></div>
        <div class="small text-muted">Open RFQs</div>
    </div></div>
    <div class="col-md-3 mb-3"><div class="card border-0 shadow-sm text-center py-3">
        <div class="h3 mb-0 text-primary"><?= $stats['unread_messages'] ?></div>
        <div class="small text-muted">Unread Messages</div>
    </div></div>
</div>

<div class="row">
    <!-- Recent POs -->
    <div class="col-md-7 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 font-weight-bold d-flex justify-content-between">
                <span>Recent Purchase Orders</span>
                <a href="<?= base_url('vendor-portal/orders') ?>" class="btn btn-xs btn-outline-dark">View All</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.8rem;">
                    <thead class="thead-light"><tr><th>PO #</th><th>Date</th><th>Amount</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($recent_pos)): ?><tr><td colspan="5" class="text-center text-muted py-3">No POs yet.</td></tr><?php endif; ?>
                    <?php foreach ($recent_pos as $po): ?>
                    <?php $sc=['approved'=>'success','vendor_confirmed'=>'info','dispatched'=>'warning','received'=>'dark','rejected'=>'danger'][$po->status]??'secondary'; ?>
                    <tr>
                        <td><a href="<?= base_url('vendor-portal/view_po/'.$po->id) ?>"><?= htmlspecialchars($po->po_number) ?></a></td>
                        <td><?= date('d M', strtotime($po->created_at)) ?></td>
                        <td>₹<?= number_format($po->net_total,0) ?></td>
                        <td><span class="badge badge-<?= $sc ?>"><?= ucfirst(str_replace('_',' ',$po->status)) ?></span></td>
                        <td><a href="<?= base_url('vendor-portal/view_po/'.$po->id) ?>" class="btn btn-xs btn-outline-primary">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Recent messages -->
    <div class="col-md-5 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header py-2 font-weight-bold d-flex justify-content-between">
                <span>Messages</span>
                <a href="<?= base_url('vendor-portal/messages') ?>" class="btn btn-xs btn-outline-dark">View All</a>
            </div>
            <div class="card-body p-0" style="max-height:280px;overflow-y:auto;">
                <?php if (empty($recent_messages)): ?><div class="text-center text-muted py-3 small">No messages.</div><?php endif; ?>
                <?php foreach ($recent_messages as $m): ?>
                <div class="border-bottom px-3 py-2" style="font-size:.78rem;">
                    <div class="d-flex justify-content-between">
                        <span class="font-weight-bold"><?= htmlspecialchars($m->po_number??'General') ?></span>
                        <small class="text-muted"><?= date('d M H:i',strtotime($m->created_at)) ?></small>
                    </div>
                    <div class="text-truncate text-muted"><?= htmlspecialchars($m->message) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<style>.btn-xs{padding:2px 6px;font-size:.72rem;}</style>
