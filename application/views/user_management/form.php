<?php // FILE: application/views/user_management/form.php
$is_edit = !empty($edit_user);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><?= $is_edit ? 'Edit User' : 'Add User' ?></h5>
        <small class="text-muted"><a href="<?= base_url('user_management') ?>">Users</a> / <?= $is_edit ? 'Edit' : 'Add' ?></small>
    </div>
    <a href="<?= base_url('user_management') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
<?php endif; ?>
<?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

<?= form_open($is_edit ? 'user_management/edit/'.$edit_user->id : 'user_management/add') ?>
<div class="row">

    <!-- Left: User details -->
    <div class="col-lg-5 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-dark text-white py-2"><strong>User Details</strong></div>
            <div class="card-body">

                <div class="form-group">
                    <label class="font-weight-bold small">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="<?= htmlspecialchars($edit_user->name ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control"
                           value="<?= htmlspecialchars($edit_user->username ?? '') ?>"
                           <?= $is_edit ? 'readonly' : 'required' ?>>
                    <?php if ($is_edit): ?>
                        <small class="text-muted">Username cannot be changed.</small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($edit_user->email ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small">
                        Password <?= $is_edit ? '<span class="text-muted font-weight-normal">(leave blank to keep)</span>' : '<span class="text-danger">*</span>' ?>
                    </label>
                    <input type="password" name="password" class="form-control"
                           <?= $is_edit ? '' : 'required' ?>
                           placeholder="<?= $is_edit ? 'Leave blank to keep current' : 'Enter password' ?>">
                </div>

                <div class="form-row">
                    <div class="form-group col-<?= $is_edit ? '6' : '12' ?>">
                        <label class="font-weight-bold small">Role</label>
                        <select name="role" class="form-control" id="roleSelect" onchange="handleRoleChange(this.value)">
                            <option value="owner"      <?= ($edit_user->role ?? '') === 'owner'      ? 'selected' : '' ?>>Owner (All Locations — Full Access)</option>
                            <option value="superadmin" <?= ($edit_user->role ?? '') === 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                            <option value="manager"    <?= ($edit_user->role ?? '') === 'manager'    ? 'selected' : '' ?>>Manager</option>
                            <option value="cashier"    <?= ($edit_user->role ?? '') === 'cashier'    ? 'selected' : '' ?>>Cashier</option>
                        </select>
                        <small class="text-muted" id="roleHint"></small>
                    </div>
                    <?php if ($is_edit): ?>
                    <div class="form-group col-6">
                        <label class="font-weight-bold small">Status</label>
                        <select name="status" class="form-control">
                            <option value="1" <?= $edit_user->status ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= !$edit_user->status ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group mb-0">
                    <label class="font-weight-bold small">Assigned Location</label>
                    <select name="location_id" class="form-control" id="locationSelect" onchange="handleLocationChange(this)">
                        <option value="">-- All Locations (Owner / Admin) --</option>
                        <?php if (!empty($locations)): foreach ($locations as $loc): ?>
                            <option value="<?= $loc->id ?>"
                                    data-type="<?= htmlspecialchars($loc->type) ?>"
                                    <?= ($edit_user->location_id ?? null) == $loc->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc->name) ?>
                                (<?= $loc->type === 'warehouse' ? 'Warehouse' : 'Outlet' ?>)
                            </option>
                        <?php endforeach; endif; ?>
                    </select>
                    <small class="text-muted" id="locHint">
                        Leave blank for owner/admin who can access all locations.
                    </small>
                </div>

            </div>
        </div>
    </div>

    <!-- Right: Permissions -->
    <div class="col-lg-7 mb-3">

        <!-- Module Access -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <strong>Module Access Permissions</strong>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-success py-0" onclick="setAll('.mod-toggle', 1)">All ON</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 ml-1" onclick="setAll('.mod-toggle', 0)">All OFF</button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                <?php foreach ($modules as $key => $label): ?>
                <div class="col-md-6 mb-2">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input mod-toggle" id="mod_<?= $key ?>"
                               name="perm_<?= $key ?>" value="1"
                               <?= !empty($user_perms[$key]) ? 'checked' : '' ?>>
                        <label class="custom-control-label small" for="mod_<?= $key ?>">
                            <?= htmlspecialchars($label) ?>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Form-level Permissions -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background:#f8f3e3;">
                <strong><i class="fas fa-file-alt mr-1"></i> Page / Form Access Permissions</strong>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-success py-0" onclick="setAll('.frm-toggle', 1)">All ON</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0 ml-1" onclick="setAll('.frm-toggle', 0)">All OFF</button>
                </div>
            </div>
            <div class="card-body px-3 py-2">
                <?php
                // Group the form permissions by their group
                $groups = [];
                foreach ($form_modules as $key => $meta) {
                    $groups[$meta['group']][$key] = $meta['label'];
                }
                foreach ($groups as $group_name => $forms):
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge badge-dark px-2 py-1" style="font-size:0.75rem;"><?= htmlspecialchars($group_name) ?></span>
                        <div>
                            <button type="button" class="btn btn-xs btn-outline-success py-0 px-1"
                                style="font-size:0.7rem;"
                                onclick="setGroupAll('grp_<?= preg_replace('/\W+/','_', $group_name) ?>', 1)">ON</button>
                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1 ml-1"
                                style="font-size:0.7rem;"
                                onclick="setGroupAll('grp_<?= preg_replace('/\W+/','_', $group_name) ?>', 0)">OFF</button>
                        </div>
                    </div>
                    <div class="row pl-2" id="grp_<?= preg_replace('/\W+/','_', $group_name) ?>">
                    <?php foreach ($forms as $fkey => $flabel): ?>
                    <div class="col-md-6 mb-1">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input frm-toggle" id="frm_<?= $fkey ?>"
                                   name="fperm_<?= $fkey ?>" value="1"
                                   <?= !empty($user_form_perms[$fkey]) ? 'checked' : '' ?>>
                            <label class="custom-control-label" style="font-size:0.8rem;" for="frm_<?= $fkey ?>">
                                <?= htmlspecialchars($flabel) ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Billing Permissions -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2" style="background:#e3f0e3;">
                <strong><i class="fas fa-rupee-sign mr-1"></i> Billing Permissions</strong>
            </div>
            <div class="card-body px-3 py-2">
                <?php
                    $po_val  = isset($user_perm_row->allow_price_override)      ? (int)$user_perm_row->allow_price_override      : 1;
                    $pmc_val = isset($user_perm_row->allow_payment_mode_change)  ? (int)$user_perm_row->allow_payment_mode_change  : 0;
                    $due_val = isset($user_perm_row->allow_due_bills)            ? (int)$user_perm_row->allow_due_bills            : 0;
                ?>
                <div class="custom-control custom-switch mb-2">
                    <input type="hidden" name="allow_price_override" value="0">
                    <input type="checkbox" class="custom-control-input" id="allow_price_override"
                           name="allow_price_override" value="1" <?= $po_val ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="allow_price_override" style="font-size:0.85rem;">
                        Allow Price Override
                        <div class="text-muted small">When OFF, this user cannot change item rates in the billing screen.</div>
                    </label>
                </div>
                <div class="custom-control custom-switch mb-2">
                    <input type="hidden" name="allow_payment_mode_change" value="0">
                    <input type="checkbox" class="custom-control-input" id="allow_payment_mode_change"
                           name="allow_payment_mode_change" value="1" <?= $pmc_val ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="allow_payment_mode_change" style="font-size:0.85rem;">
                        Allow Payment Mode Change
                        <div class="text-muted small">When ON, this user can change the payment mode on saved invoices.</div>
                    </label>
                </div>
                <div class="custom-control custom-switch">
                    <input type="hidden" name="allow_due_bills" value="0">
                    <input type="checkbox" class="custom-control-input" id="allow_due_bills"
                           name="allow_due_bills" value="1" <?= $due_val ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="allow_due_bills" style="font-size:0.85rem;">
                        Allow Due / Credit Bills
                        <div class="text-muted small">When ON, this user can create invoices with Credit payment mode.</div>
                    </label>
                </div>
            </div>
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-warning btn-block btn-lg">
                <i class="fas fa-save mr-1"></i> <?= $is_edit ? 'Update User' : 'Create User' ?>
            </button>
        </div>
    </div>

</div>
<?= form_close() ?>

<script>
function setAll(selector, val) {
    document.querySelectorAll(selector).forEach(function(el){ el.checked = !!val; });
}
function setGroupAll(groupId, val) {
    document.querySelectorAll('#' + groupId + ' .frm-toggle').forEach(function(el){ el.checked = !!val; });
}
function handleLocationChange(sel) {
    var hint = document.getElementById('locHint');
    if (!sel.value) {
        hint.textContent = 'Leave blank for owner/admin who can access all locations.';
        hint.className = 'text-muted small';
    } else {
        var type = sel.options[sel.selectedIndex].dataset.type;
        hint.textContent = (type === 'outlet' ? 'Outlet staff — locked to this outlet only.' : 'Warehouse staff — locked to this warehouse only.');
        hint.className = 'text-warning small font-weight-bold';
    }
}
function handleRoleChange(role) {
    var hints = {
        'owner':      'Owner has full access to all outlets and warehouses. No location restriction.',
        'superadmin': 'Super Admin has full access to all modules and locations.',
        'manager':    'Manager can access most modules. Assign a location if needed.',
        'cashier':    'Cashier must be assigned to a specific outlet.'
    };
    var el = document.getElementById('roleHint');
    if (el) { el.textContent = hints[role] || ''; }
    // Auto-clear location for owner/superadmin
    if (role === 'owner' || role === 'superadmin') {
        var loc = document.getElementById('locationSelect');
        if (loc) { loc.value = ''; handleLocationChange(loc); }
    }
}
// Module-to-form permission mapping: toggling a module auto-syncs related form perms
var MOD_FORM_MAP = {
    'mod_dashboard':       ['frm_dashboard'],
    'mod_billing':         ['frm_billing', 'frm_billing_create', 'frm_estimates', 'frm_sales_returns', 'frm_order_requests'],
    'mod_accounts':        ['frm_payments_receive'],
    'mod_item_master':     ['frm_products', 'frm_products_add', 'frm_products_import', 'frm_plu', 'frm_hsn', 'frm_masters', 'frm_schemes'],
    'mod_guest_master':    ['frm_customers', 'frm_suppliers'],
    'mod_purchase':        ['frm_purchases', 'frm_purchase_orders', 'frm_grn', 'frm_rfq', 'frm_purchase_returns', 'frm_payments_supplier', 'frm_payments_print_cheque', 'frm_purchase_suggestions'],
    'mod_inventory':       ['frm_inventory'],
    'mod_stock_transfers': ['frm_stock_transfers', 'frm_stock_transfers_edit'],
    'mod_stock_take':      ['frm_stock_take'],
    'mod_reports':         ['frm_rpt_sales','frm_rpt_profit_loss','frm_rpt_stock','frm_rpt_purchase','frm_rpt_daily_shift','frm_rpt_party_ledger','frm_rpt_outstanding','frm_rpt_gst','frm_rpt_inventory','frm_rpt_category','frm_rpt_credit_notes','frm_rpt_audit_log','frm_rpt_wallet_ledger','frm_rpt_portal_funnel','frm_rpt_po_grn_variance','frm_rpt_stock_take_variance','frm_rpt_trial_balance','frm_rpt_general_ledger','frm_rpt_schemes','frm_rpt_export','frm_rpt_email','frm_rpt_print'],
    'mod_accounting':      ['frm_coa_manage', 'frm_journal_create', 'frm_journal_reverse', 'frm_bank_accounts'],
    'mod_settings':        ['frm_settings'],
    'mod_locations':       ['frm_locations'],
    'mod_user_management': ['frm_user_management'],
    'mod_whatsapp_marketing': ['frm_wa_dashboard', 'frm_wa_campaigns', 'frm_wa_responses'],
};
function syncModuleToForms(modKey, checked) {
    var forms = MOD_FORM_MAP[modKey];
    if (!forms) return;
    forms.forEach(function(fk) {
        var el = document.getElementById('frm_' + fk);
        if (el) el.checked = checked;
    });
}
document.querySelectorAll('.mod-toggle').forEach(function(el) {
    el.addEventListener('change', function() {
        var modKey = this.name.replace('perm_', '');
        syncModuleToForms(modKey, this.checked);
    });
});
// Also sync "All ON" / "All OFF" for modules
var origSetAll = setAll;
setAll = function(selector, val) {
    origSetAll(selector, val);
    if (selector === '.mod-toggle') {
        Object.keys(MOD_FORM_MAP).forEach(function(mk) { syncModuleToForms(mk, !!val); });
    }
};

// Run on load for edit mode
document.addEventListener('DOMContentLoaded', function() {
    handleLocationChange(document.getElementById('locationSelect'));
    handleRoleChange(document.getElementById('roleSelect').value);
});
</script>
