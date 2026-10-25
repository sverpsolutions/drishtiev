<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#0DB14B">
<title><?= htmlspecialchars($company['company_name'] ?? 'Store') ?> — Create Account</title>
<link rel="stylesheet" href="<?= base_url('assets/css/font-awesome.min.css') ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/store.css') ?>">
<style>
:root { <?php include_once APPPATH . 'views/store/_theme.php'; echo store_theme_vars($company ?? null); ?> }
.st-body-auth { font-family: 'Inter', sans-serif; background: var(--st-bg); min-height: 100vh; margin: 0; }
.st-auth-hero { background: linear-gradient(160deg, var(--st-primary-dark), var(--st-primary)); padding: 44px 24px 60px; text-align: center; color: #fff; }
.st-auth-hero h1 { font-family: 'Poppins', sans-serif; font-size: 1.3rem; font-weight: 800; margin: 10px 0 4px; }
</style>
</head>
<body class="st-body-auth">

<div class="st-auth-hero">
  <?php if (!empty($company['company_logo'])): ?><img src="<?= base_url($company['company_logo']) ?>" style="height:48px;background:#fff;border-radius:10px;padding:4px;"><?php endif; ?>
  <h1>Create Your Account</h1>
  <div style="font-size:.8rem;opacity:.9;">Fast checkout, order tracking &amp; more</div>
</div>

<div class="st-auth-wrap" style="margin-top:-30px;">
  <div class="st-auth-card" style="text-align:left;">
    <?php if (!empty($error)): ?><div class="st-flash st-flash-danger" style="margin:0 0 14px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="post" action="<?= base_url('store/register') ?>">
      <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
      <div class="st-field"><label>Full Name</label><input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required></div>
      <div class="st-field"><label>Mobile Number</label><input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="10-digit mobile number" required></div>
      <div class="st-field"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required></div>
      <div class="st-field"><label>Delivery Address</label><textarea name="address" rows="2" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea></div>
      <button type="submit" class="st-btn-primary st-btn-block">Send Verification Code</button>
    </form>

    <div style="margin-top:16px;font-size:.82rem;color:#666;text-align:center;">
      Already have an account? <a href="<?= base_url('store/login') ?>" style="color:var(--st-primary);font-weight:700;">Login</a>
    </div>
  </div>
</div>
</body>
</html>
