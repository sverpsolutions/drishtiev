<!DOCTYPE html>
<html lang="en" id="htmlRoot">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?> — <?= isset($page_title) ? htmlspecialchars($page_title) : 'ERP' ?></title>
    <script>
        // Global base URL for offline sync engine
        window.APP_BASE_URL = '<?= base_url() ?>';
    </script>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/font-awesome.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/erp-ds.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap">
    <?php
        if (!function_exists('admin_contrast_text')) {
            // Picks readable dark-brown or white text against whatever top-bar colour is set.
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
    ?>
    <style>
    /* ── Admin Theme (Light Mode) ── */
    body:not(.dark-mode) {
        background-color: #f5f7fa;
        color: #1a1a2e;
    }
    body:not(.dark-mode) .navbar-dark .navbar-brand {
        color: #fff !important;
        font-weight: 700;
    }
    body:not(.dark-mode) .navbar-dark .navbar-nav .nav-link {
        color: rgba(255,255,255,.9) !important;
        font-weight: 600;
    }
    body:not(.dark-mode) .navbar-dark .navbar-nav .nav-link:hover {
        color: #fff !important;
        background-color: rgba(255,255,255,.12) !important;
        border-radius: 4px;
    }
    body:not(.dark-mode) .navbar-dark .navbar-nav .dropdown-menu {
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    body:not(.dark-mode) .navbar-dark .navbar-nav .dropdown-item {
        color: #1a1a2e !important;
        font-weight: 500;
    }
    body:not(.dark-mode) .navbar-dark .navbar-nav .dropdown-item:hover {
        background-color: #e3f2fd !important;
        color: #0d47a1 !important;
    }
    body:not(.dark-mode) .card {
        border-color: #E6DCBF;
        box-shadow: 0 2px 8px rgba(59,35,20,0.04) !important;
    }
    body:not(.dark-mode) .card-header {
        background-color: #FFF2CC !important;
        color: #3B2314 !important;
        border-bottom: 1px solid #E6DCBF;
        font-weight: 700;
    }
    body:not(.dark-mode) .table thead.thead-dark th {
        background-color: #3B2314 !important;
        color: #FFF9E6 !important;
        border-color: #3B2314;
    }
    body:not(.dark-mode) .table thead.thead-light th {
        background-color: #FFF2CC !important;
        color: #3B2314 !important;
        border-color: #E6DCBF;
    }
    body:not(.dark-mode) .btn-primary {
        background-color: #3B2314 !important;
        border-color: #3B2314 !important;
        color: #FFF9E6 !important;
    }
    body:not(.dark-mode) .btn-primary:hover {
        background-color: #5E3E26 !important;
        border-color: #5E3E26 !important;
    }
    body:not(.dark-mode) .btn-success {
        background-color: #2e7d32 !important;
        border-color: #2e7d32 !important;
    }
    body:not(.dark-mode) .text-warning {
        color: #EAA01E !important;
    }
    body:not(.dark-mode) .badge-success {
        background-color: #FFF2CC !important;
        color: #A07810 !important;
        border: 1px solid #E5C26B;
    }

    /* ── Sandal House Theme (Dark Mode) ── */
    body.dark-mode { background:#1E110A; color:#EBD5C8; }
    body.dark-mode .card { background:#2E1B10; border-color:#4A2D1C; color:#EBD5C8; }
    body.dark-mode .card-header { background:#4A2D1C !important; color:#FFF3EA !important; border-color:#2E1B10; }
    body.dark-mode .table { color:#EBD5C8; }
    body.dark-mode .table-bordered td, body.dark-mode .table-bordered th { border-color:#4A2D1C; }
    body.dark-mode .table-hover tbody tr:hover { background:#4A2D1C; color:#fff; }
    body.dark-mode .thead-light th { background:#4A2D1C; color:#EBD5C8; }
    body.dark-mode .thead-dark th  { background:#000; color:#fff; }
    body.dark-mode .form-control, body.dark-mode .custom-select { background:#4A2D1C; color:#FFF3EA; border-color:#1E110A; }
    body.dark-mode .modal-content { background:#2E1B10; color:#EBD5C8; }
    body.dark-mode .dropdown-menu { background:#4A2D1C; border-color:#1E110A; }
    body.dark-mode .dropdown-item { color:#EBD5C8; }
    body.dark-mode .dropdown-item:hover { background:#1E110A; }
    body.dark-mode .alert-light { background:#4A2D1C; border-color:#4A2D1C; }
    body.dark-mode .list-group-item { background:#4A2D1C; border-color:#1E110A; color:#EBD5C8; }
    body.dark-mode .bg-light { background:#4A2D1C !important; }
    body.dark-mode .text-dark { color:#FFF3EA !important; }
    body.dark-mode .navbar { background:#22140C !important; border-bottom: 2px solid #4A2D1C; }
    body.dark-mode .table-light td, body.dark-mode .table-light th { background:#2E1B10; color:#EBD5C8; }

    /* ── Theme toggle button ── */
    #themeToggle { cursor:pointer; font-size:1.1rem; padding:4px 10px; }
    /* ── Keyboard badge ── */
    .btn-xs { padding:.15rem .4rem; font-size:.75rem; line-height:1.5; border-radius:.2rem; }
    /* ── Stock low badge pulse ── */
    .badge-pulse { animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
    /* ── Responsive ── */
    @media(max-width:768px){ .navbar-brand{font-size:.9rem;} }
    /* ── FIX: Prevent blue focus/active highlight on nav links ── */
    .navbar-dark .navbar-nav .nav-link,
    .navbar-dark .navbar-nav .nav-link:focus,
    .navbar-dark .navbar-nav .nav-link:active,
    .navbar-dark .navbar-nav .show > .nav-link {
        color: rgba(255,255,255,.85) !important;
        background-color: transparent !important;
        outline: none !important;
        box-shadow: none !important;
    }
    .navbar-dark .navbar-nav .nav-link:hover { color: #fff !important; }
    .navbar-dark .navbar-nav .dropdown-toggle::after { color: rgba(255,255,255,.85); }
    /* ── SuperAdmin badge ── */
    .badge-superadmin { background: linear-gradient(135deg,#f093fb,#f5576c); color:#fff; font-size:.6rem; }

    /* ── Offline POS Status Badge ── */
    .sync-status-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 20px; font-size: .78rem;
        font-weight: 600; white-space: nowrap; cursor: default;
        transition: background .3s, color .3s;
        border: 1px solid rgba(255,255,255,.2);
    }
    .sync-status-badge.badge-online  { background: rgba(40,167,69,.15); color: #28a745; border-color: #28a745; }
    .sync-status-badge.badge-offline { background: rgba(220,53,69,.18); color: #dc3545; border-color: #dc3545; animation: pulse 1.5s infinite; }
    .sync-status-badge.badge-syncing { background: rgba(255,193,7,.15); color: #e0a800; border-color: #e0a800; }

    /* ════════════════════════════════════════
       SIDEBAR — Deep Navy Theme
       ════════════════════════════════════════ */
    :root {
        --sb-w: 260px;
        --sb-mini: 60px;
        --sb-bg: #1B3A6B;
        --sb-border: #ffffff18;
        --sb-text: #b8ccec;
        --sb-text-head: #ffffff;
        --sb-text-muted: #5d85b5;
        --sb-hover-bg: #ffffff14;
        --sb-active-bg: #0891B2;
        --sb-active-border: #06b6d4;
        --sb-section-color: #5d85b5;
        --sb-font: 'Segoe UI', system-ui, sans-serif;
    }

    /* ── Shell ── */
    .erp-sidebar {
        position: fixed; top: 0; left: 0; height: 100vh; width: var(--sb-w);
        background: var(--sb-bg);
        border-right: 1px solid var(--sb-border);
        color: var(--sb-text);
        overflow-y: auto; overflow-x: hidden;
        z-index: 1040;
        transition: transform .22s ease, box-shadow .22s ease;
        display: flex; flex-direction: column;
        box-shadow: 2px 0 16px rgba(0,0,0,.25);
        font-family: var(--sb-font);
        -webkit-font-smoothing: antialiased;
    }
    .erp-sidebar::-webkit-scrollbar { width: 4px; }
    .erp-sidebar::-webkit-scrollbar-thumb { background: #2d5ba0; border-radius: 4px; }
    .erp-sidebar::-webkit-scrollbar-thumb:hover { background: #3a72c8; }

    /* ── Brand / Logo bar ── */
    .erp-sidebar .sb-brand {
        display: flex; align-items: center; gap: 10px;
        padding: 1.25rem 1rem; min-height: 64px;
        color: #ffffff;
        font-weight: 700; font-size: .95rem; letter-spacing: -.01em;
        border-bottom: 1px solid var(--sb-border);
        white-space: nowrap; overflow: hidden; flex-shrink: 0;
    }
    .erp-sidebar .sb-brand .sb-brand-icon {
        font-size: 1.15rem; flex-shrink: 0;
        width: 34px; height: 34px;
        background: #0891B2; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
    }

    /* ── Section headers ── */
    .erp-sidebar .sb-section {
        font-size: .72rem; font-weight: 400; letter-spacing: .04em;
        text-transform: uppercase;
        margin: 12px 8px 2px;
        padding: 4px 10px;
        color: var(--sb-section-color);
        white-space: nowrap; overflow: hidden;
        display: flex; align-items: center; gap: 8px;
        cursor: pointer;
        user-select: none;
    }
    .erp-sidebar .sb-section:hover { color: #b8ccec; }
    .erp-sidebar .sb-section .sec-dot { display: none; }
    .erp-sidebar .sb-section i.mr-2 { font-size: .72rem; width: 14px; text-align: center; margin-right: 0 !important; flex-shrink: 0; }
    .erp-sidebar .sb-section .sec-text { flex: 1; }
    .erp-sidebar .sb-section .sb-sec-chev {
        font-size: .5rem; opacity: .5;
        transition: transform .2s ease;
    }
    .erp-sidebar .sb-section.sec-collapsed .sb-sec-chev { transform: rotate(-90deg); }

    /* ── Nav items ── */
    .erp-sidebar .sb-item {
        display: flex; align-items: center;
        padding: 7px 10px; min-height: 36px;
        color: var(--sb-text);
        text-decoration: none;
        font-size: .84rem; font-weight: 400; letter-spacing: 0;
        transition: background .15s ease, color .15s ease;
        white-space: nowrap; overflow: hidden;
        margin: 1px 8px; border-radius: 6px;
    }
    .erp-sidebar .sb-item:hover {
        background: var(--sb-hover-bg);
        color: #ffffff;
        text-decoration: none;
    }
    .erp-sidebar .sb-item.sb-active {
        background: var(--sb-active-bg);
        color: #ffffff !important;
        font-weight: 400;
    }
    .erp-sidebar .sb-item.sb-active i { color: #ffffff !important; }
    .erp-sidebar .sb-item:focus { outline: none; }
    .erp-sidebar .sb-item i {
        width: 18px; text-align: center; font-size: .88rem;
        flex-shrink: 0; margin-right: 9px;
        color: inherit;
    }
    .erp-sidebar .sb-label { flex: 1; overflow: hidden; text-overflow: ellipsis; }

    /* ── Section body — items indented under their group header ── */
    .erp-sidebar .sb-sec-body .sb-item {
        margin-left: 20px; margin-right: 6px;
        padding-left: 10px;
        border-left: 2px solid #ffffff12;
        border-radius: 0 6px 6px 0;
    }
    .erp-sidebar .sb-sec-body .sb-item:hover { border-left-color: #0891B2; }
    .erp-sidebar .sb-sec-body .sb-item.sb-active { border-left-color: #06b6d4; }

    /* ── Sub-menu (legacy) ── */
    .erp-sidebar .sb-sub {
        background: transparent; border-left: 2px solid #ffffff18;
        margin: 0 0 2px 24px; border-radius: 0; overflow: hidden;
    }
    .erp-sidebar .sb-sub .sb-item { font-size: .82rem; font-weight: 400; padding: 5px 10px 5px 12px; min-height: 30px; color: #94b3d4; border-left: none; margin: 1px 0; border-radius: 0 6px 6px 0; background: transparent; }
    .erp-sidebar .sb-sub .sb-item:hover { color: #ffffff; background: #ffffff14; }
    .erp-sidebar .sb-sub .sb-item.sb-active { background: #0891B2; color: #ffffff !important; }
    .erp-sidebar .sb-sub .sb-item i { font-size: .8rem; margin-right: 8px; width: 16px; }

    /* ── Divider ── */
    .erp-sidebar .sb-divider { border: none; border-top: 1px solid var(--sb-border); margin: 6px 12px; }

    /* ── Badge ── */
    .erp-sidebar .sb-badge {
        margin-left: auto; flex-shrink: 0; font-size: .6rem !important;
        background: rgba(255,255,255,.15) !important;
        color: #fff !important;
        border: none !important;
    }
    .erp-sidebar .sb-item.sb-active .sb-badge { background: rgba(255,255,255,.25) !important; }

    /* ── Bottom user panel ── */
    .erp-sidebar .sb-bottom {
        margin-top: auto; padding: 14px 16px 16px;
        border-top: 1px solid var(--sb-border);
        background: rgba(0,0,0,.15);
        flex-shrink: 0;
    }
    .erp-sidebar .sb-bottom .sb-user-name {
        color: #ffffff; font-weight: 600; font-size: .82rem;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px;
    }
    .erp-sidebar .sb-bottom .sb-user-email {
        color: var(--sb-section-color); font-size: .69rem;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 10px;
    }
    .erp-sidebar .sb-bottom .sb-logout {
        display: flex; align-items: center; gap: 8px;
        color: #94b3d4; font-size: .79rem; font-weight: 500;
        text-decoration: none; padding: 5px 4px; border-radius: 4px;
        transition: color .15s ease;
    }
    .erp-sidebar .sb-bottom .sb-logout:hover { color: #ffffff; text-decoration: none; background: #ffffff14; }

    /* ── Collapse pin button ── */
    .sb-pin-btn {
        position: absolute; top: 18px; right: -13px;
        width: 26px; height: 26px;
        background: #0891B2; border: 1.5px solid #06b6d4; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; z-index: 1042;
        color: #ffffff; font-size: .62rem;
        box-shadow: 0 2px 8px rgba(0,0,0,.3);
        transition: transform .18s, box-shadow .15s;
    }
    .sb-pin-btn:hover { transform: scale(1.18); box-shadow: 0 3px 12px rgba(0,0,0,.4); }

    /* ── Collapsed (hidden) mode ── */
    .erp-sidebar.sb-mini {
        transform: translateX(-100%);
        box-shadow: none;
    }

    /* ── Topbar ── */
    .erp-topbar {
        position: fixed; top: 0; left: var(--sb-w); right: 0; height: 54px;
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
        display: flex; align-items: center; padding: 0 20px;
        z-index: 1039; gap: 12px;
        transition: left .22s ease;
        box-shadow: 0 1px 6px rgba(27,58,107,.08);
    }
    .erp-topbar.tb-mini { left: 0; }
    .erp-topbar .tb-title { font-weight: 700; color: #1a202c; font-size: .88rem; }
    .erp-topbar .ml-auto { margin-left: auto; display: flex; align-items: center; gap: 8px; }

    body.sb-layout { overflow-x: hidden; } html.sb-active { overflow-x: hidden; }
    body.sb-layout .container-fluid { margin-left: var(--sb-w); padding-top: 70px; min-height: 100vh; transition: margin-left .22s ease; max-width: calc(100vw - var(--sb-w)); overflow-x: hidden; }
    body.sb-layout.sb-collapsed .container-fluid { margin-left: 0; max-width: 100vw; }
    body.sb-layout footer { margin-left: var(--sb-w); transition: margin-left .22s ease; }
    body.sb-layout.sb-collapsed footer { margin-left: 0; }

    /* ── Offline POS Status Badge ── */
    .sync-status-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 10px; border-radius: 20px; font-size: .78rem;
        font-weight: 600; white-space: nowrap; cursor: default;
        transition: background .3s, color .3s;
    }
    .sync-status-badge.badge-online  { color: #28a745; }
    .sync-status-badge.badge-offline { color: #dc3545; animation: pulse 1.5s infinite; }
    .sync-status-badge.badge-syncing { color: #e0a800; }

    .sb-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 1039; }
    @media(max-width:991px) {
        .erp-sidebar { width: var(--sb-w) !important; transform: translateX(calc(-1 * var(--sb-w))); transition: transform .25s ease; }
        .erp-sidebar.sb-mini { width: var(--sb-w) !important; }
        .erp-sidebar.sb-open { transform: translateX(0) !important; }
        .sb-overlay.active { display: block; }
        body.sb-layout .container-fluid,
        body.sb-layout.sb-collapsed .container-fluid { margin-left: 0; }
        body.sb-layout footer,
        body.sb-layout.sb-collapsed footer { margin-left: 0; }
        .erp-topbar, .erp-topbar.tb-mini { left: 0; }
        .sb-pin-btn { display: none; }
    }
    </style>
</head>
<body>

<?php if ($this->session->userdata('user_id')):
    $__role     = $this->session->userdata('role');
    $__perms    = $this->session->userdata('module_perms') ?? [];
    $__is_sa    = ($__role === 'superadmin');
    $__is_owner = ((int)$this->session->userdata('user_id') === 9999);
    // Helper inline fn: show module if superadmin OR permission enabled
    $__m = function($key) use ($__is_sa, $__perms) {
        return $__is_sa || !empty($__perms[$key]);
    };
    // WhatsApp Marketing is vendor-controlled and deliberately does NOT use the
    // superadmin bypass above — role='superadmin' alone (a client's own "Owner"
    // login) must not see it, only the literal system-owner account (user_id 9999,
    // $__is_owner) or a user explicitly granted mod_whatsapp_marketing.
    $__wa_visible = $__is_owner || !empty($__perms['mod_whatsapp_marketing']);
    // Pending order requests badge
    $__pending_orders = $this->db->where('status','pending')->count_all_results('order_requests');
    $__web_new = $this->db->table_exists('web_orders') ? $this->db->where('order_status','new')->count_all_results('web_orders') : 0;
    $__pending_transfers = $this->db->where('status','pending')->count_all_results('stock_transfers');
    $__loc_id   = $this->session->userdata('location_id') ?: null;
    $__act_loc  = $this->session->userdata('active_location_name') ?: null;
    $__is_owner = ($__loc_id === null);
    // Load all active locations for owner switcher
    $__all_locs = $__is_owner ? $this->db->where('status',1)->order_by('sort_order')->get('locations')->result() : [];
    // Active Financial Year — read-only resolve, doesn't touch any counter (just the
    // same Apr1-Mar31 lookup the numbering engine uses, so the badge always matches
    // what a document saved right now would actually be numbered under).
    // get_instance() is used explicitly (not bare $this) because CI3's library loader
    // attaches new libraries to the real controller singleton via get_instance() — and
    // $this inside an included view isn't guaranteed to be that same object, so reading
    // $this->documentnumbering right back on the next line was coming back null.
    $__CI =& get_instance();
    $__CI->load->library('DocumentNumbering');
    $__active_fy = $__CI->documentnumbering->resolve_financial_year(date('Y-m-d'));
    // Navigation layout
    $__layout_row = $this->db->where('key_name','menu_layout')->get('company_settings')->row();
    $__nav_layout = $__layout_row ? $__layout_row->key_value : 'top_nav';
?>
<?php if ($__nav_layout === 'sidebar'): ?>
<script>document.body.classList.add('sb-layout');</script>
<?php else: ?>
<nav class="navbar navbar-expand-lg navbar-dark py-1 shadow-sm" style="background: linear-gradient(90deg, #0d47a1 0%, #1565c0 100%) !important; border-bottom: 2px solid #43a047;">
    <a class="navbar-brand font-weight-bold d-flex align-items-center py-0" href="<?= base_url('dashboard') ?>" style="line-height: 1.1; max-width: 320px;">
        <img src="<?= base_url('assets/web/logo.jpeg') ?>" alt="<?= htmlspecialchars($company['company_name'] ?? 'ERP') ?>" style="height: 42px; width: auto; object-fit: contain; margin-right: 10px; border-radius: 4px; background: rgba(0,0,0,0.2); padding: 2px 4px;">
        <div class="d-flex flex-column justify-content-center">
            <span style="color: #fff; font-size: 0.95rem; font-weight: 700; letter-spacing: 0.5px; line-height: 1.2;"><?= htmlspecialchars($company['company_name'] ?? 'ERP') ?></span>
            <small class="d-none d-lg-block text-truncate" style="font-size: .65rem; color: #a5d6a7; font-weight: 500; letter-spacing: 0.8px;"><?= htmlspecialchars($company['company_tagline'] ?? 'Grace in Every Thread') ?></small>
        </div>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNav" style="border-color: rgba(201,162,74,0.5);">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
        <ul class="navbar-nav mr-auto">

            <?php if ($__m('mod_guest_master') || $__m('mod_item_master')): ?>
            <!-- Masters — Suppliers/Customers + Product/PLU Master + Classification, all in one -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown"><i class="fas fa-sitemap fa-sm"></i> Masters</a>
                <div class="dropdown-menu shadow-sm border-0" style="min-width:240px;">
                    <?php if ($__m('mod_guest_master')): ?>
                    <a class="dropdown-item" href="<?= base_url('suppliers') ?>"><i class="fas fa-truck fa-sm mr-1"></i> Suppliers</a>
                    <a class="dropdown-item" href="<?= base_url('customers') ?>"><i class="fas fa-users fa-sm mr-1"></i> Customers</a>
                    <?php endif; ?>
                    <?php if ($__m('mod_item_master')): ?>
                    <?php if ($__m('mod_guest_master')): ?><div class="dropdown-divider"></div><?php endif; ?>
                    <h6 class="dropdown-header" style="font-size:.72rem;letter-spacing:.05em;">PRODUCT MASTER</h6>
                    <a class="dropdown-item" href="<?= base_url('products') ?>"><i class="fas fa-list fa-sm mr-1 text-muted"></i> All Products</a>
                    <a class="dropdown-item" href="<?= base_url('products/add') ?>"><i class="fas fa-plus fa-sm mr-1 text-success"></i> Add Product</a>
                    <a class="dropdown-item" href="<?= base_url('products/print_barcodes') ?>"><i class="fas fa-barcode fa-sm mr-1 text-muted"></i> Print Barcodes</a>
                    <a class="dropdown-item text-success font-weight-bold" href="<?= base_url('products/import') ?>"><i class="fas fa-file-excel fa-sm mr-1"></i> Import Excel</a>
                    <a class="dropdown-item text-primary font-weight-bold" href="<?= base_url('products/bulk_price_update') ?>"><i class="fas fa-tags fa-sm mr-1"></i> Bulk Price Update</a>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header" style="font-size:.72rem;letter-spacing:.05em;">
                        <i class="fas fa-tags mr-1" style="color:#f0a500;"></i>PLU MASTER <span class="badge badge-warning" style="font-size:.6rem;">Multi-MRP</span>
                    </h6>
                    <a class="dropdown-item font-weight-bold" href="<?= base_url('plu') ?>"><i class="fas fa-tags fa-sm mr-1 text-warning"></i> All PLUs</a>
                    <a class="dropdown-item" href="<?= base_url('plu/create') ?>"><i class="fas fa-plus fa-sm mr-1 text-success"></i> Add New PLU</a>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header" style="font-size:.72rem;letter-spacing:.05em;">CLASSIFICATION</h6>
                    <a class="dropdown-item" href="<?= base_url('masters/categories') ?>"><i class="fas fa-folder fa-sm mr-1 text-muted"></i> Categories</a>
                    <?php /* Sub-Categories hidden — not needed currently */ ?>
                    <?php /* <a class="dropdown-item" href="<?= base_url('masters/sub_categories') ?>"><i class="fas fa-folder-open fa-sm mr-1 text-muted"></i> Sub-Categories</a> */ ?>
<?php /* Groups & Departments hidden from menu
                    <a class="dropdown-item" href="<?= base_url('masters/groups') ?>"><i class="fas fa-layer-group fa-sm mr-1 text-muted"></i> Groups</a>
                    <a class="dropdown-item" href="<?= base_url('masters/departments') ?>"><i class="fas fa-building fa-sm mr-1 text-muted"></i> Departments</a>
*/ ?>
                    <a class="dropdown-item" href="<?= base_url('masters/brands') ?>"><i class="fas fa-trademark fa-sm mr-1 text-muted"></i> Brands</a>
                    <a class="dropdown-item font-weight-bold" href="<?= base_url('masters/colours') ?>"><i class="fas fa-palette fa-sm mr-1 text-warning"></i> Color Master (Vehicle Colours)</a>
                    <a class="dropdown-item" href="<?= base_url('masters/tags') ?>"><i class="fas fa-tag fa-sm mr-1 text-muted"></i> Tags</a>
                    <a class="dropdown-item" href="<?= base_url('masters/racks') ?>"><i class="fas fa-th fa-sm mr-1 text-muted"></i> Racks</a>
                    <a class="dropdown-item" href="<?= base_url('hsn') ?>"><i class="fas fa-percent fa-sm mr-1 text-muted"></i> HSN / GST Rates</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item font-weight-bold text-success" href="<?= base_url('schemes') ?>">🎯 Schemes</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endif; ?>

            <?php if ($__m('mod_item_master') && in_array(($company['retail_business_type'] ?? 'both'), ['clothing_only', 'both'], true)): ?>
            <!-- Clothing & Garments Module -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-warning font-weight-bold" href="#" role="button" data-toggle="dropdown">
                    <i class="fas fa-tshirt fa-sm"></i> Garments
                </a>
                <div class="dropdown-menu shadow-sm border-0" style="min-width:240px;">
                    <h6 class="dropdown-header" style="font-size:.72rem;letter-spacing:.05em;">STYLE &amp; VARIANTS</h6>
                    <a class="dropdown-item font-weight-bold" href="<?= base_url('garments') ?>"><i class="fas fa-tshirt fa-sm mr-1 text-primary"></i> Style Master</a>
                    <a class="dropdown-item text-success font-weight-bold" href="<?= base_url('garments/create') ?>"><i class="fas fa-plus fa-sm mr-1"></i> Add Garment Style</a>
                    <a class="dropdown-item" href="<?= base_url('garments/print_tags') ?>" target="_blank"><i class="fas fa-barcode fa-sm mr-1 text-dark"></i> Print Barcode Tags</a>
                    <a class="dropdown-item" href="<?= base_url('garments/import') ?>"><i class="fas fa-file-csv fa-sm mr-1 text-success"></i> Import CSV</a>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header" style="font-size:.72rem;letter-spacing:.05em;">ATTRIBUTES MASTER</h6>
                    <a class="dropdown-item" href="<?= base_url('garments/attributes') ?>"><i class="fas fa-tags fa-sm mr-1 text-primary"></i> Fabrics, Fits &amp; Categories</a>
                    <a class="dropdown-item" href="<?= base_url('garments/sizes') ?>"><i class="fas fa-ruler-combined fa-sm mr-1 text-muted"></i> Size Sets &amp; Sizes</a>
                    <a class="dropdown-item" href="<?= base_url('masters/colours') ?>"><i class="fas fa-palette fa-sm mr-1 text-muted"></i> Colours Palette</a>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header" style="font-size:.72rem;letter-spacing:.05em;">CLOTHING REPORTS</h6>
                    <a class="dropdown-item font-weight-bold" href="<?= base_url('garmentreports/size_matrix') ?>"><i class="fas fa-th-large fa-sm mr-1 text-info"></i> Size-wise Sales Matrix</a>
                    <a class="dropdown-item" href="<?= base_url('garmentreports/colour_matrix') ?>"><i class="fas fa-palette fa-sm mr-1 text-muted"></i> Colour Analytics</a>
                    <a class="dropdown-item font-weight-bold" href="<?= base_url('garmentreports/stock_report') ?>"><i class="fas fa-boxes fa-sm mr-1 text-success"></i> Garment Stock Report</a>
                </div>
            </li>
            <?php endif; ?>

            <?php if ($__m('mod_billing')): ?>
            <!-- Billing / Invoices / Estimates / Army Quotation -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown"><i class="fas fa-file-invoice fa-sm"></i> Billing</a>
                <div class="dropdown-menu shadow-sm border-0">
                    <a class="dropdown-item" href="<?= base_url('billing') ?>"><i class="fas fa-list fa-xs mr-1"></i>All Invoices</a>
                    <a class="dropdown-item text-primary font-weight-bold" href="<?= base_url('billing/create') ?>"><i class="fas fa-plus fa-sm mr-1"></i>New Invoice <small class="text-success">(📷 Barcode Ready)</small></a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-warning font-weight-bold" href="<?= base_url('refunds/create') ?>"><i class="fas fa-undo fa-xs mr-1"></i>Refund / Return Bill</a>
                    <a class="dropdown-item text-muted" href="<?= base_url('refunds') ?>"><i class="fas fa-list fa-xs mr-1"></i>All Refunds</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="<?= base_url('sales_returns/create') ?>"><i class="fas fa-undo fa-xs mr-1"></i>Sales Return (Credit Note)</a>
                    <a class="dropdown-item text-muted" href="<?= base_url('sales_returns') ?>"><i class="fas fa-list fa-xs mr-1"></i>All Credit Notes</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= base_url('einvoice') ?>"><i class="fas fa-file-contract fa-xs mr-1"></i>e-Invoice (IRN) Export</a>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header" style="font-size:.72rem;letter-spacing:.05em;">WHOLESALE</h6>
                    <a class="dropdown-item" href="<?= base_url('wholesale_billing/create') ?>"><i class="fas fa-plus fa-sm mr-1 text-success"></i>New Wholesale Invoice</a>
                    <a class="dropdown-item" href="<?= base_url('wholesale_billing') ?>"><i class="fas fa-file-invoice-dollar fa-sm mr-1"></i>All Wholesale Invoices</a>
                    <?php if ($this->session->userdata('role') === 'superadmin'): ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= base_url('estimates') ?>"><i class="fas fa-list fa-sm mr-1"></i> All Estimates</a>
                    <a class="dropdown-item text-primary" href="<?= base_url('estimates/create') ?>"><i class="fas fa-plus fa-sm mr-1"></i> New Estimate</a>
                    <?php endif; ?>
                    <?php if ($__is_sa || in_array($__role, ['owner','admin','manager'])): ?>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header" style="font-size:.72rem;letter-spacing:.05em;">QUOTATION</h6>
                    <a class="dropdown-item" href="<?= base_url('army_quotations/create') ?>"><i class="fas fa-plus fa-sm mr-1 text-success"></i> New Quotation</a>
                    <a class="dropdown-item" href="<?= base_url('army_quotations') ?>"><i class="fas fa-list fa-sm mr-1"></i> All Quotations</a>
                    <a class="dropdown-item" href="<?= base_url('army_company_master') ?>"><i class="fas fa-building fa-sm mr-1"></i> Company Master</a>
                    <?php endif; ?>
                    <?php if ($__is_sa || $this->session->userdata('role') === 'admin' || $this->session->userdata('role') === 'manager'): ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-warning" href="<?= base_url('billing/all_audit_logs') ?>"><i class="fas fa-history fa-sm mr-1"></i>Bill Edit Logs</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endif; ?>

            <?php if ($__m('mod_purchase') || $__m('mod_inventory')): ?>
            <!-- Purchases + Inventory -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown"><i class="fas fa-shopping-cart fa-sm"></i> Purchases</a>
                <div class="dropdown-menu shadow-sm border-0">
                    <?php if ($__m('mod_purchase')): ?>
                    <a class="dropdown-item" href="<?= base_url('purchases') ?>">All Purchases</a>
                    <a class="dropdown-item text-primary font-weight-bold" href="<?= base_url('purchases/create') ?>"><i class="fas fa-plus fa-sm mr-1"></i>New Purchase</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-warning" href="<?= base_url('purchase_returns/create') ?>">Purchase Return (Debit Note)</a>
                    <a class="dropdown-item text-muted" href="<?= base_url('purchase_returns') ?>">All Debit Notes</a>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header">PO Management</h6>
                    <a class="dropdown-item text-success font-weight-bold" href="<?= base_url('purchase_orders/create') ?>"><i class="fas fa-plus-circle fa-xs mr-1"></i>New PO</a>
                    <a class="dropdown-item" href="<?= base_url('purchase_orders') ?>">All POs</a>
                    <a class="dropdown-item text-info" href="<?= base_url('purchase_suggestions') ?>">Suggestions</a>
                    <a class="dropdown-item" href="<?= base_url('grn') ?>">GRN</a>
                    <a class="dropdown-item" href="<?= base_url('rfq') ?>">RFQ</a>
                    <a class="dropdown-item text-primary" href="<?= base_url('vendor-portal') ?>" target="_blank"><i class="fas fa-building fa-xs mr-1"></i>Vendor Portal</a>
                    <?php endif; ?>
                    <?php if ($__m('mod_inventory')): ?>
                    <?php if ($__m('mod_purchase')): ?><div class="dropdown-divider"></div><?php endif; ?>
                    <a class="dropdown-item font-weight-bold" href="<?= base_url('inventory') ?>"><i class="fas fa-warehouse fa-sm mr-1 text-muted"></i> Inventory</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endif; ?>

            <?php if ($__m('mod_stock_transfers') || $__m('mod_locations')): ?>
            <!-- Locations & Stock Transfers -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                    <i class="fas fa-exchange-alt fa-sm"></i> Transfers
                    <?php if ($__pending_transfers > 0): ?>
                    <span class="badge badge-warning badge-pill badge-pulse"><?= $__pending_transfers ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu shadow-sm border-0">
                    <?php if ($__m('mod_stock_transfers')): ?>
                    <a class="dropdown-item" href="<?= base_url('stock_transfers') ?>"><i class="fas fa-list fa-sm mr-1"></i> All Transfers</a>
                    <?php if (!isset($__tri_transit)) { $__tri_loc = (int)$this->session->userdata('location_id'); $__tri_transit = 0; if ($this->db->table_exists('stock_transfer_ins')) { $this->db->where('status','in_transit'); if ($__tri_loc) $this->db->where('to_location_id', $__tri_loc); $__tri_transit = $this->db->count_all_results('stock_transfer_ins'); } } ?>
                    <a class="dropdown-item" href="<?= base_url('stock_transfers/transfer_in') ?>"><i class="fas fa-arrow-left fa-sm mr-1"></i> Transfer In<?php if ($__tri_transit > 0): ?> <span class="badge badge-primary badge-pill"><?= $__tri_transit ?></span><?php endif; ?></a>
                    <a class="dropdown-item text-primary font-weight-bold" href="<?= base_url('stock_transfers/create') ?>"><i class="fas fa-plus fa-sm mr-1"></i> New Transfer</a>
                    <?php endif; ?>
                    <?php if ($__m('mod_locations')): ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= base_url('locations') ?>"><i class="fas fa-map-marker-alt fa-sm mr-1"></i> Manage Locations</a>
                    <a class="dropdown-item" href="<?= base_url('locations/stock') ?>"><i class="fas fa-boxes fa-sm mr-1"></i> Location Stock</a>
                    <?php endif; ?>
                    <?php if ($__m('mod_stock_take')): ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item font-weight-bold text-success" href="<?= base_url('stock_take') ?>">
                        <i class="fas fa-clipboard-check fa-sm mr-1"></i> Stock Take
                    </a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endif; ?>



            <?php if ($__m('mod_accounting') || $__m('mod_accounts')): ?>
            <!-- Accounting + Accounts/Payments -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown"><i class="fas fa-book fa-sm"></i> Accounting</a>
                <div class="dropdown-menu shadow-sm border-0">
                    <?php if ($__m('mod_accounts')): ?>
                    <a class="dropdown-item font-weight-bold" href="<?= base_url('payments') ?>"><i class="fas fa-rupee-sign fa-sm mr-1"></i> Payments &amp; Ledger</a>
                    <?php if ($__m('mod_accounting')): ?><div class="dropdown-divider"></div><?php endif; ?>
                    <?php endif; ?>
                    <?php if ($__m('mod_accounting')): ?>
                    <a class="dropdown-item" href="<?= base_url('accounting/chart_of_accounts') ?>"><i class="fas fa-sitemap fa-sm mr-1"></i> Chart of Accounts</a>
                    <a class="dropdown-item" href="<?= base_url('accounting/journal_entries') ?>"><i class="fas fa-book-open fa-sm mr-1"></i> Journal Entries</a>
                    <a class="dropdown-item" href="<?= base_url('accounting/bank_accounts') ?>"><i class="fas fa-university fa-sm mr-1"></i> Bank &amp; Cash Accounts</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= base_url('reports/trial_balance') ?>"><i class="fas fa-balance-scale-right fa-sm mr-1"></i> Trial Balance</a>
                    <a class="dropdown-item" href="<?= base_url('reports/general_ledger') ?>"><i class="fas fa-list-alt fa-sm mr-1"></i> General Ledger</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endif; ?>

            <?php if ($__wa_visible): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown"><i class="fab fa-whatsapp fa-sm" style="color:#25D366;"></i> Marketing</a>
                <div class="dropdown-menu shadow-sm border-0">
                    <a class="dropdown-item" href="<?= base_url('marketing') ?>"><i class="fas fa-chart-line fa-sm mr-1"></i> Dashboard</a>
                    <a class="dropdown-item" href="<?= base_url('marketing/campaigns') ?>"><i class="fas fa-bullhorn fa-sm mr-1"></i> Campaigns</a>
                    <a class="dropdown-item" href="<?= base_url('marketing/templates') ?>"><i class="fas fa-file-alt fa-sm mr-1"></i> Templates</a>
                    <a class="dropdown-item" href="<?= base_url('marketing/posters') ?>"><i class="fas fa-image fa-sm mr-1"></i> Poster Templates</a>
                    <a class="dropdown-item" href="<?= base_url('marketing/responses') ?>"><i class="fas fa-inbox fa-sm mr-1"></i> Responses</a>
                    <a class="dropdown-item" href="<?= base_url('marketing/analytics') ?>"><i class="fas fa-chart-bar fa-sm mr-1"></i> Analytics</a>
                    <a class="dropdown-item text-success font-weight-bold" href="<?= base_url('marketing/campaign_create') ?>"><i class="fas fa-plus fa-sm mr-1"></i> New Campaign</a>
                    <a class="dropdown-item text-warning font-weight-bold" href="<?= base_url('marketing/campaign_wizard') ?>"><i class="fas fa-bolt fa-sm mr-1"></i> One-Click Send</a>
                </div>
            </li>
            <?php endif; ?>

            <!-- Shifts & Day Control -->
            <?php if ($__m('mod_day_open') || $__m('mod_day_close') || $__m('mod_shift_open') || $__m('mod_shift_close')
                     || $__m('mod_warranty') || $__m('mod_loyalty') || $__m('mod_sms') || $__m('mod_cash_management')): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown"><i class="fas fa-clock fa-sm"></i> Operations Control</a>
                <div class="dropdown-menu shadow-sm border-0" style="max-height:78vh;overflow-y:auto;">
                    <?php if ($__m('mod_day_open')): ?>
                    <a class="dropdown-item text-success font-weight-bold" href="<?= base_url('shifts/day_open') ?>"><i class="fas fa-sun fa-sm mr-1"></i> Day Open</a>
                    <?php endif; ?>
                    <?php if ($__m('mod_day_close')): ?>
                    <a class="dropdown-item text-danger font-weight-bold" href="<?= base_url('shifts/day_close') ?>"><i class="fas fa-moon fa-sm mr-1"></i> Day Close</a>
                    <?php endif; ?>
                    <?php if (($__m('mod_day_open') || $__m('mod_day_close')) && ($__m('mod_shift_open') || $__m('mod_shift_close'))): ?>
                    <div class="dropdown-divider"></div>
                    <?php endif; ?>
                    <a class="dropdown-item text-dark font-weight-bold" href="<?= base_url('PrintSuite') ?>"><i class="fas fa-print fa-sm mr-1 text-info"></i> Print Suite</a>
                    <div class="dropdown-divider"></div>
                    <?php if ($__m('mod_shift_open')): ?>
                    <a class="dropdown-item text-primary" href="<?= base_url('shifts/shift_open') ?>"><i class="fas fa-play fa-sm mr-1"></i> Shift Open</a>
                    <?php endif; ?>
                    <?php if ($__m('mod_shift_close')): ?>
                    <a class="dropdown-item text-secondary" href="<?= base_url('shifts/shift_close') ?>"><i class="fas fa-stop fa-sm mr-1"></i> Shift Close</a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item font-weight-bold text-primary" href="<?= base_url('shifts/shift_reports_list') ?>"><i class="fas fa-receipt fa-sm mr-1"></i> Shift Reports &amp; Reprint</a>
                    <a class="dropdown-item font-weight-bold text-danger" href="<?= base_url('shifts/cashier_shortage_report') ?>"><i class="fas fa-user-times fa-sm mr-1"></i> Cashier Shortage Report</a>
                    <?php $__ops_admin = ($__is_sa || in_array($__role, ['owner','admin','manager'])); ?>

                    <?php if ($__m('mod_cash_management')): ?>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header text-uppercase" style="font-size:.68rem;letter-spacing:.06em;">Cash Management</h6>
                    <a class="dropdown-item font-weight-bold" href="<?= base_url('cashManagement') ?>"><i class="fas fa-cash-register fa-sm mr-1 text-success"></i> Cash Dashboard</a>
                    <a class="dropdown-item" href="<?= base_url('cashManagement/handover') ?>"><i class="fas fa-hand-holding-usd fa-sm mr-1 text-warning"></i> Cash Handover</a>
                    <a class="dropdown-item" href="<?= base_url('cashManagement/ledger') ?>"><i class="fas fa-book fa-sm mr-1 text-info"></i> Cash Ledger Report</a>
                    <?php if ($__ops_admin): ?>
                    <a class="dropdown-item" href="<?= base_url('cashManagement/categories') ?>"><i class="fas fa-tags fa-sm mr-1"></i> Cash Categories</a>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($__m('mod_warranty')): ?>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header text-uppercase" style="font-size:.68rem;letter-spacing:.06em;">Warranty &amp; Serial Numbers</h6>
                    <a class="dropdown-item" href="<?= base_url('warranty') ?>"><i class="fas fa-shield-alt fa-sm mr-1 text-primary"></i> Warranty Dashboard</a>
                    <a class="dropdown-item" href="<?= base_url('warranty/search') ?>"><i class="fas fa-barcode fa-sm mr-1"></i> Serial Number Search</a>
                    <a class="dropdown-item" href="<?= base_url('warranty/claims') ?>"><i class="fas fa-tools fa-sm mr-1"></i> Warranty Claims</a>
                    <a class="dropdown-item" href="<?= base_url('warranty/reports') ?>"><i class="fas fa-chart-bar fa-sm mr-1"></i> Warranty Reports</a>
                    <?php endif; ?>

                    <?php if ($__m('mod_loyalty')): ?>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header text-uppercase" style="font-size:.68rem;letter-spacing:.06em;">Loyalty &amp; Rewards</h6>
                    <a class="dropdown-item" href="<?= base_url('loyalty') ?>"><i class="fas fa-gift fa-sm mr-1 text-success"></i> Loyalty Dashboard</a>
                    <a class="dropdown-item" href="<?= base_url('loyalty/statement') ?>"><i class="fas fa-file-invoice fa-sm mr-1"></i> Customer Statement</a>
                    <a class="dropdown-item" href="<?= base_url('loyalty/earn_redeem') ?>"><i class="fas fa-exchange-alt fa-sm mr-1"></i> Earned vs Redeemed</a>
                    <a class="dropdown-item" href="<?= base_url('loyalty/reports') ?>"><i class="fas fa-chart-bar fa-sm mr-1"></i> Loyalty Reports</a>
                    <?php if ($__ops_admin): ?>
                    <a class="dropdown-item text-success font-weight-bold" href="<?= base_url('loyalty/settings') ?>"><i class="fas fa-cog fa-sm mr-1"></i> Loyalty Setup</a>
                    <a class="dropdown-item" href="<?= base_url('loyalty/adjust') ?>"><i class="fas fa-sliders-h fa-sm mr-1"></i> Manual Point Adjustment</a>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($__m('mod_sms')): ?>
                    <div class="dropdown-divider"></div>
                    <h6 class="dropdown-header text-uppercase" style="font-size:.68rem;letter-spacing:.06em;">SMS &amp; OTP</h6>
                    <?php if ($__ops_admin): ?>
                    <a class="dropdown-item text-primary font-weight-bold" href="<?= base_url('smsmanager') ?>"><i class="fas fa-sms fa-sm mr-1"></i> SMS Setup &amp; OTP</a>
                    <?php endif; ?>
                    <a class="dropdown-item" href="<?= base_url('smsmanager/logs') ?>"><i class="fas fa-list fa-sm mr-1"></i> SMS Send Log</a>
                    <?php endif; ?>

                    <?php if ($__is_sa): ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= base_url('financial_years') ?>"><i class="fas fa-calendar-alt fa-sm mr-1"></i> Financial Years</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endif; ?>

            <!-- Hotel Operations (Placeholders) (Hidden) -->
            <?php if (false && ($__m('mod_reservation') || $__m('mod_check_in') || $__m('mod_check_out') || $__m('mod_room_master'))): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown"><i class="fas fa-hotel fa-sm"></i> Hotel Ops</a>
                <div class="dropdown-menu shadow-sm border-0">
                    <?php if ($__m('mod_reservation')): ?>
                    <a class="dropdown-item" href="<?= base_url('shifts/placeholder/Reservation') ?>"><i class="fas fa-calendar-alt fa-sm mr-1"></i> Reservation</a>
                    <?php endif; ?>
                    <?php if ($__m('mod_check_in')): ?>
                    <a class="dropdown-item" href="<?= base_url('shifts/placeholder/Check_In') ?>"><i class="fas fa-sign-in-alt fa-sm mr-1"></i> Check-In</a>
                    <?php endif; ?>
                    <?php if ($__m('mod_check_out')): ?>
                    <a class="dropdown-item" href="<?= base_url('shifts/placeholder/Check_Out') ?>"><i class="fas fa-sign-out-alt fa-sm mr-1"></i> Check-Out</a>
                    <?php endif; ?>
                    <?php if ($__m('mod_room_master')): ?>
                    <a class="dropdown-item" href="<?= base_url('shifts/placeholder/Room_Master') ?>"><i class="fas fa-door-closed fa-sm mr-1"></i> Room Master</a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endif; ?>

            <?php if ($__m('mod_reports')): ?>
            <!-- Reports mega-menu -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown"><i class="fas fa-chart-bar fa-sm"></i> Reports</a>
                <div class="dropdown-menu shadow border-0 p-0" style="min-width:560px;left:auto;right:0;">
                    <!-- Top quick links -->
                    <div class="d-flex border-bottom" style="background:#f8f9fa;">
                        <a class="flex-fill text-center py-2 px-1 text-success font-weight-bold border-right" href="<?= base_url('reports/fmcg_suite') ?>" style="font-size:.8rem;text-decoration:none;">
                            <i class="fas fa-rocket d-block mb-1"></i>Reports Suite
                        </a>
                        <a class="flex-fill text-center py-2 px-1 text-primary font-weight-bold border-right" href="<?= base_url('reports') ?>" style="font-size:.8rem;text-decoration:none;">
                            <i class="fas fa-calendar-day d-block mb-1"></i>Daily Reports
                        </a>
                        <a class="flex-fill text-center py-2 px-1 text-danger font-weight-bold border-right" href="<?= base_url('reports/profit_loss') ?>" style="font-size:.8rem;text-decoration:none;">
                            <i class="fas fa-rupee-sign d-block mb-1"></i>Profit &amp; Loss
                        </a>
                        <a class="flex-fill text-center py-2 px-1 font-weight-bold text-warning" href="<?= base_url('reports/inventory_analytics') ?>" style="font-size:.8rem;text-decoration:none;">
                            <i class="fas fa-chart-pie d-block mb-1"></i>Analytics
                        </a>
                    </div>
                    <!-- 2-column body -->
                    <?php
                    $__fp = $this->session->userdata('form_perms') ?: [];
                    $__rpt = function($key) use ($__is_sa, $__role, $__fp) {
                        return $__is_sa || $__role==='owner' || $__role==='admin' || !empty($__fp[$key]);
                    };
                    ?>
                    <div class="d-flex">
                        <!-- Left column -->
                        <div class="border-right" style="flex:1;min-width:0;">
                            <div class="px-3 pt-2 pb-1" style="font-size:.7rem;font-weight:700;color:#999;letter-spacing:.06em;">SALES</div>
                            <?php if ($__rpt('frm_rpt_sales')): ?><a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/sales') ?>"><i class="fas fa-file-invoice fa-xs mr-2 text-muted"></i>Sales Report</a><?php endif; ?>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/refunds_report') ?>"><i class="fas fa-undo fa-xs mr-2 text-warning"></i>Refunds Report</a>
                            <?php if ($__rpt('frm_rpt_schemes')): ?><a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/scheme_sales') ?>"><i class="fas fa-percentage fa-xs mr-2 text-success"></i>Scheme Sales Report</a><?php endif; ?>
                            <?php if ($__rpt('frm_rpt_credit_notes')): ?><a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('sales_returns') ?>"><i class="fas fa-undo fa-xs mr-2 text-danger"></i>Credit Notes</a><?php endif; ?>
                            <?php if ($__rpt('frm_rpt_portal_funnel')): ?><a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/portal_order_funnel') ?>"><i class="fas fa-filter fa-xs mr-2 text-muted"></i>Portal Order Funnel</a><?php endif; ?>
                            <?php if ($__rpt('frm_rpt_outstanding')): ?><a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/outstanding') ?>"><i class="fas fa-exclamation-circle fa-xs mr-2 text-warning"></i>Outstanding Dues</a><?php endif; ?>
                            <?php if ($__rpt('frm_rpt_profit_loss')): ?><a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/profit_loss') ?>"><i class="fas fa-rupee-sign fa-xs mr-2 text-danger"></i>Profit &amp; Loss</a><?php endif; ?>

                            <?php if ($__rpt('frm_rpt_gst')): ?>
                            <div class="px-3 pt-2 pb-1 mt-1 border-top" style="font-size:.7rem;font-weight:700;color:#999;letter-spacing:.06em;">GST</div>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/gstr1') ?>"><i class="fas fa-file-alt fa-xs mr-2 text-muted"></i>GSTR-1</a>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/gstr2') ?>"><i class="fas fa-file-alt fa-xs mr-2 text-muted"></i>GSTR-2</a>
                            <a class="dropdown-item py-1 font-weight-bold" style="font-size:.82rem;" href="<?= base_url('reports/gstr3b') ?>"><i class="fas fa-file-alt fa-xs mr-2 text-danger"></i>GSTR-3B</a>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/gst') ?>"><i class="fas fa-receipt fa-xs mr-2 text-muted"></i>GST Summary</a>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/hsn_summary_report') ?>"><i class="fas fa-list fa-xs mr-2 text-muted"></i>HSN Summary</a>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/tally_export') ?>"><i class="fas fa-file-export fa-xs mr-2 text-muted"></i>Tally Export</a>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/gst2b_reconciliation') ?>"><i class="fas fa-balance-scale fa-xs mr-2 text-danger"></i>GSTR-2B Reconciliation</a>
                            <?php endif; ?>

                            <?php if ($__rpt('frm_rpt_audit_log')): ?>
                            <div class="px-3 pt-2 pb-1 mt-1 border-top" style="font-size:.7rem;font-weight:700;color:#999;letter-spacing:.06em;">SECURITY</div>
                            <a class="dropdown-item py-1 text-danger" style="font-size:.82rem;" href="<?= base_url('reports/audit_log') ?>"><i class="fas fa-shield-alt fa-xs mr-2"></i>Audit Log</a>
                            <a class="dropdown-item py-1 text-warning" style="font-size:.82rem;" href="<?= base_url('reports/payment_mode_changes') ?>"><i class="fas fa-exchange-alt fa-xs mr-2"></i>Pay Mode Changes</a>
                            <a class="dropdown-item py-1 text-danger" style="font-size:.82rem;" href="<?= base_url('reports/removed_items') ?>"><i class="fas fa-trash-alt fa-xs mr-2"></i>Removed Items</a>
                            <?php endif; ?>
                        </div>
                        <!-- Right column -->
                        <div style="flex:1;min-width:0;">
                            <?php if ($__rpt('frm_rpt_stock') || $__rpt('frm_rpt_purchase') || $__rpt('frm_rpt_inventory') || $__rpt('frm_rpt_category')): ?>
                            <div class="px-3 pt-2 pb-1" style="font-size:.7rem;font-weight:700;color:#999;letter-spacing:.06em;">INVENTORY</div>
                            <?php if ($__rpt('frm_rpt_stock')): ?><a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/stock') ?>"><i class="fas fa-boxes fa-xs mr-2 text-muted"></i>Stock Report</a><?php endif; ?>
                            <?php if ($__rpt('frm_rpt_stock')): ?><a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/rack_stock_report') ?>"><i class="fas fa-th fa-xs mr-2 text-muted"></i>Rack Stock Report</a><?php endif; ?>
                            <?php if ($__rpt('frm_rpt_purchase')): ?><a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/purchases') ?>"><i class="fas fa-shopping-cart fa-xs mr-2 text-muted"></i>Purchase Report</a><?php endif; ?>
                            <?php if ($__rpt('frm_rpt_purchase')): ?><a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/purchase_item_wise') ?>"><i class="fas fa-list fa-xs mr-2 text-muted"></i>Purchase (Item-Wise)</a><?php endif; ?>
                            <?php if ($__rpt('frm_rpt_inventory')): ?>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/fast_slow_report') ?>"><i class="fas fa-tachometer-alt fa-xs mr-2 text-primary"></i>Fast &amp; Slow Movement</a>
                            <?php endif; ?>
                            <?php if ($__rpt('frm_rpt_category')): ?>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/category_report') ?>"><i class="fas fa-tags fa-xs mr-2 text-muted"></i>Category Report</a>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/brand_report') ?>"><i class="fas fa-trademark fa-xs mr-2 text-muted"></i>Brand Report</a>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/supplier_report') ?>"><i class="fas fa-truck fa-xs mr-2 text-muted"></i>Supplier Report</a>
                            <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($__rpt('frm_rpt_party_ledger') || $__rpt('frm_rpt_wallet_ledger')): ?>
                            <div class="px-3 pt-2 pb-1 mt-1 border-top" style="font-size:.7rem;font-weight:700;color:#999;letter-spacing:.06em;">LEDGER</div>
                            <?php if ($__rpt('frm_rpt_party_ledger')): ?>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/party_ledger?party_type=customer') ?>"><i class="fas fa-user fa-xs mr-2 text-muted"></i>Customer Ledger</a>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/party_ledger?party_type=supplier') ?>"><i class="fas fa-industry fa-xs mr-2 text-muted"></i>Supplier Ledger</a>
                            <?php endif; ?>
                            <?php if ($__rpt('frm_rpt_wallet_ledger')): ?>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/wallet_ledger') ?>"><i class="fas fa-wallet fa-xs mr-2 text-muted"></i>Wallet Ledger</a>
                            <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($__rpt('frm_rpt_po_grn_variance') || $__rpt('frm_rpt_stock_take_variance')): ?>
                            <div class="px-3 pt-2 pb-1 mt-1 border-top" style="font-size:.7rem;font-weight:700;color:#999;letter-spacing:.06em;">PURCHASING</div>
                            <?php if ($__rpt('frm_rpt_po_grn_variance')): ?>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/po_grn_variance') ?>"><i class="fas fa-exchange-alt fa-xs mr-2 text-muted"></i>PO vs GRN Variance</a>
                            <?php endif; ?>
                            <?php if ($__rpt('frm_rpt_stock_take_variance')): ?>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('reports/stock_take_variance') ?>"><i class="fas fa-balance-scale fa-xs mr-2 text-muted"></i>Stock-Take Variance</a>
                            <?php endif; ?>
                            <?php endif; ?>

                            <?php if ($__m('mod_billing')): ?>
                            <div class="px-3 pt-2 pb-1 mt-1 border-top" style="font-size:.7rem;font-weight:700;color:#999;letter-spacing:.06em;">OFFLINE SYNC</div>
                            <a class="dropdown-item py-1 text-warning font-weight-bold" style="font-size:.82rem;" href="<?= base_url('offline_sync/dashboard') ?>"><i class="fas fa-sync fa-xs mr-2"></i>Sync Dashboard</a>
                            <a class="dropdown-item py-1" style="font-size:.82rem;" href="<?= base_url('offline_sync/report') ?>"><i class="fas fa-file-alt fa-xs mr-2 text-muted"></i>Offline Report</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </li>
            <?php endif; ?>

            <?php if ($__m('mod_webstore')): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('webadmin') ?>">
                    <i class="fas fa-store fa-sm"></i> Online Store
                    <?php if ($__web_new > 0): ?><span class="badge badge-danger badge-pill badge-pulse"><?= $__web_new ?></span><?php endif; ?>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($__m('mod_billing')): ?>
            <!-- Order Requests -->
            <li class="nav-item">
                <a class="nav-link" href="<?= base_url('order_requests') ?>">
                    <i class="fas fa-clipboard-list fa-sm"></i> Orders
                    <?php if ($__pending_orders > 0): ?>
                    <span class="badge badge-danger badge-pill badge-pulse"><?= $__pending_orders ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>

        </ul>

        <!-- Right Nav -->
        <ul class="navbar-nav ml-auto align-items-center">

            <!-- Location Switcher / Indicator -->
            <?php if ($__is_owner && !empty($__all_locs)): ?>
            <li class="nav-item dropdown mr-1">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" title="Switch Location">
                    <i class="fas fa-map-marker-alt fa-sm"></i>
                    <span class="badge badge-<?= $__act_loc ? 'success' : 'warning' ?> ml-1" style="font-size:.65rem;">
                        <?= $__act_loc ? htmlspecialchars($__act_loc) : 'All Locations' ?>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow-sm">
                    <h6 class="dropdown-header">View as Location</h6>
                    <form method="POST" action="<?= base_url('locations/switch_location') ?>">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                        <input type="hidden" name="location_id" value="">
                        <button type="submit" class="dropdown-item <?= !$__act_loc ? 'font-weight-bold text-primary' : '' ?>">
                            <i class="fas fa-globe fa-xs mr-1"></i> All Locations
                        </button>
                    </form>
                    <div class="dropdown-divider"></div>
                    <?php foreach ($__all_locs as $__loc): ?>
                    <form method="POST" action="<?= base_url('locations/switch_location') ?>">
                        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                        <input type="hidden" name="location_id" value="<?= $__loc->id ?>">
                        <button type="submit" class="dropdown-item <?= ($this->session->userdata('active_location_id') == $__loc->id) ? 'font-weight-bold text-primary' : '' ?>">
                            <span class="badge badge-<?= $__loc->type === 'warehouse' ? 'primary' : 'success' ?> mr-1"><?= htmlspecialchars($__loc->code) ?></span>
                            <?= htmlspecialchars($__loc->name) ?>
                        </button>
                    </form>
                    <?php endforeach; ?>
                </div>
            </li>
            <?php elseif (!$__is_owner): ?>
            <li class="nav-item mr-1 d-flex align-items-center">
                <span class="badge badge-success" style="font-size:.7rem; padding:.3rem .5rem;">
                    <i class="fas fa-map-marker-alt fa-xs mr-1"></i>
                    <?= htmlspecialchars($this->session->userdata('location_name') ?: '') ?>
                </span>
            </li>
            <?php endif; ?>

            <!-- Active Financial Year -->
            <li class="nav-item mr-1 d-flex align-items-center">
                <span class="badge badge-info" style="font-size:.7rem; padding:.3rem .5rem;" title="Active Financial Year">
                    <i class="fas fa-calendar-alt fa-xs mr-1"></i>FY <?= htmlspecialchars($__active_fy) ?>
                </span>
            </li>

            <!-- Offline POS Status Badge -->
            <li class="nav-item mr-2 d-flex align-items-center">
                <span id="pos-sync-badge" class="sync-status-badge">🟢 Online</span>
            </li>

            <!-- Dark Mode Toggle -->
            <li class="nav-item mr-2">
                <button id="themeToggle" class="btn btn-outline-secondary btn-sm text-white border-secondary" title="Toggle Dark/Light Mode">🌙</button>
            </li>

            <?php if (in_array($__role, ['owner', 'superadmin', 'admin', 'manager'], true)): ?>
            <!-- Help & Support (SV ERP helpdesk) — badge = new replies from support -->
            <li class="nav-item mr-1">
                <a class="nav-link position-relative" href="<?= base_url('support') ?>" title="Help &amp; Support"><i class="fas fa-headset"></i>
                    <span id="supBadge" class="badge badge-danger" style="display:none;position:absolute;top:2px;right:-2px;font-size:.6rem;"></span></a>
            </li>
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                fetch('<?= base_url('support/api?action=notifications') ?>', { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); }).then(function (d) {
                        var n = (d && d.updates) ? d.updates.length : 0, b = document.getElementById('supBadge');
                        if (b && n) { b.textContent = n; b.style.display = ''; b.parentNode.title = n + ' support update' + (n > 1 ? 's' : '') + ' — click to view'; }
                    }).catch(function () {});
            });
            </script>
            <?php endif; ?>

            <!-- User Management & Settings dropdown -->
            <?php if ($__m('mod_user_management') || $__m('mod_settings') || $__is_sa): ?>
            <li class="nav-item dropdown mr-1">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" title="Admin Options">
                    <i class="fas fa-cog"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow-sm" style="min-width:200px;">
                    <?php if ($__m('mod_user_management') || $__is_sa): ?>
                    <a class="dropdown-item" href="<?= base_url('user_management') ?>">
                        <i class="fas fa-users fa-sm mr-1"></i> Manage Users
                    </a>
                    <?php endif; ?>
                    <?php if ($__is_sa): ?>
                    <a class="dropdown-item text-primary" href="<?= base_url('device_access') ?>">
                        <i class="fas fa-laptop fa-sm mr-1"></i> Device Access
                        <span class="badge badge-danger ml-1" id="devicePendingBadge" style="display:none;"></span>
                    </a>
                    <?php endif; ?>
                    <?php if ($__is_admin || $__is_sa): ?>
                    <a class="dropdown-item text-danger font-weight-bold" href="<?= base_url('emergency_day_close') ?>">
                        <i class="fas fa-exclamation-triangle fa-sm mr-1"></i> Emergency Day Management
                    </a>
                    <?php endif; ?>
                    <?php if ($__is_sa): ?>
                    <a class="dropdown-item" href="<?= base_url('error_logs') ?>">
                        <i class="fas fa-bug fa-sm mr-1"></i> Error Logs
                    </a>
                    <?php endif; ?>
                    <a class="dropdown-item" href="<?= base_url() ?>" target="_blank">
                        <i class="fas fa-store fa-sm mr-1"></i> Visit Online Store / Website →
                    </a>
                    <div class="dropdown-divider"></div>
                    <?php if ($__is_owner): ?>
                    <a class="dropdown-item" href="<?= base_url('settings') ?>">
                        <i class="fas fa-sliders-h fa-sm mr-1"></i> Settings
                    </a>
                    <a class="dropdown-item text-danger font-weight-bold" href="<?= base_url('data_reset') ?>">
                        <i class="fas fa-trash-alt fa-sm mr-1"></i> Data Reset
                    </a>
                    <?php endif; ?>
                </div>
            </li>
            <?php endif; ?>

            <!-- User Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                    <i class="fas fa-user-circle"></i>
                    <?= htmlspecialchars($this->session->userdata('name')) ?>
                    <?php if ($__is_sa): ?>
                    <span class="badge badge-superadmin ml-1" style="font-size:.6rem;">SA</span>
                    <?php else: ?>
                    <span class="badge badge-<?= $__role === 'admin' ? 'danger' : 'info' ?> ml-1" style="font-size:.65rem;"><?= $__role ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow-sm">
                    <span class="dropdown-item-text text-muted" style="font-size:.8rem;"><?= htmlspecialchars($this->session->userdata('email')) ?></span>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?= base_url() ?>" target="_blank">
                        <i class="fas fa-external-link-alt text-muted mr-2"></i> Visit Online Website
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="<?= base_url('logout') ?>"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
                </div>
            </li>
        </ul>
    </div>
</nav>
<?php endif; // top_nav ?>

<?php if ($__nav_layout === 'sidebar'): ?>
<!-- ═══ LEFT SIDEBAR ═══ -->
<!-- ── Quick Search Modal ── -->
<div id="qsOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:2000;" onclick="qsClose()"></div>
<div id="qsModal" style="display:none;position:fixed;top:80px;left:50%;transform:translateX(-50%);width:min(620px,96vw);background:#fff;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.25);z-index:2001;overflow:hidden;">
    <div style="display:flex;align-items:center;padding:14px 16px;border-bottom:1px solid #e2e8f0;gap:10px;">
        <i class="fas fa-search" style="color:#94a3b8;font-size:.9rem;"></i>
        <input id="qsInput" type="text" placeholder="Search products, customers…" autocomplete="off"
               style="flex:1;border:none;outline:none;font-size:.95rem;color:#1a202c;background:transparent;">
        <kbd onclick="qsClose()" style="cursor:pointer;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;padding:2px 7px;font-size:.72rem;color:#64748b;">Esc</kbd>
    </div>
    <div id="qsResults" style="max-height:420px;overflow-y:auto;padding:8px 0;">
        <div id="qsHint" style="padding:32px 16px;text-align:center;color:#94a3b8;font-size:.85rem;">Type at least 2 characters to search…</div>
    </div>
</div>

<div class="sb-overlay" id="sbOverlay" onclick="toggleSidebar()"></div>
<aside class="erp-sidebar" id="erpSidebar">
    <div class="sb-pin-btn" id="sbPinBtn" title="Collapse / Expand sidebar" onclick="sbToggleMini()">
        <i class="fas fa-chevron-left" id="sbPinIcon"></i>
    </div>

    <!-- Brand -->
    <div class="sb-brand" style="background: linear-gradient(180deg, #0d47a1, #1565c0); border-bottom: 2px solid #43a047; padding: 12px 14px;">
        <div class="sb-brand-icon" style="width: auto; height: auto;">
            <img src="<?= base_url('assets/web/logo.jpeg') ?>" alt="logo" style="height: 38px; width: auto; object-fit: contain; border-radius: 4px; background: rgba(0,0,0,0.25); padding: 2px 4px;">
        </div>
        <div class="sb-brand-text" style="overflow:hidden; margin-left: 8px;">
            <div style="font-size:.9rem;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:0.5px;"><?= htmlspecialchars($company['company_name'] ?? 'ERP') ?></div>
            <div style="font-size:.62rem;font-weight:500;color:#a5d6a7;letter-spacing:.04em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($company['company_tagline'] ?? 'Grace in Every Thread') ?></div>
        </div>
    </div>

    <?php
    $__uri  = $this->uri->uri_string();
    $__seg1 = $this->uri->segment(1);
    $__seg2 = $this->uri->segment(2);
    if (!function_exists('sbActive')) {
        function sbActive($segs, $uri) {
            foreach ((array)$segs as $s) { if (strpos($uri, $s) === 0) return ' sb-active'; }
            return '';
        }
    }
    // Helper: collapse-toggle section header
    function sbSection($id, $label, $icon, $uri_segs = []) {
        echo '<div class="sb-section">'
           . '<i class="' . $icon . ' mr-2" style="font-size:.8rem;width:16px;text-align:center;"></i>'
           . '<span class="sec-text">' . $label . '</span>'
           . '</div>';
    }
    function sbSectionEnd() { /* items are direct siblings; JS wraps them */ }
    ?>

    <!-- Dashboard -->
    <a class="sb-item<?= sbActive('dashboard',$__uri) ?>" href="<?= base_url('dashboard') ?>">
        <i class="fas fa-home"></i><span class="sb-label">Dashboard</span>
    </a>

    <?php if ($__m('mod_item_master')): ?>
    <?php sbSection('master','Master','fas fa-layer-group'); ?>
        <a class="sb-item<?= ($__seg1==='products'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('products') ?>"><i class="fas fa-box-open"></i><span class="sb-label">Products</span></a>
        <a class="sb-item<?= sbActive('products/add',$__uri) ?>" href="<?= base_url('products/add') ?>"><i class="fas fa-plus-circle"></i><span class="sb-label">Add Product</span></a>
        <a class="sb-item<?= ($__seg1==='plu'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('plu') ?>"><i class="fas fa-tags"></i><span class="sb-label">PLU / Multi-MRP</span></a>
        <a class="sb-item" href="<?= base_url('masters/categories') ?>"><i class="fas fa-folder"></i><span class="sb-label">Categories</span></a>
        <a class="sb-item" href="<?= base_url('masters/brands') ?>"><i class="fas fa-trademark"></i><span class="sb-label">Brands</span></a>
        <a class="sb-item" href="<?= base_url('masters/colours') ?>"><i class="fas fa-palette"></i><span class="sb-label">Color Master</span></a>
        <a class="sb-item" href="<?= base_url('hsn') ?>"><i class="fas fa-percent"></i><span class="sb-label">HSN / GST Codes</span></a>
        <a class="sb-item" href="<?= base_url('masters/racks') ?>"><i class="fas fa-th"></i><span class="sb-label">Racks</span></a>
        <a class="sb-item" href="<?= base_url('products/print_barcodes') ?>"><i class="fas fa-barcode"></i><span class="sb-label">Print Barcodes</span></a>
        <a class="sb-item" href="<?= base_url('products/import') ?>"><i class="fas fa-file-excel"></i><span class="sb-label">Import Excel</span></a>
    <?php sbSectionEnd(); ?>
    <?php endif; ?>

    <?php if ($__m('mod_item_master') && in_array(($company['retail_business_type'] ?? 'both'), ['clothing_only', 'both'], true)): ?>
    <?php sbSection('garments','Clothing / Garments','fas fa-tshirt'); ?>
        <a class="sb-item<?= ($__seg1==='garments'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('garments') ?>"><i class="fas fa-tshirt"></i><span class="sb-label">Style Master</span></a>
        <a class="sb-item<?= sbActive('garments/create',$__uri) ?>" href="<?= base_url('garments/create') ?>"><i class="fas fa-plus-circle"></i><span class="sb-label">Add Garment Style</span></a>
        <a class="sb-item<?= sbActive('garments/attributes',$__uri) ?>" href="<?= base_url('garments/attributes') ?>"><i class="fas fa-tags"></i><span class="sb-label">Clothing Attributes</span></a>
        <a class="sb-item<?= sbActive('garments/sizes',$__uri) ?>" href="<?= base_url('garments/sizes') ?>"><i class="fas fa-ruler-combined"></i><span class="sb-label">Size Sets &amp; Sizes</span></a>
        <a class="sb-item<?= sbActive('garments/colours',$__uri) ?>" href="<?= base_url('masters/colours') ?>"><i class="fas fa-palette"></i><span class="sb-label">Colours Palette</span></a>
        <a class="sb-item" href="<?= base_url('garments/print_tags') ?>" target="_blank"><i class="fas fa-barcode"></i><span class="sb-label">Print Barcode Tags</span></a>
        <a class="sb-item<?= sbActive('garments/import',$__uri) ?>" href="<?= base_url('garments/import') ?>"><i class="fas fa-file-csv"></i><span class="sb-label">Import CSV</span></a>
        <a class="sb-item<?= sbActive('garmentreports/size_matrix',$__uri) ?>" href="<?= base_url('garmentreports/size_matrix') ?>"><i class="fas fa-th-large"></i><span class="sb-label">Size Sales Matrix</span></a>
        <a class="sb-item<?= sbActive('garmentreports/colour_matrix',$__uri) ?>" href="<?= base_url('garmentreports/colour_matrix') ?>"><i class="fas fa-swatchbook"></i><span class="sb-label">Colour Analytics</span></a>
        <a class="sb-item<?= sbActive('garmentreports/stock_report',$__uri) ?>" href="<?= base_url('garmentreports/stock_report') ?>"><i class="fas fa-boxes"></i><span class="sb-label">Garment Stock Report</span></a>
    <?php sbSectionEnd(); ?>
    <?php endif; ?>

    <?php if ($__m('mod_guest_master')): ?>
    <?php sbSection('parties','Parties','fas fa-users'); ?>
        <a class="sb-item<?= sbActive('customers',$__uri) ?>" href="<?= base_url('customers') ?>"><i class="fas fa-user-friends"></i><span class="sb-label">Customers</span></a>
        <a class="sb-item<?= sbActive('suppliers',$__uri) ?>" href="<?= base_url('suppliers') ?>"><i class="fas fa-truck"></i><span class="sb-label">Suppliers</span></a>
    <?php sbSectionEnd(); ?>
    <?php endif; ?>

    <?php if ($__m('mod_billing')): ?>
    <?php sbSection('sales','Sales','fas fa-chart-line'); ?>
        <a class="sb-item<?= sbActive('billing/create',$__uri) ?>" href="<?= base_url('billing/create') ?>"><i class="fas fa-plus-circle"></i><span class="sb-label">New Invoice</span></a>
        <a class="sb-item<?= ($__seg1==='billing'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('billing') ?>"><i class="fas fa-file-invoice"></i><span class="sb-label">All Invoices</span></a>
        <?php if ($__m('mod_webstore')): ?><a class="sb-item<?= sbActive('webadmin',$__uri) ?>" href="<?= base_url('webadmin') ?>"><i class="fas fa-store"></i><span class="sb-label">Online Store Admin<?php if ($__web_new > 0): ?> <span class="sb-badge badge badge-pill"><?= $__web_new ?></span><?php endif; ?></span></a><?php endif; ?>
        <a class="sb-item" href="<?= base_url() ?>" target="_blank"><i class="fas fa-external-link-alt text-success"></i><span class="sb-label">Visit Website <i class="fas fa-external-link-alt" style="font-size:.55rem;opacity:.55;"></i></span></a>
        <a class="sb-item<?= sbActive('order_requests',$__uri) ?>" href="<?= base_url('order_requests') ?>"><i class="fas fa-clipboard-list"></i><span class="sb-label">Order Requests<?php if ($__pending_orders > 0): ?> <span class="sb-badge badge badge-pill"><?= $__pending_orders ?></span><?php endif; ?></span></a>
        <a class="sb-item<?= ($__seg1==='refunds'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('refunds') ?>"><i class="fas fa-undo-alt"></i><span class="sb-label">Refunds</span></a>
        <a class="sb-item<?= ($__seg1==='sales_returns'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('sales_returns') ?>"><i class="fas fa-file-minus"></i><span class="sb-label">Credit Notes</span></a>
        <a class="sb-item<?= ($__seg1==='einvoice')?' sb-active':'' ?>" href="<?= base_url('einvoice') ?>"><i class="fas fa-file-contract"></i><span class="sb-label">e-Invoice (IRN)</span></a>
        <?php if ($__is_sa): ?>
        <a class="sb-item<?= ($__seg1==='estimates'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('estimates') ?>"><i class="fas fa-file-alt"></i><span class="sb-label">Estimates</span></a>
        <?php endif; ?>
        <a class="sb-item<?= ($__seg1==='schemes')?' sb-active':'' ?>" href="<?= base_url('schemes') ?>"><i class="fas fa-tags"></i><span class="sb-label">Schemes</span></a>
    <?php sbSectionEnd(); ?>
    <?php endif; ?>

    <?php if ($__m('mod_purchase')): ?>
    <?php sbSection('purchase','Purchase','fas fa-shopping-cart'); ?>
        <a class="sb-item<?= sbActive('purchases/create',$__uri) ?>" href="<?= base_url('purchases/create') ?>"><i class="fas fa-plus-circle"></i><span class="sb-label">New Purchase</span></a>
        <a class="sb-item<?= ($__seg1==='purchases'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('purchases') ?>"><i class="fas fa-list"></i><span class="sb-label">All Purchases</span></a>
        <a class="sb-item<?= ($__seg1==='purchase_returns'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('purchase_returns') ?>"><i class="fas fa-undo"></i><span class="sb-label">Debit Notes</span></a>
        <a class="sb-item<?= sbActive('purchase_orders/create',$__uri) ?>" href="<?= base_url('purchase_orders/create') ?>"><i class="fas fa-plus"></i><span class="sb-label">New PO</span></a>
        <a class="sb-item<?= ($__seg1==='purchase_orders'&&$__seg2!=='create')?' sb-active':'' ?>" href="<?= base_url('purchase_orders') ?>"><i class="fas fa-file-contract"></i><span class="sb-label">Purchase Orders</span></a>
        <a class="sb-item<?= ($__seg1==='grn')?' sb-active':'' ?>" href="<?= base_url('grn') ?>"><i class="fas fa-truck-loading"></i><span class="sb-label">GRN</span></a>
        <a class="sb-item<?= ($__seg1==='rfq')?' sb-active':'' ?>" href="<?= base_url('rfq') ?>"><i class="fas fa-envelope-open-text"></i><span class="sb-label">RFQ</span></a>
        <a class="sb-item<?= ($__seg1==='purchase_suggestions')?' sb-active':'' ?>" href="<?= base_url('purchase_suggestions') ?>"><i class="fas fa-magic"></i><span class="sb-label">Auto Suggestions</span></a>
    <?php sbSectionEnd(); ?>
    <?php endif; ?>

    <?php if ($__m('mod_inventory') || $__m('mod_stock_transfers') || $__m('mod_stock_take') || $__m('mod_locations') || $__m('mod_warranty') || $__m('mod_loyalty')): ?>
    <?php sbSection('inventory','Inventory','fas fa-boxes'); ?>
        <?php if ($__m('mod_inventory')): ?>
        <a class="sb-item<?= sbActive('inventory',$__uri) ?>" href="<?= base_url('inventory') ?>"><i class="fas fa-cubes"></i><span class="sb-label">Stock Inventory</span></a>
        <?php endif; ?>
        <?php if ($__m('mod_stock_take')): ?>
        <a class="sb-item<?= sbActive('stock_take',$__uri) ?>" href="<?= base_url('stock_take') ?>"><i class="fas fa-clipboard-check"></i><span class="sb-label">Stock Take</span></a>
        <?php endif; ?>
        <?php if ($__m('mod_stock_transfers')): ?>
        <a class="sb-item<?= ($__seg1==='stock_transfers'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('stock_transfers') ?>"><i class="fas fa-exchange-alt"></i><span class="sb-label">Stock Transfers<?php if ($__pending_transfers > 0): ?> <span class="sb-badge badge badge-pill"><?= $__pending_transfers ?></span><?php endif; ?></span></a>
        <?php if (!isset($__tri_transit)) { $__tri_loc = (int)$this->session->userdata('location_id'); $__tri_transit = 0; if ($this->db->table_exists('stock_transfer_ins')) { $this->db->where('status','in_transit'); if ($__tri_loc) $this->db->where('to_location_id', $__tri_loc); $__tri_transit = $this->db->count_all_results('stock_transfer_ins'); } } ?>
        <a class="sb-item<?= ($__seg1==='stock_transfers'&&$__seg2==='transfer_in')?' sb-active':'' ?>" href="<?= base_url('stock_transfers/transfer_in') ?>"><i class="fas fa-truck-loading"></i><span class="sb-label">Transfer In<?php if ($__tri_transit > 0): ?> <span class="sb-badge badge badge-pill"><?= $__tri_transit ?></span><?php endif; ?></span></a>
        <?php endif; ?>
        <?php if ($__m('mod_loyalty')): ?>
        <a class="sb-item<?= ($__seg1==='loyalty'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('loyalty') ?>"><i class="fas fa-gift"></i><span class="sb-label">Loyalty Dashboard</span></a>
        <a class="sb-item<?= sbActive('loyalty/statement',$__uri) ?>" href="<?= base_url('loyalty/statement') ?>"><i class="fas fa-file-invoice"></i><span class="sb-label">Loyalty Statement</span></a>
        <a class="sb-item<?= sbActive('loyalty/settings',$__uri) ?>" href="<?= base_url('loyalty/settings') ?>"><i class="fas fa-cog"></i><span class="sb-label">Loyalty Setup</span></a>
        <?php endif; ?>
        <?php if ($__m('mod_warranty')): ?>
        <a class="sb-item<?= ($__seg1==='warranty'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('warranty') ?>"><i class="fas fa-shield-alt"></i><span class="sb-label">Warranty Dashboard</span></a>
        <a class="sb-item<?= sbActive('warranty/search',$__uri) ?>" href="<?= base_url('warranty/search') ?>"><i class="fas fa-barcode"></i><span class="sb-label">Serial Search</span></a>
        <a class="sb-item<?= sbActive('warranty/claims',$__uri) ?>" href="<?= base_url('warranty/claims') ?>"><i class="fas fa-tools"></i><span class="sb-label">Warranty Claims</span></a>
        <?php endif; ?>
        <?php if ($__m('mod_locations')): ?>
        <a class="sb-item<?= sbActive('locations/stock',$__uri) ?>" href="<?= base_url('locations/stock') ?>"><i class="fas fa-map-marker-alt"></i><span class="sb-label">Location Stock</span></a>
        <a class="sb-item<?= ($__seg1==='locations'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('locations') ?>"><i class="fas fa-store"></i><span class="sb-label">Manage Locations</span></a>
        <?php endif; ?>
        <?php if ($__is_sa): ?>
        <a class="sb-item<?= ($__seg1==='financial_years')?' sb-active':'' ?>" href="<?= base_url('financial_years') ?>"><i class="fas fa-calendar-alt"></i><span class="sb-label">Financial Years</span></a>
        <?php endif; ?>
        <a class="sb-item" href="<?= base_url('vendor-portal') ?>" target="_blank"><i class="fas fa-building"></i><span class="sb-label">Vendor Portal <i class="fas fa-external-link-alt" style="font-size:.55rem;opacity:.55;"></i></span></a>
    <?php sbSectionEnd(); ?>
    <?php endif; ?>

    <?php if ($__m('mod_accounts')): ?>
    <?php sbSection('accounts','Accounts','fas fa-rupee-sign'); ?>
        <a class="sb-item<?= sbActive('payments',$__uri) ?>" href="<?= base_url('payments') ?>"><i class="fas fa-wallet"></i><span class="sb-label">Payments & Ledger</span></a>
    <?php sbSectionEnd(); ?>
    <?php endif; ?>

    <?php if ($__m('mod_accounting')): ?>
    <?php sbSection('accounting','Accounting','fas fa-book'); ?>
        <a class="sb-item<?= sbActive('accounting/chart_of_accounts',$__uri) ?>" href="<?= base_url('accounting/chart_of_accounts') ?>"><i class="fas fa-sitemap"></i><span class="sb-label">Chart of Accounts</span></a>
        <a class="sb-item<?= sbActive('accounting/journal_entries',$__uri) ?>" href="<?= base_url('accounting/journal_entries') ?>"><i class="fas fa-book-open"></i><span class="sb-label">Journal Entries</span></a>
        <a class="sb-item<?= sbActive('accounting/bank_accounts',$__uri) ?>" href="<?= base_url('accounting/bank_accounts') ?>"><i class="fas fa-university"></i><span class="sb-label">Bank &amp; Cash Accounts</span></a>
        <a class="sb-item<?= sbActive('reports/trial_balance',$__uri) ?>" href="<?= base_url('reports/trial_balance') ?>"><i class="fas fa-balance-scale-right"></i><span class="sb-label">Trial Balance</span></a>
        <a class="sb-item<?= sbActive('reports/general_ledger',$__uri) ?>" href="<?= base_url('reports/general_ledger') ?>"><i class="fas fa-list-alt"></i><span class="sb-label">General Ledger</span></a>
    <?php sbSectionEnd(); ?>
    <?php endif; ?>

    <?php if ($__m('mod_reports')): ?>
    <?php sbSection('reports','Reports','fas fa-chart-bar'); ?>
        <a class="sb-item<?= sbActive('reports/fmcg_suite',$__uri) ?>" href="<?= base_url('reports/fmcg_suite') ?>"><i class="fas fa-rocket"></i><span class="sb-label">Reports Suite</span></a>
        <a class="sb-item<?= ($__seg1==='reports'&&!$__seg2)?' sb-active':'' ?>" href="<?= base_url('reports') ?>"><i class="fas fa-folder-open"></i><span class="sb-label">Daily &amp; Shift Reports</span></a>
        <a class="sb-item" href="<?= base_url('reports/sales') ?>"><i class="fas fa-chart-line"></i><span class="sb-label">Sales Report</span></a>
        <a class="sb-item" href="<?= base_url('reports/refunds_report') ?>"><i class="fas fa-undo"></i><span class="sb-label">Refunds Report</span></a>
        <a class="sb-item" href="<?= base_url('reports/profit_loss') ?>"><i class="fas fa-balance-scale"></i><span class="sb-label">Profit &amp; Loss</span></a>
        <a class="sb-item" href="<?= base_url('reports/purchases') ?>"><i class="fas fa-shopping-bag"></i><span class="sb-label">Purchase Report</span></a>
        <a class="sb-item" href="<?= base_url('reports/purchase_item_wise') ?>"><i class="fas fa-list"></i><span class="sb-label">Purchase (Item-Wise)</span></a>
        <a class="sb-item" href="<?= base_url('reports/outstanding') ?>"><i class="fas fa-exclamation-circle"></i><span class="sb-label">Outstanding Dues</span></a>
        <a class="sb-item" href="<?= base_url('reports/party_ledger?party_type=customer') ?>"><i class="fas fa-user-check"></i><span class="sb-label">Customer Ledger</span></a>
        <a class="sb-item" href="<?= base_url('reports/party_ledger?party_type=supplier') ?>"><i class="fas fa-truck"></i><span class="sb-label">Supplier Ledger</span></a>
        <a class="sb-item" href="<?= base_url('reports/wallet_ledger') ?>"><i class="fas fa-wallet"></i><span class="sb-label">Wallet Ledger</span></a>
        <a class="sb-item" href="<?= base_url('reports/portal_order_funnel') ?>"><i class="fas fa-filter"></i><span class="sb-label">Portal Order Funnel</span></a>
        <a class="sb-item" href="<?= base_url('reports/po_grn_variance') ?>"><i class="fas fa-exchange-alt"></i><span class="sb-label">PO vs GRN Variance</span></a>
        <a class="sb-item" href="<?= base_url('reports/stock_take_variance') ?>"><i class="fas fa-balance-scale"></i><span class="sb-label">Stock-Take Variance</span></a>
        <a class="sb-item" href="<?= base_url('reports/stock') ?>"><i class="fas fa-boxes"></i><span class="sb-label">Stock Report</span></a>
        <a class="sb-item" href="<?= base_url('reports/rack_stock_report') ?>"><i class="fas fa-th"></i><span class="sb-label">Rack Stock Report</span></a>
        <a class="sb-item" href="<?= base_url('reports/category_report') ?>"><i class="fas fa-folder"></i><span class="sb-label">Category Report</span></a>
        <a class="sb-item" href="<?= base_url('reports/brand_report') ?>"><i class="fas fa-trademark"></i><span class="sb-label">Brand Report</span></a>
        <a class="sb-item" href="<?= base_url('reports/gstr1') ?>"><i class="fas fa-file-invoice"></i><span class="sb-label">GSTR-1</span></a>
        <a class="sb-item" href="<?= base_url('reports/gstr2') ?>"><i class="fas fa-file-invoice"></i><span class="sb-label">GSTR-2</span></a>
        <a class="sb-item" href="<?= base_url('reports/gstr3b') ?>"><i class="fas fa-file-invoice-dollar"></i><span class="sb-label">GSTR-3B</span></a>
        <a class="sb-item" href="<?= base_url('reports/gst') ?>"><i class="fas fa-calculator"></i><span class="sb-label">GST Summary</span></a>
        <a class="sb-item" href="<?= base_url('reports/hsn_summary_report') ?>"><i class="fas fa-list"></i><span class="sb-label">HSN Summary</span></a>
        <a class="sb-item" href="<?= base_url('reports/tally_export') ?>"><i class="fas fa-file-export"></i><span class="sb-label">Tally Export</span></a>
    <?php sbSectionEnd(); ?>
    <?php endif; ?>

    <?php sbSection('operations','Operations','fas fa-sliders-h'); ?>
        <?php if ($__m('mod_day_open')): ?>
        <a class="sb-item" href="<?= base_url('shifts/day_open') ?>"><i class="fas fa-sun"></i><span class="sb-label">Day Open</span></a>
        <?php endif; ?>
        <?php if ($__m('mod_day_close')): ?>
        <a class="sb-item" href="<?= base_url('shifts/day_close') ?>"><i class="fas fa-moon"></i><span class="sb-label">Day Close</span></a>
        <?php endif; ?>
        <?php if ($__m('mod_shift_open')): ?>
        <a class="sb-item" href="<?= base_url('shifts/shift_open') ?>"><i class="fas fa-play-circle"></i><span class="sb-label">Shift Open</span></a>
        <?php endif; ?>
        <?php if ($__m('mod_shift_close')): ?>
        <a class="sb-item" href="<?= base_url('shifts/shift_close') ?>"><i class="fas fa-stop-circle"></i><span class="sb-label">Shift Close</span></a>
        <?php endif; ?>
        <?php if ($__m('mod_billing')): ?>
        <a class="sb-item<?= sbActive('offline_sync/dashboard',$__uri) ?>" href="<?= base_url('offline_sync/dashboard') ?>"><i class="fas fa-sync-alt"></i><span class="sb-label">Sync Dashboard</span></a>
        <?php endif; ?>
        <?php if ($__m('mod_cash_management')): ?>
        <div class="sb-divider mt-1 mb-1"></div>
        <a class="sb-item<?= sbActive('cashManagement',$__uri) ?>" href="<?= base_url('cashManagement') ?>"><i class="fas fa-cash-register"></i><span class="sb-label">Cash Dashboard</span></a>
        <a class="sb-item" href="<?= base_url('cashManagement/handover') ?>"><i class="fas fa-hand-holding-usd"></i><span class="sb-label">Cash Handover</span></a>
        <a class="sb-item" href="<?= base_url('cashManagement/ledger') ?>"><i class="fas fa-book"></i><span class="sb-label">Cash Ledger</span></a>
        <?php endif; ?>
    <?php sbSectionEnd(); ?>

    <?php if ($__is_owner): ?>
    <div class="sb-divider mt-1"></div>
    <a class="sb-item<?= sbActive('settings',$__uri) ?>" href="<?= base_url('settings') ?>"><i class="fas fa-cog"></i><span class="sb-label">Settings</span></a>
    <?php endif; ?>

    <!-- Bottom user panel -->
    <div class="sb-bottom">
        <div class="sb-bottom-text">
            <div class="sb-user-name">
                <?php if ($__is_sa): ?><span class="badge badge-superadmin mr-1">SA</span><?php endif; ?>
                <?= htmlspecialchars($this->session->userdata('name')) ?>
            </div>
            <div class="sb-user-email"><?= htmlspecialchars($this->session->userdata('email')) ?></div>
        </div>
        <?php if (in_array($__role, ['owner', 'superadmin', 'admin', 'manager'], true)): ?>
        <a href="<?= base_url('support') ?>" class="sb-logout" title="Help &amp; Support"><i class="fas fa-headset"></i><span class="sb-bottom-text">Support</span></a>
        <?php endif; ?>
        <a href="<?= base_url('logout') ?>" class="sb-logout"><i class="fas fa-sign-out-alt"></i><span class="sb-bottom-text">Logout</span></a>
    </div>
</aside>

<!-- Topbar (sidebar mode) -->
<div class="erp-topbar" style="display:flex;align-items:center;gap:8px;padding:0 16px;">
    <!-- Hamburger: mobile = open drawer, desktop = collapse sidebar -->
    <button class="tb-icon-btn d-lg-none" onclick="toggleSidebar()" title="Menu"><i class="fas fa-bars"></i></button>
    <button class="tb-icon-btn d-none d-lg-inline-flex" onclick="sbToggleMini()" title="Collapse sidebar"><i class="fas fa-bars"></i></button>

    <span class="tb-title"><?= isset($page_title) ? htmlspecialchars($page_title) : htmlspecialchars($company['company_name'] ?? '') ?></span>

    <!-- Global search bar -->
    <div class="tb-search d-none d-md-flex ml-2" onclick="qsOpen()" style="cursor:pointer;">
        <i class="fas fa-search" style="font-size:.75rem;opacity:.6;"></i>
        <span>Quick search</span>
        <kbd>Ctrl K</kbd>
    </div>

    <div class="ml-auto d-flex align-items-center" style="gap:6px;">
        <!-- Location indicator -->
        <?php if ($__act_loc): ?>
        <span class="badge badge-success d-none d-sm-inline-block" style="font-size:.68rem;padding:4px 8px;">
            <i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($__act_loc) ?>
        </span>
        <?php elseif ($__is_owner): ?>
        <span class="badge badge-secondary d-none d-sm-inline-block" style="font-size:.68rem;padding:4px 8px;">All Locations</span>
        <?php endif; ?>

        <!-- Active Financial Year -->
        <span class="badge badge-info d-none d-sm-inline-block" style="font-size:.68rem;padding:4px 8px;" title="Active Financial Year">
            <i class="fas fa-calendar-alt mr-1"></i>FY <?= htmlspecialchars($__active_fy) ?>
        </span>

        <!-- Order requests badge -->
        <?php if ($__m('mod_billing') && $__pending_orders > 0): ?>
        <a href="<?= base_url('order_requests') ?>" class="tb-icon-btn" title="<?= $__pending_orders ?> pending orders" style="position:relative;">
            <i class="fas fa-clipboard-list"></i>
            <span style="position:absolute;top:-2px;right:-2px;background:var(--ds-danger);color:#fff;font-size:.55rem;font-weight:700;border-radius:10px;padding:1px 4px;min-width:14px;text-align:center;"><?= $__pending_orders ?></span>
        </a>
        <?php endif; ?>

        <!-- Multiple Window Support: Open current page or module in new tab -->
        <div class="dropdown">
            <button class="tb-icon-btn" title="Open in New Window" data-toggle="dropdown"><i class="fas fa-external-link-alt"></i></button>
            <div class="dropdown-menu dropdown-menu-right shadow-sm" style="min-width:200px;font-size:.82rem;">
                <h6 class="dropdown-header" style="font-size:.7rem;">Open in New Window</h6>
                <a class="dropdown-item" href="<?= base_url('billing/create') ?>" target="_blank"><i class="fas fa-file-invoice mr-1 text-success"></i> New Bill</a>
                <a class="dropdown-item" href="<?= base_url('billing') ?>" target="_blank"><i class="fas fa-receipt mr-1 text-muted"></i> Billing List</a>
                <a class="dropdown-item" href="<?= base_url('customers') ?>" target="_blank"><i class="fas fa-users mr-1 text-info"></i> Customers</a>
                <a class="dropdown-item" href="<?= base_url('products') ?>" target="_blank"><i class="fas fa-box-open mr-1 text-warning"></i> Products</a>
                <a class="dropdown-item" href="<?= base_url('reports') ?>" target="_blank"><i class="fas fa-chart-bar mr-1 text-primary"></i> Reports</a>
                <a class="dropdown-item" href="<?= base_url('purchase_orders') ?>" target="_blank"><i class="fas fa-shopping-cart mr-1 text-danger"></i> Purchase Orders</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item font-weight-bold" href="javascript:window.open(window.location.href,'_blank')"><i class="fas fa-clone mr-1"></i> Current Page</a>
            </div>
        </div>

        <!-- Dark mode toggle -->
        <button id="themeToggle2" class="tb-icon-btn" title="Toggle light/dark">🌙</button>

        <!-- Settings -->
        <?php if ($__is_owner): ?>
        <a href="<?= base_url('settings') ?>" class="tb-icon-btn" title="Settings"><i class="fas fa-cog"></i></a>
        <?php endif; ?>

        <!-- User avatar -->
        <div class="tb-avatar" title="<?= htmlspecialchars($this->session->userdata('name')) ?>">
            <?= strtoupper(substr($this->session->userdata('name') ?: 'U', 0, 2)) ?>
        </div>
    </div>
</div>

<script>
// ── Sidebar collapse (fully hide / show) ──
(function(){
    var sb   = document.getElementById('erpSidebar');
    var tb   = document.querySelector('.erp-topbar');
    var icon = document.getElementById('sbPinIcon');
    var body = document.body;
    var hidden = localStorage.getItem('sb_mini') === '1';

    function apply(hide) {
        if (hide) {
            sb.classList.add('sb-mini');
            tb && tb.classList.add('tb-mini');
            body.classList.add('sb-collapsed');
            icon && icon.setAttribute('class', 'fas fa-bars');
        } else {
            sb.classList.remove('sb-mini');
            tb && tb.classList.remove('tb-mini');
            body.classList.remove('sb-collapsed');
            icon && icon.setAttribute('class', 'fas fa-bars');
        }
    }

    apply(hidden);

    window.sbToggleMini = function() {
        hidden = !hidden;
        localStorage.setItem('sb_mini', hidden ? '1' : '0');
        apply(hidden);
    };

    // Mobile open/close
    window.toggleSidebar = function() {
        sb.classList.toggle('sb-open');
        document.getElementById('sbOverlay').classList.toggle('active');
    };
})();

// ── Quick Search ──
(function(){
    var overlay = document.getElementById('qsOverlay');
    var modal   = document.getElementById('qsModal');
    var input   = document.getElementById('qsInput');
    var results = document.getElementById('qsResults');
    var hint    = document.getElementById('qsHint');
    var timer, activeIdx = -1, items = [];

    window.qsOpen = function() {
        overlay.style.display = 'block';
        modal.style.display   = 'block';
        input.value = '';
        results.innerHTML = '';
        results.appendChild(hint);
        hint.style.display = 'block';
        activeIdx = -1; items = [];
        setTimeout(function(){ input.focus(); }, 50);
    };

    window.qsClose = function() {
        overlay.style.display = 'none';
        modal.style.display   = 'none';
    };

    // Ctrl+K / Cmd+K
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); qsOpen(); return; }
        if (e.key === 'Escape') { qsClose(); return; }
        if (modal.style.display === 'none') return;
        if (e.key === 'ArrowDown') { e.preventDefault(); moveSel(1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); moveSel(-1); }
        else if (e.key === 'Enter') { if (activeIdx >= 0 && items[activeIdx]) items[activeIdx].click(); }
    });

    input.addEventListener('input', function() {
        clearTimeout(timer);
        var q = input.value.trim();
        if (q.length < 2) { results.innerHTML = ''; results.appendChild(hint); hint.style.display='block'; activeIdx=-1; items=[]; return; }
        hint.style.display = 'none';
        results.innerHTML = '<div style="padding:20px;text-align:center;color:#94a3b8;font-size:.8rem;"><i class="fas fa-spinner fa-spin"></i> Searching…</div>';
        timer = setTimeout(function(){ doSearch(q); }, 250);
    });

    function doSearch(q) {
        fetch('<?= base_url('quick_search/search_ajax') ?>?q=' + encodeURIComponent(q))
        .then(function(r){ return r.json(); })
        .then(function(data) {
            results.innerHTML = '';
            activeIdx = -1; items = [];
            var html = '';

            if (data.products && data.products.length) {
                html += '<div style="padding:6px 14px 4px;font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;">Products</div>';
                data.products.forEach(function(p) {
                    html += '<a href="<?= base_url('products/edit/') ?>' + p.id + '" class="qs-item" style="display:flex;align-items:center;padding:9px 14px;gap:12px;text-decoration:none;color:#1a202c;border-radius:0;">'
                          + '<span style="width:32px;height:32px;background:#f0f9ff;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-box" style="color:#0891B2;font-size:.8rem;"></i></span>'
                          + '<span style="flex:1;min-width:0;"><span style="display:block;font-size:.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + esc(p.name) + '</span>'
                          + '<span style="font-size:.72rem;color:#64748b;">' + esc(p.item_code) + (p.category ? ' · ' + esc(p.category) : '') + '</span></span>'
                          + '<span style="font-size:.82rem;font-weight:500;color:#0f172a;flex-shrink:0;">₹' + parseFloat(p.price).toFixed(2) + '</span>'
                          + '<span style="font-size:.72rem;color:#64748b;flex-shrink:0;margin-left:8px;">Qty: ' + p.stock + '</span>'
                          + '</a>';
                });
            }

            if (data.customers && data.customers.length) {
                html += '<div style="padding:6px 14px 4px;font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-top:4px;">Customers</div>';
                data.customers.forEach(function(c) {
                    html += '<a href="<?= base_url('customers/view/') ?>' + c.id + '" class="qs-item" style="display:flex;align-items:center;padding:9px 14px;gap:12px;text-decoration:none;color:#1a202c;">'
                          + '<span style="width:32px;height:32px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="fas fa-user" style="color:#16a34a;font-size:.8rem;"></i></span>'
                          + '<span style="flex:1;min-width:0;"><span style="display:block;font-size:.85rem;">' + esc(c.name) + '</span>'
                          + '<span style="font-size:.72rem;color:#64748b;">' + esc(c.phone) + (c.city ? ' · ' + esc(c.city) : '') + '</span></span>'
                          + '</a>';
                });
            }

            if (!html) {
                html = '<div style="padding:32px 16px;text-align:center;color:#94a3b8;font-size:.85rem;"><i class="fas fa-search" style="display:block;font-size:1.5rem;margin-bottom:8px;opacity:.3;"></i>No results for "' + esc(q) + '"</div>';
            }

            results.innerHTML = html;
            items = Array.from(results.querySelectorAll('.qs-item'));
            items.forEach(function(el) {
                el.addEventListener('mouseenter', function(){ setSel(items.indexOf(el)); });
            });
        })
        .catch(function(){ results.innerHTML = '<div style="padding:20px;text-align:center;color:#ef4444;font-size:.82rem;">Search error. Please try again.</div>'; });
    }

    function moveSel(dir) {
        var next = Math.max(0, Math.min(items.length - 1, activeIdx + dir));
        setSel(next);
    }
    function setSel(idx) {
        items.forEach(function(el, i) {
            el.style.background = (i === idx) ? '#f0f9ff' : '';
        });
        activeIdx = idx;
        if (items[idx]) items[idx].scrollIntoView({block:'nearest'});
    }
    function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
})();

// Wire secondary dark-mode toggle in topbar
var tt2 = document.getElementById('themeToggle2');
if (tt2) tt2.addEventListener('click', function(){ var t=document.getElementById('themeToggle'); if(t) t.click(); });

// ── Sidebar section collapse with chevron ──
(function() {
    // Superadmin: sections collapsed by default. Other roles: expanded by default.
    var IS_SA       = <?= $__is_sa ? 'true' : 'false' ?>;
    var STORAGE_KEY = 'sb_sec_v2_' + <?= json_encode($this->session->userdata('role') ?: 'user') ?>;
    var stored = {};
    try { stored = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); } catch(e) {}

    function save() { try { localStorage.setItem(STORAGE_KEY, JSON.stringify(stored)); } catch(e) {} }

    document.addEventListener('DOMContentLoaded', function() {
        var sb = document.getElementById('erpSidebar');
        if (!sb) return;

        sb.querySelectorAll('.sb-section').forEach(function(sec, idx) {
            var secId = 'sbSec' + idx;

            // Collect siblings until next .sb-section or .sb-divider
            var items = [], el = sec.nextElementSibling;
            while (el && !el.classList.contains('sb-section') && !el.classList.contains('sb-divider')) {
                items.push(el);
                el = el.nextElementSibling;
            }
            if (!items.length) return;

            // Determine initial state:
            // - Use stored preference if user has explicitly toggled before
            // - Otherwise: auto-open if this section contains the active page item, else collapse
            var hasActivePage = !!sec.parentNode.querySelector('.sb-active, .sb-sub.show');
            // Check within siblings (before they are moved into wrap)
            var _hasActive = items.some(function(el) {
                return el.classList.contains('sb-active') ||
                       (el.querySelector && el.querySelector('.sb-active')) ||
                       el.classList.contains('show');
            });
            var defaultCollapsed = !_hasActive;
            var isCollapsed = (secId in stored) ? stored[secId] : defaultCollapsed;

            // Wrap items in Bootstrap collapse container
            var wrap = document.createElement('div');
            wrap.id = secId;
            wrap.className = 'collapse' + (isCollapsed ? '' : ' show') + ' sb-sec-body';
            sec.parentNode.insertBefore(wrap, items[0]);
            items.forEach(function(i) { wrap.appendChild(i); });

            // Add chevron
            var chev = document.createElement('i');
            chev.className = 'fas fa-chevron-down sb-sec-chev';
            sec.appendChild(chev);
            if (isCollapsed) sec.classList.add('sec-collapsed');

            // Toggle on click — accordion: collapse all other sections first
            sec.addEventListener('click', function() {
                isCollapsed = !isCollapsed;
                stored[secId] = isCollapsed;
                save();
                if (!isCollapsed) {
                    // Close every other section body
                    document.querySelectorAll('#erpSidebar .sb-sec-body.show').forEach(function(other) {
                        if (other !== wrap) {
                            other.classList.remove('show');
                            var otherSec = other.previousElementSibling;
                            while (otherSec && !otherSec.classList.contains('sb-section')) {
                                otherSec = otherSec.previousElementSibling;
                            }
                            if (otherSec) otherSec.classList.add('sec-collapsed');
                        }
                    });
                    // Close all open sub-menus
                    document.querySelectorAll('#erpSidebar .sb-sub.show').forEach(function(sub) {
                        sub.classList.remove('show');
                        var tog = document.querySelector('[data-target="#' + sub.id + '"]');
                        if (tog) tog.classList.add('collapsed');
                    });
                    wrap.classList.add('show');
                } else {
                    wrap.classList.remove('show');
                }
                sec.classList.toggle('sec-collapsed', isCollapsed);
            });
        });

    });
})();
</script>
<?php endif; // sidebar ?>

<!-- Flash Messages -->
<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show mb-0 rounded-0" role="alert">
    <i class="fas fa-check-circle"></i> <?= $this->session->flashdata('success') ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
    <i class="fas fa-exclamation-circle"></i> <?= $this->session->flashdata('error') ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
<?php endif; ?>
<?php if ($this->session->flashdata('warning')): ?>
<div class="alert alert-warning alert-dismissible fade show mb-0 rounded-0" role="alert">
    <?= $this->session->flashdata('warning') ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
<?php endif; ?>

<div class="container-fluid mt-3 mb-5">

<script src="<?= base_url('assets/js/product-search.js?v=' . time()) ?>"></script>
<script>
window.BASE_URL = '<?= base_url() ?>';
// Dark/Light Mode
(function(){
    var isDark = localStorage.getItem('sv_dark_mode') === '1';
    if (isDark) { document.body.classList.add('dark-mode'); }
    document.addEventListener('DOMContentLoaded', function(){
        var btn = document.getElementById('themeToggle');
        if (!btn) return;
        btn.textContent = isDark ? '☀️' : '🌙';
        btn.addEventListener('click', function(){
            isDark = !isDark;
            document.body.classList.toggle('dark-mode', isDark);
            localStorage.setItem('sv_dark_mode', isDark ? '1' : '0');
            btn.textContent = isDark ? '☀️' : '🌙';
        });
    });
    // Remove focus from nav links after click to prevent blue bleed
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.nav-link, .dropdown-item').forEach(function(el){
            el.addEventListener('click', function(){ this.blur(); });
        });
    });
})();
</script>
<?php if ($__is_sa): ?>
<script>
// Poll for pending device approvals every 30s and show badge
function checkDevicePending() {
    fetch('<?= base_url('device_access/pending_count') ?>', {credentials:'same-origin'})
    .then(r => r.json()).then(d => {
        var badge = document.getElementById('devicePendingBadge');
        if (badge) {
            if (d.count > 0) { badge.textContent = d.count; badge.style.display = 'inline-block'; }
            else { badge.style.display = 'none'; }
        }
    }).catch(function(){});
}
checkDevicePending();
setInterval(checkDevicePending, 30000);
</script>
<?php endif; ?>
<?php endif; // user_id session check ?>
