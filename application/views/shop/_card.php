<?php
$img = $p->web_image ?: $p->image1;
$off = discount_pct($p->mrp, $p->price);
$out = (float)$p->stock <= 0;
?>
<article class="pcard<?= $out ? ' soldout-veil' : '' ?>">
  <div class="media">
    <?php if ($out): ?><span class="tag soldout">Sold out</span><?php elseif ($off): ?><span class="tag"><?= $off ?>% OFF</span><?php endif; ?>
    <button type="button" class="wish" data-wish="<?= (int)$p->id ?>" aria-label="Save <?= html_escape($p->name) ?> to wishlist" aria-pressed="false">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20s-7-4.4-9-9.2C1.7 7.6 3.8 4 7.3 4c2 0 3.6 1.1 4.7 2.7C13.1 5.1 14.7 4 16.7 4c3.5 0 5.6 3.6 4.3 6.8C19 15.6 12 20 12 20z"/></svg>
    </button>
    <?php if ($img): ?>
      <img src="<?= web_img($img) ?>" alt="<?= html_escape($p->name) ?>" loading="lazy" decoding="async" width="600" height="800">
    <?php else: ?>
      <div class="ph" aria-hidden="true"><span class="mono">SU</span><small><?= html_escape($p->garment_category ?: ($p->category_name ?: 'Suvasan')) ?></small></div>
    <?php endif; ?>
  </div>
  <div class="info">
    <?php if ($p->brand): ?><span class="brand"><?= html_escape($p->brand) ?></span><?php endif; ?>
    <a class="name" href="<?= product_url($p) ?>"><?= html_escape($p->name) ?></a>
    <div class="price">
      <span class="now"><?= inr($p->price) ?></span>
      <?php if ($off): ?><span class="was"><?= inr($p->mrp) ?></span><span class="off"><?= $off ?>% off</span><?php endif; ?>
    </div>
  </div>
</article>
