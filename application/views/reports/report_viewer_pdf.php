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
        .badge { display: inline-block; padding: 2px 6px; font-size: 9px; font-weight: bold; border-radius: 4px; }
        .badge-success { background-color: #28a745; color: #fff; }
        .badge-secondary { background-color: #6c757d; color: #fff; }
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
        <h2 class="mb-1 font-weight-bold" style="margin-top:0px;"><?= htmlspecialchars($company['company_name'] ?? 'View & Chew') ?></h2>
        <div style="font-size: 9px; color: #555;"><?= htmlspecialchars($company['company_address'] ?? '') ?></div>
        <div style="font-size: 9px; color: #555;">GSTIN: <?= htmlspecialchars($company['company_gstin'] ?? 'N/A') ?> | Phone: <?= htmlspecialchars($company['company_phone'] ?? '') ?></div>
        <hr class="my-3">
        <h3 class="font-weight-bold text-uppercase text-secondary" style="margin-top:0px; margin-bottom:5px;"><?= htmlspecialchars($report_title) ?></h3>
        <div style="font-size: 10px; font-weight: bold; margin-bottom: 15px;">Period: <?= date('d M Y', strtotime($from)) ?> to <?= date('d M Y', strtotime($to)) ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <?php foreach ($headers as $header): ?>
                    <th><?= htmlspecialchars($header) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rows)): ?>
                <?php foreach ($rows as $row_data):
                    $__is_total = isset($row_data[0]) && strtoupper(trim((string)$row_data[0])) === 'TOTAL';
                    $__row_style = $__is_total ? 'background-color:#fde68a;font-weight:bold;' : '';
                ?>
                    <tr style="<?= $__row_style ?>">
                        <?php foreach ($row_data as $cell): ?>
                            <td style="<?= $__row_style ?>">
                                <?php
                                $cell_str = trim((string)$cell);
                                if (strtoupper($cell_str) === 'OPEN') {
                                    echo '<span class="badge badge-success">Open</span>';
                                } elseif (strtoupper($cell_str) === 'CLOSED') {
                                    echo '<span class="badge badge-secondary">Closed</span>';
                                } else {
                                    echo htmlspecialchars($cell);
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= count($headers) ?>" style="text-align: center; color: #777; padding: 20px;">
                        No records found for this period
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
