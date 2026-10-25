<?php
$page_title = 'Sales Report';
$_active    = 'sales';
function inrs($n){return '₹'.number_format($n,0);}
$mode_colors=['cash'=>'badge-green','upi'=>'badge-blue','card'=>'badge-purple','split'=>'badge-amber','credit'=>'badge-red'];
?>
<?php $this->load->view('owner_app/_shell_open'); ?>
<div class="topbar">
    <div style="flex:1;">
        <div class="topbar-title">📈 Sales Report</div>
        <div class="topbar-sub"><?= date('d M Y',strtotime($from)) ?> – <?= date('d M Y',strtotime($to)) ?></div>
    </div>
</div>

<div class="content">

<!-- Date Filter -->
<form method="get" class="date-filter">
    <div style="flex:1;"><label>From</label><input type="date" name="from" value="<?= $from ?>"></div>
    <div style="flex:1;"><label>To</label><input type="date" name="to" value="<?= $to ?>"></div>
    <button type="submit">Go</button>
</form>

<!-- Summary cards -->
<div class="stat-grid">
    <div class="stat-card green">
        <div class="stat-label">Total Sales</div>
        <div class="stat-value big"><?= inrs($summary->gross ?? 0) ?></div>
        <div class="stat-sub"><?= (int)($summary->bills??0) ?> bills</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Collected</div>
        <div class="stat-value"><?= inrs($summary->collected ?? 0) ?></div>
        <div class="stat-sub">Due: <?= inrs($summary->due ?? 0) ?></div>
    </div>
    <div class="stat-card amber">
        <div class="stat-label">Total GST</div>
        <div class="stat-value"><?= inrs($summary->gst ?? 0) ?></div>
    </div>
    <div class="stat-card purple">
        <div class="stat-label">Discount</div>
        <div class="stat-value"><?= inrs($summary->disc ?? 0) ?></div>
    </div>
</div>

<!-- Payment mode -->
<div class="card">
    <div class="card-title">💳 By Payment Mode</div>
    <?php foreach ($by_mode as $m): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid #0f172a;">
        <div>
            <span class="badge <?= $mode_colors[strtolower($m->payment_mode)] ?? 'badge-gray' ?>"><?= strtoupper($m->payment_mode) ?></span>
            <span style="font-size:11px;color:#64748b;margin-left:6px;"><?= $m->bills ?> bills</span>
        </div>
        <div class="amt">₹<?= number_format($m->amount,0) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Top products -->
<?php if (!empty($top_products)): ?>
<div class="card">
    <div class="card-title">🏆 Top 5 Products</div>
    <?php foreach ($top_products as $i=>$p): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #0f172a;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="width:22px;height:22px;background:#334155;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#94a3b8;"><?= $i+1 ?></span>
            <div>
                <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($p->name) ?></div>
                <div style="font-size:10px;color:#64748b;">Qty: <?= number_format($p->qty,0) ?></div>
            </div>
        </div>
        <div class="amt">₹<?= number_format($p->revenue,0) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Daily breakdown -->
<div class="sec-head">Daily Breakdown</div>
<?php if (empty($daily)): ?>
<div style="text-align:center;color:#475569;padding:20px;">No data for selected period.</div>
<?php else: ?>
<table class="mob-table">
    <thead><tr><th>Date</th><th>Bills</th><th class="amt">Amount</th></tr></thead>
    <tbody>
    <?php foreach ($daily as $d): ?>
    <tr>
        <td><?= date('d M',strtotime($d->dt)) ?></td>
        <td><span class="badge badge-gray"><?= $d->bills ?></span></td>
        <td class="amt">₹<?= number_format($d->amount,0) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

</div>

<nav class="bottom-nav">
    <a href="<?= base_url('owner/dashboard') ?>" class="nav-item <?= $_active==='dashboard'?'active':'' ?>"><span class="icon">🏠</span>Dashboard</a>
    <a href="<?= base_url('owner/sales') ?>" class="nav-item <?= $_active==='sales'?'active':'' ?>"><span class="icon">📈</span>Sales</a>
    <a href="<?= base_url('owner/purchases') ?>" class="nav-item <?= $_active==='purchases'?'active':'' ?>"><span class="icon">📦</span>Purchase</a>
    <a href="<?= base_url('owner/transfers') ?>" class="nav-item <?= $_active==='transfers'?'active':'' ?>"><span class="icon">🔁</span>Transfers</a>
</nav>
