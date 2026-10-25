<div class="st-page">
  <div class="st-section-head"><div class="st-section-title">My Profile</div></div>

  <div class="st-card">
    <div class="st-card-body" style="text-align:center;padding:22px 16px;">
      <div class="st-auth-icon" style="margin:0 auto 10px;"><i class="fa fa-user"></i></div>
      <div style="font-family:'Poppins',sans-serif;font-weight:800;font-size:1.05rem;"><?= htmlspecialchars($customer->name) ?></div>
      <div style="font-size:.8rem;color:#999;"><?= htmlspecialchars($customer->phone) ?></div>
    </div>
  </div>

  <div class="st-card">
    <div class="st-card-body">
      <div class="st-summary-row"><span>Email</span><span><?= htmlspecialchars($customer->email ?: '—') ?></span></div>
      <div class="st-summary-row"><span>Address</span><span style="text-align:right;max-width:60%;"><?= htmlspecialchars($customer->address ?: '—') ?></span></div>
      <div class="st-summary-row"><span>Total Orders</span><span><?= (int)$total_orders ?></span></div>
    </div>
  </div>

  <a href="<?= base_url('store/my_orders') ?>" class="st-card" style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;">
    <span style="font-size:.85rem;font-weight:700;"><i class="fa fa-clipboard-list mr-2"></i> My Orders</span>
    <i class="fa fa-chevron-right" style="color:#ccc;"></i>
  </a>

  <a href="<?= base_url('store/logout') ?>" class="st-btn-ghost st-btn-block" style="margin-top:16px;color:#c62828;border-color:#f5c6c6;text-align:center;display:block;">
    <i class="fa fa-sign-out-alt mr-2"></i> Logout
  </a>
</div>
