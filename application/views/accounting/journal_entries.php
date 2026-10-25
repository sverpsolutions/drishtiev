<?php // FILE: application/views/accounting/journal_entries.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold">Journal Entries</h5>
        <small class="text-muted">All postings — automatic and manual</small>
    </div>
    <div>
        <a href="<?= base_url('accounting/chart_of_accounts') ?>" class="btn btn-outline-dark btn-sm">Chart of Accounts</a>
        <a href="<?= base_url('accounting/journal_create') ?>" class="btn btn-dark btn-sm ml-1">+ New Journal Entry</a>
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
        <form method="get" class="form-row align-items-end">
            <div class="col-auto form-group mb-0">
                <label class="small font-weight-bold mb-0">From</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="col-auto form-group mb-0">
                <label class="small font-weight-bold mb-0">To</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="col-auto form-group mb-0">
                <button class="btn btn-outline-dark btn-sm">Filter</button>
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
                        <th>Entry No</th>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Narration</th>
                        <th class="text-right">Amount</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($entries)): foreach ($entries as $e): ?>
                    <tr>
                        <td><a href="<?= base_url('accounting/journal_view/' . $e->id) ?>"><?= htmlspecialchars($e->entry_no) ?></a></td>
                        <td><?= date('d M Y', strtotime($e->entry_date)) ?></td>
                        <td><span class="badge badge-light border text-capitalize"><?= str_replace('_', ' ', $e->source) ?></span></td>
                        <td><?= htmlspecialchars($e->narration ?? '') ?></td>
                        <td class="text-right">₹<?= number_format($e->total_amount, 2) ?></td>
                        <td class="text-center">
                            <?php if ($e->status === 'reversed'): ?>
                                <span class="badge badge-secondary">Reversed</span>
                            <?php else: ?>
                                <span class="badge badge-success">Posted</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No journal entries in this date range.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
