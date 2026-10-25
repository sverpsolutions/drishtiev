<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * EV Scooter Dealership Selection Modal for Billing
 * Lets the operator pick Scooter Model -> Colour -> Chassis/VIN -> Battery Serial(s) -> Charger Serial
 */
?>
<div class="modal fade" id="evScooterModal" tabindex="-1" role="dialog" data-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-3 text-white" style="background: linear-gradient(135deg, #15803d 0%, #166534 100%);">
                <h5 class="modal-title font-weight-bold d-flex align-items-center">
                    <i class="fas fa-motorcycle mr-2 fa-lg"></i>
                    <span>EV Scooter Dealership Configuration &amp; VIN Allocation</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.9;">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body p-4 bg-light">
                <!-- Vehicle Info Banner -->
                <div class="card border-0 mb-3 shadow-sm" style="background: #ffffff; border-radius: 8px;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <h5 class="font-weight-bold text-dark mb-1" id="evModalProductName">Select Scooter Model</h5>
                                <div class="text-muted small" id="evModalProductCode">Choose a model below or search by VIN</div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-success px-3 py-2 font-weight-bold" style="font-size: 0.95rem;" id="evModalPriceBadge">₹ 0.00</span>
                                <div class="small text-muted mt-1" id="evModalGstBadge">GST 5% (Included)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wizard Steps Form -->
                <div class="card border-0 shadow-sm" style="background: #ffffff; border-radius: 8px;">
                    <div class="card-body p-4">
                        <input type="hidden" id="evSelectedProductId" value="">
                        <input type="hidden" id="evSelectedRate" value="0">
                        <input type="hidden" id="evSelectedMrp" value="0">
                        <input type="hidden" id="evSelectedGstPct" value="5">
                        <input type="hidden" id="evSelectedUnit" value="PCS">
                        <input type="hidden" id="evSelectedHsn" value="87116090">
                        <input type="hidden" id="evSelectedWarrantyDays" value="1095">
                        <input type="hidden" id="evSelectedWarrantyPolicy" value="3 Years Warranty">

                        <!-- Step 1: Scooter Model Selection (if opened from button rather than item row) -->
                        <div class="form-group mb-3" id="evModelSelectGroup">
                            <label class="font-weight-bold text-dark"><span class="badge badge-success mr-1">1</span> Select Scooter Model</label>
                            <select id="evScooterModelSelect" class="form-control form-control-lg font-weight-bold text-dark" onchange="onEvScooterModelChange(this.value)">
                                <option value="">-- Choose Scooter Model --</option>
                                <?php if (!empty($products)): ?>
                                    <?php foreach ($products as $p): ?>
                                        <?php if (($p->product_type ?? '') === 'electric_scooter'): ?>
                                            <option value="<?= $p->id ?>"
                                                    data-name="<?= htmlspecialchars($p->name) ?>"
                                                    data-model="<?= htmlspecialchars($p->model_name ?: $p->model_number) ?>"
                                                    data-colour="<?= htmlspecialchars($p->colour) ?>"
                                                    data-price="<?= $p->selling_price ?>"
                                                    data-mrp="<?= $p->mrp ?>"
                                                    data-gst="<?= $p->gst_percent ?>"
                                                    data-hsn="<?= htmlspecialchars($p->hsn_code ?? '87116090') ?>"
                                                    data-warranty-days="<?= (int)($p->warranty_days ?? 1095) ?>"
                                                    data-warranty-policy="<?= htmlspecialchars($p->warranty_policy ?? '3 Years Standard Warranty') ?>">
                                                <?= htmlspecialchars($p->name) ?> (<?= htmlspecialchars($p->model_number ?: $p->item_code) ?>) - ₹<?= number_format($p->selling_price, 2) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- Step 2: Colour Filter & Chassis / VIN Selection -->
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label class="font-weight-bold text-dark"><span class="badge badge-success mr-1">2</span> Vehicle Colour</label>
                                <select id="evColourSelect" class="form-control" onchange="onEvColourFilterChange(this.value)">
                                    <option value="">-- All Available Colours --</option>
                                </select>
                            </div>
                            <div class="col-md-7 mb-3">
                                <label class="font-weight-bold text-dark">
                                    <span class="badge badge-success mr-1">3</span> Available Physical Chassis / VIN <span class="text-danger">*</span>
                                </label>
                                <select id="evChassisSelect" class="form-control font-weight-bold text-primary" onchange="onEvChassisChange()">
                                    <option value="">-- Select Available Chassis / VIN --</option>
                                </select>
                                <small class="text-muted" id="evChassisStockMsg">Units currently in stock at this location</small>
                            </div>
                        </div>

                        <!-- Motor Serial Number (auto populated or editable) -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold text-dark">Motor Serial Number</label>
                                <input type="text" id="evMotorNoInput" class="form-control font-weight-bold" placeholder="e.g. MOT-2026-99881">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold text-dark">Warranty Terms</label>
                                <input type="text" id="evWarrantyTermsInput" class="form-control" placeholder="e.g. Scooter: 3 Yrs | Battery: 3 Yrs | Charger: 1 Yr">
                            </div>
                        </div>

                        <hr class="my-3">

                        <!-- Step 4: Battery Allocation -->
                        <div class="mb-3 p-3 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="font-weight-bold text-dark mb-0">
                                    <i class="fas fa-car-battery text-warning mr-1"></i> <span class="badge badge-success mr-1">4</span> Battery Serial Number(s)
                                </label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="evIncludeBatteryChk" checked onchange="toggleEvBatteryIncluded(this.checked)">
                                    <label class="custom-control-label font-weight-bold text-success" for="evIncludeBatteryChk">Battery Included in Scooter</label>
                                </div>
                            </div>

                            <div id="evBatterySelectSection">
                                <div class="row align-items-center">
                                    <div class="col-md-7">
                                        <select id="evBatterySelect" class="form-control font-weight-bold">
                                            <option value="">-- Select Battery Serial --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" id="evCustomBatteryInput" class="form-control" placeholder="Or scan/type Battery Serial">
                                    </div>
                                </div>
                                <small class="text-muted mt-1 d-block">
                                    For dual battery models, separate serials with a comma (e.g. <code>BAT1001, BAT1002</code>).
                                </small>
                            </div>
                        </div>

                        <!-- Step 5: Charger Allocation -->
                        <div class="mb-3 p-3 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="font-weight-bold text-dark mb-0">
                                    <i class="fas fa-charging-station text-info mr-1"></i> <span class="badge badge-success mr-1">5</span> Charger Serial Number
                                </label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="evIncludeChargerChk" checked onchange="toggleEvChargerIncluded(this.checked)">
                                    <label class="custom-control-label font-weight-bold text-info" for="evIncludeChargerChk">Charger Included in Scooter</label>
                                </div>
                            </div>

                            <div id="evChargerSelectSection">
                                <div class="row align-items-center">
                                    <div class="col-md-7">
                                        <select id="evChargerSelect" class="form-control font-weight-bold">
                                            <option value="">-- Select Charger Serial --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" id="evCustomChargerInput" class="form-control" placeholder="Or scan/type Charger Serial">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alert Box for Validation -->
                        <div id="evModalAlert" class="alert alert-danger py-2 px-3 mb-0" style="display: none; font-size: 0.9rem;"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer py-3 d-flex justify-content-between bg-white">
                <button type="button" class="btn btn-outline-secondary font-weight-bold" data-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-success btn-lg px-4 font-weight-bold shadow-sm" id="btnConfirmEvAdd" onclick="confirmEvScooterAddition()">
                    <i class="fas fa-check-circle mr-1"></i> Allocate &amp; Add to Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<script>
var EV_MODAL_STATE = {
    productId: null,
    scooters: [],
    batteries: [],
    chargers: [],
    colours: [],
    targetRowId: null
};

function openEvScooterModal(productId, targetRowId, preselectChassis) {
    EV_MODAL_STATE.targetRowId = targetRowId || null;
    EV_MODAL_STATE.preselectChassis = preselectChassis || null;
    var modal = $('#evScooterModal');
    var alertEl = document.getElementById('evModalAlert');
    if (alertEl) alertEl.style.display = 'none';

    var modelSelect = document.getElementById('evScooterModelSelect');
    if (productId) {
        if (modelSelect) modelSelect.value = productId;
        onEvScooterModelChange(productId);
    } else if (modelSelect && modelSelect.value) {
        onEvScooterModelChange(modelSelect.value);
    }

    modal.modal('show');
}

function onEvScooterModelChange(pid) {
    if (!pid) return;
    EV_MODAL_STATE.productId = pid;

    var modelSelect = document.getElementById('evScooterModelSelect');
    var selectedOpt = modelSelect ? modelSelect.options[modelSelect.selectedIndex] : null;

    if (selectedOpt) {
        var name = selectedOpt.getAttribute('data-name') || '';
        var model = selectedOpt.getAttribute('data-model') || '';
        var price = parseFloat(selectedOpt.getAttribute('data-price')) || 0;
        var mrp = parseFloat(selectedOpt.getAttribute('data-mrp')) || 0;
        var gst = parseFloat(selectedOpt.getAttribute('data-gst')) || 5;
        var hsn = selectedOpt.getAttribute('data-hsn') || '87116090';
        var wDays = parseInt(selectedOpt.getAttribute('data-warranty-days')) || 1095;
        var wPolicy = selectedOpt.getAttribute('data-warranty-policy') || '3 Years Comprehensive Warranty';

        document.getElementById('evModalProductName').textContent = name;
        document.getElementById('evModalProductCode').textContent = 'Model: ' + model + ' | HSN: ' + hsn;
        document.getElementById('evModalPriceBadge').textContent = '₹ ' + price.toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('evModalGstBadge').textContent = 'GST ' + gst + '% Included';

        document.getElementById('evSelectedProductId').value = pid;
        document.getElementById('evSelectedRate').value = price;
        document.getElementById('evSelectedMrp').value = mrp;
        document.getElementById('evSelectedGstPct').value = gst;
        document.getElementById('evSelectedHsn').value = hsn;
        document.getElementById('evSelectedWarrantyDays').value = wDays;
        document.getElementById('evSelectedWarrantyPolicy').value = wPolicy;
        document.getElementById('evWarrantyTermsInput').value = wPolicy;
    }

    // Load available serials from server
    fetchEvSerialsForModel(pid);
}

function fetchEvSerialsForModel(pid, colour) {
    var chassisSelect = document.getElementById('evChassisSelect');
    var batterySelect = document.getElementById('evBatterySelect');
    var chargerSelect = document.getElementById('evChargerSelect');
    var colourSelect  = document.getElementById('evColourSelect');

    if (chassisSelect) chassisSelect.innerHTML = '<option value="">Loading in-stock units...</option>';

    var fd = new FormData();
    fd.append('product_id', pid);
    if (colour) fd.append('colour', colour);

    fetch('<?= base_url('serials/ev_available_serials_ajax') ?>', {
        method: 'POST',
        body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (!res.ok) {
            if (chassisSelect) chassisSelect.innerHTML = '<option value="">Error loading units</option>';
            return;
        }

        EV_MODAL_STATE.scooters  = res.scooters || [];
        EV_MODAL_STATE.batteries = res.batteries || [];
        EV_MODAL_STATE.chargers  = res.chargers || [];
        EV_MODAL_STATE.colours   = res.colours || [];

        // Populate Colour Select
        if (colourSelect && (!colour || colourSelect.options.length <= 1)) {
            var currColour = colourSelect.value;
            colourSelect.innerHTML = '<option value="">-- All Available Colours --</option>';
            EV_MODAL_STATE.colours.forEach(function(c) {
                var opt = document.createElement('option');
                opt.value = c;
                opt.textContent = c;
                if (c === currColour) opt.selected = true;
                colourSelect.appendChild(opt);
            });
        }

        // Populate Chassis / VIN Select
        if (chassisSelect) {
            chassisSelect.innerHTML = '<option value="">-- Select Available Chassis / VIN --</option>';
            if (EV_MODAL_STATE.scooters.length === 0) {
                chassisSelect.innerHTML = '<option value="">No Chassis/VIN available in stock at this outlet</option>';
            } else {
                EV_MODAL_STATE.scooters.forEach(function(s) {
                    var opt = document.createElement('option');
                    opt.value = s.serial_no;
                    opt.setAttribute('data-motor', s.motor_no || '');
                    opt.setAttribute('data-colour', s.colour || '');
                    opt.textContent = s.serial_no + (s.colour ? ' (' + s.colour + ')' : '') + (s.motor_no ? ' [Motor: ' + s.motor_no + ']' : '');
                    chassisSelect.appendChild(opt);
                });

                if (EV_MODAL_STATE.preselectChassis) {
                    chassisSelect.value = EV_MODAL_STATE.preselectChassis;
                    onEvChassisChange();
                    EV_MODAL_STATE.preselectChassis = null;
                }
            }
        }

        // Populate Battery Select
        if (batterySelect) {
            batterySelect.innerHTML = '<option value="">-- Select Battery Serial --</option>';
            EV_MODAL_STATE.batteries.forEach(function(b) {
                var opt = document.createElement('option');
                opt.value = b.serial_no;
                opt.textContent = b.serial_no + (b.battery_capacity ? ' (' + b.battery_capacity + ')' : '') + (b.product_name ? ' - ' + b.product_name : '');
                batterySelect.appendChild(opt);
            });
        }

        // Populate Charger Select
        if (chargerSelect) {
            chargerSelect.innerHTML = '<option value="">-- Select Charger Serial --</option>';
            EV_MODAL_STATE.chargers.forEach(function(c) {
                var opt = document.createElement('option');
                opt.value = c.serial_no;
                opt.textContent = c.serial_no + (c.charger_model ? ' (' + c.charger_model + ')' : '');
                chargerSelect.appendChild(opt);
            });
        }
    })
    .catch(function(err) {
        console.error('Error fetching EV serials:', err);
    });
}

function onEvColourFilterChange(colour) {
    if (EV_MODAL_STATE.productId) {
        fetchEvSerialsForModel(EV_MODAL_STATE.productId, colour);
    }
}

function onEvChassisChange() {
    var chassisSelect = document.getElementById('evChassisSelect');
    if (!chassisSelect) return;
    var opt = chassisSelect.options[chassisSelect.selectedIndex];
    if (opt && opt.value) {
        var motor = opt.getAttribute('data-motor') || '';
        var clr   = opt.getAttribute('data-colour') || '';
        if (motor) document.getElementById('evMotorNoInput').value = motor;
        if (clr && !document.getElementById('evColourSelect').value) {
            document.getElementById('evColourSelect').value = clr;
        }
    }
}

function toggleEvBatteryIncluded(on) {
    var sec = document.getElementById('evBatterySelectSection');
    if (sec) sec.style.opacity = on ? '1' : '0.4';
    if (!on) {
        document.getElementById('evBatterySelect').value = '';
        document.getElementById('evCustomBatteryInput').value = '';
    }
}

function toggleEvChargerIncluded(on) {
    var sec = document.getElementById('evChargerSelectSection');
    if (sec) sec.style.opacity = on ? '1' : '0.4';
    if (!on) {
        document.getElementById('evChargerSelect').value = '';
        document.getElementById('evCustomChargerInput').value = '';
    }
}

function confirmEvScooterAddition() {
    var alertEl = document.getElementById('evModalAlert');
    alertEl.style.display = 'none';

    var pid = document.getElementById('evSelectedProductId').value;
    if (!pid) {
        alertEl.textContent = 'Please select a Scooter Model.';
        alertEl.style.display = 'block';
        return;
    }

    var chassisSelect = document.getElementById('evChassisSelect');
    var chassisNo = chassisSelect ? chassisSelect.value : '';
    if (!chassisNo) {
        alertEl.textContent = 'Please select an available Chassis / VIN number.';
        alertEl.style.display = 'block';
        return;
    }

    var motorNo     = document.getElementById('evMotorNoInput').value.trim();
    var colour      = document.getElementById('evColourSelect').value.trim();
    var wTerms      = document.getElementById('evWarrantyTermsInput').value.trim();
    var incBattery  = document.getElementById('evIncludeBatteryChk').checked;
    var incCharger  = document.getElementById('evIncludeChargerChk').checked;

    var batteryNo = '';
    if (incBattery) {
        batteryNo = document.getElementById('evCustomBatteryInput').value.trim()
                 || document.getElementById('evBatterySelect').value.trim();
        if (!batteryNo) {
            alertEl.textContent = 'Please select or enter the Battery Serial Number, or uncheck "Battery Included".';
            alertEl.style.display = 'block';
            return;
        }
    }

    var chargerNo = '';
    if (incCharger) {
        chargerNo = document.getElementById('evCustomChargerInput').value.trim()
                 || document.getElementById('evChargerSelect').value.trim();
        if (!chargerNo) {
            alertEl.textContent = 'Please select or enter the Charger Serial Number, or uncheck "Charger Included".';
            alertEl.style.display = 'block';
            return;
        }
    }

    var rate = parseFloat(document.getElementById('evSelectedRate').value) || 0;
    var mrp  = parseFloat(document.getElementById('evSelectedMrp').value) || rate;
    var gst  = parseFloat(document.getElementById('evSelectedGstPct').value) || 5;
    var hsn  = document.getElementById('evSelectedHsn').value || '87116090';
    var name = document.getElementById('evModalProductName').textContent || 'Electric Scooter';

    var evPayload = {
        productId: parseInt(pid),
        productName: name,
        rate: rate,
        mrp: mrp,
        gstPercent: gst,
        hsnCode: hsn,
        chassisNo: chassisNo,
        motorNo: motorNo,
        colour: colour,
        batterySerial: batteryNo,
        chargerSerial: chargerNo,
        warrantyTerms: wTerms,
        serials: [chassisNo]
    };

    // Close modal
    $('#evScooterModal').modal('hide');

    // Deliver to billing UI
    if (typeof window.onEvScooterAllocated === 'function') {
        window.onEvScooterAllocated(evPayload, EV_MODAL_STATE.targetRowId);
    } else {
        console.log('EV Allocated payload:', evPayload);
    }
}
</script>
