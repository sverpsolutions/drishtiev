<?php
$page_title = 'Stock Transfer Report';
$_active    = 'transfers';
function inrt($n){return '₹'.number_format($n,0);}
?>
<?php $this->load->view('owner_app/_shell_open'); ?>
<div class="topbar">
    <div style="flex:1;">
        <div class="topbar-title">🔁 Stock Transfer Report</div>
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
        <div class="stat-label">Transfer Value</div>
        <div class="stat-value big"><?= inrt($summary->value ?? 0) ?></div>
        <div class="stat-sub"><?= (int)($summary->transfers ?? 0) ?> transfers</div>
    </div>
    <div class="stat-card <?= (int)($pending->cnt ?? 0) > 0 ? 'red' : 'green' ?>">
        <div class="stat-label">Pending Approval</div>
        <div class="stat-value"><?= (int)($pending->cnt ?? 0) ?></div>
        <div class="stat-sub">awaiting action</div>
    </div>
</div>

<?php if (!empty($by_outlet)): ?>
<div class="card">
    <div class="card-title">🏬 Transfer Out by Outlet</div>
    <?php foreach ($by_outlet as $o): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid #0f172a;">
        <div>
            <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($o->outlet ?? 'Unassigned') ?></div>
            <div style="font-size:10px;color:#64748b;"><?= $o->transfers ?> transfer<?= $o->transfers == 1 ? '' : 's' ?></div>
        </div>
        <div class="amt">₹<?= number_format($o->value,0) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="sec-head">Daily Breakdown</div>
<?php if (empty($daily)): ?>
<div style="text-align:center;color:#475569;padding:20px;">No data for selected period.</div>
<?php else: ?>
<table class="mob-table">
    <thead><tr><th>Date</th><th>Transfers</th><th class="amt">Value</th></tr></thead>
    <tbody>
    <?php foreach ($daily as $d): ?>
    <tr>
        <td><?= date('d M',strtotime($d->dt)) ?></td>
        <td><span class="badge badge-amber"><?= $d->transfers ?></span></td>
        <td class="amt">₹<?= number_format($d->value,0) ?></td>
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
