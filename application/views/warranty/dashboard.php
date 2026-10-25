<?php defined('BASEPATH') OR exit('No direct script access allowed');
// Every tile links straight to the report behind the number — the spec asks for
// the count to be clickable, not just displayed.
function wtile($label, $value, $url, $colour = 'primary', $icon = 'fa-circle') { ?>
    <div class="col-md-3 col-sm-6 mb-3">
        <a href="<?= $url ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 wt-card">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($label) ?></div>
                            <div class="font-weight-bold text-<?= $colour ?>" style="font-size:1.6rem;line-height:1.2;">
                                <?= number_format((int)$value) ?>
                            </div>
                        </div>
                        <i class="fas <?= $icon ?> fa-2x text-<?= $colour ?>" style="opacity:.25;"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
<?php }
$R = base_url('warranty/reports/');
?>
<style>.wt-card{transition:transform .12s ease,box-shadow .12s ease}.wt-card:hover{transform:translateY(-2px);box-shadow:0 .4rem 1rem rgba(0,0,0,.12)!important}</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 font-weight-bold"><i class="fas fa-shield-alt text-primary mr-1"></i> Warranty Dashboard</h5>
        <small class="text-muted">Click any number to open the report behind it</small>
    </div>
    <div style="gap:6px;" class="d-flex">
        <a href="<?= base_url('warranty/search') ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-search mr-1"></i> Serial Search</a>
        <a href="<?= base_url('warranty/claims') ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-tools mr-1"></i> Claims</a>
        <a href="<?= base_url('warranty/claim_create') ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Claim</a>
    </div>
</div>

<h6 class="text-uppercase text-muted mb-2" style="font-size:.75rem;letter-spacing:.06em;">Serial Numbers</h6>
<div class="row">
    <?php
    wtile('Total Serialized Units', $c['total_serialized'], $R.'serial_stock', 'dark',    'fa-barcode');
    wtile('Currently In Stock',     $c['in_stock'],         $R.'serial_stock', 'success', 'fa-boxes');
    wtile('Sold',                   $c['sold'],             $R.'serial_sales', 'primary', 'fa-receipt');
    wtile('Under Repair',           $c['under_repair'],     $R.'claims_pending','warning','fa-wrench');
    ?>
</div>

<h6 class="text-uppercase text-muted mb-2 mt-2" style="font-size:.75rem;letter-spacing:.06em;">Warranty</h6>
<div class="row">
    <?php
    wtile('Active Warranty',        $c['warranty_active'],  $R.'warranty_active',  'success', 'fa-check-circle');
    wtile('Expired Warranty',       $c['warranty_expired'], $R.'warranty_expired', 'secondary','fa-times-circle');
    wtile('Expiring in 30 Days',    $c['expiring_30'],      $R.'warranty_expiring?days=30', 'danger', 'fa-hourglass-half');
    wtile('Customer Returns Held',  $c['sales_return'],     $R.'serial_stock',     'info',    'fa-undo');
    ?>
</div>

<h6 class="text-uppercase text-muted mb-2 mt-2" style="font-size:.75rem;letter-spacing:.06em;">Claims</h6>
<div class="row">
    <?php
    wtile('Open Claims',        $c['claims_open'],        base_url('warranty/claims'),                        'primary', 'fa-folder-open');
    wtile('Pending Inspection', $c['claims_inspection'],  base_url('warranty/claims?status=UNDER_INSPECTION'),'warning', 'fa-search');
    wtile('Approved',           $c['claims_approved'],    base_url('warranty/claims?status=APPROVED'),        'success', 'fa-thumbs-up');
    wtile('Rejected',           $c['claims_rejected'],    base_url('warranty/claims?status=REJECTED'),        'danger',  'fa-thumbs-down');
    wtile('Repair Pending',     $c['claims_repair'],      base_url('warranty/claims?status=REPAIR'),          'warning', 'fa-wrench');
    wtile('Replacement Pending',$c['claims_replacement'], base_url('warranty/claims?status=REPLACEMENT'),     'info',    'fa-exchange-alt');
    wtile('Just Received',      $c['claims_received'],    base_url('warranty/claims?status=RECEIVED'),        'secondary','fa-inbox');
    wtile('Scrapped Units',     $c['scrapped'],           $R.'serial_movement',                               'dark',    'fa-trash');
    ?>
</div>

<div class="card border-0 shadow-sm mt-2">
    <div class="card-body py-3">
        <h6 class="font-weight-bold mb-2">All Reports</h6>
        <div class="row">
        <?php foreach ($this->Warranty_model->report_types() as $group => $types): ?>
            <div class="col-md-6 mb-2">
                <div class="text-muted mb-1" style="font-size:.75rem;"><?= htmlspecialchars($group) ?></div>
                <?php foreach ($types as $t): $m = $this->Warranty_model->report_meta($t); if (!$m) continue; ?>
                    <a href="<?= $R . $t ?>" class="btn btn-outline-secondary btn-sm mb-1"><?= htmlspecialchars($m[0]) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
</div>
