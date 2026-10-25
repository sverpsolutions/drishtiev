<?php
$off = discount_pct($p->mrp, $p->price);
$has_var = count($p->variants) > 0;
$sizes = []; $colours = [];
foreach ($p->variants as $v) {
    $sizes[$v->size_name] = $v->measurement;
    $colours[$v->colour_name] = $v->hex;
}
$vjs = array_map(function ($v) {
    return ['id' => (int)$v->id, 's' => $v->size_name, 'c' => $v->colour_name, 'p' => (float)$v->price, 'm' => (float)$v->mrp, 'q' => (int)$v->stock, 'sku' => $v->variant_sku];
}, $p->variants);
$imgs = $p->images;
$show_count = ($company['web_show_stock_count'] ?? '1') === '1';
$ship = (float)($company['web_shipping_charge'] ?? 0); $free = (float)($company['web_free_shipping_above'] ?? 0);
$ret_days = (int)($company['web_return_days'] ?? 0);
$specs = array_filter([
    'Brand' => $p->brand, 'Category' => $p->category_name, 'Type' => $p->garment_category, 'Fabric' => $p->fabric_type,
    'Pattern' => $p->pattern_print, 'Fit' => $p->fit_type, 'Sleeve' => $p->sleeve_type, 'Neck' => $p->neck_type,
    'Length' => $p->garment_length, 'For' => $p->gender, 'Collection' => $p->collection_season, 'Style code' => $p->style_code,
    'Country of origin' => $p->country_of_origin,
]);
$ld = [
    '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p->name, 'sku' => $p->style_code ?: $p->item_code,
    'brand' => ['@type' => 'Brand', 'name' => $p->brand ?: ($company['company_name'] ?? '')],
    'description' => trim(strip_tags($p->description ?: $meta_desc)),
    'image' => array_map(function ($i) { return web_img($i['path']); }, $imgs) ?: [base_url('assets/web/og-image.jpg')],
    'offers' => ['@type' => 'AggregateOffer', 'priceCurrency' => 'INR', 'lowPrice' => number_format($has_var ? min(array_column($vjs, 'p')) : $p->price, 2, '.', ''),
        'highPrice' => number_format($has_var ? max(array_column($vjs, 'p')) : $p->price, 2, '.', ''), 'offerCount' => max(1, count($vjs)),
        'availability' => $p->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock', 'url' => product_url($p)],
];
if ($p->category_name) $ld['category'] = $p->category_name;
?>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= base_url() ?>">Home</a> / <?php if ($p->category_name): ?><a href="<?= category_url((object)['id' => $p->category_id, 'name' => $p->category_name]) ?>"><?= html_escape($p->category_name) ?></a> / <?php endif; ?><?= html_escape($p->name) ?></nav>
  <div class="pdp">
    <div class="gallery">
      <div class="thumbs" id="thumbs">
        <?php foreach ($imgs as $k => $im): ?>
        <button type="button" data-src="<?= web_img($im['path']) ?>" data-colour="<?= html_escape($im['colour'] ?? '') ?>" aria-current="<?= $k ? 'false' : 'true' ?>" aria-label="Image <?= $k + 1 ?>"><img src="<?= web_img($im['path']) ?>" alt="" loading="lazy"></button>
        <?php endforeach; ?>
      </div>
      <div class="main">
        <?php if ($imgs): ?><img id="main-img" src="<?= web_img($imgs[0]['path']) ?>" alt="<?= html_escape($p->name) ?>" width="900" height="1200">
        <?php else: ?><div class="ph"><span class="mono">SU</span><small><?= html_escape($p->garment_category ?: 'Suvasan Clothes') ?></small><small>Photo coming soon</small></div><?php endif; ?>
      </div>
    </div>

    <div class="pdp-info">
      <?php if ($p->brand): ?><div class="brand"><?= html_escape($p->brand) ?></div><?php endif; ?>
      <h1><?= html_escape($p->name) ?></h1>
      <div class="pdp-price" aria-live="polite">
        <span class="now" id="pp-now"><?= inr($p->price) ?></span>
        <span class="was" id="pp-was" <?= $off ? '' : 'hidden' ?>>MRP <?= inr($p->mrp) ?></span>
        <span class="off" id="pp-off" <?= $off ? '' : 'hidden' ?>><?= $off ?>% off</span>
      </div>
      <div class="muted small">Inclusive of all taxes</div>

      <form data-add-to-cart <?= $has_var ? 'data-needs-variant' : '' ?> novalidate>
        <input type="hidden" name="product_id" value="<?= (int)$p->id ?>">
        <input type="hidden" name="variant_id" value="">
        <?php if ($has_var): ?>
        <?php if (count($colours) > 0): ?>
        <div class="opt-label"><span>Colour: <span id="sel-colour" style="text-transform:none;font-weight:400"></span></span></div>
        <div class="opts-row" role="group" aria-label="Colour">
          <?php foreach ($colours as $c => $hex): ?><button type="button" class="opt colour" data-colour="<?= html_escape($c) ?>" aria-pressed="false"><span class="swatch" style="background:<?= html_escape($hex ?: '#ccc') ?>"></span><?= html_escape($c) ?></button><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="opt-label"><span>Size</span><button type="button" onclick="document.getElementById('size-guide').showModal()">Size guide</button></div>
        <div class="opts-row" role="group" aria-label="Size">
          <?php foreach ($sizes as $s => $_): ?><button type="button" class="opt" data-size="<?= html_escape($s) ?>" aria-pressed="false"><?= html_escape($s) ?></button><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <p class="stock-msg" id="stock-msg" role="status"><?php if (!$has_var) echo $p->stock > 0 ? 'In stock' : 'Out of stock'; ?></p>
        <div class="buy-row">
          <div class="qty"><button type="button" data-step="-1" aria-label="Decrease quantity">−</button><input name="qty" type="number" value="1" min="1" max="<?= max(1, min(20, (int)$p->stock)) ?>" aria-label="Quantity"><button type="button" data-step="1" aria-label="Increase quantity">+</button></div>
          <button class="btn btn-primary" type="submit" name="add" id="btn-add" <?= $p->stock <= 0 ? 'disabled' : '' ?>>Add to Bag</button>
          <button class="btn btn-gold" type="submit" name="buy_now" id="btn-buy" <?= $p->stock <= 0 ? 'disabled' : '' ?>>Buy Now</button>
          <button type="button" class="btn btn-outline wish" style="position:static;width:auto;height:auto;border-radius:999px;padding:12px 16px" data-wish="<?= (int)$p->id ?>" aria-label="Save to wishlist" aria-pressed="false"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20s-7-4.4-9-9.2C1.7 7.6 3.8 4 7.3 4c2 0 3.6 1.1 4.7 2.7C13.1 5.1 14.7 4 16.7 4c3.5 0 5.6 3.6 4.3 6.8C19 15.6 12 20 12 20z"/></svg></button>
        </div>
      </form>

      <div class="trust">
        <span>✦ <?= $ship <= 0 ? 'Free shipping' : ($free > 0 ? 'Free shipping above ' . inr($free) : 'Shipping ' . inr($ship)) ?></span>
        <span>✦ <?= $ret_days > 0 ? $ret_days . '-day returns' : '<a href="' . site_url('page/return-policy') . '">Return policy</a>' ?></span>
        <span>✦ GST invoice provided</span>
      </div>

      <div class="pdp-acc" style="margin-top:18px">
        <details open><summary>Product details</summary>
          <?php if ($p->description): ?><div style="margin:10px 0"><?= nl2br(html_escape($p->description)) ?></div><?php endif; ?>
          <table class="specs"><?php foreach ($specs as $k => $v): ?><tr><th><?= $k ?></th><td><?= html_escape($v) ?></td></tr><?php endforeach; ?></table>
        </details>
        <?php if ($p->care_instructions): ?><details><summary>Care instructions</summary><p><?= nl2br(html_escape($p->care_instructions)) ?></p></details><?php endif; ?>
        <details><summary>Delivery &amp; returns</summary>
          <p>Shipping charges (if any) are shown at checkout. Courier and tracking details are shared once your order ships. <a href="<?= site_url('page/shipping-policy') ?>">Shipping policy</a> · <a href="<?= site_url('page/return-policy') ?>">Return &amp; refund policy</a></p>
        </details>
        <?php if ($reviews): ?>
        <details><summary>Reviews (<?= count($reviews) ?>)</summary>
          <?php foreach ($reviews as $r): ?><div class="review"><span class="stars"><?= str_repeat('★', (int)$r->rating) . str_repeat('☆', 5 - (int)$r->rating) ?></span> <strong><?= html_escape($r->review_title) ?></strong><div class="small muted"><?= html_escape($r->customer_name) ?></div><p style="margin:4px 0 0"><?= html_escape($r->review_text) ?></p></div><?php endforeach; ?>
        </details>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($related): ?>
  <section class="section" style="padding-top:8px">
    <div class="sec-head"><h2>You may also like</h2><div class="orn">✦</div></div>
    <div class="pgrid"><?php foreach (array_slice($related, 0, 4) as $r) $this->load->view('shop/_card', ['p' => $r]); ?></div>
  </section>
  <?php endif; ?>
</div>

<dialog id="size-guide" aria-labelledby="sg-title">
  <div class="dlg-head"><h3 id="sg-title">Size Guide</h3><button class="close-x" onclick="this.closest('dialog').close()" aria-label="Close">×</button></div>
  <div class="dlg-body prose">
    <?php if (array_filter($sizes)): ?>
    <table class="tbl"><thead><tr><th>Size</th><th>Measurement</th></tr></thead><tbody>
      <?php foreach ($sizes as $s => $m): ?><tr><td><?= html_escape($s) ?></td><td><?= html_escape($m ?: '—') ?></td></tr><?php endforeach; ?>
    </tbody></table>
    <?php endif; ?>
    <?= safe_html($size_guide->content ?? '') ?>
    <?php if (!array_filter($sizes) && empty($size_guide->content)): ?><p>Please contact the store for measurements of this style.</p><?php endif; ?>
  </div>
</dialog>

<?php if ($has_var): ?>
<script>
(function () {
  var V = <?= json_encode($vjs) ?>, showCount = <?= $show_count ? 'true' : 'false' ?>;
  var form = document.querySelector('[data-add-to-cart]'), sel = {s: null, c: null};
  var colours = [].slice.call(document.querySelectorAll('[data-colour].opt')), sizes = [].slice.call(document.querySelectorAll('[data-size]'));
  var msg = document.getElementById('stock-msg'), add = document.getElementById('btn-add'), buy = document.getElementById('btn-buy');
  var inr = function (n) { return '₹' + Math.round(n).toLocaleString('en-IN'); };
  function find(s, c) { return V.filter(function (v) { return (s === null || v.s === s) && (c === null || v.c === c); }); }
  function paint() {
    sizes.forEach(function (b) {
      var st = find(b.dataset.size, sel.c).reduce(function (a, v) { return a + v.q; }, 0);
      b.classList.toggle('is-out', st <= 0); b.setAttribute('aria-pressed', b.dataset.size === sel.s ? 'true' : 'false');
      b.title = st <= 0 ? 'Out of stock' : '';
    });
    colours.forEach(function (b) {
      var st = find(sel.s, b.dataset.colour).reduce(function (a, v) { return a + v.q; }, 0);
      b.classList.toggle('is-out', st <= 0); b.setAttribute('aria-pressed', b.dataset.colour === sel.c ? 'true' : 'false');
    });
    document.getElementById('sel-colour') && (document.getElementById('sel-colour').textContent = sel.c || '');
    var exact = (sel.s !== null && (sel.c !== null || !colours.length)) ? find(sel.s, sel.c)[0] : null;
    form.variant_id.value = exact && exact.q > 0 ? exact.id : '';
    var ok = !!(exact && exact.q > 0);
    add.disabled = buy.disabled = !ok;
    if (!exact) { msg.className = 'stock-msg'; msg.textContent = sel.s === null ? 'Select a size' : 'Select a colour'; }
    else if (exact.q <= 0) { msg.className = 'stock-msg out'; msg.textContent = 'Out of stock in this size/colour'; }
    else if (exact.q <= 3) { msg.className = 'stock-msg low'; msg.textContent = showCount ? 'Hurry — only ' + exact.q + ' left' : 'Few left'; }
    else { msg.className = 'stock-msg ok'; msg.textContent = 'In stock'; }
    if (exact) {
      form.qty.max = Math.max(1, Math.min(20, exact.q)); if (+form.qty.value > +form.qty.max) form.qty.value = form.qty.max;
      document.getElementById('pp-now').textContent = inr(exact.p);
      var off = exact.m > exact.p ? Math.round((exact.m - exact.p) * 100 / exact.m) : 0;
      var was = document.getElementById('pp-was'), o = document.getElementById('pp-off');
      was.hidden = o.hidden = !off; was.textContent = 'MRP ' + inr(exact.m); o.textContent = off + '% off';
    }
  }
  sizes.forEach(function (b) { b.addEventListener('click', function () { sel.s = b.dataset.size; paint(); }); });
  colours.forEach(function (b) { b.addEventListener('click', function () {
    sel.c = b.dataset.colour; paint();
    var t = document.querySelector('#thumbs [data-colour="' + CSS.escape(sel.c) + '"]'); if (t) t.click();
  }); });
  // Preselect the only option / first in-stock colour
  if (colours.length === 1) sel.c = colours[0].dataset.colour;
  else { var firstIn = V.filter(function (v) { return v.q > 0; })[0]; if (firstIn && colours.length) sel.c = firstIn.c; }
  if (sizes.length === 1) sel.s = sizes[0].dataset.size;
  paint();
})();
</script>
<?php endif; ?>
<script>
document.querySelectorAll('#thumbs button').forEach(function (b) {
  b.addEventListener('click', function () {
    var m = document.getElementById('main-img'); if (m) m.src = b.dataset.src;
    document.querySelectorAll('#thumbs button').forEach(function (x) { x.setAttribute('aria-current', x === b ? 'true' : 'false'); });
  });
});
</script>
