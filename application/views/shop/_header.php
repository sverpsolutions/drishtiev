<?php
$store = $company['company_name'] ?? 'Suvasan Clothes';
$store_disp = ucwords(strtolower($store));
$title = $meta_title ?: $store_disp;
$desc  = $meta_desc ?: ($company['web_meta_description'] ?? '');
$seg = $this->uri->segment(1);
$genders = $nav['genders'] ?? [];
$has_kids = (bool)array_intersect(['Boys', 'Girls'], $genders);
$ver = '20261010';
?><!doctype html>
<html lang="en-IN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= html_escape($title) ?></title>
<meta name="description" content="<?= html_escape($desc) ?>">
<?php if ($noindex): ?><meta name="robots" content="noindex, follow"><?php endif; ?>
<link rel="canonical" href="<?= html_escape($canonical) ?>">
<meta property="og:type" content="<?= isset($p) ? 'product' : 'website' ?>">
<meta property="og:site_name" content="<?= html_escape($store_disp) ?>">
<meta property="og:title" content="<?= html_escape($title) ?>">
<meta property="og:description" content="<?= html_escape($desc) ?>">
<meta property="og:url" content="<?= html_escape($canonical) ?>">
<meta property="og:image" content="<?= html_escape($og_image) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#4d0c14">
<link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('assets/web/icon-32.png') ?>">
<link rel="apple-touch-icon" href="<?= base_url('assets/web/icon-180.png') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@400;500;600&display=swap">
<link rel="stylesheet" href="<?= base_url('assets/web/shop.css?v=' . $ver) ?>">
<script>window.SHOP = {base: <?= json_encode(base_url()) ?>, csrfName: <?= json_encode($this->security->get_csrf_token_name()) ?>, csrfHash: <?= json_encode($this->security->get_csrf_hash()) ?>};</script>
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org', '@type' => 'ClothingStore', 'name' => $store_disp,
    'url' => base_url(), 'logo' => base_url('assets/web/logo.png'), 'image' => base_url('assets/web/og-image.jpg'),
    'telephone' => $company['company_phone'] ?? null,
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'LGF-21, Ground Floor, Moti Manzil, Radhika Vihar', 'addressLocality' => $company['company_city'] ?? 'Mathura',
                  'addressRegion' => 'Uttar Pradesh', 'postalCode' => $company['company_pincode'] ?? '281001', 'addressCountry' => 'IN'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<?php if (!empty($company['web_announcement'])): ?>
<div class="announce"><?= html_escape($company['web_announcement']) ?></div>
<?php endif; ?>
<header class="site-header">
  <div class="hdr">
    <button class="icon-btn menu-btn" data-open=".mainnav" aria-label="Open menu">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
    <a class="logo" href="<?= base_url() ?>" aria-label="<?= html_escape($store_disp) ?> home">
      <img src="<?= base_url('assets/web/logo.png') ?>" alt="<?= html_escape($store_disp) ?> — Grace in Every Thread" width="480" height="268">
    </a>
    <form class="search" action="<?= site_url('shop') ?>" method="get" role="search">
      <label for="q" class="sr-only">Search products</label>
      <input id="q" name="q" type="search" placeholder="Search kurtis, palazzos, shirts…" value="<?= html_escape($this->input->get('q', true) ?? '') ?>" autocomplete="off">
      <button type="submit" aria-label="Search"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></button>
      <div class="suggest" id="suggest"></div>
    </form>
    <nav class="hdr-icons" aria-label="Account and cart">
      <a class="icon-btn hide-sm" href="<?= site_url($customer ? 'account' : 'account/login') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>
        <span><?= $customer ? 'Account' : 'Login' ?></span>
      </a>
      <a class="icon-btn" href="<?= site_url('wishlist') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 20s-7-4.4-9-9.2C1.7 7.6 3.8 4 7.3 4c2 0 3.6 1.1 4.7 2.7C13.1 5.1 14.7 4 16.7 4c3.5 0 5.6 3.6 4.3 6.8C19 15.6 12 20 12 20z"/></svg>
        <span class="hide-sm">Wishlist</span><span class="count" data-wish-count data-n="0"></span>
      </a>
      <a class="icon-btn" href="<?= site_url('cart') ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 7h14l-1.2 12.1a2 2 0 0 1-2 1.9H8.2a2 2 0 0 1-2-1.9z"/><path d="M9 7V6a3 3 0 0 1 6 0v1"/></svg>
        <span class="hide-sm">Bag</span><span class="count" data-cart-count data-n="<?= (int)$cart_count ?>"><?= (int)$cart_count ?: '' ?></span>
      </a>
    </nav>
  </div>
  <nav class="mainnav" aria-label="Main">
    <ul>
      <li><a href="<?= base_url() ?>" class="<?= $seg === null ? 'is-active' : '' ?>">Home</a></li>
      <li><a href="<?= site_url('shop?sort=newest') ?>">New Arrivals</a></li>
      <?php if (in_array('Women', $genders, true)): ?><li><a href="<?= site_url('shop?gender=Women') ?>">Women</a></li><?php endif; ?>
      <?php if (in_array('Men', $genders, true)): ?><li><a href="<?= site_url('shop?gender=Men') ?>">Men</a></li><?php endif; ?>
      <?php if ($has_kids): ?><li><a href="<?= site_url('shop?gender=Kids') ?>">Kids</a></li><?php endif; ?>
      <?php foreach (array_slice($nav['categories'] ?? [], 0, 5) as $c): ?>
      <li><a href="<?= category_url($c) ?>"><?= html_escape($c->name) ?></a></li>
      <?php endforeach; ?>
      <li><a href="<?= site_url('shop?sale=1') ?>">Offers</a></li>
      <li><a href="<?= site_url('track') ?>">Track Order</a></li>
      <li class="hide-lg"><a href="<?= site_url($customer ? 'account' : 'account/login') ?>"><?= $customer ? 'My Account' : 'Login / Register' ?></a></li>
    </ul>
  </nav>
</header>
<div class="drawer-backdrop"></div>
<main id="main">
<?php if ($m = $this->session->flashdata('success')): ?><div class="wrap" style="padding-top:16px"><div class="alert alert-ok" role="status"><?= html_escape($m) ?></div></div><?php endif; ?>
<?php if ($m = $this->session->flashdata('error')): ?><div class="wrap" style="padding-top:16px"><div class="alert alert-error" role="alert"><?= html_escape($m) ?></div></div><?php endif; ?>
