<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Return Note - <?= htmlspecialchars($return->return_number) ?></title>
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
        .box-content { border: 1px solid #0055a5; border-top: none; padding: 5px; min-height: 50px; }

        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9px; }
        table.items-table th, table.items-table td { border: 1px solid #999; padding: 2px 3px; text-align: left; }
        table.items-table th { background: #f0f4f8; color: #0055a5; font-weight: bold; text-align: center; font-size: 9px; text-transform: uppercase; }
        table.items-table td.text-right, table.items-table th.text-right { text-align: right; }
        table.items-table td.text-center, table.items-table th.text-center { text-align: center; }
        table.items-table td { white-space: nowrap; }

        .totals-row { display: flex; justify-content: flex-end; }
        .totals-box { width: 280px; border: 1px solid #0055a5; padding: 4px; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 2px 3px; font-size: 10px; }
        .totals-table td:nth-child(2) { text-align: right; font-weight: bold; }
        .grand-total-row { border-top: 1px solid #0055a5; font-size: 13px !important; color: #0055a5; }

        .reason-box { border-left: 4px solid #0055a5; background: #f0f4f8; padding: 4px 8px; margin-bottom: 8px; font-size: 10px; }

        .gst-section { border: 1px solid #0055a5; padding: 5px; margin-bottom: 8px; }
        .gst-section-title { color: #0055a5; font-weight: bold; font-size: 10px; text-transform: uppercase; margin-bottom: 4px; }
        table.gst-table { width: 100%; border-collapse: collapse; font-size: 9px; }
        table.gst-table th { background: #f0f4f8; color: #0055a5; padding: 2px 4px; border: 1px solid #ccc; text-align: center; }
        table.gst-table td { padding: 2px 4px; border: 1px solid #ccc; text-align: right; }
        table.gst-table td:first-child { text-align: center; }

        .footer { margin-top: 30px; display: flex; justify-content: space-between; padding: 0 40px; }
        .signature-box { text-align: center; width: 180px; }
        .signature-line { border-top: 1px solid #000; margin-top: 50px; padding-top: 4px; font-weight: bold; font-size: 11px; text-transform: uppercase; color: #0055a5; }

        .note-box { background: #f0f4f8; border: 1px solid #cce; padding: 5px 8px; font-size: 9px; color: #444; margin-bottom: 8px; }

        @media print {
            body { padding: 0; }
            .print-wrap { border: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<?php $r = $return; $s = $settings; ?>

<div style="text-align: right; margin-bottom: 10px;" class="no-print">
    <button onclick="window.print()" style="padding: 6px 15px; background: #0055a5; color: #fff; border: 1px solid #004080; cursor: pointer; font-size: 14px; font-weight: bold; border-radius: 2px;">Print Document</button>
</div>

<div class="print-wrap">

    <div class="header">
        <div class="company-info">
            <?php if (!empty($s['company_logo']) && file_exists('./' . $s['company_logo'])): ?>
                <img src="<?= base_url($s['company_logo']) ?>" class="company-logo" alt="Logo">
            <?php endif; ?>
            <h2><?= htmlspecialchars($s['company_name'] ?? 'Your Company Name') ?></h2>
            <p><?= htmlspecialchars($s['company_address'] ?? 'Company Address') ?></p>
            <p><strong>GSTIN:</strong> <?= htmlspecialchars($s['company_gst'] ?? $s['gstin'] ?? 'N/A') ?></p>
        </div>
        <div class="doc-title">
            <h1>PURCHASE RETURN NOTE</h1>
            <p>Debit Note No: <?= htmlspecialchars($r->return_number) ?></p>
            <p>Date: <?= date('d-M-Y', strtotime($r->return_date)) ?></p>
            <?php if ($r->original_purchase_no): ?>
            <p style="font-size:11px; color:#555;">Against PO: <?= htmlspecialchars($r->original_purchase_no) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="meta-row">
        <div class="supplier-box">
            <div class="box-title">Supplier Details (Return To)</div>
            <div class="box-content">
                <strong><?= htmlspecialchars($r->supplier_name) ?></strong><br>
                <?php if (!empty($r->supplier_address)): ?><?= htmlspecialchars($r->supplier_address) ?><br><?php endif; ?>
                <strong>GSTIN:</strong> <?= htmlspecialchars($r->supplier_gst ?? 'N/A') ?>
                &nbsp; | &nbsp; State: <?= htmlspecialchars($r->supplier_state ?? 'N/A') ?>
            </div>
        </div>
        <div class="purchase-box">
            <div class="box-title">Return Details</div>
            <div class="box-content">
                <table style="width:100%; border:none; padding:0; margin:0; font-size:11px;">
                    <tr>
                        <td style="width:50%; border:none; padding:1px;"><strong>Return No:</strong> <?= htmlspecialchars($r->return_number) ?></td>
                        <td style="border:none; padding:1px;"><strong>Return Date:</strong> <?= date('d-M-Y', strtotime($r->return_date)) ?></td>
                    </tr>
                    <tr>
                        <td style="border:none; padding:1px;"><strong>Supply Type:</strong> <?= $r->is_interstate ? 'Interstate (IGST)' : 'Intrastate (CGST+SGST)' ?></td>
                        <?php if ($r->original_purchase_no): ?>
                        <td style="border:none; padding:1px;"><strong>Against PO:</strong> <?= htmlspecialchars($r->original_purchase_no) ?></td>
                        <?php endif; ?>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <?php if ($r->reason): ?>
    <div class="reason-box"><strong>Return Reason:</strong> <?= htmlspecialchars($r->reason) ?></div>
    <?php endif; ?>

    <table class="items-table">
        <thead>
            <tr>
                <th>SI</th>
                <th style="text-align:left;">Product</th>
                <th>Item Code</th>
                <th>HSN</th>
                <th class="text-right">Rate (₹)</th>
                <th>Tax %</th>
                <th class="text-right">Taxable Amt</th>
                <?php if ($r->is_interstate): ?>
                    <th class="text-right">IGST ₹</th>
                <?php else: ?>
                    <th class="text-right">CGST ₹</th>
                    <th class="text-right">SGST ₹</th>
                <?php endif; ?>
                <th class="text-right">MRP</th>
                <th class="text-right">SP</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Total ₹</th>
            </tr>
        </thead>
        <tbody>
        <?php
            $i = 1;
            $total_qty    = 0;
            $sum_taxable  = 0;
            $sum_cgst     = 0;
            $sum_sgst     = 0;
            $sum_igst     = 0;
            $sum_total    = 0;
            $sum_mrp_val  = 0;

            foreach ($r->items as $item):
                $qty     = (float)$item->qty;
                $rate    = (float)$item->rate;
                $taxable = (float)$item->taxable_amt;
                $cgst    = (float)$item->cgst_amount;
                $sgst    = (float)$item->sgst_amount;
                $igst    = (float)$item->igst_amount;
                $total   = (float)$item->total;
                $mrp     = (float)($item->mrp ?? 0);
                $sp      = (float)($item->selling_price ?? 0);
                $mrp_val = $qty * $mrp;

                $total_qty   += $qty;
                $sum_taxable += $taxable;
                $sum_cgst    += $cgst;
                $sum_sgst    += $sgst;
                $sum_igst    += $igst;
                $sum_total   += $total;
                $sum_mrp_val += $mrp_val;
        ?>
            <tr>
                <td><?= $i++ ?></td>
                <td style="text-align:left; font-weight:600;"><?= htmlspecialchars($item->product_name) ?></td>
                <td><?= htmlspecialchars($item->item_code ?? '-') ?></td>
                <td class="text-center"><?= htmlspecialchars($item->hsn_code ?? '') ?></td>
                <td class="text-right"><?= number_format($rate, 2) ?></td>
                <td class="text-center"><?= (float)$item->gst_percent ?>%</td>
                <td class="text-right"><?= number_format($taxable, 2) ?></td>
                <?php if ($r->is_interstate): ?>
                    <td class="text-right"><?= number_format($igst, 2) ?> <small>(<?= $item->igst_percent ?>%)</small></td>
                <?php else: ?>
                    <td class="text-right"><?= number_format($cgst, 2) ?> <small>(<?= $item->cgst_percent ?>%)</small></td>
                    <td class="text-right"><?= number_format($sgst, 2) ?> <small>(<?= $item->sgst_percent ?>%)</small></td>
                <?php endif; ?>
                <td class="text-right"><?= $mrp > 0 ? number_format($mrp, 2) : '-' ?></td>
                <td class="text-right"><?= $sp > 0 ? number_format($sp, 2) : '-' ?></td>
                <td class="text-center" style="font-weight:bold;"><?= rtrim(rtrim($item->qty,'0'),'.') ?> <?= htmlspecialchars($item->unit) ?></td>
                <td class="text-right" style="font-weight:bold;"><?= number_format($total, 2) ?></td>
            </tr>
        <?php endforeach; ?>

            <tr style="font-weight: bold; background: #f0f4f8; color: #0055a5;">
                <td colspan="6" class="text-right" style="text-transform:uppercase;">Grand Totals:</td>
                <td class="text-right"><?= number_format($sum_taxable, 2) ?></td>
                <?php if ($r->is_interstate): ?>
                    <td class="text-right"><?= number_format($sum_igst, 2) ?></td>
                <?php else: ?>
                    <td class="text-right"><?= number_format($sum_cgst, 2) ?></td>
                    <td class="text-right"><?= number_format($sum_sgst, 2) ?></td>
                <?php endif; ?>
                <td class="text-right"><?= number_format($sum_mrp_val, 2) ?></td>
                <td></td>
                <td class="text-center"><?= $total_qty ?></td>
                <td class="text-right"><?= number_format($sum_total, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div style="display: flex; justify-content: space-between; align-items: flex-start;">

        <div style="width: 55%;">
            <!-- GST Summary -->
            <div class="gst-section">
                <div class="gst-section-title">GST Summary</div>
                <table class="gst-table">
                    <thead>
                        <tr>
                            <th>GST Rate</th>
                            <th>Taxable</th>
                            <th>CGST</th>
                            <th>SGST</th>
                            <th>IGST</th>
                            <th>Total GST</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $gst_map = [];
                        foreach ($r->items as $item) {
                            $key = $item->gst_percent;
                            if (!isset($gst_map[$key])) $gst_map[$key] = ['taxable'=>0,'cgst'=>0,'sgst'=>0,'igst'=>0];
                            $gst_map[$key]['taxable'] += $item->taxable_amt;
                            $gst_map[$key]['cgst']    += $item->cgst_amount;
                            $gst_map[$key]['sgst']    += $item->sgst_amount;
                            $gst_map[$key]['igst']    += $item->igst_amount;
                        }
                        foreach ($gst_map as $rate => $v):
                    ?>
                        <tr>
                            <td><?= $rate ?>%</td>
                            <td>₹<?= number_format($v['taxable'],2) ?></td>
                            <td>₹<?= number_format($v['cgst'],2) ?></td>
                            <td>₹<?= number_format($v['sgst'],2) ?></td>
                            <td>₹<?= number_format($v['igst'],2) ?></td>
                            <td>₹<?= number_format($v['cgst']+$v['sgst']+$v['igst'],2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="note-box">This Debit Note has been issued for goods returned to supplier. ITC on returned goods must be reversed in GSTR-3B Table 4(B)(2).</div>

            <?php if ($r->reason): ?>
            <div class="note-box"><strong>Reason:</strong> <?= htmlspecialchars($r->reason) ?></div>
            <?php endif; ?>
        </div>

        <div class="totals-box">
            <table class="totals-table">
                <tr>
                    <td>Subtotal (Basic):</td>
                    <td>₹ <?= number_format($r->subtotal, 2) ?></td>
                </tr>
                <?php if ($r->discount > 0): ?>
                <tr>
                    <td>Discount:</td>
                    <td>- ₹ <?= number_format($r->discount, 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Taxable Amount:</td>
                    <td>₹ <?= number_format($r->taxable_amount, 2) ?></td>
                </tr>
                <?php if ($r->cgst_amount > 0): ?>
                <tr>
                    <td>CGST:</td>
                    <td>₹ <?= number_format($r->cgst_amount, 2) ?></td>
                </tr>
                <tr>
                    <td>SGST:</td>
                    <td>₹ <?= number_format($r->sgst_amount, 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($r->igst_amount > 0): ?>
                <tr>
                    <td>IGST:</td>
                    <td>₹ <?= number_format($r->igst_amount, 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand-total-row">
                    <td style="padding-top: 5px;"><strong>DEBIT NOTE TOTAL:</strong></td>
                    <td style="padding-top: 5px; font-size: 14px;">₹ <?= number_format($r->total_amount, 2) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        <div class="signature-box">
            <div class="signature-line">Supplier Acknowledgement</div>
        </div>
        <div class="signature-box">
            <div class="signature-line">Authorized Signatory</div>
        </div>
    </div>

</div>

<script>window.onload = function(){ window.print(); };</script>
</body>
</html>
