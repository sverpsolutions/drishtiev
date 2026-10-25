<?php defined('BASEPATH') OR exit('No direct script access allowed');
$t  = $totals;
$qs = function ($over = []) use ($from, $to, $mode) {
    return http_build_query(array_merge(['from' => $from, 'to' => $to, 'mode' => $mode], $over));
};
$n = function ($v) { return number_format((float)$v, 0); };
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-exchange-alt text-success mr-1"></i> Points Earned vs Redeemed</h5>
        <small class="text-muted">
            <?= date('d-M-Y', strtotime($from)) ?> to <?= date('d-M-Y', strtotime($to)) ?>
            &middot; <?= $n($t->customers ?? 0) ?> customer(s) with activity
        </small>
    </div>
    <div class="d-flex" style="gap:6px;">
        <a href="<?= base_url('loyalty/earn_redeem') ?>?<?= $qs(['export' => 1]) ?>" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel mr-1"></i> Export Excel</a>
        <a href="<?= base_url('loyalty') ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body py-2">
<form method="get" class="form-inline" style="gap:8px;">
    <label class="small font-weight-bold mb-0">From</label>
    <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>">
    <label class="small font-weight-bold mb-0">To</label>
    <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>">

    <div class="btn-group btn-group-sm ml-2" role="group">
        <button type="submit" name="mode" value="summary"
                class="btn <?= $mode === 'summary' ? 'btn-primary' : 'btn-outline-primary' ?>">Summary</button>
        <button type="submit" name="mode" value="detail"
                class="btn <?= $mode === 'detail' ? 'btn-primary' : 'btn-outline-primary' ?>">Detail</button>
    </div>

    <span class="ml-3 small text-muted">Quick:</span>
    <?php
    $ranges = [
        'Today'      => [date('Y-m-d'), date('Y-m-d')],
        'This Month' => [date('Y-m-01'), date('Y-m-d')],
        'Last Month' => [date('Y-m-01', strtotime('first day of last month')),
                         date('Y-m-t',  strtotime('last day of last month'))],
        'This Year'  => [date('Y-01-01'), date('Y-m-d')],
    ];
    foreach ($ranges as $label => $r): ?>
        <a class="btn btn-sm btn-outline-secondary"
           href="<?= base_url('loyalty/earn_redeem') ?>?from=<?= $r[0] ?>&to=<?= $r[1] ?>&mode=<?= $mode ?>"><?= $label ?></a>
    <?php endforeach; ?>
</form>
</div></div>

<!-- Totals: shown in both views, because the summary IS these numbers -->
<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #16a34a !important;">
            <div class="card-body text-center py-4">
                <div class="text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.06em;">Total Points Earned</div>
                <div class="font-weight-bold text-success" style="font-size:2.4rem;line-height:1.1;"><?= $n($t->earned ?? 0) ?></div>
                <div class="text-muted" style="font-size:.8rem;">worth &#8377; <?= number_format((float)($t->earned ?? 0) * $rate, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #0284c7 !important;">
            <div class="card-body text-center py-4">
                <div class="text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.06em;">Total Points Redeemed</div>
                <div class="font-weight-bold text-info" style="font-size:2.4rem;line-height:1.1;"><?= $n($t->redeemed ?? 0) ?></div>
                <div class="text-muted" style="font-size:.8rem;">worth &#8377; <?= number_format((float)($t->redeemed ?? 0) * $rate, 2) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <?php $net = (float)($t->net ?? 0); ?>
        <div class="card border-0 shadow-sm h-100" style="border-left:4px solid <?= $net >= 0 ? '#7c3aed' : '#dc2626' ?> !important;">
            <div class="card-body text-center py-4">
                <div class="text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.06em;">Net Movement</div>
                <div class="font-weight-bold" style="font-size:2.4rem;line-height:1.1;color:<?= $net >= 0 ? '#7c3aed' : '#dc2626' ?>;">
                    <?= $net >= 0 ? '+' : '' ?><?= $n($net) ?>
                </div>
                <div class="text-muted" style="font-size:.8rem;">change in outstanding points</div>
            </div>
        </div>
    </div>
</div>

<!-- The other movement types, so the net actually reconciles -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row text-center" style="font-size:.85rem;">
            <div class="col"><div class="text-muted">Manual Credit</div><div class="font-weight-bold text-success">+ <?= $n($t->manual_credit ?? 0) ?></div></div>
            <div class="col"><div class="text-muted">Manual Debit</div><div class="font-weight-bold text-warning">- <?= $n($t->manual_debit ?? 0) ?></div></div>
            <div class="col"><div class="text-muted">Expired</div><div class="font-weight-bold text-secondary">- <?= $n($t->expired ?? 0) ?></div></div>
            <div class="col"><div class="text-muted">Reversed <small>(returns / cancels)</small></div><div class="font-weight-bold text-danger">- <?= $n($t->reversed ?? 0) ?></div></div>
            <div class="col"><div class="text-muted">Transactions</div><div class="font-weight-bold"><?= $n($t->txns ?? 0) ?></div></div>
        </div>
        <div class="text-muted mt-1" style="font-size:.76rem;">
            Net = Earned + Manual Credit &minus; Redeemed &minus; Manual Debit &minus; Expired &minus; Reversed.
        </div>
    </div>
</div>

<?php if ($mode === 'detail'): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 font-weight-bold">
        Customer Detail <span class="text-muted font-weight-normal">(<?= count($rows) ?> customer(s))</span>
    </div>
    <div class="table-responsive">
    <table class="table table-hover table-sm mb-0" style="font-size:.85rem;">
        <thead class="thead-dark"><tr>
            <th>Customer</th><th>Mobile</th><th>Tier</th>
            <th class="text-right">Earned</th><th class="text-right">Redeemed</th>
            <th class="text-right">Manual +</th><th class="text-right">Manual -</th>
            <th class="text-right">Expired</th><th class="text-right">Reversed</th>
            <th class="text-right">Net</th><th class="text-right">Balance Now</th>
        </tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="11" class="text-center text-muted py-4">No loyalty activity in this period.</td></tr>
        <?php else: foreach ($rows as $r):
            $rnet = $r->earned + $r->manual_credit - $r->redeemed - $r->manual_debit - $r->expired - $r->reversed; ?>
            <tr>
                <td><a href="<?= base_url('loyalty/statement/' . $r->customer_id) ?>"><?= htmlspecialchars($r->customer_name) ?></a></td>
                <td><?= htmlspecialchars($r->phone ?? '—') ?></td>
                <td><span class="badge badge-light border"><?= htmlspecialchars($r->loyalty_tier) ?></span></td>
                <td class="text-right text-success font-weight-bold"><?= $r->earned > 0 ? $n($r->earned) : '—' ?></td>
                <td class="text-right text-info font-weight-bold"><?= $r->redeemed > 0 ? $n($r->redeemed) : '—' ?></td>
                <td class="text-right"><?= $r->manual_credit > 0 ? $n($r->manual_credit) : '—' ?></td>
                <td class="text-right"><?= $r->manual_debit > 0 ? $n($r->manual_debit) : '—' ?></td>
                <td class="text-right"><?= $r->expired > 0 ? $n($r->expired) : '—' ?></td>
                <td class="text-right"><?= $r->reversed > 0 ? $n($r->reversed) : '—' ?></td>
                <td class="text-right font-weight-bold" style="color:<?= $rnet >= 0 ? '#7c3aed' : '#dc2626' ?>;">
                    <?= $rnet >= 0 ? '+' : '' ?><?= $n($rnet) ?></td>
                <td class="text-right"><?= $n($r->balance) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
        <?php if (!empty($rows)): ?>
        <tfoot class="bg-light font-weight-bold">
            <tr>
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-right text-success"><?= $n($t->earned) ?></td>
                <td class="text-right text-info"><?= $n($t->redeemed) ?></td>
                <td class="text-right"><?= $n($t->manual_credit) ?></td>
                <td class="text-right"><?= $n($t->manual_debit) ?></td>
                <td class="text-right"><?= $n($t->expired) ?></td>
                <td class="text-right"><?= $n($t->reversed) ?></td>
                <td class="text-right"><?= $net >= 0 ? '+' : '' ?><?= $n($net) ?></td>
                <td></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
    </div>
</div>
<?php else: ?>
<div class="alert alert-light border" style="font-size:.87rem;">
    <i class="fas fa-info-circle mr-1"></i>
    This is the <strong>summary</strong> view — period totals only, no customer names.
    Switch to <strong>Detail</strong> above for the customer-by-customer breakdown.
</div>
<?php endif; ?>
