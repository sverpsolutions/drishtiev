<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#1a237e">
<title><?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?> — Create Account</title>
<link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/font-awesome.min.css') ?>">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
<?php include_once APPPATH.'views/portal/_theme.php'; echo portal_theme_vars($company ?? null); ?>
}
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
body { font-family: 'Poppins', sans-serif; background: var(--bg); min-height: 100vh; margin: 0; display: flex; flex-direction: column; }

.bb-reg-hero {
  background: linear-gradient(160deg, var(--gold-dark) 0%, var(--gold) 100%);
  padding: 36px 24px 56px;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.bb-reg-hero::before {
  content: ''; position: absolute; top: -30px; right: -30px;
  width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;
}
.bb-reg-logo {
  width: 66px; height: 66px; background: var(--primary);
  border-radius: 18px; display: flex; align-items: center;
  justify-content: center; font-size: 28px; margin: 0 auto 12px;
  box-shadow: 0 8px 24px rgba(26,35,126,0.3);
  animation: popIn 0.5s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes popIn { from{opacity:0;transform:scale(0.7)} to{opacity:1;transform:scale(1)} }
.bb-reg-hero h1 { color: var(--primary-dark); font-size: 1.45rem; font-weight: 800; margin: 0 0 4px; }
.bb-reg-hero p  { color: rgba(26,35,126,0.7); font-size: 0.8rem; margin: 0; }

.bb-reg-card {
  background: #fff;
  border-radius: 24px 24px 0 0;
  margin-top: -22px;
  flex: 1;
  padding: 26px 22px 40px;
  box-shadow: 0 -8px 30px rgba(26,35,126,0.1);
  animation: slideUp 0.4s ease;
}
@keyframes slideUp { from{transform:translateY(30px);opacity:0} to{transform:translateY(0);opacity:1} }

/* Step Progress */
.bb-steps {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
  margin-bottom: 22px;
}
.bb-step-dot {
  width: 28px; height: 28px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem;
  font-weight: 700;
  transition: all 0.3s;
}
.bb-step-dot.done   { background: var(--primary); color: #fff; }
.bb-step-dot.active { background: var(--gold); color: var(--primary-dark); box-shadow: 0 0 0 3px rgba(249,168,37,0.25); }
.bb-step-dot.todo   { background: #e0e0e0; color: #90a4ae; }
.bb-step-line { flex: 1; height: 2px; max-width: 40px; background: #e0e0e0; margin: 0 4px; }
.bb-step-line.done { background: var(--primary); }
.bb-step-labels { display: flex; justify-content: space-around; margin-bottom: 18px; }
.bb-step-lbl { font-size: 0.65rem; font-weight: 600; color: #90a4ae; text-align: center; flex: 1; }
.bb-step-lbl.active { color: var(--primary); }

.bb-form-group { margin-bottom: 13px; }
.bb-form-group label { display: block; font-size: 0.78rem; font-weight: 600; color: #37474f; margin-bottom: 5px; }
.bb-input {
  width: 100%; border: 1.5px solid #e0e0e0; border-radius: 10px;
  padding: 10px 14px; font-family: 'Poppins', sans-serif;
  font-size: 0.86rem; color: #1a1a2e; outline: none; background: #fafafa; transition: all 0.2s;
}
.bb-input:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(26,35,126,0.07); }

.bb-submit {
  width: 100%; border: none; border-radius: 12px;
  padding: 13px; font-family: 'Poppins', sans-serif;
  font-size: 0.9rem; font-weight: 700; cursor: pointer;
  transition: all 0.25s; margin-top: 6px;
}
.bb-submit-gold {
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  color: var(--primary-dark);
  box-shadow: 0 6px 18px rgba(249,168,37,0.32);
}
.bb-submit-gold:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(249,168,37,0.42); }

.bb-alert { border-radius: 10px; padding: 10px 14px; font-size: 0.8rem; font-weight: 500; margin-bottom: 14px; display: flex; align-items: flex-start; gap: 8px; }
.bb-alert-danger  { background: #fce4ec; color: #c62828; border-left: 3px solid #c62828; }
.bb-alert-info    { background: #e8eaf6; color: var(--primary); border-left: 3px solid var(--primary); }

.bb-login-link {
  text-align: center; margin-top: 16px;
  font-size: 0.78rem; color: #78909c;
}
.bb-login-link a { color: var(--primary); font-weight: 600; text-decoration: none; }
</style>
</head>
<body>

<div class="bb-reg-hero">
  <div class="bb-reg-logo" style="background: #3B2314; color: #FFF; box-shadow: 0 8px 24px rgba(59,35,20,0.3);">👤</div>
  <h1 style="color: #3B2314;">Create Account</h1>
  <p style="color: #5E3E26;">Join <?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?> — Shop & Save!</p>
</div>

<div class="bb-reg-card">

  <?php if (!empty($error)): ?>
  <div class="bb-alert bb-alert-danger"><i class="fa fa-exclamation-circle mt-1"></i><div><?= htmlspecialchars($error) ?></div></div>
  <?php endif; ?>

  <!-- Step Progress Bar -->
  <div class="bb-steps">
    <div class="bb-step-dot done">1</div>
    <div class="bb-step-line done"></div>
    <div class="bb-step-dot active">2</div>
    <div class="bb-step-line"></div>
    <div class="bb-step-dot todo">3</div>
  </div>
  <div class="bb-step-labels">
    <span class="bb-step-lbl">Details</span>
    <span class="bb-step-lbl active">Address</span>
    <span class="bb-step-lbl">Verify</span>
  </div>

  <div class="bb-alert bb-alert-info">
    <i class="fa fa-info-circle mt-1"></i>
    <div>All fields are required. A 6-digit OTP will be sent to your email to verify your account.</div>
  </div>

  <form method="post" action="<?= base_url('portal/register') ?>">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
    <div class="bb-form-group">
      <label>Full Name <span style="color:#c62828">*</span></label>
      <input type="text" name="name" class="bb-input" placeholder="e.g. Rahul Sharma"
             value="<?= htmlspecialchars($this->input->post('name') ?? '') ?>" required autofocus>
    </div>

    <div class="bb-form-group">
      <label>Mobile Number <span style="color:#c62828">*</span></label>
      <input type="tel" name="phone" class="bb-input" placeholder="e.g. 9876543210"
             value="<?= htmlspecialchars($this->input->post('phone') ?? '') ?>" required maxlength="15">
    </div>

    <div class="bb-form-group">
      <label>Email Address <span style="color:#c62828">*</span></label>
      <input type="email" name="email" class="bb-input" placeholder="e.g. you@example.com"
             value="<?= htmlspecialchars($this->input->post('email') ?? '') ?>" required>
    </div>

    <div class="bb-form-group">
      <label>Delivery Address <span style="color:#c62828">*</span></label>
      <textarea name="address" class="bb-input" rows="3"
                placeholder="House No., Street, Landmark, City, PIN Code..." required
                style="height: auto; resize: none;"><?= htmlspecialchars($this->input->post('address') ?? '') ?></textarea>
    </div>

    <button type="submit" class="bb-submit bb-submit-gold">
      <i class="fa fa-paper-plane mr-1"></i> Send Verification OTP
    </button>
  </form>

  <div class="bb-login-link">
    Already have an account? <a href="<?= base_url('portal/login') ?>">Sign In</a>
  </div>

</div>

<script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
