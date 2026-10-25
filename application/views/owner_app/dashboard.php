<?php
$page_title = 'Dashboard';
$_active    = 'dashboard';
$co         = $_company;
$name       = $this->session->userdata('owner_app_name');
$initials   = strtoupper(substr($name, 0, 1));

$sales   = (float)($today_row->sales ?? 0);
$bills   = (int)($today_row->bills ?? 0);
$collected = (float)($today_row->collected ?? 0);
$due     = (float)($today_row->due ?? 0);
$m_sales = (float)($month_row->sales ?? 0);
$m_bills = (int)($month_row->bills ?? 0);

$cash  = (float)($mode_map['cash']->amount ?? 0);
$upi   = (float)($mode_map['upi']->amount  ?? 0);
$card  = (float)($mode_map['card']->amount  ?? 0);
$split = (float)($mode_map['split']->amount ?? 0);
$credit= (float)($mode_map['credit']->amount ?? 0);

// Build hourly bar chart
$hrs = array_fill(0, 24, 0);
foreach ($hourly as $h) $hrs[(int)$h->hr] = (float)$h->amt;
$open_hr = 8; $close_hr = 22;
$visible_hrs = array_slice($hrs, $open_hr, $close_hr - $open_hr, true);
$max_hr = max($visible_hrs) ?: 1;

function inr($n) { return '₹'.number_format($n,0); }
?>
<?php $this->load->view('owner_app/_shell_open'); ?>

<!-- TOP BAR -->
<div class="topbar">
    <div style="flex:1;">
        <div class="topbar-title">👑 <?= htmlspecialchars($co['company_name'] ?? 'Dashboard') ?></div>
        <div class="topbar-sub"><?= date('D, d M Y') ?> &bull; Hi <?= htmlspecialchars($name) ?></div>
    </div>
    <div class="topbar-avatar"><?= $initials ?></div>
</div>

<div class="content">

<!-- TODAY SALES HERO -->
<div class="stat-wide" style="background:linear-gradient(135deg,#1e3a5f,#1e293b);border:1px solid #1d4ed8;">
    <div class="card-title" style="color:#60a5fa;">📅 Today — <?= date('d M Y') ?></div>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;">
        <div>
            <div style="font-size:32px;font-weight:900;color:#fff;line-height:1;"><?= inr($sales) ?></div>
            <div style="font-size:12px;color:#93c5fd;margin-top:4px;"><?= $bills ?> bills &bull; Collected <?= inr($collected) ?></div>
        </div>
        <?php if ($due > 0): ?>
        <div style="text-align:right;">
            <div style="font-size:11px;color:#f87171;">Due</div>
            <div style="font-size:18px;font-weight:700;color:#f87171;"><?= inr($due) ?></div>
        </div>
        <?php endif; ?>
    </div>
    <!-- live dot -->
    <div style="margin-top:10px;display:flex;align-items:center;gap:6px;">
        <span id="liveDot" style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;animation:blink 1.2s infinite;"></span>
        <span id="liveText" style="font-size:11px;color:#64748b;">Live &bull; updated just now</span>
    </div>
</div>

<!-- PAYMENT MODE BREAKDOWN -->
<div class="card">
    <div class="card-title">💳 Payment Mode Breakdown</div>
    <div class="mode-row">
        <?php
        $mode_display = [
            'cash'   => ['💵','Cash',   '#4ade80', $cash],
            'upi'    => ['⚡','UPI',    '#60a5fa', $upi],
            'card'   => ['💳','Card',   '#c084fc', $card],
            'split'  => ['✂️','Split',  '#fbbf24', $split],
            'credit' => ['📋','Credit', '#f87171', $credit],
        ];
        foreach ($mode_display as $key => [$icon,$label,$color,$amt]):
            if ($amt <= 0) continue;
        ?>
        <div class="mode-chip">
            <div class="mc-label"><?= $icon ?> <?= $label ?></div>
            <div class="mc-val" style="color:<?= $color ?>;"><?= inr($amt) ?></div>
            <div class="mc-bills"><?= (int)($mode_map[$key]->bills ?? 0) ?> bills</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!empty($by_location) && count($by_location) > 1): ?>
<!-- OUTLET-WISE SALES -->
<div class="card">
    <div class="card-title">🏬 Outlet-wise Sales (Today)</div>
    <?php foreach ($by_location as $loc): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #1e293b;">
        <div>
            <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($loc->location_name ?? 'Unassigned') ?></div>
            <div style="font-size:11px;color:#64748b;"><?= (int)$loc->bills ?> bill<?= (int)$loc->bills == 1 ? '' : 's' ?></div>
        </div>
        <div style="font-weight:800;color:#4ade80;"><?= inr((float)$loc->amount) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- STATS GRID -->
<div class="stat-grid">
    <div class="stat-card green">
        <div class="stat-label">This Month Sales</div>
        <div class="stat-value"><?= inr($m_sales) ?></div>
        <div class="stat-sub"><?= $m_bills ?> bills</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Purchase (Today)</div>
        <div class="stat-value"><?= inr((float)($purchase_row->amount ?? 0)) ?></div>
        <div class="stat-sub"><?= (int)($purchase_row->count ?? 0) ?> orders</div>
    </div>
</div>

<!-- HOURLY CHART -->
<div class="card">
    <div class="card-title">📊 Hourly Sales (Today)</div>
    <div class="chart-wrap">
    <?php for ($h = $open_hr; $h < $close_hr; $h++):
        $v = $hrs[$h] ?? 0;
        $pct = $max_hr > 0 ? ($v / $max_hr * 100) : 0;
        $lbl = date('ha', mktime($h, 0, 0));
    ?>
    <div class="bar-col">
        <div class="bar" style="height:<?= max(2, round($pct * 0.65)) ?>px;" title="<?= $lbl ?>: ₹<?= number_format($v,0) ?>"></div>
        <div class="bar-lbl"><?= $lbl ?></div>
    </div>
    <?php endfor; ?>
    </div>
</div>

<!-- RECENT BILLS -->
<div class="sec-head">Recent Bills</div>
<?php foreach ($recent as $r): ?>
<div style="background:#1e293b;border-radius:10px;padding:12px 14px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <div style="font-weight:700;font-size:13px;"><?= htmlspecialchars($r->invoice_no) ?></div>
        <div style="font-size:11px;color:#64748b;margin-top:2px;"><?= htmlspecialchars($r->customer ?? 'Walk-in') ?> &bull; <?= date('h:i A', strtotime($r->created_at)) ?></div>
    </div>
    <div style="text-align:right;">
        <div style="font-weight:800;color:#4ade80;">₹<?= number_format($r->total_amount,0) ?></div>
        <div style="margin-top:2px;">
            <?php $mc = ['cash'=>'badge-green','upi'=>'badge-blue','card'=>'badge-purple','split'=>'badge-amber','credit'=>'badge-red']; ?>
            <span class="badge <?= $mc[$r->payment_mode] ?? 'badge-gray' ?>"><?= strtoupper($r->payment_mode) ?></span>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php if (empty($recent)): ?>
<div style="text-align:center;color:#475569;padding:24px;font-size:13px;">No bills today yet.</div>
<?php endif; ?>

<button class="logout-btn" onclick="if(confirm('Logout?')) location.href='<?= base_url('owner/logout') ?>'">⬅ Logout</button>

</div><!-- end content -->

<!-- BOTTOM NAV -->
<nav class="bottom-nav">
    <a href="<?= base_url('owner/dashboard') ?>" class="nav-item <?= $_active==='dashboard'?'active':'' ?>">
        <span class="icon">🏠</span>Dashboard
    </a>
    <a href="<?= base_url('owner/sales') ?>" class="nav-item <?= $_active==='sales'?'active':'' ?>">
        <span class="icon">📈</span>Sales
    </a>
    <a href="<?= base_url('owner/purchases') ?>" class="nav-item <?= $_active==='purchases'?'active':'' ?>">
        <span class="icon">📦</span>Purchase
    </a>
    <a href="<?= base_url('owner/transfers') ?>" class="nav-item <?= $_active==='transfers'?'active':'' ?>">
        <span class="icon">🔁</span>Transfers
    </a>
</nav>

<style>
@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}
</style>
<script>
// Auto-refresh live stats every 60s
function refreshLive() {
    fetch('<?= base_url('owner/live_stats') ?>')
        .then(r => r.json())
        .then(d => {
            document.getElementById('liveText').textContent = 'Live • updated at ' + d.time;
        }).catch(()=>{});
}
setInterval(refreshLive, 60000);
</script>
