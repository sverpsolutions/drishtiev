<div class="wrap auth-box">
  <div class="card">
    <h1 style="font-size:32px">Track your order</h1>
    <p class="muted">Enter your order number and the mobile number used at checkout.</p>
    <?php if ($err): ?><div class="alert alert-error" role="alert"><?= html_escape($err) ?></div><?php endif; ?>
    <form method="post" action="<?= site_url('track') ?>">
      <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
      <div class="field"><label for="order_no">Order number</label><input class="input" id="order_no" name="order_no" required maxlength="30" placeholder="e.g. SVC261010-12" value="<?= html_escape($order_no) ?>"></div>
      <div class="field"><label for="phone">Mobile number</label><input class="input" id="phone" name="phone" required inputmode="numeric" maxlength="10"></div>
      <button class="btn btn-primary btn-block" type="submit">Track order</button>
    </form>
  </div>
</div>
