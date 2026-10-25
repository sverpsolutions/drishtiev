<?php // FILE: application/views/portal/products.php — Sandal House Premium Catalog ?>
<style>
/* ── Banner (for home) ─────────────────────────────────────── */
.bb-banner-wrap { position: relative; border-radius: 16px; overflow: hidden; margin-bottom: 14px; background: #fff; box-shadow: 0 4px 16px rgba(59,35,20,0.08); }
.bb-banner-track { display: flex; transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1); width: 100%; }
.bb-banner-slide { width: 100%; flex-shrink: 0; padding: 22px 18px; display: flex; align-items: center; gap: 16px; background: linear-gradient(135deg, var(--gold-light), #fff); box-sizing: border-box; }
.bb-banner-slide.s2 { background: linear-gradient(135deg, #e3f2fd, #fff); }
.bb-banner-slide.s3 { background: linear-gradient(135deg, #e8f5e9, #fff); }
.bb-banner-slide.s4 { background: linear-gradient(135deg, #fff3e0, #fff); }
.bb-banner-emoji { font-size: 3rem; flex-shrink: 0; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1)); animation: floaty 3s ease-in-out infinite; }
@keyframes floaty { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
.bb-banner-tag { display: inline-block; background: var(--primary); color: #fff; font-size: 0.6rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; margin-bottom: 6px; }
.bb-banner-title { font-size: 1.15rem; font-weight: 800; color: var(--primary); line-height: 1.2; margin: 0 0 4px; }
.bb-banner-sub { font-size: 0.72rem; color: var(--text-muted); margin: 0 0 10px; line-height: 1.3; }
.bb-banner-cta { display: inline-block; background: #fff; color: var(--primary); border: 1.5px solid var(--primary); border-radius: 8px; padding: 4px 12px; font-size: 0.7rem; font-weight: 700; text-decoration: none; }
.bb-banner-cta:hover { background: var(--primary); color: #fff; }
.bb-banner-dots { position: absolute; bottom: 8px; left: 0; right: 0; display: flex; justify-content: center; gap: 6px; }
.bb-banner-dot { width: 6px; height: 6px; border-radius: 50%; background: rgba(59,35,20,0.2); cursor: pointer; transition: all 0.3s; }
.bb-banner-dot.active { width: 16px; border-radius: 3px; background: var(--primary); }

/* ── Food Marks ──────────────────────────────────────────── */
.bb-food-dot {
  display: inline-block; width: 13px; height: 13px;
  border-width: 1.5px; border-style: solid;
  border-radius: 2px; vertical-align: middle; margin-right: 3px;
  position: relative; background: #fff; flex-shrink: 0;
}
.bb-food-dot.veg { border-color: #2e7d32; }
.bb-food-dot.veg::after { content:''; display:block; width:6px; height:6px; background:#2e7d32; border-radius:50%; position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); }
.bb-food-dot.nveg { border-color: #c62828; }
.bb-food-dot.nveg::after { content:''; position:absolute; top:1px; left:1.5px; width:0; height:0; border-left:4px solid transparent; border-right:4px solid transparent; border-bottom:8px solid #c62828; }

/* ── Category Chips ──────────────────────────────────────── */
.bb-chip-scroll {
  display: flex; gap: 12px; overflow-x: auto;
  padding: 5px 2px 15px; scrollbar-width: none; margin-bottom: 14px;
}
.bb-chip-scroll::-webkit-scrollbar { display: none; }
.bb-chip {
  flex-shrink: 0; display: flex; flex-direction: column; align-items: center; gap: 8px;
  width: 76px; cursor: pointer; text-decoration: none; transition: transform 0.2s;
}
.bb-chip:hover { transform: translateY(-2px); text-decoration: none; }
.bb-chip-img-wrap {
  width: 64px; height: 64px; border-radius: 50%; background: #f4f6f8; 
  display: flex; align-items: center; justify-content: center; overflow: hidden;
  box-shadow: 0 3px 6px rgba(0,0,0,0.08); border: 2px solid transparent;
  transition: border-color 0.2s;
}
.bb-chip:hover .bb-chip-img-wrap { border-color: var(--primary); box-shadow: 0 4px 10px rgba(0,0,0,0.12); }
.bb-chip.active .bb-chip-img-wrap { border-color: var(--primary); border-width: 2px; }
.bb-chip-img { width: 100%; height: 100%; object-fit: cover; }
.bb-chip-text {
  font-size: 0.7rem; font-weight: 600; color: #37474f; text-align: center;
  white-space: normal; line-height: 1.2; display: -webkit-box;
  -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.bb-chip:hover .bb-chip-text, .bb-chip.active .bb-chip-text { color: var(--primary); }

/* ── Veg filter toggle ───────────────────────────────────── */
.bb-veg-toggle {
  display: flex; align-items: center; gap: 8px;
  cursor: pointer; font-size: 0.75rem; font-weight: 600; color: #37474f;
}
.bb-veg-toggle input[type=checkbox] { display: none; }
.bb-veg-pill {
  width: 36px; height: 20px; background: #e0e0e0;
  border-radius: 10px; position: relative; transition: background 0.2s;
}
.bb-veg-pill::after {
  content: ''; position: absolute;
  top: 3px; left: 3px;
  width: 14px; height: 14px;
  background: #fff; border-radius: 50%;
  transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.bb-veg-toggle input:checked + .bb-veg-pill { background: #2e7d32; }
.bb-veg-toggle input:checked + .bb-veg-pill::after { transform: translateX(16px); }

/* ── Product Grid ────────────────────────────────────────── */
.bb-prod-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
@media (min-width: 600px) { .bb-prod-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 900px) { .bb-prod-grid { grid-template-columns: repeat(4, 1fr); } }

.bb-pcard {
  background: #fff; border-radius: 12px;
  border: 1px solid #f0f0f0;
  overflow: hidden; transition: all 0.2s;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  position: relative; display: flex; flex-direction: column;
}
.bb-pcard:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  border-color: #e2e8f0;
}
.bb-pcard-img-wrap { position: relative; flex-shrink: 0; }
.bb-pcard-img { width: 100%; height: 150px; object-fit: cover; display: block; }
.bb-pcard-no-img {
  width: 100%; height: 150px;
  background: linear-gradient(135deg, #f5f5f5, #eeeeee);
  display: flex; align-items: center; justify-content: center;
  color: #bdbdbd; font-size: 2.5rem;
}
@media (max-width: 400px) { .bb-pcard-img, .bb-pcard-no-img { height: 120px; } }
.bb-pcard-oos-badge {
  position: absolute; top: 8px; right: 8px;
  background: rgba(0,0,0,0.65); color: #fff;
  border-radius: 6px; font-size: 0.6rem; font-weight: 700;
  padding: 3px 8px;
}
.bb-pcard-body { padding: 10px; flex: 1; display: flex; flex-direction: column; }
.bb-pcard-brand { font-size: 0.62rem; font-weight: 600; color: #90a4ae; text-transform: uppercase; letter-spacing: 0.4px; margin: 0 0 3px; }
.bb-pcard-name {
  font-size: 0.8rem; font-weight: 600; color: var(--primary);
  margin: 0 0 5px; line-height: 1.3; flex: 1;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.bb-pcard-name a { color: inherit; text-decoration: none; }
.bb-pcard-name a:hover { color: var(--primary-light); }
.bb-pcard-rating { display: flex; align-items: center; gap: 3px; margin-bottom: 6px; }
.bb-star { color: var(--gold-dark); font-size: 0.75rem; }
.bb-rating-val { font-size: 0.72rem; font-weight: 600; color: #37474f; }
.bb-rating-cnt { font-size: 0.65rem; color: #90a4ae; }

/* ── Price Block ── */
.bb-pcard-price-row { display: flex; align-items: flex-end; justify-content: space-between; margin-top: auto; }
.bb-price-block { display: flex; flex-direction: column; gap: 1px; }
.bb-price-sp   { font-size: 0.95rem; font-weight: 500; color: var(--gold-dark); line-height: 1; }
.bb-price-mrp  { font-size: 0.68rem; font-weight: 400; color: #9e9e9e; text-decoration: line-through; line-height: 1; }
.bb-price-save {
  display: inline-block; font-size: 0.58rem; font-weight: 600;
  background: #e8f5e9; color: #2e7d32;
  border-radius: 4px; padding: 1px 5px; margin-top: 2px; line-height: 1.5;
}
.bb-price-only { font-size: 0.95rem; font-weight: 500; color: var(--gold-dark); line-height: 1; }
.bb-pcard-unit  { font-size: 0.62rem; color: #90a4ae; margin-left: 2px; }

/* Cart Spinner */
.bb-spinner {
  display: flex; align-items: center; justify-content: space-between;
  background: #38a169; border-radius: 6px;
  height: 32px; width: 72px; padding: 0 2px;
}
.bb-spin-btn {
  background: transparent; border: none; color: #fff;
  width: 24px; height: 100%; display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 1rem; font-weight: 600; transition: background 0.2s;
}
.bb-spin-btn:hover { background: rgba(0,0,0,0.1); border-radius: 4px; }
.bb-spin-val { font-size: 0.8rem; font-weight: 700; color: #fff; text-align: center; flex: 1; }

.bb-add-btn {
  border: 1px solid #38a169; border-radius: 6px;
  background: #f0fff4;
  color: #38a169; font-size: 0.75rem; font-weight: 800;
  height: 32px; padding: 0 16px;
  font-family: 'Poppins', sans-serif; cursor: pointer;
  transition: all 0.2s; white-space: nowrap;
  text-transform: uppercase;
}
.bb-add-btn:hover { background: #38a169; color: #fff; }
.bb-add-btn-oos {
  border: 1px solid #e2e8f0; border-radius: 6px; background: #f7fafc; color: #a0aec0;
  font-size: 0.72rem; font-weight: 600; height: 32px; padding: 0 10px;
  font-family: 'Poppins', sans-serif; cursor: not-allowed;
}

/* Floating Cart Bar */
.bb-float-cart {
  position: fixed; bottom: 74px; left: 50%;
  transform: translateX(-50%) translateY(20px);
  background: #38a169;
  color: #fff; width: calc(100% - 24px); max-width: 480px;
  border-radius: 12px; padding: 12px 18px;
  display: flex; align-items: center; justify-content: space-between;
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
  z-index: 997; opacity: 0;
  transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
  pointer-events: none;
}
.bb-float-cart.show { opacity: 1; transform: translateX(-50%) translateY(0); pointer-events: all; }
.bb-float-cart-info { display: flex; align-items: center; gap: 10px; }
.bb-float-cart-icon { font-size: 1.4rem; display: none; }
.bb-float-cart-count { font-size: 0.85rem; font-weight: 800; line-height: 1.2; }
.bb-float-cart-total { font-size: 0.75rem; font-weight: 600; opacity: 0.9; }
.bb-float-cart-btn {
  background: transparent; color: #fff;
  border: none; padding: 4px 8px;
  font-size: 0.85rem; font-weight: 800;
  font-family: 'Poppins', sans-serif; cursor: pointer;
  text-decoration: none; display: flex; align-items: center; gap: 4px;
}

/* Empty state */
.bb-empty { text-align: center; padding: 50px 20px; color: #90a4ae; }
.bb-empty-icon { font-size: 3rem; margin-bottom: 12px; display: block; opacity: 0.4; }

/* Wrapper */
.bb-prods { padding: 12px; max-width: 900px; margin: 0 auto; }
</style>

<div class="bb-prods">

  <!-- Search bar (within page) + Veg toggle -->
  <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
    <form method="get" action="<?= base_url('portal/products') ?>" style="flex:1; position:relative;" id="productSearchForm">
      <?php if ($selected_category): ?>
        <input type="hidden" name="category" value="<?= htmlspecialchars($selected_category) ?>">
      <?php endif; ?>
      <input type="text" name="q" id="productSearch"
             style="width:100%; padding:0 40px 0 36px; border-radius:24px; font-size:16px; background:#fff; border:1.5px solid #e0e0e0; height:38px; font-family:'Poppins',sans-serif; outline:none; box-sizing:border-box;"
             placeholder="Search products…" value="<?= htmlspecialchars($search) ?>"
             autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
      <i class="fa fa-search" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#90a4ae; font-size:0.82rem; pointer-events:none;"></i>
      <?php if ($search): ?>
        <button type="button" onclick="window.location='<?= base_url('portal/products'.($selected_category?'?category='.urlencode($selected_category):'')) ?>'"
                style="position:absolute; right:40px; top:50%; transform:translateY(-50%); background:none; border:none; color:#90a4ae; cursor:pointer; font-size:1rem; line-height:1; padding:0;">&#215;</button>
      <?php endif; ?>
      <button type="submit" style="position:absolute; right:4px; top:50%; transform:translateY(-50%); background:linear-gradient(135deg,var(--primary),var(--primary-light)); border:none; border-radius:20px; color:#fff; width:30px; height:30px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.78rem; padding:0;" title="Search">
        <i class="fa fa-search"></i>
      </button>
    </form>
  </div>

  <?php if (!empty($is_home)): ?>
  <!-- Banner Slider (Only shown on home page) -->
  <div class="bb-banner-wrap">
    <div class="bb-banner-track" id="bannerTrack">
      <?php
      $custom_banners = [];
      for($i = 1; $i <= 4; $i++) {
          if (!empty($company['portal_banner_'.$i])) {
              $custom_banners[] = [
                  'img' => base_url($company['portal_banner_'.$i]),
                  'link' => !empty($company['portal_banner_'.$i.'_link']) ? $company['portal_banner_'.$i.'_link'] : ''
              ];
          }
      }
      
      if (!empty($custom_banners)): 
          foreach($custom_banners as $idx => $banner): 
            $href = '';
            if ($banner['link']) {
                if (strpos($banner['link'], 'http') === 0) {
                    $href = $banner['link'];
                } elseif (strpos($banner['link'], '?') === 0) {
                    $href = base_url('portal/products' . $banner['link']);
                } else {
                    $href = base_url(ltrim($banner['link'], '/'));
                }
            }
          ?>
            <div class="bb-banner-slide" style="padding:0; background:none;">
                <?php if ($href): ?>
                <a href="<?= htmlspecialchars($href) ?>" style="display:block; width:100%;">
                    <img src="<?= $banner['img'] ?>" style="width:100%; height:auto; display:block; border-radius:16px;">
                </a>
                <?php else: ?>
                <img src="<?= $banner['img'] ?>" style="width:100%; height:auto; display:block; border-radius:16px;">
                <?php endif; ?>
            </div>
      <?php 
          endforeach; 
      else: 
      ?>
          <div class="bb-banner-slide s1">
            <div class="bb-banner-emoji">🛒</div>
            <div>
              <span class="bb-banner-tag">🔥 Hot Deals</span>
              <p class="bb-banner-title">Shop &amp; Save<br>Big Today!</p>
              <p class="bb-banner-sub">Browse 1000+ products at best prices</p>
              <a href="#productGrid" class="bb-banner-cta">Shop Now →</a>
            </div>
          </div>
          <div class="bb-banner-slide s2">
            <div class="bb-banner-emoji">⚡</div>
            <div>
              <span class="bb-banner-tag">⚡ Fast Delivery</span>
              <p class="bb-banner-title">Order Now,<br>Get it Today!</p>
              <p class="bb-banner-sub">Place order & we'll deliver fast</p>
              <a href="#productGrid" class="bb-banner-cta">Order Now →</a>
            </div>
          </div>
          <div class="bb-banner-slide s3">
            <div class="bb-banner-emoji">🌿</div>
            <div>
              <span class="bb-banner-tag">🌿 Fresh Stock</span>
              <p class="bb-banner-title">Fresh Products<br>Every Day!</p>
              <p class="bb-banner-sub">Quality guaranteed on every order</p>
              <a href="#productGrid" class="bb-banner-cta">Browse →</a>
            </div>
          </div>
          <div class="bb-banner-slide s4">
            <div class="bb-banner-emoji">💰</div>
            <div>
              <span class="bb-banner-tag">💰 Best Prices</span>
              <p class="bb-banner-title"><?= htmlspecialchars($company['company_name'] ?? 'Discount Store') ?><br>Promise!</p>
              <p class="bb-banner-sub">MRP prices on every single product</p>
              <a href="#productGrid" class="bb-banner-cta">Explore →</a>
            </div>
          </div>
      <?php endif; ?>
    </div>
    <div class="bb-banner-dots" id="bannerDots">
      <?php 
      $dot_count = !empty($custom_banners) ? count($custom_banners) : 4;
      for($j=0; $j<$dot_count; $j++): ?>
          <div class="bb-banner-dot <?= $j===0 ? 'active' : '' ?>" data-idx="<?= $j ?>"></div>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Category Selection -->
  <?php if (!empty($categories)): ?>
    <?php if (!empty($is_home)): ?>
    <!-- Home Page Category Grid -->
    <div style="margin-bottom:20px; padding: 0 4px;">
      <div style="font-size:1rem; font-weight:800; color:#1a202c; margin-bottom:12px;">Shop by Category</div>
      <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:12px 10px;">
        <?php foreach (array_slice($categories, 0, 12) as $cat): ?>
        <a href="<?= base_url('portal/products?category='.urlencode($cat->name)) ?>" style="text-decoration:none; display:flex; flex-direction:column; align-items:center;">
          <div style="width:100%; aspect-ratio:1; background:#f7fafc; border-radius:12px; overflow:hidden; display:flex; align-items:center; justify-content:center; margin-bottom:6px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <?php if (!empty($cat->image)): ?>
              <img src="<?= base_url('assets/uploads/categories/'.$cat->image) ?>" style="width:100%; height:100%; object-fit:cover;" alt="<?= htmlspecialchars($cat->name) ?>">
            <?php else: ?>
              <span style="font-size:1.8rem; color:#cbd5e0;">📁</span>
            <?php endif; ?>
          </div>
          <div style="font-size:0.65rem; font-weight:600; color:#4a5568; text-align:center; line-height:1.2; word-break:break-word; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;"><?= htmlspecialchars($cat->name) ?></div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php else: ?>
    <!-- Category Chip Scroll (Search/Category pages) -->
    <div class="bb-chip-scroll">
      <a href="<?= base_url('portal/products'.($search?'?q='.urlencode($search):'')) ?>"
         class="bb-chip <?= empty($selected_category) ? 'active' : '' ?>">
        <div class="bb-chip-img-wrap" style="font-size: 1.5rem; background: #e3f2fd; border-color: #bbdefb;">🍽️</div>
        <div class="bb-chip-text">All Items</div>
      </a>
      <?php foreach ($categories as $cat): ?>
      <a href="<?= base_url('portal/products?category='.urlencode($cat->name).($search?'&q='.urlencode($search):'')) ?>"
         class="bb-chip <?= $selected_category === $cat->name ? 'active' : '' ?>">
        <div class="bb-chip-img-wrap">
          <?php if (!empty($cat->image)): ?>
            <img src="<?= base_url('assets/uploads/categories/'.$cat->image) ?>" class="bb-chip-img" alt="<?= htmlspecialchars($cat->name) ?>">
          <?php else: ?>
            <span style="font-size:1.5rem; color:#90a4ae;">📁</span>
          <?php endif; ?>
        </div>
        <div class="bb-chip-text"><?= htmlspecialchars($cat->name) ?></div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Buy Again Horizontal Strip -->
  <?php if (!empty($past_products) && empty($search) && empty($selected_category)): ?>
  <div style="margin-bottom:16px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
      <div style="font-size:0.88rem; font-weight:700; color:var(--primary); display:flex; align-items:center; gap:6px;">
        <span style="display:inline-block; width:3px; height:14px; background:linear-gradient(var(--gold),var(--primary)); border-radius:2px;"></span>
        🔄 Buy Again
      </div>
      <a href="<?= base_url('portal/products') ?>" style="font-size:0.72rem; font-weight:600; color:var(--primary); text-decoration:none;">See all →</a>
    </div>
    <div class="bb-prod-scroll" style="display:flex; gap:10px; overflow-x:auto; padding-bottom:8px;">
      <?php foreach ($past_products as $pp):
        $pp_sp  = (float)($pp->selling_price ?? 0);
        $pp_mrp = (float)($pp->mrp ?? 0);
        $pp_has_discount = ($pp_sp > 0 && $pp_mrp > 0 && $pp_sp < $pp_mrp);
        $pp_display_price = $pp_has_discount ? $pp_sp : $pp_mrp;
        $pp_save = $pp_has_discount ? round($pp_mrp - $pp_sp, 2) : 0;
      ?>
      <div style="flex-shrink:0; width:130px; background:#fff; border-radius:12px; overflow:hidden; border:1.5px solid #f0f0f0; box-shadow:0 2px 8px rgba(59,35,20,0.06);">
        <?php if (!empty($pp->image1)): ?>
          <img src="<?= base_url('assets/uploads/products/'.$pp->image1) ?>" style="width:100%;height:90px;object-fit:cover;">
        <?php elseif (!empty($company['company_logo'])): ?>
          <div style="height:90px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;"><img src="<?= base_url($company['company_logo']) ?>" style="max-width:50%;max-height:50%;opacity:0.55;object-fit:contain;"></div>
        <?php else: ?>
          <div style="height:90px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;color:#bdbdbd;font-size:1.8rem;">🛒</div>
        <?php endif; ?>
        <div style="padding:8px;">
          <p style="font-size:0.7rem;font-weight:600;color:var(--primary);margin:0 0 4px;line-height:1.3;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;"><?= htmlspecialchars($pp->name) ?></p>
          <div style="margin-bottom:4px;">
            <span style="font-size:0.82rem;font-weight:500;color:var(--gold-dark);">₹<?= number_format($pp_display_price, 2) ?></span>
            <?php if ($pp_has_discount): ?>
              <span style="display:block;font-size:0.62rem;font-weight:400;color:#9e9e9e;text-decoration:line-through;">MRP ₹<?= number_format($pp_mrp, 2) ?></span>
              <span style="display:inline-block;font-size:0.56rem;font-weight:600;background:#e8f5e9;color:#2e7d32;border-radius:4px;padding:1px 4px;">Save ₹<?= number_format($pp_save, 2) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($pp->stock_qty > 0): ?>
          <button onclick="bbAddToCart(<?= $pp->id ?>)" style="width:100%;border:none;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;border-radius:6px;padding:5px 0;font-size:0.68rem;font-weight:600;font-family:'Poppins',sans-serif;cursor:pointer;">＋ Add</button>
          <?php else: ?>
          <button disabled style="width:100%;border:none;background:#f5f5f5;color:#90a4ae;border-radius:6px;padding:5px 0;font-size:0.68rem;font-weight:500;font-family:'Poppins',sans-serif;cursor:not-allowed;">Sold Out</button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Section Heading -->
  <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
    <div style="font-size:0.88rem; font-weight:700; color:var(--primary); display:flex; align-items:center; gap:6px;">
      <span style="display:inline-block; width:3px; height:14px; background:linear-gradient(var(--gold),var(--primary)); border-radius:2px;"></span>
      <?= $selected_category ? '📂 '.htmlspecialchars($selected_category) : '✨ All Products' ?>
    </div>
    <span id="prodCount" style="font-size:0.7rem; color:#78909c; font-weight:600;"><?= count($products) ?> items</span>
  </div>

  <!-- Product Grid -->
  <?php if (empty($products)): ?>
  <div class="bb-empty">
    <span class="bb-empty-icon">📦</span>
    <div style="font-size:0.9rem; font-weight:600; color:#37474f;">No products found</div>
    <div style="font-size:0.78rem; margin-top:4px;">Try a different search or category</div>
    <?php if ($search || $selected_category): ?>
    <a href="<?= base_url('portal/products') ?>" style="display:inline-block;margin-top:14px;background:var(--primary);color:#fff;border-radius:20px;padding:8px 20px;font-size:0.78rem;font-weight:600;text-decoration:none;">Clear Filters</a>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <?php 
    $total_prods = count($products);
    $show_strips = !empty($is_home) && empty($search) && empty($selected_category) && $total_prods > 8;
    if ($show_strips): ?>
      <div style="font-size:1.05rem; font-weight:800; color:#1a202c; margin-bottom:12px;">🔥 Trending Items</div>
      <div class="bb-prod-scroll" style="display:flex; gap:12px; overflow-x:auto; padding-bottom:14px; margin-bottom:24px; scrollbar-width:none;">
  <?php else: ?>
      <div class="bb-prod-grid" id="productGrid">
  <?php endif; ?>

    <?php 
    $p_idx = 0;
    foreach ($products as $p):
      $p_idx++;
      if ($show_strips && $p_idx === 9): ?>
        </div>
        <div style="font-size:1.05rem; font-weight:800; color:#1a202c; margin-bottom:12px;">🛒 Daily Staples</div>
        <div class="bb-prod-scroll" style="display:flex; gap:12px; overflow-x:auto; padding-bottom:14px; margin-bottom:24px; scrollbar-width:none;">
      <?php elseif ($show_strips && $p_idx === 17): ?>
        </div>
        <div style="font-size:1.05rem; font-weight:800; color:#1a202c; margin-bottom:12px;">✨ Explore More</div>
        <div class="bb-prod-grid" id="productGrid">
      <?php endif; ?>
      <?php
      $p_veg   = isset($p->is_veg) ? (int)$p->is_veg : 1;
      $in_cart  = isset($cart[$p->id]);
      $cart_qty = $in_cart ? $cart[$p->id]['qty'] : 0;
      $p_sp     = (float)($p->selling_price ?? 0);
      $p_mrp    = (float)($p->mrp ?? 0);
      $has_disc = ($p_sp > 0 && $p_mrp > 0 && $p_sp < $p_mrp);
      $disp_price = $has_disc ? $p_sp : $p_mrp;
      $save_amt   = $has_disc ? round($p_mrp - $p_sp, 2) : 0;
    ?>
    <div class="bb-pcard product-card-wrapper" <?= ($show_strips && $p_idx < 17) ? 'style="min-width:145px; max-width:160px; flex-shrink:0;"' : '' ?> data-veg="<?= $p_veg ?>" data-name="<?= htmlspecialchars($p->name) ?>" data-code="<?= htmlspecialchars($p->item_code ?? '') ?>">
      <div class="bb-pcard-img-wrap">
        <div style="position:absolute; bottom:6px; left:6px; background:rgba(255,255,255,0.95); font-size:0.55rem; font-weight:800; padding:2px 6px; border-radius:4px; color:#1a202c; box-shadow:0 2px 4px rgba(0,0,0,0.1); display:flex; align-items:center; gap:3px; z-index:2;">
          <span style="color:#e53e3e;">⚡</span> 12 MINS
        </div>
        <?php if ($p->stock_qty <= 0): ?>
          <span class="bb-pcard-oos-badge" style="z-index:2;">Sold Out</span>
        <?php endif; ?>
        <a href="<?= base_url('portal/product_detail/'.$p->id) ?>">
          <?php if (!empty($p->image1)): ?>
            <img src="<?= base_url('assets/uploads/products/'.$p->image1) ?>" class="bb-pcard-img" alt="<?= htmlspecialchars($p->name) ?>">
          <?php elseif (!empty($company['company_logo'])): ?>
            <div class="bb-pcard-no-img"><img src="<?= base_url($company['company_logo']) ?>" style="max-width:55%;max-height:55%;opacity:0.55;object-fit:contain;"></div>
          <?php else: ?>
            <div class="bb-pcard-no-img">🛒</div>
          <?php endif; ?>
        </a>
      </div>
      <div class="bb-pcard-body">
        <?php if (!empty($p->brand)): ?>
        <p class="bb-pcard-brand"><?= htmlspecialchars($p->brand) ?></p>
        <?php endif; ?>
        <p class="bb-pcard-name">
          <a href="<?= base_url('portal/product_detail/'.$p->id) ?>"><?= htmlspecialchars($p->name) ?></a>
        </p>
        <?php if (isset($p->avg_rating) && $p->review_count > 0): ?>
        <div class="bb-pcard-rating">
          <span class="bb-star">★</span>
          <span class="bb-rating-val"><?= number_format($p->avg_rating, 1) ?></span>
          <span class="bb-rating-cnt">(<?= $p->review_count ?>)</span>
        </div>
        <?php else: ?>
        <div class="bb-pcard-rating" style="margin-bottom:6px;"><span style="font-size:0.65rem;color:#b0bec5;">☆ New</span></div>
        <?php endif; ?>
        <div class="bb-pcard-price-row">
          <div class="bb-price-block">
            <?php if ($has_disc): ?>
              <!-- SP with MRP strikethrough -->
              <span class="bb-price-sp">₹<?= number_format($disp_price, 2) ?></span>
              <span class="bb-price-mrp">MRP ₹<?= number_format($p_mrp, 2) ?></span>
              <span class="bb-price-save">Save ₹<?= number_format($save_amt, 2) ?></span>
            <?php else: ?>
              <!-- Only MRP, no discount -->
              <span class="bb-price-only">₹<?= number_format($disp_price, 2) ?></span>
              <span class="bb-pcard-unit">/ <?= htmlspecialchars($p->unit ?? 'unit') ?></span>
            <?php endif; ?>
          </div>
          <div class="bb-cart-action" data-product-id="<?= $p->id ?>">
            <?php if ($p->stock_qty <= 0): ?>
              <span class="bb-add-btn-oos">Sold Out</span>
            <?php elseif ($in_cart): ?>
              <div class="bb-spinner">
                <button class="bb-spin-btn" onclick="bbChangeQty(<?= $p->id ?>, -1)">−</button>
                <span class="bb-spin-val bb-qty-display"><?= $cart_qty ?></span>
                <button class="bb-spin-btn" onclick="bbChangeQty(<?= $p->id ?>, 1)">+</button>
              </div>
            <?php else: ?>
              <button class="bb-add-btn" onclick="bbAddToCart(<?= $p->id ?>)">＋ Add</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <div style="margin-top:10px; font-size:0.72rem; color:#90a4ae; text-align:center;" id="visibleCount">
    Showing <?= count($products) ?> product(s)
  </div>
  <?php endif; ?>

</div><!-- /bb-prods -->

<!-- Floating Cart Bar -->
<div class="bb-float-cart" id="floatCart">
  <div class="bb-float-cart-info">
    <span class="bb-float-cart-icon">🛒</span>
    <div>
      <div class="bb-float-cart-count" id="floatCartCount">0 Items</div>
      <div class="bb-float-cart-total">Total: <span id="floatCartTotal">₹0.00</span></div>
    </div>
  </div>
  <a href="<?= base_url('portal/order') ?>" class="bb-float-cart-btn">
    View Cart →
  </a>
</div>

<script>
var BASE_URL = '<?= base_url() ?>';

/* ── Veg only filter ── */
function bbFilterVeg(isVeg) {
  var cards = document.querySelectorAll('.product-card-wrapper');
  var vis = 0;
  cards.forEach(function(c) {
    var v = parseInt(c.dataset.veg) === 1;
    var show = !isVeg || v;
    c.style.display = show ? '' : 'none';
    if (show) vis++;
  });
  var vc = document.getElementById('visibleCount');
  if (vc) vc.textContent = 'Showing ' + vis + ' product(s)';
}

/* ── Add to Cart ── */
function bbAddToCart(pid) {
  var fd = new FormData();
  fd.append('product_id', pid);
  fetch(BASE_URL + 'portal/add_to_cart_ajax', {
    method:'POST', body:fd,
    headers:{'X-Requested-With':'XMLHttpRequest'}
  })
  .then(r=>r.json())
  .then(d=>{
    if (d.status === 'success') {
      bbUpdateCartUI(pid, d.qty);
      if (typeof bbUpdateCartBadge === 'function') bbUpdateCartBadge(d.cart_count);
      bbRefreshFloatCart();
    } else { alert(d.message || 'Error'); }
  });
}

/* ── Change Qty ── */
function bbChangeQty(pid, delta) {
  var container = document.querySelector('.bb-cart-action[data-product-id="'+pid+'"]');
  if (!container) return;
  var valEl = container.querySelector('.bb-qty-display');
  var cur = valEl ? parseInt(valEl.textContent) || 0 : 0;
  var next = cur + delta;
  var fd = new FormData();
  fd.append('product_id', pid);
  fd.append('qty', next);
  fetch(BASE_URL + 'portal/update_cart_ajax', {
    method:'POST', body:fd,
    headers:{'X-Requested-With':'XMLHttpRequest'}
  })
  .then(r=>r.json())
  .then(d=>{
    if (d.status === 'success') {
      bbUpdateCartUI(pid, d.qty);
      if (typeof bbUpdateCartBadge === 'function') bbUpdateCartBadge(d.cart_count);
      bbRefreshFloatCart();
    }
  });
}

function bbUpdateCartUI(pid, qty) {
  var containers = document.querySelectorAll('.bb-cart-action[data-product-id="'+pid+'"]');
  containers.forEach(function(c) {
    if (qty <= 0) {
      c.innerHTML = '<button class="bb-add-btn" onclick="bbAddToCart('+pid+')">＋ Add</button>';
    } else {
      c.innerHTML =
        '<div class="bb-spinner">' +
          '<button class="bb-spin-btn" onclick="bbChangeQty('+pid+',-1)">−</button>' +
          '<span class="bb-spin-val bb-qty-display">'+qty+'</span>' +
          '<button class="bb-spin-btn" onclick="bbChangeQty('+pid+',1)">+</button>' +
        '</div>';
    }
  });
}

/* ── Floating Cart Bar ── */
function bbRefreshFloatCart() {
  fetch(BASE_URL + 'portal/get_cart_ajax', {
    headers:{'X-Requested-With':'XMLHttpRequest'}
  })
  .then(r=>r.json())
  .then(d=>{
    var bar = document.getElementById('floatCart');
    if (d.cart_count > 0) {
      document.getElementById('floatCartCount').textContent = d.cart_count + ' Item' + (d.cart_count>1?'s':'');
      document.getElementById('floatCartTotal').textContent = '₹' + parseFloat(d.total).toFixed(2);
      bar.classList.add('show');
    } else {
      bar.classList.remove('show');
    }
    if (typeof bbUpdateCartBadge === 'function') bbUpdateCartBadge(d.cart_count);
  });
}

window.addEventListener('DOMContentLoaded', function() { bbRefreshFloatCart(); });

/* ── Banner Auto-Slide (if present) ── */
var track = document.getElementById('bannerTrack');
if (track) {
  var currentSlide = 0;
  var totalSlides = 4;
  var dots = document.querySelectorAll('.bb-banner-dot');
  function goToSlide(idx) {
    currentSlide = (idx + totalSlides) % totalSlides;
    track.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
    dots.forEach(function(d, i) {
      d.classList.toggle('active', i === currentSlide);
    });
  }
  dots.forEach(function(d) {
    d.addEventListener('click', function() { goToSlide(parseInt(d.dataset.idx)); });
  });
  setInterval(function() { goToSlide(currentSlide + 1); }, 3500);
}

/* ── Live AJAX Search ── */
var _st = null;
var _searching = false;
var searchInput = document.getElementById('productSearch');
var productGrid = document.getElementById('productGrid');
var visibleCount = document.getElementById('visibleCount');

function renderProductCard(p) {
    var img = p.image ? '<img src="'+BASE_URL+'assets/uploads/products/'+p.image+'" class="bb-pcard-img" loading="lazy">'
                      : '<div class="bb-pcard-no-img">🛒</div>';
    var priceHtml = p.has_disc
        ? '<div class="bb-price-block"><span class="bb-price-sp">₹'+p.sp.toFixed(2)+'</span><span class="bb-price-mrp">₹'+p.mrp.toFixed(2)+'</span></div>'
        : '<div class="bb-price-block"><span class="bb-price-only">₹'+p.mrp.toFixed(2)+'</span></div>';
    
    var deliveryBadge = '<div style="position:absolute; bottom:6px; left:6px; background:rgba(255,255,255,0.95); font-size:0.55rem; font-weight:800; padding:2px 6px; border-radius:4px; color:#1a202c; box-shadow:0 2px 4px rgba(0,0,0,0.1); display:flex; align-items:center; gap:3px; z-index:2;"><span style="color:#e53e3e;">⚡</span> 12 MINS</div>';
    var oos = p.stock <= 0 ? '<span class="bb-pcard-oos-badge" style="z-index:2;">Sold Out</span>' : '';
    
    var btn = '';
    if (p.stock > 0) {
        if (p.in_cart > 0) {
            btn = '<div class="bb-spinner"><button class="bb-spin-btn" onclick="bbChangeQty('+p.id+',-1)">−</button><span class="bb-spin-val bb-qty-display">'+p.in_cart+'</span><button class="bb-spin-btn" onclick="bbChangeQty('+p.id+',1)">+</button></div>';
        } else {
            btn = '<button class="bb-add-btn" onclick="bbAddToCart('+p.id+')">＋ Add</button>';
        }
    } else {
        btn = '<span class="bb-add-btn-oos">Sold Out</span>';
    }

    return '<div class="bb-pcard product-card-wrapper bb-cart-action" data-product-id="'+p.id+'" data-name="'+p.name+'" data-code="'+(p.code||'')+'">'
        +'<div class="bb-pcard-img-wrap">'
        + deliveryBadge + oos
        +'<a href="'+BASE_URL+'portal/product_detail/'+p.id+'">'+img+'</a>'
        +'</div>'
        +'<div class="bb-pcard-body">'
        +'<h3 class="bb-pcard-name"><a href="'+BASE_URL+'portal/product_detail/'+p.id+'">'+p.name+'</a></h3>'
        +'<div class="bb-pcard-unit" style="margin-bottom:8px;">'+p.unit+'</div>'
        +'<div class="bb-pcard-price-row">'
        + priceHtml
        +'<div>'+btn+'</div>'
        +'</div>'
        +'</div>'
        +'</div>';
}

function doSearch(q) {
    if (_searching) return;
    _searching = true;
    var url = BASE_URL + 'portal/search_ajax?q=' + encodeURIComponent(q);
    <?php if ($selected_category): ?>url += '&category=<?= urlencode($selected_category) ?>';<?php endif; ?>
    if (visibleCount) visibleCount.textContent = 'Searching…';
    fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){return r.json();})
    .then(function(d){
        _searching = false;
        if (!productGrid) return;
        if (d.products && d.products.length) {
            productGrid.innerHTML = d.products.map(renderProductCard).join('');
            if (visibleCount) visibleCount.textContent = 'Found ' + d.count + ' product(s)';
        } else {
            productGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#999;">No products found for "'+q+'"</div>';
            if (visibleCount) visibleCount.textContent = '0 products found';
        }
    })
    .catch(function(){_searching=false;});
}

if (searchInput) {
    searchInput.addEventListener('input', function() {
        var q = this.value.trim();
        clearTimeout(_st);
        if (q.length === 0) {
            // Reload page to show default 20
            window.location.href = BASE_URL + 'portal/products<?php echo $selected_category ? '?category='.urlencode($selected_category) : ''; ?>';
            return;
        }
        if (q.length < 2) return;
        _st = setTimeout(function(){ doSearch(q); }, 350);
    });
    searchInput.closest('form').addEventListener('submit', function(e) {
        e.preventDefault();
        var q = searchInput.value.trim();
        if (q.length >= 2) doSearch(q);
    });
}
</script>
