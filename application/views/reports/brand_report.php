<?php // FILE: application/views/reports/brand_report.php ?>
<?php
$base_qp = 'from='.$from.'&to='.$to.($sel_brand?'&brand='.urlencode($sel_brand):'');
?>
<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <div>
        <h5 class="font-weight-bold mb-0"><i class="fas fa-trademark text-warning mr-1"></i> Brand Report</h5>
        <small class="text-muted"><?= date('d M Y', strtotime($from)) ?> to <?= date('d M Y', strtotime($to)) ?><?= $sel_brand ? ' — <strong>'.htmlspecialchars($sel_brand).'</strong>' : ' — All Brands' ?></small>
    </div>
    <div class="d-flex" style="gap:6px;">
        <a href="<?= base_url('reports/brand_report?'.$base_qp.'&export=excel') ?>" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel mr-1"></i> Excel
        </a>
        <a href="<?= base_url('reports/brand_report?'.$base_qp.'&export=pdf') ?>" class="btn btn-danger btn-sm">
            <i class="fas fa-file-pdf mr-1"></i> PDF
        </a>
        <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#emailModal">
            <i class="fas fa-envelope mr-1"></i> Email
        </button>
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="fas fa-print mr-1"></i> Print
        </button>
    </div>
</div>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-2">
                <h6 class="modal-title mb-0"><i class="fas fa-envelope mr-1"></i> Email Brand Report</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <?= form_open('reports/brand_report?'.$base_qp.'&export=email', ['method'=>'post']) ?>
            <div class="modal-body">
                <div class="form-group mb-2">
                    <label class="small font-weight-bold">Send to Email</label>
                    <input type="email" name="email_to" class="form-control form-control-sm" placeholder="name@example.com" required>
                </div>
                <small class="text-muted">Report will be sent as a CSV attachment.</small>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-paper-plane mr-1"></i> Send</button>
            </div>
            <?= form_close() ?>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-3 d-print-none">
    <div class="card-body py-2">
        <form method="get" class="form-row align-items-end">
            <div class="form-group col-auto mb-0">
                <label class="small font-weight-bold mb-1">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
            </div>
            <div class="form-group col-auto mb-0">
                <label class="small font-weight-bold mb-1">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
            </div>
            <div class="form-group col-md-3 mb-0">
                <label class="small font-weight-bold mb-1">Brand <span class="text-muted font-weight-normal">(for details)</span></label>
                <select name="brand" class="form-control form-control-sm">
                    <option value="">— All Brands (Summary only) —</option>
                    <?php foreach ($brand_names as $bn): ?>
                    <option value="<?= htmlspecialchars($bn) ?>" <?= $sel_brand===$bn?'selected':'' ?>><?= htmlspecialchars($bn) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-auto mb-0">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i>Filter</button>
                <?php if ($sel_brand): ?>
                <a href="<?= base_url('reports/brand_report?from='.$from.'&to='.$to) ?>" class="btn btn-outline-secondary btn-sm ml-1">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- SUMMARY TABLE (always shown) -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-dark text-white py-2 font-weight-bold" style="font-size:.85rem;">
        <i class="fas fa-chart-bar mr-1"></i> Brand Summary — All Brands
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered mb-0" style="font-size:.83rem;">
                <thead class="thead-dark">
                    <tr>
                        <th class="pl-3">#</th>
                        <th>Brand</th>
                        <th class="text-right">Qty Sold</th>
                        <th class="text-right">Sales Value</th>
                        <th class="text-right">Gross Profit</th>
                        <th class="text-right">Margin %</th>
                        <th class="text-right">Contribution</th>
                        <th class="text-right">Stock Qty</th>
                        <th class="text-right">Stock Value</th>
                        <th class="text-right">SKUs</th>
                        <th class="text-center d-print-none">Details</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i=1; foreach ($brands as $r):
                    $margin = $r->total_sales > 0 ? round($r->gross_profit/$r->total_sales*100,1) : 0;
                    $is_sel = $sel_brand === $r->brand_name;
                ?>
                <tr class="<?= $is_sel ? 'table-warning font-weight-bold' : '' ?>">
                    <td class="pl-3"><?= $i++ ?></td>
                    <td class="font-weight-bold"><?= htmlspecialchars($r->brand_name) ?></td>
                    <td class="text-right"><?= number_format($r->total_qty,0) ?></td>
                    <td class="text-right font-weight-bold">₹<?= number_format($r->total_sales,0) ?></td>
                    <td class="text-right <?= $r->gross_profit>=0?'text-success':'text-danger' ?> font-weight-bold">₹<?= number_format($r->gross_profit,0) ?></td>
                    <td class="text-right"><?= $margin ?>%</td>
                    <td class="text-right">
                        <?= $r->contribution_pct ?>%
                        <div class="progress mt-1" style="height:3px;"><div class="progress-bar bg-warning" style="width:<?= min(100,$r->contribution_pct??0) ?>%"></div></div>
                    </td>
                    <td class="text-right"><?= number_format($r->current_stock??0,0) ?></td>
                    <td class="text-right text-warning">₹<?= number_format($r->stock_value??0,0) ?></td>
                    <td class="text-right text-muted"><?= $r->product_count??0 ?></td>
                    <td class="text-center d-print-none">
                        <a href="<?= base_url('reports/brand_report?from='.$from.'&to='.$to.'&brand='.urlencode($r->brand_name)) ?>"
                           class="btn btn-xs btn-<?= $is_sel?'warning':'outline-primary' ?> py-0 px-2" style="font-size:.75rem;">
                           <?= $is_sel ? '▲ Shown' : '🔍 Detail' ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($brands)): ?>
                <tr><td colspan="11" class="text-center text-muted py-4">No sales in this period</td></tr>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($brands)):
                    $ts = array_sum(array_column((array)$brands,'total_sales'));
                    $tp = array_sum(array_column((array)$brands,'gross_profit'));
                    $tq = array_sum(array_column((array)$brands,'total_qty'));
                    $tsq = array_sum(array_map(fn($b)=>$b->current_stock??0,(array)$brands));
                    $tsv = array_sum(array_map(fn($b)=>$b->stock_value??0,(array)$brands));
                ?>
                <tfoot class="font-weight-bold" style="background:#222;color:#fff;">
                    <tr>
                        <td colspan="2" class="pl-3">TOTAL</td>
                        <td class="text-right"><?= number_format($tq,0) ?></td>
                        <td class="text-right">₹<?= number_format($ts,0) ?></td>
                        <td class="text-right text-success">₹<?= number_format($tp,0) ?></td>
                        <td class="text-right"><?= $ts>0?number_format($tp/$ts*100,1):0 ?>%</td>
                        <td class="text-right">100%</td>
                        <td class="text-right"><?= number_format($tsq,0) ?></td>
                        <td class="text-right text-warning">₹<?= number_format($tsv,0) ?></td>
                        <td></td><td class="d-print-none"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php if ($sel_brand && !empty($detail_products)): ?>
<!-- PRODUCT-LEVEL DETAIL for selected brand -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#1a4a6e;color:#fff;">
        <span class="font-weight-bold" style="font-size:.88rem;"><i class="fas fa-boxes mr-1"></i> Product-wise Breakdown — <?= htmlspecialchars($sel_brand) ?></span>
        <span class="small"><?= count($detail_products) ?> products</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr>
                        <th class="pl-3">#</th>
                        <th>Product</th>
                        <th>Item Code</th>
                        <th class="text-right">Qty Sold</th>
                        <th class="text-right">Sales</th>
                        <th class="text-right">Cost</th>
                        <th class="text-right">Profit</th>
                        <th class="text-right">Margin%</th>
                        <th class="text-right">Stock</th>
                    </tr>
                </thead>
                <tbody>
                <?php $pi=1; foreach ($detail_products as $p):
                    $pm = $p->total_sales>0 ? round($p->gross_profit/$p->total_sales*100,1) : 0;
                ?>
                <tr>
                    <td class="pl-3"><?= $pi++ ?></td>
                    <td class="font-weight-bold"><?= htmlspecialchars($p->product_name) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($p->item_code??'—') ?></td>
                    <td class="text-right"><?= number_format($p->total_qty,0) ?></td>
                    <td class="text-right font-weight-bold">₹<?= number_format($p->total_sales,0) ?></td>
                    <td class="text-right text-muted">₹<?= number_format($p->total_cost,0) ?></td>
                    <td class="text-right <?= $p->gross_profit>=0?'text-success':'text-danger' ?> font-weight-bold">₹<?= number_format($p->gross_profit,0) ?></td>
                    <td class="text-right"><?= $pm ?>%</td>
                    <td class="text-right"><?= number_format($p->current_stock??0,0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="font-weight-bold bg-light">
                    <tr>
                        <td colspan="3" class="pl-3">TOTAL</td>
                        <td class="text-right"><?= number_format(array_sum(array_column((array)$detail_products,'total_qty')),0) ?></td>
                        <td class="text-right">₹<?= number_format(array_sum(array_column((array)$detail_products,'total_sales')),0) ?></td>
                        <td class="text-right">₹<?= number_format(array_sum(array_column((array)$detail_products,'total_cost')),0) ?></td>
                        <td class="text-right text-success">₹<?= number_format(array_sum(array_column((array)$detail_products,'gross_profit')),0) ?></td>
                        <td></td><td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- BILL-LEVEL DETAIL -->
<div class="card border-0 shadow-sm">
    <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#2d5a27;color:#fff;">
        <span class="font-weight-bold" style="font-size:.88rem;"><i class="fas fa-receipt mr-1"></i> Bill-wise Detail — <?= htmlspecialchars($sel_brand) ?></span>
        <span class="small"><?= count($detail_items) ?> line items across <?= count(array_unique(array_column((array)$detail_items,'invoice_no'))) ?> bills</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.81rem;">
                <thead class="thead-dark">
                    <tr>
                        <th class="pl-3">Bill No</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Item Code</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Rate</th>
                        <th class="text-right">GST%</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $prev_inv = '';
                $bill_total = 0;
                $grand_total = 0;
                foreach ($detail_items as $idx => $d):
                    $is_new_bill = $d->invoice_no !== $prev_inv;
                    // Print subtotal row before new bill
                    if ($is_new_bill && $prev_inv !== '') {
                        echo '<tr class="table-secondary"><td colspan="8" class="text-right pl-3 small font-weight-bold">Bill Total — '.$prev_inv.'</td><td class="text-right font-weight-bold">₹'.number_format($bill_total,2).'</td></tr>';
                        $bill_total = 0;
                    }
                    $bill_total += $d->total;
                    $grand_total += $d->total;
                    $prev_inv = $d->invoice_no;
                ?>
                <tr <?= $is_new_bill ? 'class="border-top-2"' : '' ?>>
                    <td class="pl-3 font-weight-bold <?= $is_new_bill?'text-primary':'' ?>"><?= $is_new_bill ? '<a href="'.base_url('billing/view/'.htmlspecialchars($d->invoice_no)).'" target="_blank">'.htmlspecialchars($d->invoice_no).'</a>' : '' ?></td>
                    <td><?= $is_new_bill ? date('d-M-Y', strtotime($d->inv_date)) : '' ?></td>
                    <td class="<?= $is_new_bill?'font-weight-bold':'' ?>"><?= $is_new_bill ? htmlspecialchars($d->customer_name??'Walk-in') : '' ?></td>
                    <td><?= htmlspecialchars($d->product_name) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($d->item_code??'—') ?></td>
                    <td class="text-right"><?= $d->qty ?></td>
                    <td class="text-right">₹<?= number_format($d->rate,2) ?></td>
                    <td class="text-right text-muted"><?= $d->gst_percent ?>%</td>
                    <td class="text-right font-weight-bold">₹<?= number_format($d->total,2) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if ($prev_inv): ?>
                <tr class="table-secondary"><td colspan="8" class="text-right pl-3 small font-weight-bold">Bill Total — <?= $prev_inv ?></td><td class="text-right font-weight-bold">₹<?= number_format($bill_total,2) ?></td></tr>
                <?php endif; ?>
                <?php if (empty($detail_items)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No items found</td></tr>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($detail_items)): ?>
                <tfoot style="background:#2d5a27;color:#fff;" class="font-weight-bold">
                    <tr>
                        <td colspan="8" class="pl-3">GRAND TOTAL — <?= htmlspecialchars($sel_brand) ?></td>
                        <td class="text-right">₹<?= number_format($grand_total,2) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php elseif ($sel_brand): ?>
<div class="alert alert-info">No sales found for <strong><?= htmlspecialchars($sel_brand) ?></strong> in this period.</div>
<?php else: ?>
<div class="alert alert-light border text-muted text-center py-3" style="font-size:.88rem;">
    <i class="fas fa-mouse-pointer mr-1"></i> Click <strong>🔍 Detail</strong> on any brand above, or select a brand from the dropdown to see bill-wise and product-wise breakdowns.
</div>
<?php endif; ?>

<style>
@media print {
    .d-print-none { display:none!important; }
    .card { border:1px solid #dee2e6!important; box-shadow:none!important; margin-bottom:12px!important; }
    .table { font-size:9px!important; }
}
.border-top-2 { border-top:2px solid #dee2e6!important; }
</style>
