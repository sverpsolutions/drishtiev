<?php
// $s = supplier object or null; $is_edit = bool
$v = function($field, $default = '') use ($s) { return $s ? htmlspecialchars($s->$field ?? $default) : $default; };
$users = $this->db->select('id,name')->where('status',1)->where_in('role',['admin','manager','superadmin','owner'])->get('users')->result();
?>
<?php if (validation_errors()): ?>
<div class="alert alert-danger"><?= validation_errors() ?></div>
<?php endif; ?>

<!-- Basic Info -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-dark text-white py-2"><i class="fas fa-building mr-1"></i> Basic Information</div>
    <div class="card-body">
        <div class="form-row">
            <div class="col-md-4 form-group"><label class="small font-weight-bold">Supplier Name <span class="text-danger">*</span></label>
                <input name="name" class="form-control form-control-sm" value="<?= $v('name') ?>" required></div>
            <div class="col-md-4 form-group"><label class="small font-weight-bold">Company Name</label>
                <input name="company_name" class="form-control form-control-sm" value="<?= $v('company_name') ?>"></div>
            <div class="col-md-4 form-group"><label class="small font-weight-bold">Contact Person</label>
                <input name="contact_name" class="form-control form-control-sm" value="<?= $v('contact_name') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Mobile <span class="text-danger">*</span></label>
                <input name="phone" class="form-control form-control-sm" value="<?= $v('phone') ?>" required></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">WhatsApp</label>
                <input name="whatsapp" class="form-control form-control-sm" value="<?= $v('whatsapp') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Email</label>
                <input name="email" type="email" class="form-control form-control-sm" value="<?= $v('email') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Website</label>
                <input name="website" class="form-control form-control-sm" value="<?= $v('website') ?>"></div>
            <div class="col-md-4 form-group"><label class="small font-weight-bold">GSTIN</label>
                <input name="gst_number" class="form-control form-control-sm text-uppercase" value="<?= $v('gst_number') ?>"></div>
            <div class="col-md-4 form-group"><label class="small font-weight-bold">PAN</label>
                <input name="pan_number" class="form-control form-control-sm text-uppercase" value="<?= $v('pan_number') ?>"></div>
            <div class="col-md-4 form-group"><label class="small font-weight-bold">Preferred Supplier</label>
                <select name="is_preferred" class="form-control form-control-sm">
                    <option value="0" <?= $v('is_preferred')=='0'?'selected':'' ?>>No</option>
                    <option value="1" <?= $v('is_preferred')=='1'?'selected':'' ?>>Yes ★</option>
                </select></div>
        </div>
    </div>
</div>

<!-- Address -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-secondary text-white py-2"><i class="fas fa-map-marker-alt mr-1"></i> Address</div>
    <div class="card-body">
        <div class="form-row">
            <div class="col-md-12 form-group"><label class="small font-weight-bold">Address</label>
                <textarea name="address" class="form-control form-control-sm" rows="2"><?= $v('address') ?></textarea></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">City</label>
                <input name="city" class="form-control form-control-sm" value="<?= $v('city') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">State</label>
                <input name="state" class="form-control form-control-sm" value="<?= $v('state') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Country</label>
                <input name="country" class="form-control form-control-sm" value="<?= $v('country','India') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">PIN Code</label>
                <input name="pincode" class="form-control form-control-sm" value="<?= $v('pincode') ?>"></div>
        </div>
    </div>
</div>

<!-- Financial -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-success text-white py-2"><i class="fas fa-rupee-sign mr-1"></i> Financial Terms</div>
    <div class="card-body">
        <div class="form-row">
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Credit Days</label>
                <input name="credit_days" type="number" class="form-control form-control-sm" value="<?= $v('credit_days','0') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Credit Limit (₹)</label>
                <input name="credit_limit" type="number" step="0.01" class="form-control form-control-sm" value="<?= $v('credit_limit','0') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Opening Balance (₹)</label>
                <input name="opening_balance" type="number" step="0.01" class="form-control form-control-sm" value="<?= $v('opening_balance','0') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Currency</label>
                <select name="currency" class="form-control form-control-sm">
                    <option value="INR" <?= $v('currency')=='INR'?'selected':'' ?>>INR ₹</option>
                    <option value="USD" <?= $v('currency')=='USD'?'selected':'' ?>>USD $</option>
                </select></div>
            <div class="col-md-6 form-group"><label class="small font-weight-bold">Payment Terms</label>
                <input name="payment_terms" class="form-control form-control-sm" placeholder="e.g. Net 30, COD, Advance" value="<?= $v('payment_terms') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Default Tax %</label>
                <select name="default_tax_pct" class="form-control form-control-sm">
                    <?php foreach ([0,5,12,18,40] as $t): ?>
                    <option value="<?= $t ?>" <?= $v('default_tax_pct','18')==$t?'selected':'' ?>><?= $t ?>%</option>
                    <?php endforeach; ?>
                </select></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Default Discount %</label>
                <input name="default_discount" type="number" step="0.01" class="form-control form-control-sm" value="<?= $v('default_discount','0') ?>"></div>
        </div>
    </div>
</div>

<!-- Purchase Details -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-info text-white py-2"><i class="fas fa-shopping-cart mr-1"></i> Purchase Details</div>
    <div class="card-body">
        <div class="form-row">
            <div class="col-md-3 form-group"><label class="small font-weight-bold">MOQ (Min. Order Qty)</label>
                <input name="moq" type="number" class="form-control form-control-sm" value="<?= $v('moq','1') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Pack Size</label>
                <input name="pack_size" type="number" class="form-control form-control-sm" value="<?= $v('pack_size','1') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Lead Time (Days)</label>
                <input name="lead_time_days" type="number" class="form-control form-control-sm" value="<?= $v('lead_time_days','2') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Delivery Days</label>
                <input name="delivery_days" type="number" class="form-control form-control-sm" value="<?= $v('delivery_days','1') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Order Cut-off Time</label>
                <input name="order_cutoff_time" type="time" class="form-control form-control-sm" value="<?= $v('order_cutoff_time') ?>"></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Freight Applicable</label>
                <select name="freight_applicable" class="form-control form-control-sm">
                    <option value="0">No</option>
                    <option value="1" <?= $v('freight_applicable')=='1'?'selected':'' ?>>Yes</option>
                </select></div>
            <div class="col-md-6 form-group"><label class="small font-weight-bold">Notes</label>
                <input name="notes" class="form-control form-control-sm" value="<?= $v('notes') ?>"></div>
        </div>
    </div>
</div>

<!-- Visit Schedule -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-warning text-dark py-2"><i class="fas fa-calendar-alt mr-1"></i> Visit Schedule</div>
    <div class="card-body">
        <?php $sch = $s->schedule ?? null; ?>
        <div class="form-row">
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Schedule Type</label>
                <select name="schedule_type" id="schedType" class="form-control form-control-sm" onchange="toggleSched()">
                    <option value="">-- None --</option>
                    <option value="daily"         <?= ($sch->schedule_type??'')==='daily'?'selected':'' ?>>Daily</option>
                    <option value="interval"      <?= ($sch->schedule_type??'')==='interval'?'selected':'' ?>>Every N Days</option>
                    <option value="weekly"        <?= ($sch->schedule_type??'')==='weekly'?'selected':'' ?>>Specific Weekdays</option>
                    <option value="monthly_dates" <?= ($sch->schedule_type??'')==='monthly_dates'?'selected':'' ?>>Monthly Dates (1st, 15th...)</option>
                </select></div>
            <div class="col-md-3 form-group" id="intDiv" style="display:none"><label class="small font-weight-bold">Every N Days</label>
                <input name="interval_days" type="number" class="form-control form-control-sm" min="1" value="<?= $sch->interval_days ?? 7 ?>"></div>
            <div class="col-md-4 form-group" id="wdDiv" style="display:none"><label class="small font-weight-bold">Weekdays</label><br>
                <?php $selWd = json_decode($sch->weekdays ?? '[]', true) ?? [];
                $days = [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
                foreach ($days as $n => $d): ?>
                <div class="custom-control custom-checkbox custom-control-inline">
                    <input type="checkbox" name="weekdays[]" value="<?= $n ?>" class="custom-control-input" id="wd<?= $n ?>" <?= in_array($n,$selWd)?'checked':'' ?>>
                    <label class="custom-control-label" for="wd<?= $n ?>"><?= $d ?></label>
                </div><?php endforeach; ?></div>
            <div class="col-md-4 form-group" id="mdDiv" style="display:none"><label class="small font-weight-bold">Month Dates (e.g. 1,15)</label>
                <input name="month_dates_str" class="form-control form-control-sm" placeholder="1,15"
                    value="<?= implode(',', json_decode($sch->month_dates ?? '[]', true) ?? []) ?>">
                <input type="hidden" name="month_dates" id="hiddenMD" value="<?= htmlspecialchars($sch->month_dates ?? '[]') ?>">
            </div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Assigned Buyer</label>
                <select name="assigned_buyer" class="form-control form-control-sm">
                    <option value="">-- None --</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u->id ?>" <?= ($sch->assigned_buyer??'')==$u->id?'selected':'' ?>><?= htmlspecialchars($u->name) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="col-md-3 form-group"><label class="small font-weight-bold">Last Visit Date</label>
                <input name="last_visit_date" type="date" class="form-control form-control-sm" value="<?= $sch->last_visit_date ?? '' ?>"></div>
        </div>
    </div>
</div>

<!-- Vendor Portal Access -->
<div class="card border-0 shadow-sm mb-3" style="border-left:4px solid #8e44ad!important;">
    <div class="card-header py-2 text-white" style="background:#8e44ad;">
        <i class="fas fa-user-shield mr-1"></i> Vendor Portal Access
        <small class="ml-2 opacity-75" style="font-size:.75rem;">Give this supplier login access to the vendor portal</small>
    </div>
    <div class="card-body">
        <div class="form-row align-items-end">
            <div class="col-md-3 form-group mb-0">
                <label class="small font-weight-bold">Portal Username</label>
                <input name="portal_username" class="form-control form-control-sm" placeholder="e.g. supplier01"
                    value="<?= $v('portal_username') ?>" autocomplete="off">
                <small class="text-muted">Supplier will use this to log in</small>
            </div>
            <div class="col-md-3 form-group mb-0">
                <label class="small font-weight-bold">Set New Password</label>
                <div class="input-group input-group-sm">
                    <input name="portal_password" id="portalPwd" type="password" class="form-control form-control-sm"
                        placeholder="<?= $is_edit ? 'Leave blank to keep existing' : 'Enter password' ?>" autocomplete="new-password">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="togglePwd()" title="Show/Hide">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <?php if ($is_edit && $v('portal_password')): ?>
                <small class="text-success"><i class="fas fa-check-circle"></i> Password is set</small>
                <?php else: ?>
                <small class="text-muted">Min 6 characters</small>
                <?php endif; ?>
            </div>
            <div class="col-md-3 form-group mb-0">
                <label class="small font-weight-bold">Portal Access</label>
                <select name="portal_active" class="form-control form-control-sm">
                    <option value="0" <?= $v('portal_active','0')==='0'?'selected':'' ?>>Disabled</option>
                    <option value="1" <?= $v('portal_active','0')==='1'?'selected':'' ?>>Enabled</option>
                </select>
                <small class="text-muted">Enable to allow login</small>
            </div>
            <div class="col-md-3 form-group mb-0">
                <?php if ($is_edit && $v('portal_username')): ?>
                <label class="small font-weight-bold d-block">Portal Link</label>
                <a href="<?= base_url('vendor-portal') ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-external-link-alt"></i> Open Vendor Portal
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="form-group mt-2">
    <button type="submit" class="btn btn-dark btn-sm px-4"><i class="fas fa-save mr-1"></i> <?= $is_edit ? 'Update' : 'Save' ?> Supplier</button>
    <a href="<?= base_url('suppliers') ?>" class="btn btn-outline-secondary btn-sm ml-2">Cancel</a>
</div>

<script>
function togglePwd() {
    var f = document.getElementById('portalPwd');
    var i = document.getElementById('eyeIcon');
    if (f.type === 'password') { f.type = 'text'; i.className = 'fas fa-eye-slash'; }
    else { f.type = 'password'; i.className = 'fas fa-eye'; }
}
</script>

<script>
function toggleSched() {
    var t = document.getElementById('schedType').value;
    document.getElementById('intDiv').style.display = t === 'interval' ? '' : 'none';
    document.getElementById('wdDiv').style.display  = t === 'weekly'   ? '' : 'none';
    document.getElementById('mdDiv').style.display  = t === 'monthly_dates' ? '' : 'none';
}
toggleSched();
document.querySelector('[name="month_dates_str"]') && document.querySelector('[name="month_dates_str"]').addEventListener('input', function() {
    var parts = this.value.split(',').map(function(x){return parseInt(x.trim());}).filter(function(x){return !isNaN(x);});
    document.getElementById('hiddenMD').value = JSON.stringify(parts);
});
</script>
