<!DOCTYPE html>
<?php
// FILE: application/views/sales_returns/print.php
$r = $return;
$s = $settings;
$company_logo = $s['company_logo'] ?? '';

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
?>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Refund Bill - <?= htmlspecialchars($r->return_number) ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 14px; color: #222; background: #f0f2f5; }
.page { background: #fff; position: relative; width: 210mm; min-height: 297mm; margin: 20px auto; padding: 10mm 12mm; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 4px; }

.inv-header { display: flex; gap: 15px; align-items: center; border-bottom: 2px solid #b91c1c; padding-bottom: 8px; margin-bottom: 8px; }
.company-name { font-size: 20px; font-weight: bold; color: #1a1a1a; }
.company-sub  { font-size: 13px; color: #555; line-height: 1.5; margin-top: 3px; }
.inv-title    { text-align: right; flex-shrink: 0; }
.inv-title h2 { font-size: 20px; color: #b91c1c; text-transform: uppercase; letter-spacing: 2px; font-weight: 900; }
.inv-title p  { font-size: 12px; color: #555; margin-top: 2px; }

.inv-meta { display: flex; gap: 8px; margin-bottom: 8px; }
.meta-box { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 6px 10px; font-size: 13px; }
.meta-box span { font-weight: bold; }
.meta-box.highlight { border-color: #b91c1c; background: #fff5f5; }
.meta-box.highlight span { color: #b91c1c; }

.against-bar { background: #fff3cd; border: 1px solid #ffc107; border-left: 5px solid #ffc107; border-radius: 4px; padding: 7px 12px; margin-bottom: 8px; font-size: 13px; }
.against-bar strong { color: #856404; font-size: 14px; }

.parties { display: flex; gap: 8px; margin-bottom: 8px; }
.party-box { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 8px 10px; }
.party-box h4 { font-size: 11px; text-transform: uppercase; color: #888; letter-spacing: .5px; margin-bottom: 4px; }
.party-box .name { font-size: 15px; font-weight: bold; margin-bottom: 2px; }
.party-box p { font-size: 13px; line-height: 1.5; color: #444; }

.reason-box { background: #fff9e6; border-left: 4px solid #e67e22; padding: 6px 10px; margin-bottom: 8px; font-size: 12px; border-radius: 0 4px 4px 0; }

.items-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
.items-table th { background: #b91c1c; color: #fff; padding: 6px 5px; font-size: 12px; text-align: center; border: 1px solid #b91c1c; }
.items-table td { padding: 5px 5px; font-size: 13px; border: 1px solid #ddd; vertical-align: top; }
.items-table tr:nth-child(even) td { background: #fdf2f2; }
.items-table td.right { text-align: right; }
.items-table td.center { text-align: center; }

.totals-section { display: flex; justify-content: flex-end; margin-top: 4px; }
.totals-table { width: 280px; border-collapse: collapse; font-size: 13px; }
.totals-table td { padding: 4px 8px; border: 1px solid #ddd; }
.totals-table td:last-child { text-align: right; font-weight: bold; }
.totals-table .grand { background: #b91c1c; color: #fff; font-size: 15px; }
.totals-table .grand td { padding: 6px 8px; }

.gst-table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 12px; }
.gst-table th { background: #f0f0f0; border: 1px solid #ccc; padding: 4px 6px; text-align: center; }
.gst-table td { border: 1px solid #ddd; padding: 4px 6px; text-align: center; }

.amt-words { border: 1px solid #ddd; border-radius: 4px; padding: 6px 10px; margin: 8px 0; font-size: 13px; }
.amt-words span { font-weight: bold; }

.inv-footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 12px; border-top: 1px solid #ddd; padding-top: 8px; font-size: 12px; }
.terms { flex: 1; color: #666; }
.signature { text-align: right; font-size: 11px; }
.signature p { margin-top: 36px; border-top: 1px solid #333; padding-top: 4px; color: #333; }

@media print {
    body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
    .page { width: 100%; min-height: auto; margin: 0; padding: 6mm 8mm; box-shadow: none; border-radius: 0; }
    @page { size: A4 portrait; margin: 10mm; }
}
</style>
</head>
<body>

<div class="no-print" style="background:#f5f5f5;padding:10px 20px;display:flex;gap:10px;align-items:center;border-bottom:1px solid #ddd;">
    <button onclick="window.print()" style="padding:6px 18px;background:#b91c1c;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;">&#128438; Print</button>
    <span style="color:#666;font-size:12px;">Refund Bill: <strong><?= htmlspecialchars($r->return_number) ?></strong></span>
</div>

<div class="page">

    <!-- HEADER -->
    <div class="inv-header">
        <?php if (!empty($company_logo) && file_exists('./' . $company_logo)): ?>
        <div style="flex-shrink:0;">
            <img src="<?= base_url($company_logo) ?>" alt="Logo" style="max-height:70px;max-width:150px;object-fit:contain;">
        </div>
        <?php endif; ?>
        <div style="flex-grow:1;">
            <div class="company-name"><?= htmlspecialchars($s['company_name'] ?? '') ?></div>
            <div class="company-sub">
                <?= htmlspecialchars($s['company_address'] ?? '') ?><br>
                <?php if (!empty($s['company_phone'])): ?>Ph: <?= htmlspecialchars($s['company_phone']) ?> &nbsp;|&nbsp; <?php endif; ?>
                <?php if (!empty($s['gstin'])): ?>GSTIN: <strong><?= htmlspecialchars($s['gstin']) ?></strong><?php endif; ?>
            </div>
        </div>
        <div class="inv-title">
            <h2>&#8617; REFUND BILL</h2>
            <p><?= $r->is_interstate ? 'Interstate (IGST)' : 'Intrastate (CGST+SGST)' ?></p>
            <p style="font-size:10px;color:#888;margin-top:3px;">Credit Note / Sales Return</p>
        </div>
    </div>

    <!-- META ROW -->
    <div class="inv-meta">
        <div class="meta-box highlight"><label style="font-size:10px;color:#888;display:block;">Refund Bill No</label><span><?= htmlspecialchars($r->return_number) ?></span></div>
        <div class="meta-box"><label style="font-size:10px;color:#888;display:block;">Date</label><span><?= date('d-M-Y', strtotime($r->return_date)) ?></span></div>
        <div class="meta-box"><label style="font-size:10px;color:#888;display:block;">Refund Method</label><span><?= ($r->refund_method ?? 'balance') === 'wallet' ? 'Wallet Credit' : (($r->refund_method ?? 'balance') === 'cash' ? 'Cash Refund' : 'Adjust Outstanding') ?></span></div>
    </div>

    <!-- AGAINST BILL BAR -->
    <?php if ($r->original_invoice_no): ?>
    <div class="against-bar">
        <i>&#8617;</i> Against Bill No: <strong><?= htmlspecialchars($r->original_invoice_no) ?></strong>
        <?php if ($r->original_invoice_date): ?>
        &nbsp;&nbsp;|&nbsp;&nbsp; Bill Date: <strong><?= date('d-M-Y', strtotime($r->original_invoice_date)) ?></strong>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- REASON -->
    <?php if ($r->reason): ?>
    <div class="reason-box"><strong>Return Reason:</strong> <?= htmlspecialchars($r->reason) ?></div>
    <?php endif; ?>

    <!-- PARTIES -->
    <div class="parties">
        <div class="party-box">
            <h4>Refund To (Customer)</h4>
            <div class="name"><?= htmlspecialchars($r->customer_name) ?></div>
            <p>
                <?= htmlspecialchars($r->customer_address ?? '') ?>
                <?= $r->customer_city ? ', '.htmlspecialchars($r->customer_city) : '' ?>
                <?php if (!empty($r->customer_state)): ?><br>State: <?= htmlspecialchars($r->customer_state) ?><?php endif; ?>
                <?php if (!empty($r->customer_gst)): ?><br>GSTIN: <strong><?= htmlspecialchars($r->customer_gst) ?></strong><?php endif; ?>
            </p>
        </div>
        <div class="party-box">
            <h4>Refunded By (Seller)</h4>
            <div class="name"><?= htmlspecialchars($s['company_name'] ?? '') ?></div>
            <p>
                <?= htmlspecialchars($s['company_address'] ?? '') ?><br>
                <?php if (!empty($s['gstin'])): ?>GSTIN: <strong><?= htmlspecialchars($s['gstin']) ?></strong><br><?php endif; ?>
                State: <?= htmlspecialchars($s['company_state'] ?? '') ?>
            </p>
        </div>
    </div>

    <!-- ITEMS TABLE -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:30%">Product Description</th>
                <th style="width:9%">HSN/SAC</th>
                <th style="width:6%">Qty</th>
                <th style="width:6%">Unit</th>
                <th style="width:9%">Rate (₹)</th>
                <th style="width:9%">Taxable Amt</th>
                <?php if ($r->is_interstate): ?>
                <th style="width:9%">IGST</th>
                <?php else: ?>
                <th style="width:7%">CGST</th>
                <th style="width:7%">SGST</th>
                <?php endif; ?>
                <th style="width:10%">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($r->items as $i => $item): ?>
            <tr>
                <td class="center"><?= $i+1 ?></td>
                <td><strong><?= htmlspecialchars($item->product_name) ?></strong></td>
                <td class="center"><code><?= htmlspecialchars($item->hsn_code ?? '—') ?></code></td>
                <td class="center"><?= rtrim(rtrim((string)$item->qty, '0'), '.') ?></td>
                <td class="center"><?= htmlspecialchars($item->unit) ?></td>
                <td class="right">₹ <?= number_format($item->rate, 2) ?></td>
                <td class="right">₹ <?= number_format($item->taxable_amt, 2) ?></td>
                <?php if ($r->is_interstate): ?>
                <td class="right">₹ <?= number_format($item->igst_amount, 2) ?> <small>(<?= $item->igst_percent ?? $item->gst_percent ?>%)</small></td>
                <?php else:
                    $half = round($item->cgst_amount ?? ($item->gst_amount ?? 0)/2, 2);
                    $sgst = $item->sgst_amount ?? (($item->gst_amount ?? 0) - $half);
                ?>
                <td class="right">₹ <?= number_format($half, 2) ?> <small>(<?= ($item->cgst_percent ?? ($item->gst_percent/2)) ?>%)</small></td>
                <td class="right">₹ <?= number_format($sgst, 2) ?> <small>(<?= ($item->sgst_percent ?? ($item->gst_percent/2)) ?>%)</small></td>
                <?php endif; ?>
                <td class="right"><strong>₹ <?= number_format($item->total, 2) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- GST SUMMARY -->
    <table class="gst-table">
        <thead>
            <tr>
                <th>HSN/SAC</th>
                <th>Taxable Value</th>
                <?php if ($r->is_interstate): ?>
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
        $gst_map = [];
        foreach ($r->items as $item) {
            $key = ($item->gst_percent ?? 0) . '_' . ($item->hsn_code ?? '');
            if (!isset($gst_map[$key])) $gst_map[$key] = ['hsn'=>$item->hsn_code ?? '','rate'=>$item->gst_percent ?? 0,'taxable'=>0,'cgst'=>0,'sgst'=>0,'igst'=>0];
            $gst_map[$key]['taxable'] += $item->taxable_amt;
            $gst_map[$key]['cgst']    += $item->cgst_amount ?? 0;
            $gst_map[$key]['sgst']    += $item->sgst_amount ?? 0;
            $gst_map[$key]['igst']    += $item->igst_amount ?? 0;
        }
        foreach ($gst_map as $g):
            $half = round($g['cgst'] ?: $g['igst']/2, 2);
        ?>
        <tr>
            <td><?= htmlspecialchars($g['hsn']) ?></td>
            <td>₹ <?= number_format($g['taxable'], 2) ?></td>
            <?php if ($r->is_interstate): ?>
            <td><?= $g['rate'] ?>%</td><td>₹ <?= number_format($g['igst'], 2) ?></td>
            <?php else: ?>
            <td><?= $g['rate']/2 ?>%</td><td>₹ <?= number_format($g['cgst'], 2) ?></td>
            <td><?= $g['rate']/2 ?>%</td><td>₹ <?= number_format($g['sgst'], 2) ?></td>
            <?php endif; ?>
            <td><strong>₹ <?= number_format($g['cgst'] + $g['sgst'] + $g['igst'], 2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- TOTALS -->
    <div class="totals-section">
        <table class="totals-table">
            <tr><td>Subtotal</td><td>₹ <?= number_format($r->subtotal, 2) ?></td></tr>
            <?php if ($r->discount > 0): ?>
            <tr><td>Discount</td><td>- ₹ <?= number_format($r->discount, 2) ?></td></tr>
            <?php endif; ?>
            <tr><td>Taxable Amount</td><td>₹ <?= number_format($r->taxable_amount, 2) ?></td></tr>
            <?php if ($r->is_interstate): ?>
            <tr><td>IGST</td><td>₹ <?= number_format($r->igst_amount, 2) ?></td></tr>
            <?php else: ?>
            <tr><td>CGST</td><td>₹ <?= number_format($r->cgst_amount, 2) ?></td></tr>
            <tr><td>SGST</td><td>₹ <?= number_format($r->sgst_amount, 2) ?></td></tr>
            <?php endif; ?>
            <tr class="grand"><td>REFUND TOTAL</td><td>₹ <?= number_format($r->total_amount, 2) ?></td></tr>
        </table>
    </div>

    <!-- AMOUNT IN WORDS -->
    <div class="amt-words">
        Refund Amount in words: <span>Rupees <?= inr_words($r->total_amount) ?> Only</span>
    </div>

    <!-- FOOTER -->
    <div class="inv-footer">
        <div class="terms">
            <strong>Note:</strong> This is a Refund Bill (Credit Note) against original Bill No.
            <strong><?= htmlspecialchars($r->original_invoice_no ?: 'N/A') ?></strong>
            <?php if ($r->original_invoice_date): ?>dated <?= date('d-M-Y', strtotime($r->original_invoice_date)) ?><?php endif; ?>.<br>
            <span style="font-size:11px;color:#888;">For GSTR-1 → Section 9B (Credit Notes – Registered) / 9C (Unregistered).</span>
        </div>
        <div class="signature" style="position:relative;">
            <strong>For <?= htmlspecialchars($s['company_name'] ?? '') ?></strong>
            <div style="position:absolute;right:14px;bottom:4px;pointer-events:none;z-index:10;opacity:0.85;transform:rotate(-10deg);">
                <div style="width:64px;height:64px;border:2.5px solid #b91c1c;border-radius:50%;color:#b91c1c;display:flex;align-items:center;justify-content:center;text-align:center;">
                    <span style="font-weight:800;font-size:8px;line-height:1.05;text-transform:uppercase;padding:4px;"><?= htmlspecialchars($s['company_name'] ?? '') ?></span>
                </div>
            </div>
            <p>Authorised Signatory</p>
        </div>
    </div>

</div>

<div class="no-print" style="text-align:center;padding:15px;">
    <button onclick="window.print()" style="padding:8px 24px;background:#b91c1c;color:white;border:none;border-radius:4px;cursor:pointer;font-size:13px;">&#128438; Print / Save PDF</button>
    <button onclick="window.close()" style="padding:8px 20px;background:#666;color:white;border:none;border-radius:4px;cursor:pointer;margin-left:8px;">Close</button>
</div>
<script>window.onload = function() { window.print(); };</script>
</body>
</html>
