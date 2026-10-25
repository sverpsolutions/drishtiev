<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($d) { return $d ? date('d-M-Y H:i', strtotime($d)) : '—'; };
$sign = ['EARN'=>'+','MANUAL_CREDIT'=>'+','REDEEM'=>'-','EXPIRY'=>'-','MANUAL_DEBIT'=>'-',
         'SALES_RETURN_REVERSAL'=>'-','BILL_CANCEL_REVERSAL'=>'±','ADJUSTMENT'=>'±'];
$col  = ['EARN'=>'success','MANUAL_CREDIT'=>'success','REDEEM'=>'info','EXPIRY'=>'secondary',
         'MANUAL_DEBIT'=>'warning','SALES_RETURN_REVERSAL'=>'danger','BILL_CANCEL_REVERSAL'=>'danger','ADJUSTMENT'=>'dark'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-file-invoice text-primary mr-1"></i> Customer Loyalty Statement</h5>
    <a href="<?= base_url('loyalty') ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body py-3">
    <form method="get" class="form-inline">
        <input type="text" name="q" class="form-control mr-2" style="min-width:320px;"
               value="<?= htmlspecialchars($q) ?>" autofocus
               placeholder="Mobile number, loyalty card number or customer ID">
        <button class="btn btn-primary"><i class="fas fa-search mr-1"></i> Find</button>
    </form>
</div></div>

<?php if (!empty($customer)): $cu = $customer;
    $diff = round((float)$cu->loyalty_points - (float)$ledger_bal, 2); ?>
<div class="row">
  <div class="col-md-5 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-2 font-weight-bold">Member</div>
      <div class="card-body py-2">
        <table class="table table-sm mb-0" style="font-size:.87rem;">
          <tr><td class="text-muted" style="width:45%;">Customer</td><td class="font-weight-bold"><?= htmlspecialchars($cu->name) ?></td></tr>
          <tr><td class="text-muted">Mobile</td><td><?= htmlspecialchars($cu->phone ?? '—') ?></td></tr>
          <tr><td class="text-muted">Loyalty Member</td>
              <td><?= (int)$cu->loyalty_member === 1
                    ? '<span class="badge badge-success">YES</span>'
                    : '<span class="badge badge-secondary">NOT ENROLLED</span>' ?></td></tr>
          <tr><td class="text-muted">Tier</td><td><?= htmlspecialchars($cu->loyalty_tier) ?></td></tr>
          <tr><td class="text-muted">Card No</td><td><?= htmlspecialchars($cu->loyalty_card_no ?? '—') ?></td></tr>
          <tr><td class="text-muted">Enrolled</td><td><?= $cu->loyalty_enrolled_at ? date('d-M-Y', strtotime($cu->loyalty_enrolled_at)) : '—' ?></td></tr>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-7 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-2 font-weight-bold">Points</div>
      <div class="card-body py-2">
        <div class="row text-center">
          <div class="col"><div class="text-muted small">Current Balance</div>
            <div class="h4 font-weight-bold text-primary mb-0"><?= number_format((float)$cu->loyalty_points) ?></div></div>
          <div class="col"><div class="text-muted small">Lifetime Earned</div>
            <div class="h5 font-weight-bold text-success mb-0"><?= number_format((float)$cu->loyalty_lifetime_earned) ?></div></div>
          <div class="col"><div class="text-muted small">Redeemed</div>
            <div class="h5 font-weight-bold text-info mb-0"><?= number_format((float)$cu->loyalty_lifetime_redeemed) ?></div></div>
          <div class="col"><div class="text-muted small">Expired</div>
            <div class="h5 font-weight-bold text-secondary mb-0"><?= number_format((float)$cu->loyalty_lifetime_expired) ?></div></div>
        </div>
        <?php if (abs($diff) > 0.009): ?>
        <div class="alert alert-danger py-2 px-3 mt-2 mb-0" style="font-size:.83rem;">
            <strong>Balance mismatch.</strong> Cached <?= number_format((float)$cu->loyalty_points, 2) ?>
            vs ledger <?= number_format((float)$ledger_bal, 2) ?> (difference <?= number_format($diff, 2) ?>).
            The ledger is the source of truth — see the Reconciliation report.
        </div>
        <?php else: ?>
        <div class="text-success mt-2" style="font-size:.82rem;">
            <i class="fas fa-check-circle mr-1"></i> Balance agrees with the ledger.
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white py-2 font-weight-bold">Ledger</div>
  <div class="table-responsive"><table class="table table-sm table-hover mb-0" style="font-size:.85rem;">
    <thead class="thead-dark"><tr>
      <th>When</th><th>Type</th><th class="text-right">Points</th><th class="text-right">Balance</th>
      <th>Reference</th><th>Expires</th><th>Remarks</th><th>By</th>
    </tr></thead>
    <tbody>
    <?php if (empty($rows)): ?>
      <tr><td colspan="8" class="text-center text-muted py-4">No loyalty activity yet.</td></tr>
    <?php else: foreach ($rows as $r): ?>
      <tr>
        <td class="text-nowrap"><?= $fmt($r->created_at) ?></td>
        <td><span class="badge badge-<?= $col[$r->txn_type] ?? 'secondary' ?>"><?= str_replace('_',' ',$r->txn_type) ?></span></td>
        <td class="text-right font-weight-bold"><?= ($sign[$r->txn_type] ?? '') . number_format((float)$r->points) ?></td>
        <td class="text-right"><?= number_format((float)$r->closing_balance) ?></td>
        <td><?= htmlspecialchars($r->reference_no ?? '—') ?></td>
        <td><?= $r->expiry_date ? date('d-M-Y', strtotime($r->expiry_date)) : '—' ?></td>
        <td><small><?= htmlspecialchars($r->remarks ?? '') ?></small></td>
        <td><small><?= htmlspecialchars($r->created_by_name ?? '') ?></small></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table></div>
</div>

<?php elseif ($q !== ''): ?>
<div class="alert alert-warning">No customer found for "<strong><?= htmlspecialchars($q) ?></strong>".</div>
<?php endif; ?>
