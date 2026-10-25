<p><a href="<?= site_url('webadmin/pages') ?>">← Website content</a></p>
<div class="card">
  <?= form_open('webadmin/pages/' . $pg->slug) ?>
    <div class="f"><label for="title">Title</label><input id="title" name="title" maxlength="150" value="<?= html_escape($pg->title) ?>"></div>
    <div class="f"><label for="content">Content (basic HTML allowed: &lt;p&gt;, &lt;h3&gt;, &lt;ul&gt;&lt;li&gt;, &lt;strong&gt;, &lt;a href&gt;, &lt;table&gt;. Scripts are removed.)</label>
      <textarea id="content" name="content" rows="18" style="font-family:Consolas,monospace;font-size:13px"><?= html_escape($pg->content) ?></textarea></div>
    <?php if ($pg->slug === 'size-guide'): ?><p class="small muted">The size charts from the Sizes master are shown automatically below this text.</p><?php endif; ?>
    <?php if ($pg->slug === 'contact'): ?><p class="small muted">Store address, phone, email and GSTIN are shown automatically below this text.</p><?php endif; ?>
    <div class="f"><label for="meta">Meta description (for Google, max 155 characters)</label><input id="meta" name="meta_description" maxlength="255" value="<?= html_escape($pg->meta_description) ?>"></div>
    <button class="btn btn-primary">Save page</button> <a class="btn" href="<?= site_url('page/' . $pg->slug) ?>" target="_blank">View</a>
  </form>
</div>
