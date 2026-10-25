<?php
$csrf = '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">';
$e = function ($k) use ($f) { return html_escape($f[$k] ?? ''); };
$upi = $company['web_upi_id'] ?? '';
$blocked = (bool)$pr['errors'] || !$methods;
?>
<div class="wrap">
  <nav class="crumbs"><a href="<?= site_url('cart') ?>">Bag</a> / Checkout</nav>
  <h1>Checkout</h1>
  <div class="steps"><span>1. Bag</span>›<b>2. Address &amp; Payment</b>›<span>3. Confirmation</span></div>
  <?php if ($errors): ?><div class="alert alert-error" role="alert"><?php foreach ($errors as $er) echo html_escape($er) . '<br>'; ?></div><?php endif; ?>
  <?php foreach ($pr['errors'] as $k => $er): ?><div class="alert alert-error"><?= html_escape(($pr['lines'][$k]->name ?? '') . ($k !== '_min' ? ': ' : '') . $er) ?> <a href="<?= site_url('cart') ?>">Update bag</a></div><?php endforeach; ?>
  <?php if (!$methods): ?><div class="alert alert-error">Online ordering is temporarily unavailable — no payment method is enabled. Please contact the store.</div><?php endif; ?>
  <?php if (!$customer): ?><div class="alert alert-info">Checking out as a guest. <a href="<?= site_url('account/login?next=checkout') ?>">Log in</a> to see your past orders in one place.</div><?php endif; ?>

  <form class="two-col" method="post" action="<?= site_url('checkout') ?>" data-once novalidate>
    <?= $csrf ?><input type="hidden" name="checkout_key" value="<?= $checkout_key ?>">
    <div>
      <div class="card" style="margin-bottom:16px">
        <h3>Contact</h3>
        <div class="grid2">
          <div class="field"><label for="name">Full name *</label><input class="input" id="name" name="name" required maxlength="100" autocomplete="name" value="<?= $e('name') ?>"></div>
          <div class="field"><label for="phone">Mobile number *</label><input class="input" id="phone" name="phone" required inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10" autocomplete="tel-national" value="<?= $e('phone') ?>"></div>
        </div>
        <div class="field"><label for="email">Email (for order updates)</label><input class="input" id="email" name="email" type="email" maxlength="120" autocomplete="email" value="<?= $e('email') ?>"></div>
      </div>
      <div class="card" style="margin-bottom:16px">
        <h3>Delivery address</h3>
        <div class="field"><label for="address_line">House no., building, street, area *</label><input class="input" id="address_line" name="address_line" required maxlength="255" autocomplete="street-address" value="<?= $e('address_line') ?>"></div>
        <div class="field"><label for="landmark">Landmark</label><input class="input" id="landmark" name="landmark" maxlength="120" value="<?= $e('landmark') ?>"></div>
        <div class="grid2">
          <div class="field"><label for="city">City *</label><input class="input" id="city" name="city" required maxlength="80" autocomplete="address-level2" value="<?= $e('city') ?>"></div>
          <div class="field"><label for="pincode">PIN code *</label><input class="input" id="pincode" name="pincode" required inputmode="numeric" pattern="[1-9][0-9]{5}" maxlength="6" autocomplete="postal-code" value="<?= $e('pincode') ?>"></div>
        </div>
        <div class="field"><label for="state_code">State *</label>
          <select class="input" id="state_code" name="state_code" required>
            <?php foreach ($states as $code => $name): ?><option value="<?= $code ?>" <?= $f['state_code'] === (string)$code ? 'selected' : '' ?>><?= html_escape($name) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label for="notes">Order notes (optional)</label><textarea class="input" id="notes" name="notes" rows="2" maxlength="500"><?= $e('notes') ?></textarea></div>
      </div>
      <div class="card">
        <h3>Payment</h3>
        <?php foreach ($methods as $k => $lbl): ?>
        <label class="pay-opt"><input type="radio" name="payment_method" value="<?= $k ?>" <?= $f['payment_method'] === $k ? 'checked' : '' ?>>
          <span><strong><?= html_escape($lbl) ?></strong><br><span class="small muted"><?php
            if ($k === 'cod') echo 'Pay in cash when your order is delivered.';
            elseif ($k === 'upi') echo 'Pay ' . inr($pr['grand_total'], 2) . ' to UPI ID <b>' . html_escape($upi) . '</b>. The store confirms your order once the payment is verified.';
            else echo 'You will be redirected to a secure Razorpay payment page.';
          ?></span>
          <?php if ($k === 'upi'): ?><span style="display:block;margin-top:8px"><label for="payment_ref" class="small">UPI transaction ID / UTR (optional, speeds up verification)</label><input class="input" id="payment_ref" name="payment_ref" maxlength="30" value="<?= $e('payment_ref') ?>"></span><?php endif; ?>
          </span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <aside class="card summary">
      <h3>Your order (<?= (int)$pr['count'] ?> item<?= $pr['count'] == 1 ? '' : 's' ?>)</h3>
      <?php foreach ($pr['lines'] as $l): ?>
      <div class="row" style="align-items:flex-start;gap:10px"><span><?= html_escape($l->name) ?><?= $l->size ? '<br><span class="small muted">' . html_escape($l->size . ' / ' . $l->colour) . '</span>' : '' ?> <span class="small muted">× <?= $l->qty ?></span></span><span><?= inr($l->gross, 2) ?></span></div>
      <?php endforeach; ?>
      <?php if ($pr['coupon']): ?>
      <div class="row save"><span>Coupon <?= html_escape($pr['coupon']->code) ?></span><span>−<?= inr($pr['discount'], 2) ?></span></div>
      <?php elseif (!empty($f['coupon']) && $pr['coupon_error']): ?>
      <div class="alert alert-error small"><?= html_escape($pr['coupon_error']) ?></div>
      <?php endif; ?>
      <div class="row"><span>Shipping</span><span><?= $pr['shipping'] > 0 ? inr($pr['shipping'], 2) : 'Free' ?></span></div>
      <div class="row total"><span>Total payable</span><span><?= inr($pr['grand_total'], 2) ?></span></div>
      <details class="small muted" style="margin:6px 0 14px"><summary>Tax breakup (included)</summary>
        <div class="row"><span>Taxable value</span><span><?= inr($pr['taxable_total'], 2) ?></span></div>
        <?php if ($pr['interstate']): ?><div class="row"><span>IGST</span><span><?= inr($pr['igst'], 2) ?></span></div>
        <?php else: ?><div class="row"><span>CGST</span><span><?= inr($pr['cgst'], 2) ?></span></div><div class="row"><span>SGST</span><span><?= inr($pr['sgst'], 2) ?></span></div><?php endif; ?>
        <p style="margin:4px 0 0">Tax is worked out for your delivery state — changing the state updates it when you place the order.</p>
      </details>
      <button class="btn btn-primary btn-block" type="submit" data-loading="Placing order…" <?= $blocked ? 'disabled' : '' ?>>Place Order · <?= inr($pr['grand_total'], 2) ?></button>
      <p class="small muted" style="margin:10px 0 0">By placing the order you agree to our <a href="<?= site_url('page/terms') ?>">terms</a> and <a href="<?= site_url('page/return-policy') ?>">return policy</a>.</p>
    </aside>
  </form>
</div>
