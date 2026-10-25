<?php // FILE: application/views/purchases/index.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Purchases</h5>
        <small class="text-muted">Stock inward &amp; purchase records</small>
    </div>
    <a href="<?= base_url('purchases/drafts') ?>" class="btn btn-outline-secondary btn-sm">Drafts</a>
    <a href="<?= base_url('purchases/create') ?>" class="btn btn-dark btn-sm">+ New Purchase</a>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<!-- Advanced Search Panel -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= base_url('purchases') ?>" class="row align-items-end g-2">
            <div class="col-md-3">
                <label class="small text-muted mb-1">Supplier</label>
                <select name="supplier_id" class="form-control form-control-sm select2">
                    <option value="">-- All Suppliers --</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?= $sup->id ?>" <?= (isset($filters['supplier_id']) && $filters['supplier_id'] == $sup->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sup->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted mb-1">Supplier Code</label>
                <input type="text" name="supplier_code" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['supplier_code'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small text-muted mb-1">Invoice No</label>
                <input type="text" name="invoice_no" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['invoice_no'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="small text-muted mb-1">Invoice Date</label>
                <input type="date" name="invoice_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['invoice_date'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="small text-muted mb-1">Date Range (From - To)</label>
                <div class="input-group input-group-sm">
                    <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($filters['from_date'] ?? '') ?>">
                    <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($filters['to_date'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-12 mt-2 text-right">
                <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm">Search</button>
                <a href="<?= base_url('purchases') ?>" class="btn btn-outline-secondary btn-sm px-3 shadow-sm ml-1">Reset Filters</a>
            </div>
        </form>
    </div>
</div>

<div class="mb-2 text-muted small">
    <strong><?= count($purchases) ?></strong> records found.
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.85rem;">
                <thead class="thead-dark">
                    <tr>
                        <th>Purchase #</th>
                        <th>Supplier</th>
                        <th>Supplier Inv #</th>
                        <th>Date</th>
                        <th class="text-right">Subtotal</th>
                        <th class="text-right">GST</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Due</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($purchases)): ?>
                    <?php foreach ($purchases as $p): ?>
                    <tr>
                        <td><a href="<?= base_url('purchases/view/'.$p->id) ?>" class="font-weight-bold"><?= $p->purchase_no ?></a></td>
                        <td><?= htmlspecialchars($p->supplier_name) ?></td>
                        <td><?= htmlspecialchars($p->invoice_no ?? '—') ?></td>
                        <td><?= date('d M Y', strtotime($p->invoice_date)) ?></td>
                        <td class="text-right">₹ <?= number_format($p->subtotal, 2) ?></td>
                        <td class="text-right">₹ <?= number_format($p->total_gst, 2) ?></td>
                        <td class="text-right font-weight-bold">₹ <?= number_format($p->total_amount, 2) ?></td>
                        <td class="text-right <?= $p->due_amount > 0 ? 'text-danger' : 'text-success' ?>">
                            ₹ <?= number_format($p->due_amount, 2) ?>
                        </td>
                        <td>
                            <?php
                            $badges = ['received'=>'success','partial'=>'warning','pending'=>'danger','returned'=>'secondary','draft'=>'secondary'];
                            $b = $badges[$p->status] ?? 'secondary';
                            ?>
                            <span class="badge badge-<?= $b ?>"><?= ucfirst($p->status) ?></span>
                        </td>
                        <td class="text-center">
                            <a href="<?= base_url('purchases/view/'.$p->id) ?>" class="btn btn-sm btn-outline-secondary py-0 px-2">View</a>
                            <?php if ($p->status === 'draft'): ?>
                            <a href="<?= base_url('purchases/edit/'.$p->id) ?>" class="btn btn-sm btn-outline-primary py-0 px-2">Edit</a>
                            <?php endif; ?>
                            <a href="<?= base_url('purchases/return_create/'.$p->id) ?>" class="btn btn-sm btn-outline-warning py-0 px-2">PRN</a>
                            <a href="<?= base_url('purchases/print_po/'.$p->id) ?>" class="btn btn-sm btn-outline-info py-0 px-2" target="_blank">Print</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No purchases yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
