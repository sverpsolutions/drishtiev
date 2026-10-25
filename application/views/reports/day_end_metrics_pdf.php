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
        <h3 class="font-weight-bold text-uppercase text-secondary" style="margin-top:0px; margin-bottom:5px;">Day End Metrics Dashboard</h3>
        <div style="font-size: 10px; font-weight: bold; margin-bottom: 15px;">Date: <?= date('d M Y', strtotime($date)) ?></div>
    </div>

    <h4 style="margin-bottom: 5px;">Key Performance Indicators (KPIs)</h4>
    <table>
        <tbody>
            <tr>
                <td class="font-weight-bold">Total Sales</td>
                <td class="text-right font-weight-bold">Rs <?= number_format($total_sales, 2) ?></td>
            </tr>
            <tr>
                <td class="font-weight-bold">Bills Generated</td>
                <td class="text-right"><?= number_format($bill_count) ?></td>
            </tr>
            <tr>
                <td class="font-weight-bold">Avg Bill Value</td>
                <td class="text-right">Rs <?= number_format($avg_bill, 2) ?></td>
            </tr>
            <tr>
                <td class="font-weight-bold">Customers</td>
                <td class="text-right"><?= number_format($customer_count) ?></td>
            </tr>
            <tr>
                <td class="font-weight-bold">Returns Value</td>
                <td class="text-right">Rs <?= number_format($total_returns, 2) ?></td>
            </tr>
            <tr>
                <td class="font-weight-bold">Cancellations</td>
                <td class="text-right"><?= number_format($cancelled_count) ?></td>
            </tr>
        </tbody>
    </table>

    <h4 style="margin-bottom: 5px;">Payment Mode Summary</h4>
    <table>
        <thead>
            <tr>
                <th>Mode</th>
                <th class="text-right">Bills</th>
                <th class="text-right">Amount (Rs)</th>
                <th class="text-right">%</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $modes_label = ['cash'=>'Cash','upi'=>'UPI','card'=>'Card','neft'=>'NEFT','cheque'=>'Cheque','wallet'=>'Wallet','credit'=>'Credit','split'=>'Split'];
            $total_collected = array_sum(array_column($payment_modes, 'amount'));
            foreach ($payment_modes as $mode => $vals):
                $pct = $total_collected > 0 ? round($vals['amount']/$total_collected*100,1) : 0;
            ?>
            <tr>
                <td><?= $modes_label[$mode] ?? ucfirst($mode) ?></td>
                <td class="text-right"><?= $vals['count'] ?></td>
                <td class="text-right font-weight-bold"><?= number_format($vals['amount'],2) ?></td>
                <td class="text-right text-muted"><?= $pct ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="font-weight-bold bg-light">
            <tr>
                <td>Total</td>
                <td class="text-right"><?= $bill_count ?></td>
                <td class="text-right"><?= number_format($total_collected,2) ?></td>
                <td class="text-right">100%</td>
            </tr>
        </tfoot>
    </table>

</body>
</html>
