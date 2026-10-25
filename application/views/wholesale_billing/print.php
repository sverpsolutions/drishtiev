<!DOCTYPE html>
<?php
if (!function_exists('inr_words')) {
    function inr_words($n) {
        $n = (int)round($n);
        $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        if ($n === 0) return 'Zero';
        if ($n < 20)  return $ones[$n];
        if ($n < 100) return $tens[intval($n/10)] . ($n%10 ? ' '.$ones[$n%10] : '');
        if ($n < 1000) return $ones[intval($n/100)].' Hundred'.($n%100 ? ' '.inr_words($n%100) : '');
        if ($n < 100000) return inr_words(intval($n/1000)).' Thousand'.($n%1000 ? ' '.inr_words($n%1000) : '');
        if ($n < 10000000) return inr_words(intval($n/100000)).' Lakh'.($n%100000 ? ' '.inr_words($n%100000) : '');
        return inr_words(intval($n/10000000)).' Crore'.($n%10000000 ? ' '.inr_words($n%10000000) : '');
    }
}
$company_logo = $company['company_logo'] ?? '';
?>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Tax Invoice <?= $invoice->invoice_no ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 13px; color: #222; background: #f0f2f5; }
.page { background: #fff; width: 210mm; min-height: 297mm; margin: 20px auto; padding: 10mm 12mm; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 4px; position: relative; }

.inv-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 8px; }
.company-name { font-size: 20px; font-weight: bold; color: #1a1a1a; }
.company-sub  { font-size: 12px; color: #555; line-height: 1.5; margin-top: 3px; }
.inv-title    { text-align: right; flex-shrink: 0; }
.inv-title h2 { font-size: 18px; color: #1a1a1a; text-transform: uppercase; letter-spacing: 1px; }
.inv-title p  { font-size: 12px; color: #555; margin-top: 2px; }

.inv-meta { display: flex; gap: 8px; margin: 8px 0; }
.meta-box { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 5px 8px; font-size: 12px; }
.meta-box span { font-weight: bold; }

.parties { display: flex; gap: 8px; margin: 8px 0; }
.party-box { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 8px 10px; }
.party-box h4 { font-size: 10px; text-transform: uppercase; color: #888; letter-spacing: .5px; margin-bottom: 4px; }
.party-box .name { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
.party-box p { font-size: 12px; line-height: 1.5; color: #444; }

.items-table { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 11.5px; }
.items-table th { background: #f5f5f5; border: 1px solid #ccc; padding: 5px 6px; text-align: left; font-size: 10px; text-transform: uppercase; color: #444; }
.items-table td { border: 1px solid #ddd; padding: 4px 6px; vertical-align: top; }
.items-table .right { text-align: right; }
.items-table .center { text-align: center; }

.gst-table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 11px; }
.gst-table th { background: #f0f0f0; border: 1px solid #ccc; padding: 4px 6px; text-align: right; font-size: 10px; }
.gst-table td { border: 1px solid #ddd; padding: 3px 6px; text-align: right; }

.totals-table { margin-left: auto; border-collapse: collapse; font-size: 12px; }
.totals-table td { padding: 3px 10px; }
.totals-table td:last-child { text-align: right; font-weight: bold; }
.totals-table .grand td { font-size: 15px; font-weight: bold; border-top: 2px solid #333; padding-top: 6px; }

.amt-words { font-size: 11px; margin: 8px 0; padding: 5px 8px; background: #f9f9f9; border: 1px solid #eee; border-radius: 3px; }
.amt-words span { font-weight: bold; }

.inv-footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 12px; border-top: 1px solid #ddd; padding-top: 8px; }
.terms { font-size: 9px; color: #555; max-width: 340px; }
.signature { text-align: center; font-size: 11px; }
.signature p { margin-top: 40px; border-top: 1px dotted #666; display: inline-block; width: 120px; padding-top: 3px; }

.stamp { position: absolute; top: 40%; left: 50%; transform: translate(-50%,-50%) rotate(-30deg); font-size: 60px; color: rgba(200,0,0,0.12); font-weight: 900; text-transform: uppercase; pointer-events: none; z-index: 1; }

.einvoice-block { text-align: center; max-width: 150px; }
.einvoice-block img { display: block; margin: 0 auto 3px; }

@media print {
    body { background: #fff; }
    .page { margin: 0; padding: 8mm 10mm; box-shadow: none; border-radius: 0; width: auto; min-height: auto; }
    .no-print { display: none !important; }
}
</style>
</head>
<body>
<div class="no-print" style="text-align:center; padding:10px; background:#333; color:#fff; font-size:13px;">
    <button onclick="window.print()" style="background:#1a73e8; color:#fff; border:none; padding:8px 24px; border-radius:4px; cursor:pointer; font-size:14px; margin-right:10px;">
        <strong>Print Invoice</strong>
    </button>
    <a href="<?= base_url('wholesale_billing/view/' . $invoice->id) ?>" style="color:#aaa; text-decoration:none; font-size:12px;">Back to Invoice</a>
</div>

<div class="page">
    <?php if (in_array($invoice->status, ['cancelled','paid','partial'])): ?>
    <div class="stamp"><?= strtoupper($invoice->status) ?></div>
    <?php endif; ?>

    <!-- HEADER -->
    <div class="inv-header" style="display: flex; gap: 15px; align-items: center;">
        <?php if (!empty($company_logo) && file_exists('./' . $company_logo)): ?>
        <div style="flex-shrink: 0;">
            <img src="<?= base_url($company_logo) ?>" alt="Logo" style="max-height: 70px; max-width: 150px; object-fit: contain;">
        </div>
        <?php endif; ?>
        <div style="flex-grow: 1;">
            <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? '') ?></div>
            <div class="company-sub">
                <?= htmlspecialchars($company['company_address'] ?? '') ?><br>
                <?php if (!empty($company['company_phone'])): ?>Ph: <?= htmlspecialchars($company['company_phone']) ?><?php endif; ?>
                <?php if (!empty($company['company_email'])): ?>&nbsp;|&nbsp; <?= htmlspecialchars($company['company_email']) ?><?php endif; ?><br>
                <?php if (!empty($company['company_gstin'])): ?>GSTIN: <strong><?= htmlspecialchars($company['company_gstin']) ?></strong> &nbsp;|&nbsp;<?php endif; ?>
                State: <?= htmlspecialchars($company['company_state'] ?? '') ?> (<?= htmlspecialchars($company['company_state_code'] ?? '') ?>)
                <?php if (!empty($company['company_pan'])): ?>&nbsp;|&nbsp; PAN: <?= htmlspecialchars($company['company_pan']) ?><?php endif; ?>
            </div>
        </div>
        <div class="inv-title">
            <h2>Tax Invoice</h2>
            <p>Wholesale &middot; <?= $invoice->is_interstate ? 'Interstate (IGST)' : 'Intrastate (CGST+SGST)' ?></p>
        </div>
    </div>

    <!-- META -->
    <div class="inv-meta">
        <div class="meta-box">Invoice No: <span><?= htmlspecialchars($invoice->invoice_no) ?></span></div>
        <div class="meta-box">Date: <span><?= date('d-M-Y', strtotime($invoice->invoice_date)) ?></span></div>
        <div class="meta-box">Payment: <span><?= ucfirst($invoice->payment_mode ?? 'credit') ?></span></div>
        <div class="meta-box">Status: <span><?= strtoupper($invoice->status) ?></span></div>
    </div>

    <!-- PARTIES -->
    <div class="parties">
        <div class="party-box">
            <h4>Bill To (Buyer)</h4>
            <div class="name"><?= htmlspecialchars($customer->name) ?></div>
            <p>
                <?php if (!empty($customer->address)): ?><?= htmlspecialchars($customer->address) ?><br><?php endif; ?>
                <?php if (!empty($customer->phone)): ?>Ph: <?= htmlspecialchars($customer->phone) ?><br><?php endif; ?>
                <?php if (!empty($customer->gst_number)): ?>GSTIN: <strong><?= htmlspecialchars($customer->gst_number) ?></strong><br><?php endif; ?>
                <?php if (!empty($customer->state)): ?>State: <?= htmlspecialchars($customer->state) ?><?php endif; ?>
            </p>
        </div>
        <?php if (!empty($invoice->ship_to_different)): ?>
        <div class="party-box">
            <h4>Ship To (Delivery Address)</h4>
            <div class="name"><?= htmlspecialchars($invoice->ship_name ?? '') ?></div>
            <p>
                <?= htmlspecialchars($invoice->ship_address ?? '') ?><br>
                <?= htmlspecialchars($invoice->ship_place ?? '') ?> - <?= htmlspecialchars($invoice->ship_pincode ?? '') ?><br>
                <?php if (!empty($invoice->ship_gstin)): ?>GSTIN: <strong><?= htmlspecialchars($invoice->ship_gstin) ?></strong><br><?php endif; ?>
                State: <?= htmlspecialchars(class_exists('GstAddress') ? GstAddress::state_name($invoice->ship_state_code) : '') ?> | Code: <?= htmlspecialchars($invoice->ship_state_code ?? '') ?>
            </p>
        </div>
        <?php endif; ?>
        <div class="party-box">
            <h4>Sold By (Supplier)</h4>
            <div class="name"><?= htmlspecialchars($company['company_name'] ?? '') ?></div>
            <p>
                <?= htmlspecialchars($company['company_address'] ?? '') ?><br>
                GSTIN: <strong><?= htmlspecialchars($company['company_gstin'] ?? '') ?></strong><br>
                State: <?= htmlspecialchars($company['company_state'] ?? '') ?> | Code: <?= htmlspecialchars($company['company_state_code'] ?? '') ?>
            </p>
        </div>
    </div>

    <!-- ITEMS -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:24%">Description</th>
                <th style="width:9%">HSN/SAC</th>
                <th style="width:6%">Qty</th>
                <th style="width:8%">Rate</th>
                <th style="width:7%">Disc</th>
                <th style="width:10%">Taxable</th>
                <th style="width:5%">GST%</th>
                <?php if ($invoice->is_interstate): ?>
                <th style="width:9%">IGST</th>
                <?php else: ?>
                <th style="width:7%">CGST</th>
                <th style="width:7%">SGST</th>
                <?php endif; ?>
                <th style="width:10%">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $i => $item): ?>
            <?php
            $taxable = $item->taxable_amt ?? ($item->qty * $item->rate - ($item->discount ?? 0));
            $gst_amt = $item->gst_amount ?? 0;
            $half_gst = round($gst_amt / 2, 2);
            ?>
            <tr>
                <td class="center"><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars(strtoupper($item->product_name)) ?></strong></td>
                <td class="center"><code><?= htmlspecialchars($item->hsn_code ?? '—') ?></code></td>
                <td class="center"><?= (float)$item->qty ?> <?= htmlspecialchars($item->unit ?? '') ?></td>
                <td class="right"><?= number_format($item->rate, 2) ?></td>
                <td class="right"><?= number_format($item->discount ?? 0, 2) ?></td>
                <td class="right"><?= number_format($taxable, 2) ?></td>
                <td class="center"><?= (float)($item->gst_percent ?? 0) ?>%</td>
                <?php if ($invoice->is_interstate): ?>
                <td class="right"><?= number_format($gst_amt, 2) ?></td>
                <?php else: ?>
                <td class="right"><?= number_format($half_gst, 2) ?></td>
                <td class="right"><?= number_format($gst_amt - $half_gst, 2) ?></td>
                <?php endif; ?>
                <td class="right"><strong><?= number_format($item->total ?? 0, 2) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- GST SUMMARY -->
    <table class="gst-table">
        <thead>
            <tr>
                <th style="text-align:left;">HSN/SAC</th>
                <th>Taxable Value</th>
                <?php if ($invoice->is_interstate): ?>
                <th>IGST Rate</th><th>IGST Amt</th>
                <?php else: ?>
                <th>CGST Rate</th><th>CGST Amt</th>
                <th>SGST Rate</th><th>SGST Amt</th>
                <?php endif; ?>
                <th>Total Tax</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $gst_groups = [];
        foreach ($items as $item) {
            $key = ($item->gst_percent ?? 0) . '_' . ($item->hsn_code ?? '');
            if (!isset($gst_groups[$key])) $gst_groups[$key] = ['hsn'=>$item->hsn_code,'rate'=>$item->gst_percent ?? 0,'taxable'=>0,'gst'=>0];
            $gst_groups[$key]['taxable'] += (float)($item->taxable_amt ?? ($item->qty * $item->rate - ($item->discount ?? 0)));
            $gst_groups[$key]['gst']     += (float)($item->gst_amount ?? 0);
        }
        foreach ($gst_groups as $g):
            $half = round($g['gst']/2, 2);
        ?>
        <tr>
            <td style="text-align:left;"><?= htmlspecialchars($g['hsn'] ?? '—') ?></td>
            <td>₹ <?= number_format($g['taxable'], 2) ?></td>
            <?php if ($invoice->is_interstate): ?>
            <td><?= $g['rate'] ?>%</td><td>₹ <?= number_format($g['gst'], 2) ?></td>
            <?php else: ?>
            <td><?= $g['rate']/2 ?>%</td><td>₹ <?= number_format($half, 2) ?></td>
            <td><?= $g['rate']/2 ?>%</td><td>₹ <?= number_format($g['gst']-$half, 2) ?></td>
            <?php endif; ?>
            <td><strong>₹ <?= number_format($g['gst'], 2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- TOTALS -->
    <div style="display:flex; justify-content:flex-end; margin-top:6px;">
        <?php $total_qty = 0; foreach ($items as $_q) { $total_qty += (float)$_q->qty; } ?>
        <table class="totals-table">
            <tr><td>Subtotal (Qty: <?= $total_qty == floor($total_qty) ? (int)$total_qty : $total_qty ?>)</td><td>₹ <?= number_format($invoice->subtotal ?? $invoice->taxable_amount, 2) ?></td></tr>
            <?php if ((float)($invoice->discount ?? 0) > 0): ?>
            <tr><td>Discount</td><td>- ₹ <?= number_format($invoice->discount, 2) ?></td></tr>
            <?php endif; ?>
            <tr><td>Taxable Amount</td><td>₹ <?= number_format($invoice->taxable_amount, 2) ?></td></tr>
            <?php if ($invoice->is_interstate): ?>
            <tr><td>IGST</td><td>₹ <?= number_format($invoice->igst_amount, 2) ?></td></tr>
            <?php else: ?>
            <tr><td>CGST</td><td>₹ <?= number_format($invoice->cgst_amount, 2) ?></td></tr>
            <tr><td>SGST</td><td>₹ <?= number_format($invoice->sgst_amount, 2) ?></td></tr>
            <?php endif; ?>
            <?php if ((float)($invoice->round_off ?? 0) != 0): ?>
            <tr><td>Round Off</td><td><?= $invoice->round_off > 0 ? '+' : '-' ?> ₹ <?= number_format(abs($invoice->round_off), 2) ?></td></tr>
            <?php endif; ?>
            <tr class="grand"><td>Grand Total</td><td>₹ <?= number_format($invoice->total_amount, 2) ?></td></tr>
            <tr><td>Paid</td><td>₹ <?= number_format($invoice->paid_amount, 2) ?></td></tr>
            <?php if ((float)$invoice->due_amount > 0): ?>
            <tr><td>Balance Due</td><td style="color:#c00;">₹ <?= number_format($invoice->due_amount, 2) ?></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Amount in words -->
    <div class="amt-words">
        Amount in words: <span>Rupees <?= inr_words($invoice->total_amount) ?> Only</span>
    </div>

    <?php if (!empty($invoice->notes)): ?>
    <div style="font-size:11px; margin:6px 0; padding:5px 8px; border:1px solid #ddd; background:#f9f9f9;">
        <strong>Remarks:</strong> <?= nl2br(htmlspecialchars($invoice->notes)) ?>
    </div>
    <?php endif; ?>

    <!-- Transport details -->
    <?php if (!empty($invoice->vehicle_no) || !empty($invoice->transporter_name)): ?>
    <div style="font-size:11px; margin:6px 0; padding:5px 8px; border:1px solid #ddd; background:#f9f9f9;">
        <strong>Transport:</strong>
        <?php $modes = [1=>'Road',2=>'Rail',3=>'Air',4=>'Ship']; ?>
        Mode: <?= $modes[$invoice->transport_mode ?? 1] ?? 'Road' ?>
        <?php if (!empty($invoice->vehicle_no)): ?>&nbsp;|&nbsp; Vehicle: <?= htmlspecialchars($invoice->vehicle_no) ?> (<?= $invoice->vehicle_type === 'O' ? 'ODC' : 'Regular' ?>)<?php endif; ?>
        <?php if (!empty($invoice->transporter_name)): ?>&nbsp;|&nbsp; Transporter: <?= htmlspecialchars($invoice->transporter_name) ?><?php endif; ?>
        <?php if (!empty($invoice->transporter_id)): ?> (<?= htmlspecialchars($invoice->transporter_id) ?>)<?php endif; ?>
        <?php if (!empty($invoice->transport_distance)): ?>&nbsp;|&nbsp; Distance: <?= $invoice->transport_distance ?> KM<?php endif; ?>
        <?php if (!empty($invoice->transport_doc_no)): ?>&nbsp;|&nbsp; Doc: <?= htmlspecialchars($invoice->transport_doc_no) ?><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($invoice->ewb_no) && ($invoice->ewb_status ?? '') === 'generated'): ?>
    <div style="font-size:11px; margin:6px 0; padding:5px 8px; border:1px solid #28a745; background:#f0fff0; border-radius:3px;">
        <strong>E-Way Bill:</strong> <?= htmlspecialchars($invoice->ewb_no) ?>
        <?php if (!empty($invoice->ewb_date)): ?>&nbsp;|&nbsp; Date: <?= date('d-M-Y H:i', strtotime($invoice->ewb_date)) ?><?php endif; ?>
        <?php if (!empty($invoice->ewb_valid_until)): ?>&nbsp;|&nbsp; Valid Until: <?= date('d-M-Y H:i', strtotime($invoice->ewb_valid_until)) ?><?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- FOOTER -->
    <div class="inv-footer">
        <div class="terms">
            <strong>Terms &amp; Conditions:</strong><br>
            <?= htmlspecialchars($company['invoice_terms'] ?? 'Goods once sold will not be taken back.') ?>
        </div>

        <?php if (($invoice->einvoice_status ?? '') === 'generated' && !empty($invoice->einvoice_irn)): ?>
        <div class="einvoice-block">
            <?php if (!empty($invoice->einvoice_qr_data)): ?>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&qzone=2&ecc=L&data=<?= rawurlencode($invoice->einvoice_qr_data) ?>" style="width:92px; height:92px;" alt="e-Invoice QR">
            <?php endif; ?>
            <div style="font-size:7px; color:#444; word-break:break-all; margin-top:3px;"><strong>IRN:</strong> <?= htmlspecialchars($invoice->einvoice_irn) ?></div>
            <?php if (!empty($invoice->einvoice_ack_no)): ?>
            <div style="font-size:7px; color:#666;">Ack No: <?= htmlspecialchars($invoice->einvoice_ack_no) ?><?php if (!empty($invoice->einvoice_ack_date)): ?> | <?= date('d-M-Y', strtotime($invoice->einvoice_ack_date)) ?><?php endif; ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="signature" style="position:relative;">
            <strong>For <?= htmlspecialchars($company['company_name'] ?? '') ?></strong>
            <div style="position:absolute; right:14px; bottom:4px; pointer-events:none; z-index:10; opacity:0.85; transform:rotate(-10deg);">
                <div style="width:64px; height:64px; border:2.5px solid #1a5fb4; border-radius:50%; color:#1a5fb4; display:flex; align-items:center; justify-content:center; text-align:center;">
                    <span style="font-weight:800; font-size:8px; line-height:1.05; text-transform:uppercase; padding:4px;"><?= htmlspecialchars($company['company_name'] ?? '') ?></span>
                </div>
            </div>
            <p>Authorised Signatory</p>
        </div>
    </div>
</div>

</body>
</html>
