<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#0DB14B">
<title><?= htmlspecialchars($company['company_name'] ?? 'Store') ?> — Login</title>
<link rel="stylesheet" href="<?= base_url('assets/css/font-awesome.min.css') ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/store.css') ?>">
<style>
:root { <?php include_once APPPATH . 'views/store/_theme.php'; echo store_theme_vars($company ?? null); ?> }
.st-body-auth { font-family: 'Inter', sans-serif; background: var(--st-bg); min-height: 100vh; margin: 0; }
.st-auth-hero { background: linear-gradient(160deg, var(--st-primary-dark), var(--st-primary)); padding: 44px 24px 60px; text-align: center; color: #fff; }
.st-auth-hero h1 { font-family: 'Poppins', sans-serif; font-size: 1.3rem; font-weight: 800; margin: 10px 0 4px; }
.st-tabs { display: flex; gap: 6px; margin-bottom: 18px; background: #f0f0f0; border-radius: 12px; padding: 4px; }
.st-tab { flex: 1; text-align: center; padding: 9px; border-radius: 9px; font-size: .82rem; font-weight: 700; cursor: pointer; color: #888; }
.st-tab.active { background: #fff; color: var(--st-primary); box-shadow: 0 1px 4px rgba(0,0,0,.1); }
</style>
</head>
<body class="st-body-auth">

<div class="st-auth-hero">
  <?php if (!empty($company['company_logo'])): ?><img src="<?= base_url($company['company_logo']) ?>" style="height:48px;background:#fff;border-radius:10px;padding:4px;"><?php endif; ?>
  <h1>Welcome Back</h1>
  <div style="font-size:.8rem;opacity:.9;">Login to continue shopping</div>
</div>

<div class="st-auth-wrap" style="margin-top:-30px;">
  <div class="st-auth-card">
    <?php if (!empty($error)): ?><div class="st-flash st-flash-danger" style="margin:0 0 14px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($this->session->flashdata('success')): ?><div class="st-flash st-flash-success" style="margin:0 0 14px;"><?= htmlspecialchars($this->session->flashdata('success')) ?></div><?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?><div class="st-flash st-flash-danger" style="margin:0 0 14px;"><?= htmlspecialchars($this->session->flashdata('error')) ?></div><?php endif; ?>

    <div class="st-tabs">
      <div class="st-tab active" onclick="stShowTab('pw')" id="stTabPw">Mobile &amp; Password</div>
      <div class="st-tab" onclick="stShowTab('otp')" id="stTabOtp">Email OTP</div>
    </div>

    <form method="post" action="<?= base_url('store/login') ?>" id="stLoginPwForm">
      <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
      <div class="st-field" style="text-align:left;">
        <label>Mobile Number</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($prefill_phone ?? '') ?>" placeholder="10-digit mobile number" required>
      </div>
      <div class="st-field" style="text-align:left;">
        <label>Password</label>
        <input type="password" name="password" placeholder="Last 4 digits of your mobile" required>
        <small style="color:#999;font-size:.7rem;">New here? Your password is the last 4 digits of your registered mobile number.</small>
      </div>
      <button type="submit" class="st-btn-primary st-btn-block">Login</button>
    </form>

    <form method="post" action="<?= base_url('store/send_login_otp') ?>" id="stLoginOtpForm" style="display:none;">
      <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
      <div class="st-field" style="text-align:left;">
        <label>Registered Email</label>
        <input type="email" name="email" placeholder="you@example.com" required>
      </div>
      <button type="submit" class="st-btn-primary st-btn-block">Send OTP</button>
    </form>

    <div style="margin-top:16px;font-size:.82rem;color:#666;">
      New here? <a href="<?= base_url('store/register') ?>" style="color:var(--st-primary);font-weight:700;">Create an account</a>
    </div>
  </div>
</div>

<script>
function stShowTab(tab) {
  document.getElementById('stTabPw').classList.toggle('active', tab === 'pw');
  document.getElementById('stTabOtp').classList.toggle('active', tab === 'otp');
  document.getElementById('stLoginPwForm').style.display = tab === 'pw' ? 'block' : 'none';
  document.getElementById('stLoginOtpForm').style.display = tab === 'otp' ? 'block' : 'none';
}
<?php if (($_GET['tab'] ?? '') === 'email'): ?>stShowTab('otp');<?php endif; ?>
</script>
</body>
</html>
