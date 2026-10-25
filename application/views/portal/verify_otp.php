<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#3B2314">
<title><?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?> — Verify OTP</title>
<link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/font-awesome.min.css') ?>">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
<?php include_once APPPATH.'views/portal/_theme.php'; echo portal_theme_vars($company ?? null); ?>
}
* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
body { font-family: 'Poppins', sans-serif; background: var(--bg); min-height: 100vh; margin: 0; display: flex; flex-direction: column; }

.bb-otp-hero {
  background: linear-gradient(160deg, var(--primary-dark) 0%, var(--primary) 60%, #4A2D1C 100%);
  padding: 36px 24px 56px;
  text-align: center;
}
.bb-otp-icon-wrap {
  width: 80px; height: 80px;
  background: rgba(255,255,255,0.12);
  border: 2px solid rgba(246,205,60,0.4);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 36px;
  margin: 0 auto 14px;
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(246,205,60,0.3); }
  50%       { box-shadow: 0 0 0 12px rgba(246,205,60,0); }
}
.bb-otp-hero h1 { color: #fff; font-size: 1.35rem; font-weight: 700; margin: 0 0 4px; }
.bb-otp-hero p  { color: rgba(255,255,255,0.72); font-size: 0.8rem; margin: 0; }

.bb-otp-card {
  background: #fff;
  border-radius: 24px 24px 0 0;
  margin-top: -22px;
  flex: 1;
  padding: 28px 22px 40px;
  box-shadow: 0 -8px 30px rgba(59,35,20,0.08);
  animation: slideUp 0.4s ease;
}
@keyframes slideUp { from{transform:translateY(30px);opacity:0} to{transform:translateY(0);opacity:1} }

.bb-email-chip {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--gold-light); border-radius: 24px;
  padding: 6px 16px; font-size: 0.8rem;
  font-weight: 600; color: var(--primary);
  margin-bottom: 22px;
}

/* 6-box OTP input */
.bb-otp-boxes {
  display: flex; gap: 8px; justify-content: center; margin-bottom: 22px;
}
.bb-otp-box {
  width: 44px; height: 52px;
  border: 2px solid #e0e0e0;
  border-radius: 10px;
  text-align: center;
  font-size: 1.4rem;
  font-weight: 700;
  font-family: 'Poppins', sans-serif;
  color: var(--primary);
  outline: none;
  transition: all 0.2s;
  background: #fafafa;
}
.bb-otp-box:focus {
  border-color: var(--gold);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(246,205,60,0.2);
  transform: scale(1.05);
}
.bb-otp-box.filled {
  border-color: var(--primary);
  background: var(--gold-light);
}

/* Hidden combined OTP field */
#otpHidden { display: none; }

.bb-submit {
  width: 100%; border: none; border-radius: 12px;
  padding: 13px; font-family: 'Poppins', sans-serif;
  font-size: 0.9rem; font-weight: 700; cursor: pointer;
  transition: all 0.25s;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: #fff;
  box-shadow: 0 6px 18px rgba(59,35,20,0.2);
}
.bb-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(59,35,20,0.3); }
.bb-submit:disabled { background: #b0bec5; box-shadow: none; cursor: not-allowed; transform: none; }

/* Timer & resend */
.bb-timer-row { text-align: center; margin-top: 16px; font-size: 0.78rem; color: #78909c; }
.bb-timer { font-weight: 700; color: var(--primary); }
.bb-resend-btn { background: none; border: none; color: var(--gold-dark); font-weight: 700; font-size: 0.78rem; cursor: pointer; font-family: 'Poppins', sans-serif; padding: 0; }
.bb-resend-btn:disabled { color: #b0bec5; cursor: not-allowed; }

.bb-alert { border-radius: 10px; padding: 10px 14px; font-size: 0.8rem; font-weight: 500; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
.bb-alert-danger  { background: #fce4ec; color: #c62828; border-left: 3px solid #c62828; }
.bb-alert-success { background: #e8f5e9; color: #2e7d32; border-left: 3px solid #2e7d32; }

.bb-back-link { text-align: center; margin-top: 18px; font-size: 0.78rem; }
.bb-back-link a { color: #78909c; text-decoration: none; }
.bb-back-link a:hover { color: var(--primary); }
</style>
</head>
<body>

<div class="bb-otp-hero">
  <div class="bb-otp-icon-wrap">📧</div>
  <h1>Verify Your Email</h1>
  <p>We sent a 6-digit code to your inbox</p>
</div>

<div class="bb-otp-card">

  <?php if ($this->session->flashdata('success')): ?>
  <div class="bb-alert bb-alert-success"><i class="fa fa-check-circle"></i><?= htmlspecialchars($this->session->flashdata('success')) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
  <div class="bb-alert bb-alert-danger"><i class="fa fa-exclamation-circle"></i><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div style="text-align:center; margin-bottom:6px; font-size:0.82rem; color:#37474f;">Code sent to</div>
  <div style="text-align:center; margin-bottom:22px;">
    <span class="bb-email-chip"><i class="fa fa-envelope"></i><?= htmlspecialchars($email) ?></span>
  </div>

  <form method="post" action="<?= base_url('portal/verify_otp') ?>" id="otpForm">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
    <input type="hidden" name="otp" id="otpHidden">

    <div class="bb-otp-boxes" id="otpBoxes">
      <input class="bb-otp-box" type="text" inputmode="numeric" maxlength="1" data-index="0">
      <input class="bb-otp-box" type="text" inputmode="numeric" maxlength="1" data-index="1">
      <input class="bb-otp-box" type="text" inputmode="numeric" maxlength="1" data-index="2">
      <input class="bb-otp-box" type="text" inputmode="numeric" maxlength="1" data-index="3">
      <input class="bb-otp-box" type="text" inputmode="numeric" maxlength="1" data-index="4">
      <input class="bb-otp-box" type="text" inputmode="numeric" maxlength="1" data-index="5">
    </div>

    <button type="submit" class="bb-submit" id="verifyBtn" disabled>
      <i class="fa fa-check-circle mr-1"></i> Verify & Sign In
    </button>
  </form>

  <div class="bb-timer-row">
    <span id="timerText">Resend OTP in <span class="bb-timer" id="countdown">10:00</span></span>
    <button class="bb-resend-btn" id="resendBtn" disabled onclick="resendOtp()" style="display:none;">
      Resend OTP
    </button>
  </div>

  <div class="bb-back-link">
    <a href="<?= base_url('portal/login') ?>"><i class="fa fa-arrow-left mr-1"></i> Back to Login</a>
  </div>

</div>

<script>
/* ── OTP box auto-advance ── */
var boxes = document.querySelectorAll('.bb-otp-box');
var verifyBtn = document.getElementById('verifyBtn');
var otpHidden = document.getElementById('otpHidden');

boxes.forEach(function(box, idx) {
  box.addEventListener('input', function(e) {
    var val = e.target.value.replace(/\D/g,'');
    e.target.value = val;
    if (val) {
      e.target.classList.add('filled');
      if (idx < 5) boxes[idx+1].focus();
    } else {
      e.target.classList.remove('filled');
    }
    checkAllFilled();
  });

  box.addEventListener('keydown', function(e) {
    if (e.key === 'Backspace' && !e.target.value && idx > 0) {
      boxes[idx-1].focus();
      boxes[idx-1].value = '';
      boxes[idx-1].classList.remove('filled');
    }
  });

  box.addEventListener('paste', function(e) {
    e.preventDefault();
    var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'');
    pasted.split('').forEach(function(ch, i) {
      if (i < 6) { boxes[i].value = ch; boxes[i].classList.add('filled'); }
    });
    if (pasted.length >= 6) boxes[5].focus();
    checkAllFilled();
  });
});

function checkAllFilled() {
  var allFilled = true;
  var otp = '';
  boxes.forEach(function(b) {
    if (!b.value) allFilled = false;
    otp += b.value;
  });
  verifyBtn.disabled = !allFilled;
  otpHidden.value = otp;
}

/* Auto focus first box */
boxes[0].focus();

/* ── Countdown Timer (10 min = 600s) ── */
var totalSecs = 600;
var timerEl = document.getElementById('countdown');
var timerTextEl = document.getElementById('timerText');
var resendBtn = document.getElementById('resendBtn');

var timerInterval = setInterval(function() {
  totalSecs--;
  var m = Math.floor(totalSecs / 60);
  var s = totalSecs % 60;
  timerEl.textContent = m + ':' + (s < 10 ? '0' : '') + s;
  if (totalSecs <= 0) {
    clearInterval(timerInterval);
    timerTextEl.style.display = 'none';
    resendBtn.style.display = 'inline';
    resendBtn.disabled = false;
  }
}, 1000);

function resendOtp() {
  window.location.href = '<?= base_url('portal/login?tab=email') ?>';
}
</script>
</body>
</html>
