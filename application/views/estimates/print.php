<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Estimate <?= $estimate->estimate_no ?></title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Arial, sans-serif; font-size: 12px; color: #000; }
.header { text-align: center; padding: 10px 0; border-bottom: 2px solid #000; margin-bottom: 8px; }
.header h2 { font-size: 18px; font-weight: bold; }
.header h3 { font-size: 14px; color: #333; }
.header p { font-size: 11px; }
.estimate-badge { background: #f0f0f0; border: 1px solid #ccc; padding: 4px 12px; display: inline-block; font-weight: bold; font-size: 13px; margin: 4px 0; border-radius: 3px; }
.info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #ccc; margin-bottom: 8px; }
.info-block { width: 48%; }
.info-block p { margin-bottom: 3px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
th { background: #333; color: #fff; padding: 5px 4px; text-align: left; font-size: 11px; }
td { padding: 4px; border-bottom: 1px solid #e0e0e0; font-size: 11px; }
.text-right { text-align: right; }
.text-center { text-align: center; }
.totals-table { width: 280px; margin-left: auto; }
.totals-table td { padding: 3px 6px; border: none; }
.grand-total { font-size: 14px; font-weight: bold; background: #f0f0f0; }
.footer-note { margin-top: 16px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 10px; color: #666; }
.status-badge { padding: 3px 10px; border-radius: 3px; font-size: 11px; font-weight: bold; }
.status-pending    { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
.status-closed_cash { background: #d4edda; color: #155724; border: 1px solid #28a745; }
.status-converted  { background: #cce5ff; color: #004085; border: 1px solid #007bff; }
.status-cancelled  { background: #f8d7da; color: #721c24; border: 1px solid #dc3545; }
@media print {
    .no-print { display: none; }
    body { -webkit-print-color-adjust: exact; }
}
/* Thermal mode (58mm/80mm) */
.thermal { max-width: 300px; margin: 0 auto; }
</style>
</head>
<body>
<div class="no-print" style="padding:10px; background:#f8f9fa; border-bottom:1px solid #dee2e6;">
    <button onclick="window.print()" style="padding:6px 16px; background:#007bff; color:#fff; border:none; border-radius:3px; cursor:pointer;">🖨 Print</button>
    <button onclick="window.close()" style="padding:6px 16px; margin-left:8px; background:#6c757d; color:#fff; border:none; border-radius:3px; cursor:pointer;">Close</button>
    <label style="margin-left:20px;"><input type="checkbox" id="thermalMode" onchange="toggleThermal()"> Thermal Mode (80mm)</label>
</div>

<div id="printContent" class="container" style="max-width:800px; margin:0 auto; padding:16px;">

<!-- Header -->
<div class="header">
    <h2><?= htmlspecialchars($company['company_name'] ?? 'SV ERP Solutions') ?></h2>
    <?php if (!empty($company['company_address'])): ?>
    <p><?= htmlspecialchars($company['company_address']) ?></p>
    <?php endif; ?>
    <?php if (!empty($company['company_phone'])): ?>
    <p>📞 <?= $company['company_phone'] ?> <?= !empty($company['company_email']) ? ' | ✉ '.$company['company_email'] : '' ?></p>
    <?php endif; ?>
    <?php if (!empty($company['gst_number'])): ?>
    <p>GSTIN: <?= $company['gst_number'] ?></p>
    <?php endif; ?>
</div>

<div style="text-align:center; margin:8px 0;">
    <div class="estimate-badge">ESTIMATE / QUOTATION</div>
    <span class="status-badge status-<?= $estimate->status ?>" style="margin-left:10px;">
        <?php $labels = ['pending'=>'PENDING','converted'=>'CONVERTED','closed_cash'=>'CASH SALE','cancelled'=>'CANCELLED'];
        echo $labels[$estimate->status] ?? strtoupper($estimate->status); ?>
    </span>
</div>

<!-- Info -->
<div class="info-row">
    <div class="info-block">
        <p><b>Customer:</b> <?= htmlspecialchars($estimate->customer_name) ?></p>
        <?php if ($estimate->customer_mobile): ?>
        <p><b>Mobile:</b> <?= $estimate->customer_mobile ?></p>
        <?php endif; ?>
    </div>
    <div class="info-block" style="text-align:right;">
        <p><b>Estimate No:</b> <?= $estimate->estimate_no ?></p>
        <p><b>Date:</b> <?= date('d-M-Y', strtotime($estimate->estimate_date)) ?></p>
        <?php if ($estimate->valid_until): ?>
        <p><b>Valid Until:</b> <?= date('d-M-Y', strtotime($estimate->valid_until)) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Items Table -->
<table>
<thead>
<tr>
    <th width="25">#</th>
    <th>Description</th>
    <th width="55" class="text-center">Qty</th>
    <th width="55" class="text-right">Rate</th>
    <?php if ($estimate->total_discount > 0): ?>
    <th width="50" class="text-right">Disc%</th>
    <?php endif; ?>
    <?php if ($estimate->total_tax > 0): ?>
    <th width="50" class="text-right">Tax%</th>
    <?php endif; ?>
    <th width="75" class="text-right">Amount</th>
</tr>
</thead>
<tbody>
<?php foreach ($items as $idx => $item): ?>
<tr>
    <td><?= $idx+1 ?></td>
    <td><?= htmlspecialchars($item->description) ?><?= $item->item_code ? ' <small>['.$item->item_code.']</small>' : '' ?></td>
    <td class="text-center"><?= $item->qty ?> <?= $item->unit ?></td>
    <td class="text-right">₹<?= number_format($item->rate, 2) ?></td>
    <?php if ($estimate->total_discount > 0): ?>
    <td class="text-right"><?= $item->discount ?>%</td>
    <?php endif; ?>
    <?php if ($estimate->total_tax > 0): ?>
    <td class="text-right"><?= $item->tax_percent ?>%</td>
    <?php endif; ?>
    <td class="text-right">₹<?= number_format($item->total, 2) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- Totals -->
<table class="totals-table">
<tr><td>Subtotal:</td><td class="text-right">₹<?= number_format($estimate->subtotal, 2) ?></td></tr>
<?php if ($estimate->total_discount > 0): ?>
<tr><td style="color:#c00;">Discount:</td><td class="text-right" style="color:#c00;">-₹<?= number_format($estimate->total_discount, 2) ?></td></tr>
<?php endif; ?>
<?php if ($estimate->total_tax > 0): ?>
<tr><td>Tax:</td><td class="text-right">₹<?= number_format($estimate->total_tax, 2) ?></td></tr>
<?php endif; ?>
<tr class="grand-total"><td><b>Grand Total:</b></td><td class="text-right"><b>₹<?= number_format($estimate->total_amount, 2) ?></b></td></tr>
</table>

<?php if ($estimate->notes): ?>
<div style="margin-top:10px; padding:6px; background:#f9f9f9; border:1px solid #ddd; border-radius:3px;">
    <b>Notes:</b> <?= nl2br(htmlspecialchars($estimate->notes)) ?>
</div>
<?php endif; ?>

<?php if ($estimate->status === 'closed_cash'): ?>
<div style="margin-top:10px; padding:6px; background:#d4edda; border:1px solid #28a745; border-radius:3px;">
    ✅ <b>CASH SALE — Paid in Full. No GST Bill.</b>
</div>
<?php endif; ?>

<!-- Footer -->
<div class="footer-note">
    <p>* This is an estimate only and not a tax invoice.</p>
    <p>* Prices subject to change without notice.</p>
    <?php if (!empty($company['company_name'])): ?>
    <div style="margin-top:20px; text-align:right;">
        <p>_____________________________</p>
        <p>Authorized Signatory</p>
        <p><?= htmlspecialchars($company['company_name']) ?></p>
    </div>
    <?php endif; ?>
    <p style="text-align:center; margin-top:10px; color:#999;">Generated by SV ERP Solutions | <?= date('d-M-Y H:i') ?></p>
</div>

</div>

<script>
function toggleThermal() {
    var thermal = document.getElementById('thermalMode').checked;
    document.getElementById('printContent').style.maxWidth = thermal ? '300px' : '800px';
    document.getElementById('printContent').style.fontSize = thermal ? '10px' : '';
}
window.onafterprint = function() { window.close(); };
</script>
</body>
</html>
