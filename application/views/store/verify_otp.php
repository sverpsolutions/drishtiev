<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#0DB14B">
<title><?= htmlspecialchars($company['company_name'] ?? 'Store') ?> — Verify OTP</title>
<link rel="stylesheet" href="<?= base_url('assets/css/font-awesome.min.css') ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/store.css') ?>">
<style>
:root { <?php include_once APPPATH . 'views/store/_theme.php'; echo store_theme_vars($company ?? null); ?> }
.st-body-auth { font-family: 'Inter', sans-serif; background: var(--st-bg); min-height: 100vh; margin: 0; display: flex; align-items: center; }
.st-otp-input { font-size: 1.6rem; letter-spacing: 10px; text-align: center; font-weight: 800; }
</style>
</head>
<body class="st-body-auth">
<div class="st-auth-wrap">
  <div class="st-auth-card">
    <div class="st-auth-icon"><i class="fa fa-envelope-open-text"></i></div>
    <div style="font-family:'Poppins',sans-serif;font-weight:800;font-size:1.1rem;margin-bottom:4px;">Verify Your Email</div>
    <div style="font-size:.8rem;color:#999;margin-bottom:18px;">We sent a 6-digit code to <strong><?= htmlspecialchars($email ?? 'your email') ?></strong></div>

    <?php if (!empty($error)): ?><div class="st-flash st-flash-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="post" action="<?= base_url('store/verify_otp') ?>">
      <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
      <div class="st-field">
        <input type="text" name="otp" class="st-otp-input" maxlength="6" pattern="[0-9]*" inputmode="numeric" autocomplete="one-time-code" required autofocus>
      </div>
      <button type="submit" class="st-btn-primary st-btn-block">Verify &amp; Continue</button>
    </form>
    <div style="margin-top:14px;"><a href="<?= base_url('store/login') ?>" style="font-size:.8rem;color:#999;">← Back to login</a></div>
  </div>
</div>
</body>
</html>
