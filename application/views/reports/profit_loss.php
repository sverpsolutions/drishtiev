<?php
// ============================================================
// FILE: application/views/reports/profit_loss.php
// ============================================================
$view = $view ?? 'summary';
$item_filters = $item_filters ?? [];
$tabs = [
    'summary'  => 'Summary',
    'item'     => 'Item-wise',
    'category' => 'Category-wise',
    'brand'    => 'Brand-wise',
    'supplier' => 'Supplier-wise',
];
// Build a query string that carries every current filter forward, for tab links etc.
$carry_params = array_filter(array_merge(['from'=>$from,'to'=>$to], $item_filters));
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Profit &amp; Loss</h5>
        <small class="text-muted"><a href="<?= base_url('reports') ?>">Reports</a> / P&amp;L</small>
        <?php if (!empty($filter_summary)): ?>
        <div class="mt-1"><span class="badge badge-dark" style="font-size:.75rem;"><?= htmlspecialchars(implode(' | ', $filter_summary)) ?></span></div>
        <?php endif; ?>
    </div>
    <div class="d-flex no-print" style="gap:5px;">
        <?php $this->load->view('reports/_export_bar', ['export_route'=>$export_route??'reports/profit_loss','export_params'=>$export_params??'','export_title'=>$export_title??'Profit & Loss']); ?>
    </div>
</div>

<!-- Tabs -->
<div class="btn-group mb-3 no-print flex-wrap" role="group">
    <?php foreach ($tabs as $key => $label):
        $qs = http_build_query(array_merge($carry_params, ['view' => $key]));
    ?>
    <a href="<?= base_url('reports/profit_loss?'.$qs) ?>" class="btn btn-sm <?= $view===$key ? 'btn-dark' : 'btn-outline-dark' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<!-- Filter bar -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end g-2">
            <input type="hidden" name="view" value="<?= $view ?>">
            <div class="col-md-2">
                <label class="small mb-0">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Location</label>
                <select name="location_id" class="form-control form-control-sm" style="min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem; text-overflow:ellipsis;">
                    <?php if (count($locations ?? []) > 1): ?>
                    <option value="">All Locations</option>
                    <?php endif; ?>
                    <?php foreach ($locations ?? [] as $loc): ?>
                    <option value="<?= $loc->id ?>" <?= ($location_id ?? 0) == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($view !== 'summary'): ?>
            <div class="col-md-2">
                <label class="small mb-0">Item Name</label>
                <input type="text" name="item_name" class="form-control form-control-sm" placeholder="e.g. Limca" value="<?= htmlspecialchars($item_filters['item_name'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Item Code</label>
                <input type="text" name="item_code" class="form-control form-control-sm" value="<?= htmlspecialchars($item_filters['item_code'] ?? '') ?>">
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
            <?php endif; ?>
            <div class="col-md-2">
                <button type="submit" class="btn btn-dark btn-sm btn-block">Apply</button>
            </div>
        </form>
    </div>
</div>

<!-- Net Profit calculator (manual — this system doesn't track operating expenses) -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Net Profit Calculator</div>
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-md-4 mb-2 mb-md-0">
                <div class="text-muted small">Gross Profit (<?= $tabs[$view] ?>)</div>
                <div class="h5 font-weight-bold mb-0 <?= ($gross_profit_total ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">₹ <span id="grossProfitVal"><?= number_format($gross_profit_total ?? 0, 2) ?></span></div>
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <label class="small mb-0">Other Expenses for this period (₹)</label>
                <input type="number" step="0.01" id="expensesInput" class="form-control form-control-sm" placeholder="Rent, salaries, electricity, etc." value="0" oninput="calcNetProfit()">
                <small class="text-muted">Typed here only — not saved. Enter total operating expenses to see Net Profit.</small>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Net Profit</div>
                <div class="h5 font-weight-bold mb-0" id="netProfitVal">₹ <?= number_format($gross_profit_total ?? 0, 2) ?></div>
            </div>
        </div>
    </div>
</div>
<script>
const grossProfitBase = <?= json_encode((float)($gross_profit_total ?? 0)) ?>;
function calcNetProfit() {
    const exp = parseFloat(document.getElementById('expensesInput').value) || 0;
    const net = grossProfitBase - exp;
    const el = document.getElementById('netProfitVal');
    el.textContent = '₹ ' + net.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    el.className = 'h5 font-weight-bold mb-0 ' + (net >= 0 ? 'text-success' : 'text-danger');
}
</script>

<style>
/* Bootstrap's bg-primary/bg-success + text-white utilities don't reliably win against
   this admin theme's table CSS (same issue fixed earlier on the Reports index banner) —
   dedicated classes with !important on both properties instead. */
.pl-row-primary, .pl-row-primary td, .pl-row-primary th { background-color: #1B3A6B !important; color: #ffffff !important; }
.pl-row-success, .pl-row-success td, .pl-row-success th { background-color: #1e7e34 !important; color: #ffffff !important; }
.pl-row-primary small, .pl-row-success small { color: #ffffff !important; }
</style>

<?php if ($view === 'summary'): ?>

<div class="row">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Income Statement <small class="text-muted font-weight-normal">(excl. GST)</small></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.88rem;">
                    <tr>
                        <td class="pl-3">Gross Sales</td>
                        <td class="text-right pr-3">₹ <?= number_format($pl['gross_sales'],2) ?></td>
                    </tr>
                    <?php if ($pl['sales_return_value'] > 0): ?>
                    <tr>
                        <td class="pl-3 text-muted">Less: Sales Returns</td>
                        <td class="text-right pr-3 text-danger">- ₹ <?= number_format($pl['sales_return_value'],2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($pl['direct_refunds'] > 0): ?>
                    <tr>
                        <td class="pl-3 text-muted">Less: Other Refunds</td>
                        <td class="text-right pr-3 text-danger">- ₹ <?= number_format($pl['direct_refunds'],2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="pl-row-success">
                        <td class="pl-3 font-weight-bold">Net Revenue</td>
                        <td class="text-right pr-3 font-weight-bold">₹ <?= number_format($pl['revenue'],2) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3 text-muted">Less: Cost of Goods Sold (COGS)</td>
                        <td class="text-right pr-3 text-danger">- ₹ <?= number_format($pl['cogs'],2) ?></td>
                    </tr>
                    <tr class="font-weight-bold <?= $pl['gross_profit'] >= 0 ? 'text-success':'text-danger' ?>">
                        <td class="pl-3">Gross Profit</td>
                        <td class="text-right pr-3">₹ <?= number_format($pl['gross_profit'],2) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3 text-muted">Gross Margin</td>
                        <td class="text-right pr-3"><?= $pl['gross_margin'] ?>%</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">GST Position</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.88rem;">
                    <tr>
                        <td class="pl-3">GST Collected (Output Tax)</td>
                        <td class="text-right pr-3 text-success font-weight-bold">₹ <?= number_format($pl['gst_collected'],2) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3">GST Paid on Purchases (Input Tax)</td>
                        <td class="text-right pr-3 text-danger">- ₹ <?= number_format($pl['gst_paid'],2) ?></td>
                    </tr>
                    <tr class="font-weight-bold <?= $pl['gst_payable'] >= 0 ? 'text-danger':'text-success' ?>">
                        <td class="pl-3">Net GST Payable to Govt</td>
                        <td class="text-right pr-3">₹ <?= number_format($pl['gst_payable'],2) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3 text-muted" colspan="2" style="font-size:.78rem;">
                            Note: Negative = Input Tax Credit available
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Sales Volume &amp; Value</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.88rem;">
                    <tr class="pl-row-primary">
                        <td class="pl-3 font-weight-bold">Total Sales Value <small style="opacity:.85;">(incl. GST)</small></td>
                        <td class="text-right pr-3 font-weight-bold">₹ <?= number_format($pl['total_sales_value'],2) ?></td>
                    </tr>
                    <?php if ($pl['sales_return_value_incl_gst'] > 0 || $pl['direct_refunds'] > 0): ?>
                    <tr>
                        <td class="pl-3 text-muted">Less: Returns &amp; Refunds</td>
                        <td class="text-right pr-3 text-danger">- ₹ <?= number_format($pl['sales_return_value_incl_gst'] + $pl['direct_refunds'],2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="pl-row-success">
                        <td class="pl-3 font-weight-bold">Net Sales <small style="opacity:.85;">(incl. GST)</small></td>
                        <td class="text-right pr-3 font-weight-bold">₹ <?= number_format($pl['net_sales_value'],2) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3">Total Bills</td>
                        <td class="text-right pr-3"><?= number_format($pl['bill_count'],0) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3">Average Bill Value <small class="text-muted">(excl. GST)</small></td>
                        <td class="text-right pr-3">₹ <?= number_format($pl['avg_bill_value'],2) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3">Total Quantity Sold</td>
                        <td class="text-right pr-3"><?= number_format($pl['qty_sold'],0) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3">Total Discount Given</td>
                        <td class="text-right pr-3 text-danger">₹ <?= number_format($pl['discount_given'],2) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">Payment Split <small class="text-muted font-weight-normal">(incl. GST)</small></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.88rem;">
                    <tr>
                        <td class="pl-3">Cash Sales</td>
                        <td class="text-right pr-3">₹ <?= number_format($pl['cash_sales'],2) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3">Credit Sales</td>
                        <td class="text-right pr-3">₹ <?= number_format($pl['credit_sales'],2) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3">Total Customers Billed</td>
                        <td class="text-right pr-3"><?= number_format($pl['total_customers'],0) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3">New Customers</td>
                        <td class="text-right pr-3"><?= number_format($pl['new_customers'],0) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php elseif ($view === 'item'): ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.82rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Item Code</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Supplier</th>
                        <th class="text-right">Qty Sold</th>
                        <th class="text-right">Qty Returned</th>
                        <th class="text-right">Net Qty</th>
                        <th class="text-right">Revenue (excl. GST)</th>
                        <th class="text-right">COGS</th>
                        <th class="text-right pl-row-primary">Gross Profit</th>
                        <th class="text-right">Margin %</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $t_qty_sold=0; $t_qty_ret=0; $t_net_qty=0; $t_rev=0; $t_cogs=0; $t_gp=0;
                if (!empty($items)): foreach ($items as $i => $r):
                    $t_qty_sold += $r->qty_sold; $t_qty_ret += $r->qty_returned; $t_net_qty += $r->net_qty;
                    $t_rev += $r->revenue; $t_cogs += $r->cogs; $t_gp += $r->gross_profit;
                ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td class="font-weight-bold"><?= htmlspecialchars($r->name) ?></td>
                        <td><code><?= htmlspecialchars($r->item_code ?? '—') ?></code></td>
                        <td><?= htmlspecialchars($r->category_name ?? '—') ?></td>
                        <td><?= htmlspecialchars($r->brand_name ?? '—') ?></td>
                        <td><?= htmlspecialchars($r->supplier_name ?? '—') ?></td>
                        <td class="text-right"><?= number_format($r->qty_sold, 0) ?></td>
                        <td class="text-right text-danger"><?= $r->qty_returned > 0 ? '-'.number_format($r->qty_returned,0) : '0' ?></td>
                        <td class="text-right font-weight-bold"><?= number_format($r->net_qty, 0) ?></td>
                        <td class="text-right">₹ <?= number_format($r->revenue, 2) ?></td>
                        <td class="text-right text-muted">₹ <?= number_format($r->cogs, 2) ?></td>
                        <td class="text-right font-weight-bold <?= $r->gross_profit>=0?'text-success':'text-danger' ?>">₹ <?= number_format($r->gross_profit, 2) ?></td>
                        <td class="text-right"><?= $r->margin_pct ?>%</td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="13" class="text-center text-muted py-4">No sales found for this filter/period.</td></tr>
                <?php endif; ?>
                </tbody>
                <?php if (!empty($items)): ?>
                <tfoot>
                    <tr class="bg-light font-weight-bold">
                        <td colspan="6" class="text-right">Totals:</td>
                        <td class="text-right"><?= number_format($t_qty_sold,0) ?></td>
                        <td class="text-right text-danger"><?= $t_qty_ret>0?'-'.number_format($t_qty_ret,0):'0' ?></td>
                        <td class="text-right"><?= number_format($t_net_qty,0) ?></td>
                        <td class="text-right">₹ <?= number_format($t_rev,2) ?></td>
                        <td class="text-right">₹ <?= number_format($t_cogs,2) ?></td>
                        <td class="text-right <?= $t_gp>=0?'text-success':'text-danger' ?>">₹ <?= number_format($t_gp,2) ?></td>
                        <td class="text-right"><?= $t_rev>0 ? round(($t_gp/$t_rev)*100,2) : 0 ?>%</td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php else: // category / brand / supplier ?>

<?php
$param_key = ['category'=>'category_id','brand'=>'brand_id','supplier'=>'supplier_id'][$view];
$drill_base = array_filter(array_merge($carry_params, ['view'=>'item']));
?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-dark text-white py-2"><strong><?= $tabs[$view] ?> Profit &amp; Loss</strong></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.83rem;">
                <thead class="thead-light">
                    <tr>
                        <th><?= ucfirst($view) ?></th>
                        <th class="text-right"># Products</th>
                        <th class="text-right">Qty Sold</th>
                        <th class="text-right">Net Qty</th>
                        <th class="text-right">Revenue (excl. GST)</th>
                        <th class="text-right">COGS</th>
                        <th class="text-right pl-row-primary">Gross Profit</th>
                        <th class="text-right">Margin %</th>
                        <th class="text-center no-print">Details</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($groups)):
                    $g_count=0;$g_qty=0;$g_net=0;$g_rev=0;$g_cogs=0;$g_gp=0;
                    foreach ($groups as $g):
                        $g_count+=$g->count; $g_qty+=$g->qty_sold; $g_net+=$g->net_qty; $g_rev+=$g->revenue; $g_cogs+=$g->cogs; $g_gp+=$g->gross_profit;
                ?>
                    <tr>
                        <td><?= htmlspecialchars($g->name) ?></td>
                        <td class="text-right"><?= $g->count ?></td>
                        <td class="text-right"><?= number_format($g->qty_sold,0) ?></td>
                        <td class="text-right"><?= number_format($g->net_qty,0) ?></td>
                        <td class="text-right">₹ <?= number_format($g->revenue,2) ?></td>
                        <td class="text-right text-muted">₹ <?= number_format($g->cogs,2) ?></td>
                        <td class="text-right font-weight-bold <?= $g->gross_profit>=0?'text-success':'text-danger' ?>">₹ <?= number_format($g->gross_profit,2) ?></td>
                        <td class="text-right"><?= $g->margin_pct ?>%</td>
                        <td class="text-center no-print">
                            <?php if ($g->id): $qs = http_build_query(array_merge($drill_base, [$param_key => $g->id])); ?>
                            <a href="<?= base_url('reports/profit_loss?'.$qs) ?>" class="btn btn-outline-dark btn-sm py-0">View</a>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                    <tr class="bg-light font-weight-bold">
                        <td class="text-right">TOTAL</td>
                        <td class="text-right"><?= $g_count ?></td>
                        <td class="text-right"><?= number_format($g_qty,0) ?></td>
                        <td class="text-right"><?= number_format($g_net,0) ?></td>
                        <td class="text-right">₹ <?= number_format($g_rev,2) ?></td>
                        <td class="text-right">₹ <?= number_format($g_cogs,2) ?></td>
                        <td class="text-right <?= $g_gp>=0?'text-success':'text-danger' ?>">₹ <?= number_format($g_gp,2) ?></td>
                        <td class="text-right"><?= $g_rev>0 ? round(($g_gp/$g_rev)*100,2) : 0 ?>%</td>
                        <td class="no-print"></td>
                    </tr>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No sales found for this filter/period.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>

<style>@media print { .no-print { display:none; } }</style>
