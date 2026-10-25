<?php // FILE: application/views/stock_transfers/prev_day_sales_print.php
// Pick list of previous-day sales, sorted by rack then item name (items with no rack last).
$rack_label = function ($it) {
    if ($it['rack_code'] === '') return '';
    return $it['rack_code'] . ($it['rack_name'] !== '' && $it['rack_name'] !== $it['rack_code'] ? ' - ' . $it['rack_name'] : '');
};
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Last Day Sales - <?= htmlspecialchars($to_name) ?> - <?= date('d-m-Y', strtotime($sales_date)) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #000; margin: 0; padding: 15px; }
        .hdr { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 10px; }
        .hdr h2 { margin: 0; font-size: 18px; }
        .hdr p { margin: 2px 0; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px 5px; }
        th { background: #f0f0f0; text-align: center; }
        td.num { text-align: right; }
        td.center { text-align: center; }
        tr.rack-head td { background: #e8e8e8; font-weight: bold; }
        tfoot td { font-weight: bold; }
        .tools { margin-bottom: 10px; }
        .tools button, .tools a { font-size: 13px; padding: 5px 12px; margin-right: 6px; }
        @media print {
            .tools { display: none; }
            body { padding: 0; }
            @page { margin: 1cm; size: A4 portrait; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="tools">
        <button onclick="window.print()">Print</button>
        <a href="<?= htmlspecialchars(current_url() . '?' . http_build_query(array_merge($this->input->get(), ['format' => 'excel']))) ?>">Download Excel</a>
    </div>

    <div class="hdr">
        <div>
            <h2><?= htmlspecialchars($store_name) ?></h2>
            <p><?= nl2br(htmlspecialchars($store_address)) ?></p>
        </div>
        <div class="doc-title">
            <h1>Previous Day Sales</h1>
            <p>Sales at: <strong><?= htmlspecialchars($to_name) ?></strong></p>
            <?php if ($has_source): ?><p>Pick from: <strong><?= htmlspecialchars($from_name) ?></strong></p><?php endif; ?>
            <p>Sales Date: <?= date('d-m-Y', strtotime($sales_date)) ?> &middot; Requisition: <?= date('d-m-Y', strtotime($req_date)) ?></p>
            <p>Items: <?= count($items) ?> &middot; Units: <?= rtrim(rtrim(number_format($total_units, 3), '0'), '.') ?></p>
        </div>
    </div>

    <?php if (empty($items)): ?>
        <p style="text-align:center; padding:30px;">No sales found at <?= htmlspecialchars($to_name) ?> on <?= date('d-m-Y', strtotime($sales_date)) ?>.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:10%;">Rack</th>
                <th style="width:9%;">Item Code</th>
                <th style="width:12%;">Barcode</th>
                <th style="text-align:left;">Item Name</th>
                <th style="width:6%;">Unit</th>
                <th style="width:7%;">MRP</th>
                <th style="width:7%;">Sold Qty</th>
                <th style="width:8%;">Stock at <?= htmlspecialchars($to_name) ?></th>
                <?php if ($has_source): ?><th style="width:8%;">Stock at <?= htmlspecialchars($from_name) ?></th><?php endif; ?>
                <th style="width:8%;">Qty Sent</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $cols = $has_source ? 11 : 10;
        $prev_rack = null;
        foreach ($items as $i => $it):
            $rk = $rack_label($it);
            if ($rk !== $prev_rack):
                $prev_rack = $rk; ?>
            <tr class="rack-head"><td colspan="<?= $cols ?>"><?= $rk !== '' ? 'Rack: ' . htmlspecialchars($rk) : 'No Rack Assigned' ?></td></tr>
            <?php endif; ?>
            <tr>
                <td class="center"><?= $i + 1 ?></td>
                <td class="center"><?= htmlspecialchars($it['rack_code']) ?></td>
                <td class="center"><?= htmlspecialchars($it['item_code']) ?></td>
                <td class="center"><?= htmlspecialchars($it['ean_code']) ?></td>
                <td><?= htmlspecialchars($it['name']) ?></td>
                <td class="center"><?= htmlspecialchars($it['unit']) ?></td>
                <td class="num"><?= number_format($it['mrp'], 2) ?></td>
                <td class="num"><strong><?= rtrim(rtrim(number_format($it['prev_day_sales_qty'], 3), '0'), '.') ?></strong></td>
                <td class="num"><?= rtrim(rtrim(number_format($it['current_stock'], 3), '0'), '.') ?></td>
                <?php if ($has_source): ?><td class="num"><?= rtrim(rtrim(number_format((float)$it['source_stock'], 3), '0'), '.') ?></td><?php endif; ?>
                <td></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" class="num">Total</td>
                <td class="num"><?= rtrim(rtrim(number_format($total_units, 3), '0'), '.') ?></td>
                <td colspan="<?= $has_source ? 3 : 2 ?>"></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
</body>
</html>
