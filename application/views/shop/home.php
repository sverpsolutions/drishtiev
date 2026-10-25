<?php
$store_disp = ucwords(strtolower($company['company_name'] ?? 'Suvasan Clothes'));
$section = function ($title, $sub, $rows, $link) {
    if (!$rows) return;
    echo '<div class="sec-head"><h2>' . html_escape($title) . '</h2><div class="orn">✦</div>' . ($sub ? '<p>' . html_escape($sub) . '</p>' : '') . '</div><div class="pgrid">';
    foreach ($rows as $p) $this->load->view('shop/_card', ['p' => $p]);
    echo '</div><div class="sec-link"><a class="btn btn-outline" href="' . $link . '">View all</a></div>';
};
$gcards = ['Women' => ['g-women', 'Kurtis, palazzos, ethnic &amp; western wear'], 'Men' => ['g-men', 'Shirts, tees &amp; everyday casuals'], 'Kids' => ['g-kids', 'Festive and everyday styles for little ones']];
$gcards = array_filter($gcards, function ($k) use ($gender_counts) { return $gender_counts[$k] > 0; }, ARRAY_FILTER_USE_KEY);
?>
<h1 class="sr-only"><?= html_escape($store_disp) ?> — Garment outlet in Mathura</h1>

<?php if ($banners): ?>
<section class="hero" aria-roledescription="carousel" aria-label="Featured collections">
  <div class="slides">
    <?php foreach ($banners as $k => $b): $link = $b->link ? (preg_match('#^https?://#', $b->link) ? $b->link : site_url($b->link)) : site_url('shop'); ?>
    <div class="slide" role="group" aria-roledescription="slide" aria-label="<?= $k + 1 ?> of <?= count($banners) ?>">
      <picture>
        <?php if ($b->image_mobile): ?><source media="(max-width: 760px)" srcset="<?= web_img($b->image_mobile) ?>"><?php endif; ?>
        <img src="<?= web_img($b->image_desktop ?: $b->image_mobile) ?>" alt="" <?= $k ? 'loading="lazy"' : 'fetchpriority="high"' ?> width="1920" height="640">
      </picture>
      <div class="slide-body">
        <span class="eyebrow"><?= html_escape($store_disp) ?></span>
        <h2><?= html_escape($b->title) ?></h2>
        <?php if ($b->offer_text): ?><span class="offer"><?= html_escape($b->offer_text) ?></span><?php endif; ?>
        <?php if ($b->subtitle): ?><p><?= html_escape($b->subtitle) ?></p><?php endif; ?>
        <a class="btn btn-gold" href="<?= html_escape($link) ?>"><?= html_escape($b->button_text) ?></a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (count($banners) > 1): ?>
  <button class="hero-nav prev" aria-label="Previous slide"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></button>
  <button class="hero-nav next" aria-label="Next slide"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></button>
  <div class="dots"><?php foreach ($banners as $k => $b): ?><button aria-label="Go to slide <?= $k + 1 ?>"></button><?php endforeach; ?></div>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if ($nav['categories']): ?>
<section class="section">
  <div class="wrap">
    <div class="sec-head"><h2>Shop by Category</h2><div class="orn">✦</div></div>
    <div class="cat-grid">
      <?php foreach ($nav['categories'] as $c): ?>
      <a class="cat-tile" href="<?= category_url($c) ?>">
        <span class="mono" aria-hidden="true"><?= html_escape(mb_substr($c->name, 0, 1)) ?></span>
        <div><h3><?= html_escape($c->name) ?></h3><span><?= (int)$c->n ?> style<?= $c->n == 1 ? '' : 's' ?></span></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section-alt"><div class="wrap"><?php $section('New Arrivals', 'The latest additions to our racks', $new, site_url('shop?sort=newest')); ?></div></section>

<?php if ($gcards): ?>
<section class="section">
  <div class="wrap">
    <div class="sec-head"><h2>Collections</h2><div class="orn">✦</div></div>
    <div class="gender-grid" style="grid-template-columns:repeat(<?= count($gcards) ?>,1fr)">
      <?php foreach ($gcards as $g => [$cls, $txt]): ?>
      <a class="gender-card <?= $cls ?>" href="<?= site_url('shop?gender=' . $g) ?>">
        <span class="orn-ring" aria-hidden="true"></span>
        <h3><?= $g === 'Kids' ? "Kids'" : $g . "'s" ?> Wear</h3><p><?= $txt ?></p>
        <span class="btn btn-gold btn-sm" style="align-self:flex-start">Explore</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($featured): ?><section class="section"><div class="wrap"><?php $section('Featured', 'Handpicked by our team', $featured, site_url('shop?featured=1')); ?></div></section><?php endif; ?>
<section class="section section-alt"><div class="wrap"><?php $section('Best Sellers', 'Customer favourites', $best, site_url('shop?sort=popular')); ?></div></section>
<?php if ($sale): ?><section class="section"><div class="wrap"><?php $section('Special Offers', 'Styles priced below MRP', $sale, site_url('shop?sale=1')); ?></div></section><?php endif; ?>

<section class="section">
  <div class="wrap benefits">
    <?php $pays = $this->ws->payment_methods(); ?>
    <div class="benefit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6z"/><path d="m9 12 2 2 4-4"/></svg><div><b>Genuine Products</b><span>Every piece sold from our own store stock</span></div></div>
    <div class="benefit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/></svg><div><b><?= isset($pays['razorpay']) ? 'Secure Payments' : 'Easy Payment' ?></b><span><?= html_escape(implode(' · ', array_map(function ($m) { return explode(' (', $m)[0]; }, $pays)) ?: 'Pay at checkout') ?></span></div></div>
    <div class="benefit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg><div><b>Order Tracking</b><span>Follow every step of your order online</span></div></div>
    <div class="benefit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 4h16v12H5.2L4 17.5z"/><path d="M8 9h8M8 12h5"/></svg><div><b>Store Support</b><span>Talk to our Mathura store team</span></div></div>
  </div>
</section>

<section class="section section-alt" id="about">
  <div class="wrap about">
    <div>
      <div class="sec-head" style="text-align:left;margin-bottom:12px"><h2>About <?= html_escape($store_disp) ?></h2></div>
      <div class="prose"><?= safe_html($about->content ?? '') ?></div>
      <a class="btn btn-outline" href="<?= site_url('page/about') ?>" style="margin-top:8px">Read our story</a>
    </div>
    <img src="<?= base_url('assets/web/logo-scene.jpg') ?>" alt="<?= html_escape($store_disp) ?> logo — Grace in Every Thread" loading="lazy" width="960" height="640">
  </div>
</section>

<section class="section" id="contact">
  <div class="wrap">
    <div class="sec-head"><h2>Visit &amp; Contact</h2><div class="orn">✦</div></div>
    <div class="contact-grid">
      <div class="card"><h3>Store Address</h3><p style="margin:0"><?= nl2br(html_escape(str_replace('\n', "\n", $company['company_address'] ?? ''))) ?></p></div>
      <div class="card"><h3>Get in Touch</h3>
        <?php if (!empty($company['company_phone'])): ?><p style="margin:0 0 6px">Phone: <a href="tel:<?= html_escape(preg_replace('/[^\d+]/', '', $company['company_phone'])) ?>"><?= html_escape($company['company_phone']) ?></a></p><?php endif; ?>
        <?php if (!empty($company['company_email'])): ?><p style="margin:0">Email: <a href="mailto:<?= html_escape($company['company_email']) ?>"><?= html_escape($company['company_email']) ?></a></p><?php endif; ?>
      </div>
      <div class="card"><h3>Business Details</h3>
        <p style="margin:0 0 6px"><?= html_escape($company['company_name'] ?? '') ?></p>
        <?php if (!empty($company['company_gstin'])): ?><p style="margin:0">GSTIN: <strong><?= html_escape($company['company_gstin']) ?></strong></p><?php endif; ?>
      </div>
    </div>
  </div>
</section>
