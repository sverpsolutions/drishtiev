<div class="wrap" style="padding-bottom:48px">
  <nav class="crumbs"><a href="<?= base_url() ?>">Home</a> / <?= html_escape($pg->title) ?></nav>
  <div class="card prose" style="margin:8px auto 0">
    <h1><?= html_escape($pg->title) ?></h1>
    <?= safe_html($pg->content) ?>
    <?php if ($pg->slug === 'contact'): ?>
      <p><strong><?= html_escape($company['company_name'] ?? '') ?></strong><br><?= nl2br(html_escape(str_replace('\n', "\n", $company['company_address'] ?? ''))) ?></p>
      <?php if (!empty($company['company_phone'])): ?><p>Phone: <a href="tel:<?= html_escape(preg_replace('/[^\d+]/', '', $company['company_phone'])) ?>"><?= html_escape($company['company_phone']) ?></a></p><?php endif; ?>
      <?php if (!empty($company['company_email'])): ?><p>Email: <a href="mailto:<?= html_escape($company['company_email']) ?>"><?= html_escape($company['company_email']) ?></a></p><?php endif; ?>
      <?php if (!empty($company['company_gstin'])): ?><p>GSTIN: <?= html_escape($company['company_gstin']) ?></p><?php endif; ?>
    <?php elseif ($pg->slug === 'size-guide'): $sizes = $this->db->select('gs.name, gs.measurement, gss.name AS set_name')->from('garment_sizes gs')->join('garment_size_sets gss', 'gss.id = gs.size_set_id')->where('gs.status', 1)->where('gss.status', 1)->order_by('gss.sort_order, gs.sort_order')->get()->result(); $by = []; foreach ($sizes as $s) $by[$s->set_name][] = $s; ?>
      <?php foreach ($by as $set => $rows): ?>
      <h3><?= html_escape($set) ?></h3>
      <table><thead><tr><th>Size</th><th>Measurement</th></tr></thead><tbody><?php foreach ($rows as $s): ?><tr><td><?= html_escape($s->name) ?></td><td><?= html_escape($s->measurement ?: '—') ?></td></tr><?php endforeach; ?></tbody></table>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
