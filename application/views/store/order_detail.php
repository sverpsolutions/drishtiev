<div class="st-page">
  <div class="st-section-head">
    <div class="st-section-title">Order <?= htmlspecialchars($order->request_no) ?></div>
    <span class="st-badge st-badge-<?= $order->status ?>"><?= ucfirst($order->status) ?></span>
  </div>
  <div style="font-size:.78rem;color:#999;margin-bottom:14px;">Placed on <?= date('d M Y, h:i A', strtotime($order->created_at)) ?></div>

  <div class="st-card">
    <div class="st-card-header">Items</div>
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
      <div class="st-summary-row total"><span>Total</span><span>₹<?= number_format($order->total_amount, 2) ?></span></div>
    </div>
  </div>

  <?php if (!empty($order->delivery_slot)): ?>
  <div class="st-card"><div class="st-card-body"><strong style="font-size:.8rem;">Delivery Slot:</strong> <span style="font-size:.8rem;"><?= htmlspecialchars($order->delivery_slot) ?></span></div></div>
  <?php endif; ?>
  <?php if (!empty($order->notes)): ?>
  <div class="st-card"><div class="st-card-body"><strong style="font-size:.8rem;">Notes:</strong> <span style="font-size:.8rem;"><?= nl2br(htmlspecialchars($order->notes)) ?></span></div></div>
  <?php endif; ?>
</div>
