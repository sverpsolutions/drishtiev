<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#1a237e">
<title><?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?> — Customer Login</title>
<link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/font-awesome.min.css') ?>">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
<?php include_once APPPATH.'views/portal/_theme.php'; echo portal_theme_vars($company ?? null); ?>
}
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
body {
  font-family: 'Poppins', sans-serif;
  background: var(--bg);
  min-height: 100vh;
  margin: 0;
  display: flex;
  flex-direction: column;
}

/* Hero splash area */
.bb-login-hero {
  background: linear-gradient(160deg, var(--primary-dark) 0%, var(--primary) 50%, var(--primary-light) 100%);
  padding: 40px 24px 60px;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.bb-login-hero::before {
  content: '';
  position: absolute;
  top: -40px; right: -40px;
  width: 180px; height: 180px;
  background: rgba(249,168,37,0.08);
  border-radius: 50%;
}
.bb-login-hero::after {
  content: '';
  position: absolute;
  bottom: -20px; left: -30px;
  width: 120px; height: 120px;
  background: rgba(255,255,255,0.05);
  border-radius: 50%;
}
.bb-hero-logo {
  width: 72px; height: 72px;
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  border-radius: 20px;
  display: flex; align-items: center; justify-content: center;
  font-size: 32px;
  margin: 0 auto 14px;
  box-shadow: 0 8px 24px rgba(249,168,37,0.4);
  animation: popIn 0.5s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes popIn {
  from { opacity:0; transform: scale(0.7); }
  to   { opacity:1; transform: scale(1); }
}
.bb-hero-title {
  color: #fff;
  font-size: 1.6rem;
  font-weight: 800;
  margin: 0 0 4px;
  letter-spacing: -0.5px;
}
.bb-hero-title span { color: var(--gold); }
.bb-hero-sub {
  color: rgba(255,255,255,0.75);
  font-size: 0.82rem;
  margin: 0;
}

/* Main card */
.bb-login-card {
  background: #fff;
  border-radius: 24px 24px 0 0;
  margin-top: -24px;
  flex: 1;
  padding: 28px 24px 40px;
  position: relative;
  z-index: 2;
  box-shadow: 0 -8px 30px rgba(26,35,126,0.12);
  animation: slideUp 0.4s ease;
}
@keyframes slideUp {
  from { transform: translateY(30px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

/* Tab switcher */
.bb-tabs {
  display: flex;
  background: var(--bg);
  border-radius: 12px;
  padding: 4px;
  margin-bottom: 22px;
}
.bb-tab {
  flex: 1;
  border: none;
  background: transparent;
  border-radius: 8px;
  padding: 8px 6px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.78rem;
  font-weight: 600;
  color: #78909c;
  cursor: pointer;
  transition: all 0.2s;
}
.bb-tab.active {
  background: #fff;
  color: var(--primary);
  box-shadow: 0 2px 8px rgba(26,35,126,0.12);
}

/* Form elements */
.bb-form-group {
  margin-bottom: 14px;
}
.bb-form-group label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  color: #37474f;
  margin-bottom: 6px;
}
.bb-input {
  width: 100%;
  border: 1.5px solid #e0e0e0;
  border-radius: 10px;
  padding: 11px 14px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.88rem;
  color: #1a1a2e;
  transition: all 0.2s;
  outline: none;
  background: #fafafa;
}
.bb-input:focus {
  border-color: var(--primary);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(26,35,126,0.07);
}

/* Info hint box */
.bb-hint {
  background: #e8eaf6;
  border-radius: 8px;
  padding: 10px 12px;
  font-size: 0.75rem;
  color: var(--primary);
  margin-bottom: 14px;
  line-height: 1.5;
}

/* Submit button */
.bb-submit {
  width: 100%;
  border: none;
  border-radius: 12px;
  padding: 13px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.92rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s;
  margin-top: 4px;
}
.bb-submit-primary {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: #fff;
  box-shadow: 0 6px 18px rgba(26,35,126,0.28);
}
.bb-submit-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(26,35,126,0.38);
}
.bb-submit-gold {
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  color: var(--primary-dark);
  box-shadow: 0 6px 18px rgba(249,168,37,0.32);
}
.bb-submit-gold:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(249,168,37,0.42);
}

/* Divider */
.bb-divider {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 18px 0;
  color: #b0bec5;
  font-size: 0.75rem;
  font-weight: 500;
}
.bb-divider::before, .bb-divider::after {
  content: ''; flex: 1; height: 1px; background: #e0e0e0;
}

/* Register link area */
.bb-register-area {
  text-align: center;
  padding: 14px;
  background: linear-gradient(135deg, #e8eaf6, #fff9c4);
  border-radius: 12px;
  border: 1.5px dashed #c5cae9;
}
.bb-register-area p {
  font-size: 0.78rem;
  color: #37474f;
  margin: 0 0 8px;
}
.bb-register-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  color: var(--primary-dark);
  text-decoration: none;
  border-radius: 24px;
  padding: 9px 22px;
  font-size: 0.82rem;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(249,168,37,0.3);
  transition: all 0.2s;
}
.bb-register-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(249,168,37,0.4);
  color: var(--primary-dark);
  text-decoration: none;
}

/* Alert */
.bb-alert {
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 0.8rem;
  font-weight: 500;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.bb-alert-danger  { background: #fce4ec; color: #c62828; border-left: 3px solid #c62828; }
.bb-alert-success { background: #e8f5e9; color: #2e7d32; border-left: 3px solid #2e7d32; }
</style>
</head>
<body>

<!-- Hero Splash -->
<div class="bb-login-hero">
  <div class="bb-hero-logo" style="background: #FFF; color: #3B2314; box-shadow: 0 8px 24px rgba(59,35,20,0.3); overflow: hidden; display: flex; align-items: center; justify-content: center; padding: 10px; border: 2px solid #F6CD3C;">
    <?php 
    $logo_src = !empty($company['company_logo']) ? base_url($company['company_logo']) : base_url('assets/uploads/logo_sandalhouse_cropped.png');
    ?>
    <img src="<?= $logo_src ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
  </div>
  <h1 class="bb-hero-title" style="font-family: 'Georgia', serif; font-weight: 800;"><?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?></h1>
  <p class="bb-hero-sub"><?= htmlspecialchars($company['company_tagline'] ?? 'The Aroma of Holy Spirit') ?></p>
</div>

<!-- Main Card -->
<div class="bb-login-card">

  <?php if ($this->session->flashdata('success')): ?>
  <div class="bb-alert bb-alert-success"><i class="fa fa-check-circle"></i><?= htmlspecialchars($this->session->flashdata('success')) ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
  <div class="bb-alert bb-alert-danger"><i class="fa fa-exclamation-circle"></i><?= htmlspecialchars($this->session->flashdata('error')) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
  <div class="bb-alert bb-alert-danger"><i class="fa fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div style="font-size:1.05rem; font-weight:700; color:#3B2314; margin-bottom:16px; text-align:center;">Sign in to your Account</div>

  <!-- Tab Switcher -->
  <div class="bb-tabs" role="tablist">
    <button class="bb-tab <?= isset($_GET['tab']) && $_GET['tab']==='email' ? '' : 'active' ?>" id="tab-phone" onclick="switchTab('phone')">
      📱 Phone Login
    </button>
    <button class="bb-tab <?= isset($_GET['tab']) && $_GET['tab']==='email' ? 'active' : '' ?>" id="tab-email" onclick="switchTab('email')">
      ✉️ Email OTP
    </button>
  </div>

  <!-- TAB: Phone Password -->
  <div id="pane-phone" style="<?= isset($_GET['tab']) && $_GET['tab']==='email' ? 'display:none' : '' ?>">
    <div class="bb-hint">
      🔑 <strong>Username:</strong> Your 10-digit mobile number<br>
      🔐 <strong>Password:</strong> Last 4 digits of your mobile
    </div>
    <form method="post" action="<?= base_url('portal/login') ?>">
      <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
      <div class="bb-form-group">
        <label>Mobile Number</label>
        <input type="tel" name="phone" class="bb-input" placeholder="e.g. 9876543210"
               value="<?= htmlspecialchars($prefill_phone ?? '') ?>" required maxlength="15">
      </div>
      <div class="bb-form-group">
        <label>Password (Last 4 digits)</label>
        <input type="password" name="password" class="bb-input" placeholder="e.g. 3210" required maxlength="4" pattern="\d{4}">
      </div>
      <button type="submit" class="bb-submit bb-submit-primary">
        <i class="fa fa-sign-in-alt mr-1"></i> Sign In
      </button>
    </form>
  </div>

  <!-- TAB: Email OTP -->
  <div id="pane-email" style="<?= isset($_GET['tab']) && $_GET['tab']==='email' ? '' : 'display:none' ?>">
    <div class="bb-hint">
      ✉️ Enter your registered email. We'll send a 6-digit OTP to sign you in securely.
    </div>
    <form method="post" action="<?= base_url('portal/send_login_otp') ?>">
      <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
      <div class="bb-form-group">
        <label>Email Address</label>
        <input type="email" name="email" class="bb-input" placeholder="e.g. you@example.com" required autofocus>
      </div>
      <button type="submit" class="bb-submit bb-submit-gold">
        <i class="fa fa-paper-plane mr-1"></i> Send OTP to Email
      </button>
    </form>
  </div>

  <!-- Divider -->
  <div class="bb-divider">New Customer?</div>

  <!-- Register Area -->
  <div class="bb-register-area">
    <p>First time at <?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?>?<br>Register & start shopping in 2 minutes!</p>
    <a href="<?= base_url('portal/register') ?>" class="bb-register-btn">
      <i class="fa fa-user-plus"></i> Create Account
    </a>
  </div>

  <!-- Guest Browsing CTA -->
  <div style="text-align:center; margin-top:14px; padding-top:14px; border-top:1px solid rgba(0,0,0,0.06);">
    <a href="<?= base_url('portal/products') ?>" style="display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg, var(--gold-light), #fff); border:1.5px solid var(--gold); border-radius:24px; padding:10px 24px; font-size:0.82rem; font-weight:700; color:var(--primary); text-decoration:none; transition:all 0.2s; box-shadow:0 2px 8px rgba(249,168,37,0.15);">
      🛍️ Browse Products as Guest
    </a>
    <p style="font-size:0.68rem; color:#90a4ae; margin:8px 0 0;">No account needed — shop and add to cart freely!</p>
  </div>

  <div style="text-align:center; margin-top:18px;">
    <a href="<?= base_url('login') ?>" style="font-size:0.72rem; color:#90a4ae; text-decoration:none;">
      ← Admin / Staff Login
    </a>
  </div>

</div>

<script>
function switchTab(tab) {
  var phonePaneEl = document.getElementById('pane-phone');
  var emailPaneEl = document.getElementById('pane-email');
  var phoneTabBtn = document.getElementById('tab-phone');
  var emailTabBtn = document.getElementById('tab-email');
  if (tab === 'phone') {
    phonePaneEl.style.display = ''; emailPaneEl.style.display = 'none';
    phoneTabBtn.classList.add('active'); emailTabBtn.classList.remove('active');
  } else {
    emailPaneEl.style.display = ''; phonePaneEl.style.display = 'none';
    emailTabBtn.classList.add('active'); phoneTabBtn.classList.remove('active');
  }
}
window.addEventListener('DOMContentLoaded', function() {
  var params = new URLSearchParams(window.location.search);
  if (params.get('tab') === 'email') switchTab('email');
});
</script>
</body>
</html>
