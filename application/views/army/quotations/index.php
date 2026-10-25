<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Army Quotations</h5>
        <small class="text-muted">All quotations</small>
    </div>
    <div>
        <a href="<?= base_url('army_company_master') ?>" class="btn btn-outline-dark btn-sm">Company Master</a>
        <a href="<?= base_url('army_quotations/create') ?>" class="btn btn-dark btn-sm">+ New Quotation</a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row align-items-end g-2">
            <div class="col-md-2">
                <label class="small mb-0">Quotation No</label>
                <input type="text" name="quotation_no" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['quotation_no'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Customer</label>
                <input type="text" name="customer_name" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['customer_name'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Company</label>
                <select name="company_id" class="form-control form-control-sm">
                    <option value="">All Companies</option>
                    <?php foreach ($companies as $c): ?>
                    <option value="<?= $c->id ?>" <?= ($filters['company_id'] ?? '')==$c->id?'selected':'' ?>><?= htmlspecialchars($c->company_name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Mobile</label>
                <input type="text" name="mobile" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['mobile'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">Status</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">All Status</option>
                    <?php foreach (['draft','sent','accepted','rejected','expired'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '')==$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-dark btn-sm btn-block">Filter</button>
            </div>
            <div class="col-md-2">
                <label class="small mb-0">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small mb-0">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['to'] ?? '') ?>">
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Quotation No</th>
                        <th>Date</th>
                        <th>Company</th>
                        <th>Customer</th>
                        <th>Mobile</th>
                        <th class="text-right">Grand Total</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($quotations)): $sbadge = ['draft'=>'secondary','sent'=>'info','accepted'=>'success','rejected'=>'danger','expired'=>'dark']; ?>
                    <?php foreach ($quotations as $q): ?>
                    <tr>
                        <td><a href="<?= base_url('army_quotations/view/'.$q->id) ?>" class="font-weight-bold"><?= htmlspecialchars($q->quotation_no) ?></a></td>
                        <td><?= date('d M Y', strtotime($q->quotation_date)) ?></td>
                        <td><?= htmlspecialchars($q->company_name) ?></td>
                        <td><?= htmlspecialchars($q->customer_name) ?></td>
                        <td><?= htmlspecialchars($q->mobile ?? '—') ?></td>
                        <td class="text-right">₹ <?= number_format($q->grand_total, 2) ?></td>
                        <td><span class="badge badge-<?= $sbadge[$q->status] ?? 'secondary' ?>"><?= ucfirst($q->status) ?></span></td>
                        <td class="text-center">
                            <a href="<?= base_url('army_quotations/view/'.$q->id) ?>" class="btn btn-sm btn-outline-secondary py-0 px-1" title="View">View</a>
                            <a href="<?= base_url('army_quotations/edit/'.$q->id) ?>" class="btn btn-sm btn-outline-primary py-0 px-1" title="Edit">Edit</a>
                            <a href="<?= base_url('army_quotations/print/'.$q->id) ?>" class="btn btn-sm btn-outline-dark py-0 px-1" target="_blank" title="Print">Print</a>
                            <a href="<?= base_url('army_quotations/view/'.$q->id.'?open_email=1') ?>" class="btn btn-sm btn-outline-info py-0 px-1" title="Email">Email</a>
                            <a href="<?= base_url('army_quotations/duplicate/'.$q->id) ?>" class="btn btn-sm btn-outline-warning py-0 px-1" title="Duplicate">Duplicate</a>
                            <a href="<?= base_url('army_quotations/delete/'.$q->id) ?>" class="btn btn-sm btn-outline-danger py-0 px-1" title="Delete" onclick="return confirm('Delete this quotation?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No quotations found. <a href="<?= base_url('army_quotations/create') ?>">Create one.</a></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
