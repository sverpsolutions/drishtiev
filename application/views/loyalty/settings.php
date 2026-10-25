<?php defined('BASEPATH') OR exit('No direct script access allowed');
$s = $scheme;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-cog text-primary mr-1"></i> Loyalty Setup</h5>
        <small class="text-muted">How points are earned, what they are worth, and how much of a bill they can pay</small>
    </div>
    <a href="<?= base_url('loyalty') ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
<?php endif; ?>

<div class="alert alert-info py-2 px-3" style="font-size:.86rem;">
    <i class="fas fa-info-circle mr-1"></i>
    <strong><?= number_format($members) ?></strong> loyalty members currently hold
    <strong><?= number_format($outstanding) ?></strong> points.
    Changing these settings <strong>does not</strong> recalculate points already earned —
    old bills keep the points they were given. New rules apply to future bills only.
</div>

<?= form_open('loyalty/settings') ?>
<div class="row">

  <div class="col-md-6 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-2 font-weight-bold">Earning</div>
      <div class="card-body">

        <div class="form-group">
            <label class="font-weight-bold">Rule Name</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($s->name) ?>">
        </div>

        <div class="form-group">
            <label class="font-weight-bold">How points are calculated</label>
            <select name="calc_type" id="calcType" class="form-control">
                <option value="amount_per_point"  <?= $s->calc_type==='amount_per_point'  ? 'selected':'' ?>>Spend a certain amount to earn 1 point</option>
                <option value="points_per_amount" <?= $s->calc_type==='points_per_amount' ? 'selected':'' ?>>Earn a number of points per ₹1</option>
                <option value="fixed_per_unit"    <?= $s->calc_type==='fixed_per_unit'    ? 'selected':'' ?>>Fixed points per unit sold</option>
            </select>
        </div>

        <div class="form-group calc-amount_per_point">
            <label class="font-weight-bold">&#8377; spent for 1 point</label>
            <input type="number" step="0.01" min="1" name="amount_per_point" class="form-control"
                   value="<?= htmlspecialchars($s->amount_per_point) ?>">
            <small class="text-muted">100 means a &#8377;500 line earns 5 points.</small>
        </div>

        <div class="form-group calc-points_per_amount" style="display:none;">
            <label class="font-weight-bold">Points per &#8377;1</label>
            <input type="number" step="0.001" min="0" name="points_per_amount" class="form-control"
                   value="<?= htmlspecialchars($s->points_per_amount) ?>">
        </div>

        <div class="form-group calc-fixed_per_unit" style="display:none;">
            <label class="font-weight-bold">Points per unit sold</label>
            <input type="number" step="0.001" min="0" name="fixed_points" class="form-control"
                   value="<?= htmlspecialchars($s->fixed_points) ?>">
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="font-weight-bold">Rounding</label>
                <select name="round_mode" class="form-control">
                    <option value="floor" <?= $s->round_mode==='floor'?'selected':'' ?>>Round down (recommended)</option>
                    <option value="round" <?= $s->round_mode==='round'?'selected':'' ?>>Round to nearest</option>
                    <option value="ceil"  <?= $s->round_mode==='ceil' ?'selected':'' ?>>Round up</option>
                </select>
                <small class="text-muted">Never award a part point.</small>
            </div>
            <div class="form-group col-md-6">
                <label class="font-weight-bold">Tier Multiplier</label>
                <input type="number" step="0.001" min="0" name="tier_multiplier" class="form-control"
                       value="<?= htmlspecialchars($s->tier_multiplier) ?>">
                <small class="text-muted">1 = normal. 2 = double points.</small>
            </div>
        </div>

        <div class="form-group mb-0">
            <label class="font-weight-bold">Points expire after (days)</label>
            <input type="number" min="0" step="1" name="expiry_days" class="form-control"
                   value="<?= (int)$s->expiry_days ?>">
            <small class="text-muted">0 = never expire. Expiry only happens when the sweep is run.</small>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6 mb-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-2 font-weight-bold">Redemption</div>
      <div class="card-body">

        <div class="form-group">
            <label class="font-weight-bold">1 point is worth (&#8377;)</label>
            <input type="number" step="0.01" min="0.01" name="redeem_value_per_point" class="form-control"
                   value="<?= htmlspecialchars($s->redeem_value_per_point) ?>">
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="font-weight-bold">Max % of bill payable</label>
                <input type="number" step="0.01" min="0" max="100" name="max_redeem_percent" class="form-control"
                       value="<?= htmlspecialchars($s->max_redeem_percent) ?>">
                <small class="text-muted">0 = no cap.</small>
            </div>
            <div class="form-group col-md-6">
                <label class="font-weight-bold">Max points per bill</label>
                <input type="number" step="1" min="0" name="max_redeem_points" class="form-control"
                       value="<?= htmlspecialchars($s->max_redeem_points) ?>">
                <small class="text-muted">0 = no cap.</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label class="font-weight-bold">Min bill to redeem (&#8377;)</label>
                <input type="number" step="0.01" min="0" name="min_bill_amount" class="form-control"
                       value="<?= htmlspecialchars($s->min_bill_amount) ?>">
            </div>
            <div class="form-group col-md-6">
                <label class="font-weight-bold">Min points to redeem</label>
                <input type="number" step="1" min="0" name="min_redeem_points" class="form-control"
                       value="<?= htmlspecialchars($s->min_redeem_points) ?>">
            </div>
        </div>

        <div class="alert alert-secondary py-2 px-3" style="font-size:.84rem;">
            <strong>Worked example</strong> at the current settings:<br>
            A bill of <strong>&#8377;1,000</strong> lets a customer spend at most
            <strong id="egPoints">—</strong> points, worth <strong id="egValue">—</strong>.
        </div>

        <div class="custom-control custom-switch mb-0">
            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                   <?= (int)$s->is_active === 1 ? 'checked' : '' ?>>
            <label class="custom-control-label font-weight-bold" for="is_active">
                Loyalty programme is active
            </label>
            <small class="text-muted d-block">
                Switch off to stop all earning and redeeming without losing any balances.
            </small>
        </div>
      </div>
    </div>
  </div>
</div>

<button class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save Loyalty Rule</button>
<a href="<?= base_url('loyalty') ?>" class="btn btn-outline-secondary ml-1">Cancel</a>
<?= form_close() ?>

<script>
(function () {
    var sel = document.getElementById('calcType');
    function syncCalc() {
        ['amount_per_point','points_per_amount','fixed_per_unit'].forEach(function (t) {
            document.querySelectorAll('.calc-' + t).forEach(function (e) {
                e.style.display = (sel.value === t) ? '' : 'none';
            });
        });
        syncExample();
    }
    // Mirrors Loyalty_model::max_redeemable() so the operator sees the real cap
    // before saving, not after a cashier hits it.
    function syncExample() {
        var bill = 1000,
            rate = parseFloat(document.querySelector('[name=redeem_value_per_point]').value) || 1,
            pct  = parseFloat(document.querySelector('[name=max_redeem_percent]').value) || 0,
            capP = parseFloat(document.querySelector('[name=max_redeem_points]').value) || 0,
            minB = parseFloat(document.querySelector('[name=min_bill_amount]').value) || 0;

        var pts = bill / rate;
        if (pct > 0)  pts = Math.min(pts, (bill * pct / 100) / rate);
        if (capP > 0) pts = Math.min(pts, capP);
        pts = Math.floor(pts);
        if (bill < minB) pts = 0;

        document.getElementById('egPoints').textContent = pts.toLocaleString();
        document.getElementById('egValue').textContent  = '₹ ' + (pts * rate).toFixed(2);
    }
    sel.addEventListener('change', syncCalc);
    ['redeem_value_per_point','max_redeem_percent','max_redeem_points','min_bill_amount']
        .forEach(function (n) {
            var el = document.querySelector('[name=' + n + ']');
            if (el) el.addEventListener('input', syncExample);
        });
    syncCalc();
})();
</script>
