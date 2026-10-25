<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#0DB14B">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($company['company_name'] ?? 'Store') ?>">
<link rel="manifest" href="<?= base_url('manifest-store.json') ?>">
<link rel="apple-touch-icon" href="<?= base_url($company['company_logo'] ?? 'assets/uploads/logo_1782204427.png') ?>">
<title><?= isset($page_title) ? htmlspecialchars($page_title) . ' — ' . htmlspecialchars($company['company_name'] ?? 'Store') : htmlspecialchars($company['company_name'] ?? 'Store') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/font-awesome.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/store.css?v='.filemtime(FCPATH.'assets/css/store.css')) ?>">
<style>
:root {
<?php include_once APPPATH . 'views/store/_theme.php'; echo store_theme_vars($company ?? null); ?>
}
</style>
</head>
<body class="st-body">

<?php
$header_cart = $this->session->userdata('store_cart') ?? [];
$header_cart_count = 0;
foreach ($header_cart as $hc_item) $header_cart_count += $hc_item['qty'];
?>

<!-- ══ TOP BAR ══════════════════════════════════════════════ -->
<nav class="st-topbar">
  <a href="<?= base_url('store') ?>" class="st-logo">
    <?php if (!empty($company['company_logo'])): ?>
        <img src="<?= base_url($company['company_logo']) ?>" alt="Logo">
    <?php else: ?>
        <div class="st-logo-icon">⚡</div>
        <div class="st-logo-text"><?= htmlspecialchars($company['company_name'] ?? 'Store') ?></div>
    <?php endif; ?>
  </a>

  <form class="st-search-bar" method="get" action="<?= base_url('store/products') ?>">
    <i class="fa fa-search"></i>
    <input type="text" name="q" placeholder="Search for products…"
           value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>"
           autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
  </form>

  <div class="st-topbar-icons">
    <a href="<?= base_url('store/cart') ?>" class="st-icon-btn" title="Cart">
      <i class="fa fa-shopping-bag"></i>
      <span class="st-cart-badge" id="stTopCartBadge" <?= $header_cart_count > 0 ? '' : 'style="display:none"' ?>><?= $header_cart_count ?></span>
    </a>
    <?php if ($this->session->userdata('store_customer_id')): ?>
    <a href="<?= base_url('store/profile') ?>" class="st-icon-btn" title="My Account"><i class="fa fa-user-circle"></i></a>
    <?php else: ?>
    <a href="<?= base_url('store/login') ?>" class="st-login-pill"><i class="fa fa-sign-in"></i> Login</a>
    <?php endif; ?>
  </div>
</nav>

<!-- ══ FLASH MESSAGES ══════════════════════════════════════ -->
<?php if ($this->session->flashdata('success')): ?>
<div class="st-flash st-flash-success"><i class="fa fa-check-circle"></i><?= htmlspecialchars($this->session->flashdata('success')) ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
<div class="st-flash st-flash-danger"><i class="fa fa-exclamation-circle"></i><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>

<!-- ══ PWA INSTALL SHEET ════════════════════════════════════ -->
<div id="stPwaOverlay" class="st-pwa-overlay" onclick="if(event.target===this)closeStorePwaSheet()">
  <div class="st-pwa-sheet">
    <div class="st-pwa-handle"></div>
    <div class="st-pwa-brand">
      <img src="<?= base_url($company['company_logo'] ?? 'assets/uploads/logo_1782204427.png') ?>" onerror="this.style.display='none'">
      <div>
        <div class="st-pwa-title"><?= htmlspecialchars($company['company_name'] ?? 'Store') ?></div>
        <div class="st-pwa-sub">Add to your home screen for lightning-fast access</div>
      </div>
    </div>
    <button id="stPwaInstallBtn" class="st-btn-primary st-btn-block" onclick="installStorePwa()">📲 Add to Home Screen</button>
    <div id="stPwaSteps" class="st-pwa-steps">
      <div id="stPwaStepsIos">
        <div class="st-pwa-step-title">On iPhone / iPad (Safari):</div>
        <div>1. Tap the <strong>Share</strong> button</div>
        <div>2. Tap <strong>"Add to Home Screen"</strong></div>
        <div>3. Tap <strong>Add</strong> — done!</div>
      </div>
      <div id="stPwaStepsAndroid" style="display:none;">
        <div class="st-pwa-step-title">On Android (Chrome):</div>
        <div>1. Tap the <strong>⋮ menu</strong></div>
        <div>2. Tap <strong>"Add to Home screen"</strong></div>
        <div>3. Tap <strong>Add</strong> — done!</div>
      </div>
    </div>
    <button class="st-btn-ghost st-btn-block" onclick="closeStorePwaSheet()">Maybe Later</button>
  </div>
</div>
<script>
(function () {
    var SHOWN_KEY = 'store_install_shown_v1';
    var deferredPrompt = null;
    function isIos() { return /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream; }
    function isStandalone() { return window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true; }
    window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); deferredPrompt = e; });
    function showSheet() {
        if (localStorage.getItem(SHOWN_KEY)) return;
        if (isStandalone()) return;
        document.getElementById('stPwaOverlay').style.display = 'flex';
    }
    setTimeout(showSheet, 2000);
    window.installStorePwa = function () {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function (r) {
                if (r.outcome === 'accepted') localStorage.setItem(SHOWN_KEY, '1');
                deferredPrompt = null;
                closeStorePwaSheet();
            });
        } else {
            document.getElementById('stPwaInstallBtn').style.display = 'none';
            document.getElementById('stPwaSteps').style.display = 'block';
            if (!isIos()) {
                document.getElementById('stPwaStepsIos').style.display = 'none';
                document.getElementById('stPwaStepsAndroid').style.display = 'block';
            }
        }
    };
    window.closeStorePwaSheet = function () {
        document.getElementById('stPwaOverlay').style.display = 'none';
        localStorage.setItem(SHOWN_KEY, '1');
    };
})();
</script>

<!-- ══ PAGE CONTENT WRAPPER ════════════════════════════════ -->
<div class="st-page-content">
