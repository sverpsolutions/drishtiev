<?php // FILE: application/views/purchases/drafts.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Purchase Drafts</h5>
        <small class="text-muted">Unfinished purchase entries — auto-saved while scanning</small>
    </div>
    <div>
        <a href="<?= base_url('purchases/create') ?>" class="btn btn-dark btn-sm">+ New Purchase</a>
        <a href="<?= base_url('purchases') ?>" class="btn btn-outline-secondary btn-sm">Back to Purchases</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Draft #</th>
                        <?php if ($is_admin): ?><th>User</th><?php endif; ?>
                        <th>Supplier</th>
                        <th>Invoice No</th>
                        <th class="text-right">Items</th>
                        <th class="text-right">Total Value</th>
                        <th>Created</th>
                        <th>Last Modified</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="draftsTbody">
                <?php if (!empty($drafts)): ?>
                    <?php foreach ($drafts as $d): ?>
                    <tr id="draft_row_<?= $d->id ?>">
                        <td>#<?= $d->id ?></td>
                        <?php if ($is_admin): ?><td><?= htmlspecialchars($d->user_name ?? '—') ?></td><?php endif; ?>
                        <td><?= htmlspecialchars($d->supplier_name ?? '—') ?></td>
                        <td><?= htmlspecialchars($d->invoice_no ?? '—') ?></td>
                        <td class="text-right"><?= (int)$d->item_count ?></td>
                        <td class="text-right">₹ <?= number_format((float)$d->total_value, 2) ?></td>
                        <td><?= date('d-M-y H:i', strtotime($d->created_at)) ?></td>
                        <td><?= date('d-M-y H:i', strtotime($d->last_modified_at)) ?></td>
                        <td class="text-center">
                            <a href="<?= base_url('purchases/create?resume=' . $d->id) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Open</a>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteDraft(<?= $d->id ?>)">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?= $is_admin ? 9 : 8 ?>" class="text-center text-muted py-4">No unfinished drafts.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function deleteDraft(id) {
    if (!confirm('Delete this draft? This cannot be undone.')) return;
    fetch('<?= base_url('purchases/delete_draft_ajax/') ?>' + id, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const row = document.getElementById('draft_row_' + id);
                if (row) row.remove();
            } else {
                alert('Could not delete draft.');
            }
        });
}
</script>
