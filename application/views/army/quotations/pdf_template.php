<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .company-name { font-size: 16px; font-weight: bold; color: #2f3e1f; }
        .quotation-title { font-size: 15px; font-weight: bold; text-align: center; text-transform: uppercase;
            letter-spacing: 1px; color: #2f3e1f; border-top: 2px solid #4b5320; border-bottom: 2px solid #4b5320;
            padding: 5px 0; margin: 8px 0; }
        .meta-table td { padding: 2px 4px; font-size: 9.5px; }
        .items-table th { background: #4b5320; color: #fff; padding: 5px; font-size: 9px; text-align: left; }
        .items-table td { padding: 4px 5px; border-bottom: 1px solid #ddd; font-size: 9.5px; }
        .totals-table td { padding: 3px 6px; font-size: 9.5px; }
        .totals-table .grand { font-weight: bold; font-size: 11px; border-top: 2px solid #4b5320; }
        .terms-box { font-size: 8.5px; white-space: pre-line; border: 1px solid #ddd; padding: 6px; margin-top: 8px; background: #fafaf7; }
        .sign-block { text-align: right; margin-top: 20px; }
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; font-size: 7.5px; color: #777;
            border-top: 1px solid #ccc; padding-top: 3px; text-align: center; }
    </style>
</head>
<body>

<table class="header-table">
    <tr>
        <td style="width:20%;">
            <?php if (!empty($quotation->logo_path) && file_exists('./' . $quotation->logo_path)): ?>
                <img src="<?= './' . $quotation->logo_path ?>" style="max-height:55px;max-width:120px;">
            <?php endif; ?>
        </td>
        <td style="width:55%;text-align:center;">
            <div class="company-name"><?= htmlspecialchars($quotation->company_name) ?></div>
            <div style="white-space:pre-line;font-size:9px;color:#555;"><?= htmlspecialchars($quotation->company_address ?? '') ?></div>
        </td>
        <td style="width:25%;text-align:right;font-size:8.5px;color:#555;">
            <?php if (!empty($quotation->company_gst_number)): ?>GSTIN: <?= htmlspecialchars($quotation->company_gst_number) ?><br><?php endif; ?>
            <?php if (!empty($quotation->company_phone)): ?>Ph: <?= htmlspecialchars($quotation->company_phone) ?><br><?php endif; ?>
            <?php if (!empty($quotation->company_email)): ?><?= htmlspecialchars($quotation->company_email) ?><br><?php endif; ?>
            <?php if (!empty($quotation->company_website)): ?><?= htmlspecialchars($quotation->company_website) ?><?php endif; ?>
        </td>
    </tr>
</table>

<div class="quotation-title">Quotation</div>

<table class="meta-table">
    <tr>
        <td style="width:50%;">
            <strong>Quotation No:</strong> <?= htmlspecialchars($quotation->quotation_no) ?><br>
            <strong>Date:</strong> <?= date('d M Y', strtotime($quotation->quotation_date)) ?><br>
            <strong>Valid Till:</strong> <?= $quotation->valid_till ? date('d M Y', strtotime($quotation->valid_till)) : '—' ?><br>
            <?php if (!empty($quotation->subject)): ?><strong>Subject:</strong> <?= htmlspecialchars($quotation->subject) ?><br><?php endif; ?>
            <?php if (!empty($quotation->reference)): ?><strong>Reference:</strong> <?= htmlspecialchars($quotation->reference) ?><?php endif; ?>
        </td>
        <td style="width:50%;">
            <strong>To:</strong> <?= htmlspecialchars($quotation->customer_name) ?><br>
            <?php if (!empty($quotation->contact_person)): ?>Attn: <?= htmlspecialchars($quotation->contact_person) ?><br><?php endif; ?>
            <span style="white-space:pre-line;"><?= htmlspecialchars($quotation->address ?? '') ?></span><br>
            <?php if (!empty($quotation->mobile)): ?>Mobile: <?= htmlspecialchars($quotation->mobile) ?><br><?php endif; ?>
            <?php if (!empty($quotation->email)): ?><?= htmlspecialchars($quotation->email) ?><?php endif; ?>
        </td>
    </tr>
</table>

<table class="items-table" style="margin-top:10px;">
    <thead>
        <tr>
            <th style="width:6%;">Sr.No</th>
            <th>Particulars</th>
            <th style="width:12%;text-align:right;">Qty</th>
            <th style="width:16%;text-align:right;">Rate</th>
            <th style="width:16%;text-align:right;">Amount</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $it): ?>
        <tr>
            <td><?= $it->sr_no ?></td>
            <td><?= htmlspecialchars($it->particulars) ?></td>
            <td style="text-align:right;"><?= number_format($it->qty, 2) ?> <?= htmlspecialchars($it->unit ?? 'Nos') ?></td>
            <td style="text-align:right;">&#8377; <?= number_format($it->rate, 2) ?></td>
            <td style="text-align:right;">&#8377; <?= number_format($it->amount, 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<table style="margin-top:6px;">
    <tr>
        <td style="width:60%;"></td>
        <td style="width:40%;">
            <table class="totals-table">
                <tr><td>Sub Total</td><td style="text-align:right;">&#8377; <?= number_format($quotation->sub_total, 2) ?></td></tr>
                <tr><td>GST (<?= number_format($quotation->gst_percent, 2) ?>%)</td><td style="text-align:right;">&#8377; <?= number_format($quotation->gst_amount, 2) ?></td></tr>
                <tr class="grand"><td>Grand Total</td><td style="text-align:right;">&#8377; <?= number_format($quotation->grand_total, 2) ?></td></tr>
            </table>
        </td>
    </tr>
</table>

<?php if (!empty($quotation->terms_text)): ?>
<div style="font-size:9px;font-weight:bold;margin-top:8px;">Terms & Conditions</div>
<div class="terms-box"><?= htmlspecialchars($quotation->terms_text) ?></div>
<?php endif; ?>

<div class="sign-block">
    <?php if (!empty($quotation->signature_path) && file_exists('./' . $quotation->signature_path)): ?>
        <img src="<?= './' . $quotation->signature_path ?>" style="max-height:45px;"><br>
    <?php endif; ?>
    <?php if (!empty($quotation->stamp_path) && file_exists('./' . $quotation->stamp_path)): ?>
        <img src="<?= './' . $quotation->stamp_path ?>" style="max-height:65px;"><br>
    <?php endif; ?>
    <div style="font-weight:bold;">For <?= htmlspecialchars($quotation->company_name) ?></div>
    <div><?= htmlspecialchars($quotation->signatory_name ?? '') ?></div>
    <div style="font-size:8.5px;color:#555;">Authorized Signatory</div>
</div>

<div class="footer">
    Page {PAGENO} of {nbpg} &nbsp;|&nbsp; Printed on <?= date('d M Y, h:i A') ?>
</div>

</body>
</html>
