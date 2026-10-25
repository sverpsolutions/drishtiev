<div class="card">
  <p class="muted">These pages are linked from the website footer. Contact details, business address, GSTIN and social links come from <a href="<?= site_url('webadmin/settings') ?>">Store Settings</a>.</p>
  <table class="tbl">
    <tr><th>Page</th><th>URL</th><th>Last updated</th><th></th></tr>
    <?php foreach ($rows as $pg): ?>
    <tr><td><strong><?= html_escape($pg->title) ?></strong><?= trim(strip_tags((string)$pg->content)) === '' ? ' <span class="badge b-amber">empty</span>' : '' ?></td><td><a href="<?= site_url('page/' . $pg->slug) ?>" target="_blank">/page/<?= html_escape($pg->slug) ?></a></td><td><?= date('d M Y', strtotime($pg->updated_at)) ?></td><td><a class="btn btn-sm" href="<?= site_url('webadmin/pages/' . $pg->slug) ?>">Edit</a></td></tr>
    <?php endforeach; ?>
  </table>
</div>
