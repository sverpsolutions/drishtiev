<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — <?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php
        if (!function_exists('admin_contrast_text')) {
            // Picks readable dark-brown or white text against whatever accent colour is set.
            function admin_contrast_text($hex) {
                $hex = ltrim((string)$hex, '#');
                if (strlen($hex) !== 6 || !ctype_xdigit($hex)) return '#3B2314';
                $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
                $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
                return $luminance > 0.55 ? '#3B2314' : '#FFFFFF';
            }
        }
        $__admin_primary = htmlspecialchars($company['admin_primary_color'] ?? '#F6CD3C');
        $__admin_accent  = htmlspecialchars($company['admin_accent_color']  ?? '#E5A823');
        $__admin_text    = admin_contrast_text($company['admin_primary_color'] ?? '#F6CD3C');

        // Left-panel background: admin-configurable (Settings > Login Page Background
        // Image); falls back to a plain brand-coloured gradient so the page still looks
        // designed with nothing uploaded, instead of a blank/broken image.
        $__has_bg = !empty($company['login_bg_image']) && file_exists('./' . $company['login_bg_image']);
        $__bg_url = $__has_bg ? base_url($company['login_bg_image']) : '';

        $__license = trim((string)($company['license_number'] ?? ''));
        $__support_until = trim((string)($company['support_valid_until'] ?? ''));
        $__support_until_fmt = $__support_until ? date('d M Y', strtotime($__support_until)) : '';
        $__helpline = trim((string)($company['support_helpline'] ?? '')) ?: '+91 98711 18688';
        // SV ERP licence / AMC status from the CRM (cached 6 h; if the CRM is down the page just skips it)
        $__sup_lic = null; $__sup_key = '';
        try {
            $__CI =& get_instance(); $__CI->load->library('SverpSupport', null, 'sup');
            $__sup_lic = $__CI->sup->license(); $__sup_key = $__CI->sup->masked_license();
        } catch (Throwable $e) { $__sup_lic = null; }
    ?>
    <style>
        html, body { height: 100%; }
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            color: #2D1C13;
            background: #FAF6EB;
        }
        .login-split {
            min-height: 100vh;
            display: flex;
        }
        /* ── Left: image panel ── */
        .login-visual {
            flex: 1 1 52%;
            position: relative;
            min-height: 320px;
            background-color: #2D1C13;
            <?php if ($__has_bg): ?>
            background-image: linear-gradient(180deg, rgba(20,12,7,.25) 0%, rgba(20,12,7,.72) 100%), url('<?= $__bg_url ?>');
            background-size: cover;
            background-position: center;
            <?php else: ?>
            background-image: linear-gradient(160deg, <?= $__admin_accent ?> 0%, #2D1C13 100%);
            <?php endif; ?>
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 56px 52px;
            color: #fff;
        }
        /* ── Right: form panel ── */
        .login-formside {
            flex: 1 1 48%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 32px;
            background: #fff;
        }
        .login-form-inner { width: 100%; max-width: 380px; }
        .login-logo-row { display: flex; align-items: center; gap: 12px; margin-bottom: 26px; }
        .login-logo-row img { max-height: 44px; width: auto; }
        .login-logo-row .lr-name { font-weight: 700; font-size: 1.1rem; color: #2D1C13; line-height: 1.15; }
        .login-logo-row .lr-tag { font-size: .72rem; color: #8a7a6a; font-weight: 600; letter-spacing: .03em; }
        .login-form-inner h2 {
            font-weight: 700; font-size: 1.55rem; margin-bottom: 4px; color: #2D1C13;
        }
        .login-form-inner .lead-sub {
            font-size: .88rem; color: #8a7a6a; margin-bottom: 24px;
        }
        label { font-size: 0.85rem; font-weight: 600; color: #3B2314; }
        .input-group-text {
            background: #FFF2CC; border-color: #ced4da; border-right: none; color: #3B2314;
        }
        .form-control { border-left: none; }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(59, 35, 20, 0.15);
            border-color: #3B2314;
        }
        .pw-toggle {
            background: #fff; border-color: #ced4da; border-left: none; cursor: pointer; color: #8a7a6a;
        }
        .pw-toggle:hover { color: #3B2314; }
        .btn-login {
            width: 100%;
            padding: 11px;
            font-weight: 600;
            background-color: <?= $__admin_accent ?>;
            border-color: <?= $__admin_accent ?>;
            color: <?= $__admin_text ?>;
            transition: all 0.2s;
            border-radius: 8px;
        }
        .btn-login:hover, .btn-login:focus, .btn-login:active {
            filter: brightness(0.92);
            color: <?= $__admin_text ?> !important;
            box-shadow: 0 4px 14px rgba(59,35,20,0.22) !important;
        }
        /* ── License / support footer strip ── */
        .login-support-strip {
            margin-top: 26px;
            border-top: 1px dashed #E6DCBF;
            padding-top: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px 18px;
            font-size: .76rem;
            color: #8a7a6a;
        }
        .login-support-strip .ss-item { display: flex; align-items: center; gap: 6px; white-space: nowrap; }
        .login-support-strip .ss-item b { color: #3B2314; font-weight: 700; }
        .login-support-strip a { color: #6D3D1E; font-weight: 700; text-decoration: none; }
        .login-support-strip a:hover { text-decoration: underline; }
        @media (max-width: 860px) {
            .login-split { flex-direction: column; }
            .login-visual { flex: 0 0 auto; min-height: 220px; padding: 32px 28px; }
            .login-visual h1 { font-size: 1.7rem; }
            .login-formside { flex: 1 1 auto; padding: 32px 24px; }
        }
    </style>
</head>
<body>

<div class="login-split">
    <!-- ── LEFT: picture-only panel (no overlaid brand/company text) ── -->
    <?php $logo_src = !empty($company['company_logo']) ? base_url($company['company_logo']) : base_url('assets/logo.jpeg'); ?>
    <div class="login-visual"></div>

    <!-- ── RIGHT: login form ── -->
    <div class="login-formside">
        <div class="login-form-inner">

            <div class="login-logo-row">
                <img src="<?= $logo_src ?>" alt="Logo">
                <div>
                    <div class="lr-name"><?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?></div>
                    <?php if (!empty($company['company_tagline'])): ?>
                    <div class="lr-tag"><?= htmlspecialchars(strtoupper($company['company_tagline'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <h2>Welcome Back</h2>
            <p class="lead-sub">Please sign in to your account to continue.</p>

            <?php if ($this->session->flashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <small><?= $this->session->flashdata('error') ?></small>
                    <button type="button" class="close py-2" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($this->session->flashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                    <small><?= $this->session->flashdata('success') ?></small>
                    <button type="button" class="close py-2" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <?= form_open('auth/login', ['id' => 'loginForm']) ?>

                <div class="form-group mb-3">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">&#128100;</span>
                        </div>
                        <input
                            type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            placeholder="Enter username"
                            value="<?= set_value('username') ?>"
                            autocomplete="username"
                            required
                        >
                    </div>
                    <div id="userLocationBadge" style="display:none;font-size:0.78rem;margin-top:6px;">
                        <span class="badge badge-pill" id="userLocationBadgeInner" style="padding:5px 10px;font-weight:600;">
                            &#128205; <span id="userLocationText"></span>
                        </span>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">&#128274;</span>
                        </div>
                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            placeholder="Enter password"
                            autocomplete="current-password"
                            required
                        >
                        <div class="input-group-append">
                            <button class="btn pw-toggle" type="button" id="pwToggleBtn" tabindex="-1" title="Show/hide password">&#128065;</button>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="device_id" id="device_id_input">
                <input type="hidden" name="screen_info" id="screen_info_input">

                <button type="submit" class="btn btn-login">
                    Sign In
                </button>

            <?= form_close() ?>

            <div style="text-align:center; margin-top:18px;">
                <a href="<?= base_url('portal/login') ?>" style="font-size:0.78rem; color:#6D3D1E; font-weight: 600; text-decoration:none;">
                    ← Go to Customer Portal
                </a>
            </div>

            <?php if ($__sup_lic):
                $__st = $__sup_lic['status'];
                $__till = !empty($__sup_lic['support_valid_till']) ? date('d M Y', strtotime($__sup_lic['support_valid_till'])) : '—';
                list($__lbl, $__clr, $__bg, $__ico) = $__st === 'suspended' ? ['License Suspended', '#b91c1c', 'rgba(220,38,38,.08)', '⛔']
                    : ($__st === 'expired' ? ['AMC Support Expired', '#b45309', 'rgba(245,158,11,.10)', '⏳']
                    : ['AMC Support Active', '#047857', 'rgba(16,185,129,.09)', '🛡️']); ?>
            <div style="margin-top:16px;border:1px solid <?= $__clr ?>33;background:<?= $__bg ?>;border-radius:10px;padding:10px 14px;font-size:.78rem;">
                <div style="font-weight:700;color:<?= $__clr ?>;font-size:.85rem;"><?= $__ico ?> <?= $__lbl ?><?php if ($__st === 'active' && isset($__sup_lic['days_remaining'])): ?> <span style="font-weight:500;color:#6b7280;">· <?= (int)$__sup_lic['days_remaining'] ?> days left</span><?php endif; ?></div>
                <div style="display:flex;justify-content:space-between;margin-top:4px;"><span style="color:#6b7280;">Support valid till</span><b><?= htmlspecialchars($__till) ?></b></div>
                <div style="display:flex;justify-content:space-between;"><span style="color:#6b7280;">License No.</span><b style="font-family:monospace;letter-spacing:.5px;"><?= htmlspecialchars($__sup_key) ?></b></div>
            </div>
            <?php endif; ?>

            <?php if ($__license || $__support_until_fmt || $__helpline): ?>
            <div class="login-support-strip">
                <?php if ($__license): ?>
                <span class="ss-item">📄 <b>License:</b> <?= htmlspecialchars($__license) ?></span>
                <?php endif; ?>
                <?php if ($__support_until_fmt && !$__sup_lic): ?>
                <span class="ss-item">🛟 <b>Free Support Till:</b> <?= htmlspecialchars($__support_until_fmt) ?></span>
                <?php endif; ?>
                <?php if ($__helpline): ?>
                <span class="ss-item">📞 <b>Helpline:</b> <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $__helpline)) ?>"><?= htmlspecialchars($__helpline) ?></a></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <hr class="mt-4 mb-3">
            <p class="text-center text-muted mb-0" style="font-size:0.78rem;">
                <?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?> &copy; <?= date('Y') ?> &mdash; <?= htmlspecialchars($company['company_state'] ?? 'Uttar Pradesh') ?>
            </p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Generate a stable device fingerprint stored in localStorage
(function() {
    var key = 'erp_device_id';
    var did = localStorage.getItem(key);
    if (!did) {
        // Generate UUID-like fingerprint based on browser characteristics
        var raw = navigator.userAgent + screen.width + 'x' + screen.height + navigator.language + (new Date()).getTimezoneOffset();
        // Simple hash
        var hash = 0;
        for (var i = 0; i < raw.length; i++) { hash = ((hash << 5) - hash) + raw.charCodeAt(i); hash |= 0; }
        did = 'dev_' + Math.abs(hash).toString(16) + '_' + Math.random().toString(36).substr(2, 8);
        localStorage.setItem(key, did);
    }
    document.getElementById('device_id_input').value = did;
    document.getElementById('screen_info_input').value = screen.width + 'x' + screen.height;
})();

// Auto-display which location this username is tied to, so staff can confirm
// they're on the right outlet's terminal before signing in.
(function() {
    var usernameInput = document.getElementById('username');
    var badge          = document.getElementById('userLocationBadge');
    var badgeInner      = document.getElementById('userLocationBadgeInner');
    var badgeText       = document.getElementById('userLocationText');
    var lookupTimer, lastQuery = '';

    usernameInput.addEventListener('input', function() {
        var val = this.value.trim();
        clearTimeout(lookupTimer);
        if (val.length < 2) { badge.style.display = 'none'; return; }
        lookupTimer = setTimeout(function() { lookupLocation(val); }, 400);
    });

    function lookupLocation(username) {
        if (username === lastQuery) return;
        lastQuery = username;
        fetch('<?= base_url("auth/get_user_location_ajax") ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'username=' + encodeURIComponent(username)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (usernameInput.value.trim() !== username) return; // user kept typing — stale response
            if (data.found) {
                badgeText.textContent = data.location;
                badgeInner.style.background = '#FFF2CC';
                badgeInner.style.color = '#3B2314';
                badge.style.display = 'block';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(function() { badge.style.display = 'none'; });
    }
})();

// Show/hide password
(function() {
    var btn = document.getElementById('pwToggleBtn');
    var pw  = document.getElementById('password');
    if (!btn || !pw) return;
    btn.addEventListener('click', function() {
        var showing = pw.type === 'text';
        pw.type = showing ? 'password' : 'text';
        btn.style.opacity = showing ? '1' : '0.6';
    });
})();
</script>
</body>
</html>
