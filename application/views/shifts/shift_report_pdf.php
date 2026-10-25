<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; color: #333; font-size: 11px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        .mb-0 { margin-bottom: 0px; }
        .mb-2 { margin-bottom: 8px; }
        .my-3 { margin-top: 12px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table, th, td { border: 1px solid #ccc; }
        th, td { padding: 5px; }
        th { background-color: #343a40; color: #fff; }
        .bg-light { background-color: #f8f9fa; }
        .text-danger { color: #dc3545; }
        .text-success { color: #28a745; }
        .signature-section { margin-top: 50px; }
        .signature-line { border-top: 1px dashed #777; width: 150px; display: inline-block; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="text-center">
        <h2 class="mb-0 font-weight-bold"><?= htmlspecialchars($company['company_name'] ?? 'View & Chew') ?></h2>
        <div style="font-size: 10px; color: #555;"><?= htmlspecialchars($company['company_address'] ?? 'ERP Solutions') ?></div>
        <div style="font-size: 10px; color: #555;">GSTIN: <?= htmlspecialchars($company['company_gstin'] ?? 'N/A') ?></div>
        <hr class="my-3">
        <h4 class="font-weight-bold text-uppercase text-secondary" style="margin-top:0px;">Shift Closing Summary Report</h4>
        <div class="font-weight-bold">Shift ID: #<?= $shift->id ?> &middot; Status: <?= strtoupper($shift->status) ?></div>
    </div>

    <table style="border:none; margin-top:15px;">
        <tr style="border:none;">
            <td style="border:none; width:50%;">
                <span style="color:#555;">Cashier Name:</span><br>
                <strong><?= htmlspecialchars($shift->cashier_name) ?></strong>
            </td>
            <td style="border:none; width:50%; text-align:right;">
                <span style="color:#555;">Business Date:</span><br>
                <strong><?= date('d-M-Y', strtotime($shift->business_date)) ?></strong>
            </td>
        </tr>
        <tr style="border:none;">
            <td style="border:none;">
                <span style="color:#555;">Shift Start Time:</span><br>
                <strong><?= date('d-M-Y H:i:s', strtotime($shift->opened_at)) ?></strong>
            </td>
            <td style="border:none; text-align:right;">
                <span style="color:#555;">Shift End Time:</span><br>
                <strong><?= $shift->closed_at ? date('d-M-Y H:i:s', strtotime($shift->closed_at)) : 'N/A' ?></strong>
            </td>
        </tr>
    </table>

    <h4 class="font-weight-bold text-uppercase" style="margin-bottom:5px; font-size:10px;">Collection Reconciliation</h4>
    <table>
        <thead>
            <tr>
                <th>Payment Mode</th>
                <th>System (₹)</th>
                <th>Actual (₹)</th>
                <th>Difference (₹)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-weight-bold">Cash</td>
                <td class="text-right">₹<?= number_format($shift->system_cash, 2) ?></td>
                <td class="text-right">₹<?= number_format($shift->actual_cash, 2) ?></td>
                <td class="text-right font-weight-bold <?= $shift->diff_cash < 0 ? 'text-danger' : ($shift->diff_cash > 0 ? 'text-success' : '') ?>">
                    <?= $shift->diff_cash < 0 ? '-' : ($shift->diff_cash > 0 ? '+' : '') ?> ₹<?= number_format(abs($shift->diff_cash), 2) ?>
                </td>
            </tr>
            <tr>
                <td class="font-weight-bold">UPI</td>
                <td class="text-right">₹<?= number_format($shift->system_upi, 2) ?></td>
                <td class="text-right">₹<?= number_format($shift->actual_upi, 2) ?></td>
                <td class="text-right font-weight-bold <?= $shift->diff_upi < 0 ? 'text-danger' : ($shift->diff_upi > 0 ? 'text-success' : '') ?>">
                    <?= $shift->diff_upi < 0 ? '-' : ($shift->diff_upi > 0 ? '+' : '') ?> ₹<?= number_format(abs($shift->diff_upi), 2) ?>
                </td>
            </tr>
            <tr>
                <td class="font-weight-bold">Card</td>
                <td class="text-right">₹<?= number_format($shift->system_card, 2) ?></td>
                <td class="text-right">₹<?= number_format($shift->actual_card, 2) ?></td>
                <td class="text-right font-weight-bold <?= $shift->diff_card < 0 ? 'text-danger' : ($shift->diff_card > 0 ? 'text-success' : '') ?>">
                    <?= $shift->diff_card < 0 ? '-' : ($shift->diff_card > 0 ? '+' : '') ?> ₹<?= number_format(abs($shift->diff_card), 2) ?>
                </td>
            </tr>
            <tr>
                <td class="font-weight-bold">Bank Transfer</td>
                <td class="text-right">₹<?= number_format($shift->system_bank, 2) ?></td>
                <td class="text-right">₹<?= number_format($shift->actual_bank, 2) ?></td>
                <td class="text-right text-muted">—</td>
            </tr>
            <tr>
                <td class="font-weight-bold">Wallet</td>
                <td class="text-right">₹<?= number_format($shift->system_wallet, 2) ?></td>
                <td class="text-right">₹<?= number_format($shift->actual_wallet, 2) ?></td>
                <td class="text-right text-muted">—</td>
            </tr>
            <tr class="bg-light" style="color: #777;">
                <td>Credit Sales (Bills Due)</td>
                <td class="text-right">₹<?= number_format($shift->system_credit, 2) ?></td>
                <td class="text-center">—</td>
                <td class="text-center">—</td>
            </tr>
        </tbody>
    </table>

    <table style="border:none;">
        <tr style="border:none;">
            <td style="border:none; width:50%;"></td>
            <td style="border:none; width:50%; padding:0px;">
                <table>
                    <tr>
                        <td class="font-weight-bold bg-light">Opening Cash Float:</td>
                        <td class="text-right font-weight-bold">₹<?= number_format($shift->opening_cash, 2) ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold bg-light">Total Sales Value:</td>
                        <td class="text-right font-weight-bold">₹<?= number_format($total_sales, 2) ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold bg-light">Refund Amount:</td>
                        <td class="text-right font-weight-bold">₹<?= number_format($total_refunds ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold bg-light">Overall Discount:</td>
                        <td class="text-right font-weight-bold">₹<?= number_format($total_disc ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold bg-light">No. of Bills:</td>
                        <td class="text-right font-weight-bold"><?= $bill_count ?? 0 ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold bg-light">Sales Receipts No(s):</td>
                        <td class="text-right font-weight-bold"><?= ($first_receipt ?? '') . ' - ' . ($last_receipt ?? '') ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold bg-light">No. of Items Sold:</td>
                        <td class="text-right font-weight-bold"><?= $items_sold ?? 0 ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold bg-light">No. of Refunds:</td>
                        <td class="text-right font-weight-bold"><?= $refund_count ?? 0 ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold bg-light">Bill Payment Mode Changes:</td>
                        <td class="text-right font-weight-bold"><?= $payment_mode_changes ?? 0 ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold bg-light text-danger">Total Shortage:</td>
                        <td class="text-right font-weight-bold text-danger">₹<?= number_format($shift->short_amount, 2) ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-bold bg-light text-success">Total Excess:</td>
                        <td class="text-right font-weight-bold text-success">₹<?= number_format($shift->excess_amount, 2) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <?php if (!empty($shift->remarks)): ?>
        <div style="margin-top:10px;">
            <strong>Remarks:</strong>
            <div style="background-color:#f8f9fa; padding:8px; border:1px solid #ccc; font-style:italic; margin-top:3px;">
                <?= htmlspecialchars($shift->remarks) ?>
            </div>
        </div>
    <?php endif; ?>

    <table class="signature-section" style="border:none; margin-top:60px;">
        <tr style="border:none;">
            <td style="border:none; width:50%; text-align:center;">
                <div class="signature-line"></div><br>
                Cashier Signature
            </td>
            <td style="border:none; width:50%; text-align:center;">
                <div class="signature-line"></div><br>
                Manager Signature
            </td>
        </tr>
    </table>
</body>
</html>
