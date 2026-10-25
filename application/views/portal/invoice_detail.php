<?php // FILE: application/views/portal/invoice_detail.php — Sandal House ?>
<style>
.bb-inv-d-wrap { padding: 12px; max-width: 700px; margin: 0 auto; }
.bb-inv-d-card {
  background: #fff; border-radius: 14px;
  border: 1.5px solid #f0f0f0;
  box-shadow: 0 2px 8px rgba(59,35,20,0.06);
  margin-bottom: 12px; overflow: hidden;
}
.bb-inv-d-head {
  padding: 12px 16px; border-bottom: 1px solid #f5f5f5;
  font-size: 0.88rem; font-weight: 700; color: var(--primary);
  display: flex; align-items: center; gap: 6px;
}
.bb-inv-d-head::before {
  content: ''; display: inline-block; width: 3px; height: 14px;
  background: linear-gradient(var(--gold),var(--primary)); border-radius: 2px;
}
.bb-inv-d-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 10px 16px; border-bottom: 1px solid #f5f5f5;
  font-size: 0.8rem;
}
.bb-inv-d-row:last-child { border-bottom: none; }
.bb-inv-d-lbl { color: #78909c; }
.bb-inv-d-val { color: var(--primary); font-weight: 700; text-align: right; }

/* Items */
.bb-item-line {
  display: flex; align-items: flex-start; padding: 10px 16px;
  border-bottom: 1px dashed #f0f0f0; gap: 8px;
}
.bb-item-line:last-child { border-bottom: none; }
.bb-item-num {
  width: 24px; height: 24px; border-radius: 6px;
  background: var(--gold-light); color: var(--primary);
  font-size: 0.68rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; margin-top: 2px;
}
.bb-item-info { flex: 1; min-width: 0; }
.bb-item-pname { font-size: 0.82rem; font-weight: 600; color: var(--primary); }
.bb-item-batch { font-size: 0.65rem; color: #90a4ae; }
.bb-item-qty-col { font-size: 0.78rem; color: #78909c; text-align: right; white-space: nowrap; }
.bb-item-amt-col { font-size: 0.86rem; font-weight: 700; color: var(--gold-dark); white-space: nowrap; text-align: right; }

/* Totals */
.bb-totals-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 8px 16px; font-size: 0.8rem;
}
.bb-totals-lbl { color: #78909c; }
.bb-totals-val { font-weight: 700; color: var(--primary); }
.bb-grand-line {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 16px;
  background: linear-gradient(135deg, var(--gold-light), #fff);
  border-top: 1.5px dashed var(--gold); font-size: 0.88rem;
}
.bb-grand-label { font-weight: 700; color: var(--primary); }
.bb-grand-amount { font-size: 1.2rem; font-weight: 800; color: var(--gold-dark); }
.bb-due-line {
  display: flex; justify-content: space-between; align-items: center;
  padding: 10px 16px;
  background: #fce4ec; border-top: 1px solid #f8bbd0;
  font-size: 0.82rem;
}
.bb-paid-line {
  display: flex; justify-content: space-between; align-items: center;
  padding: 8px 16px;
  background: #e8f5e9; border-top: 1px solid #c8e6c9;
  font-size: 0.78rem;
}
</style>

<div class="bb-inv-d-wrap">

  <!-- Back + Title -->
  <div style="margin-bottom:14px;">
    <a href="<?= base_url('portal/invoices') ?>" style="display:inline-flex;align-items:center;gap:4px;color:var(--primary);font-size:0.78rem;font-weight:600;text-decoration:none;background:var(--gold-light);border-radius:20px;padding:6px 14px;margin-bottom:10px;">
      ← My Invoices
    </a>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <h2 style="font-size:1.05rem;font-weight:800;color:var(--primary);margin:0;"><?= htmlspecialchars($invoice->invoice_no) ?></h2>
      <?php
      $st = $invoice->status;
      $bc = $st==='paid' ? '#e8f5e9' : ($st==='partial' ? '#fff8e1' : '#fce4ec');
      $tc = $st==='paid' ? '#2e7d32' : ($st==='partial' ? '#f57f17' : '#c62828');
      ?>
      <span style="background:<?= $bc ?>;color:<?= $tc ?>;padding:3px 12px;border-radius:20px;font-size:0.7rem;font-weight:700;"><?= ucfirst($st) ?></span>
    </div>
  </div>

  <!-- Invoice Info -->
  <div class="bb-inv-d-card">
    <div class="bb-inv-d-head">📄 Invoice Details</div>
    <div class="bb-inv-d-row">
      <span class="bb-inv-d-lbl">📅 Invoice Date</span>
      <span class="bb-inv-d-val"><?= date('d M Y', strtotime($invoice->invoice_date)) ?></span>
    </div>
    <div class="bb-inv-d-row">
      <span class="bb-inv-d-lbl">📦 Status</span>
      <span style="background:<?= $bc ?>;color:<?= $tc ?>;padding:2px 10px;border-radius:16px;font-size:0.68rem;font-weight:700;"><?= ucfirst($st) ?></span>
    </div>
    <?php if (!empty($invoice->payment_mode)): ?>
    <div class="bb-inv-d-row">
      <span class="bb-inv-d-lbl">💳 Payment Mode</span>
      <span class="bb-inv-d-val"><?= htmlspecialchars($invoice->payment_mode) ?></span>
    </div>
    <?php endif; ?>
    <div class="bb-inv-d-row" style="border-bottom:none;">
      <a href="<?= base_url('billing/print_invoice/'.$invoice->id) ?>" target="_blank"
         style="display:inline-flex;align-items:center;gap:6px;background:var(--gold-light);color:var(--primary);border-radius:20px;padding:7px 16px;font-size:0.76rem;font-weight:700;text-decoration:none;">
        🖨️ Print / Download PDF
      </a>
    </div>
  </div>

  <!-- Items -->
  <div class="bb-inv-d-card">
    <div class="bb-inv-d-head">🛒 Invoice Items</div>
    <?php foreach ($items as $i => $item): ?>
    <div class="bb-item-line">
      <div class="bb-item-num"><?= $i+1 ?></div>
      <div class="bb-item-info">
        <div class="bb-item-pname"><?= htmlspecialchars($item->product_name) ?></div>
        <?php if (!empty($item->batch_no)): ?>
        <div class="bb-item-batch">Batch: <?= htmlspecialchars($item->batch_no) ?></div>
        <?php endif; ?>
      </div>
      <div style="text-align:right;flex-shrink:0;">
        <div class="bb-item-qty-col"><?= $item->qty ?> <?= htmlspecialchars($item->unit ?? '') ?> × ₹<?= number_format($item->rate, 2) ?></div>
        <div class="bb-item-amt-col">₹<?= number_format($item->amount, 2) ?></div>
        <div style="margin-top:4px;">
          <a href="<?= base_url('portal/product_detail/'.$item->product_id.'#write-review') ?>"
             style="font-size:0.62rem;font-weight:700;background:var(--gold-light);color:var(--gold-dark);border-radius:10px;padding:2px 8px;text-decoration:none;white-space:nowrap;">
            ⭐ Review
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- Totals -->
    <div class="bb-totals-row">
      <span class="bb-totals-lbl">Subtotal</span>
      <span class="bb-totals-val">₹<?= number_format($invoice->subtotal, 2) ?></span>
    </div>
    <?php if (!empty($invoice->discount) && $invoice->discount > 0): ?>
    <div class="bb-totals-row">
      <span class="bb-totals-lbl">Discount</span>
      <span style="font-weight:700;color:#c62828;">− ₹<?= number_format($invoice->discount, 2) ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($invoice->total_gst) && $invoice->total_gst > 0): ?>
    <div class="bb-totals-row">
      <span class="bb-totals-lbl">GST</span>
      <span class="bb-totals-val">₹<?= number_format($invoice->total_gst, 2) ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($invoice->cd_percent) && $invoice->cd_percent > 0): ?>
    <div class="bb-totals-row">
      <span class="bb-totals-lbl">CD (<?= $invoice->cd_percent ?>%)</span>
      <span style="font-weight:700;color:#c62828;">− ₹<?= number_format($invoice->cd_amount, 2) ?></span>
    </div>
    <?php endif; ?>
    <?php if (!empty($invoice->round_off) && $invoice->round_off != 0): ?>
    <div class="bb-totals-row">
      <span class="bb-totals-lbl">Round Off</span>
      <span><?= $invoice->round_off > 0 ? '+' : '−' ?> ₹<?= number_format(abs($invoice->round_off), 2) ?></span>
    </div>
    <?php endif; ?>

    <div class="bb-grand-line">
      <span class="bb-grand-label">Grand Total</span>
      <span class="bb-grand-amount">₹<?= number_format($invoice->total_amount, 2) ?></span>
    </div>
    <?php if (isset($invoice->wallet_amount_used) && $invoice->wallet_amount_used > 0): ?>
    <div class="bb-totals-row" style="background:#e0f7fa; border-top: 1px solid #b2ebf2;">
      <span style="color:#006064;font-weight:600;">👛 Wallet Applied</span>
      <span style="color:#006064;font-weight:700;">− ₹<?= number_format($invoice->wallet_amount_used, 2) ?></span>
    </div>
    <?php endif; ?>
    <?php if (isset($invoice->wallet_amount_credited) && $invoice->wallet_amount_credited > 0): ?>
    <div class="bb-totals-row" style="background:#e8f5e9; border-top: 1px solid #c8e6c9;">
      <span style="color:#2e7d32;font-weight:600;">👛 Wallet Credited</span>
      <span style="color:#2e7d32;font-weight:700;">₹<?= number_format($invoice->wallet_amount_credited, 2) ?></span>
    </div>
    <?php endif; ?>
    <div class="bb-paid-line">
      <span style="color:#2e7d32;font-weight:600;">✅ Amount Paid</span>
      <span style="color:#2e7d32;font-weight:700;">₹<?= number_format($invoice->paid_amount, 2) ?></span>
    </div>
    <?php if ($invoice->due_amount > 0): ?>
    <div class="bb-due-line">
      <span style="color:#c62828;font-weight:700;">⚠️ Balance Due</span>
      <span style="color:#c62828;font-size:1rem;font-weight:800;">₹<?= number_format($invoice->due_amount, 2) ?></span>
    </div>
    <?php else: ?>
    <div class="bb-paid-line" style="background:#e8f5e9; border-top: 1px solid #c8e6c9;">
      <span style="color:#2e7d32;font-weight:700;">⚠️ Balance Due</span>
      <span style="color:#2e7d32;font-size:1rem;font-weight:800;">₹0.00</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Actions -->
  <div style="display:flex;gap:10px;margin-bottom:20px;">
    <a href="<?= base_url('portal/invoices') ?>" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;background:var(--gold-light);color:var(--primary);border-radius:12px;padding:12px;font-size:0.82rem;font-weight:700;text-decoration:none;border:1.5px solid var(--gold);">
      📄 All Invoices
    </a>
    <a href="<?= base_url('portal/products') ?>" style="flex:1;display:flex;align-items:center;justify-content:center;gap:6px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;border-radius:12px;padding:12px;font-size:0.82rem;font-weight:700;text-decoration:none;">
      🛍️ Shop More
    </a>
  </div>

</div>
