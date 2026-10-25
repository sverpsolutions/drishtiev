<?php
$page_title = 'Purchase Report';
$_active    = 'purchases';
function inrp($n){return '₹'.number_format($n,0);}
?>
<?php $this->load->view('owner_app/_shell_open'); ?>
<div class="topbar">
    <div style="flex:1;">
        <div class="topbar-title">📦 Purchase Report</div>
        <div class="topbar-sub"><?= date('d M Y',strtotime($from)) ?> – <?= date('d M Y',strtotime($to)) ?></div>
    </div>
</div>

<div class="content">

<form method="get" class="date-filter">
    <div style="flex:1;"><label>From</label><input type="date" name="from" value="<?= $from ?>"></div>
    <div style="flex:1;"><label>To</label><input type="date" name="to" value="<?= $to ?>"></div>
    <button type="submit">Go</button>
</form>

<div class="stat-grid">
    <div class="stat-card amber">
        <div class="stat-label">Purchase Value</div>
        <div class="stat-value big"><?= inrp($summary->gross ?? 0) ?></div>
        <div class="stat-sub"><?= (int)($summary->orders??0) ?> purchases</div>
    </div>
    <div class="stat-card <?= ($summary->due ?? 0) > 0 ? 'red' : 'green' ?>">
        <div class="stat-label">Amount Due to Suppliers</div>
        <div class="stat-value"><?= inrp($summary->due ?? 0) ?></div>
        <div class="stat-sub">outstanding</div>
    </div>
</div>

<?php if (!empty($by_supplier)): ?>
<div class="card">
    <div class="card-title">🏭 By Supplier</div>
    <?php foreach ($by_supplier as $s): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid #0f172a;">
        <div>
            <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($s->supplier) ?></div>
            <div style="font-size:10px;color:#64748b;"><?= $s->orders ?> orders</div>
        </div>
        <div class="amt">₹<?= number_format($s->amount,0) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="sec-head">Daily Breakdown</div>
<?php if (empty($daily)): ?>
<div style="text-align:center;color:#475569;padding:20px;">No data for selected period.</div>
<?php else: ?>
<table class="mob-table">
    <thead><tr><th>Date</th><th>Orders</th><th class="amt">Amount</th></tr></thead>
    <tbody>
    <?php foreach ($daily as $d): ?>
    <tr>
        <td><?= date('d M',strtotime($d->dt)) ?></td>
        <td><span class="badge badge-amber"><?= $d->orders ?></span></td>
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
