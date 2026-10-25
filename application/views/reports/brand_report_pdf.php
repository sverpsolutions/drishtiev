<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #222; }
h2 { font-size:13pt; margin:0 0 2px 0; }
.sub { font-size:8pt; color:#555; margin-bottom:8px; }
table { width:100%; border-collapse:collapse; margin-bottom:12px; }
th { background:#222; color:#fff; padding:5px 6px; font-size:8.5pt; text-align:left; }
th.r, td.r { text-align:right; }
td { padding:4px 6px; border-bottom:1px solid #e0e0e0; font-size:8.5pt; }
tr:nth-child(even) td { background:#f9f9f9; }
.section-header { background:#1a4a6e; color:#fff; padding:5px 8px; font-weight:bold; margin:10px 0 4px 0; font-size:9pt; }
.section-header2 { background:#2d5a27; color:#fff; padding:5px 8px; font-weight:bold; margin:10px 0 4px 0; font-size:9pt; }
.bill-sep td { background:#e8f0fe; font-weight:bold; }
</style>
</head>
<body>
<h2><?= htmlspecialchars($company['company_name'] ?? 'ERP') ?></h2>
<div class="sub"><?= htmlspecialchars($company['company_address'] ?? '') ?> | GSTIN: <?= htmlspecialchars($company['company_gstin'] ?? '') ?></div>
<h3 style="margin:0 0 2px 0;">Brand Sales &amp; Stock Report</h3>
<div class="sub">Period: <?= date('d M Y', strtotime($from)) ?> to <?= date('d M Y', strtotime($to)) ?><?= $sel_brand ? ' | Brand: <b>'.htmlspecialchars($sel_brand).'</b>' : '' ?> | Printed: <?= date('d M Y H:i') ?></div>

<!-- Summary -->
<table>
    <thead><tr>
        <th>#</th><th>Brand</th><th class="r">Qty Sold</th><th class="r">Sales</th>
        <th class="r">Profit</th><th class="r">Margin%</th><th class="r">Contribution%</th>
        <th class="r">Stock Qty</th><th class="r">Stock Value</th><th class="r">SKUs</th>
    </tr></thead>
    <tbody>
    <?php $i=1; foreach ($brands as $r):
        $margin = $r->total_sales>0 ? round($r->gross_profit/$r->total_sales*100,1) : 0;
    ?>
    <tr>
        <td><?= $i++ ?></td>
        <td><b><?= htmlspecialchars($r->brand_name) ?></b></td>
        <td class="r"><?= number_format($r->total_qty,0) ?></td>
        <td class="r">₹<?= number_format($r->total_sales,0) ?></td>
        <td class="r" style="color:<?= $r->gross_profit>=0?'green':'red' ?>">₹<?= number_format($r->gross_profit,0) ?></td>
        <td class="r"><?= $margin ?>%</td>
        <td class="r"><?= $r->contribution_pct ?>%</td>
        <td class="r"><?= number_format($r->current_stock??0,0) ?></td>
        <td class="r">₹<?= number_format($r->stock_value??0,0) ?></td>
        <td class="r"><?= $r->product_count??0 ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <?php
    $ts=array_sum(array_column((array)$brands,'total_sales'));
    $tp=array_sum(array_column((array)$brands,'gross_profit'));
    $tq=array_sum(array_column((array)$brands,'total_qty'));
    ?>
    <?php $tf = 'background:#222;color:#fff;font-weight:bold;padding:5px 6px;'; ?>
    <tfoot><tr>
        <td colspan="2" style="<?= $tf ?>">TOTAL</td>
        <td class="r" style="<?= $tf ?>"><?= number_format($tq,0) ?></td>
        <td class="r" style="<?= $tf ?>">₹<?= number_format($ts,0) ?></td>
        <td class="r" style="<?= $tf ?>">₹<?= number_format($tp,0) ?></td>
        <td class="r" style="<?= $tf ?>"><?= $ts>0?number_format($tp/$ts*100,1):0 ?>%</td>
        <td class="r" style="<?= $tf ?>">100%</td>
        <td class="r" style="<?= $tf ?>"><?= number_format(array_sum(array_map(fn($b)=>$b->current_stock??0,(array)$brands)),0) ?></td>
        <td class="r" style="<?= $tf ?>">₹<?= number_format(array_sum(array_map(fn($b)=>$b->stock_value??0,(array)$brands)),0) ?></td>
        <td style="<?= $tf ?>"></td>
    </tr></tfoot>
</table>

<?php if ($sel_brand && !empty($detail_products)): ?>
<div class="section-header">Product-wise Breakdown — <?= htmlspecialchars($sel_brand) ?></div>
<table>
    <thead><tr>
        <th>#</th><th>Product</th><th>Item Code</th>
        <th class="r">Qty</th><th class="r">Sales</th><th class="r">Cost</th><th class="r">Profit</th><th class="r">Margin%</th><th class="r">Stock</th>
    </tr></thead>
    <tbody>
    <?php $pi=1; foreach ($detail_products as $p):
        $pm = $p->total_sales>0?round($p->gross_profit/$p->total_sales*100,1):0;
    ?>
    <tr>
        <td><?= $pi++ ?></td>
        <td><b><?= htmlspecialchars($p->product_name) ?></b></td>
        <td><?= htmlspecialchars($p->item_code??'—') ?></td>
        <td class="r"><?= number_format($p->total_qty,0) ?></td>
        <td class="r">₹<?= number_format($p->total_sales,0) ?></td>
        <td class="r">₹<?= number_format($p->total_cost,0) ?></td>
        <td class="r" style="color:<?= $p->gross_profit>=0?'green':'red' ?>">₹<?= number_format($p->gross_profit,0) ?></td>
        <td class="r"><?= $pm ?>%</td>
        <td class="r"><?= number_format($p->current_stock??0,0) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr>
        <td colspan="3" style="<?= $tf ?>">TOTAL</td>
        <td class="r" style="<?= $tf ?>"><?= number_format(array_sum(array_column((array)$detail_products,'total_qty')),0) ?></td>
        <td class="r" style="<?= $tf ?>">₹<?= number_format(array_sum(array_column((array)$detail_products,'total_sales')),0) ?></td>
        <td class="r" style="<?= $tf ?>">₹<?= number_format(array_sum(array_column((array)$detail_products,'total_cost')),0) ?></td>
        <td class="r" style="<?= $tf ?>">₹<?= number_format(array_sum(array_column((array)$detail_products,'gross_profit')),0) ?></td>
        <td style="<?= $tf ?>"></td><td style="<?= $tf ?>"></td>
    </tr></tfoot>
</table>

<div class="section-header2">Bill-wise Detail — <?= htmlspecialchars($sel_brand) ?></div>
<table>
    <thead><tr>
        <th>Bill No</th><th>Date</th><th>Customer</th><th>Product</th><th>Item Code</th>
        <th class="r">Qty</th><th class="r">Rate</th><th class="r">GST%</th><th class="r">Amount</th>
    </tr></thead>
    <tbody>
    <?php
    $prev=''; $btotal=0; $gtotal=0;
    foreach ($detail_items as $d):
        $new = $d->invoice_no !== $prev;
        if ($new && $prev) echo '<tr class="bill-sep"><td colspan="8" style="text-align:right;">Bill Total — '.$prev.'</td><td style="text-align:right;">₹'.number_format($btotal,2).'</td></tr>';
        if ($new) $btotal = 0;
        $btotal += $d->total; $gtotal += $d->total; $prev = $d->invoice_no;
    ?>
    <tr>
        <td><b><?= $new?htmlspecialchars($d->invoice_no):'' ?></b></td>
        <td><?= $new?date('d-M-Y',strtotime($d->inv_date)):'' ?></td>
        <td><?= $new?htmlspecialchars($d->customer_name??'Walk-in'):'' ?></td>
        <td><?= htmlspecialchars($d->product_name) ?></td>
        <td><?= htmlspecialchars($d->item_code??'—') ?></td>
        <td class="r"><?= $d->qty ?></td>
        <td class="r">₹<?= number_format($d->rate,2) ?></td>
        <td class="r"><?= $d->gst_percent ?>%</td>
        <td class="r">₹<?= number_format($d->total,2) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if ($prev): ?><tr class="bill-sep"><td colspan="8" style="text-align:right;">Bill Total — <?= $prev ?></td><td style="text-align:right;">₹<?= number_format($btotal,2) ?></td></tr><?php endif; ?>
    </tbody>
    <tfoot><tr><td colspan="8" style="<?= $tf ?>">GRAND TOTAL</td><td class="r" style="<?= $tf ?>">₹<?= number_format($gtotal,2) ?></td></tr></tfoot>
</table>
<?php endif; ?>
</body>
</html>
