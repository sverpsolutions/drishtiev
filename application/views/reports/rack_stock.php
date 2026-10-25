<style>
.rack-summary-card { border-left: 4px solid #4e73df; }
.rack-summary-card:hover { background: #f8f9fc; }
.rack-hdr { background: #2c3e50; color: #fff; padding: 8px 12px; border-radius: 4px 4px 0 0; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-th mr-2"></i>Rack-wise Stock Report</h5>
    <div>
        <?php if (!empty($export_route)): ?>
        <a href="<?= base_url($export_route) ?>?export=excel&<?= $export_params ?>" class="btn btn-outline-success btn-sm mr-1"><i class="fas fa-file-excel mr-1"></i>Export Excel</a>
        <?php endif; ?>
    </div>
</div>

<!-- Rack Filter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="form-inline">
            <label class="mr-2 font-weight-bold small">Filter Rack:</label>
            <select name="rack_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                <option value="">All Racks</option>
                <?php foreach ($racks as $r): ?>
                <option value="<?= $r->id ?>" <?= $selected_rack == $r->id ? 'selected' : '' ?>><?= htmlspecialchars($r->rack_code . ' — ' . $r->rack_name) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($selected_rack): ?>
            <a href="<?= base_url('reports/rack_stock_report') ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
            <?php endif; ?>
            <span class="ml-auto text-muted small"><i class="fas fa-exclamation-circle mr-1"></i><?= $unassigned_count ?> products have no rack assigned</span>
        </form>
    </div>
</div>

<!-- Rack Summary Cards -->
<?php if (empty($selected_rack)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-2 font-weight-bold"><i class="fas fa-chart-bar mr-1"></i> Rack Summary</div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Rack Code</th>
                    <th>Rack Name</th>
                    <th>Location</th>
                    <th class="text-center">Items</th>
                    <th class="text-right">Total Qty</th>
                    <th class="text-right">Net Value (₹)</th>
                    <th class="text-right">MRP Value (₹)</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $grand_items = 0; $grand_qty = 0; $grand_net = 0; $grand_mrp = 0;
                foreach ($rack_summary as $rs):
                    $grand_items += $rs->item_count;
                    $grand_qty += $rs->total_qty;
                    $grand_net += $rs->net_value;
                    $grand_mrp += $rs->mrp_value;
                ?>
                <tr>
                    <td><span class="badge badge-secondary font-weight-bold"><?= htmlspecialchars($rs->rack_code) ?></span></td>
                    <td><?= htmlspecialchars($rs->rack_name) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($rs->location_name) ?></td>
                    <td class="text-center"><?= $rs->item_count ?></td>
                    <td class="text-right"><?= number_format($rs->total_qty) ?></td>
                    <td class="text-right">₹ <?= number_format($rs->net_value, 2) ?></td>
                    <td class="text-right">₹ <?= number_format($rs->mrp_value, 2) ?></td>
                    <td class="text-center">
                        <a href="<?= base_url('reports/rack_stock_report?rack_id=' . $rs->id) ?>" class="btn btn-outline-primary btn-sm py-0">View</a>
                        <a href="<?= base_url('masters/rack_qr/' . $rs->id) ?>" target="_blank" class="btn btn-outline-dark btn-sm py-0"><i class="fas fa-qrcode"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td colspan="3">TOTAL</td>
                    <td class="text-center"><?= $grand_items ?></td>
                    <td class="text-right"><?= number_format($grand_qty) ?></td>
                    <td class="text-right">₹ <?= number_format($grand_net, 2) ?></td>
                    <td class="text-right">₹ <?= number_format($grand_mrp, 2) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Product List per Rack -->
<?php foreach ($products as $rack_id => $grp):
    $rack = $grp['rack'];
    $items = $grp['items'];
    $r_qty = 0; $r_net = 0; $r_mrp = 0;
    foreach ($items as $it) { $r_qty += $it->stock_qty; $r_net += $it->net_value; $r_mrp += $it->mrp_value; }
?>
<div class="card border-0 shadow-sm mb-3">
    <div class="rack-hdr d-flex justify-content-between align-items-center">
        <div>
            <strong style="font-size:1.1rem;"><?= htmlspecialchars($rack->rack_code) ?></strong>
            <span class="ml-2" style="opacity:.8;"><?= htmlspecialchars($rack->rack_name) ?></span>
            <?php if (!empty($rack->location_name)): ?>
            <span class="ml-2 badge badge-light"><?= htmlspecialchars($rack->location_name) ?></span>
            <?php endif; ?>
        </div>
        <div>
            <span class="badge badge-light"><?= count($items) ?> items</span>
            <span class="badge badge-warning ml-1">MRP ₹<?= number_format($r_mrp, 2) ?></span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($items)): ?>
        <div class="text-center text-muted py-3">No products assigned to this rack.</div>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-hover table-sm mb-0" style="font-size:.85rem;">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Item Code</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th class="text-right">Basic Cost</th>
                    <th class="text-right">MRP</th>
                    <th class="text-right">SP</th>
                    <th class="text-center">Stock</th>
                    <th>Unit</th>
                    <th class="text-right">Net Value</th>
                    <th class="text-right">MRP Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $k => $p): ?>
                <tr>
                    <td><?= $k + 1 ?></td>
                    <td><code><?= htmlspecialchars($p->item_code) ?></code></td>
                    <td><?= htmlspecialchars($p->name) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($p->category_name ?? '-') ?></td>
                    <td class="text-muted"><?= htmlspecialchars($p->brand_name ?? '-') ?></td>
                    <td class="text-right"><?= number_format($p->basic_cost, 2) ?></td>
                    <td class="text-right"><?= number_format($p->mrp, 2) ?></td>
                    <td class="text-right"><?= number_format($p->selling_price, 2) ?></td>
                    <td class="text-center font-weight-bold <?= $p->stock_qty <= 0 ? 'text-danger' : '' ?>"><?= $p->stock_qty ?></td>
                    <td><?= htmlspecialchars($p->unit ?? 'PCS') ?></td>
                    <td class="text-right">₹ <?= number_format($p->net_value, 2) ?></td>
                    <td class="text-right">₹ <?= number_format($p->mrp_value, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td colspan="8" class="text-right">Rack Total:</td>
                    <td class="text-center"><?= number_format($r_qty) ?></td>
                    <td></td>
                    <td class="text-right">₹ <?= number_format($r_net, 2) ?></td>
                    <td class="text-right">₹ <?= number_format($r_mrp, 2) ?></td>
                </tr>
            </tfoot>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
