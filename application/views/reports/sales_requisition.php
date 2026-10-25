<?php // FILE: application/views/reports/sales_requisition.php
$status_badges = [
    'draft' => 'secondary', 'pending' => 'warning', 'approved' => 'info',
    'rejected' => 'danger', 'completed' => 'success',
];
?>
<div class="mb-3">
    <h5 class="font-weight-bold mb-0">Daily Sales Requisition Report</h5>
    <small class="text-muted">Transfers that used previous-day sales suggestions</small>
</div>

<form method="GET" class="mb-3">
    <div class="form-row align-items-end">
        <div class="form-group col-md-2 mb-2">
            <label class="small">From Date</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $filters['date_from'] ?>">
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small">To Date</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $filters['date_to'] ?>">
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small">Location</label>
            <select name="location_id" class="form-control form-control-sm">
                <option value="">All Locations</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc->id ?>" <?= (int)$filters['location_id'] === (int)$loc->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-2 mb-2">
            <label class="small">Status</label>
            <select name="status" class="form-control form-control-sm">
                <option value="">All</option>
                <?php foreach (['draft','pending','approved','completed'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-auto mb-2">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <a href="<?= base_url('reports/sales_requisition_report') ?>" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
        </div>
    </div>
</form>

<?php
$total_items      = count($rows);
$total_sales_qty  = 0;
$total_sugg_qty   = 0;
$total_final_qty  = 0;
$transfer_ids     = [];
foreach ($rows as $r) {
    $total_sales_qty += (float)$r->prev_day_sales_qty;
    $total_sugg_qty  += (float)$r->suggested_qty;
    $total_final_qty += (float)$r->requested_qty;
    $transfer_ids[$r->id] = true;
}
$total_transfers = count($transfer_ids);
$variance_pct = $total_sugg_qty > 0 ? round(($total_final_qty - $total_sugg_qty) / $total_sugg_qty * 100, 1) : 0;
?>
<div class="row mb-3">
    <div class="col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h5 mb-0"><?= $total_transfers ?></div>
            <small class="text-muted">Transfers</small>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h5 mb-0"><?= $total_items ?></div>
            <small class="text-muted">Line Items</small>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h5 mb-0 text-info"><?= number_format($total_sales_qty, 1) ?></div>
            <small class="text-muted">Prev Day Sales</small>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h5 mb-0"><?= number_format($total_sugg_qty, 1) ?></div>
            <small class="text-muted">Suggested Qty</small>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h5 mb-0 text-primary"><?= number_format($total_final_qty, 1) ?></div>
            <small class="text-muted">Final Qty</small>
        </div>
    </div>
    <div class="col-md-2 mb-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h5 mb-0 <?= $variance_pct > 0 ? 'text-success' : ($variance_pct < 0 ? 'text-danger' : '') ?>"><?= $variance_pct > 0 ? '+' : '' ?><?= $variance_pct ?>%</div>
            <small class="text-muted">Variance</small>
        </div>
    </div>
</div>

<?php if (empty($rows)): ?>
    <div class="alert alert-light border text-center py-4">No requisitions with sales suggestions found for this period.</div>
<?php else: ?>
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Transfer #</th>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Status</th>
                    <th>Item Code</th>
                    <th>Product</th>
                    <th>Unit</th>
                    <th class="text-right">Prev Day Sales</th>
                    <th class="text-right">Suggested</th>
                    <th class="text-right">Final Qty</th>
                    <th class="text-right">Approved</th>
                    <th class="text-right">Diff</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $prev_transfer = '';
            foreach ($rows as $r):
                $diff = (float)$r->requested_qty - (float)$r->suggested_qty;
                $show_transfer = ($r->transfer_no !== $prev_transfer);
                $prev_transfer = $r->transfer_no;
            ?>
                <tr<?= $show_transfer ? ' class="border-top"' : '' ?>>
                    <td>
                        <?php if ($show_transfer): ?>
                            <a href="<?= base_url('stock_transfers/view/'.$r->id) ?>"><?= htmlspecialchars($r->transfer_no) ?></a>
                        <?php endif; ?>
                    </td>
                    <td><?= $show_transfer ? date('d M Y', strtotime($r->created_at)) : '' ?></td>
                    <td><?= $show_transfer ? htmlspecialchars($r->from_location_name) : '' ?></td>
                    <td><?= $show_transfer ? htmlspecialchars($r->to_location_name) : '' ?></td>
                    <td>
                        <?php if ($show_transfer): ?>
                            <span class="badge badge-<?= $status_badges[$r->status] ?? 'secondary' ?>"><?= ucfirst($r->status) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><small><?= htmlspecialchars($r->item_code ?? '') ?></small></td>
                    <td><?= htmlspecialchars($r->product_name) ?></td>
                    <td><small><?= htmlspecialchars($r->unit) ?></small></td>
                    <td class="text-right text-info font-weight-bold"><?= number_format((float)$r->prev_day_sales_qty, 3) ?></td>
                    <td class="text-right"><?= number_format((float)$r->suggested_qty, 3) ?></td>
                    <td class="text-right font-weight-bold"><?= number_format((float)$r->requested_qty, 3) ?></td>
                    <td class="text-right"><?= $r->approved_qty !== null ? number_format((float)$r->approved_qty, 3) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-right <?= $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted') ?>">
                        <?= $diff > 0 ? '+' : '' ?><?= number_format($diff, 3) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot class="table-active">
                <tr>
                    <td colspan="8" class="text-right font-weight-bold">Totals:</td>
                    <td class="text-right font-weight-bold text-info"><?= number_format($total_sales_qty, 1) ?></td>
                    <td class="text-right font-weight-bold"><?= number_format($total_sugg_qty, 1) ?></td>
                    <td class="text-right font-weight-bold"><?= number_format($total_final_qty, 1) ?></td>
                    <td></td>
                    <td class="text-right font-weight-bold"><?= ($total_final_qty - $total_sugg_qty) > 0 ? '+' : '' ?><?= number_format($total_final_qty - $total_sugg_qty, 1) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>
