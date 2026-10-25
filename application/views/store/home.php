<div class="st-page">

  <div class="st-hero">
    <div class="st-hero-tag">⚡ Delivered in minutes</div>
    <div class="st-hero-title">Fresh Groceries<br>Daily Essentials</div>
    <div class="st-hero-sub"><?= htmlspecialchars($company['company_tagline'] ?? 'Quality you trust, values you deserve') ?></div>
  </div>

  <?php if (!empty($categories)): ?>
  <div class="st-section">
    <div class="st-section-head">
      <div class="st-section-title">Shop by Category</div>
      <a href="<?= base_url('store/products') ?>" class="st-section-link">See all</a>
    </div>
    <div class="st-chip-row">
      <?php foreach ($categories as $cat): ?>
      <a href="<?= base_url('store/category/' . $cat->id) ?>" class="st-chip">
        <div class="st-chip-icon">
          <?php if (!empty($cat->image)): ?>
            <img src="<?= base_url('assets/uploads/' . $cat->image) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:16px;">
          <?php else: ?>🛒<?php endif; ?>
        </div>
        <div class="st-chip-label"><?= htmlspecialchars($cat->name) ?></div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($past_products)): ?>
  <div class="st-section">
    <div class="st-section-head">
      <div class="st-section-title">🔁 Buy It Again</div>
    </div>
    <div class="st-grid">
      <?php foreach ($past_products as $p): $qty = $cart[$p->id]['qty'] ?? 0; ?>
        <?php include APPPATH . 'views/store/_product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="st-section">
    <div class="st-section-head">
      <div class="st-section-title">🔥 Popular Right Now</div>
      <a href="<?= base_url('store/products') ?>" class="st-section-link">See all</a>
    </div>
    <?php if (empty($products)): ?>
      <div class="st-empty"><i class="fa fa-box-open"></i><div>No products available yet.</div></div>
    <?php else: ?>
    <div class="st-grid">
      <?php foreach ($products as $p): $qty = $cart[$p->id]['qty'] ?? 0; ?>
        <?php include APPPATH . 'views/store/_product_card.php'; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

