<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="fas fa-file-invoice-dollar"></i> Wholesale Invoices</h4>
        <div>
            <a href="<?= base_url('wholesale_billing/api_logs') ?>" class="btn btn-outline-secondary btn-sm mr-1">
                <i class="fas fa-list-alt"></i> API Logs
            </a>
            <a href="<?= base_url('wholesale_billing/create') ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> New Wholesale Invoice
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header py-2">
            <form method="get" class="form-inline">
                <label class="mr-2">From</label>
                <input type="date" name="from" value="<?= $from ?>" class="form-control form-control-sm mr-2">
                <label class="mr-2">To</label>
                <input type="date" name="to" value="<?= $to ?>" class="form-control form-control-sm mr-2">
                <button class="btn btn-sm btn-info">Filter</button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Invoice No</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>GSTIN</th>
                            <th class="text-right">Amount</th>
                            <th>Status</th>
                            <th>E-Invoice</th>
                            <th>E-Way Bill</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr><td colspan="10" class="text-center py-4 text-muted">No wholesale invoices found</td></tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $i => $inv): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><strong><?= htmlspecialchars($inv->invoice_no) ?></strong></td>
                                <td><?= date('d M Y', strtotime($inv->invoice_date)) ?></td>
                                <td><?= htmlspecialchars($inv->customer_name) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($inv->gst_number) ?></small></td>
                                <td class="text-right font-weight-bold"><?= number_format($inv->total_amount, 2) ?></td>
                                <td>
                                    <?php
                                    $badge_map = ['paid'=>'success','unpaid'=>'warning','partial'=>'info','cancelled'=>'danger','draft'=>'secondary'];
                                    $badge = $badge_map[$inv->status] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-<?= $badge ?>"><?= ucfirst($inv->status) ?></span>
                                </td>
                                <td>
                                    <?php if (($inv->einvoice_status ?? '') === 'cancelled'): ?>
                                        <span class="badge badge-danger">Cancelled</span>
                                    <?php elseif (!empty($inv->einvoice_irn)): ?>
                                        <span class="badge badge-success" title="<?= htmlspecialchars($inv->einvoice_irn) ?>">IRN</span>
                                    <?php else: ?>
                                        <span class="badge badge-light">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (($inv->ewb_status ?? '') === 'cancelled'): ?>
                                        <span class="badge badge-danger">Cancelled</span>
                                    <?php elseif (!empty($inv->ewb_no)): ?>
                                        <span class="badge badge-success" title="EWB #<?= $inv->ewb_no ?>"><?= $inv->ewb_no ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-light">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= base_url('wholesale_billing/view/' . $inv->id) ?>" class="btn btn-xs btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header py-2"><strong>GSP Connection Test</strong></div>
        <div class="card-body py-2">
            <button class="btn btn-sm btn-outline-info mr-2" onclick="testGsp('einvoice')"><i class="fas fa-plug"></i> Test E-Invoice API</button>
            <button class="btn btn-sm btn-outline-info" onclick="testGsp('ewb')"><i class="fas fa-plug"></i> Test E-Way Bill API</button>
            <span id="gspTestResult" class="ml-2"></span>
        </div>
    </div>
</div>

<script>
function testGsp(type) {
    document.getElementById('gspTestResult').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    fetch('<?= base_url('wholesale_billing/test_connection_ajax') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'api_type=' + type,
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('gspTestResult').innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> ' + d.message + '</span>';
        } else {
            document.getElementById('gspTestResult').innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> ' + d.message + '</span>';
        }
    })
    .catch(e => {
        document.getElementById('gspTestResult').innerHTML = '<span class="text-danger">Connection error</span>';
    });
}
</script>
