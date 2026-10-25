<?php
// Shared product-card partial. Expects $p (product row: id, name, mrp,
// selling_price, unit, stock_qty, image1, is_veg) and $qty (current cart qty,
// default 0) in scope. Included directly (not $this->load->view) so the
// including view's variables stay in scope without extra array copying.
$sp = (float)($p->selling_price > 0 ? $p->selling_price : $p->mrp);
$mrp = (float)$p->mrp;
$has_disc = $sp > 0 && $sp < $mrp;
$save_pct = $has_disc ? round((($mrp - $sp) / $mrp) * 100) : 0;
$is_veg = (int)($p->is_veg ?? 1);
$in_stock = (float)$p->stock_qty > 0;
?>
<div class="st-pcard" data-pid="<?= (int)$p->id ?>" data-qty="<?= (int)$qty ?>">
  <?php if ($has_disc): ?><span class="st-pcard-discount"><?= $save_pct ?>% OFF</span><?php endif; ?>
  <div class="st-pcard-veg <?= $is_veg ? '' : 'nonveg' ?>"></div>
  <a href="<?= base_url('store/product/' . $p->id) ?>" class="st-pcard-img-wrap">
    <?php if (!empty($p->image1)): ?>
      <img src="<?= base_url('assets/uploads/products/' . $p->image1) ?>" alt="<?= htmlspecialchars($p->name) ?>">
    <?php else: ?>
      <div class="st-pcard-placeholder">🛒</div>
    <?php endif; ?>
    <?php if (!$in_stock): ?><div class="st-pcard-oos">Out of Stock</div><?php endif; ?>
  </a>
  <div class="st-pcard-body">
    <a href="<?= base_url('store/product/' . $p->id) ?>"><div class="st-pcard-name"><?= htmlspecialchars($p->name) ?></div></a>
    <div class="st-pcard-unit"><?= htmlspecialchars($p->unit ?? '') ?></div>
    <div class="st-pcard-price-row">
      <span class="st-pcard-price">₹<?= number_format($sp, 2) ?></span>
      <?php if ($has_disc): ?><span class="st-pcard-mrp">₹<?= number_format($mrp, 2) ?></span><?php endif; ?>
    </div>
    <div class="st-pcard-addbar">
      <?php if ($in_stock): ?>
        <?php if ($qty > 0): ?>
        <div class="st-pcard-stepper"><button type="button" data-d="-1">−</button><span><?= (int)$qty ?></span><button type="button" data-d="1">+</button></div>
        <?php else: ?>
        <button type="button" class="st-pcard-add-btn">ADD</button>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
