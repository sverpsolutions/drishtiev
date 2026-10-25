<?php // FILE: application/views/reports/closing_stock.php ?>
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h5 class="font-weight-bold mb-0">Closing Stock Summary Report</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / Closing Stock Summary</small>
    </div>
    <div class="d-flex" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/closing_stock_summary','export_params'=>$export_params??'','export_title'=>$export_title??'Closing Stock Summary']); ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end g-2">
            <div class="col-md-2">
                <label class="small mb-0">From Date</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['from_date'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">To Date</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['to_date'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Location</label>
                <?php $this->load->view('reports/_location_filter'); ?>
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Department</label>
                <select name="department_id" class="form-control form-control-sm">
                    <option value="">All Departments</option>
                    <?php foreach ($departments ?? [] as $d): ?>
                    <option value="<?= $d->id ?>" <?= ($filters['department_id'] ?? '')==$d->id?'selected':'' ?>><?= htmlspecialchars($d->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Category</label>
                <select name="category_id" class="form-control form-control-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories ?? [] as $c): ?>
                    <option value="<?= $c->id ?>" <?= ($filters['category_id'] ?? '')==$c->id?'selected':'' ?>><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Brand</label>
                <select name="brand_id" class="form-control form-control-sm">
                    <option value="">All Brands</option>
                    <?php foreach ($brands ?? [] as $b): ?>
                    <option value="<?= $b->id ?>" <?= ($filters['brand_id'] ?? '')==$b->id?'selected':'' ?>><?= htmlspecialchars($b->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mt-2">
                <label class="small mb-0">Supplier</label>
                <select name="supplier_id" class="form-control form-control-sm">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers ?? [] as $s): ?>
                    <option value="<?= $s->id ?>" <?= ($filters['supplier_id'] ?? '')==$s->id?'selected':'' ?>><?= htmlspecialchars($s->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mt-2">
                <label class="small mb-0">Item Code / Name</label>
                <input type="text" name="item_code" class="form-control form-control-sm" placeholder="e.g. 1002" value="<?= htmlspecialchars($filters['item_code'] ?? '') ?>">
            </div>
            <div class="col-md-2 mt-2">
                <label class="small mb-0">Status</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="1" <?= ($filters['status'] ?? '1')==='1'?'selected':'' ?>>Active Items</option>
                    <option value="0" <?= ($filters['status'] ?? '1')==='0'?'selected':'' ?>>Inactive Items</option>
                    <option value="" <?= ($filters['status'] ?? '1')===''?'selected':'' ?>>All Items</option>
                </select>
            </div>
            <div class="col-md-2 mt-2">
                <label class="small mb-0">Zero Stock</label>
                <select name="show_zero_stock" class="form-control form-control-sm">
                    <option value="1" <?= ($filters['show_zero_stock'] ?? '1')==='1'?'selected':'' ?>>Show All (Inc. Zero)</option>
                    <option value="0" <?= ($filters['show_zero_stock'] ?? '1')==='0'?'selected':'' ?>>Hide Zero Stock</option>
                </select>
            </div>
            <div class="col-md-2 mt-2">
                <button type="submit" class="btn btn-dark btn-sm btn-block">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-3 no-print">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="text-muted small">Total Basic Value</div>
            <div class="h5 font-weight-bold text-primary mb-0">₹ <?= number_format($totals['basic'] ?? 0, 2) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="text-muted small">Total CP Value</div>
            <div class="h5 font-weight-bold text-success mb-0">₹ <?= number_format($totals['cp'] ?? 0, 2) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="text-muted small">Total MRP Value</div>
            <div class="h5 font-weight-bold text-warning mb-0">₹ <?= number_format($totals['mrp'] ?? 0, 2) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="text-muted small">Total GST Value</div>
            <div class="h5 font-weight-bold text-danger mb-0">₹ <?= number_format($totals['tax'] ?? 0, 2) ?></div>
        </div>
    </div>
</div>

<!-- Print Header -->
<div class="d-none d-print-block text-center mb-4">
    <h4><?= htmlspecialchars($this->session->userdata('company_name') ?: 'Closing Stock Summary Report') ?></h4>
    <h5><?= htmlspecialchars($title) ?></h5>
    <?php if(!empty($filters['location_id'])): ?>
        <p>Location ID: <?= $filters['location_id'] ?></p>
    <?php endif; ?>
    <p class="mb-0">Generated By: <?= htmlspecialchars($this->session->userdata('name')) ?> on <?= date('d-M-Y H:i A') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-bordered table-sm mb-0" id="closingStockTable" style="font-size: 0.85rem; white-space: nowrap;">
                <thead class="thead-light">
                    <tr>
                        <th>Sr</th>
                        <th>Item Code</th>
                        <th>Barcode</th>
                        <th>Item Name</th>
                        <th>UOM</th>
                        <th class="text-right">Opening Qty</th>
                        <th class="text-right text-success">Purchase</th>
                        <th class="text-right text-danger">Pur. Return</th>
                        <th class="text-right text-success">Transfer In</th>
                        <th class="text-right text-danger">Transfer Out</th>
                        <th class="text-right text-danger">Sales</th>
                        <th class="text-right text-success">Sales Return</th>
                        <th class="text-right">Stock Take</th>
                        <th class="text-right">Adjustment</th>
                        <th class="text-right font-weight-bold bg-light">Closing Qty</th>
                        <th class="text-right">Basic Cost</th>
                        <th class="text-right">CP Rate</th>
                        <th class="text-right">MRP</th>
                        <th class="text-right">Tax %</th>
                        <th class="text-right">Val (Basic)</th>
                        <th class="text-right">Val (CP)</th>
                        <th class="text-right">Val (MRP)</th>
                        <th class="text-right">Tax Val</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr><td colspan="23" class="text-center py-4 text-muted">No records found for the selected criteria.</td></tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($items as $r): ?>
                        <tr class="item-row" data-id="<?= $r->id ?>" title="Double click to view ledger">
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($r->item_code) ?></td>
                            <td><?= htmlspecialchars($r->barcode) ?></td>
                            <td><?= htmlspecialchars($r->item_name) ?></td>
                            <td><?= htmlspecialchars($r->uom) ?></td>
                            <td class="text-right"><?= round($r->opening_qty, 3) ?></td>
                            <td class="text-right text-success"><?= round($r->purchase_qty, 3) ?></td>
                            <td class="text-right text-danger"><?= round($r->purchase_return_qty, 3) ?></td>
                            <td class="text-right text-success"><?= round($r->transfer_in_qty, 3) ?></td>
                            <td class="text-right text-danger"><?= round($r->transfer_out_qty, 3) ?></td>
                            <td class="text-right text-danger"><?= round($r->sale_qty, 3) ?></td>
                            <td class="text-right text-success"><?= round($r->sale_return_qty, 3) ?></td>
                            <td class="text-right"><?= round($r->stock_take_qty, 3) ?></td>
                            <td class="text-right"><?= round($r->adjustment_qty, 3) ?></td>
                            <td class="text-right font-weight-bold bg-light"><?= round($r->closing_qty, 3) ?></td>
                            <td class="text-right"><?= number_format($r->basic_cost, 2) ?></td>
                            <td class="text-right"><?= number_format($r->cp_rate, 2) ?></td>
                            <td class="text-right"><?= number_format($r->mrp, 2) ?></td>
                            <td class="text-right"><?= $r->tax_percent ?>%</td>
                            <td class="text-right"><?= number_format($r->closing_qty * $r->basic_cost, 2) ?></td>
                            <td class="text-right"><?= number_format($r->closing_qty * $r->cp_rate, 2) ?></td>
                            <td class="text-right"><?= number_format($r->closing_qty * $r->mrp, 2) ?></td>
                            <td class="text-right"><?= number_format((($r->closing_qty * $r->cp_rate) * $r->tax_percent) / 100, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    @page { size: landscape; margin: 10mm; }
    body { font-size: 10pt; }
    .no-print { display: none !important; }
    .table th, .table td { padding: 4px; border: 1px solid #dee2e6 !important; }
    .card { border: none !important; }
    .table-responsive { overflow: visible !important; }
}
.item-row { cursor: pointer; }
.item-row:hover { background-color: #f1f3f5 !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#closingStockTable').DataTable({
            "paging": true,
            "pageLength": 100,
            "lengthMenu": [[50, 100, 500, -1], [50, 100, 500, "All"]],
            "ordering": true,
            "info": true,
            "searching": true,
            "order": [[3, "asc"]], // Order by Item Name initially
            "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            "columnDefs": [
                { "orderable": false, "targets": [0] }
            ]
        });
    }

    // Double click to view ledger
    $('.item-row').on('dblclick', function() {
        var pid = $(this).data('id');
        if(pid) {
            window.open('<?= base_url("inventory/item_ledger/") ?>' + pid, '_blank');
        }
    });
});
</script>
