<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 font-weight-bold"><i class="fas fa-th mr-2"></i>Rack Master</h5>
    <?php if (!empty($racks)): ?>
    <a href="<?= base_url('masters/rack_qr_bulk?ids=' . implode(',', array_column($racks, 'id'))) ?>" target="_blank" class="btn btn-outline-dark btn-sm"><i class="fas fa-qrcode mr-1"></i> Print All QR Stickers</a>
    <?php endif; ?>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2"><?= $this->session->flashdata('success') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2"><?= $this->session->flashdata('error') ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold">Add Rack</div>
            <div class="card-body">
                <?= form_open('masters/racks') ?>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Rack Code <span class="text-danger">*</span></label>
                    <input type="text" name="rack_code" class="form-control text-uppercase" placeholder="e.g. A1, R-01, SHELF-3" required maxlength="20" autofocus>
                </div>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Rack Name <span class="text-danger">*</span></label>
                    <input type="text" name="rack_name" class="form-control" placeholder="e.g. Aisle 1 Top Shelf" required>
                </div>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Location</label>
                    <select name="location_id" class="form-control">
                        <option value="">-- All Locations --</option>
                        <?php foreach ($locations as $l): ?>
                        <option value="<?= $l->id ?>"><?= htmlspecialchars($l->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Optional notes">
                </div>
                <div class="form-group mb-3">
                    <label class="font-weight-bold small text-muted">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="0" min="0">
                </div>
                <button type="submit" class="btn btn-dark btn-block font-weight-bold">Save Rack</button>
                <?= form_close() ?>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Rack Name</th>
                            <th>Location</th>
                            <th class="text-center">Items</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($racks)): ?>
                            <?php foreach($racks as $k => $r): ?>
                            <?php
                                $item_count = $this->db->where('rack_id', $r->id)->where('status', 1)->count_all_results('products');
                            ?>
                            <tr>
                                <td class="align-middle"><?= $k+1 ?></td>
                                <td class="align-middle"><span class="badge badge-secondary font-weight-bold" style="font-size:.85rem;"><?= htmlspecialchars($r->rack_code) ?></span></td>
                                <td class="align-middle"><?= htmlspecialchars($r->rack_name) ?></td>
                                <td class="align-middle text-muted"><?= htmlspecialchars($r->location_name ?? 'All') ?></td>
                                <td class="text-center align-middle">
                                    <?php if ($item_count > 0): ?>
                                    <a href="<?= base_url('masters/rack_stock/' . $r->id) ?>" class="badge badge-info"><?= $item_count ?> items</a>
                                    <?php else: ?>
                                    <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right align-middle">
                                    <a href="<?= base_url('masters/rack_qr/' . $r->id) ?>" target="_blank" class="btn btn-outline-dark btn-sm mr-1" title="Print QR"><i class="fas fa-qrcode"></i></a>
                                    <a href="<?= base_url('masters/rack_stock/' . $r->id) ?>" class="btn btn-outline-info btn-sm mr-1" title="View Stock"><i class="fas fa-boxes"></i></a>
                                    <a href="<?= base_url('masters/edit_rack/' . $r->id) ?>" class="btn btn-outline-primary btn-sm mr-1">Edit</a>
                                    <a href="<?= base_url('masters/delete_rack/' . $r->id) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this rack? Products will be unassigned.')">Del</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No racks added yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
