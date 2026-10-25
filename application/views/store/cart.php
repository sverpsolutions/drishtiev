<div class="st-page">
  <div class="st-section-head"><div class="st-section-title">My Cart</div></div>

  <div class="st-card">
    <div id="stCartList">
      <?php if (empty($items)): ?>
      <div class="st-empty"><i class="fa fa-shopping-bag"></i><div>Your cart is empty</div></div>
      <?php else: ?>
        <?php foreach ($items as $it): ?>
        <div class="st-cart-row" data-pid="<?= $it->product->id ?>">
          <div class="st-cart-thumb">
            <?php if (!empty($it->product->image1)): ?>
              <img src="<?= base_url('assets/uploads/products/' . $it->product->image1) ?>" alt="">
            <?php else: ?><div class="st-pcard-placeholder">🛒</div><?php endif; ?>
          </div>
          <div class="st-cart-info">
            <div class="st-cart-name"><?= htmlspecialchars($it->product->name) ?></div>
            <div class="st-cart-unit"><?= htmlspecialchars($it->product->unit ?? '') ?></div>
            <div class="st-cart-price">₹<?= number_format($it->price, 2) ?></div>
          </div>
          <div class="st-pcard-stepper" style="width:96px;">
            <button type="button" data-action="dec">−</button>
            <span class="st-qty-val"><?= (int)$it->qty ?></span>
            <button type="button" data-action="inc">+</button>
          </div>
          <button type="button" data-action="remove" class="st-icon-btn" style="color:#c62828;"><i class="fa fa-trash"></i></button>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($items)): ?>
  <div class="st-card">
    <div class="st-card-body">
      <div class="st-summary-row"><span>Subtotal</span><span id="stCartSubtotal">₹<?= number_format($subtotal, 2) ?></span></div>
      <div style="font-size:.72rem;color:#999;">Delivery charges calculated at checkout.</div>
    </div>
  </div>
  <?php endif; ?>
</div>

<div class="st-sticky-checkout-bar" id="stCheckoutBar" style="<?= empty($items) ? 'display:none;' : '' ?>">
  <div>
    <div style="font-size:.68rem;color:#999;">Total</div>
    <div style="font-size:1.05rem;font-weight:800;" id="stCheckoutTotal">₹<?= number_format($subtotal, 2) ?></div>
  </div>
  <a href="<?= base_url('store/checkout') ?>" class="st-btn-primary">Proceed to Checkout →</a>
</div>

<script src="<?= base_url('assets/js/store-cart.js') ?>"></script>
