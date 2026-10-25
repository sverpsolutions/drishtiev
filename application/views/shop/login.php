<div class="wrap auth-box">
  <div class="card">
    <h1 style="font-size:32px">Log in</h1>
    <?php if ($err): ?><div class="alert alert-error" role="alert"><?= html_escape($err) ?></div><?php endif; ?>
    <form method="post" action="<?= current_url() . ($this->input->get('next') ? '?next=' . urlencode($this->input->get('next')) : '') ?>">
      <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
      <div class="field"><label for="phone">Mobile number</label><input class="input" id="phone" name="phone" required inputmode="numeric" maxlength="10" autocomplete="username"></div>
      <div class="field"><label for="password">Password</label><input class="input" id="password" type="password" name="password" required autocomplete="current-password"></div>
      <button class="btn btn-primary btn-block" type="submit">Log in</button>
    </form>
    <p class="center" style="margin:16px 0 0">New here? <a href="<?= site_url('account/register') ?>">Create an account</a></p>
    <p class="center small muted" style="margin:8px 0 0">Forgot your password? Please contact the store to reset it.<br>You can also check out as a guest and <a href="<?= site_url('track') ?>">track orders</a> with your order number.</p>
  </div>
</div>
