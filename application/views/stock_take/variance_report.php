<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Variance Report — <?= htmlspecialchars($take->take_no) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 12px; color: #111; background: #fff; padding: 16px; }
h1 { font-size: 18px; font-weight: 700; }
h2 { font-size: 13px; font-weight: 700; }
.header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #1e40af; padding-bottom: 8px; margin-bottom: 10px; }
.company-name { font-size: 16px; font-weight: 800; color: #1e40af; }
.company-sub { font-size: 10px; color: #555; margin-top: 2px; }
.title-block { text-align: right; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; }
.badge-danger { background: #fee2e2; color: #b91c1c; }
.badge-success { background: #dcfce7; color: #15803d; }
.meta { display: flex; gap: 24px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 12px; margin-bottom: 12px; }
.meta-item { font-size: 11px; }
.meta-item strong { display: block; font-size: 12px; }
.kpi-row { display: flex; gap: 12px; margin-bottom: 12px; }
.kpi { flex: 1; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 10px; text-align: center; }
.kpi .num { font-size: 20px; font-weight: 800; }
.kpi .lbl { font-size: 10px; color: #666; }
.num-red { color: #dc2626; }
.num-blue { color: #1e40af; }
.num-orange { color: #d97706; }
table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
thead th { background: #1e40af; color: #fff; padding: 6px 8px; font-size: 11px; text-align: left; }
thead th.r { text-align: right; }
tbody tr:nth-child(even) { background: #f8fafc; }
tbody td { padding: 5px 8px; font-size: 11px; border-bottom: 1px solid #e8ecf0; vertical-align: middle; }
tbody td.r { text-align: right; }
.var-neg { color: #dc2626; font-weight: 700; }
.var-pos { color: #1e40af; font-weight: 700; }
.val-neg { color: #dc2626; }
.val-pos { color: #1e40af; }
.total-row { background: #1e40af !important; color: #fff; font-weight: 700; }
.total-row td { border: none; padding: 6px 8px; }
.footer { border-top: 1px solid #e2e8f0; padding-top: 8px; font-size: 10px; color: #888; display: flex; justify-content: space-between; }
.no-variance { text-align: center; padding: 30px; color: #16a34a; font-size: 15px; font-weight: 700; }
@media print {
    body { padding: 0; }
    .no-print { display: none !important; }
    @page { margin: 10mm; }
}
</style>
</head>
<body onload="window.print()">

<!-- Header -->
<div class="header">
    <div>
        <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? 'Company') ?></div>
        <div class="company-sub"><?= htmlspecialchars($company['company_address'] ?? '') ?></div>
    </div>
    <div class="title-block">
        <div style="font-size:15px;font-weight:800;color:#1e40af;">STOCK VARIANCE REPORT</div>
        <div style="font-size:11px;margin-top:3px;"><?= htmlspecialchars($take->take_no) ?></div>
        <div style="font-size:10px;color:#666;">Printed: <?= date('d M Y h:i A') ?></div>
    </div>
</div>

<!-- Meta info -->
<div class="meta">
    <div class="meta-item"><strong><?= htmlspecialchars($take->location_name) ?></strong>Location</div>
    <div class="meta-item"><strong><?= date('d M Y', strtotime($take->started_at)) ?></strong>Count Date</div>
    <div class="meta-item"><strong><?= htmlspecialchars($take->started_by_name) ?></strong>Started By</div>
    <?php if ($take->finalized_by_name): ?>
    <div class="meta-item"><strong><?= htmlspecialchars($take->finalized_by_name) ?></strong>Finalized By</div>
    <div class="meta-item">
        <strong><?= $take->adjust_stock ? 'YES' : 'NO' ?></strong>Stock Adjusted
    </div>
    <?php endif; ?>
</div>

<!-- KPI row -->
<div class="kpi-row">
    <div class="kpi">
        <div class="num num-orange"><?= count($variance_items) ?></div>
        <div class="lbl">Items with Variance</div>
    </div>
    <div class="kpi">
        <div class="num num-red"><?= $total_shortage ?></div>
        <div class="lbl">Shortages (−)</div>
    </div>
    <div class="kpi">
        <div class="num num-blue"><?= $total_surplus ?></div>
        <div class="lbl">Surpluses (+)</div>
    </div>
    <div class="kpi">
        <div class="num <?= $total_val >= 0 ? 'num-blue' : 'num-red' ?>">
            <?= ($total_val >= 0 ? '+' : '') . '₹' . number_format(abs($total_val), 2) ?>
        </div>
        <div class="lbl">Net Value Impact</div>
    </div>
</div>

<?php if (empty($variance_items)): ?>
<div class="no-variance">✓ No variance found — all counts match system stock!</div>
<?php else: ?>

<!-- Variance table -->
<table>
    <thead>
        <tr>
            <th style="width:30px">#</th>
            <th>Item Code</th>
            <th>Product Name</th>
            <th class="r">Unit</th>
            <th class="r">System Qty</th>
            <th class="r">Counted Qty</th>
            <th class="r">Variance</th>
            <th class="r">Value Impact</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $grand_var_val = 0;
    foreach ($variance_items as $i => $item):
        $var = (float)$item->_variance;
        $val = $var * (float)($item->cost_price ?? 0);
        $grand_var_val += $val;
    ?>
        <tr>
            <td class="r" style="color:#999;"><?= $i + 1 ?></td>
            <td><small><?= htmlspecialchars($item->item_code) ?></small></td>
            <td><strong><?= htmlspecialchars($item->product_name) ?></strong></td>
            <td class="r"><?= htmlspecialchars($item->unit) ?></td>
            <td class="r"><?= number_format((float)$item->system_qty, 3) ?></td>
            <td class="r"><?= number_format((float)$item->counted_qty, 3) ?></td>
            <td class="r <?= $var < 0 ? 'var-neg' : 'var-pos' ?>">
                <?= ($var >= 0 ? '+' : '') . number_format($var, 3) ?>
            </td>
            <td class="r <?= $val < 0 ? 'val-neg' : 'val-pos' ?>">
                <?= ($val >= 0 ? '+' : '') . '₹' . number_format($val, 2) ?>
            </td>
        </tr>
    <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="6" style="text-align:right;">TOTAL</td>
            <td class="r">—</td>
            <td class="r"><?= ($grand_var_val >= 0 ? '+' : '') . '₹' . number_format($grand_var_val, 2) ?></td>
        </tr>
    </tbody>
</table>
<?php endif; ?>

<!-- Legend -->
<div style="font-size:10px;color:#666;margin-bottom:12px;">
    <span style="color:#dc2626;font-weight:700;">Negative (−)</span> = Shortage: counted less than system stock &nbsp;|&nbsp;
    <span style="color:#1e40af;font-weight:700;">Positive (+)</span> = Surplus: counted more than system stock
</div>

<!-- Signature row -->
<table style="width:100%;border:none;margin-top:24px;">
    <tr>
        <td style="width:33%;border-top:1px solid #999;padding-top:4px;font-size:10px;text-align:center;">Counter Signature</td>
        <td style="width:33%;border-top:1px solid #999;padding-top:4px;font-size:10px;text-align:center;">Verified By</td>
        <td style="width:33%;border-top:1px solid #999;padding-top:4px;font-size:10px;text-align:center;">Manager / Owner</td>
    </tr>
</table>

<div class="footer" style="margin-top:16px;">
    <span><?= htmlspecialchars($company['company_name'] ?? '') ?> — Stock Variance Report — <?= htmlspecialchars($take->take_no) ?></span>
    <span>Page 1</span>
</div>

<div class="no-print" style="text-align:center;margin-top:16px;">
    <button onclick="window.print()" style="padding:8px 24px;background:#1e40af;color:#fff;border:none;border-radius:4px;font-size:13px;cursor:pointer;">
        &#128438; Print Again
    </button>
    <button onclick="window.close()" style="padding:8px 24px;background:#6b7280;color:#fff;border:none;border-radius:4px;font-size:13px;cursor:pointer;margin-left:8px;">
        Close
    </button>
</div>

</body>
</html>
