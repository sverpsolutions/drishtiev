<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#3B2314">
<title><?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?> — Shop Online</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
<?php include_once APPPATH.'views/portal/_theme.php'; echo portal_theme_vars($company ?? null); ?>
}
* { box-sizing: border-box; margin: 0; padding: 0; -webkit-tap-highlight-color: transparent; }
body {
  font-family: 'Poppins', sans-serif;
  background: var(--primary-dark);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  overflow: hidden;
}

/* Background animation */
.bb-scan-bg {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: linear-gradient(160deg, var(--primary-dark) 0%, var(--primary) 50%, #4A2D1C 100%);
  z-index: 0;
}
.bb-scan-orb {
  position: fixed;
  border-radius: 50%;
  background: rgba(249,168,37,0.08);
  animation: orbFloat 6s ease-in-out infinite;
}
.orb1 { width: 300px; height: 300px; top: -80px; right: -80px; animation-delay: 0s; }
.orb2 { width: 200px; height: 200px; bottom: 100px; left: -60px; animation-delay: 2s; }
.orb3 { width: 150px; height: 150px; top: 40%; right: 20px; animation-delay: 4s; }
@keyframes orbFloat {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-20px) scale(1.05); }
}

/* Main content */
.bb-scan-hero {
  position: relative; z-index: 2;
  width: 100%; max-width: 480px;
  padding: 40px 20px;
  text-align: center;
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.bb-scan-logo {
  width: 90px; height: 90px;
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  border-radius: 24px;
  display: flex; align-items: center; justify-content: center;
  font-size: 40px; margin: 0 auto 20px;
  box-shadow: 0 12px 36px rgba(246,205,60,0.4);
  animation: popIn 0.8s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes popIn { from{opacity:0;transform:scale(0.5)} to{opacity:1;transform:scale(1)} }
.bb-scan-brand {
  color: #fff; font-size: 2rem; font-weight: 800;
  letter-spacing: -0.5px; margin-bottom: 4px;
  animation: fadeUp 0.6s ease 0.3s both;
}
.bb-scan-brand span { color: var(--gold); }
.bb-scan-tagline {
  color: rgba(255,255,255,0.65); font-size: 0.88rem;
  margin-bottom: 40px;
  animation: fadeUp 0.6s ease 0.5s both;
}
@keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

/* Features */
.bb-scan-features {
  display: flex; gap: 16px; margin-bottom: 40px;
  animation: fadeUp 0.6s ease 0.7s both;
}
.bb-feat {
  flex: 1; background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 14px; padding: 14px 10px;
  text-align: center;
}
.bb-feat-icon { font-size: 1.6rem; display: block; margin-bottom: 6px; }
.bb-feat-text { font-size: 0.65rem; color: rgba(255,255,255,0.7); font-weight: 600; line-height: 1.3; }

/* Bottom Card */
.bb-scan-card {
  position: relative; z-index: 2;
  width: 100%; max-width: 480px;
  background: #fff;
  border-radius: 28px 28px 0 0;
  padding: 28px 24px 40px;
  box-shadow: 0 -12px 40px rgba(59,35,20,0.2);
  animation: slideUp 0.5s ease 0.8s both;
}
@keyframes slideUp { from{transform:translateY(60px);opacity:0} to{transform:translateY(0);opacity:1} }
.bb-scan-card-title {
  font-size: 1.05rem; font-weight: 800; color: #1a237e;
  margin-bottom: 6px; text-align: center;
}
.bb-scan-card-sub {
  font-size: 0.78rem; color: #78909c; text-align: center; margin-bottom: 22px;
}

/* CTA Buttons */
.bb-cta-primary {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  width: 100%; border: none; border-radius: 14px;
  padding: 15px; font-family: 'Poppins', sans-serif;
  font-size: 0.96rem; font-weight: 700; cursor: pointer;
  text-decoration: none;
  background: linear-gradient(135deg, #f9a825, #f57f17);
  color: #1a237e;
  box-shadow: 0 8px 24px rgba(249,168,37,0.4);
  transition: all 0.25s;
  margin-bottom: 12px;
}
.bb-cta-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(249,168,37,0.5); text-decoration:none; color:#1a237e; }

.bb-cta-secondary {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  width: 100%; border: 1.5px solid var(--gold-dark); border-radius: 14px;
  padding: 13px; font-family: 'Poppins', sans-serif;
  font-size: 0.92rem; font-weight: 600; cursor: pointer;
  text-decoration: none;
  background: var(--bg); color: var(--primary);
  transition: all 0.2s;
  margin-bottom: 18px;
}
.bb-cta-secondary:hover { background: var(--gold); text-decoration:none; color:var(--primary); }

.bb-scan-trust {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  font-size: 0.7rem; color: #90a4ae;
}
.bb-scan-trust-dot {
  width: 4px; height: 4px; border-radius: 50%; background: var(--gold);
}
</style>
</head>
<body>

<div class="bb-scan-bg">
  <div class="bb-scan-orb orb1"></div>
  <div class="bb-scan-orb orb2"></div>
  <div class="bb-scan-orb orb3"></div>
</div>

<div class="bb-scan-hero">
  <div class="bb-scan-logo" style="background: #FFF; display: flex; align-items: center; justify-content: center; overflow: hidden; padding: 4px; box-shadow: 0 12px 36px rgba(59,35,20,0.15); border: 2.5px solid var(--gold); width: 90px; height: 90px; border-radius: 24px; margin: 0 auto 20px; animation: popIn 0.8s cubic-bezier(0.34,1.56,0.64,1);">
    <?php 
    $logo_src = !empty($company['company_logo']) ? base_url($company['company_logo']) : base_url('assets/uploads/logo_sandalhouse_cropped.png');
    ?>
    <img src="<?= $logo_src ?>" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 18px;">
  </div>
  <?php
  $comp_name = $company['company_name'] ?? 'Sandal House';
  $parts = explode(' ', $comp_name, 2);
  if (count($parts) == 2) {
      $brand_html = htmlspecialchars($parts[0]) . '<span>' . htmlspecialchars($parts[1]) . '</span>';
  } else {
      $brand_html = htmlspecialchars($comp_name);
  }
  ?>
  <h1 class="bb-scan-brand"><?= $brand_html ?></h1>
  <p class="bb-scan-tagline">Your Premium Local Shopping App</p>

  <div class="bb-scan-features">
    <div class="bb-feat">
      <span class="bb-feat-icon">🛍️</span>
      <div class="bb-feat-text">1000+ Products</div>
    </div>
    <div class="bb-feat">
      <span class="bb-feat-icon">🚀</span>
      <div class="bb-feat-text">Fast Delivery</div>
    </div>
    <div class="bb-feat">
      <span class="bb-feat-icon">💰</span>
      <div class="bb-feat-text">Best Prices</div>
    </div>
  </div>
</div>

<div class="bb-scan-card">
  <div class="bb-scan-card-title">Welcome! 👋</div>
  <div class="bb-scan-card-sub">Sign in or create an account to start shopping and get exclusive deals!</div>

  <a href="<?= base_url('portal/register') ?>" class="bb-cta-primary">
    🎉 Create Free Account
  </a>

  <a href="<?= base_url('portal/login') ?>" class="bb-cta-secondary">
    🔑 Already have an account? Sign In
  </a>

  <div class="bb-scan-trust">
    <span>🛡️ 100% Secure</span>
    <div class="bb-scan-trust-dot"></div>
    <span>✅ Verified Products</span>
    <div class="bb-scan-trust-dot"></div>
    <span>🔒 OTP Login</span>
  </div>
</div>

</body>
</html>
