<?php
$words = function ($n) {
    $n = (int)round($n);
    $a = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $t = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    $two = function ($x) use ($a, $t) { return $x < 20 ? $a[$x] : trim($t[intdiv($x, 10)] . ' ' . $a[$x % 10]); };
    if ($n === 0) return 'Zero';
    $out = [];
    foreach ([[10000000, 'Crore'], [100000, 'Lakh'], [1000, 'Thousand'], [100, 'Hundred']] as [$div, $lbl]) {
        if ($n >= $div) { $out[] = $two(intdiv($n, $div)) . ' ' . $lbl; $n %= $div; }
    }
    if ($n) $out[] = $two($n);
    return implode(' ', $out);
};
$ship_rate = 0; foreach ($items as $it) $ship_rate = max($ship_rate, (float)$it->gst_rate);
$addr = nl2br(html_escape(str_replace('\n', "\n", $company['company_address'] ?? '')));
?><!doctype html>
<html lang="en-IN"><head><meta charset="utf-8"><meta name="robots" content="noindex">
<title><?= $slip ? 'Packing slip' : 'Tax invoice' ?> <?= html_escape($o->invoice_no ?: $o->order_no) ?></title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 24px; }
  .doc { max-width: 820px; margin: 0 auto; border: 1px solid #ccc; padding: 20px; }
  .head { display: flex; justify-content: space-between; gap: 20px; border-bottom: 2px solid #7a1420; padding-bottom: 12px; }
  .head img { height: 80px; border-radius: 4px; } h1 { color: #7a1420; font-size: 20px; margin: 0 0 4px; } h2 { font-size: 16px; margin: 0; text-align: right; color: #7a1420; }
  table { width: 100%; border-collapse: collapse; margin-top: 12px; } th, td { border: 1px solid #ccc; padding: 6px; text-align: left; vertical-align: top; }
  th { background: #f6efe6; font-size: 11px; } .r { text-align: right; } .two { display: flex; gap: 12px; margin-top: 12px; } .two > div { flex: 1; border: 1px solid #ccc; padding: 8px; }
  .muted { color: #666; } .box { width: 14px; height: 14px; border: 1px solid #333; display: inline-block; }
  .no-print { text-align: center; margin-bottom: 12px; } @media print { .no-print { display: none; } body { padding: 0; } .doc { border: 0; } }
</style></head><body>
<div class="no-print"><button onclick="window.print()">Print</button></div>
<div class="doc">
  <div class="head">
    <div style="display:flex;gap:14px">
      <img src="<?= base_url('assets/web/logo.png') ?>" alt="">
      <div><h1><?= html_escape($company['company_name'] ?? '') ?></h1><div><?= $addr ?></div>
        <?php if (!empty($company['company_gstin'])): ?><div><b>GSTIN:</b> <?= html_escape($company['company_gstin']) ?> · State: <?= html_escape(ucwords(strtolower($company['company_state'] ?? ''))) ?> (<?= html_escape($company['company_state_code'] ?? '') ?>)</div><?php endif; ?>
        <?php if (!empty($company['company_phone'])): ?><div>Phone: <?= html_escape($company['company_phone']) ?></div><?php endif; ?></div>
    </div>
    <div><h2><?= $slip ? 'PACKING SLIP' : 'TAX INVOICE' ?></h2>
      <div class="r" style="margin-top:6px">
        <?php if (!$slip): ?><b>Invoice No:</b> <?= html_escape($o->invoice_no ?: 'Not yet issued (issued on confirmation)') ?><br><?php endif; ?>
        <b>Order No:</b> <?= html_escape($o->order_no) ?><br><b>Order date:</b> <?= date('d-m-Y', strtotime($o->created_at)) ?><br>
        <?php if (!$slip): ?><b>Place of supply:</b> <?= html_escape($o->state) ?> (<?= html_escape($o->state_code) ?>)<br><?php endif; ?>
        <b>Payment:</b> <?= strtoupper($o->payment_method) ?> — <?= html_escape(Webstore_model::status_label($o->payment_status)) ?>
      </div></div>
  </div>
  <div class="two">
    <div><b>Bill to / Ship to</b><br><?= html_escape($o->customer_name) ?><br><?= html_escape($o->address_line) ?><?= $o->landmark ? ', ' . html_escape($o->landmark) : '' ?><br><?= html_escape($o->city . ', ' . $o->state . ' - ' . $o->pincode) ?><br>Mobile: <?= html_escape($o->phone) ?></div>
    <?php if ($o->courier || $o->customer_notes): ?><div><?php if ($o->courier): ?><b>Courier:</b> <?= html_escape($o->courier) ?> <?= html_escape($o->tracking_no) ?><br><?php endif; ?><?php if ($o->customer_notes): ?><b>Customer note:</b> <?= html_escape($o->customer_notes) ?><?php endif; ?></div><?php endif; ?>
  </div>

  <?php if ($slip): ?>
  <table><tr><th>#</th><th>Item</th><th>Size</th><th>Colour</th><th>SKU</th><th>Barcode</th><th class="r">Qty</th><th>Packed</th></tr>
    <?php foreach ($items as $k => $it): ?><tr><td><?= $k + 1 ?></td><td><?= html_escape($it->product_name) ?></td><td><?= html_escape($it->size_name) ?></td><td><?= html_escape($it->colour_name) ?></td><td><?= html_escape($it->sku) ?></td><td><?= html_escape($it->barcode) ?></td><td class="r"><b><?= (int)$it->qty ?></b></td><td><span class="box"></span></td></tr><?php endforeach; ?>
    <tr><td colspan="6" class="r"><b>Total pieces</b></td><td class="r"><b><?= array_sum(array_column($items, 'qty')) ?></b></td><td></td></tr>
  </table>
  <p style="margin-top:30px">Packed by: ____________________ &nbsp; Checked by: ____________________</p>
  <?php else: ?>
  <table>
    <tr><th>#</th><th>Description</th><th>HSN</th><th class="r">Qty</th><th class="r">Rate (incl.)</th><th class="r">Disc.</th><th class="r">Taxable</th><th class="r">GST %</th>
      <?= $o->is_interstate ? '<th class="r">IGST</th>' : '<th class="r">CGST</th><th class="r">SGST</th>' ?><th class="r">Amount</th></tr>
    <?php foreach ($items as $k => $it): ?>
    <tr><td><?= $k + 1 ?></td><td><?= html_escape($it->product_name) ?><?= $it->size_name ? '<br><span class="muted">' . html_escape($it->size_name . ' / ' . $it->colour_name . ' · ' . $it->sku) . '</span>' : '' ?></td><td><?= html_escape($it->hsn_code) ?></td>
      <td class="r"><?= (int)$it->qty ?></td><td class="r"><?= number_format($it->unit_price, 2) ?></td><td class="r"><?= number_format($it->discount, 2) ?></td><td class="r"><?= number_format($it->taxable, 2) ?></td><td class="r"><?= (float)$it->gst_rate ?></td>
      <?= $o->is_interstate ? '<td class="r">' . number_format($it->igst, 2) . '</td>' : '<td class="r">' . number_format($it->cgst, 2) . '</td><td class="r">' . number_format($it->sgst, 2) . '</td>' ?><td class="r"><?= number_format($it->line_total, 2) ?></td></tr>
    <?php endforeach; ?>
    <?php if ($o->shipping > 0):
        $s_tax = round($o->shipping / (1 + $ship_rate / 100), 2); $s_gst = round($o->shipping - $s_tax, 2); ?>
    <tr><td></td><td>Shipping charges</td><td>996812</td><td class="r">1</td><td class="r"><?= number_format($o->shipping, 2) ?></td><td class="r">0.00</td><td class="r"><?= number_format($s_tax, 2) ?></td><td class="r"><?= $ship_rate ?></td>
      <?= $o->is_interstate ? '<td class="r">' . number_format($s_gst, 2) . '</td>' : '<td class="r">' . number_format(round($s_gst / 2, 2), 2) . '</td><td class="r">' . number_format($s_gst - round($s_gst / 2, 2), 2) . '</td>' ?><td class="r"><?= number_format($o->shipping, 2) ?></td></tr>
    <?php endif; ?>
    <tr><td colspan="6" class="r"><b>Total</b></td><td class="r"><b><?= number_format($o->taxable_total, 2) ?></b></td><td></td>
      <?= $o->is_interstate ? '<td class="r"><b>' . number_format($o->igst, 2) . '</b></td>' : '<td class="r"><b>' . number_format($o->cgst, 2) . '</b></td><td class="r"><b>' . number_format($o->sgst, 2) . '</b></td>' ?><td class="r"><b><?= number_format($o->grand_total, 2) ?></b></td></tr>
  </table>
  <p><b>Amount in words:</b> Rupees <?= $words($o->grand_total) ?> Only<?= $o->coupon_code ? ' · Coupon ' . html_escape($o->coupon_code) . ' applied (₹' . number_format($o->discount, 2) . ')' : '' ?></p>
  <p class="muted">Prices are inclusive of GST. Tax is not payable on reverse charge basis. <?= html_escape($company['invoice_terms'] ?? '') ?></p>
  <p class="r" style="margin-top:40px">For <?= html_escape($company['company_name'] ?? '') ?><br><br><br>Authorised Signatory</p>
  <?php endif; ?>
</div>
</body></html>
