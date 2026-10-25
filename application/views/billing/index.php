<?php // FILE: application/views/billing/index.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Sales Invoices</h5>
        <small class="text-muted">GST invoice management</small>
    </div>
    <a href="<?= base_url('billing/create') ?>" class="btn btn-dark btn-sm">+ New Invoice</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<!-- Counter-wise totals -->
<?php if (!empty($counter_totals)): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap" style="gap:.75rem;">
            <?php if (!empty($grand_total)): ?>
            <div class="border rounded px-3 py-2 bg-dark text-white" style="min-width:150px;">
                <div class="small text-uppercase font-weight-bold" style="font-size:.72rem; opacity:.75;">Total</div>
                <div class="font-weight-bold">₹ <?= number_format($grand_total['total'], 2) ?></div>
                <div class="small" style="opacity:.75;"><?= $grand_total['count'] ?> bill<?= $grand_total['count'] == 1 ? '' : 's' ?></div>
            </div>
            <?php endif; ?>
            <?php foreach ($counter_totals as $label => $t): ?>
            <div class="border rounded px-3 py-2" style="min-width:150px;">
                <div class="small text-muted text-uppercase font-weight-bold" style="font-size:.72rem;"><?= htmlspecialchars($label) ?></div>
                <div class="font-weight-bold">₹ <?= number_format($t['total'], 2) ?></div>
                <div class="small text-muted"><?= $t['count'] ?> bill<?= $t['count'] == 1 ? '' : 's' ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filter bar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= base_url('billing') ?>" class="row align-items-center">
            <div class="col-md-2">
                <label class="small text-muted mb-0">From</label>
                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="small text-muted mb-0">To</label>
                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control form-control-sm">
            </div>
            <?php if (count($locations ?? []) > 1): ?>
            <div class="col-md-2">
                <label class="small text-muted mb-0">Outlet</label>
                <select name="location_id" class="form-control form-control-sm" style="min-width:180px; max-width:220px; flex-shrink:0; font-size:.8rem;">
                    <option value="">All Outlets</option>
                    <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc->id ?>" <?= ($location_id ?? 0) == $loc->id ? 'selected' : '' ?>><?= htmlspecialchars($loc->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2 d-flex align-items-end h-100">
                <button type="submit" class="btn btn-dark btn-sm btn-block">Filter</button>
            </div>
            <div class="col-md-2 d-flex align-items-end h-100">
                <a href="<?= base_url('billing') ?>" class="btn btn-outline-secondary btn-sm btn-block">Today</a>
            </div>
        </form>
        <div class="row mt-2">
            <div class="col-md-5"><input type="text" id="invSearch" class="form-control form-control-sm" placeholder="Search by invoice no, customer..."></div>
            <div class="col-md-2">
                <select id="statusFilter" class="form-control form-control-sm">
                    <option value="">All Status</option>
                    <option value="paid">Paid</option>
                    <option value="partial">Partial</option>
                    <option value="unpaid">Unpaid</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="invTable" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Outlet</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th class="text-right">Taxable</th>
                        <th class="text-right">GST</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Due</th>
                        <th>Status</th>
                        <th>Payment Mode</th>
                        <th>Counter</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($invoices)): ?>
                    <?php
                    $pmBadge = ['cash'=>'success','upi'=>'info','card'=>'primary','neft'=>'secondary','cheque'=>'secondary','credit'=>'danger','split'=>'warning'];
                    $pmLabel = [];
                    if (!empty($payment_modes)) {
                        foreach ($payment_modes as $pm) {
                            $pmLabel[$pm->code] = $pm->name;
                            if (!isset($pmBadge[$pm->code])) {
                                $pmBadge[$pm->code] = $pm->is_credit ? 'danger' : ($pm->is_cash_type ? 'success' : 'dark');
                            }
                        }
                    }
                    ?>
                    <?php foreach ($invoices as $inv): ?>
                    <tr data-status="<?= $inv->status ?>">
                        <td><a href="<?= base_url('billing/invoice/'.$inv->id) ?>" class="font-weight-bold"><?= $inv->invoice_no ?></a></td>
                        <td><?= htmlspecialchars($inv->customer_name) ?></td>
                        <td><small><?= htmlspecialchars($inv->location_name ?? '—') ?></small></td>
                        <td><span class="badge badge-<?= $inv->invoice_type === 'wholesale' ? 'primary':'secondary' ?>"><?= ucfirst($inv->invoice_type) ?></span></td>
                        <td><?= date('d M Y', strtotime($inv->invoice_date)) ?><br><small class="text-muted"><?= !empty($inv->created_at) ? date('h:i A', strtotime($inv->created_at)) : '' ?></small></td>
                        <td class="text-right">₹ <?= number_format($inv->taxable_amount, 2) ?></td>
                        <td class="text-right">₹ <?= number_format($inv->total_gst, 2) ?></td>
                        <td class="text-right font-weight-bold">₹ <?= number_format($inv->total_amount, 2) ?></td>
                        <td class="text-right <?= $inv->due_amount > 0 ? 'text-danger':($inv->due_amount < 0 ? 'text-success':'') ?>">₹ <?= number_format(max(0, $inv->due_amount), 2) ?></td>
                        <td>
                            <?php
                            $sbadge = ['paid'=>'success','partial'=>'warning','unpaid'=>'danger','cancelled'=>'secondary'];
                            ?>
                            <span class="badge badge-<?= $sbadge[$inv->status] ?? 'secondary' ?>"><?= ucfirst($inv->status) ?></span>
                        </td>
                        <td>
                            <?php $pm_code = $inv->payment_mode; ?>
                            <span class="badge badge-<?= $pmBadge[$pm_code] ?? 'dark' ?>"><?= htmlspecialchars($pmLabel[$pm_code] ?? ($pm_code !== '' ? strtoupper($pm_code) : '—')) ?></span>
                            <?php if (($this->session->userdata('role') === 'admin' || $this->session->userdata('role') === 'superadmin' || !empty($allow_payment_mode_change)) && $inv->payment_mode !== 'split' && $inv->status !== 'cancelled'): ?>
                            <a href="#" class="ml-1" title="Change payment mode" onclick="openPmModal(<?= $inv->id ?>, '<?= $inv->invoice_no ?>', '<?= $inv->payment_mode ?>'); return false;"><i class="fas fa-edit"></i></a>
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($inv->device_label) ? htmlspecialchars($inv->device_label) : '—' ?></td>
                        <td class="text-center">
                            <a href="<?= base_url('billing/invoice/'.$inv->id) ?>" class="btn btn-sm btn-outline-secondary py-0 px-2">View</a>
                            <a href="<?= base_url('billing/print_invoice/'.$inv->id) ?>" class="btn btn-sm btn-outline-dark py-0 px-2" target="_blank">Print</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="13" class="text-center text-muted py-4">No invoices yet. <a href="<?= base_url('billing/create') ?>">Create one.</a></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Change Payment Mode Modal -->
<div class="modal fade" id="pmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="pmForm" method="post" action="">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Change Payment Mode — <span id="pmInvoiceNo"></span></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Only the payment mode changes. Items, totals, and amounts stay exactly the same — use this to correct a cashier's mis-punched mode (e.g. Cash instead of Card).</p>
                    <div class="form-group mb-2">
                        <label class="font-weight-bold small">Payment Mode</label>
                        <select name="payment_mode" id="pmSelect" class="form-control form-control-sm">
                            <?php if (!empty($payment_modes)): foreach ($payment_modes as $pm): ?>
                            <option value="<?= $pm->code ?>"><?= htmlspecialchars($pm->name) ?></option>
                            <?php endforeach; else: ?>
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="card">Card</option>
                            <option value="neft">Bank / NEFT</option>
                            <option value="cheque">Cheque</option>
                            <option value="credit">Credit</option>
                            <?php endif; ?>
                            <option value="split">Split (Multiple)</option>
                        </select>
                    </div>
                    
                    <div id="pmSplitDiv" style="display:none;" class="mb-2 p-2 border rounded bg-light">
                        <label class="font-weight-bold small mb-1">Enter Split Amounts</label>
                        <?php
                        $split_modes = !empty($payment_modes) ? array_filter($payment_modes, function($m) { return $m->show_in_split && !$m->is_credit; }) : [];
                        for ($si = 0; $si < min(3, max(2, count($split_modes))); $si++):
                        ?>
                        <div class="row mb-1">
                            <div class="col-6">
                                <select name="split_mode[]" class="form-control form-control-sm">
                                    <?php $sj = 0; foreach ($split_modes as $sm): ?>
                                    <option value="<?= $sm->code ?>"<?= $sj === $si ? ' selected' : '' ?>><?= htmlspecialchars($sm->name) ?></option>
                                    <?php $sj++; endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6"><input type="number" step="0.01" name="split_amount[]" class="form-control form-control-sm" placeholder="Amount"></div>
                        </div>
                        <?php endfor; ?>
                        <small class="text-muted d-block mt-1">Leave amount blank for unused modes. Total must match bill amount.</small>
                    </div>
                    <div class="form-group mb-2">
                        <label class="font-weight-bold small">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control form-control-sm" rows="2" placeholder="Mandatory reason for changing payment mode" required></textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold small">Remarks (Optional)</label>
                        <textarea name="remarks" class="form-control form-control-sm" rows="1" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-dark btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openPmModal(id, invoice_no, current_mode) {
    $('#pmForm').attr('action', '<?= base_url("billing/update_payment_mode/") ?>' + id);
    $('#pmInvoiceNo').text(invoice_no);
    $('#pmSelect').val(current_mode);
    togglePmSplit();
    $('#pmModal').modal('show');
}

function togglePmSplit() {
    if ($('#pmSelect').val() === 'split') {
        $('#pmSplitDiv').slideDown('fast');
    } else {
        $('#pmSplitDiv').slideUp('fast');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    $('#pmSelect').on('change', togglePmSplit);
});
</script>
<script>
document.getElementById('invSearch').addEventListener('input', filterInv);
document.getElementById('statusFilter').addEventListener('change', filterInv);
function filterInv() {
    const q = document.getElementById('invSearch').value.toLowerCase();
    const s = document.getElementById('statusFilter').value;
    document.querySelectorAll('#invTable tbody tr').forEach(r => {
        r.style.display = (r.textContent.toLowerCase().includes(q) && (!s || r.dataset.status === s)) ? '' : 'none';
    });
}
</script>
