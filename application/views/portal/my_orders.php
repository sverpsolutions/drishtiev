<?php // FILE: application/views/portal/my_orders.php — Sandal House ?>
<style>
.bb-orders-wrap { padding: 12px; max-width: 700px; margin: 0 auto; }
.bb-order-card {
  background: #fff; border-radius: 14px;
  border: 1.5px solid #f0f0f0;
  box-shadow: 0 2px 8px rgba(59,35,20,0.06);
  margin-bottom: 12px; overflow: hidden;
  transition: all 0.2s;
}
.bb-order-card:hover { box-shadow: 0 4px 16px rgba(59,35,20,0.1); border-color: var(--gold-light); }
.bb-order-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 14px; border-bottom: 1px solid #f5f5f5;
}
.bb-order-no { font-size: 0.82rem; font-weight: 800; color: var(--primary); }
.bb-order-date { font-size: 0.68rem; color: #90a4ae; }
.bb-order-body { padding: 12px 14px; }
.bb-order-items-preview { font-size: 0.75rem; color: #37474f; margin-bottom: 8px; }
.bb-order-footer {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 14px; background: var(--primary-xlight); border-top: 1px solid #f0f0f0;
}
.bb-order-total { font-size: 0.88rem; font-weight: 800; color: var(--gold-dark); }

/* Status Badge */
.bb-status { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
.bb-status.pending   { background: #fff3e0; color: #e65100; }
.bb-status.confirmed { background: var(--gold-light); color: var(--primary); }
.bb-status.converted, .bb-status.delivered { background: #e8f5e9; color: #2e7d32; }
.bb-status.cancelled { background: #fce4ec; color: #c62828; }

/* Progress track */
.bb-track { display: flex; align-items: center; gap: 0; margin: 6px 0; }
.bb-track-step { display: flex; flex-direction: column; align-items: center; flex: 1; }
.bb-track-dot {
  width: 20px; height: 20px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.6rem; font-weight: 700; position: relative; z-index: 1;
}
.bb-track-dot.done  { background: var(--primary); color: #fff; }
.bb-track-dot.active { background: var(--gold); color: var(--primary); }
.bb-track-dot.todo  { background: #e0e0e0; color: #90a4ae; }
.bb-track-line { flex: 1; height: 2px; background: #e0e0e0; margin: 0; }
.bb-track-line.done { background: var(--primary); }
.bb-track-label { font-size: 0.55rem; font-weight: 600; color: #90a4ae; margin-top: 4px; text-align: center; }
.bb-track-label.done, .bb-track-label.active { color: var(--primary); }
</style>

<div class="bb-orders-wrap">

  <div style="margin-bottom:14px;">
    <h2 style="font-size:1.05rem; font-weight:800; color:var(--primary); margin:0 0 2px;">📋 My Orders</h2>
    <p style="font-size:0.78rem; color:var(--text-muted); margin:0;">Track your order requests</p>
  </div>

  <?php if (empty($orders)): ?>
  <div style="text-align:center; padding:50px 20px; background:#fff; border-radius:16px; box-shadow:0 2px 10px rgba(59,35,20,0.06);">
    <div style="font-size:3rem; opacity:0.25; margin-bottom:12px;">📋</div>
    <h5 style="font-size:0.96rem; font-weight:700; color:var(--primary);">No Orders Yet</h5>
    <p style="font-size:0.78rem; color:var(--text-muted); margin-bottom:16px;">Browse products and place your first order!</p>
    <a href="<?= base_url('portal/products') ?>" style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;border-radius:24px;padding:10px 24px;font-size:0.82rem;font-weight:700;text-decoration:none;">🛍️ Start Shopping</a>
  </div>
  <?php else: ?>

  <?php foreach ($orders as $ord):
    $status = strtolower($ord->status);
    $steps = ['pending', 'confirmed', 'converted', 'delivered'];
    $step_labels = ['Placed', 'Confirmed', 'Dispatched', 'Delivered'];
    $cur_step = array_search($status, $steps);
    if ($cur_step === false) $cur_step = 0;
  ?>
  <div class="bb-order-card">
    <div class="bb-order-card-header">
      <div>
        <div class="bb-order-no"><?= htmlspecialchars($ord->request_no) ?></div>
        <div class="bb-order-date"><?= date('d M Y, h:i A', strtotime($ord->created_at)) ?></div>
      </div>
      <span class="bb-status <?= $status ?>">
        <?php
        $icons = ['pending'=>'⏳','confirmed'=>'✅','converted'=>'🚀','delivered'=>'✅','cancelled'=>'❌'];
        $display_status = $status === 'converted' ? 'Dispatched' : ucfirst($status);
        echo ($icons[$status] ?? '📦') . ' ' . $display_status;
        ?>
      </span>
    </div>

    <?php if ($status !== 'cancelled'): ?>
    <div style="padding:10px 14px;">
      <!-- Progress Track -->
      <div style="display:flex; align-items:center;">
        <?php foreach ($steps as $i => $st):
          $is_done   = $i < $cur_step;
          $is_active = $i == $cur_step;
          $cls = $is_done ? 'done' : ($is_active ? 'active' : 'todo');
        ?>
        <?php if ($i > 0): ?>
          <div class="bb-track-line <?= $i <= $cur_step ? 'done' : '' ?>"></div>
        <?php endif; ?>
        <div style="display:flex;flex-direction:column;align-items:center;">
          <div class="bb-track-dot <?= $cls ?>"><?= $is_done ? '✓' : ($i+1) ?></div>
          <div class="bb-track-label <?= $cls ?>"><?= $step_labels[$i] ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="bb-order-footer">
      <div>
        <?php if (!empty($ord->delivery_slot)): ?>
        <div style="font-size:0.68rem; color:#78909c;">🕐 <?= htmlspecialchars($ord->delivery_slot) ?></div>
        <?php endif; ?>
        <div class="bb-order-total">₹<?= number_format($ord->total_amount ?? 0, 2) ?></div>
      </div>
      <a href="<?= base_url('portal/order_detail/'.$ord->id) ?>"
         style="display:inline-flex;align-items:center;gap:4px;background:var(--gold-light);color:var(--primary);border-radius:20px;padding:7px 14px;font-size:0.75rem;font-weight:700;text-decoration:none;">
        View Details →
      </a>
    </div>
  </div>
  <?php endforeach; ?>

  <?php endif; ?>

</div>
