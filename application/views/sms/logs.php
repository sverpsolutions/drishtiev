<?php defined('BASEPATH') OR exit('No direct script access allowed');
$badge = ['sent' => 'success', 'failed' => 'danger', 'test' => 'info', 'skipped' => 'secondary'];
$qs = http_build_query(array_filter($f, function ($v) { return $v !== null && $v !== ''; }));
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-list text-primary mr-1"></i> SMS Send Log</h5>
        <small class="text-muted"><?= date('d-M-Y', strtotime($f['from'])) ?> to <?= date('d-M-Y', strtotime($f['to'])) ?></small>
    </div>
    <div class="d-flex" style="gap:6px;">
        <a href="<?= base_url('smsmanager/logs') ?>?<?= $qs ?>&export=1" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel mr-1"></i> Export Excel</a>
        <a href="<?= base_url('smsmanager') ?>" class="btn btn-outline-secondary btn-sm">SMS Setup</a>
    </div>
</div>

<div class="row">
    <?php foreach ([['Total', $tot->total, 'dark'], ['Sent', $tot->sent, 'success'],
                    ['Failed', $tot->failed, 'danger'], ['Tests', $tot->tests, 'info'],
                    ['Skipped', $tot->skipped, 'secondary']] as $k): ?>
    <div class="col mb-3">
        <div class="card border-0 shadow-sm h-100"><div class="card-body py-3 text-center">
            <div class="text-muted" style="font-size:.75rem;"><?= $k[0] ?></div>
            <div class="font-weight-bold text-<?= $k[2] ?>" style="font-size:1.6rem;"><?= number_format((int)$k[1]) ?></div>
        </div></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body py-2">
<form method="get" class="form-inline" style="gap:8px;">
    <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($f['from']) ?>">
    <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($f['to']) ?>">
    <select name="purpose" class="form-control form-control-sm">
        <option value="">All purposes</option>
        <?php foreach ($purposes as $p): ?>
        <option value="<?= htmlspecialchars($p->purpose) ?>" <?= $f['purpose'] === $p->purpose ? 'selected' : '' ?>>
            <?= htmlspecialchars($p->label) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="form-control form-control-sm">
        <option value="">All statuses</option>
        <?php foreach (['sent', 'failed', 'test', 'skipped'] as $s): ?>
        <option value="<?= $s ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Mobile / reference" value="<?= htmlspecialchars($f['q'] ?? '') ?>">
    <button class="btn btn-sm btn-primary">Filter</button>
    <a href="<?= base_url('smsmanager/logs') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
</form>
</div></div>

<div class="card border-0 shadow-sm"><div class="table-responsive">
<table class="table table-hover table-sm mb-0" style="font-size:.84rem;">
    <thead class="thead-dark"><tr>
        <th>Date/Time</th><th>Purpose</th><th>Mobile</th><th>Status</th>
        <th>Message</th><th>Reference</th><th>Response</th><th>By</th>
    </tr></thead>
    <tbody>
    <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No messages in this period.</td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
            <td class="text-nowrap"><?= date('d-M-y H:i', strtotime($r->created_at)) ?></td>
            <td><code style="font-size:.72rem;"><?= htmlspecialchars($r->purpose) ?></code></td>
            <td><?= htmlspecialchars($r->mobile) ?></td>
            <td><span class="badge badge-<?= $badge[$r->status] ?? 'secondary' ?>"><?= strtoupper($r->status) ?></span>
                <?php if ($r->http_code): ?><small class="text-muted d-block"><?= (int)$r->http_code ?></small><?php endif; ?></td>
            <td style="max-width:280px;"><small><?= htmlspecialchars(mb_substr((string)$r->message, 0, 110)) ?></small></td>
            <td><?= htmlspecialchars($r->reference_no ?? '—') ?></td>
            <td style="max-width:220px;">
                <?php if ($r->error): ?>
                    <small class="text-danger"><?= htmlspecialchars(mb_substr($r->error, 0, 90)) ?></small>
                <?php else: ?>
                    <small class="text-muted"><?= htmlspecialchars(mb_substr((string)$r->response, 0, 90)) ?></small>
                <?php endif; ?>
            </td>
            <td><small><?= htmlspecialchars($r->user_name ?? '—') ?></small></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div></div>
<?php if (count($rows) >= 1000): ?>
<div class="text-muted mt-2" style="font-size:.8rem;">Showing the most recent 1000 — narrow the date range for more.</div>
<?php endif; ?>
