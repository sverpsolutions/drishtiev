<?php
$v = function ($k, $d = '') use ($c) { return html_escape($c->$k ?? $d); };
$dt = function ($k) use ($c) { return !empty($c->$k) ? date('Y-m-d\TH:i', strtotime($c->$k)) : ''; };
$sel_c = array_filter(explode(',', (string)($c->category_ids ?? '')));
$sel_p = array_filter(explode(',', (string)($c->product_ids ?? '')));
?>
<p><a href="<?= site_url('webadmin/coupons') ?>">← Coupons</a></p>
<?php if ($err): ?><div class="alert alert-error"><?= html_escape($err) ?></div><?php endif; ?>
<div class="card">
  <?= form_open(current_url()) ?>
    <div class="row">
      <div><label>Code *</label><input name="code" required maxlength="30" value="<?= $v('code') ?>" style="text-transform:uppercase"></div>
      <div><label>Description (internal)</label><input name="description" maxlength="255" value="<?= $v('description') ?>"></div>
    </div>
    <div class="row" style="margin-top:10px">
      <div><label>Type</label><select name="discount_type"><option value="percent" <?= ($c->discount_type ?? '') === 'percent' ? 'selected' : '' ?>>Percentage</option><option value="fixed" <?= ($c->discount_type ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed amount (₹)</option></select></div>
      <div><label>Value *</label><input name="discount_value" type="number" step="0.01" min="0" required value="<?= $v('discount_value') ?>"></div>
      <div><label>Max discount ₹ (percent only)</label><input name="max_discount" type="number" step="0.01" min="0" value="<?= $v('max_discount') ?>"></div>
      <div><label>Minimum order ₹</label><input name="min_order" type="number" step="0.01" min="0" value="<?= $v('min_order', 0) ?>"></div>
    </div>
    <div class="row" style="margin-top:10px">
      <div><label>Starts</label><input type="datetime-local" name="starts_at" value="<?= $dt('starts_at') ?>"></div>
      <div><label>Expires</label><input type="datetime-local" name="ends_at" value="<?= $dt('ends_at') ?>"></div>
      <div><label>Total usage limit</label><input type="number" min="0" name="usage_limit" value="<?= $v('usage_limit') ?>" placeholder="Unlimited"></div>
      <div><label>Per-customer limit (by mobile)</label><input type="number" min="0" name="per_customer_limit" value="<?= $v('per_customer_limit') ?>" placeholder="Unlimited"></div>
    </div>
    <div class="row" style="margin-top:10px">
      <div><label>Only for categories (leave empty for all; Ctrl+click to multi-select)</label><select name="category_ids[]" multiple size="5"><?php foreach ($cats as $cat): ?><option value="<?= $cat->id ?>" <?= in_array((string)$cat->id, $sel_c, true) ? 'selected' : '' ?>><?= html_escape($cat->name) ?></option><?php endforeach; ?></select></div>
      <div><label>Only for products</label><select name="product_ids[]" multiple size="5"><?php foreach ($prods as $pr): ?><option value="<?= $pr->id ?>" <?= in_array((string)$pr->id, $sel_p, true) ? 'selected' : '' ?>><?= html_escape($pr->name . ' (' . $pr->item_code . ')') ?></option><?php endforeach; ?></select></div>
    </div>
    <label class="check" style="margin:12px 0"><input type="checkbox" name="is_active" value="1" <?= !isset($c->is_active) || $c->is_active ? 'checked' : '' ?>> Active</label>
    <button class="btn btn-primary">Save coupon</button>
  </form>
</div>
