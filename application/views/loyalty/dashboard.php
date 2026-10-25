<?php defined('BASEPATH') OR exit('No direct script access allowed');
function ltile($label, $value, $url, $colour = 'primary', $icon = 'fa-circle', $money = false) { ?>
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="<?= $url ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 lt-card">
                <div class="card-body py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($label) ?></div>
                        <div class="font-weight-bold text-<?= $colour ?>" style="font-size:1.5rem;line-height:1.2;">
                            <?= $money ? '&#8377; ' . number_format((float)$value, 2) : number_format((float)$value) ?>
                        </div>
                    </div>
                    <i class="fas <?= $icon ?> fa-2x text-<?= $colour ?>" style="opacity:.25;"></i>
                </div>
            </div>
        </a>
    </div>
<?php }
$R = base_url('loyalty/reports/');
?>
<style>.lt-card{transition:transform .12s ease,box-shadow .12s ease}.lt-card:hover{transform:translateY(-2px);box-shadow:0 .4rem 1rem rgba(0,0,0,.12)!important}</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-gift text-success mr-1"></i> Loyalty Dashboard</h5>
        <small class="text-muted">
            <?php if ($scheme): ?>
                Current rule: &#8377;<?= number_format((float)$scheme->amount_per_point, 0) ?> = 1 point
                &middot; 1 point = &#8377;<?= number_format((float)$scheme->redeem_value_per_point, 2) ?>
                &middot; max <?= (float)$scheme->max_redeem_percent ?>% of bill
                &middot; <?= (int)$scheme->expiry_days ? (int)$scheme->expiry_days . '-day expiry' : 'no expiry' ?>
            <?php else: ?>No active loyalty rule configured.<?php endif; ?>
        </small>
    </div>
    <div class="d-flex" style="gap:6px;">
        <a href="<?= base_url('loyalty/statement') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-search mr-1"></i> Customer Statement</a>
        <a href="<?= base_url('loyalty/earn_redeem') ?>" class="btn btn-outline-info btn-sm"><i class="fas fa-exchange-alt mr-1"></i> Earned vs Redeemed</a>
        <?php if ($is_admin): ?>
        <a href="<?= base_url('loyalty/settings') ?>" class="btn btn-primary btn-sm"><i class="fas fa-cog mr-1"></i> Loyalty Setup</a>
        <a href="<?= base_url('loyalty/adjust') ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sliders-h mr-1"></i> Adjust Points</a>
        <a href="<?= base_url('loyalty/run_expiry') ?>" class="btn btn-outline-danger btn-sm"
           onclick="return confirm('Expire all points that are past their expiry date?')">
           <i class="fas fa-hourglass-end mr-1"></i> Run Expiry</a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <?php
    ltile('Loyalty Members',    $c['members'],     $R.'balances',      'dark',    'fa-users');
    ltile('Active Members',     $c['active'],      $R.'balances',      'success', 'fa-user-check');
    ltile('Outstanding Points', $c['outstanding'], $R.'balances',      'primary', 'fa-coins');
    ltile('Liability Value',    $c['liability'],   $R.'balances',      'danger',  'fa-rupee-sign', true);
    ltile('Points Issued',      $c['issued'],      $R.'earned',        'success', 'fa-arrow-up');
    ltile('Points Redeemed',    $c['redeemed'],    $R.'redeemed',      'info',    'fa-arrow-down');
    ltile('Points Expired',     $c['expired'],     $R.'expired',       'secondary','fa-hourglass-end');
    ltile('Expiring in 30 Days',$c['expiring_30'], $R.'expiring?days=30','danger','fa-exclamation-triangle');
    ?>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 font-weight-bold d-flex justify-content-between">
                <span>Top 10 Customers</span>
                <a href="<?= $R ?>top_customers" class="small">All</a>
            </div>
            <div class="table-responsive"><table class="table table-sm mb-0" style="font-size:.85rem;">
                <thead class="thead-light"><tr><th>Customer</th><th>Mobile</th><th>Tier</th><th class="text-right">Balance</th><th class="text-right">Lifetime</th></tr></thead>
                <tbody>
                <?php if (empty($top_customers)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No loyalty members yet.</td></tr>
                <?php else: foreach ($top_customers as $t): ?>
                    <tr><td><?= htmlspecialchars($t->customer_name) ?></td>
                        <td><?= htmlspecialchars($t->phone ?? '—') ?></td>
                        <td><span class="badge badge-light border"><?= htmlspecialchars($t->loyalty_tier) ?></span></td>
                        <td class="text-right font-weight-bold"><?= number_format((float)$t->loyalty_points) ?></td>
                        <td class="text-right text-muted"><?= number_format((float)$t->loyalty_lifetime_earned) ?></td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-2 font-weight-bold d-flex justify-content-between">
                <span>Top 10 Point-Earning Items</span>
                <a href="<?= $R ?>itemwise" class="small">All</a>
            </div>
            <div class="table-responsive"><table class="table table-sm mb-0" style="font-size:.85rem;">
                <thead class="thead-light"><tr><th>Item</th><th>Category</th><th class="text-right">Points</th></tr></thead>
                <tbody>
                <?php if (empty($top_items)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-3">No points awarded yet.</td></tr>
                <?php else: foreach ($top_items as $t): ?>
                    <tr><td><?= htmlspecialchars($t->product_name ?? '—') ?></td>
                        <td class="text-muted"><?= htmlspecialchars($t->category_name ?? '—') ?></td>
                        <td class="text-right font-weight-bold"><?= number_format((float)$t->points) ?></td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body py-3">
        <h6 class="font-weight-bold mb-2">All Reports</h6>
        <?php foreach ($this->Loyalty_model->report_types() as $group => $types): ?>
        <div class="mb-1">
            <span class="text-muted mr-2" style="font-size:.75rem;"><?= htmlspecialchars($group) ?>:</span>
            <?php foreach ($types as $t): $m = $this->Loyalty_model->report_meta($t); if (!$m) continue; ?>
                <a href="<?= $R . $t ?>" class="btn btn-outline-secondary btn-sm mb-1" style="font-size:.76rem;"><?= htmlspecialchars($m[0]) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <div class="alert alert-warning py-2 px-3 mt-2 mb-0" style="font-size:.82rem;">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong><?= number_format($c['non_eligible']) ?></strong> item(s) are set to earn no points.
            <a href="<?= $R ?>non_eligible">Review the list</a> to confirm that is intended.
        </div>
    </div>
</div>
