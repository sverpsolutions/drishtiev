<?php
$sp = (float)($product->selling_price > 0 ? $product->selling_price : $product->mrp);
$mrp = (float)$product->mrp;
$has_disc = $sp > 0 && $sp < $mrp;
$qty = $cart[$product->id]['qty'] ?? 0;
$is_veg = (int)($product->is_veg ?? 1);
?>
<div class="st-page">

  <div class="st-pdp-gallery">
    <?php if (!empty($product->image1)): ?>
      <img src="<?= base_url('assets/uploads/products/' . $product->image1) ?>" alt="<?= htmlspecialchars($product->name) ?>">
    <?php else: ?>
      <div class="st-pcard-placeholder" style="font-size:4rem;">🛒</div>
    <?php endif; ?>
  </div>

  <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
    <div class="st-pcard-veg <?= $is_veg ? '' : 'nonveg' ?>" style="position:static;"></div>
    <span style="font-size:.72rem;color:#999;"><?= htmlspecialchars($product->item_code ?? '') ?></span>
  </div>
  <h1 style="font-family:'Poppins',sans-serif;font-size:1.15rem;font-weight:800;margin:0 0 4px;"><?= htmlspecialchars($product->name) ?></h1>
  <div style="font-size:.82rem;color:#999;margin-bottom:10px;"><?= htmlspecialchars($product->unit ?? '') ?><?= !empty($product->brand) ? ' · ' . htmlspecialchars($product->brand) : '' ?></div>

  <?php if ($total_reviews > 0): ?>
  <div style="display:flex;align-items:center;gap:6px;margin-bottom:14px;">
    <div class="st-star-row"><?php for ($i = 1; $i <= 5; $i++): ?><i class="fa fa-star" style="<?= $i <= round($avg_rating) ? '' : 'color:#e0e0e0;' ?>"></i><?php endfor; ?></div>
    <span style="font-size:.78rem;color:#666;font-weight:600;"><?= $avg_rating ?> (<?= $total_reviews ?> review<?= $total_reviews == 1 ? '' : 's' ?>)</span>
  </div>
  <?php endif; ?>

  <div class="st-card">
    <div class="st-card-body">
      <div class="st-pcard-price-row" style="margin-bottom:2px;">
        <span style="font-size:1.4rem;font-weight:800;"><?= number_format($sp, 2) ?></span>
        <?php if ($has_disc): ?><span class="st-pcard-mrp" style="font-size:.9rem;">₹<?= number_format($mrp, 2) ?></span><?php endif; ?>
      </div>
      <?php if ($has_disc): ?>
      <div style="color:var(--st-primary);font-size:.8rem;font-weight:700;">You save ₹<?= number_format($mrp - $sp, 2) ?> (<?= round((($mrp - $sp) / $mrp) * 100) ?>%)</div>
      <?php endif; ?>
      <div style="margin-top:4px;font-size:.78rem;color:<?= $product->stock_qty > 0 ? '#0DB14B' : '#c62828' ?>;font-weight:700;">
        <?= $product->stock_qty > 0 ? '✓ In stock' : '✗ Out of stock' ?>
      </div>
    </div>
  </div>

  <?php if (!empty($product->description)): ?>
  <div class="st-card"><div class="st-card-header">Description</div><div class="st-card-body" style="font-size:.85rem;color:#555;line-height:1.6;"><?= nl2br(htmlspecialchars($product->description)) ?></div></div>
  <?php endif; ?>

  <?php if (!empty($product->ingredients)): ?>
  <div class="st-card"><div class="st-card-header">Ingredients</div><div class="st-card-body" style="font-size:.82rem;color:#555;line-height:1.6;"><?= nl2br(htmlspecialchars($product->ingredients)) ?></div></div>
  <?php endif; ?>

  <?php if (!empty($related)): ?>
  <div class="st-section">
    <div class="st-section-head"><div class="st-section-title">You may also like</div></div>
    <div class="st-grid">
      <?php foreach ($related as $rp): $qty = $cart[$rp->id]['qty'] ?? 0; $p = $rp; ?>
        <?php include APPPATH . 'views/store/_product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Reviews ── -->
  <div class="st-card">
    <div class="st-card-header">Ratings &amp; Reviews</div>
    <div class="st-card-body">
      <?php if ($total_reviews > 0): ?>
      <div style="display:flex;gap:16px;margin-bottom:16px;">
        <div style="text-align:center;flex-shrink:0;">
          <div style="font-size:1.8rem;font-weight:800;"><?= $avg_rating ?></div>
          <div class="st-star-row" style="justify-content:center;"><?php for ($i = 1; $i <= 5; $i++): ?><i class="fa fa-star" style="<?= $i <= round($avg_rating) ? '' : 'color:#e0e0e0;' ?>"></i><?php endfor; ?></div>
          <div style="font-size:.68rem;color:#999;margin-top:2px;"><?= $total_reviews ?> ratings</div>
        </div>
        <div style="flex:1;">
          <?php for ($s = 5; $s >= 1; $s--): $pct = $total_reviews > 0 ? ($rating_counts[$s] / $total_reviews) * 100 : 0; ?>
          <div style="display:flex;align-items:center;gap:6px;font-size:.7rem;color:#666;margin-bottom:3px;">
            <span style="width:10px;"><?= $s ?></span>
            <div class="st-review-bar-track"><div class="st-review-bar-fill" style="width:<?= $pct ?>%;"></div></div>
          </div>
          <?php endfor; ?>
        </div>
      </div>
      <?php foreach ($reviews as $rev): ?>
      <div style="border-top:1px solid #f2f2f2;padding:12px 0;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <strong style="font-size:.82rem;"><?= htmlspecialchars($rev->customer_name) ?></strong>
          <?php if ($rev->is_verified_purchase): ?><span class="st-badge st-badge-converted" style="font-size:.6rem;">✓ Verified</span><?php endif; ?>
        </div>
        <div class="st-star-row" style="margin:3px 0;"><?php for ($i = 1; $i <= 5; $i++): ?><i class="fa fa-star" style="<?= $i <= $rev->rating ? '' : 'color:#e0e0e0;' ?>font-size:.72rem;"></i><?php endfor; ?></div>
        <?php if (!empty($rev->review_title)): ?><div style="font-weight:700;font-size:.8rem;"><?= htmlspecialchars($rev->review_title) ?></div><?php endif; ?>
        <?php if (!empty($rev->review_text)): ?><div style="font-size:.8rem;color:#666;margin-top:2px;"><?= nl2br(htmlspecialchars($rev->review_text)) ?></div><?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <div style="text-align:center;color:#999;font-size:.82rem;padding:10px 0;">No reviews yet. Be the first!</div>
      <?php endif; ?>

      <?php if ($this->session->userdata('store_customer_id')): ?>
      <form method="post" action="<?= base_url('store/submit_review') ?>" style="margin-top:14px;border-top:1px solid #f2f2f2;padding-top:14px;">
        <input type="hidden" name="product_id" value="<?= $product->id ?>">
        <div class="st-field">
          <label>Your Rating</label>
          <div class="st-star-row" id="stRatingPicker" style="font-size:1.4rem;cursor:pointer;">
            <?php for ($i = 1; $i <= 5; $i++): ?><i class="fa fa-star" data-val="<?= $i ?>" style="color:#e0e0e0;"></i><?php endfor; ?>
          </div>
          <input type="hidden" name="rating" id="stRatingInput" value="0">
        </div>
        <div class="st-field"><label>Title (optional)</label><input type="text" name="title" maxlength="255"></div>
        <div class="st-field"><label>Your Review</label><textarea name="review_text" rows="3"></textarea></div>
        <button type="submit" class="st-btn-primary st-btn-block">Submit Review</button>
      </form>
      <script>
      (function () {
        var stars = document.querySelectorAll('#stRatingPicker i');
        var input = document.getElementById('stRatingInput');
        stars.forEach(function (star) {
          star.addEventListener('click', function () {
            var val = parseInt(star.getAttribute('data-val'), 10);
            input.value = val;
            stars.forEach(function (s, idx) { s.style.color = idx < val ? 'var(--st-accent-dark)' : '#e0e0e0'; });
          });
        });
      })();
      </script>
      <?php else: ?>
      <div style="text-align:center;margin-top:12px;"><a href="<?= base_url('store/login') ?>" class="st-btn-outline">Login to write a review</a></div>
      <?php endif; ?>
    </div>
  </div>

</div>

<div class="st-sticky-checkout-bar">
  <div>
    <div style="font-size:1rem;font-weight:800;">₹<?= number_format($sp, 2) ?></div>
    <?php if ($has_disc): ?><div style="font-size:.7rem;color:#999;text-decoration:line-through;">₹<?= number_format($mrp, 2) ?></div><?php endif; ?>
  </div>
  <?php if ($product->stock_qty > 0): ?>
  <div class="st-pcard" data-pid="<?= $product->id ?>" data-qty="<?= (int)$qty ?>" style="all:unset;width:140px;">
    <div class="st-pcard-addbar"></div>
  </div>
  <?php else: ?>
  <button class="st-btn-ghost" disabled>Out of Stock</button>
  <?php endif; ?>
</div>
