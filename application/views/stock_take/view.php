<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<style>
@media print {
    .no-print { display: none !important; }
    .container-fluid { padding: 0 !important; }
}
.variance-pos  { color: #0d6efd; font-weight: 600; }
.variance-neg  { color: #dc3545; font-weight: 600; }
.variance-zero { color: #28a745; font-weight: 600; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="mb-0">
            <i class="fas fa-clipboard-check mr-1"></i>
            Stock Take — <?= htmlspecialchars($take->take_no) ?>
        </h4>
        <small class="text-muted">
            <i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($take->location_name) ?>
            &nbsp;|&nbsp;
            <?php
            switch ($take->status) {
                case 'in_progress': echo '<span class="badge badge-warning">In Progress</span>'; break;
                case 'completed':   echo '<span class="badge badge-success">Completed</span>'; break;
                case 'cancelled':   echo '<span class="badge badge-secondary">Cancelled</span>'; break;
            }
            ?>
        </small>
    </div>
    <div>
        <?php if ($take->status === 'in_progress'): ?>
        <a href="<?= base_url('stock_take/count/' . $take->id) ?>" class="btn btn-warning btn-sm mr-1">
            <i class="fas fa-play mr-1"></i> Continue Counting
        </a>
        <?php endif; ?>
        <a href="<?= base_url('stock_take/variance_report/' . $take->id) ?>" target="_blank"
           class="btn btn-danger btn-sm mr-1">
            <i class="fas fa-print mr-1"></i> Print Variance Report
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm mr-1">
            <i class="fas fa-print mr-1"></i> Print Full
        </button>
        <a href="<?= base_url('stock_take') ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>
</div>

<!-- Summary cards -->
<div class="row mb-4">
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center shadow-sm">
            <div class="card-body py-2">
                <h3 class="mb-0"><?= $total_items ?></h3>
                <small class="text-muted">Total Items</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center shadow-sm border-warning">
            <div class="card-body py-2">
                <h3 class="mb-0 text-warning"><?= $variance_items ?></h3>
                <small class="text-muted">Items with Variance</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center shadow-sm border-primary">
            <div class="card-body py-2">
                <h3 class="mb-0 text-primary"><?= $surplus_items ?></h3>
                <small class="text-muted">Surplus Items (+)</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="card text-center shadow-sm border-danger">
            <div class="card-body py-2">
                <h3 class="mb-0 text-danger"><?= $shortage_items ?></h3>
                <small class="text-muted">Shortage Items (−)</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6 mb-2">
        <div class="card shadow-sm">
            <div class="card-body py-2">
                <strong>Total Variance Value:</strong>
                <span class="<?= $total_variance_val >= 0 ? 'text-primary' : 'text-danger' ?> font-weight-bold float-right">
                    <?= ($total_variance_val >= 0 ? '+' : '') . number_format($total_variance_val, 2) ?>
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-2">
        <div class="card shadow-sm">
            <div class="card-body py-2">
                <strong>Started by:</strong> <?= htmlspecialchars($take->started_by_name) ?>
                &nbsp;|&nbsp;
                <strong>Date:</strong> <?= date('d M Y H:i', strtotime($take->started_at)) ?>
                <?php if ($take->finalized_by_name): ?>
                <br><strong>Finalized by:</strong> <?= htmlspecialchars($take->finalized_by_name) ?>
                &nbsp;|&nbsp;
                <?= date('d M Y H:i', strtotime($take->finalized_at)) ?>
                &nbsp;|&nbsp;
                Stock <?= $take->adjust_stock ? '<span class="badge badge-success">Adjusted</span>' : '<span class="badge badge-secondary">Not Adjusted</span>' ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($take->status === 'in_progress'): ?>
<!-- Finalize form -->
<div class="card border-success shadow-sm mb-4 no-print">
    <div class="card-header font-weight-bold text-success">
        <i class="fas fa-check-circle mr-1"></i> Finalize This Stock Take
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_url('stock_take/finalize/' . $take->id) ?>"
              onsubmit="return confirm('Finalize this stock take? This cannot be undone.')">
            <input type="hidden" name="<?= $csrf_name ?>" value="<?= $csrf_hash ?>">
            <div class="custom-control custom-checkbox mb-3">
                <input type="checkbox" class="custom-control-input" id="adjustStockView"
                       name="adjust_stock" value="1">
                <label class="custom-control-label font-weight-bold" for="adjustStockView">
                    Adjust system stock to match physical count
                </label>
                <small class="form-text text-muted">
                    When checked, system stock at <strong><?= htmlspecialchars($take->location_name) ?></strong>
                    will be updated for all counted items.
                </small>
            </div>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-check mr-1"></i> Finalize Stock Take
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Items table -->
<div class="card shadow-sm">
    <div class="card-header font-weight-bold">
        <i class="fas fa-table mr-1"></i> Item-wise Count Summary
        <?php if ($take->notes): ?>
        <span class="text-muted font-weight-normal ml-2">
            <i class="fas fa-sticky-note mr-1"></i><?= htmlspecialchars($take->notes) ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Item Code</th>
                        <th>Product Name</th>
                        <th>Unit</th>
                        <th class="text-right">System Qty</th>
                        <th class="text-right">Counted Qty</th>
                        <th class="text-right">Variance</th>
                        <th class="text-right">Value Impact</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $i => $item): ?>
                    <?php
                    $counted = ($item->counted_qty !== null);
                    $var     = $counted ? (float)$item->variance : null;
                    $val_impact = ($var !== null) ? ($var * (float)($item->cost_price ?? 0)) : null;
                    ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td><small><?= htmlspecialchars($item->item_code) ?></small></td>
                        <td><?= htmlspecialchars($item->product_name) ?></td>
                        <td><?= htmlspecialchars($item->unit) ?></td>
                        <td class="text-right"><?= number_format((float)$item->system_qty, 3) ?></td>
                        <td class="text-right">
                            <?= $counted ? number_format((float)$item->counted_qty, 3) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td class="text-right">
                            <?php if ($var === null): ?>
                            <span class="text-muted">—</span>
                            <?php elseif ($var > 0): ?>
                            <span class="variance-pos">+<?= number_format($var, 3) ?></span>
                            <?php elseif ($var < 0): ?>
                            <span class="variance-neg"><?= number_format($var, 3) ?></span>
                            <?php else: ?>
                            <span class="variance-zero">0.000</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <?php if ($val_impact === null): ?>
                            <span class="text-muted">—</span>
                            <?php else: ?>
                            <span class="<?= $val_impact >= 0 ? 'text-primary' : 'text-danger' ?>">
                                <?= ($val_impact >= 0 ? '+' : '') . number_format($val_impact, 2) ?>
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
