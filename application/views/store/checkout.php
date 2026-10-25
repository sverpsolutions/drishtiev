<div class="st-page">
  <div class="st-section-head"><div class="st-section-title">Checkout</div></div>

  <div class="st-card">
    <div class="st-card-header">Order Summary (<?= count($items) ?> item<?= count($items) == 1 ? '' : 's' ?>)</div>
    <div>
      <?php foreach ($items as $it): ?>
      <div class="st-cart-row">
        <div class="st-cart-thumb">
          <?php if (!empty($it->product->image1)): ?>
            <img src="<?= base_url('assets/uploads/products/' . $it->product->image1) ?>" alt="">
          <?php else: ?><div class="st-pcard-placeholder">🛒</div><?php endif; ?>
        </div>
        <div class="st-cart-info">
          <div class="st-cart-name"><?= htmlspecialchars($it->product->name) ?></div>
          <div class="st-cart-unit">Qty: <?= (int)$it->qty ?> × ₹<?= number_format($it->price, 2) ?></div>
        </div>
        <div class="st-cart-price">₹<?= number_format($it->total, 2) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if (!$this->session->userdata('store_customer_id')): ?>
  <div class="st-card">
    <div class="st-card-body" style="text-align:center;">
      <div style="font-size:.88rem;font-weight:700;margin-bottom:10px;">Login to place your order</div>
      <a href="<?= base_url('store/login?redirect=' . urlencode('store/checkout')) ?>" class="st-btn-primary st-btn-block">Login / Register</a>
    </div>
  </div>
  <?php else: ?>
  <form method="post" action="<?= base_url('store/checkout') ?>" id="stCheckoutForm">
    <div class="st-card">
      <div class="st-card-header">Delivery Details</div>
      <div class="st-card-body">
        <div class="st-field">
          <label>Delivery Slot</label>
          <select name="delivery_slot">
            <option value="ASAP (30-60 mins)">ASAP (30–60 mins)</option>
            <option value="Today Evening">Today Evening</option>
            <option value="Tomorrow Morning">Tomorrow Morning</option>
          </select>
        </div>
        <div class="st-field">
          <label>Order Notes (optional)</label>
          <textarea name="notes" rows="2" placeholder="E.g. landmark, delivery instructions…"></textarea>
        </div>
        <input type="hidden" name="delivery_charge" value="0">
        <input type="hidden" name="delivery_distance" value="0">
      </div>
    </div>

    <div class="st-card">
      <div class="st-card-header">Payment</div>
      <div class="st-card-body">
        <div style="display:flex;flex-direction:column;gap:8px;">
          <label class="st-pay-option"><input type="radio" name="pay_app" value="upi" checked> <i class="fa fa-qrcode"></i> UPI (scan QR on next screen)</label>
          <label class="st-pay-option"><input type="radio" name="pay_app" value="gpay"> <i class="fa fa-mobile-alt"></i> Google Pay</label>
          <label class="st-pay-option"><input type="radio" name="pay_app" value="phonepe"> <i class="fa fa-mobile-alt"></i> PhonePe</label>
          <label class="st-pay-option"><input type="radio" name="pay_app" value="cod"> <i class="fa fa-money-bill-wave"></i> Cash on Delivery</label>
        </div>
      </div>
    </div>

    <div class="st-card">
      <div class="st-card-body">
        <div class="st-summary-row"><span>Subtotal</span><span>₹<?= number_format($subtotal, 2) ?></span></div>
        <div class="st-summary-row"><span>Delivery</span><span>FREE</span></div>
        <div class="st-summary-row total"><span>To Pay</span><span>₹<?= number_format($subtotal, 2) ?></span></div>
      </div>
    </div>

    <button type="submit" class="st-btn-primary st-btn-block" style="margin-top:6px;">Place Order — ₹<?= number_format($subtotal, 2) ?></button>
  </form>
  <?php endif; ?>
</div>
<style>
.st-pay-option { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1.5px solid #eee; border-radius: 10px; font-size: .85rem; font-weight: 600; cursor: pointer; }
.st-pay-option input { accent-color: var(--st-primary); }
</style>
