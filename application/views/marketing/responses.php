<?php // FILE: application/views/marketing/responses.php
$badge = ['new' => 'info', 'assigned' => 'warning', 'converted' => 'success', 'closed' => 'secondary'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> Customer Responses</h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / Responses</small>
    </div>
    <a href="<?= base_url('marketing/campaigns') ?>" class="btn btn-outline-dark btn-sm">Back to Campaigns</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="mb-3">
    <div class="btn-group btn-group-sm">
        <a href="<?= base_url('marketing/responses') ?>" class="btn btn-outline-dark <?= !$filter ? 'active' : '' ?>">All (<?= $stats['total'] ?>)</a>
        <a href="<?= base_url('marketing/responses?status=new') ?>" class="btn btn-outline-info <?= $filter === 'new' ? 'active' : '' ?>">New (<?= $stats['new'] ?>)</a>
        <a href="<?= base_url('marketing/responses?status=assigned') ?>" class="btn btn-outline-warning <?= $filter === 'assigned' ? 'active' : '' ?>">Assigned (<?= $stats['assigned'] ?>)</a>
        <a href="<?= base_url('marketing/responses?status=converted') ?>" class="btn btn-outline-success <?= $filter === 'converted' ? 'active' : '' ?>">Converted (<?= $stats['converted'] ?>)</a>
        <a href="<?= base_url('marketing/responses?status=closed') ?>" class="btn btn-outline-secondary <?= $filter === 'closed' ? 'active' : '' ?>">Closed (<?= $stats['closed'] ?>)</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr><th>Received</th><th>Phone</th><th>Message</th><th>Status</th><th>Assigned To</th><th>Follow-up</th><th class="text-center">Actions</th></tr>
                </thead>
                <tbody>
                <?php if (!empty($responses)): foreach ($responses as $r): ?>
                    <tr>
                        <td class="text-nowrap"><?= date('d M Y, h:i A', strtotime($r->received_at)) ?></td>
                        <td><?= htmlspecialchars($r->phone) ?></td>
                        <td style="max-width:320px;"><?= htmlspecialchars(mb_strimwidth($r->message_text ?? '', 0, 120, '…')) ?></td>
                        <td><span class="badge badge-<?= $badge[$r->status] ?? 'light' ?> text-capitalize"><?= $r->status ?></span></td>
                        <td><?= htmlspecialchars($r->assigned_name ?? '—') ?></td>
                        <td><?= $r->follow_up_date ? date('d M Y', strtotime($r->follow_up_date)) : '—' ?></td>
                        <td class="text-center"><a href="<?= base_url('marketing/response_edit/' . $r->id) ?>" class="btn btn-outline-secondary btn-sm py-0">Manage</a></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No responses <?= $filter ? 'with this status ' : '' ?>yet — inbound WhatsApp replies land here automatically.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
