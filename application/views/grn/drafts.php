<?php // FILE: application/views/grn/drafts.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">GRN Drafts</h5>
        <small class="text-muted">Unfinished goods-receipt entries from all users — auto-saved while scanning</small>
    </div>
    <div>
        <a href="<?= base_url('grn/create_direct') ?>" class="btn btn-dark btn-sm">+ New GRN (No PO)</a>
        <a href="<?= base_url('grn') ?>" class="btn btn-outline-secondary btn-sm">Back to GRN</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Draft #</th>
                        <th>Started By</th>
                        <th>Type</th>
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
                    <?php
                        $open_url = $d->entry_type === 'grn'
                            ? base_url('grn/create/' . $d->po_id . '?resume=' . $d->id)
                            : ($d->entry_type === 'purchase'
                                ? base_url('purchases/create?resume=' . $d->id)
                                : base_url('grn/create_direct?resume=' . $d->id));
                        $is_mine = isset($current_user_id) && (int)$d->user_id === (int)$current_user_id;
                        // Matches the 60s stale window the resume endpoint uses.
                        $in_use  = $d->locked_by && !$is_mine && $d->locked_at
                                   && strtotime($d->locked_at) > (time() - 60)
                                   && (int)$d->locked_by !== (int)($current_user_id ?? 0);
                    ?>
                    <tr id="draft_row_<?= $d->id ?>">
                        <td>#<?= $d->id ?></td>
                        <td>
                            <?= htmlspecialchars($d->user_name ?? '—') ?>
                            <?php if ($is_mine): ?><span class="badge badge-light border ml-1">you</span><?php endif; ?>
                            <?php if ($in_use): ?>
                                <span class="badge badge-warning ml-1" title="Currently open on another screen">
                                    editing: <?= htmlspecialchars($d->locked_by_name ?? 'another user') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-secondary"><?= $d->entry_type === 'grn' ? 'PO-based' : 'Direct' ?></span></td>
                        <td><?= htmlspecialchars($d->supplier_name ?? '—') ?></td>
                        <td><?= htmlspecialchars($d->invoice_no ?? '—') ?></td>
                        <td class="text-right"><?= (int)$d->item_count ?></td>
                        <td class="text-right">₹ <?= number_format((float)$d->total_value, 2) ?></td>
                        <td><?= date('d-M-y H:i', strtotime($d->created_at)) ?></td>
                        <td><?= date('d-M-y H:i', strtotime($d->last_modified_at)) ?></td>
                        <td class="text-center">
                            <a href="<?= $open_url ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Open</a>
                            <?php if ($is_mine || $is_admin): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteDraft(<?= $d->id ?>)">Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No unfinished drafts.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function deleteDraft(id) {
    if (!confirm('Delete this draft? This cannot be undone.')) return;
    fetch('<?= base_url('grn/delete_draft_ajax/') ?>' + id, {
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
