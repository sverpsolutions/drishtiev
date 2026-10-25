<?php // FILE: application/views/marketing/campaign_runner.php
$badge_map = ['running' => 'success', 'paused' => 'warning', 'completed' => 'primary'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> Sending: <?= htmlspecialchars($campaign->name) ?>
            <span id="statusBadge" class="badge badge-<?= $badge_map[$campaign->status] ?? 'secondary' ?> text-capitalize align-middle"><?= $campaign->status ?></span>
        </h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / <a href="<?= base_url('marketing/campaigns') ?>">Campaigns</a> / <a href="<?= base_url('marketing/campaign_view/' . $campaign->id) ?>"><?= htmlspecialchars($campaign->name) ?></a> / Send</small>
    </div>
    <a href="<?= base_url('marketing/campaign_view/' . $campaign->id) ?>" class="btn btn-outline-dark btn-sm">Back to Campaign</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="progress mb-2" style="height:22px;">
            <div id="progressBar" class="progress-bar bg-success" role="progressbar" style="width:0%;">0%</div>
        </div>
        <div class="row text-center small">
            <div class="col"><div class="h5 mb-0 font-weight-bold" id="cSent"><?= (int)$recipient_stats['sent'] ?></div>Sent</div>
            <div class="col"><div class="h5 mb-0 font-weight-bold text-success" id="cDelivered"><?= (int)$recipient_stats['delivered'] ?></div>Delivered</div>
            <div class="col"><div class="h5 mb-0 font-weight-bold text-success" id="cRead"><?= (int)$recipient_stats['read'] ?></div>Read</div>
            <div class="col"><div class="h5 mb-0 font-weight-bold text-danger" id="cFailed"><?= (int)$recipient_stats['failed'] ?></div>Failed</div>
            <div class="col"><div class="h5 mb-0 font-weight-bold text-secondary" id="cPending"><?= (int)$recipient_stats['pending'] ?></div>Pending</div>
        </div>
    </div>
</div>

<div class="mb-3">
    <button id="btnPause" class="btn btn-warning btn-sm" <?= $campaign->status !== 'running' ? 'disabled' : '' ?>><i class="fas fa-pause mr-1"></i> Pause</button>
    <button id="btnResume" class="btn btn-success btn-sm" <?= $campaign->status !== 'paused' ? 'disabled' : '' ?>><i class="fas fa-play mr-1"></i> Resume</button>
    <button id="btnCancel" class="btn btn-outline-danger btn-sm" <?= !in_array($campaign->status, ['running', 'paused'], true) ? 'disabled' : '' ?>><i class="fas fa-stop mr-1"></i> Cancel Campaign</button>
</div>

<div class="alert alert-info small" id="infoBox" style="display:none;"></div>

<div class="card border-0 shadow-sm">
    <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Send Log</div>
    <div class="card-body p-0">
        <div id="sendLog" style="max-height:360px;overflow-y:auto;font-size:.82rem;" class="p-2"></div>
    </div>
</div>

<script>
(function() {
    var campaignId = <?= (int)$campaign->id ?>;
    var isRunning  = <?= $campaign->status === 'running' ? 'true' : 'false' ?>;
    var isDone     = <?= $campaign->status === 'completed' ? 'true' : 'false' ?>;

    function setCounts(stats) {
        document.getElementById('cSent').textContent      = stats.sent;
        document.getElementById('cDelivered').textContent = stats.delivered;
        document.getElementById('cRead').textContent       = stats.read;
        document.getElementById('cFailed').textContent    = stats.failed;
        document.getElementById('cPending').textContent   = stats.pending + stats.queued + stats.sending;
        var processed = stats.total - (stats.pending + stats.queued + stats.sending);
        var pct = stats.total > 0 ? Math.round(processed / stats.total * 100) : 0;
        var bar = document.getElementById('progressBar');
        bar.style.width = pct + '%';
        bar.textContent = pct + '%';
    }

    function setStatus(status) {
        var badge = document.getElementById('statusBadge');
        var map = {running: 'success', paused: 'warning', completed: 'primary', cancelled: 'danger'};
        badge.className = 'badge badge-' + (map[status] || 'secondary') + ' text-capitalize align-middle';
        badge.textContent = status;
        document.getElementById('btnPause').disabled  = status !== 'running';
        document.getElementById('btnResume').disabled = status !== 'paused';
        document.getElementById('btnCancel').disabled = !(status === 'running' || status === 'paused');
    }

    function logLine(text, cls) {
        var log = document.getElementById('sendLog');
        var div = document.createElement('div');
        div.className = cls || '';
        div.textContent = text;
        log.appendChild(div);
        log.scrollTop = log.scrollHeight;
    }

    function showInfo(msg) {
        var box = document.getElementById('infoBox');
        box.style.display = 'block';
        box.textContent = msg;
    }

    function runBatch() {
        $.post('<?= base_url('marketing/process_batch_ajax/') ?>' + campaignId, {}, function(resp) {
            if (!resp.ok) {
                showInfo(resp.error || 'Sending stopped.');
                setStatus(resp.campaign_status || 'paused');
                isRunning = false;
                return;
            }
            (resp.results || []).forEach(function(r) {
                if (r.status === 'sent') {
                    logLine('✓ Sent to ' + r.name + ' (' + r.phone + ')', 'text-success');
                } else {
                    logLine('✗ Failed for ' + r.name + ' (' + r.phone + '): ' + (r.error || 'unknown error'), 'text-danger');
                }
            });
            if (resp.stats) setCounts(resp.stats);
            setStatus(resp.campaign_status);

            if (resp.done) {
                isRunning = false;
                showInfo('Campaign finished — all recipients processed.');
                return;
            }
            if (resp.campaign_status !== 'running') { isRunning = false; return; }
            setTimeout(runBatch, 2000 + Math.floor(Math.random() * 1500));
        }, 'json').fail(function() {
            showInfo('A network error interrupted sending. Click Resume to continue.');
            isRunning = false;
        });
    }

    document.getElementById('btnPause').addEventListener('click', function() {
        isRunning = false;
        $.post('<?= base_url('marketing/campaign_pause/') ?>' + campaignId, {}, function() {
            setStatus('paused');
        }, 'json');
    });
    document.getElementById('btnResume').addEventListener('click', function() {
        window.location.href = '<?= base_url('marketing/campaign_resume/') ?>' + campaignId;
    });
    document.getElementById('btnCancel').addEventListener('click', function() {
        if (!confirm('Cancel this campaign? Any remaining pending recipients will not be sent.')) return;
        window.location.href = '<?= base_url('marketing/campaign_cancel/') ?>' + campaignId;
    });

    // MY_Controller::render() outputs header, then this view, then footer —
    // jQuery only loads in footer.php, so it isn't defined yet at this point in
    // the page. Defer the auto-start (and everything using $.post) until the
    // window has fully loaded so jQuery is guaranteed to exist.
    function boot() {
        if (isRunning) {
            runBatch();
        } else if (isDone) {
            showInfo('Campaign finished — all recipients processed.');
        } else {
            showInfo('Campaign is paused. Click Resume to continue sending.');
        }
    }
    if (window.jQuery) { boot(); } else { window.addEventListener('load', boot); }
})();
</script>
