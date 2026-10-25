<?php // FILE: application/views/marketing/template_edit.php
$vars = json_decode($template->variables ?? '[]', true) ?: [];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> Edit Template</h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / <a href="<?= base_url('marketing/templates') ?>">Templates</a> / <?= htmlspecialchars($template->name) ?></small>
    </div>
    <a href="<?= base_url('marketing/templates') ?>" class="btn btn-outline-dark btn-sm">Back to Templates</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <form method="post" action="<?= base_url('marketing/template_edit/' . $template->id) ?>">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Template Details</div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Internal Name *</label>
                        <input type="text" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($template->name) ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Category</label>
                            <select name="category" class="form-control form-control-sm">
                                <?php foreach (['marketing', 'utility', 'authentication'] as $c): ?>
                                    <option value="<?= $c ?>" <?= $template->category === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Language Code</label>
                            <input type="text" name="language" class="form-control form-control-sm" value="<?= htmlspecialchars($template->language) ?>" placeholder="en / en_US / hi">
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Status *</label>
                            <select name="status" class="form-control form-control-sm">
                                <?php foreach (['draft' => 'Draft', 'pending_approval' => 'Pending Approval', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $template->status === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Meta Template Name</label>
                        <input type="text" name="meta_template_name" class="form-control form-control-sm" value="<?= htmlspecialchars($template->meta_template_name ?? '') ?>" placeholder="exact name as approved in Meta Business Manager">
                        <small class="text-muted">Required before this template can be marked Approved. Must exactly match the name registered with Meta.</small>
                    </div>
                    <div class="form-row">
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Header Type</label>
                            <select name="header_type" class="form-control form-control-sm">
                                <?php foreach (['none' => 'None', 'text' => 'Text', 'image' => 'Image'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $template->header_type === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8 form-group">
                            <label class="font-weight-bold">Header Content</label>
                            <input type="text" name="header_content" class="form-control form-control-sm" value="<?= htmlspecialchars($template->header_content ?? '') ?>" placeholder="header text, or an image URL">
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Footer Text</label>
                        <input type="text" name="footer_text" class="form-control form-control-sm" maxlength="60" value="<?= htmlspecialchars($template->footer_text ?? '') ?>">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-dark">Save Template</button>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Body Text <span class="text-muted font-weight-normal">(set when the campaign was created — not editable here)</span></div>
            <div class="card-body">
                <div class="p-2 rounded" style="background:#e9f8ee;white-space:pre-wrap;font-size:.85rem;"><?= htmlspecialchars($template->body_text) ?></div>
                <?php if ($vars): ?>
                <p class="small text-muted mt-2 mb-0">Variables (sent in this order as WhatsApp template parameters {{1}}, {{2}}, ...):
                    <?php foreach ($vars as $i => $v): ?><code class="mr-1"><?= $i + 1 ?>: <?= htmlspecialchars($v) ?></code><?php endforeach; ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm border-warning">
            <div class="card-body small">
                <strong>Before marking Approved:</strong>
                <ol class="pl-3 mb-0 mt-1">
                    <li>Register this exact template (name, language, body, header/footer) in Meta Business Manager → WhatsApp Manager → Message Templates.</li>
                    <li>Wait for Meta's review to come back Approved (usually within minutes to a day).</li>
                    <li>Enter the exact approved template name above, then set Status to Approved.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
