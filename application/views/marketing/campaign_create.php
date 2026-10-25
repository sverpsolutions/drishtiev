<?php // FILE: application/views/marketing/campaign_create.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> New Campaign</h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / <a href="<?= base_url('marketing/campaigns') ?>">Campaigns</a> / New</small>
    </div>
    <a href="<?= base_url('marketing/campaigns') ?>" class="btn btn-outline-dark btn-sm">Back to Campaigns</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<form method="post" id="campaignForm">
<div class="row">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Campaign Details</div>
            <div class="card-body">
                <div class="form-group">
                    <label class="font-weight-bold">Campaign Name *</label>
                    <input type="text" name="name" class="form-control form-control-sm" required placeholder="e.g. Diwali Mega Sale 2026">
                </div>
                <div class="form-row">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Campaign Type *</label>
                        <select name="campaign_type" class="form-control form-control-sm" required>
                            <option value="festival_sale">Festival Sale</option>
                            <option value="weekend_offer">Weekend Offer</option>
                            <option value="bogo">Buy One Get One</option>
                            <option value="combo">Combo Offer</option>
                            <option value="discount">Discount Offer</option>
                            <option value="clearance_sale">Clearance Sale</option>
                            <option value="new_arrival">New Arrival</option>
                            <option value="membership">Membership Offer</option>
                            <option value="loyalty_reward">Loyalty Reward</option>
                            <option value="birthday">Birthday Offer</option>
                            <option value="anniversary">Anniversary Offer</option>
                            <option value="cashback">Cashback</option>
                            <option value="coupon">Coupon Offer</option>
                            <option value="custom" selected>Custom Campaign</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Priority</label>
                        <input type="number" name="priority" class="form-control form-control-sm" value="5" min="1" max="10">
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Description</label>
                    <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="Internal note about this campaign"></textarea>
                </div>
                <div class="form-row">
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold">Start Date *</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold">End Date *</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="font-weight-bold">Valid Till</label>
                        <input type="date" name="valid_till" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Min Purchase</label>
                        <input type="text" name="min_purchase" class="form-control form-control-sm" value="0.00">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Max Discount</label>
                        <input type="text" name="max_discount" class="form-control form-control-sm" value="0.00">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Coupon Code</label>
                        <input type="text" name="coupon_code" class="form-control form-control-sm" placeholder="e.g. DIWALI20">
                    </div>
                    <div class="col-md-3 form-group">
                        <label class="font-weight-bold">Budget</label>
                        <input type="text" name="budget" class="form-control form-control-sm" value="0.00">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">
                <i class="fas fa-wand-magic-sparkles mr-1"></i> AI Copywriter
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="small font-weight-bold mb-0">Describe the offer</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="aiBrief" class="form-control" placeholder="e.g. 20% off groceries, this weekend only">
                        <div class="input-group-append">
                            <button type="button" id="aiGenerateBtn" class="btn btn-outline-dark">Generate with AI</button>
                        </div>
                    </div>
                </div>
                <div id="aiVariants"></div>
                <div id="aiError" class="small text-danger" style="display:none;"></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">
                Message
                <span class="badge badge-light border float-right">Sent via an approved WhatsApp template — set up in Phase 3</span>
            </div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Message Text *</label>
                    <textarea name="message_body" id="messageBody" class="form-control form-control-sm" rows="5" required
                        placeholder="🔥 Diwali Mega Sale!&#10;Flat 20% OFF on Grocery&#10;Hi {{CustomerName}}, offer valid till {{Date}}.&#10;Visit us today!"></textarea>
                    <small class="text-muted">Use <code>{{CustomerName}}</code>, <code>{{Reward}}</code>, <code>{{Date}}</code> etc. as placeholders — this is saved as a draft message template.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Customer Filter</div>
            <div class="card-body">
                <div class="form-row">
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold mb-0">City</label>
                        <select name="city" class="form-control form-control-sm filter-input">
                            <option value="">— any —</option>
                            <?php foreach ($cities as $c): if (empty($c->city)) continue; ?>
                                <option value="<?= htmlspecialchars($c->city) ?>"><?= htmlspecialchars($c->city) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold mb-0">Pincode</label>
                        <input type="text" name="pincode" class="form-control form-control-sm filter-input">
                    </div>
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold mb-0">Customer Type</label>
                    <select name="customer_type" class="form-control form-control-sm filter-input">
                        <option value="">— any —</option>
                        <option value="retail">Retail</option>
                        <option value="wholesale">Wholesale</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold mb-0">Haven't purchased in</label>
                    <select name="last_purchase_days" class="form-control form-control-sm filter-input">
                        <option value="">— any —</option>
                        <option value="30">30 days</option>
                        <option value="60">60 days</option>
                        <option value="90">90 days</option>
                        <option value="180">180 days</option>
                        <option value="365">1 year</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold mb-0">Registered within last (days)</label>
                    <input type="number" name="new_customer_days" class="form-control form-control-sm filter-input" placeholder="e.g. 30 for new customers">
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold mb-0">Minimum total spent (₹)</label>
                    <input type="text" name="min_total_spent" class="form-control form-control-sm filter-input" placeholder="e.g. 5000 for high spenders">
                </div>
                <div class="form-row">
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold mb-0">Min. outstanding balance</label>
                        <input type="text" name="min_balance" class="form-control form-control-sm filter-input">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="small font-weight-bold mb-0">Min. wallet balance</label>
                        <input type="text" name="min_wallet_balance" class="form-control form-control-sm filter-input">
                    </div>
                </div>
                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input filter-input" name="never_purchased" value="1" id="neverPurchased">
                    <label class="custom-control-label" for="neverPurchased">Never purchased (first-time targets only)</label>
                </div>
                <p class="text-muted small mb-0">
                    Not available yet (needs new customer fields): birthday, anniversary, gender, area/locality, loyalty tier.
                </p>
            </div>
        </div>

        <div class="card border-0 shadow-sm border-success">
            <div class="card-body text-center py-3">
                <div class="text-muted small">Matched Customers</div>
                <div class="h2 mb-0 font-weight-bold text-success" id="matchedCount">…</div>
                <div class="text-muted small">will receive this campaign</div>
            </div>
        </div>

        <button type="submit" class="btn btn-dark btn-block mt-3">Create Campaign (Draft)</button>
    </div>
</div>
</form>

<script>
let previewTimer;
function refreshPreviewCount() {
    clearTimeout(previewTimer);
    previewTimer = setTimeout(function() {
        const data = {};
        document.querySelectorAll('.filter-input').forEach(el => {
            if (el.type === 'checkbox') { if (el.checked) data[el.name] = 1; }
            else { data[el.name] = el.value; }
        });
        $.post('<?= base_url('marketing/preview_count_ajax') ?>', data, function(resp) {
            document.getElementById('matchedCount').textContent = resp.count;
        }, 'json').fail(function() {
            document.getElementById('matchedCount').textContent = '?';
        });
    }, 300);
}
document.querySelectorAll('.filter-input').forEach(el => {
    el.addEventListener('input', refreshPreviewCount);
    el.addEventListener('change', refreshPreviewCount);
});
refreshPreviewCount();

document.getElementById('aiGenerateBtn').addEventListener('click', function() {
    var btn = this;
    var brief = document.getElementById('aiBrief').value.trim();
    var errBox = document.getElementById('aiError');
    var listBox = document.getElementById('aiVariants');
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
                document.getElementById('messageBody').value = v;
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
