<?php // FILE: application/views/portal/profile.php — Sandal House ?>
<style>
.bb-profile-wrap { padding: 12px; max-width: 600px; margin: 0 auto; }
.bb-avatar {
  width: 80px; height: 80px; border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; margin: 0 auto 12px;
  box-shadow: 0 6px 20px rgba(59,35,20,0.2);
}
.bb-profile-name { font-size: 1.2rem; font-weight: 800; color: var(--primary); text-align: center; }
.bb-profile-phone { font-size: 0.8rem; color: var(--text-muted); text-align: center; margin-top: 3px; }
.bb-profile-card {
  background: #fff; border-radius: 14px;
  border: 1.5px solid #f0f0f0;
  box-shadow: 0 2px 8px rgba(59,35,20,0.06);
  margin-bottom: 12px; overflow: hidden;
}
.bb-profile-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 13px 16px; border-bottom: 1px solid #f5f5f5;
  font-size: 0.82rem; text-decoration: none; color: inherit;
  transition: background 0.15s;
}
.bb-profile-row:hover { background: var(--primary-xlight); text-decoration: none; }
.bb-profile-row:last-child { border-bottom: none; }
.bb-profile-row-icon {
  width: 36px; height: 36px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; margin-right: 12px; flex-shrink: 0;
}
.bb-profile-row-label { font-weight: 600; color: var(--primary); flex: 1; }
.bb-profile-row-value { font-size: 0.75rem; color: var(--text-muted); }
.bb-profile-stat {
  text-align: center; padding: 14px 10px;
}
.bb-profile-stat-val { font-size: 1.2rem; font-weight: 800; color: var(--primary); }
.bb-profile-stat-lbl { font-size: 0.65rem; color: var(--text-muted); font-weight: 600; margin-top: 2px; }
</style>

<div class="bb-profile-wrap">

  <!-- Avatar & Name -->
  <div style="text-align:center; padding: 20px 0 16px;">
    <div class="bb-avatar">
      <?= strtoupper(substr($portal_customer['name'] ?? 'U', 0, 1)) ?>
    </div>
    <div class="bb-profile-name"><?= htmlspecialchars($portal_customer['name'] ?? '') ?></div>
    <div class="bb-profile-phone">📱 <?= htmlspecialchars($portal_customer['phone'] ?? '') ?></div>
    <?php if (!empty($portal_customer['type'])): ?>
    <div style="margin-top:8px;">
      <span style="background:var(--gold-light);color:var(--primary);padding:3px 12px;border-radius:20px;font-size:0.7rem;font-weight:700;">
        <?= strtoupper($portal_customer['type']) ?> Account
      </span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Stats Strip -->
  <?php if (!empty($customer)): ?>
  <div style="background:#fff;border-radius:14px;border:1.5px solid #f0f0f0;box-shadow:0 2px 8px rgba(59,35,20,0.06);display:flex;margin-bottom:12px;overflow:hidden;">
    <div class="bb-profile-stat" style="flex:1;border-right:1px solid #f0f0f0;">
      <div class="bb-profile-stat-val"><?= $total_orders ?? 0 ?></div>
      <div class="bb-profile-stat-lbl">Orders</div>
    </div>
    <div class="bb-profile-stat" style="flex:1;border-right:1px solid #f0f0f0;">
      <div class="bb-profile-stat-val"><?= $total_invoices ?? 0 ?></div>
      <div class="bb-profile-stat-lbl">Invoices</div>
    </div>
    <div class="bb-profile-stat" style="flex:1;">
      <div class="bb-profile-stat-val" style="color:<?= ($customer->balance ?? 0) > 0 ? '#c62828' : '#2e7d32'; ?>">
        ₹<?= number_format(abs($customer->balance ?? 0), 0) ?>
      </div>
      <div class="bb-profile-stat-lbl"><?= ($customer->balance ?? 0) > 0 ? 'Due' : 'Balance' ?></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Account Info -->
  <div class="bb-profile-card">
    <div style="padding:12px 16px 8px;border-bottom:1px solid #f5f5f5;font-size:0.8rem;font-weight:700;color:#78909c;text-transform:uppercase;letter-spacing:0.5px;">
      Account Info
    </div>
    <?php if (!empty($customer->email)): ?>
    <div class="bb-profile-row" style="cursor:default;">
      <div class="bb-profile-row-icon" style="background:var(--gold-light);">✉️</div>
      <div>
        <div class="bb-profile-row-label">Email</div>
        <div class="bb-profile-row-value"><?= htmlspecialchars($customer->email) ?></div>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($customer->address)): ?>
    <div class="bb-profile-row" style="cursor:default;">
      <div class="bb-profile-row-icon" style="background:var(--gold-light);">📍</div>
      <div>
        <div class="bb-profile-row-label">Delivery Address</div>
        <div class="bb-profile-row-value"><?= htmlspecialchars($customer->address) ?></div>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($customer->gst_number)): ?>
    <div class="bb-profile-row" style="cursor:default;">
      <div class="bb-profile-row-icon" style="background:var(--gold-light);">🏢</div>
      <div>
        <div class="bb-profile-row-label">GST Number</div>
        <div class="bb-profile-row-value"><?= htmlspecialchars($customer->gst_number) ?></div>
      </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($customer->credit_limit) && $customer->credit_limit > 0): ?>
    <div class="bb-profile-row" style="cursor:default;">
      <div class="bb-profile-row-icon" style="background:var(--gold-light);">💳</div>
      <div>
        <div class="bb-profile-row-label">Credit Limit</div>
        <div class="bb-profile-row-value">₹<?= number_format($customer->credit_limit, 0) ?></div>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Quick Links -->
  <div class="bb-profile-card">
    <div style="padding:12px 16px 8px;border-bottom:1px solid #f5f5f5;font-size:0.8rem;font-weight:700;color:#78909c;text-transform:uppercase;letter-spacing:0.5px;">
      Quick Links
    </div>
    <a href="<?= base_url('portal/my_orders') ?>" class="bb-profile-row">
      <div class="bb-profile-row-icon" style="background:var(--gold-light);">📋</div>
      <span class="bb-profile-row-label">My Orders</span>
      <span style="color:var(--text-muted);">→</span>
    </a>
    <a href="<?= base_url('portal/invoices') ?>" class="bb-profile-row">
      <div class="bb-profile-row-icon" style="background:var(--gold-light);">📄</div>
      <span class="bb-profile-row-label">My Invoices</span>
      <span style="color:var(--text-muted);">→</span>
    </a>
    <a href="<?= base_url('portal/products') ?>" class="bb-profile-row">
      <div class="bb-profile-row-icon" style="background:var(--gold-light);">🛍️</div>
      <span class="bb-profile-row-label">Shop Products</span>
      <span style="color:var(--text-muted);">→</span>
    </a>
  </div>

  <!-- Logout -->
  <a href="<?= base_url('portal/logout') ?>"
     style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;border:1.5px solid #fce4ec;background:#fff;color:#c62828;border-radius:12px;padding:13px;font-size:0.88rem;font-weight:700;text-decoration:none;transition:all 0.2s;margin-bottom:20px;"
     onmouseover="this.style.background='#fce4ec'" onmouseout="this.style.background='#fff'">
    🚪 Sign Out
  </a>

  <div style="text-align:center;font-size:0.68rem;color:var(--text-muted);padding-bottom:10px;">
    <?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?> &copy; <?= date('Y') ?> — Powered by <?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?> Portal
  </div>

</div>
