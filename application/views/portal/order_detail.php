<?php // FILE: application/views/portal/order_detail.php — Sandal House ?>
<style>
.bb-od-wrap { padding: 12px; max-width: 700px; margin: 0 auto; }

/* Status Timeline */
.bb-timeline { margin: 14px 0; }
.bb-tl-step {
  display: flex; gap: 12px; align-items: flex-start;
  margin-bottom: 14px; position: relative;
}
.bb-tl-step:not(:last-child) .bb-tl-dot::after {
  content: ''; position: absolute;
  left: 11px; top: 26px;
  width: 2px; height: 100%;
  background: #e0e0e0;
}
.bb-tl-step.done:not(:last-child) .bb-tl-dot::after {
  background: var(--primary);
}
.bb-tl-dot {
  width: 24px; height: 24px; border-radius: 50%;
  flex-shrink: 0; position: relative;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.65rem; font-weight: 700;
}
.bb-tl-dot.done   { background: var(--primary); color: #fff; }
.bb-tl-dot.active { background: var(--gold); color: var(--primary); box-shadow: 0 0 0 3px rgba(246,205,60,0.25); }
.bb-tl-dot.todo   { background: #e0e0e0; color: #90a4ae; }
.bb-tl-content { flex: 1; padding-top: 2px; }
.bb-tl-title { font-size: 0.82rem; font-weight: 700; color: var(--primary); }
.bb-tl-title.todo { color: #90a4ae; }
.bb-tl-sub { font-size: 0.68rem; color: #78909c; margin-top: 1px; }

/* Card basics */
.bb-od-card {
  background: #fff; border-radius: 14px;
  border: 1.5px solid #f0f0f0;
  box-shadow: 0 2px 8px rgba(59,35,20,0.06);
  margin-bottom: 12px; overflow: hidden;
}
.bb-od-card-header {
  padding: 12px 16px 10px;
  border-bottom: 1px solid #f5f5f5;
  font-size: 0.88rem; font-weight: 700; color: var(--primary);
  display: flex; align-items: center; gap: 6px;
}
.bb-od-card-header::before {
  content: ''; display: inline-block;
  width: 3px; height: 14px;
  background: linear-gradient(var(--gold), var(--primary)); border-radius: 2px;
}
.bb-item-row {
  display: flex; align-items: flex-start; justify-content: space-between;
  padding: 10px 16px; border-bottom: 1px dashed #f0f0f0; gap: 8px;
  font-size: 0.8rem;
}
.bb-item-row:last-child { border-bottom: none; }
.bb-item-name { color: var(--primary); font-weight: 600; flex: 1; }
.bb-item-note { display: block; font-size: 0.65rem; color: #90a4ae; font-style: italic; }
.bb-item-qty  { color: #78909c; font-size: 0.72rem; white-space: nowrap; }
.bb-item-amt  { color: var(--gold-dark); font-weight: 700; white-space: nowrap; }
.bb-total-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 16px;
  background: linear-gradient(135deg, var(--gold-light), #fff);
  border-top: 1.5px dashed var(--gold);
  font-size: 0.88rem;
}
.bb-total-bar-val { font-size: 1.15rem; font-weight: 800; color: var(--gold-dark); }

.bb-status-alert {
  margin: 0 12px 12px;
  border-radius: 10px; padding: 12px 14px;
  font-size: 0.8rem; font-weight: 600;
  display: flex; align-items: center; gap: 8px;
}
</style>

<div class="bb-od-wrap">

  <!-- Back + Order No -->
  <div style="margin-bottom:14px;">
    <a href="<?= base_url('portal/my_orders') ?>" style="display:inline-flex;align-items:center;gap:4px;color:var(--primary);font-size:0.78rem;font-weight:600;text-decoration:none;background:var(--gold-light);border-radius:20px;padding:6px 14px;margin-bottom:10px;">
      ← My Orders
    </a>
    <h2 style="font-size:1.05rem; font-weight:800; color:var(--primary); margin:0 0 2px;"><?= htmlspecialchars($order->request_no) ?></h2>
    <div style="font-size:0.72rem; color:#90a4ae;"><?= date('d M Y, h:i A', strtotime($order->created_at)) ?></div>
  </div>

  <!-- Status alert -->
  <?php
  $status = strtolower($order->status);
  if ($status === 'converted' || $status === 'delivered'):
  ?>
  <div class="bb-status-alert" style="background:#e8f5e9; color:#2e7d32; border-left:3px solid #2e7d32;">
    ✅ Order confirmed & dispatched! Check <a href="<?= base_url('portal/invoices') ?>" style="color:#2e7d32;font-weight:700;">My Invoices</a> for your bill.
  </div>
  <?php elseif ($status === 'confirmed'): ?>
  <div class="bb-status-alert" style="background:var(--gold-light); color:var(--primary); border-left:3px solid var(--primary);">
    ✅ Order confirmed! We're preparing your items.
  </div>
  <?php elseif ($status === 'cancelled'): ?>
  <div class="bb-status-alert" style="background:#fce4ec; color:#c62828; border-left:3px solid #c62828;">
    ❌ Order cancelled. Contact us for assistance.
  </div>
  <?php else: ?>
  <div class="bb-status-alert" style="background:var(--gold-light); color:var(--primary); border-left:3px solid var(--gold);">
    ⏳ Order is pending confirmation. We'll notify you shortly!
  </div>
  <?php endif; ?>

  <!-- Order Progress Timeline -->
  <?php if ($status !== 'cancelled'): ?>
  <div class="bb-od-card">
    <div class="bb-od-card-header">Order Progress</div>
    <div style="padding:16px;">
      <?php
      $steps = [
        ['key'=>'pending',   'label'=>'Order Placed',    'sub'=>'Your order request was received', 'icon'=>'📥'],
        ['key'=>'confirmed', 'label'=>'Confirmed',       'sub'=>'Shop confirmed your order',       'icon'=>'✅'],
        ['key'=>'converted', 'label'=>'Dispatched',      'sub'=>'Items are on the way',            'icon'=>'🚀'],
        ['key'=>'delivered', 'label'=>'Delivered',       'sub'=>'Enjoy your purchase!',            'icon'=>'🎉'],
      ];
      $order_steps = ['pending'=>0,'confirmed'=>1,'converted'=>2,'delivered'=>3];
      $cur_step_num = $order_steps[$status] ?? 0;
      ?>
      <div class="bb-timeline">
        <?php foreach ($steps as $si => $step):
          $is_done   = $si < $cur_step_num;
          $is_active = $si === $cur_step_num;
          $dot_class = $is_done ? 'done' : ($is_active ? 'active' : 'todo');
          $title_class = (!$is_done && !$is_active) ? 'todo' : '';
        ?>
        <div class="bb-tl-step <?= $is_done ? 'done' : '' ?>">
          <div class="bb-tl-dot <?= $dot_class ?>"><?= $is_done ? '✓' : ($is_active ? $step['icon'] : ($si+1)) ?></div>
          <div class="bb-tl-content">
            <div class="bb-tl-title <?= $title_class ?>"><?= $step['label'] ?></div>
            <div class="bb-tl-sub"><?= $step['sub'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Order Items -->
  <div class="bb-od-card">
    <div class="bb-od-card-header">🛒 Order Items</div>
    <?php foreach ($items as $item): ?>
    <div class="bb-item-row">
      <div class="bb-item-name">
        <?= htmlspecialchars($item->product_name) ?>
        <?php if (!empty($item->notes)): ?>
        <span class="bb-item-note"><?= htmlspecialchars($item->notes) ?></span>
        <?php endif; ?>
      </div>
      <span class="bb-item-qty">×<?= $item->qty ?> <?= htmlspecialchars($item->unit ?? '') ?></span>
      <?php $sp = isset($item->selling_price) && $item->selling_price > 0 ? $item->selling_price : $item->mrp; ?>
      <span class="bb-item-amt" style="margin-left:12px;">₹<?= number_format($sp * $item->qty, 2) ?></span>
    </div>
    <?php endforeach; ?>
    <div class="bb-total-bar">
      <div>
        <div style="font-size:0.7rem;color:#78909c;">Subtotal + Delivery</div>
        <div style="font-size:0.72rem;font-weight:600;color:#37474f;">Grand Total</div>
      </div>
      <span class="bb-total-bar-val">₹<?= number_format($order->total_amount ?? 0, 2) ?></span>
    </div>
  </div>

  <!-- Delivery Info -->
  <?php if (!empty($order->delivery_slot) || !empty($order->delivery_distance) || !empty($order->notes)): ?>
  <div class="bb-od-card">
    <div class="bb-od-card-header">📍 Delivery Info</div>
    <div style="padding:12px 16px;">
      <?php if (!empty($order->delivery_distance)): ?>
      <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.8rem;">
        <span style="color:#78909c;">Distance</span>
        <span style="font-weight:700;color:var(--primary);"><?= $order->delivery_distance ?> KM</span>
      </div>
      <?php endif; ?>
      <?php if (!empty($order->delivery_slot)): ?>
      <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.8rem;">
        <span style="color:#78909c;">Time Slot</span>
        <span style="font-weight:700;color:var(--primary);"><?= htmlspecialchars($order->delivery_slot) ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($order->delivery_charge) && $order->delivery_charge > 0): ?>
      <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.8rem;">
        <span style="color:#78909c;">Delivery Charge</span>
        <span style="font-weight:700;color:var(--gold-dark);">₹<?= number_format($order->delivery_charge, 2) ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($order->notes)): ?>
      <div style="background:#FAF6EB;border-radius:8px;padding:10px;font-size:0.78rem;color:var(--primary);margin-top:6px;">
        <strong>📝 Notes:</strong><br><?= nl2br(htmlspecialchars($order->notes)) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div style="display:flex;gap:10px;margin-bottom:20px;">
    <a href="<?= base_url('portal/products') ?>" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;border-radius:12px;padding:12px;font-size:0.82rem;font-weight:700;text-decoration:none;">
      🛍️ Shop More
    </a>
    <a href="<?= base_url('portal/invoices') ?>" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;background:var(--gold-light);color:var(--primary);border-radius:12px;padding:12px;font-size:0.82rem;font-weight:700;text-decoration:none;border:1.5px solid var(--gold);">
      📄 Invoices
    </a>
  </div>

</div>
