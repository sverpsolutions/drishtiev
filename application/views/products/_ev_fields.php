<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * EV Scooter Dealership Specifications Partial
 * Shared by products/add.php and products/edit.php
 */
$prod_type   = $product->product_type ?? 'electric_scooter';
$m_name      = $product->model_name ?? '';
$m_number    = $product->model_number ?? '';
$m_colour    = $product->colour ?? '';
$b_type      = $product->battery_type ?? '';
$b_cap       = $product->battery_capacity ?? '';
$c_details   = $product->charger_details ?? '';
$mot_details = $product->motor_details ?? '';

$is_ev_type  = in_array($prod_type, ['electric_scooter', 'battery', 'charger', 'motor', 'controller', 'spare_part', 'accessory', 'service'], true);
?>

<!-- EV Product Classification Header -->
<div class="card border-0 mb-3 shadow-sm" style="background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%); border-left: 5px solid #16a34a !important;">
    <div class="card-body py-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
            <div class="mb-2 mb-md-0">
                <h6 class="font-weight-bold text-dark mb-1">
                    <i class="fas fa-charging-station text-success mr-1"></i> EV Product Type &amp; Dealership Classification:
                </h6>
                <small class="text-muted">Select product type &mdash; system automatically configures GST, serial tracking, and specifications</small>
            </div>
            <div>
                <select name="product_type" id="product_type_select" class="form-control font-weight-bold border-success text-dark" style="min-width: 240px; font-size: 0.95rem;" onchange="handleProductTypeChange(this.value)">
                    <option value="electric_scooter" <?= $prod_type === 'electric_scooter' ? 'selected' : '' ?>>🛵 Electric Scooter</option>
                    <option value="battery" <?= $prod_type === 'battery' ? 'selected' : '' ?>>🔋 Battery Pack</option>
                    <option value="charger" <?= $prod_type === 'charger' ? 'selected' : '' ?>>⚡ EV Charger</option>
                    <option value="motor" <?= $prod_type === 'motor' ? 'selected' : '' ?>>⚙️ Electric Motor</option>
                    <option value="controller" <?= $prod_type === 'controller' ? 'selected' : '' ?>>🎛️ Motor Controller</option>
                    <option value="spare_part" <?= $prod_type === 'spare_part' ? 'selected' : '' ?>>🔧 Spare Part</option>
                    <option value="accessory" <?= $prod_type === 'accessory' ? 'selected' : '' ?>>🪖 Accessory</option>
                    <option value="service" <?= $prod_type === 'service' ? 'selected' : '' ?>>🛠️ EV Service / Labour</option>
                    <option value="other" <?= (!in_array($prod_type, ['electric_scooter','battery','charger','motor','controller','spare_part','accessory','service'])) ? 'selected' : '' ?>>📦 General Product</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- EV Specific Specifications Card -->
<div id="evFieldsSection" class="card border-success shadow-sm mb-4" style="<?= $is_ev_type ? '' : 'display:none;' ?>; background: #fafdfb; border-width: 1.5px;">
    <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold"><i class="fas fa-motorcycle mr-2"></i> Electric Vehicle &amp; Component Specifications</span>
        <span class="badge badge-light text-success font-weight-bold" id="evTypeBadge">
            <?= ucfirst(str_replace('_', ' ', $prod_type)) ?>
        </span>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3" id="evModelNameWrap">
                <label class="font-weight-bold">Scooter / Model Name</label>
                <input type="text" name="model_name" id="ev_model_name" class="form-control"
                       value="<?= htmlspecialchars($m_name) ?>"
                       placeholder="e.g. Drishti Blaze / Optima CX / S1 Pro">
            </div>

            <div class="col-md-4 mb-3" id="evModelNumberWrap">
                <label class="font-weight-bold">Model Number / Code</label>
                <input type="text" name="model_number" id="ev_model_number" class="form-control"
                       value="<?= htmlspecialchars($m_number) ?>"
                       placeholder="e.g. EV-BLZ-2026-STD (Not individual VIN)">
            </div>

<?php
$db_colours = [];
if ($this->db->table_exists('garment_colours')) {
    $db_colours = $this->db->where('status', 1)->order_by('sort_order ASC, name ASC')->get('garment_colours')->result();
}
?>

            <div class="col-md-4 mb-3" id="evColourWrap">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="font-weight-bold mb-0">Colour</label>
                    <a href="<?= base_url('masters/colours') ?>" target="_blank" class="small text-primary font-weight-bold" title="Open Color Master in new tab">
                        <i class="fas fa-palette mr-1"></i>Color Master &rarr;
                    </a>
                </div>
                <input type="text" name="colour" id="ev_colour" class="form-control"
                       value="<?= htmlspecialchars($m_colour) ?>"
                       placeholder="e.g. Matte Black / Pearl White / Royal Blue" list="ev_colour_suggestions">
                <datalist id="ev_colour_suggestions">
                    <?php if (!empty($db_colours)): ?>
                        <?php foreach ($db_colours as $dbc): ?>
                            <option value="<?= htmlspecialchars($dbc->name) ?>">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="Pearl White">
                        <option value="Matte Black">
                        <option value="Glossy Black">
                        <option value="Metallic Silver">
                        <option value="Gunmetal Grey">
                        <option value="Royal Blue">
                        <option value="Cherry Red">
                        <option value="Matte Green">
                        <option value="Yellow">
                    <?php endif; ?>
                </datalist>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3" id="evBatteryWrap">
                <label class="font-weight-bold">Battery Type &amp; Chemistry</label>
                <input type="text" name="battery_type" id="ev_battery_type" class="form-control"
                       value="<?= htmlspecialchars($b_type) ?>"
                       placeholder="e.g. Lithium-ion (LFP) / Lithium-ion (NMC) / Graphene" list="ev_battery_types">
                <datalist id="ev_battery_types">
                    <option value="Lithium-ion (LFP)">
                    <option value="Lithium-ion (NMC)">
                    <option value="Graphene Lead-Acid">
                    <option value="Solid State Battery">
                </datalist>
            </div>

            <div class="col-md-4 mb-3" id="evBatteryCapacityWrap">
                <label class="font-weight-bold">Battery Capacity / Rating</label>
                <input type="text" name="battery_capacity" id="ev_battery_capacity" class="form-control"
                       value="<?= htmlspecialchars($b_cap) ?>"
                       placeholder="e.g. 60V 30Ah / 72V 40Ah / 3.2 kWh">
            </div>

            <div class="col-md-4 mb-3" id="evChargerWrap">
                <label class="font-weight-bold">Charger Details &amp; Rating</label>
                <input type="text" name="charger_details" id="ev_charger_details" class="form-control"
                       value="<?= htmlspecialchars($c_details) ?>"
                       placeholder="e.g. 67.2V 6A Fast Charger (Auto Cut-off)">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3" id="evMotorWrap">
                <label class="font-weight-bold">Motor Details (Wattage &amp; Type)</label>
                <input type="text" name="motor_details" id="ev_motor_details" class="form-control"
                       value="<?= htmlspecialchars($mot_details) ?>"
                       placeholder="e.g. 1500W Waterproof BLDC Hub Motor / 3000W Mid-drive">
            </div>

            <div class="col-md-6 mb-3">
                <div class="alert alert-light border py-2 px-3 mb-0" style="font-size:0.85rem; line-height:1.4;">
                    <i class="fas fa-info-circle text-primary mr-1"></i>
                    <strong>Serial Number Note:</strong> Model number represents the design catalog. Individual physical units receive their unique <strong>Chassis / VIN</strong>, <strong>Motor Serial</strong>, and <strong>Battery Serial</strong> when stock is received into inventory via Purchases / GRN.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function handleProductTypeChange(type) {
    var evSec = document.getElementById('evFieldsSection');
    var gSec  = document.getElementById('garmentFieldsSection');
    var gVar  = document.getElementById('garmentVariantSection');
    var badge = document.getElementById('evTypeBadge');
    var mSerial = document.getElementById('maintain_serial');
    var wApp    = document.getElementById('warranty_applicable');
    var wDays   = document.getElementById('warranty_days');
    var gstInput = document.getElementById('gst_percent');

    var isEV = ['electric_scooter', 'battery', 'charger', 'motor', 'controller', 'spare_part', 'accessory', 'service'].indexOf(type) !== -1;
    if (evSec) evSec.style.display = isEV ? '' : 'none';
    if (gSec)  gSec.style.display = (type === 'garment') ? '' : 'none';
    if (gVar)  gVar.style.display = (type === 'garment') ? '' : 'none';

    if (badge) {
        badge.textContent = type.replace(/_/g, ' ').toUpperCase();
    }

    // Auto-configure serial tracking & warranty defaults based on EV component
    if (type === 'electric_scooter') {
        if (mSerial) { mSerial.checked = true; mSerial.dispatchEvent(new Event('change')); }
        if (wApp)    { wApp.checked = true; wApp.dispatchEvent(new Event('change')); }
        if (wDays && (!wDays.value || parseInt(wDays.value) <= 0)) { wDays.value = 1095; wDays.dispatchEvent(new Event('input')); }
        if (gstInput && (!gstInput.value || parseFloat(gstInput.value) <= 0)) { gstInput.value = '5.00'; }
    } else if (type === 'battery') {
        if (mSerial) { mSerial.checked = true; mSerial.dispatchEvent(new Event('change')); }
        if (wApp)    { wApp.checked = true; wApp.dispatchEvent(new Event('change')); }
        if (wDays && (!wDays.value || parseInt(wDays.value) <= 0)) { wDays.value = 1095; wDays.dispatchEvent(new Event('input')); }
        if (gstInput && (!gstInput.value || parseFloat(gstInput.value) <= 0)) { gstInput.value = '18.00'; }
    } else if (type === 'charger') {
        if (mSerial) { mSerial.checked = true; mSerial.dispatchEvent(new Event('change')); }
        if (wApp)    { wApp.checked = true; wApp.dispatchEvent(new Event('change')); }
        if (wDays && (!wDays.value || parseInt(wDays.value) <= 0)) { wDays.value = 365; wDays.dispatchEvent(new Event('input')); }
        if (gstInput && (!gstInput.value || parseFloat(gstInput.value) <= 0)) { gstInput.value = '18.00'; }
    } else if (type === 'motor') {
        if (mSerial) { mSerial.checked = true; mSerial.dispatchEvent(new Event('change')); }
        if (wApp)    { wApp.checked = true; wApp.dispatchEvent(new Event('change')); }
        if (wDays && (!wDays.value || parseInt(wDays.value) <= 0)) { wDays.value = 730; wDays.dispatchEvent(new Event('input')); }
        if (gstInput && (!gstInput.value || parseFloat(gstInput.value) <= 0)) { gstInput.value = '18.00'; }
    } else if (type === 'service') {
        if (mSerial) { mSerial.checked = false; mSerial.dispatchEvent(new Event('change')); }
        if (gstInput && (!gstInput.value || parseFloat(gstInput.value) <= 0)) { gstInput.value = '18.00'; }
    }
}
</script>
