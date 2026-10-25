<?php defined('BASEPATH') OR exit('No direct script access allowed');
$is_otp = $r->category === 'otp';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-cog text-primary mr-1"></i> <?= htmlspecialchars($r->label) ?></h5>
        <small class="text-muted"><code><?= htmlspecialchars($r->purpose) ?></code></small>
    </div>
    <a href="<?= base_url('smsmanager') ?>" class="btn btn-outline-secondary btn-sm">&larr; All SMS Settings</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
<?php endif; ?>

<?= form_open('smsmanager/edit/' . $r->id) ?>
<div class="row">
  <div class="col-md-7 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-2 font-weight-bold">Gateway</div>
      <div class="card-body">

        <div class="form-group">
            <label class="font-weight-bold">API URL <span class="text-danger">*</span></label>
            <textarea name="api_url" class="form-control" rows="3"
                      style="font-family:monospace;font-size:.82rem;"
                      placeholder="https://sms-provider.com/api/send?apikey=XXXX&sender={sender}&number={mobile}&message={message_url}&template={template}"><?= htmlspecialchars($r->api_url ?? '') ?></textarea>
            <small class="text-muted">
                Paste the URL your provider gave you and swap the variable parts for the
                placeholders below. Use <code>{message_url}</code> in a GET URL &mdash; it is URL-encoded;
                <code>{message}</code> is the raw text for a POST body.
            </small>
        </div>

        <div class="form-row">
            <div class="form-group col-md-4">
                <label class="font-weight-bold">Method</label>
                <select name="http_method" id="httpMethod" class="form-control">
                    <option value="GET"  <?= $r->http_method === 'GET'  ? 'selected' : '' ?>>GET</option>
                    <option value="POST" <?= $r->http_method === 'POST' ? 'selected' : '' ?>>POST</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label class="font-weight-bold">Sender ID</label>
                <input type="text" name="sender_id" class="form-control" maxlength="20"
                       value="<?= htmlspecialchars($r->sender_id ?? '') ?>" placeholder="e.g. NCGSTR">
            </div>
            <div class="form-group col-md-4">
                <label class="font-weight-bold">DLT Template ID</label>
                <input type="text" name="dlt_template_id" class="form-control" maxlength="40"
                       value="<?= htmlspecialchars($r->dlt_template_id ?? '') ?>">
            </div>
        </div>

        <div id="postOnly" style="display:<?= $r->http_method === 'POST' ? '' : 'none' ?>;">
            <div class="form-group">
                <label class="font-weight-bold">POST Body</label>
                <textarea name="post_body" class="form-control" rows="3" style="font-family:monospace;font-size:.82rem;"
                          placeholder='{"to":"{mobile}","text":"{message}","sender":"{sender}"}'><?= htmlspecialchars($r->post_body ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="font-weight-bold">Content-Type</label>
                <input type="text" name="content_type" class="form-control"
                       value="<?= htmlspecialchars($r->content_type ?? 'application/x-www-form-urlencoded') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="font-weight-bold">Extra Headers <small class="text-muted">(one per line)</small></label>
            <textarea name="headers" class="form-control" rows="2" style="font-family:monospace;font-size:.82rem;"
                      placeholder="Authorization: Bearer xxxxx"><?= htmlspecialchars($r->headers ?? '') ?></textarea>
        </div>

        <div class="form-group mb-0">
            <label class="font-weight-bold">Success Text in Response <small class="text-muted">(optional)</small></label>
            <input type="text" name="success_match" class="form-control" maxlength="120"
                   value="<?= htmlspecialchars($r->success_match ?? '') ?>" placeholder='e.g. "success" or "OK"'>
            <small class="text-muted">
                Some gateways answer HTTP 200 even when they reject a message. If you put the
                provider's success word here, anything else is logged as <strong>failed</strong>.
            </small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-5 mb-3">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white py-2 font-weight-bold">Message</div>
      <div class="card-body">
        <div class="form-group">
            <label class="font-weight-bold">Template Text</label>
            <textarea name="message" class="form-control" rows="4" id="msgBox"
                      style="font-size:.87rem;"><?= htmlspecialchars($r->message ?? '') ?></textarea>
            <small class="text-muted">Must match your DLT-approved wording exactly.
                <span id="msgLen" class="font-weight-bold"></span></small>
        </div>
        <div style="font-size:.78rem;">
            <div class="text-muted mb-1">Placeholders you can use:</div>
            <?php foreach (['{name}','{company}','{mobile}','{otp}','{points}','{amount}',
                            '{reference}','{balance}','{location}','{status}','{validity}',
                            '{date}','{time}','{sender}','{template}'] as $p): ?>
                <code class="mr-1" style="cursor:pointer;" onclick="insertPh('<?= $p ?>')"><?= $p ?></code>
            <?php endforeach; ?>
            <div class="text-muted mt-1">Click one to insert it.</div>
        </div>
      </div>
    </div>

    <?php if ($is_otp): ?>
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white py-2 font-weight-bold">OTP Rules</div>
      <div class="card-body">
        <div class="form-row">
            <div class="form-group col-4">
                <label class="font-weight-bold">Digits</label>
                <input type="number" name="otp_length" class="form-control" min="4" max="8" value="<?= (int)$r->otp_length ?>">
            </div>
            <div class="form-group col-4">
                <label class="font-weight-bold">Valid (min)</label>
                <input type="number" name="otp_validity_min" class="form-control" min="1" value="<?= (int)$r->otp_validity_min ?>">
            </div>
            <div class="form-group col-4">
                <label class="font-weight-bold">Attempts</label>
                <input type="number" name="max_attempts" class="form-control" min="1" max="10" value="<?= (int)$r->max_attempts ?>">
            </div>
        </div>
        <div class="custom-control custom-switch mb-0">
            <input type="checkbox" class="custom-control-input" id="is_required" name="is_required" value="1"
                   <?= (int)$r->is_required === 1 ? 'checked' : '' ?>>
            <label class="custom-control-label font-weight-bold text-danger" for="is_required">
                Make this OTP mandatory
            </label>
            <small class="text-muted d-block">
                The action is <strong>blocked</strong> until the OTP is verified. Leave off while
                testing &mdash; if the gateway is down, staff cannot complete the action at all.
            </small>
        </div>
      </div>
    </div>
    <?php else: ?>
        <input type="hidden" name="otp_length" value="<?= (int)$r->otp_length ?>">
        <input type="hidden" name="otp_validity_min" value="<?= (int)$r->otp_validity_min ?>">
        <input type="hidden" name="max_attempts" value="<?= (int)$r->max_attempts ?>">
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                   <?= (int)$r->is_active === 1 ? 'checked' : '' ?>>
            <label class="custom-control-label font-weight-bold" for="is_active">Active</label>
            <small class="text-muted d-block">Nothing is sent for this purpose while this is off.</small>
        </div>
      </div>
    </div>
  </div>
</div>

<button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Settings</button>
<a href="<?= base_url('smsmanager') ?>" class="btn btn-outline-secondary ml-1">Cancel</a>
<?= form_close() ?>

<script>
document.getElementById('httpMethod').addEventListener('change', function () {
    document.getElementById('postOnly').style.display = this.value === 'POST' ? '' : 'none';
});
function insertPh(p) {
    var b = document.getElementById('msgBox');
    var s = b.selectionStart || b.value.length;
    b.value = b.value.slice(0, s) + p + b.value.slice(b.selectionEnd || s);
    b.focus(); b.selectionStart = b.selectionEnd = s + p.length;
    countMsg();
}
function countMsg() {
    var v = document.getElementById('msgBox').value || '';
    // One SMS is 160 GSM-7 characters; longer messages are billed as multiples.
    var parts = v.length === 0 ? 0 : Math.ceil(v.length / 160);
    document.getElementById('msgLen').textContent =
        v.length + ' chars · ' + parts + ' SMS' + (parts > 1 ? ' (billed as ' + parts + ')' : '');
}
document.getElementById('msgBox').addEventListener('input', countMsg);
countMsg();
</script>
