<?php // FILE: application/views/marketing/campaign_view.php
$badge_map = ['draft' => 'secondary', 'scheduled' => 'info', 'running' => 'success', 'paused' => 'warning', 'completed' => 'primary', 'cancelled' => 'danger'];
$status_badge = $badge_map[$campaign->status] ?? 'light';
$recip_badge = ['pending' => 'secondary', 'queued' => 'info', 'sending' => 'info', 'sent' => 'primary', 'delivered' => 'success', 'read' => 'success', 'failed' => 'danger', 'replied' => 'warning', 'opted_out' => 'dark'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> <?= htmlspecialchars($campaign->name) ?>
            <span class="badge badge-<?= $status_badge ?> text-capitalize align-middle"><?= $campaign->status ?></span>
        </h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / <a href="<?= base_url('marketing/campaigns') ?>">Campaigns</a> / <?= htmlspecialchars($campaign->name) ?></small>
    </div>
    <a href="<?= base_url('marketing/campaigns') ?>" class="btn btn-outline-dark btn-sm">Back to Campaigns</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<?php if ($campaign->status === 'draft'): ?>
    <div class="mb-3">
        <a href="<?= base_url('marketing/campaign_start/' . $campaign->id) ?>" class="btn btn-success btn-sm"
           onclick="return confirm('Start sending this campaign to <?= (int)$recipient_stats['total'] ?> recipients now?');">
            <i class="fab fa-whatsapp mr-1"></i> Start Sending (API)
        </a>
        <a href="<?= base_url('marketing/campaign_manual_send/' . $campaign->id) ?>" class="btn btn-outline-success btn-sm">
            <i class="fas fa-hand-pointer mr-1"></i> Send Manually (WhatsApp Web)
        </a>
    </div>
<?php elseif (in_array($campaign->status, ['running', 'paused', 'completed'], true)): ?>
    <div class="mb-3">
        <a href="<?= base_url('marketing/campaign_runner/' . $campaign->id) ?>" class="btn btn-outline-success btn-sm">
            <i class="fas fa-satellite-dish mr-1"></i> <?= $campaign->status === 'running' ? 'View Live Sending' : ($campaign->status === 'paused' ? 'Resume / View Sending' : 'View Send Report') ?>
        </a>
        <?php if ((int)$recipient_stats['pending'] > 0): ?>
        <a href="<?= base_url('marketing/campaign_manual_send/' . $campaign->id) ?>" class="btn btn-outline-success btn-sm">
            <i class="fas fa-hand-pointer mr-1"></i> Send Manually (WhatsApp Web)
        </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-2 col-6 mb-2">
        <div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 font-weight-bold"><?= (int)$recipient_stats['total'] ?></div><div class="text-muted small">Total</div></div>
    </div>
    <div class="col-md-2 col-6 mb-2">
        <div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 font-weight-bold text-primary"><?= (int)$recipient_stats['sent'] ?></div><div class="text-muted small">Sent</div></div>
    </div>
    <div class="col-md-2 col-6 mb-2">
        <div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 font-weight-bold text-success"><?= (int)$recipient_stats['delivered'] ?></div><div class="text-muted small">Delivered</div></div>
    </div>
    <div class="col-md-2 col-6 mb-2">
        <div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 font-weight-bold text-success"><?= (int)$recipient_stats['read'] ?></div><div class="text-muted small">Read</div></div>
    </div>
    <div class="col-md-2 col-6 mb-2">
        <div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 font-weight-bold text-warning"><?= (int)$recipient_stats['replied'] ?></div><div class="text-muted small">Replied</div></div>
    </div>
    <div class="col-md-2 col-6 mb-2">
        <div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 font-weight-bold text-danger"><?= (int)$recipient_stats['failed'] ?></div><div class="text-muted small">Failed</div></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Recipients <span class="text-muted font-weight-normal">(showing up to 200)</span></div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:420px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                        <thead class="thead-light" style="position:sticky;top:0;">
                            <tr><th>Name</th><th>Phone</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($recipients)): foreach ($recipients as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r->customer_name) ?></td>
                                <td><?= htmlspecialchars($r->phone) ?></td>
                                <td><span class="badge badge-<?= $recip_badge[$r->status] ?? 'light' ?> text-capitalize"><?= $r->status ?></span></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">No recipients matched the filter at creation time.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Campaign Info</div>
            <div class="card-body" style="font-size:.85rem;">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted" style="width:40%;">Type</td><td class="text-capitalize"><?= str_replace('_', ' ', $campaign->campaign_type) ?></td></tr>
                    <tr><td class="text-muted">Start Date</td><td><?= date('d M Y', strtotime($campaign->start_date)) ?></td></tr>
                    <tr><td class="text-muted">End Date</td><td><?= date('d M Y', strtotime($campaign->end_date)) ?></td></tr>
                    <?php if (!empty($campaign->valid_till)): ?>
                    <tr><td class="text-muted">Valid Till</td><td><?= date('d M Y', strtotime($campaign->valid_till)) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($campaign->min_purchase > 0): ?>
                    <tr><td class="text-muted">Min Purchase</td><td>₹<?= number_format($campaign->min_purchase, 2) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($campaign->max_discount > 0): ?>
                    <tr><td class="text-muted">Max Discount</td><td>₹<?= number_format($campaign->max_discount, 2) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($campaign->coupon_code)): ?>
                    <tr><td class="text-muted">Coupon Code</td><td><code><?= htmlspecialchars($campaign->coupon_code) ?></code></td></tr>
                    <?php endif; ?>
                    <?php if ($campaign->budget > 0): ?>
                    <tr><td class="text-muted">Budget</td><td>₹<?= number_format($campaign->budget, 2) ?></td></tr>
                    <?php endif; ?>
                    <tr><td class="text-muted">Priority</td><td><?= (int)$campaign->priority ?></td></tr>
                    <?php if (!empty($campaign->description)): ?>
                    <tr><td class="text-muted">Description</td><td><?= nl2br(htmlspecialchars($campaign->description)) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold d-flex justify-content-between align-items-center" style="font-size:.85rem;">
                <span>Poster</span>
                <a href="<?= base_url('marketing/poster_generate/' . $campaign->id) ?>" class="small"><?= $campaign->poster_path ? 'Regenerate' : 'Generate' ?></a>
            </div>
            <?php if (!empty($campaign->poster_path)): ?>
                <img src="<?= base_url($campaign->poster_path) ?>" class="card-img-bottom" alt="Campaign poster">
            <?php else: ?>
                <div class="card-body text-center text-muted small py-4">No poster generated yet.</div>
            <?php endif; ?>
        </div>

        <?php if ($template): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold d-flex justify-content-between align-items-center" style="font-size:.85rem;">
                <span>Message Template <span class="badge badge-<?= $template->status === 'approved' ? 'success' : 'light border' ?> text-capitalize"><?= str_replace('_', ' ', $template->status) ?></span></span>
                <a href="<?= base_url('marketing/template_edit/' . $template->id) ?>" class="small">Edit</a>
            </div>
            <div class="card-body">
                <div class="p-2 rounded" style="background:#e9f8ee;white-space:pre-wrap;font-size:.85rem;"><?= htmlspecialchars($template->body_text) ?></div>
                <?php if ($template->status !== 'approved' && $campaign->status === 'draft'): ?>
                <p class="small text-warning mt-2 mb-0"><i class="fas fa-triangle-exclamation mr-1"></i> Not approved yet — this must be marked Approved with its Meta template name before the campaign can start sending.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($target_filter)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Customer Filter Used</div>
            <div class="card-body" style="font-size:.82rem;">
                <?php
                $labels = [
                    'city' => 'City', 'pincode' => 'Pincode', 'customer_type' => 'Customer Type',
                    'last_purchase_days' => "Haven't purchased in (days)", 'new_customer_days' => 'Registered within (days)',
                    'min_total_spent' => 'Min total spent', 'min_balance' => 'Min outstanding balance',
                    'min_wallet_balance' => 'Min wallet balance', 'never_purchased' => 'Never purchased only',
                ];
                ?>
                <ul class="mb-0 pl-3">
                <?php foreach ($target_filter as $k => $v): ?>
                    <li><?= $labels[$k] ?? $k ?>: <strong><?= $k === 'never_purchased' ? 'Yes' : htmlspecialchars($v) ?></strong></li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
