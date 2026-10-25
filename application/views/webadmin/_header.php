<?php
$menu = [
    ['Orders', null],
    ['index', 'Dashboard', 'webadmin'],
    ['orders', 'All Orders', 'webadmin/orders', $new_count],
    ['Catalog', null],
    ['products', 'Products & Images', 'webadmin/products'],
    ['__garments', 'Product Master & Variants', 'garments'],
    ['__sizes', 'Sizes', 'garments/sizes'],
    ['__colours', 'Colours', 'garments/colours'],
    ['__cats', 'Categories', 'masters/categories'],
    ['__brands', 'Brands', 'masters/brands'],
    ['__import', 'Bulk Import', 'garments/import'],
    ['Marketing', null],
    ['banners', 'Homepage Banners', 'webadmin/banners'],
    ['coupons', 'Coupons', 'webadmin/coupons'],
    ['pages', 'Website Content', 'webadmin/pages'],
    ['People', null],
    ['customers', 'Customers', 'webadmin/customers'],
    ['Insights', null],
    ['reports', 'Reports', 'webadmin/reports'],
    ['settings', 'Store Settings', 'webadmin/settings'],
];
?><!doctype html>
<html lang="en-IN">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title><?= html_escape($title ?? 'Online Store') ?> · Store Admin</title>
<link rel="icon" href="<?= base_url('assets/web/icon-32.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=Jost:wght@400;500;600&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/web/admin.css?v=20261010') ?>">
</head>
<body>
<div class="layout">
  <aside class="side" id="side">
    <a class="brand" href="<?= site_url('webadmin') ?>"><img src="<?= base_url('assets/web/logo.png') ?>" alt="<?= html_escape($company['company_name'] ?? '') ?>"></a>
    <nav aria-label="Store admin">
      <?php foreach ($menu as $m): if ($m[1] === null): ?><div class="grp"><?= $m[0] ?></div><?php continue; endif; ?>
      <a href="<?= site_url($m[2]) ?>" class="<?= $active === $m[0] ? 'on' : '' ?>"><?= $m[1] ?><?php if (!empty($m[3])): ?><span class="pill"><?= (int)$m[3] ?></span><?php endif; ?></a>
      <?php endforeach; ?>
      <div class="grp">Links</div>
      <a href="<?= base_url() ?>" target="_blank" rel="noopener">View Website ↗</a>
      <a href="<?= site_url('dashboard') ?>">Back to ERP</a>
    </nav>
  </aside>
  <div class="main">
    <div class="topbar">
      <div style="display:flex;gap:10px;align-items:center">
        <button class="menu-toggle" onclick="document.getElementById('side').classList.toggle('open')" aria-label="Menu">☰</button>
        <strong><?= html_escape($title ?? '') ?></strong>
      </div>
      <span class="who"><?= html_escape($this->session->userdata('name')) ?> · <a href="<?= site_url('logout') ?>">Logout</a></span>
    </div>
    <div class="content">
<?php if ($m = $this->session->flashdata('success')): ?><div class="alert alert-ok"><?= html_escape($m) ?></div><?php endif; ?>
<?php if ($m = $this->session->flashdata('error')): ?><div class="alert alert-error"><?= html_escape($m) ?></div><?php endif; ?>
