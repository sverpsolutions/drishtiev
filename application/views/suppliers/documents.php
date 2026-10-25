<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-folder-open text-warning mr-2"></i>Documents — <?= htmlspecialchars($supplier->name) ?></h5>
    <a href="<?= base_url('suppliers/view/'.$supplier->id) ?>" class="btn btn-sm btn-outline-secondary">← Back</a>
</div>

<?php
$expiring = array_filter($documents??[], function($d) {
    return $d->expiry_date && (new DateTime($d->expiry_date)) <= (new DateTime('+30 days'));
});
if (!empty($expiring)):
?>
<div class="alert alert-warning mb-3">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    <?= count($expiring) ?> document(s) expiring within 30 days!
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 font-weight-bold">Documents</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" style="font-size:.8rem;">
                    <thead class="thead-light"><tr><th>Document</th><th>Type</th><th>Expiry</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (empty($documents)): ?><tr><td colspan="5" class="text-center text-muted py-3">No documents uploaded.</td></tr><?php endif; ?>
                    <?php foreach ($documents??[] as $doc): ?>
                    <?php
                    $exp_cls = 'success'; $exp_label = 'Valid';
                    if ($doc->expiry_date) {
                        $days = (new DateTime())->diff(new DateTime($doc->expiry_date))->days;
                        $future = (new DateTime($doc->expiry_date)) >= new DateTime();
                        if (!$future) { $exp_cls='danger'; $exp_label='Expired'; }
                        elseif ($days<=30) { $exp_cls='warning'; $exp_label='Exp. soon'; }
                    } else { $exp_cls='secondary'; $exp_label='No expiry'; }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($doc->doc_name) ?></td>
                        <td><span class="badge badge-light border"><?= htmlspecialchars($doc->doc_type??'-') ?></span></td>
                        <td><?= $doc->expiry_date ? date('d M Y',strtotime($doc->expiry_date)) : '-' ?></td>
                        <td><span class="badge badge-<?= $exp_cls ?>"><?= $exp_label ?></span></td>
                        <td><a href="<?= base_url('uploads/supplier_docs/'.$doc->file_path) ?>" target="_blank" class="btn btn-xs btn-outline-primary"><i class="fas fa-download"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3">
            <div class="font-weight-bold mb-2">Upload Document</div>
            <?= form_open_multipart('suppliers/upload_document/'.$supplier->id) ?>
            <div class="form-group">
                <label class="small">Document Name</label>
                <input name="doc_name" class="form-control form-control-sm" required placeholder="e.g. GST Certificate">
            </div>
            <div class="form-group">
                <label class="small">Type</label>
                <select name="doc_type" class="form-control form-control-sm">
                    <option>GST Certificate</option><option>PAN Card</option><option>Trade License</option>
                    <option>Bank Details</option><option>Product Catalog</option><option>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label class="small">Expiry Date (if applicable)</label>
                <input name="expiry_date" type="date" class="form-control form-control-sm">
            </div>
            <div class="form-group">
                <label class="small">File</label>
                <input name="document_file" type="file" class="form-control-file form-control-sm" required>
            </div>
            <button type="submit" class="btn btn-sm btn-dark w-100"><i class="fas fa-upload mr-1"></i>Upload</button>
            <?= form_close() ?>
        </div>
    </div>
</div>
<style>.btn-xs{padding:2px 6px;font-size:.72rem;}</style>
