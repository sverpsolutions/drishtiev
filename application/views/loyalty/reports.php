<?php defined('BASEPATH') OR exit('No direct script access allowed');
// Generic renderer: report_meta() supplies the column map, so adding a report
// is a model change only — no new view.
$W = $this->Loyalty_model;
$qs = function ($extra = []) {
    return http_build_query(array_filter(array_merge([
        'from' => $this->input->get('from', TRUE), 'to' => $this->input->get('to', TRUE),
        'days' => $this->input->get('days', TRUE), 'q' => $this->input->get('q', TRUE),
    ], $extra), function ($v) { return $v !== null && $v !== ''; }));
};
$date_cols = ['created_at','earned_on','expiry_date','invoice_date','updated_at'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-chart-bar text-primary mr-1"></i>
            <?= htmlspecialchars($meta ? $meta[0] : 'Loyalty Reports') ?></h5>
        <?php if ($meta): ?><small class="text-muted"><?= count($rows) ?> row(s)<?= count($rows) >= 1000 ? ' — showing the first 1000, narrow the filter for more' : '' ?></small><?php endif; ?>
    </div>
    <div class="d-flex" style="gap:6px;">
        <a href="<?= base_url('loyalty') ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
        <?php if ($meta): ?>
        <a href="<?= base_url('loyalty/report_export/' . $type) ?>?<?= $qs() ?>" class="btn btn-success btn-sm">
            <i class="fas fa-file-csv mr-1"></i> Export CSV</a>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body py-2">
    <?php foreach ($groups as $group => $types): ?>
    <div class="mb-1">
        <span class="text-muted mr-2" style="font-size:.75rem;"><?= htmlspecialchars($group) ?>:</span>
        <?php foreach ($types as $t): $m = $W->report_meta($t); if (!$m) continue; ?>
            <a href="<?= base_url('loyalty/reports/' . $t) ?>"
               class="btn btn-sm <?= $type === $t ? 'btn-primary' : 'btn-outline-secondary' ?> mb-1"
               style="font-size:.76rem;"><?= htmlspecialchars($m[0]) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
</div></div>

<?php if ($meta): ?>
<div class="card border-0 shadow-sm mb-3"><div class="card-body py-2">
<form method="get" class="form-inline" style="gap:8px;">
    <?php if ($type === 'expiring'): ?>
        <label class="mr-1" style="font-size:.85rem;">Expiring within</label>
        <?php foreach ([7, 15, 30, 60] as $d): ?>
            <a href="<?= base_url('loyalty/reports/expiring?days=' . $d) ?>"
               class="btn btn-sm <?= (int)($f['days'] ?: 30) === $d ? 'btn-primary' : 'btn-outline-primary' ?> mr-1">
               Next <?= $d ?> Days</a>
        <?php endforeach; ?>
        <span class="mx-2 text-muted">or</span>
    <?php endif; ?>
    <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($f['from'] ?? '') ?>" title="From">
    <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($f['to'] ?? '') ?>" title="To">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search" value="<?= htmlspecialchars($f['q'] ?? '') ?>">
    <button class="btn btn-sm btn-primary">Apply</button>
    <a href="<?= base_url('loyalty/reports/' . $type) ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
</form>
</div></div>

<div class="card border-0 shadow-sm"><div class="table-responsive">
<table class="table table-hover table-sm mb-0" style="font-size:.84rem;">
    <thead class="thead-dark"><tr>
        <?php foreach ($meta[1] as $label): ?><th class="text-nowrap"><?= htmlspecialchars($label) ?></th><?php endforeach; ?>
    </tr></thead>
    <tbody>
    <?php if (empty($rows)): ?>
        <tr><td colspan="<?= count($meta[1]) ?>" class="text-center text-muted py-4">No rows match this filter.</td></tr>
    <?php else: foreach ($rows as $r): ?>
        <tr>
        <?php foreach (array_keys($meta[1]) as $col):
            $v = $r->$col ?? null; ?>
            <td class="text-nowrap<?= '' ?>"
                <?= in_array($col, ['phone','loyalty_card_no'], true) ? 'style="font-family:monospace;"' : '' ?>>
            <?php
            if ($v === null || $v === '') { echo '—'; }
            elseif ($col === 'days_left') {
                $n = (int)$v;
                echo $n >= 0
                    ? '<span class="badge badge-' . ($n <= 30 ? 'danger' : 'success') . '">' . $n . ' days</span>'
                    : '<span class="badge badge-secondary">' . abs($n) . ' days ago</span>';
            }
            elseif ($col === 'status' || $col === 'txn_type') { echo '<span class="badge badge-secondary">' . htmlspecialchars(str_replace('_', ' ', $v)) . '</span>'; }
            elseif (in_array($col, $date_cols, true)) { echo date($col === 'txn_date' ? 'd-M-Y H:i' : 'd-M-Y', strtotime($v)); }
            else { echo htmlspecialchars((string)$v); }
            ?>
            </td>
        <?php endforeach; ?>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
</div></div>
<?php else: ?>
<div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5">
    Pick a report above.
</div></div>
<?php endif; ?>
