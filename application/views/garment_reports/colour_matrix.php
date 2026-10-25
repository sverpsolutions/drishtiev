<?php
// ============================================================
// FILE: application/views/garment_reports/colour_matrix.php
// Colour-wise Sales & Stock Matrix
// ============================================================
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold text-dark"><i class="fas fa-palette text-info mr-2"></i>Colour-wise Sales & Stock Analysis</h5>
        <small class="text-muted">Analyze sales performance and inventory distribution across fabric colour variations</small>
    </div>
    <div>
        <?php
        $csv_params = http_build_query(array_merge($filters, ['export' => 'csv']));
        ?>
        <a href="<?= base_url('garmentreports/colour_matrix?' . $csv_params) ?>" class="btn btn-outline-success btn-sm mr-1">
            <i class="fas fa-file-excel mr-1"></i> Export CSV
        </a>
        <a href="<?= base_url('garmentreports') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Reports Hub
        </a>
    </div>
</div>

<!-- Filter card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="get" action="<?= base_url('garmentreports/colour_matrix') ?>" class="form-row align-items-center">
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="small text-muted font-weight-bold mb-0">From Date</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['from']) ?>">
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="small text-muted font-weight-bold mb-0">To Date</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['to']) ?>">
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <label class="small text-muted font-weight-bold mb-0">Location / Outlet</label>
                <select name="location_id" class="form-control form-control-sm">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?= (int)$loc->id ?>" <?= ($filters['location_id'] == $loc->id) ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 text-right pt-3">
                <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter mr-1"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" style="font-size: 0.88rem;">
                <thead class="thead-dark">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Colour Name</th>
                        <th class="text-center">Active Variants</th>
                        <th class="text-center">Live Stock (pcs)</th>
                        <th class="text-center">Units Sold</th>
                        <th class="text-right">Sales Revenue (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($colours_data)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No colour data available.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $tot_stk = 0; 
                        $tot_sold = 0; 
                        $tot_val = 0;
                        foreach ($colours_data as $i => $c): 
                            $tot_stk  += (float)$c->current_stock;
                            $tot_sold += (float)$c->sold_qty;
                            $tot_val  += (float)$c->sold_val;
                        ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="font-weight-bold text-dark">
                                    <?= htmlspecialchars($c->colour_name ?: 'Standard') ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light border"><?= (int)$c->total_variants ?></span>
                                </td>
                                <td class="text-center font-weight-bold text-primary">
                                    <?= number_format((float)$c->current_stock, 0) ?>
                                </td>
                                <td class="text-center font-weight-bold <?= $c->sold_qty > 0 ? 'text-success' : 'text-muted' ?>">
                                    <?= number_format((float)$c->sold_qty, 0) ?>
                                </td>
                                <td class="text-right font-weight-bold text-dark">
                                    ₹<?= number_format((float)$c->sold_val, 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-dark font-weight-bold">
                            <td colspan="3" class="text-right">TOTAL:</td>
                            <td class="text-center text-info"><?= number_format($tot_stk, 0) ?> pcs</td>
                            <td class="text-center text-warning"><?= number_format($tot_sold, 0) ?> pcs</td>
                            <td class="text-right text-success" style="font-size: 1rem;">₹<?= number_format($tot_val, 2) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
