<div class="st-page">

  <div style="text-align:center;padding:20px 0;">
    <div style="width:70px;height:70px;border-radius:50%;background:var(--st-primary-xlight);color:var(--st-primary);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 12px;">✓</div>
    <div style="font-family:'Poppins',sans-serif;font-size:1.15rem;font-weight:800;">Order Placed!</div>
    <div style="font-size:.82rem;color:#999;margin-top:4px;">Order <strong><?= htmlspecialchars($order->request_no) ?></strong> — we'll confirm it shortly.</div>
  </div>

  <div class="st-card">
    <div class="st-card-header">Order Summary</div>
    <div>
      <?php foreach ($items as $item): $isp = (float)((isset($item->selling_price) && $item->selling_price > 0) ? $item->selling_price : $item->mrp); ?>
      <div class="st-cart-row">
        <div class="st-cart-info">
          <div class="st-cart-name"><?= htmlspecialchars($item->product_name) ?></div>
          <div class="st-cart-unit">Qty: <?= (int)$item->qty ?> × ₹<?= number_format($isp, 2) ?></div>
        </div>
        <div class="st-cart-price">₹<?= number_format($isp * $item->qty, 2) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="st-card-body">
      <div class="st-summary-row"><span>Subtotal</span><span>₹<?= number_format($subtotal, 2) ?></span></div>
      <?php if ($delivery_charge > 0): ?><div class="st-summary-row"><span>Delivery</span><span>₹<?= number_format($delivery_charge, 2) ?></span></div><?php endif; ?>
      <div class="st-summary-row total"><span>Total</span><span>₹<?= number_format($total, 2) ?></span></div>
    </div>
  </div>

  <?php
  $upi_id   = $company['company_upi_id']   ?? '';
  $upi_name = $company['company_upi_name'] ?? $company['company_name'] ?? 'Store';
  if (!empty($upi_id) && $pay_app !== 'cod'):
  ?>
  <div class="st-card">
    <div class="st-card-body" style="text-align:center;">
      <div style="font-size:.9rem;font-weight:700;margin-bottom:6px;">💳 Pay ₹<?= number_format($total, 2) ?> via UPI</div>
      <p style="font-size:.72rem;color:#999;margin:0 0 12px;">Tap below to pay from your UPI app, or scan the QR.</p>
      <button type="button" class="st-btn-primary st-btn-block" onclick="stLaunchUpi()" style="margin-bottom:12px;">💳 Pay ₹<?= number_format($total, 2) ?> Now</button>
      <div style="display:flex;justify-content:center;margin-bottom:10px;">
        <div id="stConfirmationUpiQr" style="background:#fff;padding:6px;border:1.5px solid var(--st-accent);border-radius:10px;"></div>
      </div>
      <div style="font-size:.8rem;font-weight:700;"><?= htmlspecialchars($upi_name) ?></div>
      <code style="font-size:.7rem;color:#999;word-break:break-all;"><?= htmlspecialchars($upi_id) ?></code>
    </div>
  </div>
  <?php elseif ($pay_app === 'cod'): ?>
  <div class="st-card"><div class="st-card-body" style="text-align:center;font-size:.85rem;font-weight:700;color:var(--st-primary);">💵 Pay ₹<?= number_format($total, 2) ?> in cash on delivery</div></div>
  <?php endif; ?>

  <div style="text-align:center;margin:16px 0;">
    <a href="<?= base_url('store') ?>" class="st-btn-outline">🛍️ Continue Shopping</a>
  </div>
</div>

<script src="<?= base_url('assets/js/qrcode.min.js') ?>"></script>
<script>
var ST_UPI_ID   = "<?= addslashes($upi_id ?? '') ?>";
var ST_UPI_NAME = "<?= addslashes($upi_name ?? 'Store') ?>";
var ST_UPI_AMT  = "<?= number_format($total, 2, '.', '') ?>";
var ST_UPI_REF  = "<?= addslashes($order->request_no) ?>";
var ST_PAY_APP  = "<?= addslashes($pay_app ?? '') ?>";

function stUpiParams() {
  return "pa=" + encodeURIComponent(ST_UPI_ID) +
         "&pn=" + encodeURIComponent(ST_UPI_NAME) +
         "&am=" + ST_UPI_AMT + "&cu=INR" +
         "&tn=" + encodeURIComponent("Order " + ST_UPI_REF) +
         "&tr=" + encodeURIComponent(ST_UPI_REF);
}
function stUpiLink(app) {
  var p = stUpiParams();
  switch (app) {
    case 'gpay':    return "tez://upi/pay?" + p;
    case 'phonepe': return "phonepe://pay?" + p;
    case 'paytm':   return "paytmmp://pay?" + p;
    default:        return "upi://pay?" + p;
  }
}
function stLaunchUpi(app) {
  if (!ST_UPI_ID) return;
  window.location.href = stUpiLink(app || ST_PAY_APP || 'upi');
}
window.addEventListener('DOMContentLoaded', function () {
  if (ST_UPI_ID) {
    var el = document.getElementById('stConfirmationUpiQr');
    if (el) new QRCode(el, { text: stUpiLink('upi'), width: 120, height: 120 });
    if (ST_PAY_APP && ST_PAY_APP !== 'cod' && ST_PAY_APP !== 'upi') {
      setTimeout(function () { stLaunchUpi(ST_PAY_APP); }, 600);
    }
  }
});
</script>
