<?php // FILE: application/views/marketing/templates.php
$badge = ['draft' => 'secondary', 'pending_approval' => 'info', 'approved' => 'success', 'rejected' => 'danger'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> Message Templates</h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / Templates</small>
    </div>
    <a href="<?= base_url('marketing/campaigns') ?>" class="btn btn-outline-dark btn-sm">Back to Campaigns</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="alert alert-info small mb-3">
    <i class="fas fa-info-circle mr-1"></i> WhatsApp only allows business-initiated marketing messages using a template that Meta has pre-approved in your Business Manager account. A template created here stays in <strong>Draft</strong> until you've registered the identical template with Meta and it comes back approved — only then mark it <strong>Approved</strong> with its exact Meta template name, or a campaign using it cannot start sending.
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Name</th>
                        <th>Meta Template Name</th>
                        <th>Category</th>
                        <th>Language</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($templates)): foreach ($templates as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t->name) ?></td>
                        <td><?= $t->meta_template_name ? '<code>' . htmlspecialchars($t->meta_template_name) . '</code>' : '<span class="text-muted">— not set —</span>' ?></td>
                        <td class="text-capitalize"><?= htmlspecialchars($t->category ?: 'marketing') ?></td>
                        <td><?= htmlspecialchars($t->language) ?></td>
                        <td><span class="badge badge-<?= $badge[$t->status] ?? 'light' ?> text-capitalize"><?= str_replace('_', ' ', $t->status) ?></span></td>
                        <td class="text-center"><a href="<?= base_url('marketing/template_edit/' . $t->id) ?>" class="btn btn-outline-secondary btn-sm py-0">Edit</a></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No templates yet. Templates are created automatically when you compose a new campaign.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
