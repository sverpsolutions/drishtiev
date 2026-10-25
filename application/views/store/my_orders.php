<div class="st-page">
  <div class="st-section-head"><div class="st-section-title">My Orders</div></div>

  <?php if (empty($orders)): ?>
  <div class="st-empty"><i class="fa fa-clipboard-list"></i><div>No orders yet.</div><a href="<?= base_url('store') ?>" class="st-btn-primary" style="margin-top:14px;display:inline-block;">Start Shopping</a></div>
  <?php else: ?>
    <?php foreach ($orders as $o): ?>
    <a href="<?= base_url('store/order_detail/' . $o->id) ?>" class="st-card" style="display:block;padding:14px 16px;">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <strong style="font-size:.88rem;"><?= htmlspecialchars($o->request_no) ?></strong>
        <span class="st-badge st-badge-<?= $o->status ?>"><?= ucfirst($o->status) ?></span>
      </div>
      <div style="font-size:.74rem;color:#999;margin-top:4px;"><?= date('d M Y, h:i A', strtotime($o->created_at)) ?></div>
      <div style="font-size:.9rem;font-weight:800;margin-top:6px;">₹<?= number_format($o->total_amount, 2) ?></div>
    </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
