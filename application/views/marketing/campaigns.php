<?php // FILE: application/views/marketing/campaigns.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> Campaigns</h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / Campaigns</small>
    </div>
    <a href="<?= base_url('marketing/campaign_create') ?>" class="btn btn-dark btn-sm">+ New Campaign</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Start</th>
                        <th>End</th>
                        <th class="text-right">Budget</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($campaigns)): foreach ($campaigns as $c): ?>
                    <tr>
                        <td><a href="<?= base_url('marketing/campaign_view/' . $c->id) ?>"><?= htmlspecialchars($c->name) ?></a></td>
                        <td><span class="text-capitalize"><?= str_replace('_', ' ', $c->campaign_type) ?></span></td>
                        <td>
                            <?php
                            $badge = ['draft' => 'secondary', 'scheduled' => 'info', 'running' => 'success', 'paused' => 'warning', 'completed' => 'primary', 'cancelled' => 'danger'][$c->status] ?? 'light';
                            ?>
                            <span class="badge badge-<?= $badge ?> text-capitalize"><?= $c->status ?></span>
                        </td>
                        <td><?= date('d M Y', strtotime($c->start_date)) ?></td>
                        <td><?= date('d M Y', strtotime($c->end_date)) ?></td>
                        <td class="text-right"><?= $c->budget > 0 ? '₹' . number_format($c->budget, 2) : '—' ?></td>
                        <td class="text-center"><a href="<?= base_url('marketing/campaign_view/' . $c->id) ?>" class="btn btn-outline-secondary btn-sm py-0">View</a></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No campaigns yet. <a href="<?= base_url('marketing/campaign_create') ?>">Create your first one</a>.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
