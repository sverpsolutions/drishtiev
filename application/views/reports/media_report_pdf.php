<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; color: #333; font-size: 10px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        .mb-0 { margin-bottom: 0px; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .my-3 { margin-top: 12px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #ccc; }
        th, td { padding: 6px 8px; }
        th { background-color: #343a40; color: #fff; font-weight: bold; text-align: left; text-transform: uppercase; }
        .bg-light { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="text-center">
        <?php 
        $company_logo = $company['company_logo'] ?? '';
        if (!empty($company_logo) && file_exists('./' . $company_logo)): 
        ?>
            <div style="margin-bottom: 8px;">
                <img src="<?= './' . $company_logo ?>" style="max-height: 50px; max-width: 150px; object-fit: contain;">
            </div>
        <?php endif; ?>
        <h2 class="mb-1 font-weight-bold" style="margin-top:0px;"><?= htmlspecialchars($company['company_name'] ?? 'Company Name') ?></h2>
        <div style="font-size: 9px; color: #555;"><?= htmlspecialchars($company['company_address'] ?? '') ?></div>
        <div style="font-size: 9px; color: #555;">GSTIN: <?= htmlspecialchars($company['company_gstin'] ?? 'N/A') ?> | Phone: <?= htmlspecialchars($company['company_phone'] ?? '') ?></div>
        <hr class="my-3">
        <h3 class="font-weight-bold text-uppercase text-secondary" style="margin-top:0px; margin-bottom:5px;">Store-wise Media Report</h3>
        <div style="font-size: 10px; font-weight: bold; margin-bottom: 15px;">Period: <?= date('d M Y', strtotime($from)) ?> to <?= date('d M Y', strtotime($to)) ?></div>
    </div>

    <?php
    $by_location = [];
    foreach ($media_summary as $row) {
        $loc = $row->location_name ?: 'Main Store';
        if (!isset($by_location[$loc])) $by_location[$loc] = [];
        $by_location[$loc][] = $row;
    }
    $modes = ['cash'=>'Cash','upi'=>'UPI','card'=>'Card','neft'=>'NEFT','cheque'=>'Cheque','wallet'=>'Wallet','credit'=>'Credit'];
    ?>

    <?php foreach ($by_location as $locName => $rows): ?>
        <h4 style="margin-bottom: 5px;"><?= htmlspecialchars($locName) ?></h4>
        <table>
            <thead>
                <tr>
                    <th>Payment Mode</th>
                    <th class="text-right">Bills</th>
                    <th class="text-right">Amount (Rs)</th>
                    <th class="text-right">% Share</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $loc_total = array_sum(array_column($rows, 'total_amount'));
                foreach ($rows as $r):
                    $pct = $loc_total > 0 ? round($r->total_amount/$loc_total*100,1) : 0;
                    $mode_label = $modes[$r->payment_mode] ?? ucfirst($r->payment_mode);
                ?>
                <tr>
                    <td><?= $mode_label ?></td>
                    <td class="text-right"><?= number_format($r->bill_count) ?></td>
                    <td class="text-right font-weight-bold"><?= number_format($r->total_amount, 2) ?></td>
                    <td class="text-right"><?= $pct ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td>Total</td>
                    <td class="text-right"><?= array_sum(array_column($rows,'bill_count')) ?></td>
                    <td class="text-right"><?= number_format($loc_total,2) ?></td>
                    <td class="text-right">100%</td>
                </tr>
            </tfoot>
        </table>
    <?php endforeach; ?>

    <?php if (!empty($totals)): ?>
        <h4 style="margin-bottom: 5px;">Grand Total - All Locations</h4>
        <table>
            <thead>
                <tr>
                    <th>Mode</th>
                    <th class="text-right">Bills</th>
                    <th class="text-right">Amount (Rs)</th>
                </tr>
            </thead>
            <tbody>
                <?php $grand = array_sum(array_column($totals, 'total_amount')); ?>
                <?php foreach ($totals as $t): ?>
                <tr>
                    <td><?= $modes[$t->payment_mode] ?? ucfirst($t->payment_mode) ?></td>
                    <td class="text-right"><?= number_format($t->bill_count) ?></td>
                    <td class="text-right font-weight-bold"><?= number_format($t->total_amount,2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td>Grand Total</td>
                    <td class="text-right"><?= array_sum(array_column($totals,'bill_count')) ?></td>
                    <td class="text-right"><?= number_format($grand,2) ?></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

</body>
</html>
