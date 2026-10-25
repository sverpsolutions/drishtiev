<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#3B2314">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($company['company_name'] ?? 'Discount Store') ?>">
<link rel="manifest" href="<?= base_url('manifest.json') ?>">
<link rel="apple-touch-icon" href="<?= base_url($company['company_logo'] ?? 'assets/uploads/logo_1782204427.png') ?>">
<title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — ' . htmlspecialchars($company['company_name'] ?? 'Sandal House') : htmlspecialchars($company['company_name'] ?? 'Sandal House') ?></title>
<link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/font-awesome.min.css') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════════════
   SANDAL HOUSE — RICH BROWN + GOLD DESIGN SYSTEM
   ═══════════════════════════════════════════════════════════ */
:root {
<?php include_once APPPATH.'views/portal/_theme.php'; echo portal_theme_vars($company ?? null); ?>
  --success:       #2e7d32;
  --danger:        #c62828;
  --text-dark:     #2D1C13;
  --text-mid:      #4E3629;
  --text-muted:    #8C7466;
  --bg-white:      #ffffff;
  --card-shadow:   0 2px 12px rgba(59,35,20,0.06);
  --card-radius:   14px;
  --nav-height:    60px;
  --bottom-nav:    64px;
  --transition:    all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

* { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
html { scroll-behavior: smooth; overflow-x: hidden; }
body {
  font-family: 'Poppins', 'Segoe UI', sans-serif;
  background: var(--bg-page);
  color: var(--text-dark);
  margin: 0;
  padding: 0;
  overflow-x: hidden;
  padding-top: var(--nav-height);
  padding-bottom: calc(var(--bottom-nav) + 10px);
  min-height: 100vh;
  -webkit-font-smoothing: antialiased;
}

/* ── Top Navigation Bar ───────────────────────────────────── */
.bb-topbar {
  position: fixed;
  top: 0; left: 0; right: 0;
  height: var(--nav-height);
  background: #fff;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 10px 14px;
  z-index: 999;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.bb-logo {
  display: flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  flex-shrink: 0;
}
.bb-logo-icon {
  width: 32px; height: 32px;
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px;
  box-shadow: 0 2px 8px rgba(249,168,37,0.4);
}
.bb-logo-text {
  color: var(--text-dark);
  font-size: 1rem;
  font-weight: 800;
  line-height: 1;
  letter-spacing: -0.3px;
}
.bb-logo-text span { color: var(--primary); }

.bb-search-bar {
  width: 100%;
  background: #f4f6f8;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  display: flex;
  align-items: center;
  padding: 0 12px;
  height: 44px;
  cursor: text;
  transition: var(--transition);
}
.bb-search-bar:hover, .bb-search-bar:focus-within {
  background: #fff;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
}
.bb-search-bar input {
  background: transparent;
  border: none;
  outline: none;
  color: #1a202c;
  font-family: 'Poppins', sans-serif;
  font-size: 0.95rem;
  font-weight: 500;
  width: 100%;
  padding: 0;
}
.bb-search-bar input::placeholder { color: #a0aec0; font-weight: 400; }
.bb-search-bar .bb-search-submit {
  background: transparent;
  border: none;
  color: #718096;
  font-size: 1.1rem;
  width: 28px; height: 28px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  padding: 0;
}
.bb-search-bar .bb-search-submit:hover { color: var(--primary); }

.bb-topbar-icons {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}
.bb-icon-btn {
  position: relative;
  width: 38px; height: 38px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%;
  background: #f4f6f8;
  color: #2d3748;
  text-decoration: none;
  font-size: 1.05rem;
  transition: var(--transition);
}
.bb-icon-btn:hover { background: #e2e8f0; color: var(--primary); }
.bb-cart-badge {
  position: absolute;
  top: -2px; right: -2px;
  min-width: 18px; height: 18px;
  background: #e53e3e;
  color: #fff;
  font-size: 0.65rem;
  font-weight: 800;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  padding: 0 4px;
  line-height: 1;
  border: 2px solid #fff;
}

/* ── Bottom Navigation Bar ────────────────────────────────── */
.bb-bottom-nav {
  position: fixed;
  bottom: 0; left: 0; right: 0;
  height: var(--bottom-nav);
  background: var(--bg-white);
  border-top: 1px solid rgba(26,35,126,0.1);
  display: flex;
  align-items: stretch;
  z-index: 998;
  box-shadow: 0 -4px 20px rgba(26,35,126,0.1);
}
.bb-nav-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3px;
  text-decoration: none;
  color: var(--text-muted);
  font-size: 0.6rem;
  font-weight: 600;
  transition: var(--transition);
  padding: 6px 0;
  position: relative;
  border-top: 2.5px solid transparent;
}
.bb-nav-item.active {
  color: var(--primary);
  border-top-color: var(--gold);
}
.bb-nav-item i { font-size: 1.1rem; line-height: 1; }
.bb-nav-item.active i { color: var(--primary); }
.bb-nav-cart-badge {
  position: absolute;
  top: 4px; right: calc(50% - 18px);
  min-width: 15px; height: 15px;
  background: var(--gold);
  color: var(--primary-dark);
  font-size: 0.55rem;
  font-weight: 800;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  padding: 0 3px;
}

/* ── Page Wrapper ─────────────────────────────────────────── */
.bb-page { padding: 12px; max-width: 600px; margin: 0 auto; }
.bb-page-wide { padding: 12px; max-width: 900px; margin: 0 auto; }

/* ── Cards ────────────────────────────────────────────────── */
.bb-card {
  background: var(--bg-white);
  border-radius: var(--card-radius);
  box-shadow: var(--card-shadow);
  overflow: hidden;
  margin-bottom: 14px;
}
.bb-card-header {
  padding: 14px 16px 10px;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.bb-section-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--text-dark);
  display: flex;
  align-items: center;
  gap: 6px;
}
.bb-section-title::before {
  content: '';
  display: inline-block;
  width: 3px; height: 16px;
  background: linear-gradient(var(--gold), var(--primary));
  border-radius: 2px;
}
.bb-view-all {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--primary);
  text-decoration: none;
}
.bb-view-all:hover { color: var(--gold-dark); }

/* ── Buttons ──────────────────────────────────────────────── */
.bb-btn-primary {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 10px 20px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  box-shadow: 0 4px 14px rgba(26,35,126,0.25);
}
.bb-btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(26,35,126,0.35);
}
.bb-btn-gold {
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  color: var(--primary-dark);
  border: none;
  border-radius: 10px;
  padding: 10px 20px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.88rem;
  font-weight: 700;
  cursor: pointer;
  transition: var(--transition);
  box-shadow: 0 4px 14px rgba(249,168,37,0.35);
}
.bb-btn-gold:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(249,168,37,0.45);
}
.bb-btn-outline {
  background: transparent;
  border: 1.5px solid var(--primary);
  color: var(--primary);
  border-radius: 10px;
  padding: 9px 20px;
  font-family: 'Poppins', sans-serif;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
}
.bb-btn-outline:hover { background: var(--primary-xlight); }

/* ── Badges ───────────────────────────────────────────────── */
.bb-badge {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  border-radius: 30px;
  font-size: 0.7rem;
  font-weight: 700;
}
.bb-badge-pending  { background: #fff3e0; color: #e65100; }
.bb-badge-confirmed { background: #e3f2fd; color: #1565c0; }
.bb-badge-converted, .bb-badge-delivered { background: #e8f5e9; color: var(--success); }
.bb-badge-cancelled { background: #fce4ec; color: var(--danger); }
.bb-badge-paid      { background: #e8f5e9; color: var(--success); }
.bb-badge-partial   { background: #fff8e1; color: #f57f17; }
.bb-badge-unpaid    { background: #fce4ec; color: var(--danger); }
.bb-badge-gold      { background: var(--gold-light); color: var(--gold-dark); }

/* ── Flash Messages ───────────────────────────────────────── */
.bb-flash {
  margin: 0 12px 10px;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 0.82rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  animation: slideDown 0.3s ease;
}
.bb-flash-success { background: #e8f5e9; color: var(--success); border-left: 3px solid var(--success); }
.bb-flash-danger  { background: #fce4ec; color: var(--danger);  border-left: 3px solid var(--danger);  }

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Scrollbar ────────────────────────────────────────────── */
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--primary-light); border-radius: 4px; }

/* ── Responsive ───────────────────────────────────────────── */
@media (min-width: 768px) {
  body { padding-bottom: 20px; }
  .bb-bottom-nav { display: none; }
  .bb-desktop-nav { display: flex !important; }
}
.bb-desktop-nav { display: none; }
</style>
</head>
<body>

<?php
/* ── compute current URL for active-state ── */
$cur = uri_string();
$header_cart = $this->session->userdata('portal_cart') ?? [];
$header_cart_count = 0;
foreach ($header_cart as $hc_item) { $header_cart_count += $hc_item['qty']; }
?>

<!-- ══ TOP BAR ══════════════════════════════════════════════ -->
<nav class="bb-topbar">
  <div style="display:flex; align-items:center; justify-content:space-between; width:100%; margin-bottom:12px;">
    <div style="display:flex; align-items:center; gap:12px;">
      <a href="<?= base_url('portal/products') ?>" class="bb-logo" style="display:flex; align-items:center; gap:8px; text-decoration:none;">
        <?php if (!empty($company['company_logo'])): ?>
            <img src="<?= base_url($company['company_logo']) ?>" alt="Logo" style="height: 36px; width: auto; object-fit: contain; max-width: 120px;">
        <?php else: ?>
            <div class="bb-logo-icon" style="background: linear-gradient(135deg, #FAF6EB, #F6CD3C); color: #3B2314; box-shadow: 0 2px 8px rgba(59,35,20,0.2);">✨</div>
        <?php endif; ?>
      </a>
      <div style="display:flex; flex-direction:column;">
        <span style="font-size:1.05rem; font-weight:800; color:var(--text-dark); line-height:1.2;">Delivery in 12 mins</span>
        <span style="font-size:0.75rem; color:var(--text-muted); display:flex; align-items:center; gap:4px;">Home - Current Location <i class="fa fa-chevron-down" style="font-size:0.6rem;"></i></span>
      </div>
    </div>

    <div class="bb-topbar-icons">
      <?php if ($this->session->userdata('portal_customer_id')): ?>
      <a href="<?= base_url('portal/profile') ?>" class="bb-icon-btn" title="My Account">
        <i class="fa fa-user-circle"></i>
      </a>
      <?php else: ?>
      <a href="<?= base_url('portal/login') ?>" class="bb-icon-btn" title="Login" style="background:var(--primary);color:#fff;width:auto;padding:0 12px;border-radius:20px;font-size:0.8rem;font-weight:600;">
        Login
      </a>
      <?php endif; ?>
      <a href="<?= base_url('portal/order') ?>" class="bb-icon-btn" title="Cart" style="background:#fff; color:var(--primary); border: 1px solid #e2e8f0;">
        <i class="fa fa-shopping-cart"></i>
        <span class="bb-cart-badge" id="topCartBadge" <?= $header_cart_count > 0 ? '' : 'style="display:none"' ?>><?= $header_cart_count ?></span>
      </a>
    </div>
  </div>
  
  <div style="width:100%;">
    <form class="bb-search-bar" id="headerSearchForm" method="get" action="<?= base_url('portal/products') ?>">
      <button type="submit" class="bb-search-submit" title="Search">
        <i class="fa fa-search"></i>
      </button>
      <input type="text" name="q" id="headerSearchInput" placeholder="Search for 'milk', 'bread'..."
             value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>"
             autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
    </form>
  </div>
</nav>

<!-- ══ FLASH MESSAGES ══════════════════════════════════════ -->
<?php if ($this->session->flashdata('success')): ?>
<div class="bb-flash bb-flash-success"><i class="fa fa-check-circle"></i><?= htmlspecialchars($this->session->flashdata('success')) ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
<div class="bb-flash bb-flash-danger"><i class="fa fa-exclamation-circle"></i><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<!-- ══ PWA INSTALL SHEET ════════════════════════════════════ -->
<div id="pwaInstallOverlay" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,0.45);align-items:flex-end;justify-content:center;" onclick="if(event.target===this)closePwaSheet()">
  <div style="background:#fff;border-radius:22px 22px 0 0;padding:24px 20px 32px;max-width:480px;width:100%;box-shadow:0 -8px 32px rgba(59,35,20,0.18);animation:slideUpSheet 0.32s cubic-bezier(0.34,1.56,0.64,1);">
    <div style="width:40px;height:4px;background:#e0d5cf;border-radius:4px;margin:0 auto 18px;"></div>
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
      <img src="<?= base_url($company['company_logo'] ?? 'assets/uploads/logo_1782204427.png') ?>" style="width:56px;height:56px;border-radius:14px;object-fit:contain;border:2px solid var(--gold);background:#fff;" onerror="this.style.display='none'">
      <div>
        <div style="font-size:1rem;font-weight:800;color:var(--primary);"><?= htmlspecialchars($company['company_name'] ?? 'Discount Store') ?></div>
        <div style="font-size:0.72rem;color:var(--text-muted);">Add to your home screen for quick access</div>
      </div>
    </div>
    <!-- Always visible: main CTA button -->
    <button id="pwaInstallBtn" onclick="installPwa()" style="width:100%;border:none;border-radius:12px;padding:13px;font-family:'Poppins',sans-serif;font-size:0.92rem;font-weight:700;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;box-shadow:0 4px 14px rgba(59,35,20,0.22);margin-bottom:10px;">
      📲 Add to Home Screen
    </button>
    <!-- Steps shown AFTER button tap (iOS or generic) -->
    <div id="pwaSteps" style="display:none;background:var(--bg-page);border-radius:12px;padding:14px 16px;margin-bottom:12px;font-size:0.8rem;color:var(--primary);line-height:1.85;">
      <div id="pwaStepsIos">
        <div style="font-weight:700;margin-bottom:4px;">On iPhone / iPad (Safari):</div>
        <div>1. Tap the <strong>Share</strong> ⬆️ button at the bottom</div>
        <div>2. Tap <strong>"Add to Home Screen"</strong></div>
        <div>3. Tap <strong>Add</strong> — done!</div>
      </div>
      <div id="pwaStepsAndroid" style="display:none;">
        <div style="font-weight:700;margin-bottom:4px;">On Android (Chrome):</div>
        <div>1. Tap the <strong>⋮ menu</strong> (top right)</div>
        <div>2. Tap <strong>"Add to Home screen"</strong></div>
        <div>3. Tap <strong>Add</strong> — done!</div>
      </div>
    </div>
    <button onclick="closePwaSheet()" style="width:100%;border:1.5px solid var(--gold);border-radius:12px;padding:11px;font-family:'Poppins',sans-serif;font-size:0.82rem;font-weight:600;cursor:pointer;background:transparent;color:var(--primary);">
      Maybe Later
    </button>
  </div>
</div>
<style>
@keyframes slideUpSheet { from { transform: translateY(100%); } to { transform: translateY(0); } }
</style>
<script>
(function() {
    var SHOWN_KEY = 'portal_install_shown_v2';
    var deferredPrompt = null;

    function isIos() { return /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream; }
    function isStandalone() { return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true; }

    // Capture Android Chrome native install prompt
    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredPrompt = e;
    });

    function showSheet() {
        if (localStorage.getItem(SHOWN_KEY)) return;
        if (isStandalone()) return;
        document.getElementById('pwaInstallOverlay').style.display = 'flex';
    }

    // Always show after 2s on first visit (button is always visible)
    setTimeout(showSheet, 2000);

    window.installPwa = function() {
        if (deferredPrompt) {
            // Android Chrome: one-tap native install
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function(r) {
                if (r.outcome === 'accepted') localStorage.setItem(SHOWN_KEY, '1');
                deferredPrompt = null;
                closePwaSheet();
            });
        } else {
            // No native prompt: show manual instructions
            document.getElementById('pwaInstallBtn').style.display = 'none';
            document.getElementById('pwaSteps').style.display = 'block';
            if (!isIos()) {
                document.getElementById('pwaStepsIos').style.display = 'none';
                document.getElementById('pwaStepsAndroid').style.display = 'block';
            }
        }
    };

    window.closePwaSheet = function() {
        document.getElementById('pwaInstallOverlay').style.display = 'none';
        localStorage.setItem(SHOWN_KEY, '1');
    };
})();
</script>

<!-- ══ PERSISTENT LOGIN + TOKEN SAVE ═══════════════════════ -->
<?php
$_portal_logged_in = (bool)$this->session->userdata('portal_customer_id');
$_portal_new_token = $this->session->flashdata('portal_new_token') ?? '';
?>
<script>
window.PORTAL_BASE = '<?= base_url() ?>';
window.PORTAL_LOGGED_IN = <?= $_portal_logged_in ? 'true' : 'false' ?>;

<?php if ($_portal_new_token): ?>
// Fresh login — save remember token to localStorage
localStorage.setItem('portal_remember_token', '<?= $_portal_new_token ?>');
<?php endif; ?>

// Auto-login: if session expired but token saved on device, restore silently
(function() {
    if (window.PORTAL_LOGGED_IN) return; // already logged in
    var tok = localStorage.getItem('portal_remember_token');
    if (!tok) return;
    fetch(window.PORTAL_BASE + 'portal/auto_login_ajax', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'token=' + encodeURIComponent(tok)
    }).then(function(r){ return r.json(); }).then(function(d){
        if (d.success) {
            window.location.reload(); // session restored, refresh to get logged-in view
        } else {
            localStorage.removeItem('portal_remember_token'); // token expired/invalid
        }
    }).catch(function(){});
})();
</script>

<!-- ══ PAGE CONTENT WRAPPER ════════════════════════════════ -->
<div class="bb-page-content">
