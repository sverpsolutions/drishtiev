<?php // FILE: application/views/admin/data_reset.php ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold text-danger"><i class="fas fa-exclamation-triangle"></i> Data Reset</h5>
        <small class="text-muted">Permanently delete records from selected tables. This cannot be undone.</small>
    </div>
    <a href="<?= base_url('settings') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back to Settings</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle mr-1"></i> <?= $this->session->flashdata('success') ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle mr-1"></i> <?= $this->session->flashdata('error') ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
        <a class="nav-link" id="storeTab" data-toggle="tab" href="#storeReset" role="tab">
            <i class="fas fa-store mr-1"></i> Reset by Store
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="fullTab" data-toggle="tab" href="#fullReset" role="tab">
            <i class="fas fa-database mr-1"></i> Full Data Reset
        </a>
    </li>
</ul>

<div class="tab-content">

<!-- ═══════════════════════════════════════════════════════════════
     TAB 1: Store-wise Reset
     ═══════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade" id="storeReset" role="tabpanel">

<div class="alert alert-warning border-0 shadow-sm mb-3">
    <h6 class="font-weight-bold mb-1"><i class="fas fa-store mr-1"></i> Store-wise Transaction Reset</h6>
    <p class="mb-0" style="font-size:.88rem;">Delete transactions for a <strong>specific outlet/warehouse</strong> only. Other stores' data stays untouched. Products, customers, and master data are shared across stores and are not affected.</p>
</div>

<form method="post" action="<?= base_url('data_reset/execute_store') ?>" id="storeResetForm">
<div class="row">

    <!-- LEFT: Store selection + table selection -->
    <div class="col-lg-8">

        <!-- Store Picker -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-primary text-white py-2 font-weight-bold" style="font-size:.9rem;">
                <i class="fas fa-map-marker-alt mr-1"></i> Select Store / Outlet
            </div>
            <div class="card-body py-3">
                <div class="row">
                <?php foreach ($locations as $loc): ?>
                    <div class="col-md-3 mb-2">
                        <label class="card border shadow-sm mb-0 p-2 text-center h-100 d-flex flex-column justify-content-center" style="cursor:pointer;<?= $loc->type === 'warehouse' ? 'border-color:#6c757d !important;' : '' ?>">
                            <input type="radio" name="location_id" value="<?= $loc->id ?>" class="store-radio d-none" required>
                            <i class="fas fa-<?= $loc->type === 'warehouse' ? 'warehouse text-secondary' : 'store text-primary' ?> mb-1" style="font-size:1.4rem;"></i>
                            <span class="font-weight-bold" style="font-size:.82rem;"><?= htmlspecialchars($loc->name) ?></span>
                            <span class="badge badge-<?= $loc->type === 'warehouse' ? 'secondary' : 'primary' ?> mt-1"><?= htmlspecialchars($loc->code) ?> — <?= ucfirst($loc->type) ?></span>
                            <?php
                            $inv_cnt = $location_counts['invoices'][$loc->id] ?? 0;
                            $pur_cnt = $location_counts['purchases'][$loc->id] ?? 0;
                            ?>
                            <small class="text-muted mt-1"><?= number_format($inv_cnt) ?> bills, <?= number_format($pur_cnt) ?> purchases</small>
                        </label>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Transaction Tables -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                <span class="font-weight-bold" style="font-size:.9rem;">Transaction Tables to Reset</span>
                <div>
                    <button type="button" class="btn btn-xs btn-outline-light py-0 px-2" onclick="toggleStoreAll(true)">All</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" onclick="toggleStoreAll(false)">None</button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.87rem;">
                    <thead class="thead-light">
                        <tr><th width="40px"></th><th>Table</th><th>Strategy</th></tr>
                    </thead>
                    <tbody>
                    <?php
                    $store_groups = [
                        'Sales' => ['invoices','invoice_items','invoice_edit_log','invoice_payment_splits','payments','scheme_transactions','scheme_application_log','sales_returns'],
                        'Purchases' => ['purchases','purchase_items','purchase_returns','purchase_return_items','purchase_orders','purchase_order_items','purchase_approvals','grn','grn_items'],
                        'Logs' => ['audit_logs','held_bills'],
                    ];
                    $strategy_labels = [
                        'direct' => '<span class="badge badge-success">Direct (location_id)</span>',
                    ];
                    foreach ($store_groups as $grp => $tbls): ?>
                        <tr class="bg-light"><td colspan="3" class="font-weight-bold text-uppercase" style="font-size:.75rem;letter-spacing:.05em;"><?= $grp ?></td></tr>
                        <?php foreach ($tbls as $tbl):
                            if (!isset($location_tables[$tbl])) continue;
                            $st = $location_tables[$tbl];
                            $st_label = ($st === 'direct')
                                ? '<span class="badge badge-success">Direct</span>'
                                : '<span class="badge badge-info">Via ' . htmlspecialchars(str_replace('via:', '', $st)) . '</span>';
                        ?>
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" name="store_tables[]" value="<?= $tbl ?>" class="store-tbl-checkbox" checked>
                            </td>
                            <td><code class="font-weight-bold"><?= $tbl ?></code></td>
                            <td><?= $st_label ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- RIGHT: Confirmation Panel -->
    <div class="col-lg-4">
        <div class="card border-warning shadow" style="position:sticky;top:70px;">
            <div class="card-header bg-warning text-dark font-weight-bold">
                <i class="fas fa-store mr-1"></i> Confirm Store Reset
            </div>
            <div class="card-body">

                <div class="alert alert-light border mb-3" id="storeSummary">
                    <small class="text-muted">Select a store above.</small>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small">Admin Password <span class="text-danger">*</span></label>
                    <input type="password" name="admin_password_store" class="form-control form-control-sm border-warning" placeholder="Enter sverpadmin password" required>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold small">Type <code class="text-danger font-weight-bold">RESET</code> to confirm</label>
                    <input type="text" name="confirm_phrase_store" id="confirm_phrase_store" class="form-control form-control-sm border-warning" placeholder="Type RESET here" autocomplete="off" required>
                </div>

                <div class="alert alert-info py-2 mb-3" style="font-size:.8rem;">
                    <strong>Not affected:</strong> Products, Customers, Suppliers, HSN, Categories, Users, Settings
                </div>

                <button type="submit" id="storeSubmitBtn" class="btn btn-warning btn-block font-weight-bold text-dark"
                        onclick="return storeConfirm()">
                    <i class="fas fa-trash-alt mr-1"></i> Reset Store Data
                </button>
            </div>
        </div>
    </div>

</div>
</form>

</div><!-- /storeReset -->


<!-- ═══════════════════════════════════════════════════════════════
     TAB 2: Full Data Reset (original)
     ═══════════════════════════════════════════════════════════════ -->
<div class="tab-pane fade" id="fullReset" role="tabpanel">

<!-- Danger Banner -->
<div class="alert alert-danger border-0 shadow-sm mb-4">
    <h6 class="font-weight-bold mb-1"><i class="fas fa-skull-crossbones mr-2"></i>WARNING — Irreversible Action</h6>
    <ul class="mb-0 pl-3" style="font-size:.88rem;">
        <li>Selected tables will be permanently <strong>TRUNCATED</strong> (all rows deleted).</li>
        <li><strong>Users, Company Settings, HSN Master</strong> are protected and will never be reset.</li>
        <li>Take a database backup before proceeding.</li>
        <li>Admin password and confirmation phrase are required.</li>
    </ul>
</div>

<form method="post" action="<?= base_url('data_reset/execute') ?>" id="resetForm">

<div class="row">

    <!-- LEFT: Table Selection -->
    <div class="col-lg-8">

        <!-- Quick Select Buttons -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <span class="font-weight-bold mr-3">Quick Select:</span>
                <button type="button" class="btn btn-sm btn-outline-danger mr-1" onclick="selectGroup('transactions')">Transactions Only</button>
                <button type="button" class="btn btn-sm btn-outline-warning mr-1" onclick="selectGroup('stock')">Stock &amp; Logs</button>
                <button type="button" class="btn btn-sm btn-outline-dark mr-1" onclick="selectGroup('all')">All Data</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectGroup('none')">Clear All</button>
            </div>
        </div>

        <?php foreach ($table_groups as $group_name => $tables): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-dark text-white py-2 d-flex justify-content-between align-items-center">
                <span class="font-weight-bold" style="font-size:.9rem;"><?= $group_name ?></span>
                <div>
                    <button type="button" class="btn btn-xs btn-outline-light py-0 px-2"
                            onclick="toggleGroup('<?= md5($group_name) ?>', true)">All</button>
                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2"
                            onclick="toggleGroup('<?= md5($group_name) ?>', false)">None</button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.87rem;">
                    <thead class="thead-light">
                        <tr>
                            <th width="40px"></th>
                            <th>Table</th>
                            <th>Description</th>
                            <th class="text-right">Records</th>
                            <th>Depends On</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tables as $tbl => $info): ?>
                    <?php $cnt = $table_counts[$tbl] ?? 0; ?>
                    <tr class="<?= $cnt > 0 ? '' : 'text-muted' ?>" data-group="<?= md5($group_name) ?>">
                        <td class="text-center">
                            <input type="checkbox" name="tables[]" value="<?= $tbl ?>"
                                   id="tbl_<?= $tbl ?>" class="tbl-checkbox">
                        </td>
                        <td>
                            <label for="tbl_<?= $tbl ?>" class="mb-0 font-weight-bold" style="cursor:pointer;">
                                <code><?= $tbl ?></code>
                            </label>
                        </td>
                        <td><?= $info['label'] ?></td>
                        <td class="text-right">
                            <?php if ($cnt > 0): ?>
                                <span class="badge badge-<?= $cnt > 100 ? 'danger' : ($cnt > 0 ? 'warning' : 'secondary') ?>">
                                    <?= number_format($cnt) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-light text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($info['deps'])): ?>
                                <?php foreach ($info['deps'] as $d): ?>
                                <code class="text-danger" style="font-size:.75rem;"><?= $d ?></code>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

    </div>

    <!-- RIGHT: Confirmation Panel -->
    <div class="col-lg-4">
        <div class="card border-danger shadow" style="position:sticky;top:70px;">
            <div class="card-header bg-danger text-white font-weight-bold">
                <i class="fas fa-lock mr-1"></i> Confirm Reset
            </div>
            <div class="card-body">

                <!-- Selected Summary -->
                <div class="alert alert-light border mb-3" id="selectedSummary">
                    <small class="text-muted">No tables selected yet.</small>
                </div>

                <!-- Admin Password -->
                <div class="form-group">
                    <label class="font-weight-bold small">Admin Password <span class="text-danger">*</span></label>
                    <input type="password" name="admin_password" class="form-control form-control-sm border-danger"
                           placeholder="Enter sverpadmin password" required>
                    <small class="text-muted">Only sverpadmin is authorized to execute data reset.</small>
                </div>

                <!-- Confirm Phrase -->
                <div class="form-group">
                    <label class="font-weight-bold small">Type <code class="text-danger font-weight-bold">RESET</code> to confirm <span class="text-danger">*</span></label>
                    <input type="text" name="confirm_phrase" id="confirm_phrase" class="form-control form-control-sm border-danger"
                           placeholder="Type RESET here" autocomplete="off" required>
                </div>

                <!-- Protected Tables Note -->
                <div class="alert alert-info py-2 mb-3" style="font-size:.8rem;">
                    <strong>Always Protected:</strong><br>
                    users &bull; company_settings &bull; hsn_master &bull; ci_sessions
                </div>

                <button type="submit" id="submitBtn" class="btn btn-danger btn-block font-weight-bold"
                        onclick="return finalConfirm()">
                    <i class="fas fa-trash-alt mr-1"></i> Execute Reset
                </button>

                <div class="text-center mt-2">
                    <small class="text-muted">This action is <strong>irreversible</strong>.<br>Take a DB backup first.</small>
                </div>
            </div>
        </div>
    </div>

</div>
</form>

</div><!-- /fullReset -->

</div><!-- /tab-content -->

<script>
// ── Tab persistence ──
$(function(){
    var activeTab = localStorage.getItem('dataResetTab') || '#storeReset';
    $('a[href="' + activeTab + '"]').tab('show');
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
        localStorage.setItem('dataResetTab', $(e.target).attr('href'));
    });
});

// ══════════════════════════════════
// STORE RESET JS
// ══════════════════════════════════
// Highlight selected store card
document.querySelectorAll('.store-radio').forEach(function(r) {
    r.addEventListener('change', function() {
        document.querySelectorAll('.store-radio').forEach(function(x) {
            x.closest('label').classList.remove('border-primary','border-warning','bg-light');
        });
        if (this.checked) {
            this.closest('label').classList.add('border-primary','border-warning','bg-light');
        }
        updateStoreSummary();
    });
});

(function(){ var el=document.getElementById('confirm_phrase_store'); if(!el) return;
function chk(){
    var ok = el.value.trim().toUpperCase() === 'RESET';
    document.getElementById('storeSubmitBtn').disabled = !ok;
    el.classList.toggle('border-success', ok);
    el.classList.toggle('border-warning', !ok);
}
['input','change','keyup','paste','blur'].forEach(function(e){el.addEventListener(e,function(){setTimeout(chk,0);});});
chk(); setTimeout(chk,300); })();

function toggleStoreAll(state) {
    document.querySelectorAll('.store-tbl-checkbox').forEach(function(cb) { cb.checked = state; });
    updateStoreSummary();
}
document.querySelectorAll('.store-tbl-checkbox').forEach(function(cb) {
    cb.addEventListener('change', updateStoreSummary);
});

function updateStoreSummary() {
    var box = document.getElementById('storeSummary');
    var sel = document.querySelector('.store-radio:checked');
    var tbls = document.querySelectorAll('.store-tbl-checkbox:checked');
    if (!sel) {
        box.innerHTML = '<small class="text-muted">Select a store above.</small>';
        return;
    }
    var storeName = sel.closest('label').querySelector('.font-weight-bold').textContent.trim();
    var html = '<strong class="text-warning">' + storeName + '</strong><br>';
    html += '<span class="text-danger">' + tbls.length + ' table(s)</span> selected for reset';
    box.innerHTML = html;
}

function storeConfirm() {
    if (document.getElementById('confirm_phrase_store').value.trim().toUpperCase() !== 'RESET') { alert('Type RESET to confirm.'); return false; }
    var sel = document.querySelector('.store-radio:checked');
    if (!sel) { alert('Please select a store!'); return false; }
    var tbls = document.querySelectorAll('.store-tbl-checkbox:checked');
    if (tbls.length === 0) { alert('No tables selected!'); return false; }
    var storeName = sel.closest('label').querySelector('.font-weight-bold').textContent.trim();
    var tblNames = Array.from(tbls).map(function(cb){ return cb.value; }).join(', ');
    return confirm('⚠️ STORE RESET WARNING ⚠️\n\nYou are about to DELETE all transactions for:\n\n🏪 ' + storeName + '\n\nTables: ' + tblNames + '\n\nThis CANNOT be undone. Continue?');
}

// ══════════════════════════════════
// FULL RESET JS (original)
// ══════════════════════════════════
(function(){ var el=document.getElementById('confirm_phrase'); if(!el) return;
function chk(){
    var ok = el.value.trim().toUpperCase() === 'RESET';
    document.getElementById('submitBtn').disabled = !ok;
    el.classList.toggle('border-success', ok);
    el.classList.toggle('border-danger',  !ok);
}
['input','change','keyup','paste','blur'].forEach(function(e){el.addEventListener(e,function(){setTimeout(chk,0);});});
chk(); setTimeout(chk,300); })();

function finalConfirm() {
    if (document.getElementById('confirm_phrase').value.trim().toUpperCase() !== 'RESET') { alert('Type RESET to confirm.'); return false; }
    var selected = document.querySelectorAll('.tbl-checkbox:checked');
    if (selected.length === 0) { alert('No tables selected!'); return false; }
    var names = Array.from(selected).map(cb => cb.value).join(', ');
    return confirm('⚠️ FINAL WARNING ⚠️\n\nYou are about to PERMANENTLY DELETE all data from:\n\n' + names + '\n\nThis CANNOT be undone. Are you absolutely sure?');
}

function updateSummary() {
    var checked = document.querySelectorAll('.tbl-checkbox:checked');
    var box = document.getElementById('selectedSummary');
    if (checked.length === 0) {
        box.innerHTML = '<small class="text-muted">No tables selected yet.</small>';
    } else {
        var html = '<strong class="text-danger">' + checked.length + ' table(s) selected:</strong><br>';
        html += '<small>' + Array.from(checked).map(cb => '<code>' + cb.value + '</code>').join(', ') + '</small>';
        box.innerHTML = html;
    }
}

document.querySelectorAll('.tbl-checkbox').forEach(function(cb) {
    cb.addEventListener('change', updateSummary);
});

function toggleGroup(groupId, state) {
    document.querySelectorAll('[data-group="' + groupId + '"] .tbl-checkbox').forEach(function(cb) {
        cb.checked = state;
    });
    updateSummary();
}

function selectGroup(preset) {
    var transactionTables = ['invoices','invoice_items','invoice_edit_log','estimates','estimate_items','payments',
                             'purchase_orders','purchase_order_items','purchase_approvals',
                             'grn','grn_items','vendor_messages'];
    var stockTables       = ['stock_ledger','stock_adjustments','audit_logs','supplier_visit_logs','supplier_ledger'];

    document.querySelectorAll('.tbl-checkbox').forEach(function(cb) { cb.checked = false; });

    if (preset === 'transactions') {
        transactionTables.forEach(function(t) {
            var el = document.getElementById('tbl_' + t);
            if (el) el.checked = true;
        });
    } else if (preset === 'stock') {
        stockTables.forEach(function(t) {
            var el = document.getElementById('tbl_' + t);
            if (el) el.checked = true;
        });
    } else if (preset === 'all') {
        document.querySelectorAll('.tbl-checkbox').forEach(function(cb) { cb.checked = true; });
    }
    updateSummary();
}
</script>

<style>
.store-radio:checked + i { color: #e65100 !important; }
label:has(.store-radio:checked) { background: #fff8e1 !important; border-color: #ff9800 !important; }
</style>
