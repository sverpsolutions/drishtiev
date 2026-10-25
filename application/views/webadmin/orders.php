<?php
$qs = $this->input->get(null, true) ?: [];
$link = function ($chg) use ($qs) { $q = array_merge($qs, $chg); unset($q['export']); return site_url('webadmin/orders') . '?' . http_build_query(array_filter($q, 'strlen')); };
$tabs = ['' => 'All', 'today' => 'Today', 'new' => 'New', 'pending_payment' => 'Pending payment', 'open' => 'Open', 'confirmed' => 'Confirmed', 'processing' => 'Processing',
         'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'rejected' => 'Rejected', 'returned' => 'Returned'];
?>
<div class="tabs">
  <?php foreach ($tabs as $k => $lbl): ?><a class="<?= $f['status'] === $k ? 'on' : '' ?>" href="<?= $link(['status' => $k, 'page' => '']) ?>"><?= $lbl ?><?php if ($k !== ''): ?><span class="n"><?= (int)($counts[$k] ?? 0) ?></span><?php endif; ?></a><?php endforeach; ?>
</div>
<form class="card row" method="get" action="<?= site_url('webadmin/orders') ?>">
  <input type="hidden" name="status" value="<?= html_escape($f['status']) ?>">
  <div><label for="q">Search order no / mobile / name</label><input id="q" name="q" value="<?= html_escape($f['q']) ?>"></div>
  <div><label for="from">From</label><input id="from" type="date" name="from" value="<?= html_escape($f['from']) ?>"></div>
  <div><label for="to">To</label><input id="to" type="date" name="to" value="<?= html_escape($f['to']) ?>"></div>
  <div><label for="payment">Payment method</label><select id="payment" name="payment"><option value="">All</option><?php foreach (['cod' => 'COD', 'upi' => 'UPI', 'razorpay' => 'Razorpay'] as $k => $v): ?><option value="<?= $k ?>" <?= $f['payment'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
  <div><label for="pstatus">Payment status</label><select id="pstatus" name="pstatus"><option value="">All</option><?php foreach (['pending', 'awaiting_verification', 'paid', 'failed', 'refunded', 'partially_refunded'] as $k): ?><option value="<?= $k ?>" <?= $f['pstatus'] === $k ? 'selected' : '' ?>><?= Webstore_model::status_label($k) ?></option><?php endforeach; ?></select></div>
  <div style="flex:0 0 auto;display:flex;gap:6px"><button class="btn btn-primary" type="submit">Filter</button><a class="btn" href="<?= $link(['export' => 1]) . '&export=1' ?>">Export CSV</a></div>
</form>
<div class="card">
  <div class="muted small" style="margin-bottom:8px"><?= number_format($total) ?> order(s)</div>
  <?php if (!$rows): ?><p class="muted">No orders match these filters.</p><?php else: ?>
  <div class="tbl-wrap"><table class="tbl">
    <tr><th>Order</th><th>Date</th><th>Customer</th><th>Mobile</th><th>City</th><th>Payment</th><th>Status</th><th class="r">Total</th></tr>
    <?php foreach ($rows as $o): ?>
    <tr>
      <td class="nowrap"><a href="<?= site_url('webadmin/order/' . $o->id) ?>"><strong><?= html_escape($o->order_no) ?></strong></a></td>
      <td class="nowrap"><?= date('d M Y, h:i A', strtotime($o->created_at)) ?></td>
      <td><?= html_escape($o->customer_name) ?></td>
      <td class="nowrap"><?= html_escape($o->phone) ?></td>
      <td><?= html_escape($o->city) ?></td>
      <td class="nowrap"><?= strtoupper($o->payment_method) ?> <?= order_badge($o->payment_status) ?></td>
      <td><?= order_badge($o->order_status) ?></td>
      <td class="r nowrap"><?= inr($o->grand_total, 2) ?></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
  <?php if ($pages > 1): ?><div class="pager"><?php for ($i = 1; $i <= $pages; $i++): ?><?php if ($i == $f['page']): ?><span><?= $i ?></span><?php else: ?><a href="<?= $link(['page' => $i]) ?>"><?= $i ?></a><?php endif; ?><?php endfor; ?></div><?php endif; ?>
  <?php endif; ?>
</div>
