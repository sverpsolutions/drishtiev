<div class="card row">
  <form method="get" action="<?= site_url('webadmin/products') ?>" class="row" style="flex:3">
    <div><label for="q">Search name / item code / style code</label><input id="q" name="q" value="<?= html_escape($q) ?>"></div>
    <div style="flex:0 0 auto"><button class="btn btn-primary">Search</button></div>
  </form>
  <div style="flex:0 0 auto;display:flex;gap:6px">
    <a class="btn btn-gold" href="<?= site_url('garments/create') ?>">+ Add product with variants</a>
    <a class="btn" href="<?= site_url('garments/import') ?>">Bulk import</a>
  </div>
</div>
<div class="alert alert-info">Product details, prices, GST/HSN, sizes, colours, SKUs and barcodes are managed in the shared <a href="<?= site_url('garments') ?>">Product Master</a> (also used by POS billing). Here you control what appears on the website and upload photos.</div>
<div class="card">
  <div class="tbl-wrap"><table class="tbl">
    <tr><th>Product</th><th>Category</th><th class="r">Price</th><th class="r">Variants</th><th class="r">Web stock</th><th class="r">Photos</th><th>On website</th><th>Featured</th><th></th></tr>
    <?php foreach ($rows as $p): ?>
    <tr>
      <td><strong><?= html_escape($p->name) ?></strong><div class="small muted"><?= html_escape($p->item_code) ?><?= $p->style_code ? ' · ' . html_escape($p->style_code) : '' ?><?= $p->brand ? ' · ' . html_escape($p->brand) : '' ?></div>
        <?php if (!$p->status): ?><span class="badge b-grey">Inactive in master</span><?php endif; ?></td>
      <td><?= html_escape($p->category) ?></td>
      <td class="r nowrap"><?= inr($p->selling_price > 0 ? $p->selling_price : $p->mrp) ?><?php if ($p->mrp > $p->selling_price && $p->selling_price > 0): ?><div class="small muted"><s><?= inr($p->mrp) ?></s></div><?php endif; ?></td>
      <td class="r"><?= (int)$p->vcount ?></td>
      <td class="r"><span class="badge <?= $p->vstock > 0 ? 'b-green' : 'b-red' ?>"><?= (float)$p->vstock ?></span></td>
      <td class="r"><?= (int)$p->imgs + ($p->image1 ? 1 : 0) ?: '<span class="badge b-amber">none</span>' ?></td>
      <?php foreach (['web_visible', 'is_featured'] as $fl): ?>
      <td><?= form_open('webadmin/product_flags/' . $p->id) ?><input type="hidden" name="field" value="<?= $fl ?>"><input type="hidden" name="value" value="<?= $p->$fl ? 0 : 1 ?>">
        <button class="btn btn-sm <?= $p->$fl ? 'btn-primary' : '' ?>" title="Click to toggle"><?= $p->$fl ? 'Yes' : 'No' ?></button></form></td>
      <?php endforeach; ?>
      <td class="nowrap"><a class="btn btn-sm" href="<?= site_url('webadmin/product_images/' . $p->id) ?>">Photos</a> <a class="btn btn-sm" href="<?= site_url('garments/edit/' . $p->id) ?>">Edit</a> <a class="btn btn-sm" href="<?= product_url($p) ?>" target="_blank">View</a></td>
    </tr>
    <?php endforeach; ?>
  </table></div>
</div>
