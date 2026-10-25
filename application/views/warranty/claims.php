<?php defined('BASEPATH') OR exit('No direct script access allowed');
$fmt = function ($d) { return $d ? date('d-M-Y', strtotime($d)) : '—'; };
$badge = ['RECEIVED'=>'secondary','UNDER_INSPECTION'=>'warning','APPROVED'=>'success','REJECTED'=>'danger',
          'REPAIR'=>'warning','REPLACEMENT'=>'info','READY'=>'primary','DELIVERED'=>'primary','CLOSED'=>'dark'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-tools text-primary mr-1"></i> Warranty Claims</h5>
    <div class="d-flex" style="gap:6px;">
        <a href="<?= base_url('warranty') ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
        <a href="<?= base_url('warranty/claim_create') ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Claim</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body py-2">
<form method="get" class="form-inline" style="gap:8px;">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Claim / serial / customer / invoice" value="<?= htmlspecialchars($f['q'] ?? '') ?>">
    <select name="status" class="form-control form-control-sm">
        <option value="">All statuses</option>
        <?php foreach (array_keys($badge) as $s): ?>
        <option value="<?= $s ?>" <?= ($f['status'] ?? '') === $s ? 'selected' : '' ?>><?= str_replace('_',' ',$s) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($f['from'] ?? '') ?>">
    <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($f['to'] ?? '') ?>">
    <button class="btn btn-sm btn-primary">Filter</button>
    <a href="<?= base_url('warranty/claims') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
</form>
</div></div>

<div class="card border-0 shadow-sm"><div class="table-responsive">
<table class="table table-hover mb-0" style="font-size:.86rem;">
<thead class="thead-dark"><tr>
    <th>Claim No</th><th>Date</th><th>Serial</th><th>Item</th><th>Customer</th><th>Invoice</th><th>Status</th><th>Assigned</th><th></th>
</tr></thead>
<tbody>
<?php if (empty($claims)): ?>
    <tr><td colspan="9" class="text-center text-muted py-4">No warranty claims found.</td></tr>
<?php else: foreach ($claims as $c): ?>
    <tr>
        <td class="font-weight-bold"><?= htmlspecialchars($c->claim_no) ?></td>
        <td><?= $fmt($c->claim_date) ?></td>
        <td style="font-family:monospace;"><?= htmlspecialchars($c->serial_no ?? '—') ?></td>
        <td><?= htmlspecialchars($c->product_name ?? '—') ?></td>
        <td><?= htmlspecialchars($c->customer_name ?? '—') ?></td>
        <td><?= htmlspecialchars($c->invoice_no ?? '—') ?></td>
        <td><span class="badge badge-<?= $badge[$c->status] ?? 'secondary' ?>"><?= str_replace('_',' ',$c->status) ?></span></td>
        <td><?= htmlspecialchars($c->assigned_name ?? '—') ?></td>
        <td><a href="<?= base_url('warranty/claim/'.$c->id) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Open</a></td>
    </tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div></div>
