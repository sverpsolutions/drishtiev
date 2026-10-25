<div class="wrap" style="padding-bottom:48px">
  <nav class="crumbs"><a href="<?= base_url() ?>">Home</a> / My account</nav>
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
    <h1 style="margin:0">Namaste, <?= html_escape($customer['name']) ?></h1>
    <a class="btn btn-outline btn-sm" href="<?= site_url('account/logout') ?>">Log out</a>
  </div>
  <div class="two-col" style="margin-top:20px">
    <div class="card">
      <h3>My orders</h3>
      <?php if (!$orders): ?>
        <div class="empty" style="padding:24px 0"><p class="muted">You haven't placed any orders yet.</p><a class="btn btn-primary" href="<?= site_url('shop') ?>">Start shopping</a></div>
      <?php else: ?>
      <div style="overflow-x:auto">
      <table class="tbl">
        <thead><tr><th>Order</th><th>Date</th><th>Status</th><th class="r">Total</th></tr></thead>
        <tbody><?php foreach ($orders as $o): ?>
          <tr><td><a href="<?= site_url('account/order/' . $o->order_no) ?>"><?= html_escape($o->order_no) ?></a></td><td><?= date('d M Y', strtotime($o->created_at)) ?></td><td><?= order_badge($o->order_status) ?></td><td class="r"><?= inr($o->grand_total, 2) ?></td></tr>
        <?php endforeach; ?></tbody>
      </table>
      </div>
      <?php endif; ?>
    </div>
    <aside class="card">
      <h3>Profile</h3>
      <p style="margin:0 0 16px"><?= html_escape($customer['name']) ?><br>Mobile: <?= html_escape($customer['phone']) ?></p>
      <h3>Change password</h3>
      <?php if ($err): ?><div class="alert alert-error"><?= html_escape($err) ?></div><?php endif; ?>
      <form method="post" action="<?= site_url('account') ?>">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
        <div class="field"><label for="cp">Current password</label><input class="input" id="cp" type="password" name="current_password" required autocomplete="current-password"></div>
        <div class="field"><label for="np">New password (min 8 characters)</label><input class="input" id="np" type="password" name="new_password" required minlength="8" autocomplete="new-password"></div>
        <button class="btn btn-primary btn-block" type="submit">Update password</button>
      </form>
    </aside>
  </div>
</div>
