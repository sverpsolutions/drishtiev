<?php
// A5 invoice body. Included from billing/print.php when printer_type = 'A5'.
// Every amount comes from the saved invoice/items (same figures as the A4 bill) — nothing recomputed.
$f = function ($k, $d = 1) use ($company) { return isset($company[$k]) ? (int)$company[$k] !== 0 : (bool)$d; };
$compact     = ($company['a5_layout'] ?? 'compact') !== 'standard';
$show_logo   = $f('a5_show_logo');
$show_contact= $f('a5_show_contact');
$show_cust   = $f('a5_show_customer');
$show_code   = $f('a5_show_code', 0);
$show_sc     = $f('a5_show_size_colour');
$show_sig    = $f('a5_show_signature');
$show_stamp  = $f('a5_show_stamp');
$show_terms  = $f('a5_show_terms');
$show_msg    = $f('a5_show_footer_msg');
$show_tax    = $print_show_tax && $f('a5_show_tax');
$img = function ($k) use ($company) {
    $p = $company[$k] ?? '';
    return ($p !== '' && file_exists('./' . $p)) ? base_url($p) : '';
};
$img_default = function ($p) { return file_exists('./' . $p) ? base_url($p) : ''; };
$logo = $show_logo ? $img('company_logo') : '';
$hdr_name = ($outlet && !empty($outlet->name)) ? $outlet->name : ($company['company_name'] ?? '');
$hdr_addr = ($outlet && !empty($outlet->address)) ? $outlet->address : ($company['company_address'] ?? '');
$hdr_ph   = ($outlet && !empty($outlet->phone)) ? $outlet->phone : ($company['company_phone'] ?? '');
$hdr_gst  = ($outlet && !empty($outlet->gstin)) ? $outlet->gstin : ($company['company_gstin'] ?? '');
$heading  = !empty($company['a5_invoice_heading']) ? $company['a5_invoice_heading'] : 'TAX INVOICE';
// GST-portal style address ("Floor No.: X Building No./Flat No.: Y ...") -> one tidy line
if (preg_match('/Floor No\.:|Building No\./i', $hdr_addr)) {
    $v = function ($re) use ($hdr_addr) { return preg_match($re, $hdr_addr, $m) ? trim($m[1]) : ''; };
    $lbl = 'Floor No\.:|Building No\.\/Flat No\.:|Name Of Premises\/Building:|Road\/Street:|Locality\/Sub Locality:|City\/Town\/Village:|District:|State:|PIN Code:';
    $g = function ($k) use ($v, $lbl) { return $v('/' . $k . '\s*(.*?)\s*(?=' . $lbl . '|$)/is'); };
    $pin = $g('PIN Code:');
    $parts = array_filter([$g('Building No\.\/Flat No\.:'), ucwords(strtolower($g('Floor No\.:'))), $g('Name Of Premises\/Building:'), ucwords(strtolower($g('Road\/Street:'))), $g('City\/Town\/Village:'), $g('District:')]);
    $st = $g('State:');
    $hdr_addr = implode(', ', array_unique($parts)) . ($st ? ', ' . $st : '') . ($pin ? ' - ' . $pin : '');
}
if (strlen(preg_replace('/\D/', '', $hdr_ph)) < 8) $hdr_ph = '';
$items_total = array_sum(array_map(function ($it) { return (float)$it->total; }, $items));
$ratio = ($invoice->cd_amount > 0 && $items_total > 0) ? ($invoice->total_amount / $items_total) : 1;
$tqty = 0; foreach ($items as $it) $tqty += (float)$it->qty;
$n = function ($v) { return number_format((float)$v, 2); };
$q = function ($v) { return $v == floor($v) ? (int)$v : $v; };
?>
<style>
.page.a5-layout { width: 148mm; min-height: 210mm; margin: 20px auto; padding: 5mm; box-shadow: 0 0 10px rgba(0,0,0,.1); font-size: <?= $compact ? '9.5pt' : '10.5pt' ?>; line-height: 1.25; }
.a5-layout, .a5-layout * { color:#000; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
.a5-layout { font-family:Arial,Helvetica,sans-serif; font-weight:500; }
.a5-layout .it th, .a5-layout .ttl, .a5-layout .tot .g td { color:#fff; }
.a5-layout .co { color:#10243f; }
.a5-layout .hd { display:flex; gap:4mm; align-items:center; border-bottom:.6mm solid #1f3a5f; padding-bottom:2mm; }
.a5-layout .hd img { height:26mm; max-width:38mm; width:auto; object-fit:contain; flex-shrink:0; }
.a5-layout .hd>div { flex:1; text-align:center; }
.a5-layout .co { font-size:1.9em; font-weight:800; color:#1f3a5f; line-height:1.1; }
.a5-layout .sub { font-size:1em; font-weight:600; margin-top:.6mm; }
.a5-layout .meta { display:flex; justify-content:space-between; gap:3mm; margin:1.5mm 0; font-size:.95em; }
.a5-layout .ttl { text-align:center; font-weight:700; letter-spacing:1px; margin-top:2mm; background:#10243f; color:#fff; padding:.8mm 0; }
.a5-layout table { width:100%; border-collapse:collapse; table-layout:fixed; }
.a5-layout .it th { background:#10243f; color:#fff; border:.3mm solid #10243f; background:#10243f; padding:.8mm 1mm; font-size:.9em; }
.a5-layout .it tbody tr:nth-child(even) td { background:#f1f5fa; }
.a5-layout .it td { border:.3mm solid #4a5a73; padding:.8mm 1mm; vertical-align:top; word-wrap:break-word; overflow-wrap:anywhere; }
.a5-layout .r { text-align:right; } .a5-layout .c { text-align:center; }
.a5-layout .tot { width:62mm; margin-left:auto; margin-top:1.5mm; }
.a5-layout .tot td { padding:.4mm 1mm; } .a5-layout .tot td:last-child { text-align:right; }
.a5-layout .tot .g td { background:#10243f; color:#fff; font-weight:700; font-size:1.15em; }
.a5-layout .ft { margin-top:2mm; border-top:.3mm solid #000; padding-top:1.5mm; display:flex; justify-content:space-between; gap:3mm; font-size:.88em; }
.a5-layout .sig { text-align:center; min-width:40mm; }
.a5-layout .sig .box { height:30mm; display:flex; align-items:flex-end; justify-content:center; gap:2mm; }
.a5-layout .sig img { max-height:30mm; max-width:30mm; object-fit:contain; mix-blend-mode:multiply; }
@media print {
  html, body { width:148mm; background:#fff; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  .page.a5-layout { width:138mm; min-height:0; margin:0 auto; padding:0; box-shadow:none; }
  .a5-layout thead { display:table-header-group; }
  .a5-layout tr, .a5-layout .tot, .a5-layout .ft { break-inside:avoid; }
}
</style>
<div class="page a5-layout">
  <div class="hd">
    <?php if ($logo): ?><img src="<?= $logo ?>" alt="Logo"><?php endif; ?>
    <div>
      <div class="co"><?= htmlspecialchars($hdr_name) ?><?php if (!empty($company['a5_branch_name'])): ?> <small>(<?= htmlspecialchars($company['a5_branch_name']) ?>)</small><?php endif; ?></div>
      <?php if ($show_contact): ?>
      <div class="sub"><?= htmlspecialchars($hdr_addr) ?></div>
      <div class="sub">
        <?php if ($hdr_ph): ?>Ph: <?= htmlspecialchars($hdr_ph) ?> <?php endif; ?>
        <?php if (!empty($company['company_email'])): ?>| <?= htmlspecialchars($company['company_email']) ?> <?php endif; ?>
        <?php if (!empty($company['company_website'])): ?>| <?= htmlspecialchars($company['company_website']) ?><?php endif; ?>
      </div>
      <?php if ($hdr_gst): ?><div class="sub">GSTIN: <strong><?= htmlspecialchars($hdr_gst) ?></strong> | State: <?= htmlspecialchars($company['company_state'] ?? '') ?> (<?= htmlspecialchars($company['company_state_code'] ?? '') ?>)</div><?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="ttl"><?= htmlspecialchars($heading) ?></div>
  <div class="meta">
    <div>
      Invoice: <strong><?= htmlspecialchars($invoice->invoice_no) ?></strong><br>
      Date: <?= date('d-M-Y', strtotime($invoice->invoice_date)) ?><?= $invoice->created_at ? ' ' . date('h:i A', strtotime($invoice->created_at)) : '' ?><br>
      <?php if ($outlet): ?>Store: <?= htmlspecialchars($outlet->name) ?><br><?php endif; ?>
      <?php if (!empty($invoice->created_by_name)): ?>Cashier: <?= htmlspecialchars($invoice->created_by_name) ?><?php endif; ?>
    </div>
    <?php if ($show_cust): ?>
    <div style="text-align:right">
      <strong><?= htmlspecialchars($invoice->customer_name) ?></strong><br>
      <?php if (!empty($invoice->customer_phone)): ?>Ph: <?= htmlspecialchars($invoice->customer_phone) ?><br><?php endif; ?>
      <?php if ($show_tax && !empty($invoice->customer_gstin)): ?>GSTIN: <?= htmlspecialchars($invoice->customer_gstin) ?><br><?php endif; ?>
      <?php if ($show_tax && !empty($invoice->customer_address)): ?><?= htmlspecialchars($invoice->customer_address) ?><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <table class="it">
    <thead><tr>
      <th style="width:5%">#</th>
      <th><?= 'Item' ?></th>
      <?php if ($show_sc): ?><th style="width:9%">Size</th><th style="width:12%">Colour</th><?php endif; ?>
      <th style="width:7%">Qty</th>
      <th style="width:12%">Rate</th>
      <th style="width:10%">Disc</th>
      <?php if ($show_tax): ?><th style="width:8%">GST%</th><?php endif; ?>
      <th style="width:14%">Total</th>
    </tr></thead>
    <tbody>
    <?php foreach ($items as $i => $it): ?>
      <tr>
        <td class="c"><?= $i + 1 ?></td>
        <td><strong><?= htmlspecialchars(strtoupper($it->product_name)) ?></strong>
          <?php if ($show_code && !empty($it->product_item_code)): ?><br><small><?= htmlspecialchars($it->product_item_code) ?></small><?php endif; ?>
          <?php if ($show_tax && !empty($it->hsn_code)): ?><br><small>HSN <?= htmlspecialchars($it->hsn_code) ?></small><?php endif; ?></td>
        <?php if ($show_sc): ?><td class="c"><?= htmlspecialchars($it->size_name ?? '') ?></td><td class="c"><?= htmlspecialchars($it->colour_name ?? '') ?></td><?php endif; ?>
        <td class="c"><?= $q($it->qty) ?></td>
        <td class="r"><?= $n($it->rate) ?></td>
        <td class="r"><?= $n($it->discount) ?></td>
        <?php if ($show_tax): ?><td class="c"><?= $it->gst_percent ?>%</td><?php endif; ?>
        <td class="r"><strong><?= $n($it->total * $ratio) ?></strong></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <table class="tot">
    <tr><td>Total Qty</td><td><?= $q($tqty) ?></td></tr>
    <tr><td>Gross Amount</td><td><?= $n($invoice->subtotal) ?></td></tr>
    <?php if ($invoice->discount > 0): ?><tr><td>Discount</td><td>- <?= $n($invoice->discount) ?></td></tr><?php endif; ?>
    <?php if ($show_tax): ?>
      <tr><td>Taxable Amount</td><td><?= $n($invoice->taxable_amount) ?></td></tr>
      <?php if ($invoice->is_interstate): ?>
        <tr><td>IGST</td><td><?= $n($invoice->igst_amount) ?></td></tr>
      <?php else: ?>
        <tr><td>CGST</td><td><?= $n($invoice->cgst_amount) ?></td></tr>
        <tr><td>SGST</td><td><?= $n($invoice->sgst_amount) ?></td></tr>
      <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($invoice->cd_percent) && $invoice->cd_percent > 0): ?><tr><td>CD (<?= $invoice->cd_percent ?>%)</td><td>- <?= $n($invoice->cd_amount) ?></td></tr><?php endif; ?>
    <?php if (!empty($invoice->round_off) && $invoice->round_off != 0): ?><tr><td>Round Off</td><td><?= $invoice->round_off > 0 ? '+' : '-' ?> <?= $n(abs($invoice->round_off)) ?></td></tr><?php endif; ?>
    <tr class="g"><td>PAYABLE</td><td>₹ <?= $n($invoice->total_amount) ?></td></tr>
    <?php if ($print_show_pending): ?>
      <tr><td>Received (<?= htmlspecialchars(ucfirst($invoice->payment_mode)) ?>)</td><td><?= $n($invoice->paid_amount) ?></td></tr>
      <?php if ($invoice->due_amount > 0): ?><tr><td>Balance Due</td><td><strong><?= $n($invoice->due_amount) ?></strong></td></tr><?php endif; ?>
    <?php endif; ?>
  </table>

  <div class="ft">
    <div style="flex:1">
      <?php if ($show_msg): ?><div><strong><?= htmlspecialchars($company['a5_footer_msg'] ?? '') ?: 'Thank you! Visit again.' ?></strong></div><?php endif; ?>
      
      <?php if ($show_terms && !empty($company['invoice_terms'])): ?><div><strong>Terms:</strong> <?= htmlspecialchars($company['invoice_terms']) ?></div><?php endif; ?>
    </div>
    <?php if ($show_sig || $show_stamp): ?>
    <div class="sig">
      <div>For <?= htmlspecialchars($hdr_name) ?></div>
      <div class="box">
        <?php if ($show_stamp && ($s = ($img('company_stamp') ?: $img_default('assets/uploads/company_stamp_suvasan.png')))): ?><img src="<?= $s ?>" alt="Stamp"><?php endif; ?>
        <?php if ($show_sig && ($s = $img('company_signature'))): ?><img src="<?= $s ?>" alt="Signature"><?php endif; ?>
      </div>
      <?php if ($show_sig): ?>
      <div style="border-top:.2mm solid #333"><?= htmlspecialchars(($company['a5_signatory_name'] ?? '') ?: 'Authorised Signatory') ?><?php if (!empty($company['a5_signatory_title'])): ?><br><small><?= htmlspecialchars($company['a5_signatory_title']) ?></small><?php endif; ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php if ($show_msg): ?>
  <div style="margin-top:2mm;border:.3mm solid #000;padding:1mm 2mm;text-align:center;font-size:.9em;font-weight:700;">
    <?= htmlspecialchars(!empty($company['a5_return_policy']) ? $company['a5_return_policy'] : 'No cash refund. Exchange only within 3 days, with price tag intact, unused, undamaged and clean. Exchange after 2 PM only.') ?>
  </div>
  <?php endif; ?>
</div>
