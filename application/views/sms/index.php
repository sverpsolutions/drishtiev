<?php defined('BASEPATH') OR exit('No direct script access allowed');
$cat = ['otp' => ['OTP / Verification', 'shield-alt'],
        'transactional' => ['Transactional Alerts', 'bell'],
        'promotional' => ['Promotional', 'bullhorn']];
$last = null;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-sms text-primary mr-1"></i> SMS Setup</h5>
        <small class="text-muted">Each purpose has its own gateway URL, sender ID and DLT template</small>
    </div>
    <a href="<?= base_url('smsmanager/logs') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-list mr-1"></i> Send Log</a>
</div>

<div class="alert alert-info py-2 px-3" style="font-size:.86rem;">
    <i class="fas fa-info-circle mr-1"></i>
    Indian TRAI/DLT rules require a <strong>separate registered template per message type</strong>, so each row
    below carries its own URL and template ID. Nothing is sent until a purpose is switched
    <strong>Active</strong>. Use <strong>Test</strong> to confirm a gateway before turning it on.
</div>

<?php foreach ($rows as $r): ?>
    <?php if ($r->category !== $last): $last = $r->category; $c = $cat[$r->category] ?? ['Other', 'tag']; ?>
    <h6 class="text-uppercase text-muted mt-3 mb-2" style="font-size:.75rem;letter-spacing:.06em;">
        <i class="fas fa-<?= $c[1] ?> mr-1"></i> <?= $c[0] ?>
    </h6>
    <?php endif; ?>
    <?php $s = $stats[$r->purpose] ?? null; $configured = trim((string)$r->api_url) !== ''; ?>
    <div class="card border-0 shadow-sm mb-2">
      <div class="card-body py-2">
        <div class="d-flex align-items-center flex-wrap" style="gap:10px;">
            <div style="min-width:250px;">
                <div class="font-weight-bold"><?= htmlspecialchars($r->label) ?></div>
                <code style="font-size:.72rem;"><?= htmlspecialchars($r->purpose) ?></code>
            </div>
            <div>
                <?php if ((int)$r->is_active === 1): ?>
                    <span class="badge badge-success">ACTIVE</span>
                <?php elseif ($configured): ?>
                    <span class="badge badge-warning">CONFIGURED &mdash; OFF</span>
                <?php else: ?>
                    <span class="badge badge-light border">NOT SET UP</span>
                <?php endif; ?>
                <?php if ((int)$r->is_required === 1): ?>
                    <span class="badge badge-danger" title="The action is blocked until the OTP is verified">MANDATORY</span>
                <?php endif; ?>
            </div>
            <div class="text-muted" style="font-size:.78rem;min-width:190px;">
                <?php if ($s && $s->total > 0): ?>
                    <?= (int)$s->sent ?> sent &middot;
                    <span class="<?= $s->failed > 0 ? 'text-danger font-weight-bold' : '' ?>"><?= (int)$s->failed ?> failed</span>
                    <?php if ($s->last_at): ?><br>last <?= date('d-M H:i', strtotime($s->last_at)) ?><?php endif; ?>
                <?php else: ?>
                    <span class="text-muted">no messages yet</span>
                <?php endif; ?>
            </div>
            <div class="ml-auto d-flex" style="gap:6px;">
                <?php if ($is_admin): ?>
                <button type="button" class="btn btn-outline-success btn-sm"
                        onclick="openSmsTest('<?= htmlspecialchars($r->purpose) ?>', '<?= htmlspecialchars(addslashes($r->label)) ?>')">
                    <i class="fas fa-paper-plane mr-1"></i> Test
                </button>
                <a href="<?= base_url('smsmanager/edit/' . $r->id) ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-cog mr-1"></i> Configure
                </a>
                <?php endif; ?>
                <a href="<?= base_url('smsmanager/logs?purpose=' . urlencode($r->purpose)) ?>" class="btn btn-outline-secondary btn-sm">Log</a>
            </div>
        </div>
      </div>
    </div>
<?php endforeach; ?>

<div class="modal fade" id="smsTestModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header py-2">
      <h5 class="modal-title"><i class="fas fa-paper-plane mr-1 text-success"></i> Send Test SMS</h5>
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
    </div>
    <div class="modal-body">
      <p class="mb-2">Purpose: <strong id="stLabel"></strong></p>
      <input type="hidden" id="stPurpose">
      <div class="form-group mb-2">
        <label class="font-weight-bold">Mobile Number</label>
        <input type="text" id="stMobile" class="form-control" placeholder="10-digit mobile" autocomplete="off">
        <small class="text-muted">Sample values fill the placeholders, and the OTP shows as 000000 rather than a working code.</small>
      </div>
      <div id="stResult"></div>
    </div>
    <div class="modal-footer py-2">
      <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
      <button type="button" class="btn btn-success btn-sm" id="stSendBtn" onclick="doSmsTest()">Send Test</button>
    </div>
  </div></div>
</div>

<script>
function openSmsTest(p, label) {
    document.getElementById('stPurpose').value = p;
    document.getElementById('stLabel').textContent = label;
    document.getElementById('stResult').innerHTML = '';
    $('#smsTestModal').modal('show');
}
function doSmsTest() {
    var btn = document.getElementById('stSendBtn'), out = document.getElementById('stResult');
    var fd = new FormData();
    fd.append('purpose', document.getElementById('stPurpose').value);
    fd.append('mobile', document.getElementById('stMobile').value);
    btn.disabled = true; btn.textContent = 'Sending...';
    out.innerHTML = '<div class="text-muted">Calling the gateway...</div>';
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
        });
    }
    fetch('<?= base_url('smsmanager/test_send') ?>', {
        method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (r) { return r.json(); })
    .then(function (res) {
        btn.disabled = false; btn.textContent = 'Send Test';
        out.innerHTML =
            '<div class="alert alert-' + (res.ok ? 'success' : 'danger') + ' py-2 px-3" style="font-size:.85rem;">'
          + '<strong>' + (res.ok ? 'Sent.' : 'Failed.') + '</strong> ' + esc(res.error || '') + '</div>'
          + '<div class="small text-muted">Message sent:</div>'
          + '<div class="border rounded p-2 mb-2" style="font-size:.83rem;background:#f8f9fa;">' + esc(res.message) + '</div>'
          + '<div class="small text-muted">Gateway response (HTTP ' + esc(res.http_code) + '):</div>'
          + '<pre class="border rounded p-2" style="font-size:.75rem;max-height:140px;overflow:auto;">' + esc(res.response) + '</pre>';
    })
    .catch(function () {
        btn.disabled = false; btn.textContent = 'Send Test';
        out.innerHTML = '<div class="alert alert-danger py-2 px-3">Could not reach the server.</div>';
    });
}
</script>
