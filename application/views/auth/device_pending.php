<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Device Approval Required</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #1a1a2e; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
.card { background: #fff; border-radius: 16px; padding: 40px 36px; max-width: 440px; width: 90%; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
.icon-wrap { width: 90px; height: 90px; border-radius: 50%; background: #fff3cd; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; border: 3px solid #ffc107; animation: pulse 2s infinite; }
.icon-wrap svg { width: 44px; height: 44px; color: #e67e00; }
@keyframes pulse { 0%,100%{transform:scale(1);} 50%{transform:scale(1.07);} }
h2 { color: #1a1a2e; font-size: 1.4rem; margin-bottom: 8px; }
p.sub { color: #666; font-size: .9rem; margin-bottom: 24px; line-height: 1.5; }
.device-info { background: #f8f9fa; border-radius: 10px; padding: 16px; text-align: left; margin-bottom: 24px; font-size: .85rem; }
.device-info table { width: 100%; border-collapse: collapse; }
.device-info td { padding: 4px 6px; color: #444; }
.device-info td:first-child { font-weight: 600; color: #222; width: 40%; }
.status-bar { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 16px; border-radius: 10px; background: #fff3cd; border: 1px solid #ffc107; margin-bottom: 20px; font-weight: 600; font-size: .9rem; color: #7d4a00; }
.dot { width: 10px; height: 10px; border-radius: 50%; background: #ffc107; animation: blink 1.2s infinite; }
@keyframes blink { 0%,100%{opacity:1;} 50%{opacity:.2;} }
.approved { background: #d4edda; border-color: #28a745; color: #155724; }
.approved .dot { background: #28a745; animation: none; }
.rejected { background: #f8d7da; border-color: #dc3545; color: #721c24; }
.rejected .dot { background: #dc3545; animation: none; }
.btn-back { display: inline-block; margin-top: 10px; color: #666; font-size: .85rem; text-decoration: none; }
.btn-back:hover { color: #333; }
.timer { font-size: .78rem; color: #999; margin-top: 6px; }
</style>
</head>
<body>
<div class="card">
    <div class="icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>
            <path d="M12 8v4M12 12l2 2"/>
        </svg>
    </div>
    <h2>New Device Detected</h2>
    <p class="sub">This computer is not authorized to access the system.<br>Your request has been sent to the administrator.</p>

    <div class="device-info">
        <table>
            <tr><td>Browser</td><td><?= htmlspecialchars($device->browser ?? '—') ?></td></tr>
            <tr><td>Operating System</td><td><?= htmlspecialchars($device->os ?? '—') ?></td></tr>
            <tr><td>IP Address</td><td><?= htmlspecialchars($device->ip_address ?? '—') ?></td></tr>
            <tr><td>Requested</td><td><?= date('d M Y, h:i A', strtotime($device->requested_at)) ?></td></tr>
        </table>
    </div>

    <div class="status-bar" id="statusBar">
        <div class="dot" id="statusDot"></div>
        <span id="statusText">Waiting for admin approval...</span>
    </div>
    <div class="timer" id="timerText">Checking status every 5 seconds</div>

    <a href="<?= base_url('login') ?>" class="btn-back">← Back to Login</a>
</div>

<script>
var checkUrl = '<?= base_url('auth/check_device_status') ?>';
var bar = document.getElementById('statusBar');
var dot = document.getElementById('statusDot');
var txt = document.getElementById('statusText');
var tmr = document.getElementById('timerText');
var countdown = 5;

function tick() {
    countdown--;
    tmr.textContent = 'Checking in ' + countdown + 's...';
    if (countdown <= 0) {
        countdown = 5;
        checkStatus();
    }
}

function checkStatus() {
    fetch(checkUrl, {credentials:'same-origin'})
    .then(r => r.json())
    .then(d => {
        if (d.status === 'approved') {
            bar.className = 'status-bar approved';
            dot.style.animation = 'none';
            txt.textContent = '✓ Approved! Logging you in...';
            tmr.textContent = '';
            clearInterval(timer);
            // Reload this page (not a direct jump to dashboard) so Auth::device_pending()
            // runs _complete_login() and picks the correct destination (dashboard vs.
            // the location picker) — jumping straight to dashboard would hit it with no
            // session yet and bounce back to login.
            setTimeout(() => { window.location.href = '<?= base_url('auth/device_pending') ?>'; }, 1500);
        } else if (d.status === 'rejected') {
            bar.className = 'status-bar rejected';
            txt.textContent = '✗ Access Rejected by Admin';
            tmr.textContent = 'Contact your administrator.';
            clearInterval(timer);
            clearInterval(ticker);
        }
    }).catch(() => {});
}

var ticker = setInterval(tick, 1000);
var timer  = setInterval(checkStatus, 5000);
</script>
</body>
</html>
