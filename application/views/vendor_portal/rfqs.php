
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:.82rem;">
            <thead class="thead-dark"><tr><th>RFQ #</th><th>Title</th><th>Items</th><th>Deadline</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php if (empty($rfqs)): ?><tr><td colspan="6" class="text-center text-muted py-4">No RFQs assigned to you.</td></tr><?php endif; ?>
            <?php foreach ($rfqs as $r): ?>
            <?php $sc=['sent'=>'info','responded'=>'success','closed'=>'dark'][$r->status]??'secondary'; ?>
            <tr>
                <td><?= htmlspecialchars($r->rfq_number) ?></td>
                <td><?= htmlspecialchars($r->title??'-') ?></td>
                <td><?= $r->item_count ?></td>
                <td><?= $r->deadline ? date('d M Y',strtotime($r->deadline)) : '-' ?></td>
                <td><span class="badge badge-<?= $sc ?>"><?= ucfirst($r->status) ?></span></td>
                <td>
                    <?php if ($r->status==='sent'): ?>
                    <a href="<?= base_url('vendor-portal/submit_quotation/'.$r->rfq_id) ?>" class="btn btn-sm btn-success">Submit Quote</a>
                    <?php else: ?>
                    <span class="text-muted small">Responded</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
