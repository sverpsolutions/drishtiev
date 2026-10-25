<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order / GRN - <?= $purchase->purchase_no ?></title>
    <style>
        @page { size: landscape; margin: 5mm; }
        body { font-family: "Segoe UI", Arial, sans-serif; font-size: 10px; margin: 0; padding: 10px; color: #000; }
        .print-wrap { width: 100%; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #0055a5; padding-bottom: 5px; margin-bottom: 10px; }
        .company-logo { max-height: 50px; max-width: 150px; object-fit: contain; margin-bottom: 4px; }
        .company-info h2 { margin: 0 0 2px 0; font-size: 18px; text-transform: uppercase; color: #0055a5; }
        .company-info p { margin: 2px 0; font-size: 11px; color: #333; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0 0 2px 0; font-size: 20px; color: #0055a5; text-transform: uppercase; }
        .doc-title p { margin: 2px 0; font-size: 12px; font-weight: bold; }
        
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .supplier-box, .purchase-box { width: 49%; }
        .box-title { background: #0055a5; color: #fff; padding: 3px 5px; font-weight: bold; border: 1px solid #0055a5; font-size: 11px; text-transform: uppercase; }
        .box-content { border: 1px solid #0055a5; border-top: none; padding: 5px; height: 50px; }
        
        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9px; }
        table.items-table th, table.items-table td { border: 1px solid #999; padding: 2px 3px; text-align: left; }
        table.items-table th { background: #f0f4f8; color: #0055a5; font-weight: bold; text-align: center; font-size: 9px; text-transform: uppercase; }
        table.items-table td.text-right, table.items-table th.text-right { text-align: right; }
        table.items-table td.text-center, table.items-table th.text-center { text-align: center; }
        table.items-table td { white-space: nowrap; }
        table.items-table td.wrap-text { white-space: normal; word-break: break-all; min-width: 90px; }
        
        .totals-row { display: flex; justify-content: flex-end; }
        .totals-box { width: 280px; border: 1px solid #0055a5; padding: 4px; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 2px 3px; font-size: 10px; }
        .totals-table td:nth-child(2) { text-align: right; font-weight: bold; }
        .grand-total-row { border-top: 1px solid #0055a5; font-size: 13px !important; color: #0055a5; }
        
        .footer { margin-top: 30px; display: flex; justify-content: space-between; padding: 0 40px; }
        .signature-box { text-align: center; width: 180px; }
        .signature-line { border-top: 1px solid #000; margin-top: 50px; padding-top: 4px; font-weight: bold; font-size: 11px; text-transform: uppercase; color: #0055a5; }
        
        @media print {
            body { padding: 0; }
            .print-wrap { border: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div style="text-align: right; margin-bottom: 10px;" class="no-print">
    <button onclick="window.print()" style="padding: 6px 15px; background: #0055a5; color: #fff; border: 1px solid #004080; cursor: pointer; font-size: 14px; font-weight: bold; border-radius: 2px;">Print Document</button>
</div>

<div class="print-wrap">
    
    <div class="header">
        <div class="company-info">
            <?php if (!empty($company['company_logo']) && file_exists('./' . $company['company_logo'])): ?>
                <img src="<?= base_url($company['company_logo']) ?>" class="company-logo" alt="Logo">
            <?php endif; ?>
            <h2><?= htmlspecialchars($company['company_name'] ?? 'Your Company Name') ?></h2>
            <p><?= htmlspecialchars($company['company_address'] ?? 'Company Address') ?></p>
            <p><strong>GSTIN:</strong> <?= htmlspecialchars($company['company_gstin'] ?? 'N/A') ?></p>
        </div>
        <div class="doc-title">
            <h1>GOODS RECEIPT NOTE</h1>
            <p>GRN / PO No: <?= $purchase->purchase_no ?></p>
            <p>Date: <?= date('d-M-Y', strtotime($purchase->invoice_date)) ?></p>
        </div>
    </div>
    
    <div class="meta-row">
        <div class="supplier-box">
            <div class="box-title">Supplier Details</div>
            <div class="box-content">
                <strong><?= htmlspecialchars($purchase->supplier_name) ?></strong><br>
                <?= htmlspecialchars($purchase->supplier_address ?? '') ?> <?= htmlspecialchars($purchase->supplier_city ?? '') ?><br>
                <strong>GSTIN:</strong> <?= htmlspecialchars($purchase->supplier_gstin ?? 'N/A') ?>
            </div>
        </div>
        <div class="purchase-box">
            <div class="box-title">Invoice Details</div>
            <div class="box-content">
                <table style="width:100%; border:none; padding:0; margin:0; font-size:11px;">
                    <tr><td style="width:50%; border:none; padding:1px;"><strong>Invoice No:</strong> <?= htmlspecialchars($purchase->invoice_no ?? 'N/A') ?></td>
                        <td style="border:none; padding:1px;"><strong>Invoice Date:</strong> <?= date('d-M-Y', strtotime($purchase->invoice_date)) ?></td></tr>
                    <tr><td style="border:none; padding:1px;"><strong>Payment Mode:</strong> <?= ucfirst($purchase->payment_mode) ?></td>
                        <td style="border:none; padding:1px;"><strong>Status:</strong> <?= ucfirst($purchase->status) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>SI</th>
                <th style="text-align:left;">Product</th>
                <th>Item Code</th>
                <th>EAN Code</th>
                <th class="text-right">Basic Cost</th>
                <th class="text-right">Disc.</th>
                <th>HSN</th>
                <th>Tax %</th>
                <th class="text-right">Cost Price</th>
                <th class="text-right">MRP</th>
                <th class="text-right">SP</th>
                <th class="text-right">Mgn(CP)</th>
                <th class="text-right">Mgn(MRP)</th>
                <th class="text-right">Mgn(SP)</th>
                <th class="text-center">LP Date</th>
                <th class="text-center">LP Qty</th>
                <th class="text-center">Sales/LP</th>
                <th>Qty</th>
                <th class="text-right">Basic Total</th>
                <th class="text-right">Tax Total</th>
                <th class="text-right">Cost Value</th>
                <th class="text-right">MRP Value</th>
                <th class="text-right">Total ₹</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1; 
            $total_qty = 0;
            $sum_basic_tot = 0;
            $sum_tax_tot = 0;
            $sum_cost_val = 0;
            $sum_mrp_val = 0;
            $sum_sp_val = 0;
            $sum_total_rs = 0;
            
            foreach ($items as $item): 
                $qty = (float)$item->qty;
                $rate = (float)$item->price;
                $basic_total = $qty * $rate;
                
                $unit_disc = $qty > 0 ? ($item->discount_amount / $qty) : 0;
                $cp = $rate - $unit_disc;
                
                $tax_total = (float)$item->gst_amount;
                $cost_value = (float)$item->taxable_amount; // CP * qty
                
                $mrp = (float)($item->mrp ?? 0);
                $sp = (float)($item->selling_price ?? 0);
                $mrp_value = $qty * $mrp;
                $sp_value = $qty * $sp;
                
                $cp_margin = $cp > 0 ? (($sp - $cp) / $cp) * 100 : 0;
                $mrp_margin = $mrp > 0 ? (($mrp - $cp) / $mrp) * 100 : 0;
                $sp_margin = $sp > 0 ? (($sp - $cp) / $sp) * 100 : 0;
                
                $total_rs = (float)$item->total;
                
                $total_qty += $qty;
                $sum_basic_tot += $basic_total;
                $sum_tax_tot += $tax_total;
                $sum_cost_val += $cost_value;
                $sum_mrp_val += $mrp_value;
                $sum_sp_val += $sp_value;
                $sum_total_rs += $total_rs;
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td style="text-align:left; font-weight:600;">
                    <?= htmlspecialchars($item->product_name) ?>
                    <?php if (!empty($item->size_name) || !empty($item->colour_name)): ?>
                        <br><span style="font-size:11px; color:#333; font-weight:normal;">[<?= htmlspecialchars(trim(($item->colour_name ? $item->colour_name . ' / ' : '') . ($item->size_name ?? ''))) ?>]</span>
                    <?php endif; ?>
                    <?php if (!empty($item->variant_sku)): ?>
                        <br><span style="font-size:9.5px; color:#666; font-weight:normal;">SKU: <?= htmlspecialchars($item->variant_sku) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($item->item_code ?? '-') ?></td>
                <td><?= htmlspecialchars(!empty($item->barcode) ? $item->barcode : ($item->ean_code ?? '-')) ?></td>
                <td class="text-right"><?= number_format($rate, 2) ?></td>
                <td class="text-right"><?= number_format($item->discount_amount, 2) ?></td>
                <td class="text-center"><?= htmlspecialchars($item->hsn_code ?? '') ?></td>
                <td class="text-center"><?= (float)$item->gst_percent ?>%</td>
                <td class="text-right"><?= number_format($cp, 2) ?></td>
                <td class="text-right"><?= number_format($mrp, 2) ?></td>
                <td class="text-right"><?= number_format($sp, 2) ?></td>
                <td class="text-right" style="color:#16a34a;"><?= number_format($cp_margin, 1) ?>%</td>
                <td class="text-right" style="color:#16a34a;"><?= number_format($mrp_margin, 1) ?>%</td>
                <td class="text-right" style="color:#16a34a;"><?= number_format($sp_margin, 1) ?>%</td>
                <td class="text-center" style="color:#0055a5;"><?= $item->last_pur_date ?></td>
                <td class="text-center" style="color:#0055a5;"><?= $item->last_pur_qty > 0 ? $item->last_pur_qty : '-' ?></td>
                <td class="text-center" style="color:#ea580c;"><?= $item->sales_since_lp > 0 ? $item->sales_since_lp : '-' ?></td>
                <td class="text-center" style="font-weight:bold;"><?= $qty ?></td>
                <td class="text-right"><?= number_format($basic_total, 2) ?></td>
                <td class="text-right"><?= number_format($tax_total, 2) ?></td>
                <td class="text-right"><?= number_format($cost_value, 2) ?></td>
                <td class="text-right"><?= number_format($mrp_value, 2) ?></td>
                <td class="text-right" style="font-weight:bold;"><?= number_format($total_rs, 2) ?></td>
            </tr>
            <?php endforeach; 
            
            // Averages for the bottom row
            $avg_cp_margin = $sum_cost_val > 0 ? (($sum_sp_val - $sum_cost_val) / $sum_cost_val) * 100 : 0;
            $avg_mrp_margin = $sum_mrp_val > 0 ? (($sum_mrp_val - $sum_cost_val) / $sum_mrp_val) * 100 : 0;
            $avg_sp_margin = $sum_sp_val > 0 ? (($sum_sp_val - $sum_cost_val) / $sum_sp_val) * 100 : 0;
            ?>
            
            <tr style="font-weight: bold; background: #f0f4f8; color: #0055a5;">
                <td colspan="11" class="text-right" style="text-transform:uppercase;">Grand Totals / Avg:</td>
                <td class="text-right" style="color:#16a34a;"><?= number_format($avg_cp_margin, 1) ?>%</td>
                <td class="text-right" style="color:#16a34a;"><?= number_format($avg_mrp_margin, 1) ?>%</td>
                <td class="text-right" style="color:#16a34a;"><?= number_format($avg_sp_margin, 1) ?>%</td>
                <td colspan="3"></td>
                <td class="text-center"><?= $total_qty ?></td>
                <td class="text-right"><?= number_format($sum_basic_tot, 2) ?></td>
                <td class="text-right"><?= number_format($sum_tax_tot, 2) ?></td>
                <td class="text-right"><?= number_format($sum_cost_val, 2) ?></td>
                <td class="text-right"><?= number_format($sum_mrp_val, 2) ?></td>
                <td class="text-right"><?= number_format($sum_total_rs, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <?php
        $est_gross_amount = $sum_sp_val; // Gross Amount we will sell this for
        $est_profit_amount = $sum_sp_val - $sum_cost_val;
        $net_profit_amount = $est_profit_amount + (float)$purchase->discount; // Additional bill discount increases net profit
    ?>

    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div style="width: 50%; font-size: 11px;">
            <?php if(!empty($purchase->notes)): ?>
            <div style="border: 1px solid #0055a5; padding: 5px; min-height: 50px;">
                <strong style="color: #0055a5;">Notes/Remarks:</strong><br>
                <?= nl2br(htmlspecialchars($purchase->notes)) ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="totals-box">
            <table class="totals-table">
                <tr>
                    <td>Est. Gross Amount (SP):</td>
                    <td>₹ <?= number_format($est_gross_amount, 2) ?></td>
                </tr>
                <tr>
                    <td>Est. Profit Amount:</td>
                    <td style="color:#16a34a;">₹ <?= number_format($est_profit_amount, 2) ?></td>
                </tr>
                <?php if($purchase->discount > 0): ?>
                <tr>
                    <td>Bill Discount (Saved):</td>
                    <td style="color:#16a34a;">+ ₹ <?= number_format($purchase->discount, 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td style="border-bottom: 1px solid #ccc; padding-bottom:4px;"><strong>Est. Net Profit:</strong></td>
                    <td style="border-bottom: 1px solid #ccc; padding-bottom:4px; color:#16a34a;"><strong>₹ <?= number_format($net_profit_amount, 2) ?></strong></td>
                </tr>
                <tr>
                    <td style="padding-top:6px;">Total Basic:</td>
                    <td style="padding-top:6px;">₹ <?= number_format($purchase->subtotal, 2) ?></td>
                </tr>
                <tr>
                    <td>Total Taxable:</td>
                    <td>₹ <?= number_format($purchase->taxable_amount, 2) ?></td>
                </tr>
                <tr>
                    <td>Total GST:</td>
                    <td>₹ <?= number_format($purchase->total_gst, 2) ?></td>
                </tr>
                <tr class="grand-total-row">
                    <td style="padding-top: 5px;"><strong>NET PAYABLE:</strong></td>
                    <td style="padding-top: 5px; font-size: 14px;">₹ <?= number_format($purchase->total_amount, 2) ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <div class="footer">
        <div class="signature-box">
            <div class="signature-line">Store Keeper / Receiver</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Authorized Signatory</div>
        </div>
    </div>

</div>

<script>
    window.onload = function() {
        window.print();
    }
</script>

</body>
</html>
