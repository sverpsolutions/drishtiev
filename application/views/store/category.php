<div class="st-page-wide">

  <div class="st-search-bar" style="background:#fff;border:1.5px solid #e0e0e0;margin-bottom:14px;height:44px;">
    <i class="fa fa-search" style="color:#999;"></i>
    <input type="text" id="stCatalogSearch" placeholder="Search for products…" value="<?= htmlspecialchars($search ?? '') ?>" style="color:#333;">
  </div>

  <?php if (!empty($categories)): ?>
  <div class="st-chip-row">
    <a href="<?= base_url('store/products') ?>" class="st-chip <?= empty($selected_category) ? 'active' : '' ?>">
      <div class="st-chip-icon">🛍️</div>
      <div class="st-chip-label">All</div>
    </a>
    <?php foreach ($categories as $cat): ?>
    <a href="<?= base_url('store/category/' . $cat->id) ?>" class="st-chip <?= ($selected_category == $cat->id) ? 'active' : '' ?>">
      <div class="st-chip-icon">
        <?php if (!empty($cat->image)): ?>
          <img src="<?= base_url('assets/uploads/' . $cat->image) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:16px;">
        <?php else: ?>🛒<?php endif; ?>
      </div>
      <div class="st-chip-label"><?= htmlspecialchars($cat->name) ?></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="st-section-head" style="margin-top:6px;">
    <div class="st-section-title"><?= isset($category) ? htmlspecialchars($category->name) : ($search ? 'Results for "' . htmlspecialchars($search) . '"' : 'All Products') ?></div>
  </div>

  <div class="st-grid" id="stCatalogGrid" data-category="<?= (int)($selected_category ?? 0) ?>">
    <?php if (empty($products)): ?>
      <div class="st-empty" style="grid-column:1/-1;"><i class="fa fa-box-open"></i><div>No products found.</div></div>
    <?php else: ?>
      <?php foreach ($products as $p): $qty = $cart[$p->id]['qty'] ?? 0; ?>
        <?php include APPPATH . 'views/store/_product_card.php'; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>
<script src="<?= base_url('assets/js/store-catalog.js') ?>"></script>
