<?php // FILE: application/views/portal/product_detail.php — Sandal House Theme ?>
<?php
$images = array_values(array_filter([$product->image1 ?? '', $product->image2 ?? '', $product->image3 ?? '']));
$in_stock = $product->stock_qty > 0;
?>
<style>
:root {
<?php include_once APPPATH.'views/portal/_theme.php'; echo portal_theme_vars($company ?? null); ?>
}
.bb-pd-wrap { max-width: 700px; margin: 0 auto; padding: 12px; }

/* Image Gallery */
.bb-pd-gallery {
  position: relative; border-radius: 16px; overflow: hidden;
  background: #f5f5f5; margin-bottom: 14px;
  box-shadow: 0 4px 20px rgba(59,35,20,0.1);
}
.bb-pd-gallery-track {
  display: flex; transition: transform 0.4s cubic-bezier(0.4,0,0.2,1);
}
.bb-pd-gallery-slide {
  min-width: 100%;
}
.bb-pd-gallery-img {
  width: 100%; height: 280px; object-fit: cover; display: block;
}
.bb-pd-gallery-noimg {
  height: 220px; display: flex; align-items: center;
  justify-content: center; color: #b0bec5; font-size: 4rem;
}
.bb-pd-gallery-btn {
  position: absolute; top: 50%; transform: translateY(-50%);
  width: 34px; height: 34px; border-radius: 50%;
  background: rgba(255,255,255,0.9); border: none;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 1rem; color: var(--primary);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 2;
  transition: all 0.2s;
}
.bb-pd-gallery-btn:hover { background: #fff; box-shadow: 0 4px 16px rgba(0,0,0,0.2); }
.bb-pd-gallery-btn.prev { left: 10px; }
.bb-pd-gallery-btn.next { right: 10px; }
.bb-pd-gallery-dots {
  position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);
  display: flex; gap: 5px;
}
.bb-pd-gallery-dot {
  width: 6px; height: 6px; border-radius: 3px;
  background: rgba(255,255,255,0.5); border: none; cursor: pointer;
  transition: all 0.2s; padding: 0;
}
.bb-pd-gallery-dot.active { background: #fff; width: 16px; }
.bb-pd-oos-overlay {
  position: absolute; top: 12px; left: 12px;
  background: rgba(0,0,0,0.6); color: #fff; border-radius: 8px;
  font-size: 0.72rem; font-weight: 700; padding: 4px 12px;
}

/* Thumb strip */
.bb-pd-thumbs {
  display: flex; gap: 8px; overflow-x: auto;
  padding: 0 0 10px; scrollbar-width: none; margin-bottom: 14px;
}
.bb-pd-thumbs::-webkit-scrollbar { display: none; }
.bb-pd-thumb {
  width: 58px; height: 58px; border-radius: 10px;
  object-fit: cover; cursor: pointer; flex-shrink: 0;
  border: 2px solid #e0e0e0; transition: all 0.2s;
}
.bb-pd-thumb.active { border-color: var(--gold); box-shadow: 0 0 0 2px rgba(246,205,60,0.3); }

/* Info Card */
.bb-pd-info {
  background: #fff; border-radius: 14px;
  border: 1.5px solid #f0f0f0;
  box-shadow: 0 2px 8px rgba(59,35,20,0.06);
  padding: 16px; margin-bottom: 12px;
}
.bb-pd-brand { font-size: 0.68rem; font-weight: 700; color: #90a4ae; text-transform: uppercase; letter-spacing: 0.5px; }
.bb-pd-name  { font-size: 1.12rem; font-weight: 800; color: var(--primary); margin: 4px 0 8px; line-height: 1.3; }
.bb-pd-rating-row { display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
.bb-pd-stars { color: var(--gold-dark); font-size: 0.9rem; }
.bb-pd-rating-val { font-size: 0.78rem; font-weight: 700; color: #37474f; }
.bb-pd-rating-cnt { font-size: 0.7rem; color: #90a4ae; }
.bb-pd-price-row  { display: flex; align-items: flex-end; gap: 8px; margin-bottom: 12px; }
.bb-pd-price { font-size: 1.6rem; font-weight: 800; color: var(--gold-dark); line-height: 1; }
.bb-pd-unit  { font-size: 0.75rem; color: #78909c; }
.bb-pd-stock-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 3px 10px; border-radius: 20px;
  font-size: 0.68rem; font-weight: 700;
}
.bb-pd-stock-badge.in  { background: #e8f5e9; color: #2e7d32; }
.bb-pd-stock-badge.out { background: #fce4ec; color: #c62828; }

/* Add to cart / qty spinner */
.bb-pd-cart-row { display: flex; align-items: center; gap: 10px; margin-top: 14px; }
.bb-pd-spinner {
  display: flex; align-items: center;
  border: 2px solid var(--primary); border-radius: 10px; overflow: hidden; height: 44px;
}
.bb-pd-spin-btn {
  border: none; background: var(--gold-light); color: var(--primary);
  width: 42px; height: 100%; font-size: 1.2rem; font-weight: 700;
  cursor: pointer; font-family: 'Poppins',sans-serif; transition: background 0.15s;
  display: flex; align-items: center; justify-content: center;
}
.bb-pd-spin-btn:hover { background: var(--gold); }
.bb-pd-spin-val { width: 42px; text-align: center; font-size: 1rem; font-weight: 700; color: var(--primary); }
.bb-pd-add-btn {
  flex: 1; border: none; border-radius: 10px; height: 44px;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: #fff; font-size: 0.9rem; font-weight: 700;
  font-family: 'Poppins',sans-serif; cursor: pointer;
  transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px;
}
.bb-pd-add-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(59,35,20,0.25); }
.bb-pd-add-btn.added { background: linear-gradient(135deg,#2e7d32,#43a047); }
.bb-pd-buy-btn {
  flex: 1; border: none; border-radius: 10px; height: 44px;
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  color: var(--primary); font-size: 0.9rem; font-weight: 800;
  font-family: 'Poppins',sans-serif; cursor: pointer;
  text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px;
  transition: all 0.2s;
}
.bb-pd-buy-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(246,205,60,0.35); text-decoration:none; color:var(--primary); }

/* Specs table */
.bb-spec-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 9px 0; border-bottom: 1px dashed #f0f0f0;
  font-size: 0.8rem;
}
.bb-spec-row:last-child { border-bottom: none; }
.bb-spec-key { color: #78909c; font-weight: 500; }
.bb-spec-val { color: var(--primary); font-weight: 700; text-align: right; }

/* Section card */
.bb-pd-section {
  background: #fff; border-radius: 14px;
  border: 1.5px solid #f0f0f0;
  box-shadow: 0 2px 8px rgba(59,35,20,0.06);
  margin-bottom: 12px; overflow: hidden;
}
.bb-pd-section-head {
  padding: 12px 16px; border-bottom: 1px solid #f5f5f5;
  font-size: 0.88rem; font-weight: 700; color: var(--primary);
  display: flex; align-items: center; gap: 6px;
}
.bb-pd-section-head::before {
  content: ''; display: inline-block; width: 3px; height: 14px;
  background: linear-gradient(var(--gold),var(--primary)); border-radius: 2px;
}

/* Ratings block */
.bb-rating-big { font-size: 3rem; font-weight: 800; color: var(--primary); line-height: 1; }
.bb-rating-bar-row { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; font-size: 0.75rem; }
.bb-rating-bar-bg { flex: 1; height: 6px; background: #e0e0e0; border-radius: 3px; overflow: hidden; }
.bb-rating-bar-fill { height: 100%; background: linear-gradient(90deg,var(--gold),var(--gold-dark)); border-radius: 3px; }

/* Star selector */
.bb-star-selector { display: flex; gap: 4px; flex-direction: row-reverse; justify-content: flex-end; }
.bb-star-selector input { display: none; }
.bb-star-selector label {
  font-size: 1.8rem; color: #e0e0e0; cursor: pointer;
  transition: color 0.15s; line-height: 1;
}
.bb-star-selector input:checked ~ label,
.bb-star-selector label:hover,
.bb-star-selector label:hover ~ label { color: var(--gold); }

/* Review card */
.bb-review-card { padding: 14px 16px; border-bottom: 1px dashed #f0f0f0; }
.bb-review-card:last-child { border-bottom: none; }
.bb-reviewer-name { font-size: 0.82rem; font-weight: 700; color: var(--primary); }
.bb-review-date   { font-size: 0.68rem; color: #90a4ae; }
.bb-review-title  { font-size: 0.82rem; font-weight: 700; color: #37474f; margin: 4px 0 2px; }
.bb-review-text   { font-size: 0.78rem; color: #546e7a; line-height: 1.5; }
.bb-verified-badge { background:#e8f5e9; color:#2e7d32; font-size:0.6rem; font-weight:700; padding:2px 8px; border-radius:12px; }

/* Review form */
.bb-input {
  width:100%; border:1.5px solid #e0e0e0; border-radius:10px;
  padding:10px 14px; font-family:'Poppins',sans-serif; font-size:0.82rem;
  color:var(--primary); background:#fafafa; outline:none; transition:all 0.2s;
}
.bb-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(59,35,20,0.07); }
.bb-label { display:block; font-size:0.76rem; font-weight:700; color:#37474f; margin-bottom:5px; }
</style>

<div class="bb-pd-wrap">

  <!-- Back -->
  <a href="<?= base_url('portal/products') ?>" style="display:inline-flex;align-items:center;gap:4px;color:var(--primary);font-size:0.78rem;font-weight:600;text-decoration:none;background:var(--gold-light);border-radius:20px;padding:6px 14px;margin-bottom:14px;">
    ← Products
  </a>

  <!-- Image Gallery -->
  <div class="bb-pd-gallery">
    <?php if (!$in_stock): ?>
    <div class="bb-pd-oos-overlay">Sold Out</div>
    <?php endif; ?>

    <div class="bb-pd-gallery-track" id="galleryTrack">
      <?php if (!empty($images)): ?>
        <?php foreach ($images as $img): ?>
        <div class="bb-pd-gallery-slide">
          <img src="<?= base_url('assets/uploads/products/'.$img) ?>" class="bb-pd-gallery-img" alt="<?= htmlspecialchars($product->name) ?>" onclick="openZoom(this.src)" style="cursor:zoom-in;">
        </div>
        <?php endforeach; ?>
      <?php elseif (!empty($company['company_logo'])): ?>
        <div class="bb-pd-gallery-slide">
          <div class="bb-pd-gallery-noimg"><img src="<?= base_url($company['company_logo']) ?>" style="max-width:40%;max-height:40%;opacity:0.55;object-fit:contain;"></div>
        </div>
      <?php else: ?>
        <div class="bb-pd-gallery-slide">
          <div class="bb-pd-gallery-noimg">🛒</div>
        </div>
      <?php endif; ?>
    </div>

    <?php if (count($images) > 1): ?>
    <button class="bb-pd-gallery-btn prev" id="galleryPrev">‹</button>
    <button class="bb-pd-gallery-btn next" id="galleryNext">›</button>
    <div class="bb-pd-gallery-dots" id="galleryDots">
      <?php for ($gi = 0; $gi < count($images); $gi++): ?>
      <button class="bb-pd-gallery-dot <?= $gi===0?'active':'' ?>" data-idx="<?= $gi ?>"></button>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Thumbnail Strip -->
  <?php if (count($images) > 1): ?>
  <div class="bb-pd-thumbs" id="thumbStrip">
    <?php foreach ($images as $gi => $img): ?>
    <img src="<?= base_url('assets/uploads/products/'.$img) ?>"
         class="bb-pd-thumb <?= $gi===0?'active':'' ?>"
         data-idx="<?= $gi ?>"
         alt="View <?= $gi+1 ?>">
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Product Info -->
  <div class="bb-pd-info">
    <?php if (!empty($product->brand)): ?>
    <div class="bb-pd-brand"><?= htmlspecialchars($product->brand) ?></div>
    <?php endif; ?>
    <h1 class="bb-pd-name"><?= htmlspecialchars($product->name) ?></h1>

    <?php if ($total_reviews > 0): ?>
    <div class="bb-pd-rating-row">
      <div class="bb-pd-stars">
        <?php for($s=1;$s<=5;$s++) echo $s<=$avg_rating?'★':'☆'; ?>
      </div>
      <span class="bb-pd-rating-val"><?= number_format($avg_rating,1) ?></span>
      <span class="bb-pd-rating-cnt">(<?= $total_reviews ?> reviews)</span>
    </div>
    <?php endif; ?>

    <?php
        $pd_sp  = (float)($product->selling_price ?? 0);
        $pd_mrp = (float)($product->mrp ?? 0);
        $pd_has_disc = $pd_sp > 0 && $pd_mrp > 0 && $pd_sp < $pd_mrp;
    ?>
    <div class="bb-pd-price-row">
      <span class="bb-pd-price">₹<?= number_format($pd_has_disc ? $pd_sp : $pd_mrp, 2) ?></span>
      <span class="bb-pd-unit">/ <?= htmlspecialchars($product->unit ?? 'unit') ?></span>
      <?php if ($pd_has_disc): ?>
        <span style="text-decoration:line-through;color:#999;font-size:1rem;">₹<?= number_format($pd_mrp, 2) ?></span>
        <span style="background:#e8f5e9;color:#2e7d32;font-size:0.78rem;font-weight:700;padding:2px 8px;border-radius:20px;">
          Save ₹<?= number_format($pd_mrp - $pd_sp, 2) ?>
        </span>
      <?php endif; ?>
    </div>

    <div style="margin-bottom:4px;">
      <span class="bb-pd-stock-badge <?= $in_stock?'in':'out' ?>">
        <?= $in_stock ? ('✅ In Stock ('.((float)$product->stock_qty).')') : '❌ Out of Stock' ?>
      </span>
    </div>

    <?php if ($in_stock): ?>
    <!-- Add to Cart / Buy Now -->
    <div class="bb-pd-cart-row" id="pdCartRow">
      <div class="bb-pd-spinner" id="pdSpinner" style="display:none;">
        <button class="bb-pd-spin-btn" id="pdSpinMinus">−</button>
        <span class="bb-pd-spin-val" id="pdSpinVal">1</span>
        <button class="bb-pd-spin-btn" id="pdSpinPlus">+</button>
      </div>
      <button class="bb-pd-add-btn" id="pdAddBtn" onclick="pdAddToCart()">
        🛒 Add to Cart
      </button>
      <a href="<?= base_url('portal/order') ?>" class="bb-pd-buy-btn" id="pdBuyBtn">
        ⚡ Buy Now
      </a>
    </div>
    <?php else: ?>
    <div style="margin-top:14px;background:#fce4ec;color:#c62828;border-radius:10px;padding:12px 14px;font-size:0.82rem;font-weight:600;">
      ❌ This product is currently out of stock. Check back soon!
    </div>
    <?php endif; ?>
  </div>

  <!-- Product Description -->
  <?php if (!empty($product->description)): ?>
  <div class="bb-pd-section">
    <div class="bb-pd-section-head">📝 Description</div>
    <div style="padding:14px 16px; font-size:0.82rem; color:var(--text-mid); line-height:1.7; white-space:pre-line;">
      <?= htmlspecialchars($product->description) ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Specifications -->
  <div class="bb-pd-section">
    <div class="bb-pd-section-head">📋 Specifications</div>
    <div style="padding:4px 16px;">
      <?php if (!empty($product->item_code)): ?>
      <div class="bb-spec-row">
        <span class="bb-spec-key">Item Code</span>
        <span class="bb-spec-val"><?= htmlspecialchars($product->item_code) ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($product->brand)): ?>
      <div class="bb-spec-row">
        <span class="bb-spec-key">Brand</span>
        <span class="bb-spec-val"><?= htmlspecialchars($product->brand) ?></span>
      </div>
      <?php endif; ?>
      <?php if (!empty($product->category)): ?>
      <div class="bb-spec-row">
        <span class="bb-spec-key">Category</span>
        <span class="bb-spec-val"><?= htmlspecialchars($product->category) ?></span>
      </div>
      <?php endif; ?>
      <div class="bb-spec-row">
        <span class="bb-spec-key">Unit</span>
        <span class="bb-spec-val"><?= htmlspecialchars($product->unit ?? 'N/A') ?></span>
      </div>
      <?php if (!empty($product->gst_percent)): ?>
      <div class="bb-spec-row">
        <span class="bb-spec-key">GST Rate</span>
        <span class="bb-spec-val"><?= $product->gst_percent ?>%</span>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Ratings Overview -->
  <div class="bb-pd-section" id="write-review">
    <div class="bb-pd-section-head">⭐ Ratings & Reviews</div>
    <div style="padding:16px;">

      <!-- Rating Summary -->
      <div style="display:flex;gap:20px;align-items:center;margin-bottom:16px;">
        <div style="text-align:center;">
          <div class="bb-rating-big"><?= number_format($avg_rating,1) ?></div>
          <div style="color:var(--gold-dark);font-size:1rem;margin:4px 0;">
            <?php for($s=1;$s<=5;$s++) echo $s<=$avg_rating?'★':'☆'; ?>
          </div>
          <div style="font-size:0.68rem;color:var(--text-muted);"><?= $total_reviews ?> review<?= $total_reviews!=1?'s':'' ?></div>
        </div>
        <div style="flex:1;">
          <?php for($star=5;$star>=1;$star--):
            $cnt = $rating_counts[$star] ?? 0;
            $pct = $total_reviews > 0 ? round(($cnt/$total_reviews)*100) : 0;
          ?>
          <div class="bb-rating-bar-row">
            <span style="width:40px;color:var(--text-muted);flex-shrink:0;"><?= $star ?>★</span>
            <div class="bb-rating-bar-bg">
              <div class="bb-rating-bar-fill" style="width:<?= $pct ?>%;"></div>
            </div>
            <span style="width:24px;text-align:right;color:var(--text-muted);"><?= $cnt ?></span>
          </div>
          <?php endfor; ?>
        </div>
      </div>

      <!-- Write Review -->
      <?php if (!empty($portal_customer)): ?>
      <?php if ($has_purchased): ?>
      <div style="background:#e8f5e9;color:#2e7d32;border-left:3px solid #2e7d32;border-radius:8px;padding:10px 14px;font-size:0.78rem;font-weight:600;margin-bottom:14px;">
        🎉 You purchased this item — your review will show a <strong>Verified Purchase</strong> badge!
      </div>
      <?php endif; ?>

      <form method="post" action="<?= base_url('portal/submit_review') ?>" style="background:#f8f9fd;border-radius:12px;padding:14px;">
        <input type="hidden" name="product_id" value="<?= $product->id ?>">
        <div style="margin-bottom:12px;">
          <label class="bb-label">Your Rating</label>
          <div class="bb-star-selector" id="starSelector">
            <?php for($s=5;$s>=1;$s--): ?>
            <input type="radio" id="star<?= $s ?>" name="rating" value="<?= $s ?>" required>
            <label for="star<?= $s ?>" title="<?= $s ?> stars">★</label>
            <?php endfor; ?>
          </div>
        </div>
        <div style="margin-bottom:10px;">
          <label class="bb-label">Review Title</label>
          <input type="text" name="title" class="bb-input" placeholder="Summarize your experience…" required>
        </div>
        <div style="margin-bottom:12px;">
          <label class="bb-label">Your Review</label>
          <textarea name="review_text" rows="3" class="bb-input" style="resize:none;" placeholder="Share what you liked or disliked…" required></textarea>
        </div>
        <button type="submit" style="width:100%;border:none;border-radius:10px;padding:11px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;font-size:0.86rem;font-weight:700;font-family:'Poppins',sans-serif;cursor:pointer;">
          ✍️ Submit Review
        </button>
      </form>
      <?php else: ?>
      <div style="background:#f8f9fd;border-radius:12px;padding:18px;text-align:center;">
        <div style="font-size:1.5rem;margin-bottom:6px;">✍️</div>
        <div style="font-size:0.85rem;font-weight:700;color:var(--primary);margin-bottom:4px;">Want to write a review?</div>
        <p style="font-size:0.75rem;color:var(--text-muted);margin:0 0 12px;">Login to share your experience with this product.</p>
        <a href="<?= base_url('portal/login?redirect=' . urlencode('portal/product_detail/' . $product->id)) ?>" style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;border-radius:20px;padding:8px 20px;font-size:0.8rem;font-weight:700;text-decoration:none;">
          <i class="fa fa-sign-in"></i> Login to Review
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Reviews Feed -->
  <?php if (!empty($reviews)): ?>
  <div class="bb-pd-section">
    <div class="bb-pd-section-head">💬 Customer Reviews (<?= $total_reviews ?>)</div>
    <?php foreach ($reviews as $rev): ?>
    <div class="bb-review-card">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px;">
        <div>
          <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="bb-reviewer-name">👤 <?= htmlspecialchars($rev->customer_name) ?></span>
            <?php if ($rev->is_verified_purchase): ?>
            <span class="bb-verified-badge">✅ Verified Purchase</span>
            <?php endif; ?>
          </div>
          <div style="color:var(--gold-dark);font-size:0.82rem;margin:3px 0;">
            <?php for($s=1;$s<=5;$s++) echo $s<=$rev->rating?'★':'☆'; ?>
          </div>
        </div>
        <div class="bb-review-date"><?= date('d M Y', strtotime($rev->created_at)) ?></div>
      </div>
      <div class="bb-review-title"><?= htmlspecialchars($rev->review_title) ?></div>
      <div class="bb-review-text"><?= nl2br(htmlspecialchars($rev->review_text)) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div style="text-align:center;padding:24px;color:#90a4ae;font-size:0.82rem;">
    💬 No reviews yet — be the first to review this product!
  </div>
  <?php endif; ?>

  <div style="height:16px;"></div>

</div>

<script>
var BASE_URL = '<?= base_url() ?>';
var PRODUCT_ID = <?= $product->id ?>;
var curQty = 0;

/* ── Gallery ── */
var gallerySlides = <?= count($images) ?>;
var galleryIdx = 0;
var track = document.getElementById('galleryTrack');
var dots  = document.querySelectorAll('.bb-pd-gallery-dot');
var thumbs= document.querySelectorAll('.bb-pd-thumb');

function goGallery(idx) {
  galleryIdx = (idx + gallerySlides) % gallerySlides;
  if (track) track.style.transform = 'translateX(-' + (galleryIdx * 100) + '%)';
  dots.forEach(function(d,i) { d.classList.toggle('active', i === galleryIdx); });
  thumbs.forEach(function(t,i) { t.classList.toggle('active', i === galleryIdx); });
}

var prevBtn = document.getElementById('galleryPrev');
var nextBtn = document.getElementById('galleryNext');
if (prevBtn) prevBtn.addEventListener('click', function() { goGallery(galleryIdx - 1); });
if (nextBtn) nextBtn.addEventListener('click', function() { goGallery(galleryIdx + 1); });
dots.forEach(function(d) { d.addEventListener('click', function() { goGallery(parseInt(d.dataset.idx)); }); });
thumbs.forEach(function(t) { t.addEventListener('click', function() { goGallery(parseInt(t.dataset.idx)); }); });

/* ── Add to Cart ── */
function pdAddToCart() {
  var fd = new FormData();
  fd.append('product_id', PRODUCT_ID);
  fetch(BASE_URL + 'portal/add_to_cart_ajax', {
    method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}
  })
  .then(r=>r.json())
  .then(d=>{
    if (d.status === 'success') {
      curQty = d.qty;
      showSpinner(curQty);
      if (typeof bbUpdateCartBadge === 'function') bbUpdateCartBadge(d.cart_count);
      var btn = document.getElementById('pdAddBtn');
      if (btn) { btn.textContent = '✓ Added!'; btn.classList.add('added');
        setTimeout(function() { btn.textContent = '🛒 Add to Cart'; btn.classList.remove('added'); }, 1500); }
    }
  });
}

function showSpinner(qty) {
  var spinner = document.getElementById('pdSpinner');
  var addBtn  = document.getElementById('pdAddBtn');
  if (qty > 0) {
    document.getElementById('pdSpinVal').textContent = qty;
    if (spinner) spinner.style.display = 'flex';
    if (addBtn) addBtn.style.display = 'none';
  } else {
    if (spinner) spinner.style.display = 'none';
    if (addBtn) addBtn.style.display = 'flex';
  }
}

var spinMinus = document.getElementById('pdSpinMinus');
var spinPlus  = document.getElementById('pdSpinPlus');
if (spinMinus) spinMinus.addEventListener('click', function() { pdChangeQty(curQty - 1); });
if (spinPlus)  spinPlus.addEventListener('click',  function() { pdChangeQty(curQty + 1); });

function pdChangeQty(newQty) {
  var fd = new FormData();
  fd.append('product_id', PRODUCT_ID);
  fd.append('qty', newQty);
  fetch(BASE_URL + 'portal/update_cart_ajax', {
    method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}
  })
  .then(r=>r.json())
  .then(d=>{
    if (d.status === 'success') {
      curQty = d.qty;
      showSpinner(curQty);
      if (typeof bbUpdateCartBadge === 'function') bbUpdateCartBadge(d.cart_count);
    }
  });
}

/* Init — check if already in cart */
window.addEventListener('DOMContentLoaded', function() {
  fetch(BASE_URL + 'portal/get_cart_ajax', { headers:{'X-Requested-With':'XMLHttpRequest'} })
  .then(r=>r.json())
  .then(d=>{
    if (d.status === 'success') {
      d.items.forEach(function(item) {
        if (item.id == PRODUCT_ID) { curQty = item.qty; showSpinner(curQty); }
      });
      if (typeof bbUpdateCartBadge === 'function') bbUpdateCartBadge(d.cart_count);
    }
  });
});

/* ── Image Zoom Lightbox ── */
function openZoom(src) {
    var modal = document.getElementById('zoomModal');
    var zoomImg = document.getElementById('zoomImg');
    zoomImg.src = src;
    zoomImg.style.transform = 'scale(1)';
    zoomImg.style.transformOrigin = 'center center';
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Mouse wheel zoom
    var scale = 1;
    zoomImg.onwheel = function(e) {
        e.preventDefault();
        scale += e.deltaY < 0 ? 0.15 : -0.15;
        scale = Math.min(Math.max(scale, 1), 4);
        zoomImg.style.transform = 'scale(' + scale + ')';
    };

    // Drag to pan
    var dragging = false, startX, startY, tx = 0, ty = 0;
    zoomImg.onmousedown = function(e) { dragging = true; startX = e.clientX - tx; startY = e.clientY - ty; };
    zoomImg.onmousemove = function(e) {
        if (!dragging || scale <= 1) return;
        tx = e.clientX - startX; ty = e.clientY - startY;
        zoomImg.style.transform = 'scale('+scale+') translate('+tx/scale+'px,'+ty/scale+'px)';
    };
    zoomImg.onmouseup = zoomImg.onmouseleave = function() { dragging = false; };

    // Touch pinch zoom
    var lastDist = 0;
    modal.ontouchstart = function(e) { if (e.touches.length === 2) lastDist = Math.hypot(e.touches[0].clientX-e.touches[1].clientX, e.touches[0].clientY-e.touches[1].clientY); };
    modal.ontouchmove = function(e) {
        if (e.touches.length === 2) {
            e.preventDefault();
            var dist = Math.hypot(e.touches[0].clientX-e.touches[1].clientX, e.touches[0].clientY-e.touches[1].clientY);
            scale *= dist / lastDist; lastDist = dist;
            scale = Math.min(Math.max(scale, 1), 4);
            zoomImg.style.transform = 'scale('+scale+')';
        }
    };
}
function closeZoom() {
    document.getElementById('zoomModal').style.display = 'none';
    document.body.style.overflow = '';
}
</script>

<!-- Zoom Modal -->
<div id="zoomModal" onclick="if(event.target===this)closeZoom()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.92);z-index:9999;align-items:center;justify-content:center;flex-direction:column;">
    <button onclick="closeZoom()" style="position:absolute;top:16px;right:20px;background:rgba(255,255,255,.15);border:none;color:#fff;font-size:1.8rem;width:42px;height:42px;border-radius:50%;cursor:pointer;line-height:1;z-index:10000;">✕</button>
    <div style="overflow:hidden;display:flex;align-items:center;justify-content:center;width:100%;height:100%;">
        <img id="zoomImg" src="" style="max-width:95vw;max-height:90vh;object-fit:contain;cursor:grab;transition:transform 0.1s;user-select:none;">
    </div>
    <div style="color:rgba(255,255,255,.5);font-size:.75rem;margin-top:8px;">Scroll to zoom · Drag to pan · Pinch on mobile</div>
</div>
