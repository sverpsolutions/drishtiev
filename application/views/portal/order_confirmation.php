<?php // FILE: application/views/portal/order_confirmation.php — Sandal House ?>
<style>
:root {
<?php include_once APPPATH.'views/portal/_theme.php'; echo portal_theme_vars($company ?? null); ?>
}
.bb-confirm-wrap { max-width: 650px; margin: 0 auto; padding: 12px; }

/* Animated Success Icon */
@keyframes successPop {
  0%   { transform: scale(0); opacity: 0; }
  70%  { transform: scale(1.15); }
  100% { transform: scale(1);   opacity: 1; }
}
@keyframes confettiDrop {
  0%   { transform: translateY(-20px) rotate(0deg); opacity: 1; }
  100% { transform: translateY(60px)  rotate(360deg); opacity: 0; }
}
.bb-success-hero {
  background: linear-gradient(160deg, var(--primary-dark) 0%, var(--primary) 60%, var(--primary-light) 100%);
  border-radius: 18px; padding: 28px 20px;
  text-align: center; margin-bottom: 16px;
  position: relative; overflow: hidden;
}
.bb-success-icon {
  width: 72px; height: 72px; border-radius: 50%;
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; margin: 0 auto 14px;
  box-shadow: 0 8px 24px rgba(249,168,37,0.4);
  animation: successPop 0.6s cubic-bezier(0.34,1.56,0.64,1);
}
.bb-success-title { color: #fff; font-size: 1.25rem; font-weight: 800; margin: 0 0 6px; }
.bb-success-sub   { color: rgba(255,255,255,0.78); font-size: 0.78rem; margin: 0 0 14px; }
.bb-order-no-chip {
  display: inline-flex; align-items: center; gap: 6px;
  background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3);
  border-radius: 24px; padding: 6px 16px;
  color: var(--gold); font-size: 0.82rem; font-weight: 800;
}

/* Details card */
.bb-conf-card {
  background: #fff; border-radius: 14px;
  border: 1.5px solid #f0f0f0;
  box-shadow: 0 2px 10px rgba(26,35,126,0.07);
  margin-bottom: 14px; overflow: hidden;
}
.bb-conf-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 16px; border-bottom: 1px solid #f5f5f5;
  font-size: 0.82rem;
}
.bb-conf-row:last-child { border-bottom: none; }
.bb-conf-lbl { color: #78909c; font-weight: 500; }
.bb-conf-val { color: var(--primary); font-weight: 700; }

/* Items list */
.bb-conf-items { padding: 12px 16px; }
.bb-conf-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: 8px 0; border-bottom: 1px dashed #f0f0f0; font-size: 0.8rem;
}
.bb-conf-item:last-child { border-bottom: none; }
.bb-conf-item-name { color: var(--primary); font-weight: 600; flex: 1; margin-right: 8px; }
.bb-conf-item-qty  { color: #78909c; font-size: 0.72rem; }
.bb-conf-item-amt  { color: var(--gold-dark); font-weight: 700; }

/* Total row */
.bb-total-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px; background: linear-gradient(135deg, var(--gold-light), #fff);
  border-top: 1.5px dashed var(--gold);
}
.bb-grand-total { font-size: 1.2rem; font-weight: 800; color: var(--gold-dark); }

/* UPI box */
.upi-payment-box {
  background: linear-gradient(135deg, var(--gold-light), #fff);
  border: 1.5px dashed var(--gold); border-radius: 12px;
  padding: 16px; text-align: center; margin: 14px 16px;
}

/* Action buttons */
.bb-action-row { display: flex; gap: 10px; margin-bottom: 14px; }
.bb-btn-action {
  flex: 1; border: none; border-radius: 12px; padding: 12px;
  font-family: 'Poppins',sans-serif; font-size: 0.82rem; font-weight: 700;
  cursor: pointer; text-align: center; text-decoration: none;
  display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: all 0.2s;
}
.bb-btn-action-primary {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: #fff; box-shadow: 0 4px 14px rgba(59,35,20,0.2);
}
.bb-btn-action-outline {
  background: var(--gold-light); color: var(--primary);
  border: 1.5px solid var(--gold);
}
.bb-btn-action:hover { transform: translateY(-2px); }
</style>


<div class="bb-confirm-wrap">
  <div class="bb-success-hero">
    <div class="bb-success-icon">🎉</div>
    <h2 class="bb-success-title">Order Placed Successfully!</h2>
    <p class="bb-success-sub">We've received your order and will confirm it shortly.</p>
    <div class="bb-order-no-chip">🧾 <?= htmlspecialchars($order->request_no) ?></div>
  </div>

  <div class="bb-conf-card">
    <div class="bb-conf-row">
      <span class="bb-conf-lbl">📅 Date Placed</span>
      <span class="bb-conf-val"><?= date('d M Y, h:i A', strtotime($order->created_at)) ?></span>
    </div>
    <div class="bb-conf-row">
      <span class="bb-conf-lbl">📦 Status</span>
      <span style="background:#fff3e0;color:#e65100;padding:3px 12px;border-radius:20px;font-size:0.72rem;font-weight:700;">⏳ Pending Confirmation</span>
    </div>
    <?php if (!empty($order->delivery_slot)): ?>
    <div class="bb-conf-row">
      <span class="bb-conf-lbl">🕐 Delivery Slot</span>
      <span class="bb-conf-val"><?= htmlspecialchars($order->delivery_slot) ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($order->delivery_distance)): ?>
    <div class="bb-conf-row">
      <span class="bb-conf-lbl">📍 Distance</span>
      <span class="bb-conf-val"><?= $order->delivery_distance ?> KM</span>
    </div>
    <?php endif; ?>
  </div>

  <div class="bb-conf-card">
    <div style="padding:12px 16px 8px;border-bottom:1px solid #f5f5f5;">
      <div style="font-size:0.88rem;font-weight:700;color:var(--primary);">🛒 Order Items</div>
    </div>
    <div class="bb-conf-items">
      <?php foreach ($items as $item): ?>
      <div class="bb-conf-item">
        <span class="bb-conf-item-name">
          <?= htmlspecialchars($item->product_name) ?>
          <?php if (!empty($item->notes)): ?>
          <span style="display:block;font-size:0.65rem;color:#90a4ae;font-style:italic;"><?= htmlspecialchars($item->notes) ?></span>
          <?php endif; ?>
        </span>
        <span class="bb-conf-item-qty" style="margin-right:10px;">×<?= $item->qty ?></span>
        <span class="bb-conf-item-amt">₹<?= number_format($item->mrp * $item->qty, 2) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="bb-total-row">
      <div>
        <div style="font-size:0.7rem;color:var(--text-muted);">Subtotal ₹<?= number_format($subtotal,2) ?> + Delivery <?= $delivery_charge > 0 ? '₹'.number_format($delivery_charge,2) : 'FREE' ?></div>
        <div style="font-size:0.72rem;font-weight:600;color:var(--primary);">Grand Total</div>
      </div>
      <div class="bb-grand-total">₹<?= number_format($total, 2) ?></div>
    </div>
    <?php if (!empty($order->notes)): ?>
    <div style="padding:10px 16px;background:#f8f9fd;font-size:0.75rem;color:var(--primary);border-top:1px solid #f0f0f0;">
      <strong>📝 Notes:</strong> <?= nl2br(htmlspecialchars($order->notes)) ?>
    </div>
    <?php endif; ?>
  </div>

  <?php
  $upi_id   = $company['company_upi_id']   ?? '';
  $upi_name = $company['company_upi_name'] ?? $company['company_name'] ?? 'Sandal House';
  if (!empty($upi_id)): ?>
  <div class="bb-conf-card">
    <div class="upi-payment-box">
      <div style="font-size:0.9rem;font-weight:700;color:var(--primary);margin-bottom:6px;">💳 Pay ₹<?= number_format($total, 2) ?> via UPI</div>
      <p id="confPayHint" style="font-size:0.72rem;color:var(--text-muted);margin:0 0 12px;">Tap below to pay from your UPI app, or scan the QR.</p>

      <!-- Direct pay button (opens UPI app with the amount filled in) -->
      <button type="button" id="confPayBtn" onclick="launchUpi()" style="width:100%;border:none;border-radius:12px;padding:13px;font-family:'Poppins',sans-serif;font-size:0.92rem;font-weight:700;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;box-shadow:0 4px 14px rgba(59,35,20,0.25);margin-bottom:12px;">
        💳 Pay ₹<?= number_format($total, 2) ?> Now
      </button>

      <div style="display:flex;justify-content:center;margin-bottom:10px;">
        <div id="confirmation-upi-qr" style="background:#fff;padding:6px;border:1.5px solid var(--gold);border-radius:10px;"></div>
      </div>
      <div style="font-size:0.8rem;font-weight:700;color:var(--primary);"><?= htmlspecialchars($upi_name) ?></div>
      <code style="font-size:0.7rem;color:var(--text-muted);word-break:break-all;"><?= htmlspecialchars($upi_id) ?></code>
    </div>
  </div>
  <?php endif; ?>

  <div class="bb-action-row">
    <a href="<?= base_url('portal/products') ?>" class="bb-btn-action bb-btn-action-primary">🛍️ Shop More</a>
  </div>
</div>

<script src="<?= base_url('assets/js/qrcode.min.js') ?>"></script>
<script>
var UPI_ID   = "<?= addslashes($upi_id ?? '') ?>";
var UPI_NAME = "<?= addslashes($upi_name ?? 'SandalHouse') ?>";
var UPI_AMT  = "<?= number_format($total, 2, '.', '') ?>";
var UPI_REF  = "<?= addslashes($order->request_no) ?>";
var PAY_APP  = "<?= addslashes($pay_app ?? '') ?>";

function upiParams() {
  return "pa=" + encodeURIComponent(UPI_ID) +
         "&pn=" + encodeURIComponent(UPI_NAME) +
         "&am=" + UPI_AMT + "&cu=INR" +
         "&tn=" + encodeURIComponent("Order " + UPI_REF) +
         "&tr=" + encodeURIComponent(UPI_REF);
}
function upiLink(app) {
  var p = upiParams();
  switch (app) {
    case 'gpay':    return "tez://upi/pay?" + p;
    case 'phonepe': return "phonepe://pay?" + p;
    case 'paytm':   return "paytmmp://pay?" + p;
    default:        return "upi://pay?" + p;   // any UPI app chooser
  }
}
function launchUpi(app) {
  if (!UPI_ID) return;
  window.location.href = upiLink(app || PAY_APP || 'upi');
}

window.addEventListener('DOMContentLoaded', function() {
  if (UPI_ID) {
    var el = document.getElementById('confirmation-upi-qr');
    if (el) new QRCode(el, { text: upiLink('upi'), width: 120, height: 120 });

    // If the customer picked an app at checkout, open it automatically with the amount
    if (PAY_APP) {
      var hint = document.getElementById('confPayHint');
      if (hint) hint.textContent = 'Opening your payment app… if it does not, tap Pay below.';
      setTimeout(function(){ launchUpi(PAY_APP); }, 900);
    }
  }
});
</script>

