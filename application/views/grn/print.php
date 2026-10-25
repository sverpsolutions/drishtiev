<?php
$company_name = $company['company_name'] ?? 'New Modern Bazaar Departmental Store Pvt Ltd';
$company_address = ($company['company_address'] ?? 'Shop No 5 to 9 & 105 to 109 ,S.S.Plaza ,Vikas Marg,Block-A Sector 47') . ', ' . ($company['company_city'] ?? 'Gurugram') . ', ' . ($company['company_state'] ?? 'Haryana') . '-' . ($company['company_pincode'] ?? '122018');
$company_phone = $company['company_phone'] ?? '0124-37896000';
$company_gstin = $company['company_gstin'] ?? '06AAECN3446L1ZA';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GRN Print - <?= htmlspecialchars($grn->grn_number) ?></title>
<style>
@page { size: A4 landscape; margin: 15mm; }
body { font-family: "Segoe UI", Arial, sans-serif; font-size: 11px; color: #333; margin: 0; background: #fff; line-height: 1.4; }
.print-wrap { width: 100%; max-width: 100%; margin: 0 auto; }

/* Header block */
.header-table { width: 100%; margin-bottom: 15px; border-bottom: 2px solid #0055a5; padding-bottom: 10px; }
.company-name { font-size: 22px; font-weight: bold; color: #0055a5; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
.company-address { font-size: 12px; color: #555; margin-bottom: 2px; }
.company-contact { font-size: 12px; font-weight: bold; color: #333; }

.doc-title-right { text-align: right; vertical-align: bottom; }
.doc-title-text { font-size: 20px; font-weight: bold; text-transform: uppercase; color: #0055a5; letter-spacing: 1px; margin-bottom: 5px; }
.status-table { width: auto; float: right; font-size: 11px; }
.status-table td { padding: 2px 10px; border: 1px solid #ddd; }
.status-table td:first-child { background: #f4f7f6; font-weight: bold; color: #0055a5; }

/* Grid Info */
.info-grid { display: table; width: 100%; font-size: 11px; margin-bottom: 15px; }
.info-col { display: table-cell; vertical-align: top; width: 33%; padding: 10px; border: 1px solid #ddd; border-right: none; }
.info-col:last-child { border-right: 1px solid #ddd; background: #fbfdfc; }
.info-label-blue { font-size: 12px; font-weight: bold; color: #0055a5; margin-bottom: 8px; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 3px; }
.info-value-black { color: #333; }

.info-row-table { width: 100%; }
.info-row-table td { padding: 3px 0; border-bottom: 1px dashed #eee; }
.info-row-table tr:last-child td { border-bottom: none; }
.info-row-table td:first-child { color: #555; font-weight: bold; width: 45%; }
.info-row-table td:last-child { font-weight: bold; color: #000; text-align: right; }

.auth-bar { display: flex; justify-content: space-between; font-size: 11px; font-weight: bold; margin: 15px 0 10px 0; padding: 6px 15px; background: #f4f7f6; border: 1px solid #ddd; border-radius: 3px; }

/* Items Table */
.items-tbl { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.items-tbl th, .items-tbl td { padding: 6px 4px; border: 1px solid #ddd; text-align: center; }
.items-tbl th { background-color: #0055a5; color: #fff; font-weight: bold; vertical-align: middle; text-transform: uppercase; font-size: 9px; letter-spacing: 0.5px; }
.items-tbl tbody tr:nth-child(even) { background-color: #f9f9f9; }
.items-tbl td.text-left { text-align: left; }
.items-tbl td.text-right { text-align: right; }
.items-tbl th.text-left { text-align: left; }
.items-tbl th.text-right { text-align: right; }
.items-tbl .green-text { color: #008000; font-weight: bold; }

.tot-row td { font-weight: bold; background: #f4f7f6; border-top: 2px solid #aaa; border-bottom: 2px solid #aaa; color: #000; font-size: 11px; }

/* Bottom Sections */
.bottom-sections { display: flex; justify-content: space-between; margin-top: 10px; gap: 20px; }

.gst-wrap { width: 65%; }
.gst-title { font-size: 13px; font-weight: bold; margin-bottom: 8px; color: #0055a5; text-transform: uppercase; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
.gst-tbl { width: 100%; border-collapse: collapse; font-size: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.gst-tbl th, .gst-tbl td { padding: 5px 4px; border: 1px solid #ddd; text-align: center; }
.gst-tbl th { background-color: #f4f7f6; color: #333; font-weight: bold; }
.gst-tbl .tot-row td { background-color: #eef2f5; }

.summary-wrap { width: 33%; font-size: 11px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.summary-tbl { width: 100%; border-collapse: collapse; }
.summary-tbl td { padding: 5px 10px; border-bottom: 1px solid #eee; text-align: right; }
.summary-tbl tr:nth-child(even) { background: #fcfcfc; }
.summary-tbl td:first-child { color: #555; text-align: left; font-weight: normal; }
.grand-tot { font-size: 14px; background: #0055a5; }
.grand-tot td { color: #fff !important; font-weight: bold; padding: 8px 10px; border: none; }

.footer-sigs { display: flex; justify-content: space-between; font-size: 11px; margin-top: 50px; }
.sig-box { text-align: center; color: #555; }
.sig-line { border-top: 1px dashed #777; width: 150px; margin: 0 auto; padding-top: 5px; margin-top: 50px; font-weight: bold; color: #333; }

.clearfix { clear: both; }

@media print {
    .no-print { display: none; }
    body { background: transparent; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .items-tbl th { background-color: #0055a5 !important; color: #fff !important; }
    .grand-tot { background-color: #0055a5 !important; }
    .grand-tot td { color: #fff !important; }
}
.btn-bar { padding: 12px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px; text-align: center; }
.btn-bar button { padding: 6px 20px; cursor: pointer; background: #0055a5; color: #fff; border: none; border-radius: 4px; font-weight: bold; margin: 0 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.btn-bar button.btn-close { background: #fff; color: #333; border: 1px solid #ccc; }
</style>
</head>
<body>

<div class="no-print btn-bar">
    <button onclick="window.print()">🖨 Print GRN</button>
    <button class="btn-close" onclick="window.close()">✕ Close</button>
</div>

<div class="print-wrap">
    
    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <div class="company-name"><?= htmlspecialchars($company_name) ?></div>
                <div class="company-address"><?= htmlspecialchars($company_address) ?></div>
                <div class="company-contact">Phone: <?= htmlspecialchars($company_phone) ?> &nbsp;&nbsp;|&nbsp;&nbsp; GSTIN: <?= htmlspecialchars($company_gstin) ?></div>
            </td>
            <td class="doc-title-right">
                <div class="doc-title-text">GOODS RECEIPT NOTE</div>
                <table class="status-table">
                    <tr><td>GRN STATUS</td><td>Updated</td></tr>
                    <tr><td>AUTHORIZE STATUS</td><td>True</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="info-grid">
        <div class="info-col">
            <div class="info-label-blue">Shipping Address</div>
            <div class="info-value-black" style="font-size:12px; font-weight:bold; margin-bottom: 4px;"><?= mb_strtoupper(htmlspecialchars($company_name)) ?></div>
            <div class="info-value-black" style="color:#555; line-height: 1.5;">
                <?php echo str_replace('Address:', '', htmlspecialchars($company_address)); ?>
            </div>
        </div>
        <div class="info-col">
            <div class="info-label-blue">Supplier Name & Address</div>
            <div class="info-value-black" style="font-size:12px; font-weight:bold; margin-bottom: 4px;"><?= htmlspecialchars($grn->supplier_name ?? '') ?></div>
            <div class="info-value-black" style="color:#555; font-weight:normal; line-height: 1.5;">
                <?= htmlspecialchars($grn->supplier_address ?? '-') ?><br>
                <strong>GSTIN:</strong> <?= htmlspecialchars($grn->supplier_gstin ?? '-') ?>
            </div>
        </div>
        <div class="info-col">
            <table class="info-row-table">
                <tr><td>GRN No</td><td class="info-value-black"><?= htmlspecialchars(str_replace('GRN-', '', $grn->grn_number)) ?></td></tr>
                <tr><td>GRN Date</td><td class="info-value-black"><?= date('d-M-Y', strtotime($grn->received_date)) ?></td></tr>
                <tr><td>Invoice No</td><td class="info-value-black"><?= htmlspecialchars($grn->supplier_invoice_no ?? '-') ?></td></tr>
                <tr><td>Invoice Date</td><td class="info-value-black"><?= $grn->supplier_invoice_date && $grn->supplier_invoice_date !== '0000-00-00' ? date('d-M-Y', strtotime($grn->supplier_invoice_date)) : '-' ?></td></tr>
                <tr><td>Receipt No</td><td class="info-value-black"><?= htmlspecialchars($grn->receipt_no ?? '-') ?></td></tr>
                <tr><td>Credit Days</td><td class="info-value-black">0</td></tr>
                <tr><td>PO Number</td><td class="info-value-black"><?= htmlspecialchars(str_replace('PO-', '', $grn->po_number ?? '0')) ?></td></tr>
            </table>
        </div>
    </div>

    <div class="auth-bar">
        <div><span style="color:#555;">User Id:</span> <?= htmlspecialchars($grn->received_by_name ?? 'admin') ?></div>
        <div><span style="color:#555;">Authorize By:</span> <?= htmlspecialchars($grn->received_by_name ?? 'admin') ?></div>
        <div><span style="color:#555;">Authorize Date:</span> <?= date('d-M-Y', strtotime($grn->received_date)) ?></div>
    </div>

    <?php
    // Calculate values
    $totPurQty = 0; $totFreeQty = 0; $totTotQty = 0;
    $totCPValue = 0; $totValue = 0;
    $totPurchaseValue = 0; $totMRPValue = 0;
    
    // For GST Table
    $gst_groups = []; // group by gst_pct
    $totGstSum = 0;

    foreach ($items as $it) {
        $qty = $it->received_qty;
        $freeQty = 0; 
        $totQty = $qty + $freeQty;

        $basicCP = $it->rate;
        $gstPct = $it->gst_pct ?? 0;
        
        $cp = $basicCP; // CP* 
        $cpValue = $totQty * $cp; 
        
        $mrp = isset($it->mrp) && $it->mrp > 0 ? $it->mrp : $it->rate * 1.5;
        $mrpValue = $totQty * $mrp;
        
        $value = $cpValue;

        $totPurQty += $qty;
        $totFreeQty += $freeQty;
        $totTotQty += $totQty;
        $totCPValue += $cpValue;
        $totValue += $value;
        $totPurchaseValue += $cpValue;
        $totMRPValue += $mrpValue;

        // GST Grouping
        if (!isset($gst_groups[$gstPct])) {
            $gst_groups[$gstPct] = ['taxable' => 0, 'tax_amt' => 0];
        }
        $taxable = $cpValue;
        $taxAmt = $taxable * ($gstPct / 100);
        
        $gst_groups[$gstPct]['taxable'] += $taxable;
        $gst_groups[$gstPct]['tax_amt'] += $taxAmt;
        $totGstSum += $taxAmt;
    }
    
    $grandTotal = $totValue + $totGstSum;
    $marginAmount = $totMRPValue - $totValue;
    $overallMarginCP = $totValue > 0 ? ($marginAmount / $totValue) * 100 : 0;
    $overallMarginMRP = $totMRPValue > 0 ? ($marginAmount / $totMRPValue) * 100 : 0;
    ?>

    <table class="items-tbl">
        <thead>
            <tr>
                <th style="width: 3%;">Sl<br>No</th>
                <th class="text-left" style="width: 10%;">Item Code</th>
                <th class="text-left" style="width: 18%;">Description</th>
                <th style="width: 6%;">HSN<br>CODE</th>
                <th class="text-right" style="width: 5%;">Pur<br>Qty</th>
                <th class="text-right" style="width: 5%;">Free<br>Qty</th>
                <th class="text-right" style="width: 5%;">Tot<br>Qty</th>
                <th class="text-right" style="width: 6%;">Basic<br>CP *</th>
                <th class="text-right" style="width: 4%;">GST %</th>
                <th class="text-right" style="width: 4%;">Disc%</th>
                <th class="text-right" style="width: 5%;">CP *</th>
                <th class="text-right" style="width: 6%;">CP<br>Value</th>
                <th class="text-right" style="width: 5%;">MRP</th>
                <th class="text-right" style="width: 5%;">Margin<br>CP *</th>
                <th class="text-right" style="width: 5%;">Margin<br>MRP</th>
                <th class="text-right" style="width: 5%;">Fixed<br>Margin</th>
                <th class="text-right" style="width: 8%;">Total<br>Value</th>
            </tr>
        </thead>
        <tbody>
            <?php $i=1; foreach($items as $it): 
                $qty = $it->received_qty;
                $freeQty = 0; $totQty = $qty + $freeQty;
                $basicCP = $it->rate;
                $gstPct = $it->gst_pct ?? 0;
                $cp = $basicCP;
                $cpValue = $totQty * $cp;
                $mrp = isset($it->mrp) && $it->mrp > 0 ? $it->mrp : $it->rate * 1.5;
                $marginCP = $cp > 0 ? (($mrp - $cp) / $cp) * 100 : 0;
                $marginMRP = $mrp > 0 ? (($mrp - $cp) / $mrp) * 100 : 0;
                $value = $cpValue;
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td class="text-left"><?= htmlspecialchars($it->item_code ?? '') ?></td>
                <td class="text-left"><strong><?= htmlspecialchars($it->product_name) ?></strong></td>
                <td><?= htmlspecialchars($it->hsn_code ?? '0407') ?></td>
                <td class="text-right"><?= number_format($qty, 2) ?></td>
                <td class="text-right"><?= number_format($freeQty, 2) ?></td>
                <td class="text-right" style="font-weight:bold;"><?= number_format($totQty, 2) ?></td>
                <td class="text-right"><?= number_format($basicCP, 2) ?></td>
                <td class="text-right"><?= number_format($gstPct, 2) ?></td>
                <td class="text-right">0.00</td>
                <td class="text-right"><?= number_format($cp, 2) ?></td>
                <td class="text-right"><?= number_format($cpValue, 2) ?></td>
                <td class="text-right"><?= number_format($mrp, 2) ?></td>
                <td class="text-right"><?= number_format($marginCP, 2) ?></td>
                <td class="text-right green-text"><?= number_format($marginMRP, 2) ?></td>
                <td class="text-right">0.00</td>
                <td class="text-right" style="font-weight:bold; color:#0055a5;"><?= number_format($value, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="tot-row">
                <td colspan="4" class="text-right" style="padding-right:10px;">TOTAL</td>
                <td class="text-right"><?= number_format($totPurQty, 2) ?></td>
                <td class="text-right"><?= number_format($totFreeQty, 2) ?></td>
                <td class="text-right"><?= number_format($totTotQty, 2) ?></td>
                <td colspan="2"></td>
                <td class="text-right">0.00</td>
                <td></td>
                <td class="text-right"><?= number_format($totCPValue, 2) ?></td>
                <td></td>
                <td class="text-right"><?= number_format($overallMarginCP, 2) ?></td>
                <td class="text-right"><?= number_format($overallMarginMRP, 2) ?></td>
                <td class="text-right">0.00</td>
                <td class="text-right" style="color:#0055a5;"><?= number_format($totValue, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="bottom-sections">
        
        <div class="gst-wrap">
            <div class="gst-title">GST Details Breakdown</div>
            <table class="gst-tbl">
                <thead>
                    <tr>
                        <th>TAX<br>%</th>
                        <th>IGST<br>%</th>
                        <th>IGST<br>TAXABLE</th>
                        <th>IGST<br>AMT</th>
                        <th>CGST<br>%</th>
                        <th>CGST<br>TAXABLE</th>
                        <th>CGST<br>AMT</th>
                        <th>SGST<br>%</th>
                        <th>SGST<br>TAXABLE</th>
                        <th>SGST<br>AMT</th>
                        <th>TAX<br>FREE<br>PUR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sum_igst_taxable = 0; $sum_igst_amt = 0;
                    $sum_cgst_taxable = 0; $sum_cgst_amt = 0;
                    $sum_sgst_taxable = 0; $sum_sgst_amt = 0;
                    $sum_tax_free = 0;
    
                    foreach ($gst_groups as $pct => $data):
                        $cgst_pct = $pct / 2;
                        $sgst_pct = $pct / 2;
                        $cgst_amt = $data['tax_amt'] / 2;
                        $sgst_amt = $data['tax_amt'] / 2;
                        
                        if ($pct == 0) {
                            $sum_tax_free += $data['taxable'];
                        } else {
                            $sum_cgst_taxable += $data['taxable'];
                            $sum_cgst_amt += $cgst_amt;
                            $sum_sgst_taxable += $data['taxable'];
                            $sum_sgst_amt += $sgst_amt;
                        }
                    ?>
                    <tr>
                        <td style="font-weight:bold;">GST <?= number_format($pct, 0) ?>%</td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td><?= number_format($cgst_pct, 2) ?></td>
                        <td><?= $pct > 0 ? number_format($data['taxable'], 2) : '0.00' ?></td>
                        <td><?= $pct > 0 ? number_format($cgst_amt, 2) : '0.00' ?></td>
                        <td><?= number_format($sgst_pct, 2) ?></td>
                        <td><?= $pct > 0 ? number_format($data['taxable'], 2) : '0.00' ?></td>
                        <td><?= $pct > 0 ? number_format($sgst_amt, 2) : '0.00' ?></td>
                        <td><?= $pct == 0 ? number_format($data['taxable'], 2) : '0.00' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr class="tot-row">
                        <td style="color:#0055a5;">Grand<br>Total</td>
                        <td></td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td></td>
                        <td><?= number_format($sum_cgst_taxable, 2) ?></td>
                        <td style="color:#0055a5;"><?= number_format($sum_cgst_amt, 2) ?></td>
                        <td></td>
                        <td><?= number_format($sum_sgst_taxable, 2) ?></td>
                        <td style="color:#0055a5;"><?= number_format($sum_sgst_amt, 2) ?></td>
                        <td><?= number_format($sum_tax_free, 2) ?></td>
                    </tr>
                </tbody>
            </table>
            
            <div style="margin-top: 30px; font-weight: bold; color: #555; font-size: 10px;">
                NOTE : All quantities are subject to final QC inspection.
            </div>
            
            <div class="footer-sigs">
                <div class="sig-box"><div class="sig-line">Prepared By</div></div>
                <div class="sig-box"><div class="sig-line">Checked By</div></div>
                <div class="sig-box"><div class="sig-line">Store Operation</div></div>
            </div>
        </div>
    
        <div class="summary-wrap">
            <table class="summary-tbl">
                <tr><td>Purchase Value</td><td style="font-weight:bold;"><?= number_format($totPurchaseValue, 2) ?></td></tr>
                <tr><td>MRP Value</td><td style="font-weight:bold;"><?= number_format($totMRPValue, 2) ?></td></tr>
                <tr><td>Margin Amount</td><td style="font-weight:bold;"><?= number_format($marginAmount, 2) ?></td></tr>
                <tr><td>C.P Margin%</td><td style="font-weight:bold;"><?= number_format($overallMarginCP, 2) ?>%</td></tr>
                <tr><td>MRP Margin%</td><td style="font-weight:bold;"><?= number_format($overallMarginMRP, 2) ?>%</td></tr>
                <tr><td colspan="2" style="border-top:2px solid #ccc; padding:0;"></td></tr>
                <tr><td>Sub Total (Before GST)</td><td style="font-weight:bold;"><?= number_format($totValue, 2) ?></td></tr>
                <tr><td>Discount Amount</td><td style="font-weight:bold;">0.00</td></tr>
                <tr><td>GST In Value</td><td style="font-weight:bold;"><?= number_format($totGstSum, 2) ?></td></tr>
                <tr><td>Discount In value</td><td style="font-weight:bold;">0.00</td></tr>
                <tr><td>Other Charge</td><td style="font-weight:bold;">0.00</td></tr>
                <tr><td>Round Off (+)</td><td style="font-weight:bold;">0.00</td></tr>
                <tr class="grand-tot">
                    <td>GRAND TOTAL</td>
                    <td><?= number_format($grandTotal, 2) ?></td>
                </tr>
            </table>
        </div>
        
    </div>

</div>
</body>
</html>
