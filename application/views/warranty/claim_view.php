<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($d) { return $d ? date('d-M-Y', strtotime($d)) : '—'; };
$wclass = $warranty['status'] === 'ACTIVE' ? 'success' : ($warranty['status'] === 'EXPIRED' ? 'danger' : 'secondary');
$flow = ['RECEIVED','UNDER_INSPECTION','APPROVED','REPAIR','REPLACEMENT','READY','DELIVERED','CLOSED'];
$cur_ix = array_search($claim->status, $flow, true);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-tools text-primary mr-1"></i> <?= htmlspecialchars($claim->claim_no) ?></h5>
        <small class="text-muted">Opened <?= $fmt($claim->claim_date) ?></small>
    </div>
    <a href="<?= base_url('warranty/claims') ?>" class="btn btn-outline-secondary btn-sm">All Claims</a>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body py-3">
    <div class="d-flex flex-wrap align-items-center" style="gap:6px;">
    <?php foreach ($flow as $ix => $st):
        $done = $cur_ix !== false && $ix < $cur_ix; $now = $claim->status === $st; ?>
        <span class="badge badge-<?= $now ? 'primary' : ($done ? 'success' : 'light') ?> border"
              style="font-size:.78rem;padding:6px 10px;"><?= str_replace('_', ' ', $st) ?></span>
        <?php if ($ix < count($flow) - 1): ?><span class="text-muted">&rsaquo;</span><?php endif; ?>
    <?php endforeach; ?>
    </div>
    <?php if ($claim->status === 'REJECTED'): ?>
      <div class="alert alert-danger py-2 px-3 mt-2 mb-0"><strong>REJECTED.</strong> <?= htmlspecialchars($claim->rejection_reason ?? '') ?></div>
    <?php endif; ?>
</div></div>

<div class="row">
  <div class="col-md-6 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-2 font-weight-bold">Unit &amp; Customer</div>
      <div class="card-body py-2">
        <table class="table table-sm mb-0" style="font-size:.86rem;">
          <tr><td class="text-muted" style="width:42%;">Serial Number</td><td style="font-family:monospace;font-weight:600;"><?= htmlspecialchars($claim->serial_no ?? '—') ?></td></tr>
          <tr><td class="text-muted">Item</td><td><?= htmlspecialchars($claim->product_name ?? '—') ?></td></tr>
          <tr><td class="text-muted">Customer</td><td><?= htmlspecialchars($claim->customer_name ?? '—') ?><?= $claim->customer_phone ? ' · ' . htmlspecialchars($claim->customer_phone) : '' ?></td></tr>
          <tr><td class="text-muted">Original Invoice</td><td><?= htmlspecialchars($claim->invoice_no ?? '—') ?> <small class="text-muted"><?= $fmt($claim->invoice_date) ?></small></td></tr>
          <tr><td class="text-muted">Unit Status</td><td><span class="badge badge-dark"><?= htmlspecialchars($claim->serial_status ?? '—') ?></span></td></tr>
          <?php if ($claim->replacement_serial_no): ?>
          <tr><td class="text-muted">Replaced By</td><td style="font-family:monospace;font-weight:600;" class="text-success"><?= htmlspecialchars($claim->replacement_serial_no) ?></td></tr>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-6 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-2 font-weight-bold">Warranty at Claim</div>
      <div class="card-body py-2">
        <div class="alert alert-<?= $wclass ?> py-2 px-3 mb-2">
          <strong><?= $warranty['status'] === 'ACTIVE' ? '&#10003; WARRANTY ACTIVE' : ($warranty['status'] === 'EXPIRED' ? '&#9888; WARRANTY EXPIRED' : 'NO WARRANTY') ?></strong><br>
          <?= htmlspecialchars($warranty['text'] ?? '') ?>
        </div>
        <table class="table table-sm mb-0" style="font-size:.86rem;">
          <tr><td class="text-muted" style="width:42%;">Warranty Period</td><td><?= (int)($claim->warranty_days ?? 0) ?> Days</td></tr>
          <tr><td class="text-muted">Start</td><td><?= $fmt($claim->warranty_start_date) ?></td></tr>
          <tr><td class="text-muted">Expiry</td><td class="font-weight-bold"><?= $fmt($claim->warranty_expiry_date) ?></td></tr>
          <tr><td class="text-muted">In warranty when claimed</td><td><?= $claim->warranty_active_at_claim ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>' ?></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-6 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-2 font-weight-bold">Complaint &amp; Inspection</div>
      <div class="card-body py-2" style="font-size:.87rem;">
        <p class="mb-1"><span class="text-muted">Customer complaint:</span><br><?= nl2br(htmlspecialchars($claim->customer_complaint ?? '—')) ?></p>
        <p class="mb-1"><span class="text-muted">Problem:</span><br><?= nl2br(htmlspecialchars($claim->problem_description ?? '—')) ?></p>
        <p class="mb-1"><span class="text-muted">Inspection:</span><br><?= nl2br(htmlspecialchars($claim->inspection_details ?? '—')) ?></p>
        <p class="mb-0"><span class="text-muted">Resolution:</span><br><?= nl2br(htmlspecialchars($claim->resolution ?? '—')) ?></p>
      </div>
    </div>
  </div>

  <div class="col-md-6 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-2 font-weight-bold">Move Claim Forward</div>
      <div class="card-body py-2">
        <?php if (empty($next)): ?>
          <p class="text-muted mb-0">This claim is closed. No further action.</p>
        <?php else: ?>
        <?= form_open('warranty/claim_status/' . $claim->id) ?>
          <div class="form-group mb-2">
            <label class="font-weight-bold" style="font-size:.85rem;">Next Step</label>
            <select name="status" class="form-control form-control-sm" required>
              <?php foreach ($next as $n): ?><option value="<?= $n ?>"><?= str_replace('_', ' ', $n) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group mb-2">
            <label class="font-weight-bold" style="font-size:.85rem;">Remarks</label>
            <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Reason / inspection note">
          </div>
          <button class="btn btn-primary btn-sm"><i class="fas fa-arrow-right mr-1"></i> Update Status</button>
          <small class="d-block text-muted mt-1">Approve and Reject require manager permission.</small>
        <?= form_close() ?>
        <?php endif; ?>

        <?php if (in_array($claim->status, ['APPROVED', 'REPLACEMENT'], true) && !$claim->replacement_serial_no): ?>
        <hr class="my-2">
        <?= form_open('warranty/claim_replace/' . $claim->id) ?>
          <label class="font-weight-bold" style="font-size:.85rem;">Issue Replacement Unit</label>
          <div class="input-group input-group-sm">
            <input type="text" name="replacement_serial" class="form-control" style="font-family:monospace;"
                   placeholder="Scan the replacement serial number" required>
            <div class="input-group-append"><button class="btn btn-success">Replace</button></div>
          </div>
          <small class="text-muted">Must be an in-stock unit of the same item. The original keeps its full history.</small>
        <?= form_close() ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white py-2 font-weight-bold">Claim History</div>
  <div class="table-responsive"><table class="table table-sm mb-0" style="font-size:.85rem;">
    <thead class="thead-light"><tr><th>When</th><th>From</th><th>To</th><th>Remarks</th><th>User</th></tr></thead>
    <tbody><?php foreach ($log as $l): ?>
      <tr><td class="text-nowrap"><?= date('d-M-Y H:i', strtotime($l->created_at)) ?></td>
          <td><?= htmlspecialchars(str_replace('_', ' ', (string)($l->old_status ?? '—'))) ?></td>
          <td><strong><?= htmlspecialchars(str_replace('_', ' ', $l->new_status)) ?></strong></td>
          <td><?= htmlspecialchars($l->remarks ?? '') ?></td>
          <td><?= htmlspecialchars($l->user_name ?? '') ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white py-2 font-weight-bold">Serial Movement History</div>
  <div class="table-responsive"><table class="table table-sm mb-0" style="font-size:.85rem;">
    <thead class="thead-light"><tr><th>Date</th><th>Transaction</th><th>Reference</th><th>Status</th><th>User</th><th>Remarks</th></tr></thead>
    <tbody><?php foreach ($history as $h): ?>
      <tr><td class="text-nowrap"><?= date('d-M-Y H:i', strtotime($h->txn_date)) ?></td>
          <td><span class="badge badge-secondary"><?= htmlspecialchars($h->txn_type) ?></span></td>
          <td><?= htmlspecialchars($h->reference_no ?? '—') ?></td>
          <td><small><?= htmlspecialchars(($h->old_status ?? '—') . ' → ' . ($h->new_status ?? '—')) ?></small></td>
          <td><?= htmlspecialchars($h->user_name ?? '') ?></td>
          <td><small><?= htmlspecialchars($h->remarks ?? '') ?></small></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
</div>
