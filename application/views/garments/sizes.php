<?php
// ============================================================
// FILE: application/views/garments/sizes.php
// Size Sets & Sizes Master
// ============================================================
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold text-dark"><i class="fas fa-ruler-combined text-primary mr-2"></i>Size Sets & Sizes Master</h5>
        <small class="text-muted">Manage standard size sets (Alpha, Numeric Waist, Chest, Free Size) and custom sizes</small>
    </div>
    <div>
        <a href="<?= base_url('garments') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Styles
        </a>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-1"></i> <?= $this->session->flashdata('success') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Left: Size Sets -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-layer-group text-info mr-1"></i> Size Sets</h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('garments/sizes') ?>" class="mb-3">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <input type="hidden" name="action" value="add_set">
                    <div class="form-row">
                        <div class="col-7">
                            <input type="text" name="set_name" class="form-control form-control-sm" placeholder="New Size Set Name (e.g. Shoes UK)" required>
                        </div>
                        <div class="col-3">
                            <input type="text" name="code" class="form-control form-control-sm" placeholder="Code">
                        </div>
                        <div class="col-2">
                            <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Set Name</th>
                                <th>Code</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($size_sets as $ss): ?>
                                <tr>
                                    <td class="font-weight-bold"><?= htmlspecialchars($ss->name) ?></td>
                                    <td><code><?= htmlspecialchars($ss->code) ?></code></td>
                                    <td class="text-center">
                                        <span class="badge badge-success">Active</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Sizes -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-tags text-success mr-1"></i> Individual Sizes</h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('garments/sizes') ?>" class="mb-3">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <input type="hidden" name="action" value="add_size">
                    <div class="form-row">
                        <div class="col-md-4 mb-2">
                            <select name="size_set_id" class="form-control form-control-sm" required>
                                <option value="">-- Size Set --</option>
                                <?php foreach ($size_sets as $ss): ?>
                                    <option value="<?= (int)$ss->id ?>"><?= htmlspecialchars($ss->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="text" name="size_name" class="form-control form-control-sm" placeholder="Size (e.g. 42 / XXL)" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="text" name="measurement" class="form-control form-control-sm" placeholder="Measure (e.g. 106 cm)">
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-success btn-sm btn-block"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Size Name</th>
                                <th>Size Set</th>
                                <th>Measurement</th>
                                <th class="text-center">Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sizes as $idx => $sz): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td class="font-weight-bold"><span class="badge badge-light border px-2 py-1"><?= htmlspecialchars($sz->name) ?></span></td>
                                    <td class="text-muted"><?= htmlspecialchars($sz->size_set_name) ?></td>
                                    <td><?= htmlspecialchars($sz->measurement ?: '-') ?></td>
                                    <td class="text-center text-muted"><?= (int)$sz->sort_order ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
