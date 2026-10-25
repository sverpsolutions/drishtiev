<?php
// ============================================================
// FILE: application/views/billing/pdf_template.php
// Loaded by Billing::pdf() — rendered to string, passed to mPDF
// All CSS must be inline or in <style> — no external files
// ============================================================
$printer_type = $company['printer_type'] ?? 'A4';
$print_show_tax = (!isset($company['print_show_tax']) || (int)$company['print_show_tax'] !== 0) && (!isset($company['enable_gst']) || (int)$company['enable_gst'] !== 0);
$print_show_pending = !isset($company['print_show_pending']) || (int)$company['print_show_pending'] !== 0;
$company_logo = $company['company_logo'] ?? '';

// Simple INR words helper
if (!function_exists('inr_words_pdf')) {
    function inr_words_pdf($n) {
        $n = (int)round($n);
        $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        if ($n === 0) return 'Zero';
        if ($n < 20) return $ones[$n];
        if ($n < 100) return $tens[intval($n/10)].($n%10?' '.$ones[$n%10]:'');
        if ($n < 1000) return $ones[intval($n/100)].' Hundred'.($n%100?' '.inr_words_pdf($n%100):'');
        if ($n < 100000) return inr_words_pdf(intval($n/1000)).' Thousand'.($n%1000?' '.inr_words_pdf($n%1000):'');
        if ($n < 10000000) return inr_words_pdf(intval($n/100000)).' Lakh'.($n%100000?' '.inr_words_pdf($n%100000):'');
        return inr_words_pdf(intval($n/10000000)).' Crore'.($n%10000000?' '.inr_words_pdf($n%10000000):'');
    }
}
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: dejavusans, sans-serif; font-size: 11px; color: #222; }

/* A4 Styles */
.header-table { width:100%; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 8px; }
.company-name  { font-size: 18px; font-weight: bold; color: #1a1a1a; }
.company-sub   { font-size: 10px; color: #555; line-height: 1.6; margin-top: 3px; }
.inv-title h2  { font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; text-align:right; }
.inv-title p   { font-size: 10px; color: #666; text-align:right; margin-top: 2px; }

.party-table { width:100%; margin: 8px 0; }
.party-box   { width:49%; vertical-align:top; border: 1px solid #ddd; padding: 7px 9px; border-radius: 3px; }
.party-label { font-size: 9px; text-transform: uppercase; color: #888; letter-spacing: .5px; margin-bottom: 3px; }
.party-name  { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
.party-info  { font-size: 10px; color: #444; line-height: 1.6; }

.meta-table { width:100%; margin-bottom: 8px; }
.meta-cell  { width:25%; border: 1px solid #ddd; padding: 5px 8px; font-size: 10px; }
.meta-label { color: #888; display:block; font-size: 9px; }
.meta-val   { font-weight: bold; }

.items { width:100%; border-collapse: collapse; margin-bottom: 5px; }
.items th { background: #333; color: #fff; padding: 5px 4px; font-size: 9.5px; text-align: center; border: 1px solid #444; }
.items td { padding: 4px 4px; font-size: 10px; border: 1px solid #ddd; vertical-align: top; }
.items tr:nth-child(even) td { background: #f9f9f9; }
.r { text-align: right; }
.c { text-align: center; }

.gst-table { width:100%; border-collapse: collapse; margin: 5px 0; font-size: 9.5px; }
.gst-table th { background: #f0f0f0; border: 1px solid #ccc; padding: 3px 5px; text-align: center; }
.gst-table td { border: 1px solid #ddd; padding: 3px 5px; text-align: center; }

.totals-wrap { text-align: right; margin-top: 4px; }
.totals-tbl  { display: inline-table; min-width: 250px; border-collapse: collapse; font-size: 10.5px; }
.totals-tbl td { padding: 3px 8px; border: 1px solid #ddd; }
.totals-tbl td:last-child { text-align: right; font-weight: bold; min-width: 90px; }
.grand-row td { background: #333; color: #fff; font-size: 12px; padding: 5px 8px; }
.due-row  td { color: <?= $invoice->due_amount > 0 ? '#c00' : '#080' ?>; }

.words-box { border: 1px solid #ddd; border-radius: 3px; padding: 5px 8px; margin: 6px 0; font-size: 10px; }

.footer-table { width:100%; margin-top: 10px; border-top: 1px solid #ddd; padding-top: 6px; }
.terms-col    { font-size: 9.5px; color: #666; }
.sig-col      { text-align: right; font-size: 10px; }
.sig-line     { margin-top: 32px; border-top: 1px solid #333; padding-top: 3px; }

.stamp { position: fixed; top: 80px; right: 30px; opacity: 0.1;
         font-size: 52px; font-weight: 900; text-transform: uppercase;
         transform: rotate(-35deg);
         color: <?= $invoice->status==='cancelled'?'#c00':($invoice->status==='paid'?'#080':'#c80') ?>;
         border: 5px solid currentColor; padding: 4px 14px; border-radius: 3px; }
</style>
</head>
<body>

<?php if (in_array($invoice->status,['paid','cancelled','partial'])): ?>
<div class="stamp"><?= strtoupper($invoice->status) ?></div>
<?php endif; ?>

<?php if ($printer_type === '3in' || $printer_type === '2in'): ?>
    <!-- ==================== THERMAL RECEIPT PDF LAYOUT ==================== -->
    <div style="width: 100%;">
        <!-- Centered Logo -->
        <?php if (!empty($company_logo) && file_exists('./' . $company_logo)): ?>
            <div style="text-align: center; margin-bottom: 5px;">
                <img src="<?= './' . $company_logo ?>" style="max-height: 42px; max-width: 100%; object-fit: contain;">
            </div>
        <?php endif; ?>

        <!-- Company Details -->
        <div style="text-align: center; margin-bottom: 5px; font-size: 9px; line-height: 1.3;">
            <div style="font-size: 12px; font-weight: bold; text-transform: uppercase;"><?= htmlspecialchars($company['company_name'] ?? 'View & Chew') ?></div>
            <div style="color: #333; margin-top: 2px;">
                <?= htmlspecialchars($company['company_address'] ?? '') ?><br>
                Ph: <?= htmlspecialchars($company['company_phone'] ?? '') ?>
                <?php if (!empty($company['company_email'])): ?><br>Email: <?= htmlspecialchars($company['company_email']) ?><?php endif; ?>
                <?php if (!empty($company['company_gstin'])): ?>
                    <br>GSTIN: <strong><?= htmlspecialchars($company['company_gstin'] ?? '') ?></strong>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bill Metadata -->
        <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 3px 0; margin-bottom: 5px; font-size: 8.5px; line-height: 1.35;">
            <div style="text-align: center; font-weight: bold; text-transform: uppercase; margin-bottom: 1px; font-size: 9.5px;">
                <?= $print_show_tax ? 'Tax Invoice' : 'Retail Bill' ?>
            </div>
            <table style="width: 100%; border: none; font-size: 8.5px; border-collapse: collapse;">
                <tr>
                    <td style="border:none; padding:1px 0;">Bill No: <strong><?= $invoice->invoice_no ?></strong></td>
                    <td style="text-align: right; border:none; padding:1px 0;">Date: <strong><?= date('d-M-y', strtotime($invoice->invoice_date)) ?></strong></td>
                </tr>
                <?php if (!empty($invoice->created_by_name)): ?>
                <tr>
                    <td colspan="2" style="border:none; padding:1px 0;">Cashier: <strong><?= htmlspecialchars($invoice->created_by_name) ?></strong></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td style="border:none; padding:1px 0;">Pay: <?= ucfirst($invoice->payment_mode) ?></td>
                    <td style="text-align: right; border:none; padding:1px 0;">Status: <?= strtoupper($invoice->status) ?></td>
                </tr>
            </table>
        </div>

        <!-- Buyer Info -->
        <?php if ($invoice->customer_name !== 'Cash' && $invoice->customer_name !== 'Cash Customer'): ?>
        <div style="font-size: 8.5px; margin-bottom: 5px; padding-bottom: 3px; border-bottom: 1px dashed #ccc; line-height: 1.3;">
            <strong>Bill To:</strong> <?= htmlspecialchars($invoice->customer_name) ?>
            <?php if (!empty($invoice->customer_phone)): ?><br>Ph: <?php 
                $cust_phone_disp = $invoice->customer_phone;
                if ((int)$invoice->is_delivery === 0) {
                    $len = strlen($cust_phone_disp);
                    if ($len > 4) {
                        $cust_phone_disp = str_repeat('X', $len - 4) . substr($cust_phone_disp, -4);
                    }
                }
                echo htmlspecialchars($cust_phone_disp);
            ?><?php endif; ?>
            <?php if ((int)$invoice->is_delivery !== 0 && !empty($invoice->customer_address)): ?><br>Add: <?= htmlspecialchars($invoice->customer_address) ?><?php endif; ?>
            <?php if (!empty($invoice->customer_gstin)): ?><br>GSTIN: <?= htmlspecialchars($invoice->customer_gstin) ?><?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Items Table -->
        <?php
        $pdf_items_total = array_sum(array_map(fn($it) => (float)$it->total, $items));
        $pdf_cd_ratio = ($invoice->cd_amount > 0 && $pdf_items_total > 0) ? ($invoice->total_amount / $pdf_items_total) : 1;
        ?>
        <table style="width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 5px;">
            <thead>
                <tr style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; font-weight: bold;">
                    <th style="text-align: left; padding: 3px 0;">Item Description</th>
                    <th style="text-align: right; padding: 3px 0; width: 65px;">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr style="border-bottom: 1px dotted #ddd;">
                    <td style="padding: 3px 0; line-height: 1.25;">
                        <div style="font-weight: bold;"><?= htmlspecialchars(strtoupper($item->product_name)) ?></div>
                        <div style="color: #444; font-size: 8px;">
                            <?php if (!empty($item->mrp) && $item->mrp > 0): ?>
                                <span style="font-size: 7px; color: #888;">MRP-<?= number_format($item->mrp, 0) ?></span>
                                <?php $pdf_sd = round((float)$item->mrp - (float)$item->rate, 2); if ($pdf_sd > 0): ?>
                                <span style="font-size: 7px; color: #15803d;"> S.D &#8377;<?= number_format($pdf_sd, 2) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?= $item->qty ?> <?= $item->unit ?> &times; ₹<?= number_format($item->rate, 2) ?>
                            <?php if ($item->discount > 0): ?>
                                (Disc: -₹<?= number_format($item->discount, 2) ?>)
                            <?php endif; ?>
                            <?php if ($print_show_tax): ?>
                                <br>
                                <span style="color: #666; font-size: 7.5px;">
                                    <?php if (!empty($item->hsn_code)): ?>HSN: <?= htmlspecialchars($item->hsn_code) ?><?php endif; ?>
                                    <?php if (!empty($item->hsn_code) && $item->gst_percent > 0): ?> | <?php endif; ?>
                                    <?php if ($item->gst_percent > 0): ?>GST: <?= $item->gst_percent ?>% (₹<?= number_format(round($item->gst_amount * $pdf_cd_ratio, 2), 2) ?>)<?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="text-align: right; padding: 3px 0; vertical-align: top; font-weight: bold;">
                        ₹<?= number_format(round($item->total * $pdf_cd_ratio, 2), 2) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals Block -->
        <?php $total_qty = 0; foreach ($items as $_qi) { $total_qty += (float)$_qi->qty; } ?>
        <table style="width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 5px; line-height: 1.35;">
            <tr><td style="padding: 1px 0;">Subtotal (Qty: <?= $total_qty == floor($total_qty) ? (int)$total_qty : $total_qty ?>)</td><td style="text-align: right; padding: 1px 0;">₹<?= number_format($invoice->subtotal, 2) ?></td></tr>
            <?php if ($invoice->discount > 0): ?>
            <tr><td style="padding: 1px 0;">Discount</td><td style="text-align: right; padding: 1px 0;">- ₹<?= number_format($invoice->discount, 2) ?></td></tr>
            <?php endif; ?>
            <?php if ($print_show_tax): ?>
            <tr><td style="padding: 1px 0;">Taxable Amount</td><td style="text-align: right; padding: 1px 0;">₹<?= number_format($invoice->taxable_amount, 2) ?></td></tr>
            <?php if ($invoice->is_interstate): ?>
            <tr><td style="padding: 1px 0;">IGST</td><td style="text-align: right; padding: 1px 0;">₹<?= number_format($invoice->igst_amount, 2) ?></td></tr>
            <?php else: ?>
            <tr><td style="padding: 1px 0;">CGST</td><td style="text-align: right; padding: 1px 0;">₹<?= number_format($invoice->cgst_amount, 2) ?></td></tr>
            <tr><td style="padding: 1px 0;">SGST</td><td style="text-align: right; padding: 1px 0;">₹<?= number_format($invoice->sgst_amount, 2) ?></td></tr>
            <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($invoice->cd_percent) && $invoice->cd_percent > 0): ?>
            <tr><td style="padding: 1px 0;">CD (<?= $invoice->cd_percent ?>%)</td><td style="text-align: right; padding: 1px 0; color:#c00;">- ₹<?= number_format($invoice->cd_amount, 2) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($invoice->round_off) && $invoice->round_off != 0): ?>
            <tr><td style="padding: 1px 0;">Round Off</td><td style="text-align: right; padding: 1px 0;"><?= $invoice->round_off > 0 ? '+' : '-' ?> ₹<?= number_format(abs($invoice->round_off), 2) ?></td></tr>
            <?php endif; ?>
            <tr style="font-weight: bold; font-size: 10px; border-top: 1px dashed #000; border-bottom: 1px dashed #000;">
                <td style="padding: 3px 0;">GRAND TOTAL</td>
                <td style="text-align: right; padding: 3px 0;">₹<?= number_format($invoice->total_amount, 2) ?></td>
            </tr>
            <?php if (isset($invoice->wallet_amount_used) && $invoice->wallet_amount_used > 0): ?>
            <tr><td style="padding: 1px 0; color:#080;">Wallet Used</td><td style="text-align: right; padding: 1px 0; color:#080;">- ₹<?= number_format($invoice->wallet_amount_used, 2) ?></td></tr>
            <?php endif; ?>
            <?php if (isset($invoice->wallet_amount_credited) && $invoice->wallet_amount_credited > 0): ?>
            <tr><td style="padding: 1px 0; color:#080;">Wallet Credited</td><td style="text-align: right; padding: 1px 0; color:#080;">₹<?= number_format($invoice->wallet_amount_credited, 2) ?></td></tr>
            <?php endif; ?>
            <?php if ($print_show_pending): ?>
            <?php
                $change_amt = round(
                    (float)($invoice->amount_tendered ?? $invoice->paid_amount)
                    - (float)$invoice->paid_amount
                    - (float)($invoice->wallet_amount_credited ?? 0),
                    2
                );
            ?>
            <?php if ($change_amt > 0.004): ?>
            <tr><td style="padding: 1px 0;">Cash Tendered</td><td style="text-align: right; padding: 1px 0;">₹<?= number_format($invoice->amount_tendered, 2) ?></td></tr>
            <tr style="font-weight: bold;"><td style="padding: 1px 0;">Change Returned</td><td style="text-align: right; padding: 1px 0;">₹<?= number_format($change_amt, 2) ?></td></tr>
            <?php endif; ?>
            <tr><td style="padding: 1px 0;">Paid Amount</td><td style="text-align: right; padding: 1px 0;">₹<?= number_format($invoice->paid_amount, 2) ?></td></tr>
            <?php if ($invoice->due_amount > 0): ?>
            <tr style="color: #c00; font-weight: bold;"><td style="padding: 1px 0;">Balance Due</td><td style="text-align: right; padding: 1px 0;">₹<?= number_format($invoice->due_amount, 2) ?></td></tr>
            <?php else: ?>
            <tr style="color: #080; font-weight: bold;"><td style="padding: 1px 0;">Balance Due</td><td style="text-align: right; padding: 1px 0;">₹0.00</td></tr>
            <?php endif; ?>
            <?php endif; ?>
        </table>

        <!-- GST Tax Breakdown -->
        <?php if ($print_show_tax): ?>
        <?php
        $gst_groups = [];
        foreach ($items as $item) {
            $key = $item->gst_percent . '_' . ($item->hsn_code ?? '');
            if (!isset($gst_groups[$key])) {
                $gst_groups[$key] = ['hsn'=>$item->hsn_code,'rate'=>$item->gst_percent,'taxable'=>0,'gst'=>0];
            }
            $gst_groups[$key]['taxable'] += round($item->taxable_amt * $pdf_cd_ratio, 2);
            $gst_groups[$key]['gst']     += round($item->gst_amount * $pdf_cd_ratio, 2);
        }
        if (!empty($gst_groups)):
        ?>
        <div style="font-size: 8px; border-bottom: 1px dashed #000; padding: 3px 0 5px 0; margin-bottom: 5px; line-height: 1.3;">
            <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 2px;">GST TAX SUMMARY</div>
            <?php foreach ($gst_groups as $g): $half = round($g['gst']/2, 2); ?>
                <div style="margin-bottom: 2px;">
                    HSN: <?= htmlspecialchars($g['hsn'] ?? '—') ?> | TxVal: ₹<?= number_format($g['taxable'], 2) ?><br>
                    <?php if ($invoice->is_interstate): ?>
                        IGST (<?= $g['rate'] ?>%): ₹<?= number_format($g['gst'], 2) ?>
                    <?php else: ?>
                        CGST (<?= $g['rate']/2 ?>%): ₹<?= number_format($half, 2) ?> | SGST (<?= $g['rate']/2 ?>%): ₹<?= number_format($g['gst']-$half, 2) ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Scan to Pay (UPI) -->
        <?php
        $upi_id = $company['company_upi_id'] ?? '';
        $due_amt = $invoice->due_amount;
        $has_upi = ($print_show_pending && !empty($upi_id) && $due_amt > 0);
        if ($has_upi): 
            $merchant_name = !empty($company['company_upi_name']) ? $company['company_upi_name'] : ($company['company_name'] ?? 'View & Chew');
            $upi_url = "upi://pay?pa=" . urlencode($upi_id) . "&pn=" . urlencode($merchant_name) . "&am=" . urlencode(number_format($due_amt, 2, '.', '')) . "&cu=INR&tn=" . urlencode($invoice->invoice_no);
        ?>
        <div style="text-align: center; margin: 6px auto; padding: 5px; border: 1px dashed #666; border-radius: 4px; background: #fafafa; width: 130px;">
            <div style="font-weight: bold; font-size: 7.5px; text-transform: uppercase; margin-bottom: 2px;">Scan to Pay</div>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=4&ecc=M&data=<?= urlencode($upi_url) ?>" style="width: 60px; height: 60px; margin-bottom: 2px;" />
            <div style="font-size: 7px; color: #555;">Amt: <strong>₹<?= number_format($due_amt, 2) ?></strong></div>
        </div>
        <?php endif; ?>

        <!-- Customer Portal Login QR -->
        <?php
        $cust_phone_raw = preg_replace('/\D/', '', $invoice->customer_phone ?? '');
        if (!empty($cust_phone_raw)):
            $portal_login_url = base_url('portal/login') . '?phone=' . urlencode($cust_phone_raw);
        ?>
        <div style="text-align: center; margin: 6px auto; padding: 5px; border: 1px dashed #666; border-radius: 4px; background: #fafafa; width: 130px;">
            <div style="font-weight: bold; font-size: 7.5px; text-transform: uppercase; margin-bottom: 2px;">Customer Portal Login</div>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&qzone=4&ecc=M&data=<?= urlencode($portal_login_url) ?>" style="width: 82px; height: 82px; margin-bottom: 2px;" />
            <div style="font-size: 7px; color: #555;">Scan to view bills &amp; order online<br>Login: Mobile No &middot; Pwd: Last 4 digits</div>
        </div>
        <?php endif; ?>

        <?php if (($invoice->einvoice_status ?? '') === 'generated' && !empty($invoice->einvoice_irn)): ?>
        <div style="text-align: center; margin: 6px auto; padding: 5px; border: 1px dashed #666; border-radius: 4px; background: #fafafa; width: 130px;">
            <?php if (!empty($invoice->einvoice_qr_data)): ?>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&qzone=2&ecc=L&data=<?= urlencode($invoice->einvoice_qr_data) ?>" style="width: 82px; height: 82px; margin-bottom: 2px;" />
            <?php endif; ?>
            <div style="font-size: 6.5px; color: #444; word-break: break-all;"><strong>IRN:</strong> <?= htmlspecialchars($invoice->einvoice_irn) ?></div>
            <?php if (!empty($invoice->einvoice_ack_no)): ?>
            <div style="font-size: 6.5px; color: #666;">Ack No: <?= htmlspecialchars($invoice->einvoice_ack_no) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Amount Words -->
        <div style="font-size: 7.5px; text-align: center; font-style: italic; margin-bottom: 5px; border-bottom: 1px dotted #ccc; padding-bottom: 3px;">
            Rupees <?= inr_words_pdf($invoice->total_amount) ?> Only
        </div>

        <!-- Outstanding & Wallet Balance -->
        <?php if (!empty($customer)): ?>
            <?php if ($customer->show_outstanding_in_print && $customer->balance > 0): ?>
            <div style="font-size: 8px; margin-bottom: 5px; padding: 4px; border: 1px solid #c00; background: #fff5f5; color: #c00; font-weight: bold; text-align: center;">
                Outstanding Balance: ₹<?= number_format($customer->balance, 2) ?>
            </div>
            <?php endif; ?>
            <?php if (isset($customer->wallet_balance) && $customer->wallet_balance > 0): ?>
            <div style="font-size: 8px; margin-bottom: 5px; padding: 4px; border: 1px solid #080; background: #f5fff5; color: #080; font-weight: bold; text-align: center;">
                Wallet Balance: ₹<?= number_format($customer->wallet_balance, 2) ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Scheme Applied -->
        <?php if (!empty($schemes_applied)): ?>
        <div style="font-size: 6.5px; color: #555; margin-bottom: 3px;">
            <?php foreach ($schemes_applied as $sa): ?>
            Scheme: <?= htmlspecialchars($sa->scheme_name) ?><?= $sa->discount_given > 0 ? ' (-₹'.number_format($sa->discount_given,2).')' : ' (free item)' ?><br>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Remarks -->
        <?php if (!empty($invoice->notes)): ?>
        <div style="font-size: 7.5px; margin-bottom: 5px; padding: 4px; border: 1px solid #ddd; background: #f9f9f9; line-height: 1.25;">
            <strong>Remarks:</strong> <?= nl2br(htmlspecialchars($invoice->notes)) ?>
        </div>
        <?php endif; ?>

        <!-- Footer terms & signatory -->
        <div style="font-size: 7.5px; line-height: 1.3; margin-top: 5px; text-align: center;">
            <?php if (!empty($company['invoice_terms'])): ?>
                <div style="text-align: left; font-size: 7px; margin-bottom: 5px; border-top: 1px dotted #ccc; padding-top: 3px;">
                    <strong>Terms:</strong> <?= htmlspecialchars($company['invoice_terms']) ?>
                </div>
            <?php endif; ?>

            <div style="margin-top: 5px; font-weight: bold; font-size: 8px;">
                For <?= htmlspecialchars($company['company_name'] ?? '') ?>
            </div>
            <div style="margin-top: 2px; margin-bottom: -8px; text-align: center;">
                <div style="width:46px; height:46px; border:2px solid #1a5fb4; border-radius:23px; color:#1a5fb4; margin:0 auto;">
                    <table style="width:46px; height:46px; border:none; border-collapse:collapse;"><tr><td style="border:none; text-align:center; vertical-align:middle; padding:2px;">
                        <span style="font-weight:bold; font-size:5.5px; line-height:1.05; text-transform:uppercase;"><?= htmlspecialchars($company['company_name'] ?? '') ?></span>
                    </td></tr></table>
                </div>
            </div>
            <div style="border-top: 1px dotted #666; display: inline-block; width: 90px; padding-top: 2px;">
                Authorised Signatory
            </div>
            
            <div style="margin-top: 6px; font-size: 7.5px; color: #555; font-weight: bold;">
                Thank you for your visit!
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- ==================== STANDARD A4 LAYOUT ==================== -->
    <!-- COMPANY HEADER -->
    <table class="header-table"><tr>
        <?php if (!empty($company_logo) && file_exists('./' . $company_logo)): ?>
        <td style="width:15%; vertical-align: middle;">
            <img src="<?= './' . $company_logo ?>" style="max-height: 55px; max-width: 100px; object-fit: contain;">
        </td>
        <td style="width:50%; vertical-align: top;">
        <?php else: ?>
        <td style="width:65%; vertical-align: top;">
        <?php endif; ?>
            <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? 'View & Chew') ?></div>
            <div class="company-sub">
                <?= htmlspecialchars($company['company_address'] ?? '') ?><br>
                Tel: <?= htmlspecialchars($company['company_phone'] ?? '') ?>
                &nbsp;|&nbsp; <?= htmlspecialchars($company['company_email'] ?? '') ?><br>
                <?php if (!empty($company['company_gstin'])): ?>
                    GSTIN: <strong><?= htmlspecialchars($company['company_gstin'] ?? '') ?></strong>
                    &nbsp;|&nbsp;
                <?php endif; ?>
                State: <?= htmlspecialchars($company['company_state'] ?? 'Punjab') ?>
                (<?= htmlspecialchars($company['company_state_code'] ?? '07') ?>)
                <?php if ($print_show_tax && !empty($company['company_pan'])): ?>
                    &nbsp;|&nbsp; PAN: <?= htmlspecialchars($company['company_pan'] ?? '') ?>
                <?php endif; ?>
            </div>
        </td>
        <td style="width:35%; vertical-align: top;">
            <div class="inv-title">
                <h2><?= $print_show_tax ? 'Tax Invoice' : 'Retail Invoice' ?></h2>
                <p><?= ucfirst($invoice->invoice_type) ?><?php if ($print_show_tax): ?> &bull;
                   <?= $invoice->is_interstate ? 'Interstate (IGST)' : 'Intrastate (CGST+SGST)' ?><?php endif; ?></p>
            </div>
        </td>
    </tr></table>

    <!-- INVOICE META -->
    <table class="meta-table"><tr>
        <td class="meta-cell"><span class="meta-label">Invoice No</span><span class="meta-val"><?= $invoice->invoice_no ?></span></td>
        <td class="meta-cell"><span class="meta-label">Date</span><span class="meta-val"><?= date('d-M-Y', strtotime($invoice->invoice_date)) ?></span></td>
        <?php if (!empty($invoice->created_by_name)): ?>
        <td class="meta-cell"><span class="meta-label">Cashier</span><span class="meta-val"><?= htmlspecialchars($invoice->created_by_name) ?></span></td>
        <?php endif; ?>
        <td class="meta-cell"><span class="meta-label">Payment Mode</span><span class="meta-val"><?= ucfirst($invoice->payment_mode) ?></span></td>
        <td class="meta-cell"><span class="meta-label">Status</span><span class="meta-val"><?= strtoupper($invoice->status) ?></span></td>
    </tr></table>

    <!-- BUYER / SELLER -->
    <table class="party-table"><tr>
        <td class="party-box">
            <div class="party-label">Bill To (Buyer)</div>
            <div class="party-name"><?= htmlspecialchars($invoice->customer_name) ?></div>
            <div class="party-info">
                <?php if ((int)$invoice->is_delivery !== 0): ?>
                    <?= htmlspecialchars($invoice->customer_address ?? '') ?>
                    <?= $invoice->customer_city ? ', '.htmlspecialchars($invoice->customer_city) : '' ?><br>
                <?php endif; ?>
                Ph: <?php 
                $cust_phone_disp = $invoice->customer_phone ?? '—';
                if ($cust_phone_disp !== '—' && (int)$invoice->is_delivery === 0) {
                    $len = strlen($cust_phone_disp);
                    if ($len > 4) {
                        $cust_phone_disp = str_repeat('X', $len - 4) . substr($cust_phone_disp, -4);
                    }
                }
                echo htmlspecialchars($cust_phone_disp);
                ?><br>
                <?php if ($invoice->customer_gstin): ?>
                GSTIN: <strong><?= htmlspecialchars($invoice->customer_gstin) ?></strong>
                <?php endif; ?>
            </div>
        </td>
        <td style="width:2%"></td>
        <td class="party-box">
            <div class="party-label">Sold By</div>
            <div class="party-name"><?= htmlspecialchars($company['company_name'] ?? '') ?></div>
            <div class="party-info">
                <?= htmlspecialchars($company['company_address'] ?? '') ?><br>
                <?php if (!empty($company['company_gstin'])): ?>
                    GSTIN: <strong><?= htmlspecialchars($company['company_gstin'] ?? '') ?></strong><br>
                <?php endif; ?>
                State Code: <?= htmlspecialchars($company['company_state_code'] ?? '07') ?>
            </div>
        </td>
    </tr></table>

    <!-- ITEMS TABLE -->
    <table class="items">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:<?= $print_show_tax ? '24%' : '50%' ?>">Description</th>
                <?php if ($print_show_tax): ?>
                <th style="width:9%">HSN/SAC</th>
                <?php endif; ?>
                <th style="width:6%">Qty</th>
                <th style="width:6%">Unit</th>
                <th style="width:9%">MRP</th>
                <th style="width:9%">Rate (&#8377;)</th>
                <th style="width:8%">Disc</th>
                <?php if ($print_show_tax): ?>
                <th style="width:9%">Taxable</th>
                <th style="width:6%">GST%</th>
                <?php if ($invoice->is_interstate): ?>
                <th style="width:8%">IGST</th>
                <?php else: ?>
                <th style="width:6%">CGST</th>
                <th style="width:6%">SGST</th>
                <?php endif; ?>
                <?php endif; ?>
                <th style="width:10%">Total (&#8377;)</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $i => $item):
            $pdf_disp_taxable = round($item->taxable_amt * $pdf_cd_ratio, 2);
            $pdf_disp_gst     = round($item->gst_amount * $pdf_cd_ratio, 2);
            $pdf_disp_total   = round($item->total * $pdf_cd_ratio, 2);
            $half  = round($pdf_disp_gst / 2, 2);
            $half2 = $pdf_disp_gst - $half;
        ?>
            <tr>
                <td class="c"><?= $i + 1 ?></td>
                <td>
                    <div style="font-weight:bold;"><?= htmlspecialchars(strtoupper($item->product_name)) ?></div>
                    <?php if(!empty($item->description)): ?><div style="font-size:8.5px;color:#666;margin-top:1px;"><?= htmlspecialchars($item->description) ?></div><?php endif; ?>
                </td>
                <?php if ($print_show_tax): ?>
                <td class="c"><?= htmlspecialchars($item->hsn_code ?? '—') ?></td>
                <?php endif; ?>
                <td class="c"><?= $item->qty ?></td>
                <td class="c"><?= $item->unit ?></td>
                <td class="r">&#8377; <?= number_format(($item->mrp > 0) ? $item->mrp : ($item->product_mrp ?? 0), 2) ?>
                <?php
                    $pdf_a4_mrp = ($item->mrp > 0) ? $item->mrp : ($item->product_mrp ?? 0);
                    $pdf_a4_sd  = round((float)$pdf_a4_mrp - (float)$item->rate, 2);
                ?>
                <?php if ($pdf_a4_sd > 0): ?>
                <div style="font-size:8px;color:#15803d;">S.D &#8377;<?= number_format($pdf_a4_sd, 2) ?></div>
                <?php endif; ?>
                </td>
                <td class="r">&#8377; <?= number_format($item->rate, 2) ?></td>
                <td class="r">&#8377; <?= number_format($item->discount, 2) ?></td>
                <?php if ($print_show_tax): ?>
                <td class="r">&#8377; <?= number_format($pdf_disp_taxable, 2) ?></td>
                <td class="c"><?= $item->gst_percent ?>%</td>
                <?php if ($invoice->is_interstate): ?>
                <td class="r">&#8377; <?= number_format($pdf_disp_gst, 2) ?></td>
                <?php else: ?>
                <td class="r">&#8377; <?= number_format($half, 2) ?></td>
                <td class="r">&#8377; <?= number_format($half2, 2) ?></td>
                <?php endif; ?>
                <?php endif; ?>
                <td class="r"><strong>&#8377; <?= number_format($pdf_disp_total, 2) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- GST RATE-WISE SUMMARY -->
    <?php if ($print_show_tax): ?>
    <table class="gst-table">
        <thead><tr>
            <th>HSN/SAC</th>
            <th>Taxable Value</th>
            <?php if ($invoice->is_interstate): ?>
            <th>IGST Rate</th><th>IGST Amt</th>
            <?php else: ?>
            <th>CGST Rate</th><th>CGST Amt</th>
            <th>SGST Rate</th><th>SGST Amt</th>
            <?php endif; ?>
            <th>Total Tax</th>
        </tr></thead>
        <tbody>
        <?php
        $gst_groups = [];
        foreach ($items as $item) {
            $key = $item->gst_percent . '_' . ($item->hsn_code ?? '');
            if (!isset($gst_groups[$key])) $gst_groups[$key] = ['hsn'=>$item->hsn_code,'rate'=>$item->gst_percent,'taxable'=>0,'gst'=>0];
            $gst_groups[$key]['taxable'] += round($item->taxable_amt * $pdf_cd_ratio, 2);
            $gst_groups[$key]['gst']     += round($item->gst_amount * $pdf_cd_ratio, 2);
        }
        foreach ($gst_groups as $g):
            $h = round($g['gst']/2, 2);
        ?>
        <tr>
            <td><?= htmlspecialchars($g['hsn'] ?? '—') ?></td>
            <td>&#8377; <?= number_format($g['taxable'],2) ?></td>
            <?php if ($invoice->is_interstate): ?>
            <td><?= $g['rate'] ?>%</td><td>&#8377; <?= number_format($g['gst'],2) ?></td>
            <?php else: ?>
            <td><?= $g['rate']/2 ?>%</td><td>&#8377; <?= number_format($h,2) ?></td>
            <td><?= $g['rate']/2 ?>%</td><td>&#8377; <?= number_format($g['gst']-$h,2) ?></td>
            <?php endif; ?>
            <td><strong>&#8377; <?= number_format($g['gst'],2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- TOTALS & SCAN TO PAY -->
    <table style="width:100%; margin-top: 5px; border-collapse: collapse;">
        <tr>
            <!-- UPI SCAN & PAY -->
            <td style="width:50%; vertical-align: top; padding-right: 15px;">
                <?php 
                $upi_id = $company['company_upi_id'] ?? '';
                $due_amt = $invoice->due_amount;
                if ($print_show_pending && !empty($upi_id) && $due_amt > 0): 
                    $merchant_name = !empty($company['company_upi_name']) ? $company['company_upi_name'] : ($company['company_name'] ?? 'View & Chew');
                    $upi_url = "upi://pay?pa=" . urlencode($upi_id) . "&pn=" . urlencode($merchant_name) . "&am=" . urlencode(number_format($due_amt, 2, '.', '')) . "&cu=INR&tn=" . urlencode($invoice->invoice_no);
                ?>
                    <div style="border: 1px dashed #444; border-radius: 4px; padding: 10px; background: #fafafa; width: 235px;">
                        <table style="width:100%; border-collapse: collapse;">
                            <tr>
                                <td style="vertical-align: middle; width: 80px; border: none; background: transparent;">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=4&ecc=M&data=<?= urlencode($upi_url) ?>" style="width: 72px; height: 72px;" />
                                </td>
                                <td style="vertical-align: middle; padding-left: 10px; border: none; background: transparent; text-align: left;">
                                    <div style="font-weight: bold; font-size: 11px; color: #111; text-transform: uppercase; margin-bottom: 3px;">Scan &amp; Pay</div>
                                    <div style="font-size: 8.5px; color: #555; line-height: 1.35;">
                                        Scan with any UPI app<br>
                                        (GPay, PhonePe, Paytm, BHIM)<br>
                                        Amount: <strong>&#8377; <?= number_format($due_amt, 2) ?></strong>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Customer Portal Login QR -->
                <?php
                $cust_phone_raw = preg_replace('/\D/', '', $invoice->customer_phone ?? '');
                if (!empty($cust_phone_raw)):
                    $portal_login_url = base_url('portal/login') . '?phone=' . urlencode($cust_phone_raw);
                ?>
                    <div style="border: 1px dashed #444; border-radius: 4px; padding: 10px; background: #fafafa; width: 235px; margin-top: 8px;">
                        <table style="width:100%; border-collapse: collapse;">
                            <tr>
                                <td style="vertical-align: middle; width: 90px; border: none; background: transparent;">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&qzone=4&ecc=M&data=<?= urlencode($portal_login_url) ?>" style="width: 84px; height: 84px;" />
                                </td>
                                <td style="vertical-align: middle; padding-left: 10px; border: none; background: transparent; text-align: left;">
                                    <div style="font-weight: bold; font-size: 11px; color: #111; text-transform: uppercase; margin-bottom: 3px;">Customer Portal</div>
                                    <div style="font-size: 8.5px; color: #555; line-height: 1.35;">
                                        Scan to view bills &amp; order online<br>
                                        Login: Your mobile number<br>
                                        Password: Last 4 digits
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- e-Invoice IRN QR -->
                <?php if (($invoice->einvoice_status ?? '') === 'generated' && !empty($invoice->einvoice_irn)): ?>
                    <div style="border: 1px dashed #444; border-radius: 4px; padding: 10px; background: #fafafa; width: 235px; margin-top: 8px;">
                        <table style="width:100%; border-collapse: collapse;">
                            <tr>
                                <?php if (!empty($invoice->einvoice_qr_data)): ?>
                                <td style="vertical-align: middle; width: 90px; border: none; background: transparent;">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&qzone=2&ecc=L&data=<?= urlencode($invoice->einvoice_qr_data) ?>" style="width: 84px; height: 84px;" />
                                </td>
                                <?php endif; ?>
                                <td style="vertical-align: middle; padding-left: 10px; border: none; background: transparent; text-align: left;">
                                    <div style="font-weight: bold; font-size: 11px; color: #111; text-transform: uppercase; margin-bottom: 3px;">e-Invoice</div>
                                    <div style="font-size: 7.5px; color: #555; line-height: 1.35; word-break: break-all;">
                                        <strong>IRN:</strong> <?= htmlspecialchars($invoice->einvoice_irn) ?>
                                        <?php if (!empty($invoice->einvoice_ack_no)): ?><br><strong>Ack No:</strong> <?= htmlspecialchars($invoice->einvoice_ack_no) ?><?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                <?php endif; ?>
            </td>
            <!-- TOTALS TABLE -->
            <td style="width:50%; vertical-align: top;">
                <div class="totals-wrap" style="text-align: right; margin-top: 0;">
                    <table class="totals-tbl" style="display: inline-table; width: 100%; border-collapse: collapse; font-size: 10.5px;">
                        <tr><td>Subtotal (Qty: <?= $total_qty == floor($total_qty) ? (int)$total_qty : $total_qty ?>)</td><td>&#8377; <?= number_format($invoice->subtotal,2) ?></td></tr>
                        <?php if ($invoice->discount > 0): ?>
                        <tr><td>Discount</td><td>- &#8377; <?= number_format($invoice->discount,2) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($print_show_tax): ?>
                        <tr><td>Taxable Amount</td><td>&#8377; <?= number_format($invoice->taxable_amount,2) ?></td></tr>
                        <?php if ($invoice->is_interstate): ?>
                        <tr><td>IGST</td><td>&#8377; <?= number_format($invoice->igst_amount,2) ?></td></tr>
                        <?php else: ?>
                        <tr><td>CGST</td><td>&#8377; <?= number_format($invoice->cgst_amount,2) ?></td></tr>
                        <tr><td>SGST</td><td>&#8377; <?= number_format($invoice->sgst_amount,2) ?></td></tr>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($invoice->round_off) && $invoice->round_off != 0): ?>
                        <tr><td>Round Off</td><td><?= $invoice->round_off > 0 ? '+' : '-' ?> &#8377; <?= number_format(abs($invoice->round_off),2) ?></td></tr>
                        <?php endif; ?>
                        <tr class="grand-row"><td>Grand Total</td><td>&#8377; <?= number_format($invoice->total_amount,2) ?></td></tr>
                        <?php if (isset($invoice->wallet_amount_used) && $invoice->wallet_amount_used > 0): ?>
                        <tr><td>Wallet Applied</td><td style="color:#080;">- &#8377; <?= number_format($invoice->wallet_amount_used, 2) ?></td></tr>
                        <?php endif; ?>
                        <?php if (isset($invoice->wallet_amount_credited) && $invoice->wallet_amount_credited > 0): ?>
                        <tr><td>Wallet Credited</td><td style="color:#080;">&#8377; <?= number_format($invoice->wallet_amount_credited, 2) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($print_show_pending): ?>
                        <?php
                            $change_amt2 = round(
                                (float)($invoice->amount_tendered ?? $invoice->paid_amount)
                                - (float)$invoice->paid_amount
                                - (float)($invoice->wallet_amount_credited ?? 0),
                                2
                            );
                        ?>
                        <?php if ($change_amt2 > 0.004): ?>
                        <tr><td>Cash Tendered</td><td>&#8377; <?= number_format($invoice->amount_tendered,2) ?></td></tr>
                        <tr><td>Change Returned</td><td>&#8377; <?= number_format($change_amt2,2) ?></td></tr>
                        <?php endif; ?>
                        <tr><td>Paid</td><td>&#8377; <?= number_format($invoice->paid_amount,2) ?></td></tr>
                        <tr class="due-row"><td>Balance Due</td><td>&#8377; <?= number_format(max(0, $invoice->due_amount),2) ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- AMOUNT IN WORDS -->
    <div class="words-box">
        <strong>Amount in words:</strong> Rupees <?= inr_words_pdf($invoice->total_amount) ?> Only
    </div>

    <?php if (!empty($schemes_applied)): ?>
    <div style="font-size:8px; color:#555; margin-top:2px;">
        <?php foreach ($schemes_applied as $sa): ?>
        Scheme: <?= htmlspecialchars($sa->scheme_name) ?><?= $sa->discount_given > 0 ? ' (-₹'.number_format($sa->discount_given,2).')' : ' (free item)' ?><br>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($invoice->notes)): ?>
    <div class="words-box" style="margin-top:2px;">
        <strong>Remarks:</strong> <?= nl2br(htmlspecialchars($invoice->notes)) ?>
    </div>
    <?php endif; ?>

    <!-- Outstanding & Wallet Balance -->
    <?php if (!empty($customer)): ?>
        <?php if ($customer->show_outstanding_in_print && $customer->balance > 0): ?>
        <div class="words-box" style="border-color: #c00; background: #fff5f5; color: #c00;">
            <strong>Outstanding Balance for <?= htmlspecialchars($customer->name) ?>:</strong> &#8377; <?= number_format($customer->balance, 2) ?>
        </div>
        <?php endif; ?>
        <?php if (isset($customer->wallet_balance) && $customer->wallet_balance > 0): ?>
        <div class="words-box" style="border-color: #080; background: #f5fff5; color: #080;">
            <strong>Wallet Balance for <?= htmlspecialchars($customer->name) ?>:</strong> &#8377; <?= number_format($customer->wallet_balance, 2) ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- FOOTER -->
    <table class="footer-table"><tr>
        <td class="terms-col" style="width:65%">
            <strong>Terms &amp; Conditions:</strong><br>
            <?= htmlspecialchars($company['invoice_terms'] ?? 'Goods once sold will not be taken back. Subject to Mohali Punjab jurisdiction.') ?>
        </td>
        <td style="width:5%"></td>
        <td class="sig-col" style="width:30%">
            <strong>For <?= htmlspecialchars($company['company_name'] ?? '') ?></strong>
            <div style="text-align: right; margin-top: 2px; margin-bottom: -8px; padding-right: 20px;">
                <div style="width:54px; height:54px; border:2px solid #1a5fb4; border-radius:27px; color:#1a5fb4; display:inline-block;">
                    <table style="width:54px; height:54px; border:none; border-collapse:collapse;"><tr><td style="border:none; text-align:center; vertical-align:middle; padding:2px;">
                        <span style="font-weight:bold; font-size:6.5px; line-height:1.05; text-transform:uppercase;"><?= htmlspecialchars($company['company_name'] ?? '') ?></span>
                    </td></tr></table>
                </div>
            </div>
            <div class="sig-line">Authorised Signatory</div>
        </td>
    </tr></table>
<?php endif; ?>

</body>
</html>
