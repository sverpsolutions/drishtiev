<?php
// ============================================================
// FILE: application/views/reports/_sales_item_details.php
// Item-level breakdown for the Sales Report "Details" view.
// Included from sales.php — shares its variable scope.
// ============================================================
$item_filters = $item_filters ?? [];
?>
<!-- Item filter bar -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end g-2">
            <input type="hidden" name="view" value="details">
            <input type="hidden" name="from" value="<?= $from ?>">
            <input type="hidden" name="to" value="<?= $to ?>">
            <input type="hidden" name="location_id" value="<?= $location_id ?? '' ?>">

            <div class="col-md-2">
                <label class="small mb-0">Item Name</label>
                <input type="text" name="item_name" class="form-control form-control-sm" placeholder="e.g. Thums Up" value="<?= htmlspecialchars($item_filters['item_name'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Item Code / EAN</label>
                <input type="text" name="item_code" class="form-control form-control-sm" placeholder="Code / barcode" value="<?= htmlspecialchars($item_filters['item_code'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Category</label>
                <select name="category_id" class="form-control form-control-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories ?? [] as $c): ?>
                    <option value="<?= $c->id ?>" <?= ($item_filters['category_id'] ?? '')==$c->id?'selected':'' ?>><?= htmlspecialchars($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Brand</label>
                <select name="brand_id" class="form-control form-control-sm">
                    <option value="">All Brands</option>
                    <?php foreach ($brands ?? [] as $b): ?>
                    <option value="<?= $b->id ?>" <?= ($item_filters['brand_id'] ?? '')==$b->id?'selected':'' ?>><?= htmlspecialchars($b->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Supplier</label>
                <select name="supplier_id" class="form-control form-control-sm">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers ?? [] as $s): ?>
                    <option value="<?= $s->id ?>" <?= ($item_filters['supplier_id'] ?? '')==$s->id?'selected':'' ?>><?= htmlspecialchars($s->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <label class="small mb-0">MRP</label>
                <input type="number" step="0.01" name="mrp" class="form-control form-control-sm" placeholder="₹" value="<?= htmlspecialchars($item_filters['mrp'] ?? '') ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-dark btn-sm btn-block">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.83rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Outlet</th>
                        <th>Item Name</th>
                        <th>Item Code</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Supplier</th>
                        <th class="text-right">MRP</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Rate</th>
                        <th class="text-right">Taxable</th>
                        <th class="text-right">GST</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($item_rows)): ?>
                    <?php
                    $t_qty = 0; $t_taxable = 0; $t_gst = 0; $t_total = 0;
                    foreach ($item_rows as $r):
                        $t_qty += $r->qty; $t_taxable += $r->taxable_amt; $t_gst += $r->gst_amount; $t_total += $r->total;
                    ?>
                    <tr>
                        <td><a href="<?= base_url('billing/invoice/'.$r->invoice_id) ?>"><?= $r->invoice_no ?></a></td>
                        <td><?= date('d M Y', strtotime($r->invoice_date)) ?></td>
                        <td><?= htmlspecialchars($r->location_name ?? '—') ?></td>
                        <td><?= htmlspecialchars($r->product_name) ?></td>
                        <td><?= htmlspecialchars($r->item_code ?? '—') ?></td>
                        <td><?= htmlspecialchars($r->category_name ?? '—') ?></td>
                        <td><?= htmlspecialchars($r->brand_name ?? '—') ?></td>
                        <td><?= htmlspecialchars($r->supplier_name ?? '—') ?></td>
                        <td class="text-right">₹ <?= number_format($r->mrp,2) ?></td>
                        <td class="text-right"><?= $r->qty ?> <?= htmlspecialchars($r->unit) ?></td>
                        <td class="text-right">₹ <?= number_format($r->rate,2) ?></td>
                        <td class="text-right">₹ <?= number_format($r->taxable_amt,2) ?></td>
                        <td class="text-right">₹ <?= number_format($r->gst_amount,2) ?></td>
                        <td class="text-right font-weight-bold">₹ <?= number_format($r->total,2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="14" class="text-center text-muted py-4">No items match this filter.</td></tr>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($item_rows)): ?>
                <tfoot>
                    <tr style="background:#fef3c7;font-weight:700;">
                        <td colspan="9" class="text-right">TOTAL</td>
                        <td class="text-right"><?= $t_qty ?></td>
                        <td></td>
                        <td class="text-right">₹ <?= number_format($t_taxable,2) ?></td>
                        <td class="text-right">₹ <?= number_format($t_gst,2) ?></td>
                        <td class="text-right">₹ <?= number_format($t_total,2) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
