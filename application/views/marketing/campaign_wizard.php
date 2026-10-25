<?php // FILE: application/views/marketing/campaign_wizard.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-bolt text-warning mr-1"></i> One-Click Send</h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / One-Click Send</small>
    </div>
    <a href="<?= base_url('marketing/campaigns') ?>" class="btn btn-outline-dark btn-sm">Back to Campaigns</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="alert alert-info small">
    <i class="fas fa-info-circle mr-1"></i> This prepares a fully-ready draft campaign in one step — audience, message, and poster. It still needs its message template <strong>Approved</strong> (Marketing → Templates) before you can hit Start Sending, since WhatsApp requires that for any business-initiated message — that's a platform rule, not something this can skip.
</div>

<form method="post" action="<?= base_url('marketing/campaign_wizard_submit') ?>" id="wizardForm">
<div class="row">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">1. Campaign Basics</div>
            <div class="card-body">
                <div class="form-row">
                    <div class="col-md-7 form-group">
                        <label class="small font-weight-bold mb-0">Campaign Name *</label>
                        <input type="text" name="name" class="form-control form-control-sm" required placeholder="e.g. Weekend Flash Sale">
                    </div>
                    <div class="col-md-5 form-group">
                        <label class="small font-weight-bold mb-0">Type</label>
                        <select name="campaign_type" class="form-control form-control-sm">
                            <option value="weekend_offer">Weekend Offer</option>
                            <option value="discount">Discount Offer</option>
                            <option value="clearance_sale">Clearance Sale</option>
                            <option value="new_arrival">New Arrival</option>
                            <option value="festival_sale">Festival Sale</option>
                            <option value="loyalty_reward">Loyalty Reward</option>
                            <option value="custom" selected>Custom</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">2. Audience</div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($presets as $key => $p): ?>
                    <div class="col-md-6 mb-2">
                        <label class="d-block border rounded p-2" style="cursor:pointer;">
                            <input type="radio" name="preset" value="<?= $key ?>" class="preset-input mr-1" <?= $key === 'all' ? 'checked' : '' ?>>
                            <?= htmlspecialchars($p['label']) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-2 pt-2 border-top">
                    <span class="text-muted small">Matched Customers:</span>
                    <span class="h4 font-weight-bold text-success ml-2" id="wizMatchedCount">…</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;"><i class="fas fa-wand-magic-sparkles mr-1"></i> 3. Message</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="small font-weight-bold mb-0">Describe the offer</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="wizAiBrief" class="form-control" placeholder="e.g. Flat 15% off this weekend">
                        <div class="input-group-append">
                            <button type="button" id="wizAiGenerateBtn" class="btn btn-outline-dark">Generate with AI</button>
                        </div>
                    </div>
                </div>
                <div id="wizAiVariants"></div>
                <div id="wizAiError" class="small text-danger mb-2" style="display:none;"></div>
                <div class="form-group mb-0">
                    <label class="small font-weight-bold mb-0">Message Text *</label>
                    <textarea name="message_body" id="wizMessageBody" class="form-control form-control-sm" rows="4" required
                        placeholder="Hi {{CustomerName}}, ..."></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">4. Poster (optional)</div>
            <div class="card-body">
                <?php if (empty($posters)): ?>
                    <p class="small text-muted mb-0">No poster templates yet — <a href="<?= base_url('marketing/posters') ?>">upload one</a> to include a poster automatically, or skip this step.</p>
                <?php else: ?>
                    <div class="row">
                        <div class="col-4 mb-2 text-center">
                            <label class="d-block mb-1" style="cursor:pointer;">
                                <div class="border rounded d-flex align-items-center justify-content-center text-muted" style="height:70px;">None</div>
                                <input type="radio" name="poster_template_id" value="" checked class="mt-1">
                            </label>
                        </div>
                        <?php foreach ($posters as $p): ?>
                        <div class="col-4 mb-2 text-center">
                            <label class="d-block mb-1" style="cursor:pointer;">
                                <img src="<?= base_url($p->background_path) ?>" class="img-fluid rounded border" style="height:70px;width:100%;object-fit:cover;">
                                <input type="radio" name="poster_template_id" value="<?= $p->id ?>" class="mt-1">
                            </label>
                            <div class="small text-truncate"><?= htmlspecialchars($p->name) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold mb-0">Offer Text (on poster)</label>
                        <input type="text" name="offer_text" class="form-control form-control-sm" placeholder="e.g. Flat 15% OFF">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <button type="submit" class="btn btn-warning btn-block font-weight-bold"><i class="fas fa-bolt mr-1"></i> Create Campaign</button>
    </div>
</div>
</form>

<script>
let wizTimer;
function wizRefreshCount() {
    clearTimeout(wizTimer);
    wizTimer = setTimeout(function() {
        const preset = document.querySelector('input[name="preset"]:checked').value;
        $.post('<?= base_url('marketing/wizard_preview_count_ajax') ?>', { preset: preset }, function(resp) {
            document.getElementById('wizMatchedCount').textContent = resp.count;
        }, 'json').fail(function() {
            document.getElementById('wizMatchedCount').textContent = '?';
        });
    }, 200);
}
document.querySelectorAll('.preset-input').forEach(el => el.addEventListener('change', wizRefreshCount));
wizRefreshCount();

document.getElementById('wizAiGenerateBtn').addEventListener('click', function() {
    var btn = this;
    var brief = document.getElementById('wizAiBrief').value.trim();
    var errBox = document.getElementById('wizAiError');
    var listBox = document.getElementById('wizAiVariants');
    errBox.style.display = 'none';
    listBox.innerHTML = '';
    if (!brief) {
        errBox.textContent = 'Describe the offer first.';
        errBox.style.display = 'block';
        return;
    }
    btn.disabled = true;
    btn.textContent = 'Generating…';
    var campaignType = document.querySelector('[name="campaign_type"]').value;
    $.post('<?= base_url('marketing/ai_generate_copy_ajax') ?>', { campaign_type: campaignType, brief: brief }, function(resp) {
        btn.disabled = false;
        btn.textContent = 'Generate with AI';
        if (!resp.ok) {
            errBox.textContent = resp.error || 'Could not generate copy.';
            errBox.style.display = 'block';
            return;
        }
        resp.variants.forEach(function(v) {
            var div = document.createElement('div');
            div.className = 'p-2 mb-2 rounded border small';
            div.style.cursor = 'pointer';
            div.style.background = '#f8f9fa';
            div.textContent = v;
            div.title = 'Click to use this message';
            div.addEventListener('click', function() {
                document.getElementById('wizMessageBody').value = v;
            });
            listBox.appendChild(div);
        });
    }, 'json').fail(function() {
        btn.disabled = false;
        btn.textContent = 'Generate with AI';
        errBox.textContent = 'Network error while generating copy.';
        errBox.style.display = 'block';
    });
});
</script>
