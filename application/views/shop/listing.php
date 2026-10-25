<?php
$g = $this->input->get(null, true) ?: [];
$base = $cat ? category_url($cat) : site_url('shop');
$qs = function ($change) use ($g) {
    $q = array_merge($g, $change);
    unset($q['page']);
    foreach ($q as $k => $v) if ($v === null || $v === '' || $v === []) unset($q[$k]);
    if (isset($change['page'])) $q['page'] = $change['page'];
    return $q ? '?' . http_build_query($q) : '';
};
$checked = function ($key, $val) use ($f) { return in_array((string)$val, array_map('strval', (array)$f[$key]), true) ? 'checked' : ''; };
$sorts = ['' => 'Recommended', 'newest' => 'Newest', 'popular' => 'Popularity', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low', 'discount' => 'Discount'];
// Active filter pills
$pills = [];
foreach (['q' => 'Search', 'gender' => '', 'min' => 'Min ₹', 'max' => 'Max ₹'] as $k => $lbl) if (($f[$k] ?? '') !== '') $pills[] = [$lbl . $f[$k], [$k => null]];
foreach (['size', 'colour', 'brand'] as $k) foreach ((array)$f[$k] as $v) {
    $label = $v;
    if ($k === 'brand') foreach ($nav['brands'] as $b) if ($b->id == $v) $label = $b->name;
    $pills[] = [$label, [$k => array_values(array_diff((array)$f[$k], [$v]))]];
}
if ($f['in_stock']) $pills[] = ['In stock', ['in_stock' => null]];
if ($f['sale']) $pills[] = ['On offer', ['sale' => null]];
?>
<div class="wrap">
  <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= base_url() ?>">Home</a> / <?php if ($cat): ?><a href="<?= site_url('shop') ?>">Shop</a> / <?= html_escape($cat->name) ?><?php else: ?>Shop<?php endif; ?></nav>
  <div class="toolbar">
    <div>
      <h1 style="margin:0"><?= html_escape($heading) ?></h1>
      <span class="muted small"><?= number_format($total) ?> product<?= $total == 1 ? '' : 's' ?></span>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
      <button type="button" class="btn btn-outline btn-sm filter-toggle" data-open=".filters">Filters</button>
      <form method="get" action="<?= $base ?>">
        <?php foreach ($g as $k => $v) if ($k !== 'sort' && $k !== 'page') foreach ((array)$v as $vv): ?><input type="hidden" name="<?= html_escape($k) . (is_array($v) ? '[]' : '') ?>" value="<?= html_escape($vv) ?>"><?php endforeach; ?>
        <label for="sort" class="sr-only">Sort by</label>
        <select id="sort" name="sort" class="input" data-autosubmit>
          <?php foreach ($sorts as $k => $lbl): ?><option value="<?= $k ?>" <?= ($f['sort'] ?? '') === $k ? 'selected' : '' ?>><?= $lbl ?></option><?php endforeach; ?>
        </select>
      </form>
    </div>
  </div>
  <?php if ($pills): ?>
  <div class="active-filters">
    <?php foreach ($pills as [$lbl, $chg]): ?><a href="<?= $base . $qs($chg) ?>"><?= html_escape($lbl) ?> ✕</a><?php endforeach; ?>
    <a href="<?= $base ?>">Clear all</a>
  </div>
  <?php endif; ?>

  <div class="listing">
    <form class="filters" method="get" action="<?= $base ?>" aria-label="Filters">
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0">
        <strong style="font-family:var(--serif);font-size:20px;color:var(--maroon-d)">Filters</strong>
        <button type="button" class="close-x filter-toggle" data-close aria-label="Close filters">×</button>
      </div>
      <?php if ($f['q'] !== ''): ?><input type="hidden" name="q" value="<?= html_escape($f['q']) ?>"><?php endif; ?>
      <?php if ($f['sort']): ?><input type="hidden" name="sort" value="<?= html_escape($f['sort']) ?>"><?php endif; ?>
      <?php if (!$cat && count($nav['categories']) > 0): ?>
      <details open><summary>Category</summary><div class="opts">
        <?php foreach ($nav['categories'] as $c): ?><label><input type="radio" name="category" value="<?= $c->id ?>" <?= $f['category'] == $c->id ? 'checked' : '' ?>> <?= html_escape($c->name) ?> <span class="muted small">(<?= $c->n ?>)</span></label><?php endforeach; ?>
        <?php if ($f['category']): ?><label><input type="radio" name="category" value=""> All categories</label><?php endif; ?>
      </div></details>
      <?php endif; ?>
      <?php $subs = array_filter($nav['sub_categories'], function ($s) use ($f) { return !$f['category'] || $s->category_id == $f['category']; }); if ($subs): ?>
      <details open><summary>Type</summary><div class="opts">
        <?php foreach ($subs as $s): ?><label><input type="radio" name="sub" value="<?= $s->id ?>" <?= $f['sub_category'] == $s->id ? 'checked' : '' ?>> <?= html_escape($s->name) ?></label><?php endforeach; ?>
      </div></details>
      <?php endif; ?>
      <?php if (count($nav['genders']) > 1): ?>
      <details open><summary>For</summary><div class="opts">
        <?php foreach (['Women', 'Men', 'Kids'] as $gg): if ($gg === 'Kids' ? !array_intersect(['Boys', 'Girls'], $nav['genders']) : !in_array($gg, $nav['genders'], true)) continue; ?>
        <label><input type="radio" name="gender" value="<?= $gg ?>" <?= $f['gender'] === $gg ? 'checked' : '' ?>> <?= $gg ?></label>
        <?php endforeach; ?>
      </div></details>
      <?php endif; ?>
      <?php if ($nav['sizes']): ?>
      <details open><summary>Size</summary><div class="opts chips">
        <?php foreach ($nav['sizes'] as $s): ?><label class="chip"><input type="checkbox" name="size[]" value="<?= html_escape($s->name) ?>" <?= $checked('size', $s->name) ?>><?= html_escape($s->name) ?></label><?php endforeach; ?>
      </div></details>
      <?php endif; ?>
      <?php if ($nav['colours']): ?>
      <details open><summary>Colour</summary><div class="opts">
        <?php foreach ($nav['colours'] as $c): ?><label><input type="checkbox" name="colour[]" value="<?= html_escape($c->name) ?>" <?= $checked('colour', $c->name) ?>><span class="swatch" style="background:<?= html_escape($c->hex ?: '#ccc') ?>"></span><?= html_escape($c->name) ?></label><?php endforeach; ?>
      </div></details>
      <?php endif; ?>
      <?php if (count($nav['brands']) > 1): ?>
      <details><summary>Brand</summary><div class="opts">
        <?php foreach ($nav['brands'] as $b): ?><label><input type="checkbox" name="brand[]" value="<?= $b->id ?>" <?= $checked('brand', $b->id) ?>> <?= html_escape($b->name) ?></label><?php endforeach; ?>
      </div></details>
      <?php endif; ?>
      <details open><summary>Price (₹)</summary><div class="opts" style="flex-direction:row;gap:8px">
        <label class="sr-only" for="pmin">Minimum price</label><input id="pmin" class="input" type="number" min="0" name="min" placeholder="Min" value="<?= html_escape($f['min']) ?>">
        <label class="sr-only" for="pmax">Maximum price</label><input id="pmax" class="input" type="number" min="0" name="max" placeholder="Max" value="<?= html_escape($f['max']) ?>">
      </div></details>
      <details open><summary>Availability</summary><div class="opts">
        <label><input type="checkbox" name="in_stock" value="1" <?= $f['in_stock'] ? 'checked' : '' ?>> In stock only</label>
        <label><input type="checkbox" name="sale" value="1" <?= $f['sale'] ? 'checked' : '' ?>> On offer</label>
      </div></details>
      <button class="btn btn-primary btn-block" type="submit" style="margin-top:14px">Apply filters</button>
    </form>

    <div>
      <?php if ($rows): ?>
      <div class="pgrid"><?php foreach ($rows as $p) $this->load->view('shop/_card', ['p' => $p]); ?></div>
      <?php if ($pages > 1): ?>
      <nav class="pager" aria-label="Pagination">
        <?php if ($f['page'] > 1): ?><a href="<?= $base . $qs(['page' => $f['page'] - 1]) ?>" rel="prev">‹ Prev</a><?php endif; ?>
        <?php for ($i = max(1, $f['page'] - 2); $i <= min($pages, $f['page'] + 2); $i++): ?>
          <?php if ($i == $f['page']): ?><span class="is-current" aria-current="page"><?= $i ?></span><?php else: ?><a href="<?= $base . $qs(['page' => $i]) ?>"><?= $i ?></a><?php endif; ?>
        <?php endfor; ?>
        <?php if ($f['page'] < $pages): ?><a href="<?= $base . $qs(['page' => $f['page'] + 1]) ?>" rel="next">Next ›</a><?php endif; ?>
      </nav>
      <?php endif; ?>
      <?php else: ?>
      <div class="empty card">
        <div class="mono">✦</div>
        <h2>No styles found</h2>
        <p class="muted"><?= $pills ? 'Try removing a filter or two.' : 'New pieces are added regularly — please check back soon.' ?></p>
        <a class="btn btn-primary" href="<?= site_url('shop') ?>">Browse all products</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
