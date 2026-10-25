<!DOCTYPE html>
<?php
// FILE: application/views/billing/print.php
// Standalone print view — no header/footer layout
// Access via: billing/print_invoice/{id}

$printer_type = $company['printer_type'] ?? 'A4';
$print_show_tax = (!isset($company['print_show_tax']) || (int)$company['print_show_tax'] !== 0) && (!isset($company['enable_gst']) || (int)$company['enable_gst'] !== 0);
$print_show_pending = !isset($company['print_show_pending']) || (int)$company['print_show_pending'] !== 0;
$show_portal_qr = !isset($company['show_portal_qr_on_bill']) || (int)$company['show_portal_qr_on_bill'] !== 0;
$company_logo = $company['company_logo'] ?? '';

// Simple INR words helper
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
<title><?= $print_show_tax ? 'GST Invoice' : 'Retail Invoice' ?> <?= $invoice->invoice_no ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 14px; color: #222; background: #f0f2f5; }
.page { background: #fff; position: relative; }

/* A4 Page Layout */
.page.a4-layout { width: 210mm; min-height: 297mm; margin: 20px auto; padding: 10mm 12mm; box-shadow: 0 0 10px rgba(0,0,0,0.1); border-radius: 4px; }

/* Thermal Layouts (Screen Preview) */
.page.thermal-3in {
    width: 80mm;
    min-height: auto;
    margin: 20px auto;
    padding: 4mm 5mm;
    border: 1px dashed #ccc;
    box-shadow: 0 0 10px rgba(0,0,0,0.08);
    border-radius: 4px;
}
.page.thermal-2in {
    width: 58mm;
    min-height: auto;
    margin: 20px auto;
    padding: 3mm 4mm;
    border: 1px dashed #ccc;
    box-shadow: 0 0 10px rgba(0,0,0,0.08);
    border-radius: 4px;
    font-size: 10px;
}

/* Header (A4) */
.inv-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 8px; }
.company-name { font-size: 20px; font-weight: bold; color: #1a1a1a; }
.company-sub  { font-size: 13px; color: #555; line-height: 1.5; margin-top: 3px; }
.inv-title    { text-align: right; }
.inv-title h2 { font-size: 18px; color: #1a1a1a; text-transform: uppercase; letter-spacing: 1px; }
.inv-title p  { font-size: 13px; color: #555; margin-top: 2px; }

/* Party Info (A4) */
.parties { display: flex; gap: 8px; margin: 8px 0; }
.party-box { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 8px 10px; }
.party-box h4 { font-size: 11px; text-transform: uppercase; color: #888; letter-spacing: .5px; margin-bottom: 4px; }
.party-box .name { font-size: 15px; font-weight: bold; margin-bottom: 2px; }
.party-box p { font-size: 13px; line-height: 1.5; color: #444; }

/* Invoice Meta (A4) */
.inv-meta { display: flex; gap: 8px; margin-bottom: 8px; }
.meta-box { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 6px 10px; font-size: 13px; }
.meta-box span { font-weight: bold; }

/* Items Table (A4) */
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
.items-table th { background: #333; color: #fff; padding: 6px 5px; font-size: 12px; text-align: center; border: 1px solid #333; }
.items-table td { padding: 5px 5px; font-size: 13px; border: 1px solid #ddd; vertical-align: top; }
.items-table tr:nth-child(even) td { background: #f9f9f9; }
.items-table td.right { text-align: right; }
.items-table td.center { text-align: center; }

/* Totals (A4) */
.totals-section { display: flex; justify-content: flex-end; margin-top: 4px; }
.totals-table { width: 280px; border-collapse: collapse; font-size: 13px; }
.totals-table td { padding: 4px 8px; border: 1px solid #ddd; }
.totals-table td:last-child { text-align: right; font-weight: bold; }
.totals-table .grand { background: #333; color: #fff; font-size: 15px; }
.totals-table .grand td { padding: 6px 8px; }

/* Amount in words */
.amt-words { border: 1px solid #ddd; border-radius: 4px; padding: 6px 10px; margin: 8px 0; font-size: 13px; }
.amt-words span { font-weight: bold; }

/* GST Summary Table */
.gst-table { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 12px; }
.gst-table th { background: #f0f0f0; border: 1px solid #ccc; padding: 4px 6px; text-align: center; }
.gst-table td { border: 1px solid #ddd; padding: 4px 6px; text-align: center; }

/* Footer (A4) */
.inv-footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 12px; border-top: 1px solid #ddd; padding-top: 8px; font-size: 12px; }
.terms { flex: 1; color: #666; }
.signature { text-align: right; font-size: 11px; }
.signature p { margin-top: 36px; border-top: 1px solid #333; padding-top: 4px; color: #333; }

/* Status stamp */
.stamp { position: absolute; opacity: .12; font-weight: 900; text-transform: uppercase; transform: rotate(-35deg); border: 4px solid currentColor; padding: 4px 12px; border-radius: 4px; color: <?= $invoice->status === 'cancelled' ? '#c00' : ($invoice->status === 'paid' ? '#0a0' : '#c80') ?>; }
.page.a4-layout .stamp { top: 30mm; right: 14mm; font-size: 42px; }
.page.thermal-3in .stamp { top: 15mm; right: 4mm; font-size: 26px; border-width: 2px; }
.page.thermal-2in .stamp { top: 12mm; right: 3mm; font-size: 20px; border-width: 2px; }

@media print {
    body { background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }

    .page.a4-layout { width: 100%; min-height: auto; margin: 0; padding: 6mm 8mm; box-shadow: none; border-radius: 0; }

    /* Thermal: auto height = page ends exactly where content ends = cutter fires right after last line.
       padding-bottom 20mm gives the paper feed gap before the physical cut blade.
       Left/right 1mm — the remaining safe margin lives on @page so the
       browser honours the printer's non-printable area instead of clipping. */
    .page.thermal-3in { width: 72mm; margin: 0 auto; padding: 1mm 1mm 20mm 1mm; border: none; box-shadow: none; border-radius: 0; }
    .page.thermal-2in { width: 52mm; margin: 0 auto; padding: 1mm 1mm 20mm 1mm; border: none; box-shadow: none; border-radius: 0; font-size: 10px; }
}
<?php if ($printer_type === '3in'): ?>
@page { size: 80mm auto; margin: 0 4mm; }
<?php elseif ($printer_type === '2in'): ?>
@page { size: 58mm auto; margin: 0 3mm; }
<?php elseif ($printer_type === 'A5'): ?>
@page { size: A5 <?= ($company['a5_orientation'] ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait' ?>; margin: 5mm; }
<?php else: ?>
@page { size: A4 portrait; margin: 10mm; }
<?php endif; ?>
/* thermal-3in readability: every inline size one step up, all text solid black */
.page.thermal-3in { font-size: 12.5px; line-height: 1.35; }
.page.thermal-3in *:not(.stamp) { color: #000 !important; }
.page.thermal-3in th[style*="width: 70px"] { width: 92px !important; }
.page.thermal-3in [style*="font-size: 6.5px"], .page.thermal-3in [style*="font-size:6.5px"] { font-size: 9px !important; }
.page.thermal-3in [style*="font-size: 7px"], .page.thermal-3in [style*="font-size:7px"] { font-size: 10px !important; }
.page.thermal-3in [style*="font-size: 7.5px"], .page.thermal-3in [style*="font-size:7.5px"] { font-size: 10.5px !important; }
.page.thermal-3in [style*="font-size: 8px"], .page.thermal-3in [style*="font-size:8px"] { font-size: 11px !important; }
.page.thermal-3in [style*="font-size: 8.5px"], .page.thermal-3in [style*="font-size:8.5px"] { font-size: 11.5px !important; }
.page.thermal-3in [style*="font-size: 9px"], .page.thermal-3in [style*="font-size:9px"] { font-size: 12px !important; }
.page.thermal-3in [style*="font-size: 9.5px"], .page.thermal-3in [style*="font-size:9.5px"] { font-size: 12.5px !important; }
.page.thermal-3in [style*="font-size: 10px"], .page.thermal-3in [style*="font-size:10px"] { font-size: 13px !important; }
.page.thermal-3in [style*="font-size: 10.5px"], .page.thermal-3in [style*="font-size:10.5px"] { font-size: 13.5px !important; }
.page.thermal-3in [style*="font-size: 11px"], .page.thermal-3in [style*="font-size:11px"] { font-size: 14px !important; }
.page.thermal-3in [style*="font-size: 11.5px"], .page.thermal-3in [style*="font-size:11.5px"] { font-size: 15px !important; }
.page.thermal-3in [style*="font-size: 14px"], .page.thermal-3in [style*="font-size:14px"] { font-size: 17px !important; }
</style>
</head>
<body>

<!-- Print/Back controls (hidden on print) -->
<div class="no-print" style="background:#f5f5f5;padding:10px 20px;display:flex;gap:10px;align-items:center;border-bottom:1px solid #ddd;font-family:sans-serif;">
    <button onclick="Promise.all([].map.call(document.images,function(i){return i.complete?1:new Promise(function(r){i.onload=i.onerror=r})})).then(function(){window.print()})" style="padding:6px 18px;background:#333;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;">&#128438; Print</button>
    <?php if (!empty($is_public)): ?>
        <a href="<?= base_url('portal/public_pdf/'.$invoice->id) ?>" style="padding:6px 18px;background:#dc3545;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;text-decoration:none;">📥 Download PDF</a>
    <?php else: ?>
        <a href="<?= base_url('billing/pdf/'.$invoice->id) ?>" style="padding:6px 18px;background:#dc3545;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:13px;text-decoration:none;">📥 Download PDF</a>
        <a href="<?= base_url('billing/invoice/'.$invoice->id) ?>" style="padding:6px 16px;border:1px solid #333;border-radius:4px;text-decoration:none;color:#333;font-size:13px;">&larr; Back</a>
    <?php endif; ?>
    <span style="color:#666;font-size:12px;">Invoice: <strong><?= $invoice->invoice_no ?></strong> (Layout: <strong><?= $printer_type === '3in' ? '3" Thermal' : ($printer_type === '2in' ? '2" Thermal' : ($printer_type === 'A5' ? 'A5 Portrait' : 'A4 Standard')) ?></strong>)</span>
    <?php if ($printer_type === 'A5'): ?>
    <span style="color:#a15c00;font-size:12px;">In the print dialog choose Paper size = A5, Scale = 100%, Margins = Default/None. Save A5 as the printer's default paper if it keeps switching to A4.</span>
    <?php endif; ?>
</div>

<?php if ($printer_type === '3in' || $printer_type === '2in'): ?>
    <!-- ==================== THERMAL RECEIPT LAYOUT (2" OR 3") ==================== -->
    <div class="page <?= $printer_type === '3in' ? 'thermal-3in' : 'thermal-2in' ?>">
        <?php if (in_array($invoice->status, ['cancelled','paid','partial'])): ?>
            <div class="stamp"><?= $invoice->status === 'paid' ? 'PAID' : ($invoice->status === 'cancelled' ? 'CANCELLED' : 'PARTIAL') ?></div>
        <?php endif; ?>

        <!-- Centered Logo -->
        <?php if (!empty($company_logo) && file_exists('./' . $company_logo)): ?>
            <div style="text-align: center; margin-bottom: 6px;">
                <img src="<?= base_url($company_logo) ?>" alt="Logo" style="max-height: 48px; max-width: 100%; object-fit: contain;">
            </div>
        <?php endif; ?>

        <!-- Outlet / Company Details -->
        <?php
        $th_name   = ($outlet && !empty($outlet->name))    ? $outlet->name    : ($company['company_name']    ?? '');
        $th_addr   = ($outlet && !empty($outlet->address)) ? $outlet->address : ($company['company_address'] ?? '');
        $th_city   = ($outlet && !empty($outlet->city))    ? $outlet->city    : '';
        $th_phone  = ($outlet && !empty($outlet->phone))   ? $outlet->phone   : ($company['company_phone']   ?? '');
        $th_email  = ($outlet && !empty($outlet->email))   ? $outlet->email   : ($company['company_email']   ?? '');
        $th_gstin  = ($outlet && !empty($outlet->gstin))   ? $outlet->gstin   : ($company['company_gstin']   ?? '');
        $th_parent = ($outlet && $outlet->name !== ($company['company_name'] ?? '')) ? ($company['company_name'] ?? '') : '';
        ?>
        <div style="text-align: center; margin-bottom: 6px;">
            <?php if (!empty($th_parent)): ?>
            <div style="font-size:8px; color:#666; text-transform:uppercase; letter-spacing:.5px;"><?= htmlspecialchars($th_parent) ?></div>
            <?php endif; ?>
            <div style="font-size: 14px; font-weight: bold; text-transform: uppercase;"><?= htmlspecialchars($th_name) ?></div>
            <div style="font-size: 9.5px; color: #333; line-height: 1.35; margin-top: 2px;">
                <?= htmlspecialchars(implode(', ', array_filter([$th_addr, $th_city]))) ?><br>
                <?php if (!empty($th_phone)): ?>Ph: <?= htmlspecialchars($th_phone) ?><?php endif; ?>
                <?php if (!empty($th_email)): ?><br><?= htmlspecialchars($th_email) ?><?php endif; ?>
                <?php if (!empty($th_gstin)): ?>
                    <br>GSTIN: <strong><?= htmlspecialchars($th_gstin) ?></strong>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bill Metadata -->
        <div style="border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 4px 0; margin-bottom: 6px; font-size: 9.5px; line-height: 1.4;">
            <div style="text-align: center; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; font-size: 10.5px;">
                <?= $print_show_tax ? 'Tax Invoice' : 'Retail Bill' ?>
            </div>
            <table style="width: 100%; border: none; font-size: 9.5px;">
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
                    <td style="border:none; padding:1px 0;">Pay: <?php if ($invoice->payment_mode === 'split' && !empty($splits)): echo implode('+', array_map(fn($s) => strtoupper($s->payment_mode), $splits)); else: echo ucfirst($invoice->payment_mode); endif; ?></td>
                    <td style="text-align: right; border:none; padding:1px 0;">Time: <strong><?= $invoice->created_at ? date('h:i A', strtotime($invoice->created_at)) : '' ?></strong></td>
                </tr>
            </table>
        </div>

        <!-- Buyer Info -->
        <?php if ($invoice->customer_name !== 'Cash' && $invoice->customer_name !== 'Cash Customer'): ?>
        <div style="font-size: 9.5px; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px dashed #ccc; line-height: 1.35;">
            <strong>Bill To:</strong> <?= htmlspecialchars($invoice->customer_name) ?>
            <?php if (!empty($invoice->customer_phone)): ?>
                <br>Ph: <?php 
                $cust_phone_disp = $invoice->customer_phone;
                if ((int)$invoice->is_delivery === 0) {
                    $len = strlen($cust_phone_disp);
                    if ($len > 4) {
                        $cust_phone_disp = str_repeat('X', $len - 4) . substr($cust_phone_disp, -4);
                    }
                }
                echo htmlspecialchars($cust_phone_disp);
                ?>
            <?php endif; ?>
            <?php if (!empty($customer->pan)): ?><br>PAN: <strong><?= htmlspecialchars($customer->pan) ?></strong><?php endif; ?>
            <?php if (!empty($customer->aadhaar_number)): ?><br>Aadhaar: <strong><?= htmlspecialchars('XXXXXXXX' . substr($customer->aadhaar_number, -4)) ?></strong><?php endif; ?>
            <?php if ((int)$invoice->is_delivery !== 0 && !empty($invoice->customer_address)): ?><br>Add: <?= htmlspecialchars($invoice->customer_address) ?><?php endif; ?>
            <?php if ($print_show_tax && !empty($invoice->customer_gstin)): ?><br>GSTIN: <?= htmlspecialchars($invoice->customer_gstin) ?><?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Items Stacked List -->
        <table style="width: 100%; border-collapse: collapse; font-size: 9.5px; border-bottom: 1px dashed #000; margin-bottom: 6px;">
            <thead>
                <tr style="border-bottom: 1px dashed #000; border-top: 1px dashed #000; font-weight: bold;">
                    <th style="text-align: left; padding: 4px 0;">Item Description</th>
                    <th style="text-align: right; padding: 4px 0; width: 70px;">Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $th_items_total = array_sum(array_map(fn($it) => (float)$it->total, $items));
            $th_cd_ratio = ($invoice->cd_amount > 0 && $th_items_total > 0) ? ($invoice->total_amount / $th_items_total) : 1;
            ?>
            <?php foreach ($items as $item):
                $th_disp_gst   = round($item->gst_amount * $th_cd_ratio, 2);
                $th_disp_total = round($item->total * $th_cd_ratio, 2);
            ?>
                <tr style="border-bottom: 1px dotted #ddd;">
                    <td style="padding: 4px 0; line-height: 1.3;">
                        <div style="font-weight: bold;"><?= htmlspecialchars(strtoupper($item->product_name)) ?><?php if (!empty($item->size_name) || !empty($item->colour_name)): ?> <span style="font-size: 8px; font-weight: normal; color: #333;">[<?= htmlspecialchars(($item->colour_name ? $item->colour_name . ' / ' : '') . $item->size_name) ?>]</span><?php endif; ?></div>
                        <div style="color: #444; font-size: 9px;">
                            <?php if (!empty($item->mrp) && $item->mrp > 0): ?>
                                <span style="font-size: 8px; color: #888;">MRP-<?= number_format($item->mrp, 0) ?></span>
                                <?php $th_sd = round((float)$item->mrp - (float)$item->rate, 2); if ($th_sd > 0): ?>
                                <span style="font-size: 8px; color: #15803d;"> S.D &#8377;<?= number_format($th_sd, 2) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?= $item->qty ?> <?= $item->unit ?> &times; ₹<?= number_format($item->rate, 2) ?>
                            <?php if ($item->discount > 0): ?>
                                (Disc: -₹<?= number_format($item->discount, 2) ?>)
                            <?php endif; ?>
                            <?php if ($print_show_tax): ?>
                                <br>
                                <span style="color: #666; font-size: 8.5px;">
                                    <?php if (!empty($item->hsn_code)): ?>HSN: <?= htmlspecialchars($item->hsn_code) ?><?php endif; ?>
                                    <?php if (!empty($item->hsn_code) && $item->gst_percent > 0): ?> | <?php endif; ?>
                                    <?php if ($item->gst_percent > 0): ?>GST: <?= $item->gst_percent ?>% (₹<?= number_format($th_disp_gst, 2) ?>)<?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php $this->load->view('billing/_serial_line', [
                            'item' => $item, 'sl_size' => '8.5px',
                            'serials' => $serials ?? [], 'item_warranty' => $item_warranty ?? [],
                        ]); ?>
                    </td>
                    <td style="text-align: right; padding: 4px 0; vertical-align: top; font-weight: bold;">
                        ₹<?= number_format($th_disp_total, 2) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals Block -->
        <?php $total_qty = 0; foreach ($items as $_qi) { $total_qty += (float)$_qi->qty; } ?>
        <table style="width: 100%; border-collapse: collapse; font-size: 9.5px; margin-bottom: 6px; line-height: 1.4;">
            <tr><td style="padding: 2px 0;">Subtotal (Qty: <?= $total_qty == floor($total_qty) ? (int)$total_qty : $total_qty ?>)</td><td style="text-align: right; padding: 2px 0;">₹<?= number_format($invoice->subtotal, 2) ?></td></tr>
            <?php if ($invoice->discount > 0): ?>
            <tr><td style="padding: 2px 0;">Discount</td><td style="text-align: right; padding: 2px 0;">- ₹<?= number_format($invoice->discount, 2) ?></td></tr>
            <?php endif; ?>
            <?php if ($print_show_tax): ?>
            <tr><td style="padding: 2px 0;">Taxable Amount</td><td style="text-align: right; padding: 2px 0;">₹<?= number_format($invoice->taxable_amount, 2) ?></td></tr>
            <?php if ($invoice->is_interstate): ?>
            <tr><td style="padding: 2px 0;">IGST</td><td style="text-align: right; padding: 2px 0;">₹<?= number_format($invoice->igst_amount, 2) ?></td></tr>
            <?php else: ?>
            <tr><td style="padding: 2px 0;">CGST</td><td style="text-align: right; padding: 2px 0;">₹<?= number_format($invoice->cgst_amount, 2) ?></td></tr>
            <tr><td style="padding: 2px 0;">SGST</td><td style="text-align: right; padding: 2px 0;">₹<?= number_format($invoice->sgst_amount, 2) ?></td></tr>
            <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($invoice->cd_percent) && $invoice->cd_percent > 0): ?>
            <tr><td style="padding: 2px 0;">CD (<?= $invoice->cd_percent ?>%)</td><td style="text-align: right; padding: 2px 0; color:#c00;">- ₹<?= number_format($invoice->cd_amount, 2) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($invoice->round_off) && $invoice->round_off != 0): ?>
            <tr><td style="padding: 2px 0;">Round Off</td><td style="text-align: right; padding: 2px 0;"><?= $invoice->round_off > 0 ? '+' : '-' ?> ₹<?= number_format(abs($invoice->round_off), 2) ?></td></tr>
            <?php endif; ?>
            <tr style="font-weight: bold; font-size: 11.5px; border-top: 1px dashed #000; border-bottom: 1px dashed #000;">
                <td style="padding: 4px 0;">GRAND TOTAL</td>
                <td style="text-align: right; padding: 4px 0;">₹<?= number_format($invoice->total_amount, 2) ?></td>
            </tr>
            <?php
                $mrp_total = 0;
                foreach ($items as $_it) {
                    $mrp_total += (float)($_it->mrp ?? $_it->rate) * (float)$_it->qty;
                }
                $total_saving = round($mrp_total - $invoice->total_amount, 2);
            ?>
            <?php if ($total_saving > 0): ?>
            <tr style="color:#15803d; font-size:11.5px; border-top:1px dashed #15803d;">
                <td style="padding: 3px 0; font-weight:600;">🎉 You Saved</td>
                <td style="text-align: right; padding: 3px 0; font-weight:700;">₹<?= number_format($total_saving, 2) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (isset($invoice->wallet_amount_used) && $invoice->wallet_amount_used > 0): ?>
            <tr><td style="padding: 2px 0; color:#080;">Wallet Used</td><td style="text-align: right; padding: 2px 0; color:#080;">- ₹<?= number_format($invoice->wallet_amount_used, 2) ?></td></tr>
            <?php endif; ?>
            <?php if (isset($invoice->wallet_amount_credited) && $invoice->wallet_amount_credited > 0): ?>
            <tr><td style="padding: 2px 0; color:#080;">Wallet Credited</td><td style="text-align: right; padding: 2px 0; color:#080;">₹<?= number_format($invoice->wallet_amount_credited, 2) ?></td></tr>
            <?php endif; ?>
            <?php if ($print_show_pending): ?>
            <?php
                // Only meaningful for a plain cash sale where the customer handed over
                // more than the bill — amount_tendered is NULL on every invoice billed
                // before this column existed, and equals paid_amount for split/card/UPI
                // (no "tendered more than owed" concept there), so this is silently 0
                // in both of those cases rather than needing its own guard everywhere.
                // Excess already credited to the wallet isn't physical change, hence
                // the wallet_amount_credited subtraction.
                $change_amt = round(
                    (float)($invoice->amount_tendered ?? $invoice->paid_amount)
                    - (float)$invoice->paid_amount
                    - (float)($invoice->wallet_amount_credited ?? 0),
                    2
                );
            ?>
            <?php if ($change_amt > 0.004): ?>
            <tr><td style="padding: 2px 0;">Cash Tendered</td><td style="text-align: right; padding: 2px 0;">₹<?= number_format($invoice->amount_tendered, 2) ?></td></tr>
            <tr style="font-weight: bold;"><td style="padding: 2px 0;">Change Returned</td><td style="text-align: right; padding: 2px 0;">₹<?= number_format($change_amt, 2) ?></td></tr>
            <?php endif; ?>
            <tr><td style="padding: 2px 0;">Paid Amount</td><td style="text-align: right; padding: 2px 0;">₹<?= number_format($invoice->paid_amount, 2) ?></td></tr>
            <?php if ($invoice->payment_mode === 'split' && !empty($splits)): ?>
            <?php foreach ($splits as $sp): ?>
            <tr style="color:#555;">
                <td style="padding: 1px 0 1px 8px; font-size:9px;">
                    — <?= strtoupper($sp->payment_mode) ?><?= ($sp->payment_mode === 'card' && !empty($sp->card_type)) ? ' (' . ucfirst($sp->card_type) . ')' : '' ?>
                </td>
                <td style="text-align:right; padding: 1px 0; font-size:9px;">₹<?= number_format($sp->amount, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($invoice->due_amount > 0): ?>
            <tr style="color: #c00; font-weight: bold;"><td style="padding: 2px 0;">Balance Due</td><td style="text-align: right; padding: 2px 0;">₹<?= number_format($invoice->due_amount, 2) ?></td></tr>
            <?php else: ?>
            <tr style="color: #080; font-weight: bold;"><td style="padding: 2px 0;">Balance Due</td><td style="text-align: right; padding: 2px 0;">₹0.00</td></tr>
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
            $gst_groups[$key]['taxable'] += round($item->taxable_amt * $th_cd_ratio, 2);
            $gst_groups[$key]['gst']     += round($item->gst_amount * $th_cd_ratio, 2);
        }
        if (!empty($gst_groups)):
        ?>
        <div style="font-size: 8.5px; border-bottom: 1px dashed #000; padding: 4px 0 6px 0; margin-bottom: 6px; line-height: 1.35;">
            <div style="font-weight: bold; text-transform: uppercase; margin-bottom: 2px; border-bottom: 1px dotted #ccc; padding-bottom: 1px;">GST TAX SUMMARY</div>
            <?php foreach ($gst_groups as $g): $half = round($g['gst']/2, 2); ?>
                <div style="margin-bottom: 3px;">
                    HSN: <?= htmlspecialchars($g['hsn'] ?? '—') ?> | TaxVal: ₹<?= number_format($g['taxable'], 2) ?><br>
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
        <div style="text-align: center; margin: 8px auto; padding: 6px; border: 1px dashed #666; border-radius: 4px; background: #fafafa; max-width: 140px;">
            <div style="font-weight: bold; font-size: 8.5px; text-transform: uppercase; margin-bottom: 3px;">Scan to Pay</div>
            <div id="payment-qr" style="display: inline-block; width: 72px; height: 72px; margin-bottom: 3px;"></div>
            <div style="font-size: 8px; color: #555;">Amt: <strong>₹<?= number_format($due_amt, 2) ?></strong></div>
        </div>
        <?php endif; ?>

        <!-- Amount Words -->
        <div style="font-size: 8.5px; text-align: center; font-style: italic; margin-bottom: 6px; border-bottom: 1px dotted #ccc; padding-bottom: 4px;">
            Rupees <?= inr_words($invoice->total_amount) ?> Only
        </div>

        <!-- Scheme Applied -->
        <?php if (!empty($schemes_applied)): ?>
        <div style="font-size: 7.5px; color: #555; margin-bottom: 4px;">
            <?php foreach ($schemes_applied as $sa): ?>
            Scheme: <?= htmlspecialchars($sa->scheme_name) ?><?= $sa->discount_given > 0 ? ' (-₹'.number_format($sa->discount_given,2).')' : ' (free item)' ?><br>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Remarks -->
        <?php if (!empty($invoice->notes)): ?>
        <div style="font-size: 8.5px; margin-bottom: 6px; padding: 4px; border: 1px solid #ddd; background: #f9f9f9; line-height: 1.3;">
            <strong>Remarks:</strong> <?= nl2br(htmlspecialchars($invoice->notes)) ?>
        </div>
        <?php endif; ?>

        <!-- Outstanding / Wallet -->
        <?php if (!empty($customer)): ?>
            <?php if ($customer->show_outstanding_in_print && $customer->balance > 0): ?>
            <div style="margin: 6px 0; padding: 4px 6px; border: 1px solid #c00; border-radius: 3px; background: #fff5f5; display: flex; justify-content: space-between; align-items: center; font-size: 8.5px; color: #c00; font-weight: bold;">
                <span>Outstanding Balance:</span>
                <span>₹<?= number_format($customer->balance, 2) ?></span>
            </div>
            <?php endif; ?>
            <?php if (isset($customer->wallet_balance) && $customer->wallet_balance > 0): ?>
            <div style="margin: 6px 0; padding: 4px 6px; border: 1px solid #080; border-radius: 3px; background: #f5fff5; display: flex; justify-content: space-between; align-items: center; font-size: 8.5px; color: #080; font-weight: bold;">
                <span>Wallet Balance:</span>
                <span>₹<?= number_format($customer->wallet_balance, 2) ?></span>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($show_loyalty)): ?>
        <?php $this->load->view('billing/_loyalty_block', ['invoice' => $invoice, 'items' => $items, 'lb_narrow' => true]); ?>
        <?php endif; ?>

        <?php $this->load->view('billing/_warranty_card', [
            'items' => $items, 'wc_narrow' => true,
            'serials' => $serials ?? [], 'item_warranty' => $item_warranty ?? [],
        ]); ?>

        <!-- Footer terms & signatory -->
        <?php
        $has_ev_line = false;
        foreach ($items as $it) {
            if (!empty($it->chassis_no) || !empty($it->battery_serial) || !empty($it->charger_serial)) {
                $has_ev_line = true;
                break;
            }
        }
        ?>
        <div style="font-size: 8.5px; line-height: 1.35; margin-top: 6px; text-align: center;">
            <?php if ($has_ev_line): ?>
                <div style="text-align: left; font-size: 8px; margin-bottom: 6px; border: 1px solid #16a34a; border-radius: 3px; padding: 4px; background: #f0fdf4;">
                    <strong style="color:#15803d;">Vehicle Handover:</strong> Received EV with keys, charger, battery &amp; toolkit in good condition.<br><br>
                    Customer Sign: ____________________
                </div>
            <?php endif; ?>
            <?php if (!empty($company['invoice_terms'])): ?>
                <div style="text-align: left; font-size: 8px; margin-bottom: 6px; border-top: 1px dotted #ccc; padding-top: 4px;">
                    <strong>Terms:</strong> <?= htmlspecialchars($company['invoice_terms']) ?>
                </div>
            <?php endif; ?>
            
            <?php
            $cust_phone_raw = preg_replace('/\D/', '', $invoice->customer_phone ?? '');
            if ($show_portal_qr && $cust_phone_raw):
                $portal_login_url = base_url('portal/login') . '?phone=' . urlencode($cust_phone_raw);
            ?>
            <div style="margin-bottom: 8px; border-top: 1px dotted #ccc; padding-top: 4px; display: flex; flex-direction: column; align-items: center;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&qzone=4&ecc=M&data=<?= rawurlencode($portal_login_url) ?>" style="width: 120px; height: 120px; margin-bottom: 2px;" alt="Customer Portal QR">
                <div style="font-size: 7.5px; color: #666;">Customer Portal Login</div>
            </div>
            <?php endif; ?>

            <?php if (($invoice->einvoice_status ?? '') === 'generated' && !empty($invoice->einvoice_irn)): ?>
            <div style="margin-bottom: 8px; border-top: 1px dotted #ccc; padding-top: 4px; display: flex; flex-direction: column; align-items: center;">
                <?php if (!empty($invoice->einvoice_qr_data)): ?>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&qzone=2&ecc=L&data=<?= rawurlencode($invoice->einvoice_qr_data) ?>" style="width: 120px; height: 120px; margin-bottom: 2px;" alt="e-Invoice QR">
                <?php endif; ?>
                <div style="font-size: 7px; color: #444; word-break: break-all; max-width: 210px; text-align: center;"><strong>IRN:</strong> <?= htmlspecialchars($invoice->einvoice_irn) ?></div>
                <?php if (!empty($invoice->einvoice_ack_no)): ?>
                <div style="font-size: 7px; color: #666;">Ack No: <?= htmlspecialchars($invoice->einvoice_ack_no) ?><?php if (!empty($invoice->einvoice_ack_date)): ?> | <?= date('d-M-Y', strtotime($invoice->einvoice_ack_date)) ?><?php endif; ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div style="position: relative; display: inline-block; margin-top: 6px;">
                <div style="font-weight: bold; font-size: 9px;">
                    For <?= htmlspecialchars($company['company_name'] ?? '') ?>
                </div>
                <div style="position: absolute; left: 25px; top: -2px; opacity: 0.85; pointer-events: none; z-index: 5; transform: rotate(-10deg);">
                    <div style="width:50px; height:50px; border:2px solid #1a5fb4; border-radius:50%; color:#1a5fb4; display:flex; align-items:center; justify-content:center; text-align:center;">
                        <span style="font-weight:800; font-size:6.5px; line-height:1.05; text-transform:uppercase; padding:3px;"><?= htmlspecialchars($company['company_name'] ?? '') ?></span>
                    </div>
                </div>
                <div style="margin-top: 28px; border-top: 1px dotted #666; display: inline-block; width: 100px; padding-top: 2px;">
                    Authorised Signatory
                </div>
            </div>
            
            <div style="margin-top: 8px; font-size: 8.5px; color: #555; font-weight: bold;">
                Thank you for your visit!
            </div>
        </div>
    </div>

<?php elseif ($printer_type === 'A5'): ?>
    <?php $this->load->view('billing/_print_a5', compact('invoice', 'items', 'company') + ['outlet' => $outlet ?? null, 'print_show_tax' => $print_show_tax, 'print_show_pending' => $print_show_pending]); ?>

<?php else: ?>
    <!-- ==================== STANDARD A4 LAYOUT ==================== -->
    <div class="page a4-layout">
        <?php if (in_array($invoice->status, ['cancelled','paid','partial'])): ?>
        <div class="stamp"><?= $invoice->status === 'paid' ? 'PAID' : ($invoice->status === 'cancelled' ? 'CANCELLED' : 'PARTIAL') ?></div>
        <?php endif; ?>

        <!-- ===== HEADER ===== -->
        <?php
        // Use outlet-specific details if this invoice was billed from a specific outlet
        $hdr_name    = ($outlet && !empty($outlet->name))    ? $outlet->name    : ($company['company_name']    ?? '');
        $hdr_address = ($outlet && !empty($outlet->address)) ? $outlet->address : ($company['company_address'] ?? '');
        $hdr_city    = ($outlet && !empty($outlet->city))    ? $outlet->city    : '';
        $hdr_state   = ($outlet && !empty($outlet->state))   ? $outlet->state   : ($company['company_state']   ?? '');
        $hdr_pincode = ($outlet && !empty($outlet->pincode)) ? $outlet->pincode : '';
        $hdr_phone   = ($outlet && !empty($outlet->phone))   ? $outlet->phone   : ($company['company_phone']   ?? '');
        $hdr_email   = ($outlet && !empty($outlet->email))   ? $outlet->email   : ($company['company_email']   ?? '');
        $hdr_gstin   = ($outlet && !empty($outlet->gstin))   ? $outlet->gstin   : ($company['company_gstin']   ?? '');
        $hdr_pan     = ($outlet && !empty($outlet->pan))     ? $outlet->pan     : ($company['company_pan']     ?? '');
        $hdr_sc      = $company['company_state_code'] ?? '09';
        // Show company name as parent brand if outlet name differs
        $parent_brand = ($outlet && $outlet->name !== ($company['company_name'] ?? '')) ? ($company['company_name'] ?? '') : '';
        ?>
        <div class="inv-header" style="display: flex; gap: 15px; align-items: center;">
            <?php if (!empty($company_logo) && file_exists('./' . $company_logo)): ?>
                <div class="company-logo-container" style="flex-shrink: 0;">
                    <img src="<?= base_url($company_logo) ?>" alt="Logo" style="max-height: 70px; max-width: 150px; object-fit: contain;">
                </div>
            <?php endif; ?>
            <div style="flex-grow: 1;">
                <?php if (!empty($parent_brand)): ?>
                    <div style="font-size:10px; color:#888; text-transform:uppercase; letter-spacing:.5px; margin-bottom:1px;"><?= htmlspecialchars($parent_brand) ?></div>
                <?php endif; ?>
                <div class="company-name"><?= htmlspecialchars($hdr_name) ?></div>
                <div class="company-sub">
                    <?php
                    $addr_parts = array_filter([
                        $hdr_address,
                        $hdr_city,
                        $hdr_state ? ($hdr_pincode ? "$hdr_state – $hdr_pincode" : $hdr_state) : $hdr_pincode
                    ]);
                    echo htmlspecialchars(implode(', ', $addr_parts));
                    ?><br>
                    <?php if (!empty($hdr_phone)): ?>Ph: <?= htmlspecialchars($hdr_phone) ?><?php endif; ?>
                    <?php if (!empty($hdr_email)): ?>&nbsp;|&nbsp; <?= htmlspecialchars($hdr_email) ?><?php endif; ?><br>
                    <?php if (!empty($hdr_gstin)): ?>
                        GSTIN: <strong><?= htmlspecialchars($hdr_gstin) ?></strong>
                        &nbsp;|&nbsp;
                    <?php endif; ?>
                    State: <?= htmlspecialchars($hdr_state) ?> (<?= htmlspecialchars($hdr_sc) ?>)
                    <?php if ($print_show_tax && !empty($hdr_pan)): ?>
                        &nbsp;|&nbsp; PAN: <?= htmlspecialchars($hdr_pan) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="inv-title" style="flex-shrink: 0; text-align: right;">
                <h2><?= $print_show_tax ? 'Tax Invoice' : 'Retail Invoice' ?></h2>
                <p><?= ucfirst($invoice->invoice_type) ?><?php if ($print_show_tax): ?> &middot; <?= $invoice->is_interstate ? 'Interstate (IGST)' : 'Intrastate (CGST+SGST)' ?><?php endif; ?></p>
                <?php if ($outlet): ?>
                    <p style="font-size:10px; color:#888; margin-top:3px;">Outlet: <strong><?= htmlspecialchars($outlet->code) ?></strong></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== INVOICE META ===== -->
        <div class="inv-meta">
            <div class="meta-box">Invoice No: <span><?= $invoice->invoice_no ?></span></div>
            <div class="meta-box">Date: <span><?= date('d-M-Y', strtotime($invoice->invoice_date)) ?></span></div>
            <?php if (!empty($invoice->created_by_name)): ?>
            <div class="meta-box">Cashier: <span><?= htmlspecialchars($invoice->created_by_name) ?></span></div>
            <?php endif; ?>
            <div class="meta-box">Time: <span><?= $invoice->created_at ? date('h:i A', strtotime($invoice->created_at)) : '' ?></span></div>
            <div class="meta-box">Payment: <span><?= ucfirst($invoice->payment_mode) ?></span></div>
            <div class="meta-box">Status: <span><?= strtoupper($invoice->status) ?></span></div>
        </div>

        <!-- ===== PARTIES ===== -->
        <div class="parties">
            <div class="party-box">
                <h4>Bill To (Buyer)</h4>
                <div class="name"><?= htmlspecialchars($invoice->customer_name) ?></div>
                <p>
                    <?php if ((int)$invoice->is_delivery !== 0): ?>
                        <?= htmlspecialchars($invoice->customer_address ?? '') ?>
                        <?php if ($invoice->customer_city): ?>, <?= htmlspecialchars($invoice->customer_city) ?><?php endif; ?><br>
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
                    <?php if ($invoice->customer_gstin): ?>GSTIN: <strong><?= htmlspecialchars($invoice->customer_gstin) ?></strong><br><?php endif; ?>
                    <?php if (!empty($customer->pan)): ?>PAN: <strong><?= htmlspecialchars($customer->pan) ?></strong><br><?php endif; ?>
                    <?php if (!empty($customer->aadhaar_number)): ?>Aadhaar: <strong><?= htmlspecialchars('XXXXXXXX' . substr($customer->aadhaar_number, -4)) ?></strong><br><?php endif; ?>
                </p>
            </div>
            <div class="party-box">
                <h4>Sold By (Supplier)</h4>
                <div class="name"><?= htmlspecialchars($company['company_name'] ?? '') ?></div>
                <p>
                    <?= htmlspecialchars($company['company_address'] ?? '') ?><br>
                    <?php if (!empty($company['company_gstin'])): ?>GSTIN: <strong><?= htmlspecialchars($company['company_gstin'] ?? '') ?></strong><br><?php endif; ?>
                    State: <?= htmlspecialchars($company['company_state'] ?? 'Punjab') ?> | Code: <?= htmlspecialchars($company['company_state_code'] ?? '07') ?>
                </p>
            </div>
        </div>

        <!-- ===== ITEMS TABLE ===== -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:4%">#</th>
                    <th style="width:<?= $print_show_tax ? '24%' : '46%' ?>">Product Description</th>
                    <?php if ($print_show_tax): ?>
                    <th style="width:9%">HSN/SAC</th>
                    <?php endif; ?>
                    <th style="width:6%">Qty</th>
                    <th style="width:6%">Unit</th>
                    <th style="width:9%">MRP</th>
                    <th style="width:9%">Rate (₹)</th>
                    <th style="width:8%">Discount</th>
                    <?php if ($print_show_tax): ?>
                    <th style="width:9%">Taxable Amt</th>
                    <th style="width:5%">GST%</th>
                    <?php if ($invoice->is_interstate): ?>
                    <th style="width:8%">IGST</th>
                    <?php else: ?>
                    <th style="width:6%">CGST</th>
                    <th style="width:6%">SGST</th>
                    <?php endif; ?>
                    <?php endif; ?>
                    <th style="width:10%">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $i => $item): ?>
                <?php
                $a4_disp_taxable = round($item->taxable_amt * $th_cd_ratio, 2);
                $a4_disp_gst     = round($item->gst_amount * $th_cd_ratio, 2);
                $a4_disp_total   = round($item->total * $th_cd_ratio, 2);
                $half_gst  = round($a4_disp_gst / 2, 2);
                $half_gst2 = $a4_disp_gst - $half_gst;
                ?>
                <tr>
                    <td class="center"><?= $i + 1 ?></td>
                    <td>
                        <div style="font-weight:bold;"><?= htmlspecialchars(strtoupper($item->product_name)) ?><?php if (!empty($item->size_name) || !empty($item->colour_name)): ?> <span style="font-size:10px;font-weight:normal;color:#555;">[<?= htmlspecialchars(($item->colour_name ? $item->colour_name . ' / ' : '') . $item->size_name) ?>]</span><?php endif; ?></div>
                        <?php if(!empty($item->description)): ?><div style="font-size:9.5px;color:#555;margin-top:1px;"><?= htmlspecialchars($item->description) ?></div><?php endif; ?>
                        <?php $this->load->view('billing/_serial_line', [
                            'item' => $item, 'sl_size' => '9.5px',
                            'serials' => $serials ?? [], 'item_warranty' => $item_warranty ?? [],
                        ]); ?>
                    </td>
                    <?php if ($print_show_tax): ?>
                    <td class="center"><code><?= htmlspecialchars($item->hsn_code ?? '—') ?></code></td>
                    <?php endif; ?>
                    <td class="center"><?= $item->qty ?></td>
                    <td class="center"><?= $item->unit ?></td>
                    <td class="right">₹ <?= number_format(($item->mrp > 0) ? $item->mrp : ($item->product_mrp ?? 0), 2) ?>
                    <?php
                        $a4_mrp = ($item->mrp > 0) ? $item->mrp : ($item->product_mrp ?? 0);
                        $a4_sd  = round((float)$a4_mrp - (float)$item->rate, 2);
                    ?>
                    <?php if ($a4_sd > 0): ?>
                    <div style="font-size:9px;color:#15803d;font-weight:normal;">S.D ₹<?= number_format($a4_sd, 2) ?></div>
                    <?php endif; ?>
                    </td>
                    <td class="right">₹ <?= number_format($item->rate, 2) ?></td>
                    <td class="right">₹ <?= number_format($item->discount, 2) ?></td>
                    <?php if ($print_show_tax): ?>
                    <td class="right">₹ <?= number_format($a4_disp_taxable, 2) ?></td>
                    <td class="center"><?= $item->gst_percent ?>%</td>
                    <?php if ($invoice->is_interstate): ?>
                    <td class="right">₹ <?= number_format($a4_disp_gst, 2) ?></td>
                    <?php else: ?>
                    <td class="right">₹ <?= number_format($half_gst, 2) ?></td>
                    <td class="right">₹ <?= number_format($half_gst2, 2) ?></td>
                    <?php endif; ?>
                    <?php endif; ?>
                    <td class="right"><strong>₹ <?= number_format($a4_disp_total, 2) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ===== GST SUMMARY ===== -->
        <?php if ($print_show_tax): ?>
        <table class="gst-table">
            <thead>
                <tr>
                    <th>HSN/SAC</th>
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
            // Group by GST rate for summary (apply CD ratio)
            $gst_groups = [];
            foreach ($items as $item) {
                $key = $item->gst_percent . '_' . ($item->hsn_code ?? '');
                if (!isset($gst_groups[$key])) {
                    $gst_groups[$key] = ['hsn'=>$item->hsn_code,'rate'=>$item->gst_percent,'taxable'=>0,'gst'=>0];
                }
                $gst_groups[$key]['taxable'] += round($item->taxable_amt * $th_cd_ratio, 2);
                $gst_groups[$key]['gst']     += round($item->gst_amount * $th_cd_ratio, 2);
            }
            foreach ($gst_groups as $g):
                $half = round($g['gst']/2, 2);
            ?>
            <tr>
                <td><?= htmlspecialchars($g['hsn'] ?? '—') ?></td>
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
        <?php endif; ?>

        <!-- ===== TOTALS & SCAN TO PAY ===== -->
        <?php
        $upi_id = $company['company_upi_id'] ?? '';
        $due_amt = $invoice->due_amount;
        $has_upi = ($print_show_pending && !empty($upi_id) && $due_amt > 0);
        ?>
        <div class="totals-section" style="display: flex; <?= $has_upi ? 'justify-content: space-between;' : 'justify-content: flex-end;' ?> align-items: flex-end; margin-top: 4px;">
            <?php if ($has_upi): 
                $merchant_name = !empty($company['company_upi_name']) ? $company['company_upi_name'] : ($company['company_name'] ?? 'View & Chew');
                $upi_url = "upi://pay?pa=" . urlencode($upi_id) . "&pn=" . urlencode($merchant_name) . "&am=" . urlencode(number_format($due_amt, 2, '.', '')) . "&cu=INR&tn=" . urlencode($invoice->invoice_no);
            ?>
                <div style="border: 1px dashed #444; border-radius: 4px; padding: 10px; background: #fafafa; display: flex; align-items: center; gap: 12px; width: 260px; text-align: left;">
                    <div id="payment-qr" style="width: 72px; height: 72px;"></div>
                    <div>
                        <div style="font-weight: bold; font-size: 11px; color: #111; text-transform: uppercase; margin-bottom: 3px; letter-spacing: 0.5px;">Scan &amp; Pay</div>
                        <div style="font-size: 9px; color: #555; line-height: 1.35;">
                            Scan with any UPI app<br>
                            (GPay, PhonePe, Paytm, BHIM)<br>
                            Amount: <strong>₹ <?= number_format($due_amt, 2) ?></strong>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!isset($total_qty)) { $total_qty = 0; foreach ($items as $_qi2) { $total_qty += (float)$_qi2->qty; } } ?>
            <table class="totals-table">
                <tr><td>Subtotal (Qty: <?= $total_qty == floor($total_qty) ? (int)$total_qty : $total_qty ?>)</td><td>₹ <?= number_format($invoice->subtotal, 2) ?></td></tr>
                <?php if ($invoice->discount > 0): ?>
                <tr><td>Discount</td><td>- ₹ <?= number_format($invoice->discount, 2) ?></td></tr>
                <?php endif; ?>
                <?php if ($print_show_tax): ?>
                <tr><td>Taxable Amount</td><td>₹ <?= number_format($invoice->taxable_amount, 2) ?></td></tr>
                <?php if ($invoice->is_interstate): ?>
                <tr><td>IGST</td><td>₹ <?= number_format($invoice->igst_amount, 2) ?></td></tr>
                <?php else: ?>
                <tr><td>CGST</td><td>₹ <?= number_format($invoice->cgst_amount, 2) ?></td></tr>
                <tr><td>SGST</td><td>₹ <?= number_format($invoice->sgst_amount, 2) ?></td></tr>
                <?php endif; ?>
                <?php endif; ?>
                <?php if (!empty($invoice->cd_percent) && $invoice->cd_percent > 0): ?>
                <tr><td>CD (<?= $invoice->cd_percent ?>%)</td><td style="color:#c00;">- ₹ <?= number_format($invoice->cd_amount, 2) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($invoice->round_off) && $invoice->round_off != 0): ?>
                <tr><td>Round Off</td><td><?= $invoice->round_off > 0 ? '+' : '-' ?> ₹ <?= number_format(abs($invoice->round_off), 2) ?></td></tr>
                <?php endif; ?>
                <tr class="grand"><td>Grand Total</td><td>₹ <?= number_format($invoice->total_amount, 2) ?></td></tr>
                <?php
                    if (!isset($mrp_total)) {
                        $mrp_total = 0;
                        foreach ($items as $_it2) { $mrp_total += (float)($_it2->mrp ?? $_it2->rate) * (float)$_it2->qty; }
                    }
                    $total_saving2 = round($mrp_total - $invoice->total_amount, 2);
                ?>
                <?php if ($total_saving2 > 0): ?>
                <tr style="color:#15803d;font-size:14px;border-top:1px dashed #15803d;"><td style="padding:3px 0;font-weight:600;">🎉 You Saved</td><td style="padding:3px 0;font-weight:700;">₹ <?= number_format($total_saving2, 2) ?></td></tr>
                <?php endif; ?>
                <?php if (isset($invoice->wallet_amount_used) && $invoice->wallet_amount_used > 0): ?>
                <tr><td>Wallet Applied</td><td style="color:#080;">- ₹ <?= number_format($invoice->wallet_amount_used, 2) ?></td></tr>
                <?php endif; ?>
                <?php if (isset($invoice->wallet_amount_credited) && $invoice->wallet_amount_credited > 0): ?>
                <tr><td>Wallet Credited</td><td style="color:#080;">₹ <?= number_format($invoice->wallet_amount_credited, 2) ?></td></tr>
                <?php endif; ?>
                <?php if ($print_show_pending): ?>
                <tr><td>Paid</td><td>₹ <?= number_format($invoice->paid_amount, 2) ?></td></tr>
                <tr><td>Balance Due</td><td style="color:<?= $invoice->due_amount > 0 ? '#c00':'#090' ?>">₹ <?= number_format(max(0, $invoice->due_amount), 2) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Amount in words -->
        <div class="amt-words">
            Amount in words: <span>Rupees <?= inr_words($invoice->total_amount) ?> Only</span>
        </div>

        <?php if (!empty($schemes_applied)): ?>
        <div style="font-size:9px; color:#555; margin: 4px 0;">
            <?php foreach ($schemes_applied as $sa): ?>
            Scheme: <?= htmlspecialchars($sa->scheme_name) ?><?= $sa->discount_given > 0 ? ' (-₹'.number_format($sa->discount_given,2).')' : ' (free item)' ?><br>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($invoice->notes)): ?>
        <div style="font-size:11px; margin: 8px 0; padding:6px 10px; border:1px solid #ddd; background:#f9f9f9;">
            <strong>Remarks:</strong> <?= nl2br(htmlspecialchars($invoice->notes)) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($customer)): ?>
            <?php if ($customer->show_outstanding_in_print && $customer->balance > 0): ?>
            <div style="margin:10px 0; padding:8px 12px; border:2px solid #c00; border-radius:4px; background:#fff5f5; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-size:11px; color:#c00; font-weight:bold;">
                    &#9888; Outstanding Balance for <?= htmlspecialchars($customer->name) ?>
                </div>
                <div style="font-size:14px; font-weight:bold; color:#c00;">
                    ₹ <?= number_format($customer->balance, 2) ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if (isset($customer->wallet_balance) && $customer->wallet_balance > 0): ?>
            <div style="margin:10px 0; padding:8px 12px; border:2px solid #080; border-radius:4px; background:#f5fff5; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-size:11px; color:#080; font-weight:bold;">
                    👛 Wallet Balance for <?= htmlspecialchars($customer->name) ?>
                </div>
                <div style="font-size:14px; font-weight:bold; color:#080;">
                    ₹ <?= number_format($customer->wallet_balance, 2) ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($show_loyalty)): ?>
        <?php $this->load->view('billing/_loyalty_block', ['invoice' => $invoice, 'items' => $items, 'lb_narrow' => false]); ?>
        <?php endif; ?>

        <?php $this->load->view('billing/_warranty_card', [
            'items' => $items, 'wc_narrow' => false,
            'serials' => $serials ?? [], 'item_warranty' => $item_warranty ?? [],
        ]); ?>

        <!-- Vehicle Delivery & Handover Acknowledgement Block -->
        <?php if ($has_ev_line): ?>
        <div style="margin-top: 10px; margin-bottom: 8px; border: 1.5px solid #0f766e; border-radius: 4px; padding: 6px 10px; background: #f0fdfa; font-size: 10px;">
            <div style="font-weight: bold; color: #0f766e; font-size: 10.5px; margin-bottom: 3px; display: flex; justify-content: space-between;">
                <span><i class="fas fa-handshake"></i> VEHICLE DELIVERY &amp; HANDOVER ACKNOWLEDGEMENT</span>
                <span style="font-weight: normal; font-size: 9px; color: #334155;">Checklist: Keys (2) [&#10003;] | Battery [&#10003;] | Charger [&#10003;] | Tool Kit &amp; Manual [&#10003;]</span>
            </div>
            <p style="margin: 0; line-height: 1.35; color: #1e293b; font-size: 9.5px;">
                I hereby acknowledge receipt of the electric vehicle mentioned above in completely satisfactory condition along with genuine battery, compatible charger, ignition keys, toolkit and owner's manual. The chassis and motor numbers have been physically verified by me. I accept the manufacturer warranty policies and terms of service.
            </p>
            <div style="display: flex; justify-content: space-between; margin-top: 24px; padding-top: 4px;">
                <div style="width: 45%; border-top: 1px dotted #333; text-align: left; font-size: 9.5px; color: #333;">
                    Customer Signature: _______________________
                </div>
                <div style="width: 45%; border-top: 1px dotted #333; text-align: right; font-size: 9.5px; color: #333;">
                    Delivery Date: <strong><?= date('d-M-Y', strtotime($invoice->invoice_date)) ?></strong>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="inv-footer">
            <div class="terms">
                <strong>Terms &amp; Conditions:</strong><br>
                <?= htmlspecialchars($company['invoice_terms'] ?? 'Goods once sold will not be taken back. Subject to Mohali Punjab jurisdiction.') ?>
                <?php
                $cust_phone_raw = preg_replace('/\D/', '', $invoice->customer_phone ?? '');
                if ($show_portal_qr && $cust_phone_raw):
                    $portal_login_url = base_url('portal/login') . '?phone=' . urlencode($cust_phone_raw);
                ?>
                <div style="margin-top:6px;font-size:9px;color:#555;">
                    Scan QR to view invoice &amp; place orders online.<br>
                    Portal: Mobile No / Password: Last 4 digits
                </div>
                <?php endif; ?>
            </div>
            <?php if ($show_portal_qr && !empty($cust_phone_raw)): ?>
            <div style="text-align:center;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&qzone=4&ecc=M&data=<?= rawurlencode($portal_login_url) ?>" style="width: 92px; height: 92px;" alt="Customer Portal QR">
                <div style="font-size:8.5px;color:#555;margin-top:3px;">Customer Portal</div>
            </div>
            <?php endif; ?>
            <?php if (($invoice->einvoice_status ?? '') === 'generated' && !empty($invoice->einvoice_irn)): ?>
            <div style="text-align:center; max-width: 140px;">
                <?php if (!empty($invoice->einvoice_qr_data)): ?>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=400x400&qzone=2&ecc=L&data=<?= rawurlencode($invoice->einvoice_qr_data) ?>" style="width: 92px; height: 92px;" alt="e-Invoice QR">
                <?php endif; ?>
                <div style="font-size:7px;color:#444;word-break:break-all;margin-top:3px;"><strong>IRN:</strong> <?= htmlspecialchars($invoice->einvoice_irn) ?></div>
                <?php if (!empty($invoice->einvoice_ack_no)): ?>
                <div style="font-size:7px;color:#666;">Ack No: <?= htmlspecialchars($invoice->einvoice_ack_no) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="signature" style="position: relative;">
                <strong>For <?= htmlspecialchars($company['company_name'] ?? 'View & Chew') ?></strong>
                <div style="position: absolute; right: 14px; bottom: 4px; pointer-events: none; z-index: 10; opacity: 0.85; transform: rotate(-10deg);">
                    <div style="width:64px; height:64px; border:2.5px solid #1a5fb4; border-radius:50%; color:#1a5fb4; display:flex; align-items:center; justify-content:center; text-align:center;">
                        <span style="font-weight:800; font-size:8px; line-height:1.05; text-transform:uppercase; padding:4px;"><?= htmlspecialchars($company['company_name'] ?? '') ?></span>
                    </div>
                </div>
                <p>Authorised Signatory</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$cust_phone_raw = preg_replace('/\D/', '', $invoice->customer_phone ?? '');
$has_upi = ($print_show_pending && !empty($company['company_upi_id']) && $invoice->due_amount > 0);
if (!empty($cust_phone_raw) || $has_upi):
?>
<?php if ($has_upi): ?>
<script src="<?= base_url('assets/js/qrcode.min.js') ?>"></script>
<script>
new QRCode(document.getElementById("payment-qr"), {
    text: "<?= addslashes($upi_url ?? '') ?>",
    width: 72,
    height: 72,
    colorDark: "#000000",
    colorLight: "#ffffff"
});
</script>
<?php endif; ?>
<?php endif; ?>
</body>
</html>
