<?php // FILE: application/views/portal/dashboard.php — Sandal House Amazon-Style Home ?>
<style>
/* ── Banner Slider ────────────────────────────────────────── */
.bb-banner-wrap {
  position: relative;
  overflow: hidden;
  border-radius: 16px;
  margin-bottom: 18px;
  box-shadow: 0 4px 20px rgba(59,35,20,0.12);
}
.bb-banner-track {
  display: flex;
  transition: transform 0.5s cubic-bezier(0.4,0,0.2,1);
  width: 100%;
}
.bb-banner-slide {
  width: 100%;
  flex-shrink: 0;
  height: 160px;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  padding: 20px 22px;
  position: relative;
  overflow: hidden;
  box-sizing: border-box;
}
.bb-banner-slide.s1 { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); }
.bb-banner-slide.s2 { background: linear-gradient(135deg, var(--gold-dark) 0%, var(--gold) 100%); }
.bb-banner-slide.s3 { background: linear-gradient(135deg, #2e7d32 0%, #43a047 100%); }
.bb-banner-slide.s4 { background: linear-gradient(135deg, #880e4f 0%, #e91e63 100%); }
.bb-banner-emoji {
  font-size: 4rem;
  position: absolute;
  right: 20px;
  bottom: 10px;
  opacity: 0.25;
  animation: floatAnim 3s ease-in-out infinite;
}
@keyframes floatAnim { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
.bb-banner-tag {
  display: inline-flex; align-items: center;
  background: rgba(255,255,255,0.2);
  border: 1px solid rgba(255,255,255,0.4);
  border-radius: 20px; padding: 3px 10px;
  font-size: 0.65rem; font-weight: 700; color: #fff;
  letter-spacing: 0.5px; text-transform: uppercase;
  margin-bottom: 6px;
}
.bb-banner-title { color: #fff; font-size: 1.2rem; font-weight: 800; margin: 0 0 4px; line-height: 1.2; }
.bb-banner-sub   { color: rgba(255,255,255,0.82); font-size: 0.75rem; margin: 0 0 10px; }
.bb-banner-cta {
  display: inline-flex; align-items: center; gap: 5px;
  background: rgba(255,255,255,0.95); color: var(--primary);
  border-radius: 20px; padding: 6px 14px;
  font-size: 0.75rem; font-weight: 700;
  text-decoration: none; transition: all 0.2s;
}
.bb-banner-cta:hover { background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.15); text-decoration:none; color:var(--primary); }
.bb-banner-dots {
  display: flex; justify-content: center; gap: 5px;
  padding: 8px 0 0;
}
.bb-banner-dot {
  width: 6px; height: 6px; border-radius: 3px;
  background: var(--gold-light); transition: all 0.3s; cursor: pointer;
}
.bb-banner-dot.active { background: var(--primary); width: 18px; }

/* ── Stats Strip ──────────────────────────────────────────── */
.bb-stats-strip {
  display: flex; gap: 10px; margin-bottom: 18px;
}
.bb-stat-pill {
  flex: 1; background: #fff; border-radius: 12px;
  padding: 12px 10px; text-align: center;
  box-shadow: 0 2px 10px rgba(59,35,20,0.06);
  transition: all 0.2s; text-decoration: none;
}
.bb-stat-pill:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(59,35,20,0.1); text-decoration:none; color:var(--primary); }
.bb-stat-val { font-size: 1.25rem; font-weight: 800; display: block; line-height: 1; margin-bottom: 3px; }
.bb-stat-lbl { font-size: 0.65rem; font-weight: 600; color: var(--text-muted); display: block; }

/* ── Section Heading ──────────────────────────────────────── */
.bb-section-head {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 12px;
}
.bb-section-head h3 {
  font-size: 0.95rem; font-weight: 700; color: var(--primary);
  margin: 0; display: flex; align-items: center; gap: 6px;
}
.bb-section-head h3::before {
  content: ''; display: inline-block;
  width: 3px; height: 16px;
  background: linear-gradient(var(--gold), var(--primary)); border-radius: 2px;
}
.bb-section-head a { font-size: 0.75rem; font-weight: 600; color: var(--primary); text-decoration: none; }
.bb-section-head a:hover { color: var(--gold-dark); }

/* ── Category Scroll ──────────────────────────────────────── */
.bb-cat-scroll {
  display: flex; gap: 12px; overflow-x: auto;
  padding: 2px 2px 12px; scrollbar-width: none;
  -ms-overflow-style: none;
}
.bb-cat-scroll::-webkit-scrollbar { display: none; }
.bb-cat-item {
  display: flex; flex-direction: column; align-items: center;
  gap: 6px; text-decoration: none; flex-shrink: 0; width: 72px;
}
.bb-cat-img {
  width: 60px; height: 60px; border-radius: 50%;
  background: linear-gradient(135deg, var(--gold-light), #fff);
  border: 2px solid var(--gold-light);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(59,35,20,0.06);
}
.bb-cat-item:hover .bb-cat-img {
  border-color: var(--gold-dark);
  box-shadow: 0 4px 14px rgba(234,160,30,0.25);
  transform: scale(1.05);
}
.bb-cat-name {
  font-size: 0.65rem; font-weight: 700; color: var(--text-mid);
  text-align: center; width: 100%;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* ── Product Horizontal Scroll ────────────────────────────── */
.bb-prod-scroll {
  display: flex; gap: 12px; overflow-x: auto;
  padding: 2px 2px 12px; scrollbar-width: none;
}
.bb-prod-scroll::-webkit-scrollbar { display: none; }
.bb-prod-hcard {
  flex-shrink: 0; width: 150px;
  background: #fff; border-radius: 14px;
  box-shadow: 0 2px 10px rgba(59,35,20,0.06);
  overflow: hidden; transition: all 0.25s;
  border: 1.5px solid #f0f0f0;
}
.bb-prod-hcard:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(59,35,20,0.1);
  border-color: var(--gold-light);
}
.bb-prod-himg {
  height: 110px; width: 100%; object-fit: cover; display: block;
}
.bb-prod-hno-img {
  height: 110px; background: #f5f5f5;
  display: flex; align-items: center; justify-content: center;
  color: var(--text-muted); font-size: 2rem;
}
.bb-prod-hbody { padding: 10px; }
.bb-prod-hname {
  font-size: 0.75rem; font-weight: 600; color: var(--primary);
  margin: 0 0 4px; line-height: 1.3;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.bb-prod-hprice { font-size: 0.88rem; font-weight: 800; color: var(--gold-dark); }
.bb-prod-hunit  { font-size: 0.62rem; color: var(--text-muted); }
.bb-prod-hadd {
  margin-top: 8px; width: 100%; border: none;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: #fff; border-radius: 8px; padding: 6px 0;
  font-size: 0.72rem; font-weight: 700;
  font-family: 'Poppins', sans-serif; cursor: pointer;
  transition: all 0.2s;
}
.bb-prod-hadd:hover { background: linear-gradient(135deg, var(--primary-dark), var(--primary)); }
.bb-prod-hadd:disabled { background: var(--text-muted); opacity: 0.6; cursor: not-allowed; }

/* ── Quick Actions ────────────────────────────────────────── */
.bb-quick-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  margin-bottom: 18px;
}
.bb-quick-card {
  background: #fff;
  border-radius: 14px;
  padding: 16px 14px;
  text-decoration: none;
  box-shadow: 0 2px 10px rgba(59,35,20,0.06);
  transition: all 0.22s;
  display: flex; align-items: center; gap: 12px;
  border: 1.5px solid #f0f0f0;
}
.bb-quick-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(59,35,20,0.1);
  text-decoration: none;
  border-color: var(--gold-light);
}
.bb-quick-icon {
  width: 42px; height: 42px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; flex-shrink: 0;
}
.bb-quick-title { font-size: 0.78rem; font-weight: 700; color: var(--primary); display: block; }
.bb-quick-sub   { font-size: 0.65rem; color: var(--text-muted); display: block; }

/* ── Order Status Strip ───────────────────────────────────── */
.bb-order-strip {
  background: linear-gradient(135deg, var(--gold-light), #fff);
  border: 1.5px solid var(--gold);
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 14px;
  display: flex; align-items: center; justify-content: space-between;
}

/* ── Page wrapper ─────────────────────────────────────────── */
.bb-dash { padding: 12px; max-width: 600px; margin: 0 auto; }
</style>

<div class="bb-dash">

  <!-- Greeting -->
  <div style="margin-bottom:14px;">
    <p style="font-size:0.78rem; color:#78909c; margin:0;">
      <?php
      $h = (int)date('H');
      $greet = $h < 12 ? '🌅 Good Morning' : ($h < 17 ? '☀️ Good Afternoon' : '🌙 Good Evening');
      echo $greet;
      ?>
    </p>
    <h2 style="font-size:1.18rem; font-weight:800; color:var(--primary); margin:2px 0 0;">
      <?= htmlspecialchars($customer->name) ?> 👋
    </h2>
  </div>

  <!-- Banner Slider -->
  <div class="bb-banner-wrap">
    <div class="bb-banner-track" id="bannerTrack">
      <?php
      $custom_banners = [];
      for($i = 1; $i <= 4; $i++) {
          if (!empty($company['portal_banner_'.$i])) {
              $custom_banners[] = base_url($company['portal_banner_'.$i]);
          }
      }
      
      if (!empty($custom_banners)): 
          foreach($custom_banners as $idx => $b_img): ?>
            <div class="bb-banner-slide" style="padding:0; background:none;">
                <img src="<?= $b_img ?>" style="width:100%; height:auto; display:block; border-radius:16px;">
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
              <a href="<?= base_url('portal/products') ?>" class="bb-banner-cta">Shop Now →</a>
            </div>
          </div>
          <div class="bb-banner-slide s2">
            <div class="bb-banner-emoji">⚡</div>
            <div>
              <span class="bb-banner-tag">⚡ Fast Delivery</span>
              <p class="bb-banner-title">Order Now,<br>Get it Today!</p>
              <p class="bb-banner-sub">Place order & we'll deliver fast</p>
              <a href="<?= base_url('portal/order') ?>" class="bb-banner-cta">Order Now →</a>
            </div>
          </div>
          <div class="bb-banner-slide s3">
            <div class="bb-banner-emoji">🌿</div>
            <div>
              <span class="bb-banner-tag">🌿 Fresh Stock</span>
              <p class="bb-banner-title">Fresh Products<br>Every Day!</p>
              <p class="bb-banner-sub">Quality guaranteed on every order</p>
              <a href="<?= base_url('portal/products') ?>" class="bb-banner-cta">Browse →</a>
            </div>
          </div>
          <div class="bb-banner-slide s4">
            <div class="bb-banner-emoji">💰</div>
            <div>
              <span class="bb-banner-tag">💰 Best Prices</span>
              <p class="bb-banner-title"><?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?><br>Promise!</p>
              <p class="bb-banner-sub">MRP prices on every single product</p>
              <a href="<?= base_url('portal/products') ?>" class="bb-banner-cta">Explore →</a>
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

  <!-- Stats Strip -->
  <div class="bb-stats-strip">
    <a href="<?= base_url('portal/invoices') ?>" class="bb-stat-pill" style="border-top: 3px solid var(--primary);">
      <span class="bb-stat-val" style="color:var(--primary);"><?= count($recent_invoices) ?></span>
      <span class="bb-stat-lbl">Invoices</span>
    </a>
    <a href="<?= base_url('portal/my_orders') ?>" class="bb-stat-pill" style="border-top: 3px solid var(--gold);">
      <span class="bb-stat-val" style="color:var(--gold-dark);"><?= $pending_orders ?></span>
      <span class="bb-stat-lbl">Pending Orders</span>
    </a>
    <a href="<?= base_url('portal/invoices') ?>" class="bb-stat-pill" style="border-top: 3px solid var(--danger);">
      <span class="bb-stat-val" style="color:var(--danger);">₹<?= number_format($total_due, 0) ?></span>
      <span class="bb-stat-lbl">Due Amount</span>
    </a>
  </div>

  <?php if ($pending_orders > 0): ?>
  <!-- Pending Order Strip -->
  <div class="bb-order-strip">
    <div style="display:flex; align-items:center; gap:10px;">
      <span style="font-size:1.5rem;">📦</span>
      <div>
        <div style="font-size:0.8rem; font-weight:700; color:var(--gold-dark);"><?= $pending_orders ?> order<?= $pending_orders>1?'s':'' ?> in progress</div>
        <div style="font-size:0.7rem; color:var(--gold-dark); opacity: 0.85;">We're processing your order(s)</div>
      </div>
    </div>
    <a href="<?= base_url('portal/my_orders') ?>" style="font-size:0.72rem; font-weight:700; color:var(--primary); text-decoration:none;">Track →</a>
  </div>
  <?php endif; ?>

  <!-- Quick Actions -->
  <div class="bb-section-head">
    <h3>Quick Actions</h3>
  </div>
  <div class="bb-quick-grid">
    <a href="<?= base_url('portal/products') ?>" class="bb-quick-card">
      <div class="bb-quick-icon" style="background:var(--gold-light);">🛍️</div>
      <div>
        <span class="bb-quick-title">Shop Now</span>
        <span class="bb-quick-sub">Browse all products</span>
      </div>
    </a>
    <a href="<?= base_url('portal/order') ?>" class="bb-quick-card">
      <div class="bb-quick-icon" style="background:var(--gold-light);">🛒</div>
      <div>
        <span class="bb-quick-title">Place Order</span>
        <span class="bb-quick-sub">Add items & checkout</span>
      </div>
    </a>
    <a href="<?= base_url('portal/my_orders') ?>" class="bb-quick-card">
      <div class="bb-quick-icon" style="background:var(--gold-light);">📋</div>
      <div>
        <span class="bb-quick-title">My Orders</span>
        <span class="bb-quick-sub">Track your orders</span>
      </div>
    </a>
    <a href="<?= base_url('portal/invoices') ?>" class="bb-quick-card">
      <div class="bb-quick-icon" style="background:var(--gold-light);">📄</div>
      <div>
        <span class="bb-quick-title">Invoices</span>
        <span class="bb-quick-sub">View bills & dues</span>
      </div>
    </a>
  </div>

  <!-- Buy Again (past products) -->
  <?php if (!empty($past_products ?? [])): ?>
  <div class="bb-section-head">
    <h3>🔄 Buy Again</h3>
    <a href="<?= base_url('portal/products') ?>">See All →</a>
  </div>
  <div class="bb-prod-scroll">
    <?php foreach (($past_products ?? []) as $pp): ?>
    <div class="bb-prod-hcard">
      <?php if (!empty($pp->image1)): ?>
        <img src="<?= base_url('assets/uploads/products/'.$pp->image1) ?>" class="bb-prod-himg" alt="<?= htmlspecialchars($pp->name) ?>">
      <?php elseif (!empty($company['company_logo'])): ?>
        <div class="bb-prod-hno-img"><img src="<?= base_url($company['company_logo']) ?>" style="max-width:50%;max-height:50%;opacity:0.55;object-fit:contain;"></div>
      <?php else: ?>
        <div class="bb-prod-hno-img">🛒</div>
      <?php endif; ?>
      <div class="bb-prod-hbody">
        <p class="bb-prod-hname"><?= htmlspecialchars($pp->name) ?></p>
        <div>
          <span class="bb-prod-hprice">₹<?= number_format($pp->mrp, 2) ?></span>
          <span class="bb-prod-hunit"> / <?= htmlspecialchars($pp->unit ?? 'unit') ?></span>
        </div>
        <?php if ($pp->stock_qty > 0): ?>
        <button class="bb-prod-hadd" onclick="bbDashAddToCart(<?= $pp->id ?>)">＋ Add to Cart</button>
        <?php else: ?>
        <button class="bb-prod-hadd" disabled>Out of Stock</button>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Recent Invoices -->
  <?php if (!empty($recent_invoices)): ?>
  <div class="bb-section-head" style="margin-top:6px;">
    <h3>🧾 Recent Invoices</h3>
    <a href="<?= base_url('portal/invoices') ?>">View All →</a>
  </div>
  <div class="bb-card" style="background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(59,35,20,0.06); overflow:hidden; margin-bottom:14px;">
    <?php foreach ($recent_invoices as $inv): ?>
    <a href="<?= base_url('portal/invoice/'.$inv->id) ?>" style="display:flex; align-items:center; padding:12px 14px; border-bottom:1px solid #f5f5f5; text-decoration:none; gap:12px;">
      <div style="width:38px; height:38px; border-radius:10px; background:var(--gold-light); display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0;">📄</div>
      <div style="flex:1; min-width:0;">
        <div style="font-size:0.8rem; font-weight:700; color:var(--primary);"><?= $inv->invoice_no ?></div>
        <div style="font-size:0.68rem; color:var(--text-muted);"><?= date('d M Y', strtotime($inv->invoice_date)) ?></div>
      </div>
      <div style="text-align:right; flex-shrink:0;">
        <div style="font-size:0.82rem; font-weight:700; color:var(--text-dark);">₹<?= number_format($inv->total_amount, 0) ?></div>
        <span style="font-size:0.65rem; font-weight:700; padding:2px 8px; border-radius:12px;
          <?= $inv->status==='paid' ? 'background:#e8f5e9;color:#2e7d32' : ($inv->status==='partial' ? 'background:#fff8e1;color:#f57f17' : 'background:#fce4ec;color:#c62828') ?>">
          <?= ucfirst($inv->status) ?>
        </span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Footer Promo -->
  <div style="text-align:center; padding: 12px 0 4px; color:var(--text-muted); font-size:0.7rem;">
    🛡️ Secured Shopping • Quality Guaranteed<br>
    <strong style="color:var(--primary);"><?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?></strong> &copy; <?= date('Y') ?>
  </div>

</div>

<script>
/* Banner Auto-Slide */
var currentSlide = 0;
var totalSlides = 4;
var track = document.getElementById('bannerTrack');
var dots  = document.querySelectorAll('.bb-banner-dot');

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

var slideTimer = setInterval(function() { goToSlide(currentSlide + 1); }, 3500);

/* Add to Cart from Buy Again section */
function bbDashAddToCart(pid) {
  var fd = new FormData();
  fd.append('product_id', pid);
  fetch('<?= base_url("portal/add_to_cart_ajax") ?>', {
    method: 'POST', body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(d => {
    if (d.status === 'success') {
      if (typeof bbUpdateCartBadge === 'function') bbUpdateCartBadge(d.cart_count);
      // Flash feedback
      var ev = event;
      var btn = ev && ev.target ? ev.target : null;
      if (btn) { btn.textContent = '✓ Added!'; btn.style.background='linear-gradient(135deg,#2e7d32,#43a047)'; setTimeout(function(){ btn.textContent='＋ Add to Cart'; btn.style.background=''; }, 1500); }
    }
  });
}
</script>
