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
        <h3 class="font-weight-bold text-uppercase text-secondary" style="margin-top:0px; margin-bottom:5px;">Stock Movements</h3>
    </div>

    <table style="margin-bottom: 20px;">
        <tbody>
            <tr>
                <td class="font-weight-bold">Product</td>
                <td><?= htmlspecialchars($product->name) ?></td>
                <td class="font-weight-bold">Category</td>
                <td><?= htmlspecialchars($product->category) ?></td>
            </tr>
            <tr>
                <td class="font-weight-bold">Current Stock</td>
                <td><?= $product->stock_qty ?> <?= htmlspecialchars($product->unit) ?></td>
                <td class="font-weight-bold">Purchase Price</td>
                <td>Rs <?= number_format($product->purchase_price, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <h4 style="margin-bottom: 5px;">Movement History</h4>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Reference</th>
                <th>Party</th>
                <th class="text-right">In (+)</th>
                <th class="text-right">Out (-)</th>
                <th class="text-right">Rate</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($movements)): foreach ($movements as $m): ?>
            <tr>
                <td><?= date('d M Y', strtotime($m->date)) ?></td>
                <td><?= htmlspecialchars($m->type_label) ?></td>
                <td><?= htmlspecialchars($m->reference) ?></td>
                <td><?= htmlspecialchars($m->party) ?></td>
                <td class="text-right font-weight-bold" style="color: green;"><?= $m->in_qty ? $m->in_qty : '-' ?></td>
                <td class="text-right font-weight-bold" style="color: red;"><?= $m->out_qty ? $m->out_qty : '-' ?></td>
                <td class="text-right">Rs <?= number_format($m->rate, 2) ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="7" class="text-center text-muted">No movements found.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
