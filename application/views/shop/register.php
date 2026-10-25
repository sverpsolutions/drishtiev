<div class="wrap auth-box">
  <div class="card">
    <h1 style="font-size:32px">Create account</h1>
    <?php if ($err): ?><div class="alert alert-error" role="alert"><?= html_escape($err) ?></div><?php endif; ?>
    <form method="post" action="<?= site_url('account/register') ?>">
      <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
      <div class="field"><label for="name">Full name</label><input class="input" id="name" name="name" required maxlength="100" autocomplete="name" value="<?= html_escape($f['name']) ?>"></div>
      <div class="field"><label for="phone">Mobile number</label><input class="input" id="phone" name="phone" required inputmode="numeric" maxlength="10" autocomplete="tel-national" value="<?= html_escape($f['phone']) ?>"></div>
      <div class="field"><label for="email">Email (optional)</label><input class="input" id="email" name="email" type="email" maxlength="120" autocomplete="email" value="<?= html_escape($f['email']) ?>"></div>
      <div class="grid2">
        <div class="field"><label for="password">Password (min 8)</label><input class="input" id="password" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
        <div class="field"><label for="password2">Confirm password</label><input class="input" id="password2" type="password" name="password2" required minlength="8" autocomplete="new-password"></div>
      </div>
      <button class="btn btn-primary btn-block" type="submit">Create account</button>
    </form>
    <p class="center" style="margin:16px 0 0">Already registered? <a href="<?= site_url('account/login') ?>">Log in</a></p>
  </div>
</div>
