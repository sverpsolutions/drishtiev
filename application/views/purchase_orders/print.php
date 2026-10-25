<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Purchase Order — <?= htmlspecialchars($po->po_number) ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; font-size:11px; color:#1a1a1a; background:#fff; }
.page { width:210mm; min-height:297mm; margin:0 auto; padding:12mm 14mm; }

/* Header */
.po-header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:3px solid #2c3e50; padding-bottom:10px; margin-bottom:10px; }
.logo-wrap img { max-height:72px; max-width:160px; object-fit:contain; }
.logo-wrap .company-name { font-size:20px; font-weight:700; color:#2c3e50; letter-spacing:1px; }
.logo-wrap .company-sub { font-size:9px; color:#666; margin-top:3px; letter-spacing:.5px; }
.company-info { text-align:right; font-size:9.5px; color:#444; line-height:1.7; }
.company-info strong { color:#2c3e50; }

/* Title band */
.po-title-band { background:#2c3e50; color:#fff; text-align:center; padding:7px 0 5px; border-radius:3px; margin-bottom:10px; }
.po-title-band h2 { font-size:15px; font-weight:700; letter-spacing:3px; margin:0; }
.po-title-band .po-sub { font-size:9.5px; opacity:.8; margin-top:2px; letter-spacing:1px; }

/* Meta row */
.meta-row { display:flex; gap:8px; margin-bottom:10px; }
.meta-box { flex:1; border:1px solid #ddd; border-radius:4px; padding:8px 10px; }
.meta-box h4 { font-size:8.5px; font-weight:700; text-transform:uppercase; color:#666; letter-spacing:.8px; border-bottom:1px solid #eee; padding-bottom:4px; margin-bottom:5px; }
.meta-box table { width:100%; }
.meta-box table td { padding:1.5px 0; font-size:10px; }
.meta-box table td:first-child { color:#555; width:42%; }
.meta-box table td:last-child { font-weight:600; color:#1a1a1a; }
.supplier-box { flex:1.4; }
.po-meta-box { flex:1; }

/* Status badge */
.status-badge { display:inline-block; padding:2px 8px; border-radius:20px; font-size:8.5px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; }
.status-approved { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.status-submitted { background:#cce5ff; color:#004085; border:1px solid #b8daff; }
.status-draft { background:#e2e3e5; color:#383d41; border:1px solid #d6d8db; }
.status-other { background:#fff3cd; color:#856404; border:1px solid #ffeeba; }

/* Items table */
.items-table { width:100%; border-collapse:collapse; margin-bottom:8px; font-size:9.5px; }
.items-table thead tr { background:#2c3e50; color:#fff; }
.items-table thead th { padding:5px 6px; text-align:left; font-weight:600; font-size:8.5px; text-transform:uppercase; letter-spacing:.4px; }
.items-table thead th.r { text-align:right; }
.items-table tbody tr:nth-child(even) { background:#f7f8fc; }
.items-table tbody tr:hover { background:#eef2ff; }
.items-table tbody td { padding:5px 6px; border-bottom:1px solid #e8e8e8; vertical-align:top; }
.items-table tbody td.r { text-align:right; }
.items-table tfoot td { padding:4px 6px; border-top:1px solid #ccc; font-size:10px; }
.items-table tfoot .subtotal td { color:#555; }
.items-table tfoot .net-row td { background:#2c3e50; color:#fff; font-weight:700; font-size:11px; }

/* Totals area */
.totals-wrap { display:flex; justify-content:flex-end; margin-bottom:10px; }
.totals-table { width:240px; border:1px solid #ddd; border-radius:4px; overflow:hidden; font-size:10px; }
.totals-table tr td { padding:4px 10px; }
.totals-table tr:nth-child(odd) { background:#f7f8fc; }
.totals-table tr td:last-child { text-align:right; font-weight:600; }
.totals-table .net-row { background:#2c3e50!important; }
.totals-table .net-row td { color:#fff; font-weight:700; font-size:12px; padding:7px 10px; }

/* Amount in words */
.amt-words { border:1px solid #ddd; border-radius:4px; padding:7px 12px; margin-bottom:10px; font-size:9.5px; background:#fffdf0; }
.amt-words strong { color:#2c3e50; }

/* Terms */
.terms-box { border:1px solid #ddd; border-radius:4px; padding:8px 12px; margin-bottom:12px; font-size:9px; color:#555; line-height:1.7; }
.terms-box h4 { font-size:9px; font-weight:700; text-transform:uppercase; color:#2c3e50; letter-spacing:.5px; margin-bottom:4px; }

/* Signatures */
.sig-row { display:flex; gap:10px; margin-top:14px; }
.sig-box { flex:1; text-align:center; }
.sig-box .sig-line { border-top:1.5px solid #333; padding-top:5px; margin-top:30px; font-size:9px; color:#444; font-weight:600; }
.sig-box .sig-sub { font-size:8px; color:#888; }

/* Footer */
.po-footer { margin-top:14px; border-top:1px solid #ddd; padding-top:6px; text-align:center; font-size:8px; color:#999; }

/* Print buttons (hidden on print) */
.no-print { background:#f0f2f5; padding:10px 14mm; display:flex; align-items:center; gap:8px; border-bottom:1px solid #ddd; }
.btn { display:inline-flex; align-items:center; gap:5px; padding:7px 16px; border-radius:4px; font-size:12px; font-weight:600; cursor:pointer; text-decoration:none; border:none; }
.btn-dark { background:#2c3e50; color:#fff; }
.btn-outline { background:#fff; color:#333; border:1px solid #ccc; }
.btn-green { background:#27ae60; color:#fff; }

@media print {
    .no-print { display:none!important; }
    body { margin:0; }
    .page { width:100%; padding:8mm 10mm; margin:0; }
}
</style>
</head>
<body>

<?php
/* Company settings map */
$cs = [];
foreach ($company as $row) { $cs[$row->key_name] = $row->key_value; }
$c_name    = $cs['company_name']    ?? 'SANDAL HOUSE';
$c_addr    = $cs['company_address'] ?? '';
$c_phone   = $cs['company_phone']   ?? '';
$c_email   = $cs['company_email']   ?? '';
$c_gstin   = $cs['company_gstin']   ?? '';
$logo_url  = base_url('assets/uploads/logo_sandalhouse.png');

$status_map = [
    'draft'=>['Draft','status-draft'],
    'submitted'=>['Submitted','status-submitted'],
    'pending_approval'=>['Pending Approval','status-other'],
    'approved'=>['Approved','status-approved'],
    'vendor_confirmed'=>['Vendor Confirmed','status-approved'],
    'received'=>['Received','status-approved'],
    'rejected'=>['Rejected','status-other'],
    'cancelled'=>['Rejected','status-other'],
];
[$po_status_label, $po_status_cls] = $status_map[$po->status] ?? [$po->status,'status-other'];

/* Amount in words */
function numToWords($n) {
    $ones=['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
           'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
           'Seventeen','Eighteen','Nineteen'];
    $tens=['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    if ($n < 20) return $ones[$n];
    if ($n < 100) return $tens[intval($n/10)].($n%10?' '.$ones[$n%10]:'');
    if ($n < 1000) return $ones[intval($n/100)].' Hundred'.(($n%100)?' '.numToWords($n%100):'');
    if ($n < 100000) return numToWords(intval($n/1000)).' Thousand'.(($n%1000)?' '.numToWords($n%1000):'');
    if ($n < 10000000) return numToWords(intval($n/100000)).' Lakh'.(($n%100000)?' '.numToWords($n%100000):'');
    return numToWords(intval($n/10000000)).' Crore'.(($n%10000000)?' '.numToWords($n%10000000):'');
}
$net = (int)round($po->net_total);
$paise = round(($po->net_total - $net) * 100);
$in_words = 'Rupees '.numToWords($net).($paise>0?' and '.numToWords($paise).' Paise':'').' Only';
?>

<!-- Print toolbar -->
<div class="no-print">
    <button class="btn btn-dark" onclick="window.print()">&#128438; Print / Save as PDF</button>
    <a class="btn btn-green" href="https://wa.me/?text=<?= urlencode('PO '.$po->po_number.' from '.$c_name.' | Supplier: '.$po->supplier_name.' | Amount: Rs.'.number_format($po->net_total,2)) ?>" target="_blank">&#128241; WhatsApp</a>
    <a class="btn btn-outline" href="mailto:<?= htmlspecialchars($po->email??'') ?>?subject=<?= urlencode('Purchase Order '.$po->po_number) ?>&body=<?= urlencode('Dear '.$po->supplier_name.',\n\nPlease find PO '.$po->po_number.' for Rs.'.number_format($po->net_total,2).'\n\nRegards,\n'.$c_name) ?>">&#9993; Email</a>
    <button class="btn btn-outline" onclick="window.close()">&#10005; Close</button>
</div>

<div class="page">

    <!-- ===== HEADER ===== -->
    <div class="po-header">
        <div class="logo-wrap">
            <img src="<?= $logo_url ?>" alt="<?= htmlspecialchars($c_name) ?>" onerror="this.style.display='none';document.getElementById('co-name').style.display='block'">
            <div id="co-name" class="company-name" style="display:none;"><?= htmlspecialchars($c_name) ?></div>
        </div>
        <div class="company-info">
            <strong><?= htmlspecialchars($c_name) ?></strong><br>
            <?= nl2br(htmlspecialchars($c_addr)) ?><br>
            <?php if ($c_phone): ?>&#128222; <?= htmlspecialchars($c_phone) ?><br><?php endif; ?>
            <?php if ($c_email): ?>&#9993; <?= htmlspecialchars($c_email) ?><br><?php endif; ?>
            <?php if ($c_gstin): ?><strong>GSTIN:</strong> <?= htmlspecialchars($c_gstin) ?><?php endif; ?>
        </div>
    </div>

    <!-- ===== PO TITLE BAND ===== -->
    <div class="po-title-band">
        <h2>PURCHASE ORDER</h2>
        <div class="po-sub"><?= htmlspecialchars($po->po_number) ?> &nbsp;&bull;&nbsp; <?= date('d F Y', strtotime($po->created_at)) ?> &nbsp;&bull;&nbsp; <span class="<?= $po_status_cls ?>"><?= $po_status_label ?></span></div>
    </div>

    <!-- ===== META ROW ===== -->
    <div class="meta-row">
        <!-- To (Supplier) -->
        <div class="meta-box supplier-box">
            <h4>Supplier (To)</h4>
            <table>
                <tr><td>Name</td><td><?= htmlspecialchars($po->supplier_name) ?></td></tr>
                <?php if (!empty($po->supplier_address)): ?>
                <tr><td>Address</td><td><?= htmlspecialchars($po->supplier_address) ?><?= !empty($po->supplier_city) ? ', '.htmlspecialchars($po->supplier_city) : '' ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($po->supplier_state)): ?>
                <tr><td>State</td><td><?= htmlspecialchars($po->supplier_state) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($po->supplier_mobile)): ?>
                <tr><td>Phone</td><td><?= htmlspecialchars($po->supplier_mobile) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($po->email)): ?>
                <tr><td>Email</td><td><?= htmlspecialchars($po->email) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($po->supplier_gstin)): ?>
                <tr><td>GSTIN</td><td><strong><?= htmlspecialchars($po->supplier_gstin) ?></strong></td></tr>
                <?php endif; ?>
                <?php if (!empty($po->supplier_code)): ?>
                <tr><td>Vendor Code</td><td><?= htmlspecialchars($po->supplier_code) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- PO Details -->
        <div class="meta-box po-meta-box">
            <h4>Order Details</h4>
            <table>
                <tr><td>PO Number</td><td><strong><?= htmlspecialchars($po->po_number) ?></strong></td></tr>
                <tr><td>PO Date</td><td><?= date('d M Y', strtotime($po->created_at)) ?></td></tr>
                <tr><td>Exp. Delivery</td><td><strong><?= $po->expected_delivery ? date('d M Y', strtotime($po->expected_delivery)) : '—' ?></strong></td></tr>
                <tr><td>Payment Terms</td><td><?= htmlspecialchars($po->payment_terms ?? '—') ?></td></tr>
                <tr><td>Location</td><td><?= htmlspecialchars($po->location_name ?? 'Main Store') ?></td></tr>
                <tr><td>Buyer</td><td><?= htmlspecialchars($po->buyer_name ?? '—') ?></td></tr>
                <?php if (!empty($po->reference)): ?>
                <tr><td>Reference</td><td><?= htmlspecialchars($po->reference) ?></td></tr>
                <?php endif; ?>
                <tr><td>Status</td><td><span class="status-badge <?= $po_status_cls ?>"><?= $po_status_label ?></span></td></tr>
            </table>
        </div>

        <!-- Ship To -->
        <div class="meta-box po-meta-box">
            <h4>Ship To</h4>
            <table>
                <tr><td colspan="2"><strong><?= htmlspecialchars($c_name) ?></strong></td></tr>
                <?php if ($po->delivery_address): ?>
                <tr><td colspan="2"><?= nl2br(htmlspecialchars($po->delivery_address)) ?></td></tr>
                <?php else: ?>
                <tr><td colspan="2"><?= nl2br(htmlspecialchars($c_addr)) ?></td></tr>
                <?php endif; ?>
                <?php if ($c_gstin): ?>
                <tr><td>GSTIN</td><td><?= htmlspecialchars($c_gstin) ?></td></tr>
                <?php endif; ?>
                <?php if ($c_phone): ?>
                <tr><td>Phone</td><td><?= htmlspecialchars($c_phone) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- ===== ITEMS TABLE ===== -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:28px;">#</th>
                <th>Product Description</th>
                <th style="width:55px;">HSN</th>
                <th style="width:35px;" class="r">Unit</th>
                <th style="width:50px;" class="r">Qty</th>
                <th style="width:65px;" class="r">Rate (&#8377;)</th>
                <th style="width:42px;" class="r">Disc%</th>
                <th style="width:42px;" class="r">GST%</th>
                <th style="width:42px;" class="r">Tax (&#8377;)</th>
                <th style="width:72px;" class="r">Amount (&#8377;)</th>
            </tr>
        </thead>
        <tbody>
        <?php $sno = 1; foreach ($items as $item): ?>
        <tr>
            <td style="color:#888;"><?= $sno++ ?></td>
            <td>
                <strong><?= htmlspecialchars($item->product_name) ?></strong>
                <?php if (!empty($item->item_code)): ?><br><span style="color:#888;font-size:8.5px;"><?= htmlspecialchars($item->item_code) ?></span><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($item->hsn_code ?? '—') ?></td>
            <td class="r"><?= htmlspecialchars($item->unit ?? 'PCS') ?></td>
            <td class="r"><strong><?= number_format($item->ordered_qty,2) ?></strong></td>
            <td class="r"><?= number_format($item->rate,2) ?></td>
            <td class="r"><?= number_format($item->discount_pct,1) ?>%</td>
            <td class="r"><?= number_format($item->gst_pct,1) ?>%</td>
            <td class="r"><?= number_format($item->tax_amt ?? 0,2) ?></td>
            <td class="r"><strong><?= number_format($item->amount,2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ===== TOTALS ===== -->
    <div class="totals-wrap">
        <table class="totals-table">
            <tr><td>Gross Amount</td><td>&#8377; <?= number_format($po->gross_amount,2) ?></td></tr>
            <?php if ($po->total_discount > 0): ?>
            <tr><td>Discount (&#8722;)</td><td style="color:#c0392b;">&#8377; <?= number_format($po->total_discount,2) ?></td></tr>
            <?php endif; ?>
            <tr><td>Taxable Amount</td><td>&#8377; <?= number_format($po->gross_amount - $po->total_discount,2) ?></td></tr>
            <tr><td>GST / Tax</td><td>&#8377; <?= number_format($po->total_tax,2) ?></td></tr>
            <?php if ($po->freight_charges > 0): ?>
            <tr><td>Freight Charges</td><td>&#8377; <?= number_format($po->freight_charges,2) ?></td></tr>
            <?php endif; ?>
            <?php if ($po->round_off != 0): ?>
            <tr><td>Round Off</td><td>&#8377; <?= number_format($po->round_off,2) ?></td></tr>
            <?php endif; ?>
            <tr class="net-row"><td>NET TOTAL</td><td>&#8377; <?= number_format($po->net_total,2) ?></td></tr>
        </table>
    </div>

    <!-- Amount in words -->
    <div class="amt-words">
        <strong>Amount in Words:</strong> <?= $in_words ?>
    </div>

    <!-- Remarks -->
    <?php if (!empty($po->remarks)): ?>
    <div class="terms-box" style="margin-bottom:8px;">
        <h4>Remarks / Special Instructions</h4>
        <?= nl2br(htmlspecialchars($po->remarks)) ?>
    </div>
    <?php endif; ?>

    <!-- Terms & Conditions -->
    <div class="terms-box">
        <h4>Terms &amp; Conditions</h4>
        1. Please confirm the order within 24 hours of receipt.&nbsp;&nbsp;
        2. Goods once dispatched will not be returned unless damaged or wrong.&nbsp;&nbsp;
        3. Payment will be made as per agreed payment terms.&nbsp;&nbsp;
        4. All goods must be accompanied by a valid invoice with GSTIN details.&nbsp;&nbsp;
        5. This is a computer-generated document and does not require a physical signature unless specified.
    </div>

    <!-- Signatures -->
    <div class="sig-row">
        <div class="sig-box">
            <div class="sig-line">Prepared By</div>
            <div class="sig-sub"><?= htmlspecialchars($po->buyer_name ?? '—') ?></div>
        </div>
        <div class="sig-box">
            <div class="sig-line">Checked By</div>
            <div class="sig-sub">Purchase Manager</div>
        </div>
        <div class="sig-box">
            <div class="sig-line">Approved By</div>
            <div class="sig-sub"><?= !empty($po->approved_by) ? htmlspecialchars($po->buyer_name) : 'Authorised Signatory' ?></div>
        </div>
        <div class="sig-box">
            <div class="sig-line">Supplier Acknowledgement</div>
            <div class="sig-sub">Seal &amp; Signature</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="po-footer">
        <?= htmlspecialchars($c_name) ?> &nbsp;&bull;&nbsp; <?= htmlspecialchars($c_addr) ?>
        <?php if ($c_gstin): ?> &nbsp;&bull;&nbsp; GSTIN: <?= htmlspecialchars($c_gstin) ?><?php endif; ?>
        <br>Generated on <?= date('d M Y H:i') ?> &nbsp;&bull;&nbsp; <?= htmlspecialchars($po->po_number) ?>
    </div>

</div><!-- /page -->

<script>
// Auto-print if ?autoprint=1 in URL
if (window.location.search.indexOf('autoprint=1') > -1) { window.onload = function(){ window.print(); } }
</script>
</body>
</html>
