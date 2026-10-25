<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $page_title ?? 'Vendor Portal' ?> — Supplier Portal</title>
<link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/all.min.css') ?>">
<style>
body{background:#f4f6fb;font-family:'Segoe UI',sans-serif;}
.vp-sidebar{background:#1a1a2e;min-height:100vh;width:240px;position:fixed;top:0;left:0;z-index:1000;}
.vp-sidebar .brand{padding:20px 16px;border-bottom:1px solid #ffffff22;}
.vp-sidebar .brand h6{color:#fff;margin:0;font-size:1rem;}
.vp-sidebar .brand small{color:#aaa;font-size:.7rem;}
.vp-sidebar nav a{display:block;color:#ccc;padding:10px 16px;text-decoration:none;font-size:.85rem;transition:background .2s;}
.vp-sidebar nav a:hover,.vp-sidebar nav a.active{background:#16213e;color:#fff;}
.vp-sidebar nav a i{width:20px;margin-right:8px;}
.vp-topbar{background:#fff;border-bottom:1px solid #e0e0e0;padding:12px 24px;display:flex;justify-content:space-between;align-items:center;}
.vp-main{margin-left:240px;padding:24px;}
</style>
</head>
<body>
<div class="vp-sidebar">
    <div class="brand">
        <h6><i class="fas fa-building mr-2"></i>Vendor Portal</h6>
        <small><?= htmlspecialchars($vendor_name ?? 'Supplier') ?></small>
    </div>
    <nav>
        <a href="<?= base_url('vendor-portal/dashboard') ?>" class="<?= uri_string()==='vendor-portal/dashboard'?'active':'' ?>"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a href="<?= base_url('vendor-portal/orders') ?>" class="<?= strpos(uri_string(),'vendor-portal/orders')!==false?'active':'' ?>"><i class="fas fa-file-invoice"></i>Purchase Orders</a>
        <a href="<?= base_url('vendor-portal/rfqs') ?>" class="<?= strpos(uri_string(),'vendor-portal/rfq')!==false?'active':'' ?>"><i class="fas fa-envelope-open-text"></i>RFQ / Quotations</a>
        <a href="<?= base_url('vendor-portal/messages') ?>" class="<?= strpos(uri_string(),'vendor-portal/messages')!==false?'active':'' ?>"><i class="fas fa-comments"></i>Messages</a>
        <a href="<?= base_url('vendor-portal/performance') ?>" class="<?= strpos(uri_string(),'vendor-portal/performance')!==false?'active':'' ?>"><i class="fas fa-chart-bar"></i>Performance</a>
        <hr style="border-color:#ffffff22;margin:8px 0;">
        <a href="<?= base_url('vendor-portal/logout') ?>"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </nav>
</div>
<div class="vp-topbar" style="margin-left:240px;">
    <span class="font-weight-bold"><?= $page_title ?? 'Dashboard' ?></span>
    <small class="text-muted"><i class="fas fa-clock mr-1"></i><?= date('d M Y, H:i') ?></small>
</div>
<div class="vp-main">
