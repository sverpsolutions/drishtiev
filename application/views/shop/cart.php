<?php $csrf = '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">'; ?>
<div class="wrap">
  <nav class="crumbs"><a href="<?= base_url() ?>">Home</a> / Shopping Bag</nav>
  <h1>Shopping Bag</h1>
  <?php if (!$pr['lines'] && !$pr['errors']): ?>
  <div class="empty card" style="margin-bottom:48px">
    <div class="mono">✦</div><h2>Your bag is empty</h2>
    <p class="muted">Discover our latest kurtis, palazzos and more.</p>
    <a class="btn btn-primary" href="<?= site_url('shop') ?>">Start shopping</a>
  </div>
  <?php else: ?>
  <div class="two-col">
    <div class="card" style="padding:4px 20px">
      <?php foreach ($this->session->userdata('web_cart') ?: [] as $key => $c): $l = $pr['lines'][$key] ?? null; $err = $pr['errors'][$key] ?? ''; ?>
      <div class="cart-line">
        <div class="thumb"><?php $img = $l ? ($this->ws->product_images($l->product)[0]['path'] ?? null) : null; if ($img): ?><img src="<?= web_img($img) ?>" alt=""><?php else: ?><div class="ph"><span class="mono" style="width:44px;height:44px;font-size:20px">SU</span></div><?php endif; ?></div>
        <div>
          <?php if ($l): ?>
          <a href="<?= product_url($l->product) ?>" style="color:var(--ink);font-weight:500"><?= html_escape($l->name) ?></a>
          <div class="meta"><?= $l->size ? 'Size: ' . html_escape($l->size) . ' · Colour: ' . html_escape($l->colour) . ' · ' : '' ?><?= inr($l->price) ?> each</div>
          <?php else: ?><strong>Unavailable item</strong><?php endif; ?>
          <?php if ($err): ?><div class="err"><?= html_escape($err) ?></div><?php endif; ?>
          <form method="post" action="<?= site_url('cart/update') ?>">
            <?= $csrf ?><input type="hidden" name="key" value="<?= html_escape($key) ?>">
            <?php if ($l): ?>
            <label class="sr-only" for="q<?= md5($key) ?>">Quantity</label>
            <select id="q<?= md5($key) ?>" name="qty" data-autosubmit>
              <?php for ($i = 1; $i <= max(1, min(20, max($l->avail, $l->qty))); $i++): ?><option value="<?= $i ?>" <?= $i == $l->qty ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?>
            </select>
            <?php endif; ?>
            <button class="linkbtn" name="qty" value="0" type="submit">Remove</button>
          </form>
        </div>
        <div class="lt" style="text-align:right"><?php if ($l): ?><strong><?= inr($l->gross) ?></strong><?php if ($l->mrp > $l->price): ?><div class="small muted" style="text-decoration:line-through"><?= inr($l->mrp * $l->qty) ?></div><?php endif; ?><?php endif; ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <aside class="card summary">
      <h3>Order Summary</h3>
      <?php $coupon = $this->session->userdata('web_coupon'); ?>
      <?php if ($pr['coupon']): ?>
      <form method="post" action="<?= site_url('cart/coupon') ?>" class="alert alert-ok" style="display:flex;justify-content:space-between;align-items:center">
        <?= $csrf ?><span>Coupon <b><?= html_escape($pr['coupon']->code) ?></b> applied</span><button class="linkbtn" name="remove" value="1">Remove</button>
      </form>
      <?php else: ?>
      <form method="post" action="<?= site_url('cart/coupon') ?>" class="coupon-form">
        <?= $csrf ?><label for="cc" class="sr-only">Coupon code</label>
        <input id="cc" class="input" name="code" placeholder="Coupon code" maxlength="30" value="<?= html_escape($coupon ?: '') ?>">
        <button class="btn btn-outline btn-sm" type="submit">Apply</button>
      </form>
      <?php endif; ?>
      <div class="row"><span>Total MRP</span><span><?= inr($pr['mrp_total'], 2) ?></span></div>
      <?php if ($pr['mrp_total'] > $pr['subtotal']): ?><div class="row save"><span>Discount on MRP</span><span>−<?= inr($pr['mrp_total'] - $pr['subtotal'], 2) ?></span></div><?php endif; ?>
      <?php if ($pr['discount'] > 0): ?><div class="row save"><span>Coupon discount</span><span>−<?= inr($pr['discount'], 2) ?></span></div><?php endif; ?>
      <div class="row"><span>Shipping</span><span><?= $pr['shipping'] > 0 ? inr($pr['shipping'], 2) : 'Free' ?></span></div>
      <div class="row total"><span>Total</span><span><?= inr($pr['grand_total'], 2) ?></span></div>
      <p class="small muted" style="margin:4px 0 12px">Inclusive of GST. Shipping is final at checkout.</p>
      <?php if ($pr['free_shipping_gap'] > 0): ?><div class="alert alert-info">Add <?= inr($pr['free_shipping_gap']) ?> more for free shipping.</div><?php endif; ?>
      <?php if (!empty($pr['errors']['_min'])): ?><div class="alert alert-error"><?= html_escape($pr['errors']['_min']) ?></div><?php endif; ?>
      <?php if ($pr['errors']): ?>
        <button class="btn btn-primary btn-block" disabled>Fix the items above to continue</button>
      <?php else: ?>
        <a class="btn btn-primary btn-block" href="<?= site_url('checkout') ?>">Proceed to Checkout</a>
      <?php endif; ?>
      <a class="btn btn-block" href="<?= site_url('shop') ?>" style="margin-top:6px">Continue shopping</a>
    </aside>
  </div>
  <?php endif; ?>
</div>
