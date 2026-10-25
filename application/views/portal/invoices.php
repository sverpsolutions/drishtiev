<?php // FILE: application/views/portal/invoices.php — Sandal House ?>
<style>
.bb-inv-wrap { padding: 12px; max-width: 700px; margin: 0 auto; }
.bb-inv-card {
  background: #fff; border-radius: 14px;
  border: 1.5px solid #f0f0f0;
  box-shadow: 0 2px 8px rgba(59,35,20,0.06);
  margin-bottom: 10px; overflow: hidden;
  transition: all 0.2s; text-decoration: none; display: block;
}
.bb-inv-card:hover { box-shadow: 0 4px 16px rgba(59,35,20,0.1); border-color: var(--gold-light); text-decoration:none; }
</style>

<div class="bb-inv-wrap">
  <div style="margin-bottom:14px;">
    <h2 style="font-size:1.05rem; font-weight:800; color:var(--primary); margin:0 0 2px;">📄 My Invoices</h2>
    <p style="font-size:0.78rem; color:var(--text-muted); margin:0;">All your purchase invoices</p>
  </div>

  <?php if (empty($invoices)): ?>
  <div style="text-align:center; padding:50px 20px; background:#fff; border-radius:16px; box-shadow:0 2px 10px rgba(59,35,20,0.06);">
    <div style="font-size:3rem; opacity:0.25; margin-bottom:12px;">📄</div>
    <h5 style="font-size:0.96rem; font-weight:700; color:var(--primary);">No Invoices Yet</h5>
    <p style="font-size:0.78rem; color:var(--text-muted);">Your invoices will appear here after purchases.</p>
  </div>
  <?php else: ?>

  <?php foreach ($invoices as $inv):
    $st = $inv->status;
    $badge_bg  = $st==='paid' ? '#e8f5e9' : ($st==='partial' ? '#fff8e1' : '#fce4ec');
    $badge_col = $st==='paid' ? '#2e7d32' : ($st==='partial' ? '#f57f17' : '#c62828');
  ?>
  <a href="<?= base_url('portal/invoice/'.$inv->id) ?>" class="bb-inv-card">
    <div style="display:flex; align-items:center; padding:12px 14px; gap:12px;">
      <div style="width:42px;height:42px;border-radius:10px;background:var(--gold-light);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">📄</div>
      <div style="flex:1; min-width:0;">
        <div style="font-size:0.82rem; font-weight:800; color:var(--primary);"><?= htmlspecialchars($inv->invoice_no) ?></div>
        <div style="font-size:0.68rem; color:#90a4ae;"><?= date('d M Y', strtotime($inv->invoice_date)) ?></div>
      </div>
      <div style="text-align:right; flex-shrink:0;">
        <div style="font-size:0.85rem; font-weight:800; color:#37474f;">₹<?= number_format($inv->total_amount, 2) ?></div>
        <?php if ($inv->due_amount > 0): ?>
        <div style="font-size:0.68rem; color:#c62828; font-weight:700;">Due: ₹<?= number_format($inv->due_amount, 2) ?></div>
        <?php endif; ?>
        <span style="display:inline-block; font-size:0.65rem; font-weight:700; padding:2px 8px; border-radius:12px; background:<?= $badge_bg ?>; color:<?= $badge_col ?>; margin-top:2px;">
          <?= ucfirst($st) ?>
        </span>
      </div>
    </div>
  </a>
  <?php endforeach; ?>

  <?php endif; ?>
</div>
