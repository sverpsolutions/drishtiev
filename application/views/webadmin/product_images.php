<p><a href="<?= site_url('webadmin/products') ?>">← Products</a></p>
<h1><?= html_escape($p->name) ?></h1>
<div class="card">
  <h3>Upload photos</h3>
  <p class="small muted">JPG, PNG or WebP, up to 5 MB each, max 12 at a time. Portrait 3:4 photos (e.g. 1200×1600) look best. Images are re-encoded and resized automatically. Tag a colour to show that photo when the shopper picks the colour.</p>
  <?= form_open_multipart('webadmin/product_images/' . $p->id, ['class' => 'row']) ?>
    <div><label for="images">Photos</label><input id="images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required></div>
    <div><label for="colour_name">Colour (optional)</label><select id="colour_name" name="colour_name"><option value="">All colours</option><?php foreach ($colours as $c): ?><option><?= html_escape($c) ?></option><?php endforeach; ?></select></div>
    <div style="flex:0 0 auto"><button class="btn btn-primary">Upload</button></div>
  </form>
</div>
<div class="card">
  <h3>Gallery (first image is the main photo)</h3>
  <?php if (!$images && !$p->image1): ?><p class="muted">No photos yet. The website shows a branded placeholder until you upload one.</p><?php endif; ?>
  <div class="thumbs">
    <?php foreach ($images as $im): ?>
    <div class="t">
      <img src="<?= web_img($im->path) ?>" alt="">
      <?= form_open('webadmin/image_action/' . $im->id, ['style' => 'padding:8px']) ?>
        <label>Order</label><input type="number" name="sort_order" value="<?= (int)$im->sort_order ?>">
        <label style="margin-top:6px">Colour</label><select name="colour_name"><option value="">All</option><?php foreach ($colours as $c): ?><option <?= $im->colour_name === $c ? 'selected' : '' ?>><?= html_escape($c) ?></option><?php endforeach; ?></select>
        <div style="display:flex;gap:4px;margin-top:6px"><button class="btn btn-sm">Save</button></div>
      </form>
      <?= form_open('webadmin/image_action/' . $im->id, ['style' => 'padding:0 8px 8px', 'data-confirm' => 'Delete this photo?']) ?><button class="btn btn-sm btn-danger" name="delete" value="1">Delete</button></form>
    </div>
    <?php endforeach; ?>
    <?php if ($p->image1): ?><div class="t"><img src="<?= web_img($p->image1) ?>" alt=""><p class="small muted" style="padding:8px;margin:0">From product master (image1)</p></div><?php endif; ?>
  </div>
</div>
