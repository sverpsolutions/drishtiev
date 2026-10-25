<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php $view = $view ?? 'summary'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-undo mr-1 text-warning"></i> Refunds & Returns Report<?= $view==='details' ? ' — Item Details' : '' ?></h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Refunds</small>
        <?php
        $badges = $filter_summary ?? [];
        if (!empty($badges)): ?>
        <div class="mt-1"><span class="badge badge-dark" style="font-size:.75rem;"><?= htmlspecialchars(implode(' | ', $badges)) ?></span></div>
        <?php endif; ?>
    </div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/refunds_report','export_params'=>$export_params??'','export_title'=>$export_title??'Refunds Report']); ?>
    </div>
</div>

<!-- Summary / Details toggle -->
<div class="btn-group mb-3 no-print" role="group">
    <a href="<?= base_url('reports/refunds_report?from='.$date_from.'&to='.$date_to.'&location_id='.($location_id??'').'&view=summary') ?>"
       class="btn btn-sm <?= $view==='summary' ? 'btn-dark' : 'btn-outline-dark' ?>">Summary</a>
    <a href="<?= base_url('reports/refunds_report?from='.$date_from.'&to='.$date_to.'&location_id='.($location_id??'').'&view=details') ?>"
       class="btn btn-sm <?= $view==='details' ? 'btn-dark' : 'btn-outline-dark' ?>">Details</a>
</div>

<!-- Date / outlet filter -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="form-inline flex-wrap">
            <input type="hidden" name="view" value="<?= $view ?>">
            <label class="mr-2" style="font-size:.85rem;">From</label>
            <input type="date" name="from" class="form-control form-control-sm mr-2" value="<?= $date_from ?>">
            <label class="mr-2" style="font-size:.85rem;">To</label>
            <input type="date" name="to" class="form-control form-control-sm mr-2" value="<?= $date_to ?>">
            <select name="location_id" class="form-control form-control-sm mr-2" style="min-width:180px; max-width:220px; font-size:.8rem; text-overflow:ellipsis;">
                <?php if (count($locations ?? []) > 1): ?>
                <option value="">All Outlets</option>
                <?php endif; ?>
                <?php foreach ($locations ?? [] as $loc): ?>
                <option value="<?= $loc->id ?>" <?= ($location_id ?? 0) == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-dark btn-sm">Apply</button>
        </form>
    </div>
</div>

<?php if (!empty($location_id)): ?>
<div class="alert alert-info py-2 mb-3 no-print" style="font-size:.85rem;">
    <i class="fas fa-store mr-1"></i> Showing data for outlet: <strong><?= htmlspecialchars(array_reduce((array)($locations??[]), fn($c,$l) => $l->id==$location_id ? $l->name : $c, 'Unknown')) ?></strong>
</div>
<?php endif; ?>

<?php if ($view === 'details'): ?>
    <?php include APPPATH . 'views/reports/_refund_item_details.php'; ?>
<?php else: ?>

<?php
$total_refunded = array_sum(array_map(fn($r) => (float)$r->refund_amount, $refunds));
$by_mode = [];
foreach ($refunds as $r) {
    $by_mode[$r->refund_mode] = ($by_mode[$r->refund_mode] ?? 0) + (float)$r->refund_amount;
}
?>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="small text-muted">Total Refunds</div>
            <h4 class="font-weight-bold text-warning mb-0"><?= count($refunds) ?></h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="small text-muted">Total Refund Amount</div>
            <h4 class="font-weight-bold text-danger mb-0">&#8377; <?= number_format($total_refunded, 2) ?></h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm py-2 px-3" style="font-size:.83rem;">
            <div class="small font-weight-bold mb-1">By Mode:</div>
            <?php foreach ($by_mode as $mode => $amt): ?>
            <div class="d-flex justify-content-between"><span><?= strtoupper($mode) ?></span><span class="font-weight-bold text-danger">&#8377; <?= number_format($amt, 2) ?></span></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.83rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Refund #</th>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th class="text-right">Amount</th>
                        <th>Mode</th>
                        <th>Reason</th>
                        <th>By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($refunds)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No refunds in this period.</td></tr>
                <?php else: ?>
                <?php foreach ($refunds as $r): ?>
                <tr>
                    <td><a href="<?= base_url('refunds/view/'.$r->id) ?>"><code><?= $r->refund_no ?></code></a></td>
                    <td><?= $r->invoice_no ?></td>
                    <td>
                        <div><?= htmlspecialchars($r->customer_name ?? '—') ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($r->customer_phone ?? '') ?></div>
                    </td>
                    <td><span class="badge badge-<?= $r->refund_type === 'full' ? 'danger' : 'warning' ?>"><?= ucfirst($r->refund_type) ?></span></td>
                    <td class="text-right font-weight-bold text-danger">&#8377; <?= number_format($r->refund_amount, 2) ?></td>
                    <td><?= strtoupper($r->refund_mode) ?></td>
                    <td><?= htmlspecialchars(substr($r->reason, 0, 50)) ?></td>
                    <td><?= htmlspecialchars($r->created_by_name) ?></td>
                    <td><?= date('d M Y', strtotime($r->created_at)) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($refunds)): ?>
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td colspan="4" class="text-right">Total:</td>
                        <td class="text-right text-danger">&#8377; <?= number_format($total_refunded, 2) ?></td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>
