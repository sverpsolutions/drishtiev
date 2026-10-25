<?php
// ============================================================
// FILE: application/views/garments/attributes.php
// Clothing Attributes Master (Categories, Fabrics, Fits, Sleeves, Necks)
// ============================================================
$tabs = [
    'category' => ['label' => 'Garment Categories', 'icon' => 'fas fa-tags',       'data' => $categories, 'placeholder' => 'e.g. Kaftan, Dhoti Pant, Dungaree, Blazer'],
    'fabric'   => ['label' => 'Fabric Types',       'icon' => 'fas fa-tshirt',     'data' => $fabrics,    'placeholder' => 'e.g. Muslin Silk, Modal, Jacquard, Pashmina'],
    'fit'      => ['label' => 'Fit Types',          'icon' => 'fas fa-cut',        'data' => $fits,       'placeholder' => 'e.g. Bootcut, Straight Fit, Mom Fit, Flared'],
    'sleeve'   => ['label' => 'Sleeve Types',       'icon' => 'fas fa-hand-paper', 'data' => $sleeves,    'placeholder' => 'e.g. Raglan Sleeve, Bishop Sleeve, Puff Sleeve'],
    'neck'     => ['label' => 'Neck Types',         'icon' => 'fas fa-user',       'data' => $necks,      'placeholder' => 'e.g. Halter Neck, Cowl Neck, Scoop Neck, Square Neck'],
];
$active_tab = $active_tab ?? 'category';
if (!isset($tabs[$active_tab])) $active_tab = 'category';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 font-weight-bold text-dark"><i class="fas fa-sliders-h text-primary mr-2"></i> Clothing Attributes Master</h5>
        <small class="text-muted">Manage your own custom garment categories, fabrics, fits, sleeves, and neck designs</small>
    </div>
    <div>
        <a href="<?= base_url('garments/create') ?>" class="btn btn-primary btn-sm mr-2">
            <i class="fas fa-plus mr-1"></i> Add Garment Style
        </a>
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

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4 bg-white p-2 rounded shadow-sm">
    <?php foreach ($tabs as $key => $tab): ?>
        <li class="nav-item">
            <a class="nav-link font-weight-bold <?= $active_tab === $key ? 'active' : '' ?>" href="<?= base_url('garments/attributes?tab='.$key) ?>">
                <i class="<?= $tab['icon'] ?> mr-1"></i> <?= $tab['label'] ?>
                <span class="badge badge-<?= $active_tab === $key ? 'light text-dark' : 'secondary' ?> ml-1"><?= count($tab['data']) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<!-- Active Tab Panel -->
<div class="row">
    <!-- Left: Add Form -->
    <div class="col-md-5 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 font-weight-bold text-dark">
                    <i class="fas fa-plus-circle text-success mr-1"></i> Add New <?= rtrim($tabs[$active_tab]['label'], 's') ?>
                </h6>
            </div>
            <div class="card-body">
                <form method="post" action="<?= base_url('garments/attributes?tab='.$active_tab) ?>">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <input type="hidden" name="attribute_type" value="<?= htmlspecialchars($active_tab) ?>">

                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-dark"><?= rtrim($tabs[$active_tab]['label'], 's') ?> Name <span class="text-danger">*</span></label>
                        <input type="text" name="attribute_value" class="form-control" placeholder="<?= htmlspecialchars($tabs[$active_tab]['placeholder']) ?>" required autofocus>
                        <small class="text-muted">This option will immediately appear in all product dropdowns.</small>
                    </div>

                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Save to Master
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right: Existing List Table -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 font-weight-bold text-dark">
                    <i class="<?= $tabs[$active_tab]['icon'] ?> text-primary mr-1"></i> Configured <?= $tabs[$active_tab]['label'] ?>
                </h6>
                <span class="badge badge-info"><?= count($tabs[$active_tab]['data']) ?> Items</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Name / Value</th>
                                <th class="text-right" style="width: 90px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tabs[$active_tab]['data'])): ?>
                                <?php foreach ($tabs[$active_tab]['data'] as $idx => $row): ?>
                                    <tr>
                                        <td class="text-muted"><?= $idx + 1 ?></td>
                                        <td>
                                            <strong class="text-dark"><?= htmlspecialchars($row->attribute_value) ?></strong>
                                        </td>
                                        <td class="text-right">
                                            <a href="<?= base_url('garments/delete_attribute/' . $row->id . '?tab=' . $active_tab) ?>" 
                                               class="btn btn-outline-danger btn-xs py-0 px-2"
                                               onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($row->attribute_value)) ?>\'?');"
                                               title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No items configured yet. Add one on the left.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
