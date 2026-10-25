<?php
// FILE: application/views/billing/email_template.php
// Loaded as a CI3 view → rendered to string → sent as HTML email
// Keep inline styles only — email clients strip <style> blocks
?><!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;font-size:14px;color:#333;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:24px 0;">
<tr><td align="center">

<!-- Outer card -->
<table width="600" cellpadding="0" cellspacing="0"
       style="background:#fff;border-radius:6px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

    <!-- Header -->
    <tr>
        <td style="background:#1a1a1a;padding:20px 28px;">
            <span style="color:#fff;font-size:20px;font-weight:bold;">
                <?= htmlspecialchars($company['company_name'] ?? 'View & Chew') ?>
            </span>
            <span style="color:#aaa;font-size:12px;margin-left:8px;">Tax Invoice</span>
        </td>
    </tr>

    <!-- Greeting -->
    <tr>
        <td style="padding:24px 28px 12px;">
            <p style="margin:0 0 12px;">Dear <strong><?= htmlspecialchars($invoice->customer_name) ?></strong>,</p>
            <p style="margin:0 0 8px;color:#555;">
                Please find your invoice details below. A PDF copy is attached to this email.
            </p>
        </td>
    </tr>

    <!-- Invoice summary box -->
    <tr>
        <td style="padding:0 28px 20px;">
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f8f8f8;border:1px solid #e0e0e0;border-radius:4px;padding:0;">
                <tr style="background:#e8e8e8;">
                    <td style="padding:8px 14px;font-size:11px;text-transform:uppercase;color:#666;letter-spacing:.5px;font-weight:bold;">
                        Invoice Summary
                    </td>
                </tr>
                <tr>
                    <td style="padding:14px;">
                        <table width="100%" cellpadding="4" cellspacing="0" style="font-size:13px;">
                            <tr>
                                <td style="color:#666;width:45%;">Invoice Number</td>
                                <td style="font-weight:bold;"><?= $invoice->invoice_no ?></td>
                            </tr>
                            <tr>
                                <td style="color:#666;">Date</td>
                                <td><?= date('d F Y', strtotime($invoice->invoice_date)) ?></td>
                            </tr>
                            <tr>
                                <td style="color:#666;">Invoice Type</td>
                                <td><?= ucfirst($invoice->invoice_type) ?></td>
                            </tr>
                            <tr>
                                <td style="color:#666;">Payment Mode</td>
                                <td><?= ucfirst($invoice->payment_mode) ?></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Amount boxes -->
    <tr>
        <td style="padding:0 28px 20px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width:32%;padding:12px 14px;background:#f0f0f0;border-radius:4px;text-align:center;">
                        <div style="font-size:10px;color:#888;margin-bottom:4px;">TOTAL AMOUNT</div>
                        <div style="font-size:18px;font-weight:bold;">&#8377; <?= number_format($invoice->total_amount, 2) ?></div>
                    </td>
                    <td style="width:2%;"></td>
                    <td style="width:32%;padding:12px 14px;background:#e8f5e9;border-radius:4px;text-align:center;">
                        <div style="font-size:10px;color:#666;margin-bottom:4px;">PAID</div>
                        <div style="font-size:18px;font-weight:bold;color:#2e7d32;">&#8377; <?= number_format($invoice->paid_amount, 2) ?></div>
                    </td>
                    <td style="width:2%;"></td>
                    <td style="width:32%;padding:12px 14px;background:<?= $invoice->due_amount > 0 ? '#fce4e4' : '#e8f5e9' ?>;border-radius:4px;text-align:center;">
                        <div style="font-size:10px;color:#666;margin-bottom:4px;">BALANCE DUE</div>
                        <div style="font-size:18px;font-weight:bold;color:<?= $invoice->due_amount > 0 ? '#c62828' : '#2e7d32' ?>;">
                            &#8377; <?= number_format($invoice->due_amount, 2) ?>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- Items (compact) -->
    <tr>
        <td style="padding:0 28px 20px;">
            <table width="100%" cellpadding="6" cellspacing="0"
                   style="font-size:12px;border-collapse:collapse;">
                <thead>
                    <tr style="background:#333;color:#fff;">
                        <td style="padding:7px 8px;">#</td>
                        <td style="padding:7px 8px;">Product</td>
                        <td style="padding:7px 8px;text-align:center;">Qty</td>
                        <td style="padding:7px 8px;text-align:right;">Rate</td>
                        <td style="padding:7px 8px;text-align:right;">GST</td>
                        <td style="padding:7px 8px;text-align:right;">Total</td>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr style="border-bottom:1px solid #eee;<?= $i % 2 === 0 ? 'background:#fafafa;' : '' ?>">
                    <td style="padding:6px 8px;"><?= $i + 1 ?></td>
                    <td style="padding:6px 8px;"><?= htmlspecialchars($item->product_name) ?></td>
                    <td style="padding:6px 8px;text-align:center;"><?= $item->qty ?> <?= $item->unit ?></td>
                    <td style="padding:6px 8px;text-align:right;">&#8377; <?= number_format($item->rate, 2) ?></td>
                    <td style="padding:6px 8px;text-align:right;"><?= $item->gst_percent ?>%</td>
                    <td style="padding:6px 8px;text-align:right;font-weight:bold;">&#8377; <?= number_format($item->total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </td>
    </tr>

    <!-- CTA -->
    <?php
    $ci =& get_instance();
    $inv_url = base_url('billing/invoice/' . $invoice->id);
    ?>
    <tr>
        <td style="padding:0 28px 24px;text-align:center;">
            <a href="<?= $inv_url ?>"
               style="display:inline-block;background:#1a1a1a;color:#fff;padding:10px 28px;border-radius:4px;text-decoration:none;font-size:13px;font-weight:bold;">
                View Invoice Online
            </a>
        </td>
    </tr>

    <!-- Note -->
    <tr>
        <td style="padding:0 28px 20px;">
            <p style="margin:0;font-size:12px;color:#888;font-style:italic;">
                <?= htmlspecialchars($company['invoice_terms'] ?? 'Goods once sold will not be taken back.') ?>
            </p>
        </td>
    </tr>

    <!-- Footer -->
    <tr>
        <td style="background:#f4f4f4;padding:14px 28px;border-top:1px solid #e0e0e0;">
            <p style="margin:0;font-size:11px;color:#999;text-align:center;">
                <?= htmlspecialchars($company['company_name'] ?? 'View & Chew') ?> &bull;
                <?= htmlspecialchars($company['company_address'] ?? 'Mohali Punjab') ?> &bull;
                <?= htmlspecialchars($company['company_phone'] ?? '') ?><br>
                GSTIN: <?= htmlspecialchars($company['company_gstin'] ?? '') ?>
            </p>
        </td>
    </tr>

</table>
<!-- /card -->

</td></tr>
</table>

</body>
</html>
