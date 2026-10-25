<?php // FILE: application/views/marketing/dashboard.php
$s = $summary;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> WhatsApp Marketing</h5>
        <small class="text-muted">Campaign performance dashboard</small>
    </div>
    <?php if ($can_configure): ?>
    <a href="<?= base_url('settings#whatsapp-marketing') ?>" class="btn btn-outline-dark btn-sm"><i class="fas fa-cog mr-1"></i> Configure API</a>
    <?php endif; ?>
</div>

<?php if (!$is_configured): ?>
<div class="alert alert-warning">
    <strong>WhatsApp Business API is not connected yet.</strong>
    <?php if ($can_configure): ?>
        Add your Phone Number ID and Access Token under <a href="<?= base_url('settings#whatsapp-marketing') ?>">Settings → WhatsApp Marketing API</a> before campaigns can send.
    <?php else: ?>
        Contact your account administrator to have this connected.
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2">
            <div class="text-muted small">Total Customers</div>
            <div class="h5 mb-0 font-weight-bold"><?= number_format($s['total_customers']) ?></div>
        </div></div>
    </div>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2">
            <div class="text-muted small">WhatsApp Active Customers</div>
            <div class="h5 mb-0 font-weight-bold text-success"><?= number_format($s['whatsapp_active']) ?></div>
        </div></div>
    </div>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2">
            <div class="text-muted small">Campaigns Created</div>
            <div class="h5 mb-0 font-weight-bold"><?= number_format($s['campaigns_created']) ?></div>
        </div></div>
    </div>
    <div class="col-md-3 col-sm-6 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2">
            <div class="text-muted small">Messages Sent Today</div>
            <div class="h5 mb-0 font-weight-bold"><?= number_format($s['messages_sent_today']) ?></div>
        </div></div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 text-center">
            <div class="text-muted small">Delivered</div>
            <div class="h6 mb-0 font-weight-bold"><?= number_format($s['delivered']) ?></div>
        </div></div>
    </div>
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 text-center">
            <div class="text-muted small">Read</div>
            <div class="h6 mb-0 font-weight-bold"><?= number_format($s['read']) ?></div>
        </div></div>
    </div>
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 text-center">
            <div class="text-muted small">Failed</div>
            <div class="h6 mb-0 font-weight-bold text-danger"><?= number_format($s['failed']) ?></div>
        </div></div>
    </div>
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 text-center">
            <div class="text-muted small">Responses</div>
            <div class="h6 mb-0 font-weight-bold"><?= number_format($s['responses']) ?></div>
        </div></div>
    </div>
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 text-center">
            <div class="text-muted small">Conversion Rate</div>
            <div class="h6 mb-0 font-weight-bold text-primary"><?= $s['conversion_rate'] ?>%</div>
        </div></div>
    </div>
    <div class="col-md-2 col-sm-4 mb-2">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-2 text-center">
            <div class="text-muted small">Sales Generated</div>
            <div class="h6 mb-0 font-weight-bold text-success">₹<?= number_format($s['sales_generated'], 0) ?></div>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header py-2 font-weight-bold d-flex justify-content-between align-items-center" style="font-size:.85rem;">
        Recent Campaigns
        <span class="badge badge-secondary">Campaign creation — coming in the next phase</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr><th>Name</th><th>Type</th><th>Status</th><th>Start</th><th>End</th></tr>
                </thead>
                <tbody>
                <?php if (!empty($recent_campaigns)): foreach ($recent_campaigns as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c->name) ?></td>
                        <td><span class="text-capitalize"><?= str_replace('_', ' ', $c->campaign_type) ?></span></td>
                        <td><span class="badge badge-light border text-capitalize"><?= $c->status ?></span></td>
                        <td><?= date('d M Y', strtotime($c->start_date)) ?></td>
                        <td><?= date('d M Y', strtotime($c->end_date)) ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No campaigns yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
