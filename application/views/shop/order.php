<?php $can_cancel = $can_cancel ?? false; ?>
<div class="wrap" style="padding-bottom:48px">
  <?php if ($just_placed): ?>
  <div class="success-hero">
    <div class="tick" aria-hidden="true">✓</div>
    <h1><?= $o->order_status === 'pending_payment' ? 'Order reserved — payment pending' : 'Thank you! Your order is placed.' ?></h1>
    <p>Order number <strong><?= html_escape($o->order_no) ?></strong>. Please save it to track your order.</p>
    <?php if ($o->payment_method === 'upi' && $o->payment_status === 'awaiting_verification'): ?>
      <div class="alert alert-info" style="max-width:560px;margin:0 auto 12px">Please pay <strong><?= inr($o->grand_total, 2) ?></strong> to UPI ID <strong><?= html_escape($company['web_upi_id'] ?? '') ?></strong> mentioning <strong><?= html_escape($o->order_no) ?></strong>. We confirm the order once the payment is verified.</div>
    <?php elseif ($o->order_status === 'pending_payment'): ?>
      <a class="btn btn-primary" href="<?= site_url('checkout/pay/' . $o->order_no) ?>">Complete payment</a>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <nav class="crumbs"><a href="<?= base_url() ?>">Home</a> / <?= $customer ? '<a href="' . site_url('account') . '">My account</a> / ' : '' ?>Order</nav>
  <h1>Order <?= html_escape($o->order_no) ?></h1>
  <?php endif; ?>

  <div class="two-col" style="margin-top:16px">
    <div class="card">
      <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:8px">
        <span class="muted small">Placed <?= date('d M Y, h:i A', strtotime($o->created_at)) ?></span>
        <span><?= order_badge($o->order_status) ?> <?= order_badge($o->payment_status) ?></span>
      </div>
      <table class="tbl">
        <thead><tr><th>Item</th><th class="r">Qty</th><th class="r">Amount</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr><td><?= html_escape($it->product_name) ?><?php if ($it->size_name): ?><div class="small muted">Size <?= html_escape($it->size_name) ?> · <?= html_escape($it->colour_name) ?></div><?php endif; ?><?php if ($it->returned_qty): ?><div class="small" style="color:var(--danger)">Returned: <?= (int)$it->returned_qty ?></div><?php endif; ?></td>
          <td class="r"><?= (int)$it->qty ?></td><td class="r"><?= inr($it->line_total, 2) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ($o->courier || $o->tracking_no): ?>
      <div class="alert alert-info" style="margin-top:16px">Shipped via <strong><?= html_escape($o->courier) ?></strong><?= $o->tracking_no ? ' · Tracking no. <strong>' . html_escape($o->tracking_no) . '</strong>' : '' ?>
        <?php if ($o->tracking_url && preg_match('#^https?://#', $o->tracking_url)): ?> · <a href="<?= html_escape($o->tracking_url) ?>" target="_blank" rel="noopener">Track shipment</a><?php endif; ?></div>
      <?php endif; ?>
      <?php if ($history): ?>
      <h3 style="margin-top:20px">Order updates</h3>
      <ul class="timeline">
        <?php foreach (array_reverse($history) as $h): ?>
        <li><strong><?= html_escape($h->to_status ? Webstore_model::status_label($h->to_status) : ucfirst($h->action)) ?></strong> <span class="small muted"><?= date('d M Y, h:i A', strtotime($h->created_at)) ?></span>
          <?php if (in_array($h->to_status, ['cancelled', 'rejected'], true) && $o->status_reason): ?><div class="small muted"><?= html_escape($o->status_reason) ?></div><?php endif; ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <?php if ($can_cancel): ?>
      <form method="post" action="<?= site_url('account/cancel/' . $o->order_no) ?>" style="margin-top:16px" onsubmit="return confirm('Cancel this order?')">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
        <label for="reason">Cancel this order</label>
        <div style="display:flex;gap:8px"><input class="input" id="reason" name="reason" maxlength="200" placeholder="Reason (optional)"><button class="btn btn-outline btn-sm" type="submit">Cancel order</button></div>
      </form>
      <?php endif; ?>
    </div>
    <aside class="card summary">
      <h3>Summary</h3>
      <div class="row"><span>Items</span><span><?= inr($o->subtotal, 2) ?></span></div>
      <?php if ($o->discount > 0): ?><div class="row save"><span>Coupon <?= html_escape($o->coupon_code) ?></span><span>−<?= inr($o->discount, 2) ?></span></div><?php endif; ?>
      <div class="row"><span>Shipping</span><span><?= $o->shipping > 0 ? inr($o->shipping, 2) : 'Free' ?></span></div>
      <div class="row total"><span>Total</span><span><?= inr($o->grand_total, 2) ?></span></div>
      <div class="row small muted"><span>Payment</span><span><?= html_escape(['cod' => 'Cash on Delivery', 'upi' => 'UPI', 'razorpay' => 'Online (Razorpay)'][$o->payment_method]) ?></span></div>
      <h3 style="margin-top:16px">Delivering to</h3>
      <p style="margin:0"><?= html_escape($o->customer_name) ?><br><?= html_escape($o->address_line) ?><?= $o->landmark ? '<br>' . html_escape($o->landmark) : '' ?><br><?= html_escape($o->city . ', ' . $o->state . ' – ' . $o->pincode) ?><br>Mobile: <?= html_escape($o->phone) ?></p>
      <a class="btn btn-outline btn-block" style="margin-top:16px" href="<?= site_url('shop') ?>">Continue shopping</a>
    </aside>
  </div>
</div>
