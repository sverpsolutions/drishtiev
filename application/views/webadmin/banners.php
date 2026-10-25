<p class="muted">The homepage slider has exactly four slots. Recommended images: desktop 1920×640 px, mobile 800×1000 px (keep the subject on the right on desktop and the top on mobile — text sits on the left / bottom). Only mention discounts that are actually set up (prices or coupons).</p>
<?php foreach ($rows as $b): $dt = function ($v) { return $v ? date('Y-m-d\TH:i', strtotime($v)) : ''; }; ?>
<div class="card">
  <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <h3>Slot <?= (int)$b->slot ?> — <?= html_escape($b->title) ?> <?= $b->is_active ? '<span class="badge b-green">Active</span>' : '<span class="badge b-grey">Hidden</span>' ?></h3>
  </div>
  <div class="grid" style="grid-template-columns:minmax(0,1fr) 300px">
    <?= form_open_multipart('webadmin/banner_save/' . $b->slot) ?>
      <div class="row"><div><label>Headline *</label><input name="title" required maxlength="150" value="<?= html_escape($b->title) ?>"></div><div><label>Offer text (optional)</label><input name="offer_text" maxlength="100" value="<?= html_escape($b->offer_text) ?>"></div></div>
      <div class="f" style="margin-top:10px"><label>Sub-heading</label><input name="subtitle" maxlength="255" value="<?= html_escape($b->subtitle) ?>"></div>
      <div class="row"><div><label>Button text</label><input name="button_text" maxlength="40" value="<?= html_escape($b->button_text) ?>"></div><div><label>Link (e.g. shop?gender=Men, category/1/women-ethnic-wear)</label><input name="link" maxlength="255" value="<?= html_escape($b->link) ?>"></div><div style="max-width:110px"><label>Display order</label><input type="number" name="sort_order" value="<?= (int)$b->sort_order ?>"></div></div>
      <div class="row" style="margin-top:10px"><div><label>Show from (optional)</label><input type="datetime-local" name="starts_at" value="<?= $dt($b->starts_at) ?>"></div><div><label>Show until (optional)</label><input type="datetime-local" name="ends_at" value="<?= $dt($b->ends_at) ?>"></div></div>
      <div class="row" style="margin-top:10px"><div><label>Desktop image</label><input type="file" name="image_desktop" accept="image/jpeg,image/png,image/webp"></div><div><label>Mobile image</label><input type="file" name="image_mobile" accept="image/jpeg,image/png,image/webp"></div></div>
      <label class="check" style="margin:10px 0"><input type="checkbox" name="is_active" value="1" <?= $b->is_active ? 'checked' : '' ?>> Active</label>
      <button class="btn btn-primary">Save slot <?= (int)$b->slot ?></button>
    </form>
    <div>
      <label>Desktop preview</label><img src="<?= web_img($b->image_desktop) ?>" alt="" style="width:100%;border-radius:6px;aspect-ratio:3/1;object-fit:cover">
      <label style="margin-top:8px">Mobile preview</label><img src="<?= web_img($b->image_mobile ?: $b->image_desktop) ?>" alt="" style="width:120px;border-radius:6px;aspect-ratio:4/5;object-fit:cover">
    </div>
  </div>
</div>
<?php endforeach; ?>
