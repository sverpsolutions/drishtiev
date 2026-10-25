<?php
// FILE: application/views/marketing/campaign_manual_send.php
// Manual click-to-chat sender: one recipient at a time, opens WhatsApp via
// wa.me with the message pre-filled, a human presses Send in WhatsApp itself.
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> Send Manually: <?= htmlspecialchars($campaign->name) ?></h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / <a href="<?= base_url('marketing/campaigns') ?>">Campaigns</a> / <a href="<?= base_url('marketing/campaign_view/'.$campaign->id) ?>"><?= htmlspecialchars($campaign->name) ?></a> / Manual Send</small>
    </div>
    <a href="<?= base_url('marketing/campaign_view/'.$campaign->id) ?>" class="btn btn-outline-dark btn-sm">Back to Campaign</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="alert alert-light border py-2 px-3 mb-3" style="font-size:.83rem;">
    <i class="fas fa-info-circle mr-1"></i> No API needed — click "Open WhatsApp", the app/WhatsApp Web opens with the message ready, you press Send there yourself. Then come back and mark it sent to move to the next customer.
</div>

<div class="row mb-3">
    <div class="col-md-3 col-6 mb-2">
        <div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 font-weight-bold"><?= (int)$recipient_stats['total'] ?></div><div class="text-muted small">Total</div></div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 font-weight-bold text-secondary"><?= (int)$recipient_stats['pending'] ?></div><div class="text-muted small">Remaining</div></div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 font-weight-bold text-primary"><?= (int)$recipient_stats['sent'] ?></div><div class="text-muted small">Sent</div></div>
    </div>
    <div class="col-md-3 col-6 mb-2">
        <div class="card border-0 shadow-sm text-center py-2"><div class="h4 mb-0 font-weight-bold text-danger"><?= (int)$recipient_stats['failed'] ?></div><div class="text-muted small">Skipped/Failed</div></div>
    </div>
</div>

<?php if (!$recipient): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-circle-check text-success" style="font-size:2.5rem;"></i>
            <h5 class="font-weight-bold mt-3 mb-1">All recipients handled</h5>
            <p class="text-muted mb-3">Every customer in this campaign has been sent to or skipped.</p>
            <a href="<?= base_url('marketing/campaign_view/'.$campaign->id) ?>" class="btn btn-dark btn-sm">Back to Campaign</a>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm" style="max-width:640px;">
        <div class="card-header bg-white py-2 font-weight-bold d-flex justify-content-between align-items-center" style="font-size:.85rem;">
            <span>Next: <?= htmlspecialchars($recipient->customer_name) ?></span>
            <span class="text-muted font-weight-normal"><?= htmlspecialchars($recipient->phone) ?></span>
        </div>
        <div class="card-body">
            <?php if (!empty($phone_invalid)): ?>
                <div class="alert alert-warning py-2 mb-3" style="font-size:.85rem;">
                    <i class="fas fa-triangle-exclamation mr-1"></i> This phone number doesn't look valid — you can't send to it. Skip to move on.
                </div>
            <?php endif; ?>

            <label class="small font-weight-bold mb-1">Message</label>
            <textarea id="msgText" class="form-control mb-2" rows="6" style="font-size:.9rem;"><?= htmlspecialchars($message_text) ?></textarea>
            <small class="text-muted d-block mb-3">You can edit this before sending — it only affects this customer's message.</small>

            <a id="waLink" href="<?= htmlspecialchars($wa_link ?? '#') ?>" target="_blank" rel="noopener"
               class="btn btn-success btn-block font-weight-bold mb-2 <?= empty($wa_link) ? 'disabled' : '' ?>">
                <i class="fab fa-whatsapp mr-1"></i> Open WhatsApp &amp; Send
            </a>

            <div class="row">
                <div class="col-6">
                    <a href="<?= base_url('marketing/campaign_manual_mark/'.$campaign->id.'/'.$recipient->id.'/sent') ?>" class="btn btn-outline-primary btn-block">
                        <i class="fas fa-check mr-1"></i> Mark Sent &amp; Next
                    </a>
                </div>
                <div class="col-6">
                    <a href="<?= base_url('marketing/campaign_manual_mark/'.$campaign->id.'/'.$recipient->id.'/skip') ?>" class="btn btn-outline-secondary btn-block"
                       onclick="return confirm('Skip this customer without sending?');">
                        <i class="fas fa-forward mr-1"></i> Skip
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Keep the WhatsApp link's ?text= in sync if the message is edited before sending
    const msgText = document.getElementById('msgText');
    const waLink  = document.getElementById('waLink');
    const baseWaUrl = waLink.href.split('?text=')[0];
    msgText.addEventListener('input', function() {
        waLink.href = baseWaUrl + '?text=' + encodeURIComponent(msgText.value);
    });
    </script>
<?php endif; ?>
