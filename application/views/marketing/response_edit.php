<?php // FILE: application/views/marketing/response_edit.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fab fa-whatsapp text-success mr-1"></i> Response from <?= htmlspecialchars($response->phone) ?></h5>
        <small class="text-muted"><a href="<?= base_url('marketing') ?>">Marketing</a> / <a href="<?= base_url('marketing/responses') ?>">Responses</a> / <?= htmlspecialchars($response->phone) ?></small>
    </div>
    <a href="<?= base_url('marketing/responses') ?>" class="btn btn-outline-dark btn-sm">Back to Responses</a>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Message</div>
            <div class="card-body">
                <div class="p-2 rounded" style="background:#e9f8ee;white-space:pre-wrap;font-size:.85rem;"><?= htmlspecialchars($response->message_text ?: '(no text)') ?></div>
                <p class="small text-muted mt-2 mb-0">Received <?= date('d M Y, h:i A', strtotime($response->received_at)) ?></p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;">Manage</div>
            <div class="card-body">
                <form method="post" action="<?= base_url('marketing/response_edit/' . $response->id) ?>">
                    <div class="form-group">
                        <label class="small font-weight-bold mb-0">Status</label>
                        <select name="status" class="form-control form-control-sm">
                            <?php foreach (['new' => 'New', 'assigned' => 'Assigned', 'converted' => 'Converted', 'closed' => 'Closed'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $response->status === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold mb-0">Assign To</label>
                        <select name="assigned_to" class="form-control form-control-sm">
                            <option value="">— unassigned —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u->id ?>" <?= (int)$response->assigned_to === (int)$u->id ? 'selected' : '' ?>><?= htmlspecialchars($u->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold mb-0">Follow-up Date</label>
                        <input type="date" name="follow_up_date" class="form-control form-control-sm" value="<?= htmlspecialchars($response->follow_up_date ?? '') ?>">
                    </div>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold mb-0">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="4"><?= htmlspecialchars($response->notes ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-dark btn-sm mt-3">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
