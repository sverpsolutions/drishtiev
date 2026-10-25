<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Shift Receipt #<?= $shift->id ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    line-height: 1.4;
    width: 72mm;
    margin: 0 auto;
    padding: 4px 6px 20px 6px;
    color: #000;
    background: #fff;
}
.center  { text-align: center; }
.right   { text-align: right; }
.bold    { font-weight: bold; }
.dashed  { border-top: 1px dashed #000; margin: 4px 0; }
.solid   { border-top: 1px solid #000;  margin: 3px 0; }
.row     { display: flex; justify-content: space-between; margin: 2px 0; }
.row .lbl{ flex: 1; }
.row .val{ text-align: right; min-width: 60px; white-space: nowrap; }
.section-title {
    background: #000; color: #fff;
    text-align: center; font-weight: bold;
    padding: 2px 0; font-size: 10px;
    letter-spacing: 0.5px; margin: 6px 0 3px 0;
}
.sub-title {
    text-align: center; font-weight: bold;
    border: 1px solid #000; padding: 1px 0;
    font-size: 9px; margin: 4px 0 2px 0;
    letter-spacing: 0.3px;
}
.warn  { font-weight: bold; }
.sig-line { border-top: 1px dashed #000; margin-top: 20px; }
table { font-family: Arial, Helvetica, sans-serif; }
@media print {
    @page { margin: 0; size: 72mm auto; }
    body  { width: 72mm; }
    .no-print { display: none; }
}
</style>
</head>
<body>

<!-- Store Header -->
<div class="center bold" style="font-size:13px;"><?= htmlspecialchars($company['company_name'] ?? 'STORE') ?></div>
<?php if (!empty($company['company_address'])): ?>
<div class="center" style="font-size:9px;"><?= htmlspecialchars($company['company_address']) ?></div>
<?php endif; ?>
<?php if (!empty($company['company_phone'])): ?>
<div class="center" style="font-size:9px;">Ph: <?= htmlspecialchars($company['company_phone']) ?></div>
<?php endif; ?>

<div class="dashed"></div>
<div class="center bold" style="font-size:12px;">** SHIFT CLOSING RECEIPT **</div>
<div class="dashed"></div>

<!-- Shift Info -->
<div class="row"><span class="lbl">Shift #</span><span class="val bold"><?= $shift->id ?></span></div>
<div class="row"><span class="lbl">Cashier</span><span class="val bold"><?= htmlspecialchars($shift->cashier_name) ?></span></div>
<?php if (!empty($shift->shift_name)): ?>
<div class="row"><span class="lbl">Shift Type</span><span class="val"><?= htmlspecialchars($shift->shift_name) ?></span></div>
<?php endif; ?>
<?php if (!empty($shift->system_name)): ?>
<div class="row"><span class="lbl">Counter/System</span><span class="val"><?= htmlspecialchars($shift->system_name) ?></span></div>
<?php endif; ?>
<?php if (!empty($shift->terminal_no)): ?>
<div class="row"><span class="lbl">Terminal</span><span class="val"><?= htmlspecialchars($shift->terminal_no) ?></span></div>
<?php endif; ?>
<div class="row"><span class="lbl">Business Date</span><span class="val"><?= date('d-M-Y', strtotime($shift->business_date)) ?></span></div>
<div class="row"><span class="lbl">Shift Open</span><span class="val"><?= date('d-M-Y H:i', strtotime($shift->opened_at)) ?></span></div>
<div class="row"><span class="lbl">Shift Close</span><span class="val"><?= $shift->closed_at ? date('d-M-Y H:i', strtotime($shift->closed_at)) : '--' ?></span></div>
<div class="row"><span class="lbl">Print Time</span><span class="val"><?= date('d-M-Y H:i:s') ?></span></div>

<!-- ═══ 1. SALES SUMMARY ═══ -->
<div class="section-title">1. SALES SUMMARY</div>
<div class="row"><span class="lbl">Opening Cash Float</span><span class="val">Rs.<?= number_format($shift->opening_cash, 2) ?></span></div>
<div class="row bold"><span class="lbl">Bills Completed</span><span class="val"><?= $bill_count ?></span></div>
<div class="row"><span class="lbl">Bills Cancelled</span><span class="val"><?= $cancelled_count ?></span></div>
<div class="dashed"></div>
<div class="row bold"><span class="lbl">Gross Sales</span><span class="val">Rs.<?= number_format($total_sales, 2) ?></span></div>
<div class="row"><span class="lbl">Refund Amount</span><span class="val">Rs.<?= number_format($refund_total ?? 0, 2) ?></span></div>
<?php if (($total_disc ?? 0) > 0): ?>
<div class="row"><span class="lbl">(-) Discounts</span><span class="val">Rs.<?= number_format($total_disc, 2) ?></span></div>
<?php endif; ?>
<div class="row"><span class="lbl">GST Collected</span><span class="val">Rs.<?= number_format($total_gst, 2) ?></span></div>
<div class="dashed"></div>
<div class="row bold"><span class="lbl">Net Sales</span><span class="val">Rs.<?= number_format($total_sales - ($total_disc ?? 0), 2) ?></span></div>
<div class="dashed"></div>
<div class="row"><span class="lbl">Sales Receipts No(s)</span><span class="val"><?= ($first_receipt ?? '') . ' - ' . ($last_receipt ?? '') ?></span></div>
<div class="row"><span class="lbl">No. of Items Sold</span><span class="val"><?= $items_sold ?? 0 ?></span></div>
<div class="row"><span class="lbl">No. of Refunds</span><span class="val"><?= $refund_count ?? 0 ?></span></div>
<div class="row"><span class="lbl">Payment Mode Changes</span><span class="val"><?= $payment_mode_changes ?? 0 ?></span></div>

<!-- ═══ 2. CASHIER DECLARATION ═══ -->
<div class="section-title">2. CASHIER DECLARATION (BLIND)</div>
<div style="font-size:8px; text-align:center; margin-bottom:2px;">Declared by cashier before system reveal</div>
<?php
$act_total = $shift->actual_cash + $shift->actual_upi + $shift->actual_card
           + $shift->actual_bank + $shift->actual_wallet + $shift->actual_gift_voucher;
?>
<div class="row"><span class="lbl">Cash Declared</span><span class="val bold">Rs.<?= number_format($shift->actual_cash, 2) ?></span></div>
<div class="row"><span class="lbl">UPI Declared</span><span class="val">Rs.<?= number_format($shift->actual_upi, 2) ?></span></div>
<div class="row"><span class="lbl">Card Declared</span><span class="val">Rs.<?= number_format($shift->actual_card, 2) ?></span></div>
<div class="row"><span class="lbl">Bank/NEFT Declared</span><span class="val">Rs.<?= number_format($shift->actual_bank, 2) ?></span></div>
<div class="row"><span class="lbl">Wallet Declared</span><span class="val">Rs.<?= number_format($shift->actual_wallet, 2) ?></span></div>
<?php if ($shift->actual_gift_voucher > 0): ?>
<div class="row"><span class="lbl">Gift Voucher Decl.</span><span class="val">Rs.<?= number_format($shift->actual_gift_voucher, 2) ?></span></div>
<?php endif; ?>
<div class="dashed"></div>
<div class="row bold"><span class="lbl">TOTAL DECLARED</span><span class="val">Rs.<?= number_format($act_total, 2) ?></span></div>

<!-- ═══ 3. SYSTEM vs ACTUAL ═══ -->
<div class="section-title">3. SYSTEM vs ACTUAL COMPARISON</div>
<?php
$sys_cash_adj = !empty($cash_summary) ? $cash_summary['carry_forward'] : $shift->system_cash;
$compare = [
    ['Cash',      $sys_cash_adj,         $shift->actual_cash,   $shift->actual_cash - $sys_cash_adj],
    ['UPI',       $shift->system_upi,    $shift->actual_upi,    $shift->diff_upi    ?? ($shift->actual_upi    - $shift->system_upi)],
    ['Card',      $shift->system_card,   $shift->actual_card,   $shift->diff_card   ?? ($shift->actual_card   - $shift->system_card)],
    ['Bank/NEFT', $shift->system_bank,   $shift->actual_bank,   $shift->diff_bank   ?? ($shift->actual_bank   - $shift->system_bank)],
    ['Wallet',    $shift->system_wallet, $shift->actual_wallet, $shift->diff_wallet ?? ($shift->actual_wallet - $shift->system_wallet)],
];
if ($shift->actual_gift_voucher > 0 || $shift->system_gift_voucher > 0)
    $compare[] = ['Gift Vchr', $shift->system_gift_voucher, $shift->actual_gift_voucher, $shift->diff_gift_voucher ?? 0];
?>
<table style="width:100%; font-size:10px; border-collapse:collapse;">
    <thead>
        <tr style="border-bottom:1px solid #000;">
            <th style="text-align:left; padding:2px 0;">Mode</th>
            <th style="text-align:right; padding:2px 4px 2px 0;">System</th>
            <th style="text-align:right; padding:2px 4px 2px 0;">Actual</th>
            <th style="text-align:right; padding:2px 0;">Diff</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($compare as [$label, $sys, $act, $diff]):
        $diff = (float)$diff;
        $diffStr = $diff == 0 ? 'OK' : ($diff > 0 ? '+'.number_format(abs($diff),2) : '-'.number_format(abs($diff),2));
    ?>
        <tr>
            <td style="text-align:left; padding:1px 0;"><?= $label ?></td>
            <td style="text-align:right; padding:1px 4px 1px 0;"><?= number_format($sys, 2) ?></td>
            <td style="text-align:right; padding:1px 4px 1px 0;"><?= number_format($act, 2) ?></td>
            <td style="text-align:right; padding:1px 0; font-weight:<?= $diff != 0 ? 'bold' : 'normal' ?>"><?= $diffStr ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="dashed"></div>
<?php
$exp_drawer = $shift->opening_cash + $shift->system_cash;
if (!empty($cash_summary)) {
    $exp_drawer = $cash_summary['carry_forward'];
}
$cash_diff_actual = (float)$shift->actual_cash - $exp_drawer;
?>
<div class="row"><span class="lbl">Expected in Drawer</span><span class="val">Rs.<?= number_format($exp_drawer, 2) ?></span></div>
<div class="row bold"><span class="lbl">Cash Counted</span><span class="val">Rs.<?= number_format($shift->actual_cash, 2) ?></span></div>
<div class="dashed"></div>
<?php if ($cash_diff_actual < -0.01): ?>
<div class="center warn" style="font-size:11px;">*** CASH SHORT: Rs.<?= number_format(abs($cash_diff_actual), 2) ?> ***</div>
<?php elseif ($cash_diff_actual > 0.01): ?>
<div class="center warn" style="font-size:11px;">*** CASH EXCESS: Rs.<?= number_format($cash_diff_actual, 2) ?> ***</div>
<?php else: ?>
<div class="center bold" style="font-size:11px;">*** CASH MATCHED ***</div>
<?php endif; ?>

<!-- ═══ CREDIT BILLS (not included in cash) ═══ -->
<?php if (!empty($credit_bills)): ?>
<div class="section-title">CREDIT BILLS (NOT IN CASH)</div>
<table style="width:100%; font-size:10px; border-collapse:collapse;">
    <thead>
        <tr style="border-bottom:1px solid #000;">
            <th style="text-align:left; padding:2px 0;">Bill / Customer</th>
            <th style="text-align:right; padding:2px 4px 2px 0;">Bill Amt</th>
            <th style="text-align:right; padding:2px 0;">Credit</th>
        </tr>
    </thead>
    <tbody>
    <?php $cb_credit = 0; foreach ($credit_bills as $cb): $cb_credit += $cb->credit_amount; ?>
        <tr>
            <td style="text-align:left; padding:1px 0;"><?= htmlspecialchars($cb->invoice_no) ?><br><span style="font-size:9px;"><?= htmlspecialchars($cb->customer_name ?: 'Walk-in') ?></span></td>
            <td style="text-align:right; padding:1px 4px 1px 0; vertical-align:top;"><?= number_format($cb->total_amount, 2) ?></td>
            <td style="text-align:right; padding:1px 0; vertical-align:top; font-weight:bold;"><?= number_format($cb->credit_amount, 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="dashed"></div>
<div class="row bold"><span class="lbl">Total Credit (<?= count($credit_bills) ?> bills)</span><span class="val">Rs.<?= number_format($cb_credit, 2) ?></span></div>
<div class="dashed"></div>
<?php endif; ?>

<!-- ═══ 4. REFUNDS & RETURNS ═══ -->
<div class="section-title">4. REFUNDS &amp; RETURNS</div>
<?php if ($refund_count == 0 && $cash_refund_count == 0): ?>
<div class="center" style="font-size:9px;">No returns/refunds during this shift</div>
<?php else: ?>
<?php if ($refund_count > 0): ?>
<div class="sub-title">SALES RETURNS (Credit Notes)</div>
<div class="row"><span class="lbl">No. of Returns</span><span class="val bold"><?= $refund_count ?></span></div>
<div class="row"><span class="lbl">Return Value</span><span class="val bold">Rs.<?= number_format($refund_total, 2) ?></span></div>
<?php if ($refund_gst > 0): ?>
<div class="row"><span class="lbl">GST Reversed</span><span class="val">Rs.<?= number_format($refund_gst, 2) ?></span></div>
<?php endif; ?>
<div class="row bold"><span class="lbl">Net Sales after Ret.</span><span class="val">Rs.<?= number_format($total_sales - $refund_total, 2) ?></span></div>
<?php endif; ?>
<?php if ($cash_refund_count > 0): ?>
<div class="sub-title">CASH REFUNDS PAID OUT</div>
<div class="row"><span class="lbl">No. of Cash Refunds</span><span class="val bold"><?= $cash_refund_count ?></span></div>
<div class="row bold"><span class="lbl">Cash Refunded</span><span class="val">Rs.<?= number_format($cash_refund_total, 2) ?></span></div>
<div style="font-size:8px;text-align:right;font-style:italic;">(deducted from cash in till)</div>
<?php endif; ?>
<?php endif; ?>

<!-- ═══ 5. PRICE CHANGES ═══ -->
<div class="section-title">5. PRICE CHANGES / BILL EDITS</div>
<?php if (empty($price_changes)): ?>
<div class="center" style="font-size:9px;">No price changes during this shift</div>
<?php else: ?>
<div class="row bold" style="font-size:9px;border-bottom:1px solid #000;padding:1px 0;">
    <span style="flex:1.5">Invoice</span>
    <span style="width:38px;text-align:right">Old</span>
    <span style="width:38px;text-align:right">New</span>
    <span style="width:38px;text-align:right">Diff</span>
</div>
<?php foreach ($price_changes as $pc):
    $old = (float)$pc->old_total;
    $new = (float)$pc->new_total;
    $d   = $new - $old;
    $sign = $d >= 0 ? '+' : '-';
?>
<div style="font-size:8.5px; display:flex; padding:1px 0;">
    <span style="flex:1.5"><?= htmlspecialchars($pc->invoice_no) ?></span>
    <span style="width:38px;text-align:right"><?= number_format($old, 0) ?></span>
    <span style="width:38px;text-align:right"><?= number_format($new, 0) ?></span>
    <span style="width:38px;text-align:right;font-weight:bold;"><?= $sign.number_format(abs($d), 0) ?></span>
</div>
<div style="font-size:7.5px;padding-left:2px;color:#333;">
    By: <?= htmlspecialchars($pc->edited_by_name ?? '—') ?> | <?= date('H:i', strtotime($pc->edited_at)) ?>
    <?php if (!empty($pc->reason)): ?> | <?= htmlspecialchars(substr($pc->reason,0,25)) ?><?php endif; ?>
</div>
<?php endforeach; ?>
<div class="dashed"></div>
<div class="row bold" style="font-size:9px;"><span class="lbl">Total Edit Transactions</span><span class="val"><?= count($price_changes) ?></span></div>
<?php endif; ?>

<!-- ═══ CASH FLOW SUMMARY ═══ -->
<?php if (!empty($cash_summary)): ?>
<div class="section-title">CASH FLOW SUMMARY</div>
<div class="row"><span class="lbl">Opening Cash</span><span class="val">+ Rs.<?= number_format($cash_summary['opening_cash'], 2) ?></span></div>
<div class="row"><span class="lbl">Cash Sales</span><span class="val">+ Rs.<?= number_format($cash_summary['cash_sales'], 2) ?></span></div>
<?php if ($cash_summary['cash_received'] > 0): ?>
<div class="row"><span class="lbl">Cash Received</span><span class="val">+ Rs.<?= number_format($cash_summary['cash_received'], 2) ?></span></div>
<?php endif; ?>
<div class="row"><span class="lbl">Pay-In</span><span class="val">+ Rs.<?= number_format($cash_summary['pay_in'], 2) ?></span></div>
<?php if (!empty($cash_payins)): foreach ($cash_payins as $pi): ?>
<div class="row" style="font-size:9.5px;"><span class="lbl" style="padding-left:6px;">&rarr; <?= htmlspecialchars($pi->txn_no) ?> <?= htmlspecialchars($pi->category_name ?? '') ?></span><span class="val">Rs.<?= number_format($pi->amount, 2) ?></span></div>
<?php endforeach; endif; ?>
<div class="row"><span class="lbl">Cash Refunds</span><span class="val">- Rs.<?= number_format($cash_summary['cash_refunds'], 2) ?></span></div>
<div class="row"><span class="lbl bold">Pay-Out</span><span class="val bold">- Rs.<?= number_format($cash_summary['pay_out'], 2) ?></span></div>
<?php if (!empty($cash_payouts)): foreach ($cash_payouts as $po): ?>
<div class="row" style="font-size:9.5px;"><span class="lbl" style="padding-left:6px;">&rarr; <?= htmlspecialchars($po->txn_no) ?> <?= htmlspecialchars($po->category_name ?? '') ?></span><span class="val">Rs.<?= number_format($po->amount, 2) ?></span></div>
<?php endforeach; endif; ?>
<div class="dashed"></div>
<div class="row bold"><span class="lbl">Expected Cash</span><span class="val">Rs.<?= number_format($cash_summary['expected_cash'], 2) ?></span></div>
<div class="row bold"><span class="lbl">Cash Handover</span><span class="val">- Rs.<?= number_format($cash_summary['handovers'], 2) ?></span></div>
<?php if (!empty($cash_handovers)): foreach ($cash_handovers as $ho): ?>
<div class="row" style="font-size:9.5px;"><span class="lbl" style="padding-left:6px;">&rarr; <?= htmlspecialchars($ho->handover_no) ?> (<?= htmlspecialchars($ho->from_user_name ?? '') ?> &rarr; <?= htmlspecialchars($ho->to_user_name ?? '') ?>)</span><span class="val">Rs.<?= number_format($ho->handover_amount, 2) ?></span></div>
<?php endforeach; endif; ?>
<div class="solid"></div>
<div class="row bold"><span class="lbl">Expected Closing</span><span class="val">Rs.<?= number_format($cash_summary['carry_forward'], 2) ?></span></div>
<div class="dashed"></div>
<div class="row bold"><span class="lbl">Cash Counted</span><span class="val">Rs.<?= number_format($shift->actual_cash, 2) ?></span></div>
<?php
$cf_diff = (float)$shift->actual_cash - (float)$cash_summary['carry_forward'];
if (abs($cf_diff) >= 0.01):
    $cf_label = $cf_diff > 0 ? 'EXCESS' : 'SHORTAGE';
    $cf_sign  = $cf_diff > 0 ? '+' : '';
?>
<div class="row bold" style="font-size:10.5px;">
    <span class="lbl"><?= $cf_label ?></span>
    <span class="val"><?= $cf_sign ?>Rs.<?= number_format($cf_diff, 2) ?></span>
</div>
<?php endif; ?>
<div class="solid"></div>
<div class="row bold" style="font-size:12px; background:#eee; padding:2px 4px; margin:2px -4px;">
    <span class="lbl">NEXT DAY OPENING</span>
    <span class="val">Rs.<?= number_format($shift->actual_cash, 2) ?></span>
</div>
<?php endif; ?>

<!-- ═══ 6. DENOMINATION BREAKDOWN ═══ -->
<?php if (!empty($denom)): ?>
<div class="section-title">6. CASH DENOMINATION</div>
<?php
$denom_vals = [500,200,100,50,20,10,5,2,1];
$denom_total = 0;
foreach ($denom_vals as $v):
    $cnt = isset($denom[$v]) ? (int)$denom[$v] : 0;
    if ($cnt <= 0) continue;
    $sub = $v * $cnt;
    $denom_total += $sub;
?>
<div class="row" style="font-size:10.5px;">
    <span class="lbl">Rs.<?= $v ?> x <?= $cnt ?></span>
    <span class="val">Rs.<?= number_format($sub) ?></span>
</div>
<?php endforeach;
$coins = isset($denom['coins']) ? (float)$denom['coins'] : 0;
if ($coins > 0):
    $denom_total += $coins;
?>
<div class="row" style="font-size:10.5px;"><span class="lbl">Coins</span><span class="val">Rs.<?= number_format($coins, 2) ?></span></div>
<?php endif; ?>
<div class="dashed"></div>
<div class="row bold"><span class="lbl">Denom. Total</span><span class="val">Rs.<?= number_format($denom_total, 2) ?></span></div>
<?php endif; ?>

<!-- Closing Remarks -->
<?php if (!empty($shift->close_remarks)): ?>
<div class="dashed"></div>
<div style="font-size:9px;"><span class="bold">Remarks: </span><?= htmlspecialchars($shift->close_remarks) ?></div>
<?php endif; ?>

<!-- Signatures -->
<div style="margin-top:16px;">
<table width="100%" style="font-size:9px;">
    <tr>
        <td width="50%" style="text-align:center;padding-top:16px;border-top:1px dashed #000;">
            <?= htmlspecialchars($shift->cashier_name) ?><br>Cashier
        </td>
        <td width="50%" style="text-align:center;padding-top:16px;border-top:1px dashed #000;">
            Authorised By<br>Manager
        </td>
    </tr>
</table>
</div>

<div class="dashed" style="margin-top:12px;"></div>
<div class="center" style="font-size:8px;">** End of Shift Report — Shift #<?= $shift->id ?> **</div>
<div class="center" style="font-size:8px; margin-top:2px;"><?= date('d-M-Y H:i:s') ?></div>
<br>

<!-- Buttons hidden on print -->
<div class="no-print" style="text-align:center;margin-top:14px;">
    <button onclick="window.print()" style="padding:6px 20px;font-size:12px;cursor:pointer;background:#222;color:#fff;border:none;border-radius:4px;margin-right:6px;">
        🖨️ Print
    </button>
    <button onclick="window.close()" style="padding:6px 16px;font-size:12px;cursor:pointer;background:#888;color:#fff;border:none;border-radius:4px;">
        Close
    </button>
</div>

<script>
if (window.location.search.indexOf('autoprint=1') !== -1) {
    window.addEventListener('load', function() { window.print(); });
}
</script>
</body>
</html>
