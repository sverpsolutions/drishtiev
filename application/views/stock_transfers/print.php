<?php // FILE: application/views/stock_transfers/print.php
$has_suggestions = false;
if (!empty($items)) {
    foreach ($items as $_it) {
        if (!empty($_it->prev_day_sales_qty) || !empty($_it->suggested_qty)) {
            $has_suggestions = true;
            break;
        }
    }
}
$doc_mode = isset($doc_mode) && $doc_mode === 'in' ? 'in' : 'out';
$tro_no   = $transfer->transfer_no;
$tri_no   = $transfer->tri_no ?? '';
$doc_no   = $doc_mode === 'in' ? $tri_no : $tro_no;
$st_label = function ($st) { return $st === 'in_transit' ? 'In Transit' : ucfirst((string)$st); };
$print_status = $doc_mode === 'in' ? $st_label($transfer->tri_status ?? '') : $st_label($transfer->status);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $doc_mode === 'in' ? 'Transfer In' : 'Transfer Out' ?> - <?= htmlspecialchars($doc_no) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #000; margin: 0; padding: 15px; }
        .hdr { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .comp-info h2 { margin: 0; font-size: 22px; color: #000; }
        .comp-info p { margin: 2px 0; font-size: 13px; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
        .doc-title p { margin: 2px 0; font-weight: bold; font-size: 14px; }
        
        .meta-box { width: 100%; border: 1px solid #000; display: flex; margin-bottom: 15px; }
        .meta-col { width: 50%; padding: 8px; box-sizing: border-box; }
        .meta-col:first-child { border-right: 1px solid #000; }
        .meta-col p { margin: 4px 0; }
        
        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items-table th, table.items-table td { border: 1px solid #000; padding: 6px; text-align: left; }
        table.items-table th { background: #f0f0f0; font-weight: bold; text-align: center; }
        table.items-table td.num { text-align: right; }
        table.items-table td.center { text-align: center; }
        
        .totals { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        .totals-tbl { width: 300px; border-collapse: collapse; }
        .totals-tbl th, .totals-tbl td { border: 1px solid #000; padding: 6px; }
        .totals-tbl th { text-align: left; background: #f0f0f0; }
        .totals-tbl td { text-align: right; font-weight: bold; font-size: 14px; }
        
        .footer { margin-top: 40px; display: flex; justify-content: space-between; }
        .sig-box { width: 200px; text-align: center; border-top: 1px solid #000; padding-top: 5px; }
        
        @media print {
            body { padding: 0; }
            @page { margin: 1cm; size: A4 portrait; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="hdr">
        <div class="comp-info">
            <?php if (!empty($store_logo)): ?>
                <img src="<?= base_url('uploads/'.$store_logo) ?>" alt="Logo" style="max-height:60px; margin-bottom:5px;">
            <?php endif; ?>
            <h2><?= htmlspecialchars($store_name) ?></h2>
            <p><?= nl2br(htmlspecialchars($store_address)) ?></p>
            <p>Phone: <?= htmlspecialchars($store_phone) ?></p>
            <p>GSTIN: <?= htmlspecialchars($store_gstin) ?></p>
        </div>
        <div class="doc-title">
            <h1><?= $doc_mode === 'in' ? 'Transfer In' : 'Transfer Out' ?></h1>
            <?php if ($doc_mode === 'in'): ?>
            <p>Transfer In No: <?= htmlspecialchars($tri_no) ?></p>
            <p>Transfer Out No: <?= htmlspecialchars($tro_no) ?></p>
            <?php else: ?>
            <p>Transfer Out No: <?= htmlspecialchars($tro_no) ?></p>
            <p>Transfer In No: <?= $tri_no !== '' ? htmlspecialchars($tri_no) : '—' ?></p>
            <?php endif; ?>
            <p>Date: <?= date('d-m-Y', strtotime($transfer->created_at)) ?></p>
            <p>Status: <?= htmlspecialchars($print_status) ?></p>
        </div>
    </div>
    
    <div class="meta-box">
        <div class="meta-col">
            <p><strong>From:</strong></p>
            <p style="font-size:16px; margin-top:2px; font-weight:bold;"><?= htmlspecialchars($transfer->from_location_name) ?><?= !empty($transfer->from_location_code) ? ' (' . htmlspecialchars($transfer->from_location_code) . ')' : '' ?></p>
        </div>
        <div class="meta-col">
            <p><strong>To:</strong></p>
            <p style="font-size:16px; margin-top:2px; font-weight:bold;"><?= htmlspecialchars($transfer->to_location_name) ?><?= !empty($transfer->to_location_code) ? ' (' . htmlspecialchars($transfer->to_location_code) . ')' : '' ?></p>
        </div>
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 20%; text-align:left;">Item Name</th>
                <th style="width: 9%;">Item Code</th>
                <th style="width: 11%;">EAN Code</th>
                <th style="width: 5%;">Unit</th>
                <th style="width: 9%;">MRP</th>
                <?php if ($has_suggestions): ?>
                <th style="width: 7%;">Prev Sales</th>
                <th style="width: 7%;">Suggested</th>
                <?php endif; ?>
                <th style="width: 8%;">Req Qty</th>
                <th style="width: 8%;">Appr Qty</th>
                <th style="width: 25%; text-align:left;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php $i=1; $t_req = 0; $t_app = 0; foreach($items as $item): ?>
            <tr>
                <td class="center"><?= $i++ ?></td>
                <td><?= htmlspecialchars($item->product_name) ?></td>
                <td class="center"><?= htmlspecialchars($item->item_code ?? '') ?></td>
                <td class="center"><?= htmlspecialchars($item->ean_code ?? '') ?></td>
                <td class="center"><?= htmlspecialchars($item->unit) ?></td>
                <td class="num"><?= number_format((float)($item->mrp ?? 0), 2) ?></td>
                <?php if ($has_suggestions): ?>
                <td class="num"><?= !empty($item->prev_day_sales_qty) ? (float)$item->prev_day_sales_qty : '-' ?></td>
                <td class="num"><?= !empty($item->suggested_qty) ? (float)$item->suggested_qty : '-' ?></td>
                <?php endif; ?>
                <td class="num"><?= $item->requested_qty ?></td>
                <td class="num"><?= $item->approved_qty ?? '-' ?></td>
                <td><?= htmlspecialchars($item->remarks ?? '') ?></td>
            </tr>
            <?php 
                $t_req += $item->requested_qty;
                $t_app += (float)$item->approved_qty;
            endforeach; 
            ?>
            <tr style="font-weight:bold; background:#f0f0f0;">
                <td colspan="<?= $has_suggestions ? 8 : 6 ?>" style="text-align:right;">Total Quantity</td>
                <td class="num"><?= $t_req ?></td>
                <td class="num"><?= $t_app > 0 ? $t_app : '-' ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    
    <?php if(!empty($transfer->notes)): ?>
    <div style="border: 1px solid #000; padding: 10px; margin-bottom: 20px;">
        <h4 style="margin:0 0 5px 0;">Notes / Remarks:</h4>
        <p style="margin:0; font-size:12px;"><?= nl2br(htmlspecialchars($transfer->notes)) ?></p>
    </div>
    <?php endif; ?>
    
    <div class="footer">
        <div class="sig-box">Prepared By</div>
        <div class="sig-box">Authorized By</div>
        <div class="sig-box">Receiver Sign</div>
    </div>

</body>
</html>
