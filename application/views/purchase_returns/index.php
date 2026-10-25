<?php // Purchase Returns / Debit Notes - Index ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-arrow-return-right text-warning mr-2"></i>Purchase Returns / Debit Notes</h4>
        <small class="text-muted">Returns to Supplier | Debit Notes issued to Supplier</small>
    </div>
    <a href="<?= base_url('purchase_returns/create') ?>" class="btn btn-warning">
        <i class="bi bi-plus-lg mr-1"></i>New Debit Note
    </a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle mr-2"></i><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle mr-2"></i><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="mb-2">
            <div class="input-group input-group-sm">
                <div class="input-group-prepend"><span class="input-group-text"><i class="bi bi-search"></i></span></div>
                <input type="text" id="prnSearch" class="form-control" placeholder="Search by Debit Note No or Supplier name..." oninput="filterPRN()">
            </div>
        </div>
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label mb-1 small">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= $from ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1 small">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= $to ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1 small">Supplier</label>
                <select name="supplier_id" class="form-control form-control-sm">
                    <option value="">All Suppliers</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?= $sup->id ?>" <?= $supplier_id == $sup->id ? 'selected':'' ?>><?= htmlspecialchars($sup->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1 small">Status</label>
                <select name="status" class="form-control form-control-sm" onchange="filterPRN()">
                    <option value="">All Status</option>
                    <option value="confirmed" <?= (isset($_GET['status']) && $_GET['status']==='confirmed')?'selected':'' ?>>Confirmed</option>
                    <option value="draft" <?= (isset($_GET['status']) && $_GET['status']==='draft')?'selected':'' ?>>Draft</option>
                    <option value="cancelled" <?= (isset($_GET['status']) && $_GET['status']==='cancelled')?'selected':'' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3 d-flex" style="gap:4px;">
                <button class="btn btn-primary btn-sm flex-fill"><i class="bi bi-search mr-1"></i>Filter</button>
                <a href="<?= base_url('purchase_returns') ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary -->
<?php
    $total_dn  = count($returns);
    $total_amt = array_sum(array_column(array_map('get_object_vars', $returns), 'total_amount'));
    $total_gst = array_sum(array_column(array_map('get_object_vars', $returns), 'total_gst'));
?>
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card bg-warning text-dark shadow-sm"><div class="card-body py-2">
        <div class="small opacity-75">Total Debit Notes</div><div class="fs-4 font-weight-bold"><?= $total_dn ?></div>
    </div></div></div>
    <div class="col-md-4"><div class="card bg-info text-white shadow-sm"><div class="card-body py-2">
        <div class="small opacity-75">Total GST Reversed</div><div class="fs-5 font-weight-bold">₹<?= number_format($total_gst, 2) ?></div>
    </div></div></div>
    <div class="col-md-4"><div class="card bg-dark text-white shadow-sm"><div class="card-body py-2">
        <div class="small opacity-75">Total Return Value</div><div class="fs-5 font-weight-bold">₹<?= number_format($total_amt, 2) ?></div>
    </div></div></div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-file-earmark-text mr-1"></i>Debit Notes (<?= count($returns) ?>)</span>
        <button class="btn btn-outline-success btn-sm" onclick="exportTable()"><i class="bi bi-file-excel mr-1"></i>Export</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0" id="dnTable">
                <thead class="table-warning">
                    <tr>
                        <th>#</th>
                        <th>Debit Note No</th>
                        <th>Supplier</th>
                        <th>Against Purchase</th>
                        <th>Date</th>
                        <th>Reason</th>
                        <th class="text-right">Taxable</th>
                        <th class="text-right">GST</th>
                        <th class="text-right">Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($returns)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4"><i class="bi bi-inbox fs-2 d-block mb-1"></i>No debit notes found</td></tr>
                <?php else: ?>
                <?php foreach ($returns as $i => $r): ?>
                    <tr class="<?= $r->status === 'cancelled' ? 'table-secondary' : '' ?>">
                        <td><?= $i+1 ?></td>
                        <td><a href="<?= base_url('purchase_returns/view/'.$r->id) ?>" class="font-weight-bold text-warning"><?= htmlspecialchars($r->return_number) ?></a></td>
                        <td><?= htmlspecialchars($r->supplier_name) ?></td>
                        <td><?= $r->original_purchase_no ? '<span class="badge badge-secondary">'.htmlspecialchars($r->original_purchase_no).'</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td><?= date('d/m/Y', strtotime($r->return_date)) ?></td>
                        <td><small><?= htmlspecialchars($r->reason) ?></small></td>
                        <td class="text-right">₹<?= number_format($r->taxable_amount, 2) ?></td>
                        <td class="text-right">₹<?= number_format($r->total_gst, 2) ?></td>
                        <td class="text-right font-weight-bold text-warning">₹<?= number_format($r->total_amount, 2) ?></td>
                        <td>
                            <?php if ($r->status === 'confirmed'): ?><span class="badge badge-success">Confirmed</span>
                            <?php elseif ($r->status === 'cancelled'): ?><span class="badge badge-secondary">Cancelled</span>
                            <?php else: ?><span class="badge badge-warning">Draft</span><?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= base_url('purchase_returns/view/'.$r->id) ?>" class="btn btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                                <a href="<?= base_url('purchase_returns/print_debit_note/'.$r->id) ?>" class="btn btn-outline-secondary" title="Print" target="_blank"><i class="bi bi-printer"></i></a>
                                <?php if ($r->status !== 'cancelled'): ?>
                                    <a href="<?= base_url('purchase_returns/edit/'.$r->id) ?>" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <a href="#" class="btn btn-outline-danger" title="Cancel" onclick="confirmCancel(<?= $r->id ?>,'<?= htmlspecialchars($r->return_number) ?>')"><i class="bi bi-x-circle"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterPRN() {
    const q = (document.getElementById('prnSearch')?.value || '').toLowerCase();
    const statusSel = document.querySelector('select[name="status"]')?.value || '';
    document.querySelectorAll('#dnTable tbody tr').forEach(function(row) {
        const text = row.textContent.toLowerCase();
        const badge = row.querySelector('.badge')?.textContent.toLowerCase() || '';
        const textMatch = !q || text.includes(q);
        const statusMatch = !statusSel || badge.includes(statusSel);
        row.style.display = (textMatch && statusMatch) ? '' : 'none';
    });
}

function confirmCancel(id, num) {
    if (confirm('Cancel Debit Note ' + num + '?\n\nThis will reverse the stock adjustment.')) {
        window.location = '<?= base_url("purchase_returns/cancel/") ?>' + id;
    }
}
function exportTable() {
    const rows = document.querySelectorAll('#dnTable tr');
    let csv = [];
    rows.forEach(r => {
        let cols = Array.from(r.querySelectorAll('th,td')).slice(0,-1);
        csv.push(cols.map(c => '"'+c.innerText.trim().replace(/"/g,'""')+'"').join(','));
    });
    const blob = new Blob([csv.join('\n')], {type:'text/csv'});
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
    a.download = 'debit_notes_<?= $from ?>_<?= $to ?>.csv'; a.click();
}
</script>
