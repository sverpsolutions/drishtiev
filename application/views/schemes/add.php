<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 font-weight-bold">Create New Target Scheme</h5>
    <a href="<?= base_url('schemes') ?>" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <?= form_open('schemes/add') ?>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Scheme Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Diwali Parle-G Target" required>
            </div>
            <div class="col-md-3 mb-3">
                <label>Period Start <span class="text-danger">*</span></label>
                <input type="date" name="period_start" class="form-control" value="<?= date('Y-m-01') ?>" required>
            </div>
            <div class="col-md-3 mb-3">
                <label>Period End <span class="text-danger">*</span></label>
                <input type="date" name="period_end" class="form-control" value="<?= date('Y-m-t') ?>" required>
            </div>

            <div class="col-md-4 mb-3">
                <label>Target Type <span class="text-danger">*</span></label>
                <select name="scheme_type" id="scheme_type" class="form-control" required onchange="toggleTargets()">
                    <option value="brand">Brand Target</option>
                    <option value="category">Category Target</option>
                    <option value="product">Specific Product Target</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>Target <span class="text-danger">*</span></label>

                <!-- Brand Select -->
                <select name="target_id_brand" id="t_brand" class="form-control target-input">
                    <?php foreach($brands as $b): ?><option value="<?= $b->id ?>"><?= htmlspecialchars($b->name) ?></option><?php endforeach; ?>
                </select>

                <!-- Category Select -->
                <select name="target_id_category" id="t_category" class="form-control target-input" style="display:none;">
                    <?php foreach($categories as $c): ?><option value="<?= $c->id ?>"><?= htmlspecialchars($c->name) ?></option><?php endforeach; ?>
                </select>

                <!-- Product Select -->
                <select name="target_id_product" id="t_product" class="form-control target-input" style="display:none;">
                    <?php foreach($products as $p): ?><option value="<?= $p->id ?>"><?= htmlspecialchars($p->name) ?></option><?php endforeach; ?>
                </select>
                
                <input type="hidden" name="target_id" id="final_target_id">
            </div>

            <div class="col-md-2 mb-3">
                <label>Target Amount (₹) <span class="text-danger">*</span></label>
                <input type="number" name="target_amount" class="form-control" value="50000" min="1" required>
            </div>
            <div class="col-md-2 mb-3">
                <label>Bonus Discount (%) <span class="text-danger">*</span></label>
                <input type="number" step="0.01" name="discount_percent" class="form-control text-success font-weight-bold" value="2.00" min="0.01" required>
            </div>
        </div>

        <hr>
        <button type="submit" class="btn btn-dark btn-lg px-5" onclick="setFinalTarget()">Activate Scheme Engine</button>
        <?= form_close() ?>
    </div>
</div>

<script>
function toggleTargets() {
    const type = document.getElementById('scheme_type').value;
    document.querySelectorAll('.target-input').forEach(el => el.style.display = 'none');
    document.getElementById('t_' + type).style.display = 'block';
}
function setFinalTarget() {
    const type = document.getElementById('scheme_type').value;
    document.getElementById('final_target_id').value = document.getElementById('t_' + type).value;
}
</script>
