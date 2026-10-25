<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($d) { return $d ? date('d-M-Y', strtotime($d)) : '—'; };
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-search text-primary mr-1"></i> Warranty / Serial Number Search</h5>
    <a href="<?= base_url('warranty') ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="get" action="<?= base_url('warranty/search') ?>" class="form-inline">
            <input type="text" name="q" class="form-control mr-2" style="min-width:340px;font-family:monospace;"
                   value="<?= htmlspecialchars($q) ?>" autofocus
                   placeholder="Scan serial number, or type customer / invoice / mobile / item">
            <button class="btn btn-primary"><i class="fas fa-search mr-1"></i> Search</button>
        </form>
        <small class="text-muted">An exact serial number opens its full history. Anything else searches sales.</small>
    </div>
</div>

<?php if (!empty($detail)):
    $d = $detail;
    $wclass = $warranty['status'] === 'ACTIVE' ? 'success' : ($warranty['status'] === 'EXPIRED' ? 'danger' : 'secondary');
?>
<div class="row">
  <div class="col-md-6 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-2 font-weight-bold">Serial Number Details</div>
      <div class="card-body py-2">
        <table class="table table-sm mb-0" style="font-size:.86rem;">
          <tr><td class="text-muted" style="width:45%;">Serial / VIN Number</td><td class="font-weight-bold" style="font-family:monospace;"><?= htmlspecialchars($d->serial_no) ?></td></tr>
          <?php if (!empty($d->motor_no)): ?>
          <tr><td class="text-muted">Motor Number</td><td class="font-weight-bold" style="font-family:monospace;"><?= htmlspecialchars($d->motor_no) ?></td></tr>
          <?php endif; ?>
          <?php if (!empty($d->colour)): ?>
          <tr><td class="text-muted">Vehicle Colour</td><td><?= htmlspecialchars($d->colour) ?></td></tr>
          <?php endif; ?>
          <?php if (!empty($d->linked_chassis_no)): ?>
          <tr><td class="text-muted">Linked Scooter VIN</td><td><a href="<?= base_url('warranty/search?q='.urlencode($d->linked_chassis_no)) ?>" class="badge badge-info"><i class="fas fa-motorcycle mr-1"></i><?= htmlspecialchars($d->linked_chassis_no) ?></a></td></tr>
          <?php endif; ?>
          <tr><td class="text-muted">Item</td><td><?= htmlspecialchars($d->product_name ?? '—') ?> <small class="text-muted"><?= htmlspecialchars($d->item_code ?? '') ?></small></td></tr>
          <tr><td class="text-muted">Purchase Date</td><td><?= $fmt($d->purchase_date) ?></td></tr>
          <tr><td class="text-muted">Supplier</td><td><?= htmlspecialchars($d->supplier_name ?? '—') ?></td></tr>
          <tr><td class="text-muted">Sale Invoice</td><td><?= htmlspecialchars($d->invoice_no ?? '—') ?></td></tr>
          <tr><td class="text-muted">Sale Date</td><td><?= $fmt($d->sale_date) ?></td></tr>
          <tr><td class="text-muted">Customer</td><td><?= htmlspecialchars($d->customer_name ?? '—') ?><?= $d->customer_phone ? ' · '.htmlspecialchars($d->customer_phone) : '' ?></td></tr>
          <tr><td class="text-muted">Current Location</td><td><?= $d->status === 'SOLD' ? '<em>With customer</em>' : htmlspecialchars($d->location_name ?? '—') ?></td></tr>
          <tr><td class="text-muted">Current Status</td><td><span class="badge badge-dark"><?= htmlspecialchars($d->status) ?></span></td></tr>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-6 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-2 font-weight-bold">Warranty</div>
      <div class="card-body py-2">
        <?php if ($d->warranty_expiry_date): ?>
        <div class="alert alert-<?= $wclass ?> py-2 px-3">
            <strong><?= $warranty['status'] === 'ACTIVE' ? '✓ WARRANTY ACTIVE' : '⚠ WARRANTY EXPIRED' ?></strong><br>
            <?= htmlspecialchars($warranty['text']) ?>
        </div>
        <table class="table table-sm mb-2" style="font-size:.86rem;">
          <tr><td class="text-muted" style="width:45%;">Warranty Period</td><td><?= (int)($d->sold_warranty_days ?: $d->warranty_days) ?> Days</td></tr>
          <tr><td class="text-muted">Warranty Start</td><td><?= $fmt($d->warranty_start_date) ?></td></tr>
          <tr><td class="text-muted">Warranty Expiry</td><td class="font-weight-bold"><?= $fmt($d->warranty_expiry_date) ?></td></tr>
        </table>
        <?php else: ?>
        <p class="text-muted mb-2">No warranty recorded for this unit.</p>
        <?php endif; ?>
        <a href="<?= base_url('warranty/claim_create?serial='.urlencode($d->serial_no)) ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-tools mr-1"></i> Create Warranty Claim
        </a>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($claims)): ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white py-2 font-weight-bold">Claims on this unit</div>
  <div class="table-responsive"><table class="table table-sm mb-0" style="font-size:.85rem;">
    <thead class="thead-light"><tr><th>Claim</th><th>Date</th><th>Status</th><th>Problem</th><th></th></tr></thead>
    <tbody><?php foreach ($claims as $cl): ?>
      <tr><td><?= htmlspecialchars($cl->claim_no) ?></td><td><?= $fmt($cl->claim_date) ?></td>
          <td><span class="badge badge-info"><?= htmlspecialchars($cl->status) ?></span></td>
          <td><?= htmlspecialchars(mb_substr((string)$cl->problem_description, 0, 60)) ?></td>
          <td><a href="<?= base_url('warranty/claim/'.$cl->id) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Open</a></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white py-2 font-weight-bold">Movement History</div>
  <div class="table-responsive"><table class="table table-sm mb-0" style="font-size:.85rem;">
    <thead class="thead-light"><tr><th>Date</th><th>Transaction</th><th>Reference</th><th>From</th><th>To</th><th>Party</th><th>Status</th><th>User</th><th>Remarks</th></tr></thead>
    <tbody>
    <?php if (empty($history)): ?>
      <tr><td colspan="9" class="text-center text-muted py-3">No movements recorded.</td></tr>
    <?php else: foreach ($history as $h): ?>
      <tr>
        <td class="text-nowrap"><?= date('d-M-Y H:i', strtotime($h->txn_date)) ?></td>
        <td><span class="badge badge-secondary"><?= htmlspecialchars($h->txn_type) ?></span></td>
        <td><?= htmlspecialchars($h->reference_no ?? '—') ?></td>
        <td><?= htmlspecialchars($h->from_location ?? '—') ?></td>
        <td><?= htmlspecialchars($h->to_location ?? '—') ?></td>
        <td><?= htmlspecialchars($h->customer_name ?? $h->supplier_name ?? '—') ?></td>
        <td><small><?= htmlspecialchars(($h->old_status ?? '—').' → '.($h->new_status ?? '—')) ?></small></td>
        <td><?= htmlspecialchars($h->user_name ?? '—') ?></td>
        <td><small><?= htmlspecialchars($h->remarks ?? '') ?></small></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table></div>
</div>

<?php elseif ($q !== ''): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <?php if (empty($rows)): ?>
      <p class="text-muted mb-0">Nothing found for "<strong><?= htmlspecialchars($q) ?></strong>".</p>
    <?php else: ?>
      <div class="table-responsive"><table class="table table-sm table-hover mb-0" style="font-size:.85rem;">
        <thead class="thead-light"><tr><th>Serial</th><th>Item</th><th>Invoice</th><th>Sold</th><th>Customer</th><th>Phone</th><th>Warranty Till</th><th></th></tr></thead>
        <tbody><?php foreach ($rows as $r): ?>
          <tr>
            <td style="font-family:monospace;"><?= htmlspecialchars($r->serial_no ?? '—') ?></td>
            <td><?= htmlspecialchars($r->product_name ?? '') ?></td>
            <td><?= htmlspecialchars($r->invoice_no ?? '') ?></td>
            <td><?= $fmt($r->sale_date ?? null) ?></td>
            <td><?= htmlspecialchars($r->customer_name ?? '') ?></td>
            <td><?= htmlspecialchars($r->phone ?? '') ?></td>
            <td><?= $fmt($r->warranty_expiry_date ?? null) ?></td>
            <td><?php if (!empty($r->serial_no)): ?><a class="btn btn-sm btn-outline-primary py-0 px-2"
                 href="<?= base_url('warranty/search?q='.urlencode($r->serial_no)) ?>">View</a><?php endif; ?></td>
          </tr>
        <?php endforeach; ?></tbody>
      </table></div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
