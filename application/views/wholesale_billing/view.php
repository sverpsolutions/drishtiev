<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-file-invoice-dollar"></i> <?= htmlspecialchars($invoice->invoice_no) ?>
            <?php
            $badge_map = ['paid'=>'success','unpaid'=>'warning','partial'=>'info','cancelled'=>'danger'];
            $badge = $badge_map[$invoice->status] ?? 'secondary';
            ?>
            <span class="badge badge-<?= $badge ?> ml-2"><?= ucfirst($invoice->status) ?></span>
        </h4>
        <div>
            <a href="<?= base_url('wholesale_billing/print_invoice/' . $invoice->id) ?>" target="_blank" class="btn btn-outline-primary btn-sm mr-1"><i class="fas fa-print"></i> Print</a>
            <a href="<?= base_url('wholesale_billing') ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?><button class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?><button class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <div id="apiMsg"></div>

    <div class="row">
        <!-- Invoice & Customer Info -->
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header py-2"><strong>Invoice Details</strong></div>
                <div class="card-body py-2">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:140px;">Invoice No</td><td><strong><?= htmlspecialchars($invoice->invoice_no) ?></strong></td></tr>
                        <tr><td class="text-muted">Date</td><td><?= date('d M Y', strtotime($invoice->invoice_date)) ?></td></tr>
                        <tr><td class="text-muted">Financial Year</td><td><?= htmlspecialchars($invoice->financial_year ?? '') ?></td></tr>
                        <tr><td class="text-muted">Payment Mode</td><td><?= ucfirst($invoice->payment_mode ?? 'credit') ?></td></tr>
                        <?php if (!empty($invoice->notes)): ?>
                        <tr><td class="text-muted">Notes</td><td><?= htmlspecialchars($invoice->notes) ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header py-2"><strong>Customer</strong></div>
                <div class="card-body py-2">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:140px;">Name</td><td><strong><?= htmlspecialchars($customer->name) ?></strong></td></tr>
                        <tr><td class="text-muted">GSTIN</td><td><code><?= htmlspecialchars($customer->gst_number) ?></code></td></tr>
                        <?php if (!empty($customer->state)): ?>
                        <tr><td class="text-muted">State</td><td><?= htmlspecialchars($customer->state) ?> <?= $invoice->is_interstate ? '<span class="badge badge-info">Interstate</span>' : '<span class="badge badge-secondary">Intrastate</span>' ?></td></tr>
                        <?php endif; ?>
                        <?php if (!empty($customer->address)): ?>
                        <tr><td class="text-muted">Address</td><td><?= htmlspecialchars($customer->address) ?></td></tr>
                        <?php endif; ?>
                        <?php if (!empty($customer->phone)): ?>
                        <tr><td class="text-muted">Phone</td><td><?= htmlspecialchars($customer->phone) ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <?php
            $__d = $addr['dispatch']; $__b = $addr['buyer']; $__s = $addr['ship'];
            $__locked = (!empty($invoice->einvoice_irn) && ($invoice->einvoice_status ?? '') !== 'cancelled')
                     || (!empty($invoice->ewb_no) && ($invoice->ewb_status ?? '') !== 'cancelled');
            ?>
            <div class="card mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-map-marker-alt"></i> Addresses (for E-Invoice / E-Way Bill)</strong>
                    <?php if (!$__locked): ?>
                    <button class="btn btn-xs btn-outline-primary" data-toggle="modal" data-target="#shipModal"><i class="fas fa-edit"></i> Delivery Address</button>
                    <?php endif; ?>
                </div>
                <div class="card-body py-2" style="font-size:.85rem;">
                    <?php if (!empty($addr['errors'])): ?>
                    <div class="alert alert-danger py-2 mb-2">
                        <strong>Fix these before generating E-Invoice / E-Way Bill:</strong>
                        <ul class="mb-0 pl-3"><?php foreach ($addr['errors'] as $__e): ?><li><?= htmlspecialchars($__e) ?></li><?php endforeach; ?></ul>
                    </div>
                    <?php endif; ?>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:120px;">Dispatch From</td>
                            <td><?= htmlspecialchars($__d['source']) ?>: <?= htmlspecialchars($__d['addr1']) ?>, <?= htmlspecialchars($__d['place']) ?> — <strong>PIN <?= htmlspecialchars($__d['pin'] ?: '?') ?></strong> (<?= htmlspecialchars(GstAddress::state_name($__d['state'])) ?>)</td></tr>
                        <tr><td class="text-muted">Bill To</td>
                            <td><?= htmlspecialchars($__b['addr1']) ?>, <?= htmlspecialchars($__b['place']) ?> — <strong>PIN <?= htmlspecialchars($__b['pin'] ?: '?') ?></strong> (<?= htmlspecialchars(GstAddress::state_name($__b['state'])) ?>)</td></tr>
                        <tr><td class="text-muted">Ship To</td>
                            <td><?php if ($__s): ?>
                                <strong><?= htmlspecialchars($__s['name']) ?></strong><?= $__s['gstin'] ? ' (' . htmlspecialchars($__s['gstin']) . ')' : '' ?>,
                                <?= htmlspecialchars($__s['addr1']) ?>, <?= htmlspecialchars($__s['place']) ?> — <strong>PIN <?= htmlspecialchars($__s['pin']) ?></strong> (<?= htmlspecialchars(GstAddress::state_name($__s['state'])) ?>)
                                <?php else: ?><span class="text-muted">Same as Bill To</span><?php endif; ?></td></tr>
                        <tr><td class="text-muted">Place of Supply</td><td><?= htmlspecialchars(GstAddress::state_name($addr['pos'])) ?> (<?= htmlspecialchars($addr['pos']) ?>)</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- E-Invoice & E-Way Bill Status -->
        <div class="col-md-6">
            <!-- E-Invoice Card -->
            <div class="card mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-stamp"></i> E-Invoice (IRN)</strong>
                    <?php if (($invoice->einvoice_status ?? '') === 'cancelled'): ?>
                        <span class="badge badge-danger">Cancelled</span>
                    <?php elseif (!empty($invoice->einvoice_irn)): ?>
                        <span class="badge badge-success">Generated</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Not Generated</span>
                    <?php endif; ?>
                </div>
                <div class="card-body py-2">
                    <?php if (!empty($invoice->einvoice_irn)): ?>
                        <table class="table table-sm table-borderless mb-2">
                            <tr><td class="text-muted" style="width:120px;">IRN</td><td><code style="word-break:break-all;font-size:.75rem;"><?= htmlspecialchars($invoice->einvoice_irn) ?></code></td></tr>
                            <?php if (!empty($invoice->einvoice_ack_no)): ?>
                            <tr><td class="text-muted">Ack No</td><td><?= htmlspecialchars($invoice->einvoice_ack_no) ?></td></tr>
                            <?php endif; ?>
                            <?php if (!empty($invoice->einvoice_ack_date)): ?>
                            <tr><td class="text-muted">Ack Date</td><td><?= date('d M Y H:i', strtotime($invoice->einvoice_ack_date)) ?></td></tr>
                            <?php endif; ?>
                        </table>
                        <?php if (($invoice->einvoice_status ?? '') !== 'cancelled'): ?>
                        <button class="btn btn-sm btn-outline-success mr-1" onclick="verifyIrn()"><i class="fas fa-check-circle"></i> Verify IRN</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="cancelIrn()"><i class="fas fa-ban"></i> Cancel IRN</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (($invoice->einvoice_status ?? '') !== 'cancelled'): ?>
                        <p class="text-muted mb-2">E-Invoice has not been generated for this invoice.</p>
                        <button class="btn btn-sm btn-primary" onclick="generateIrn()" id="btnIrn">
                            <i class="fas fa-stamp"></i> Generate E-Invoice (IRN)
                        </button>
                        <?php else: ?>
                        <p class="text-muted mb-0">IRN was cancelled.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- E-Way Bill Card -->
            <div class="card mb-3">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-truck"></i> E-Way Bill</strong>
                    <?php if (($invoice->ewb_status ?? '') === 'cancelled'): ?>
                        <span class="badge badge-danger">Cancelled</span>
                    <?php elseif (!empty($invoice->ewb_no)): ?>
                        <span class="badge badge-success">Generated</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Not Generated</span>
                    <?php endif; ?>
                </div>
                <div class="card-body py-2">
                    <?php if (!empty($invoice->ewb_no)): ?>
                        <table class="table table-sm table-borderless mb-2">
                            <tr><td class="text-muted" style="width:120px;">EWB No</td><td><strong><?= htmlspecialchars($invoice->ewb_no) ?></strong></td></tr>
                            <?php if (!empty($invoice->ewb_date)): ?>
                            <tr><td class="text-muted">Generated</td><td><?= date('d M Y H:i', strtotime($invoice->ewb_date)) ?></td></tr>
                            <?php endif; ?>
                            <?php if (!empty($invoice->ewb_valid_until)): ?>
                            <tr><td class="text-muted">Valid Until</td><td><?= date('d M Y H:i', strtotime($invoice->ewb_valid_until)) ?></td></tr>
                            <?php endif; ?>
                        </table>
                        <?php if (($invoice->ewb_status ?? '') !== 'cancelled'): ?>
                        <button class="btn btn-sm btn-outline-danger" onclick="cancelEwb()"><i class="fas fa-ban"></i> Cancel E-Way Bill</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (($invoice->ewb_status ?? '') !== 'cancelled'): ?>
                        <p class="text-muted mb-2">E-Way Bill has not been generated for this invoice.</p>
                        <button class="btn btn-sm btn-info" onclick="generateEwb()" id="btnEwb">
                            <i class="fas fa-truck"></i> Generate E-Way Bill
                        </button>
                        <?php else: ?>
                        <p class="text-muted mb-0">E-Way Bill was cancelled.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Transport Info -->
            <?php if (!empty($invoice->vehicle_no) || !empty($invoice->transporter_name)): ?>
            <div class="card mb-3">
                <div class="card-header py-2"><strong>Transport</strong></div>
                <div class="card-body py-2">
                    <table class="table table-sm table-borderless mb-0">
                        <?php if (!empty($invoice->vehicle_no)): ?>
                        <tr><td class="text-muted" style="width:140px;">Vehicle</td><td><?= htmlspecialchars($invoice->vehicle_no) ?> (<?= $invoice->vehicle_type === 'O' ? 'ODC' : 'Regular' ?>)</td></tr>
                        <?php endif; ?>
                        <?php
                        $modes = [1=>'Road',2=>'Rail',3=>'Air',4=>'Ship'];
                        ?>
                        <tr><td class="text-muted">Mode</td><td><?= $modes[$invoice->transport_mode ?? 1] ?? 'Road' ?></td></tr>
                        <?php if (!empty($invoice->transporter_name)): ?>
                        <tr><td class="text-muted">Transporter</td><td><?= htmlspecialchars($invoice->transporter_name) ?> <?= !empty($invoice->transporter_id) ? '('.$invoice->transporter_id.')' : '' ?></td></tr>
                        <?php endif; ?>
                        <?php if (!empty($invoice->transport_distance)): ?>
                        <tr><td class="text-muted">Distance</td><td><?= $invoice->transport_distance ?> KM</td></tr>
                        <?php endif; ?>
                        <?php if (!empty($invoice->transport_doc_no)): ?>
                        <tr><td class="text-muted">Doc No</td><td><?= htmlspecialchars($invoice->transport_doc_no) ?> <?= !empty($invoice->transport_doc_date) ? '('.date('d M Y', strtotime($invoice->transport_doc_date)).')' : '' ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card mb-3">
        <div class="card-header py-2"><strong>Line Items</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>HSN</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Rate</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Taxable</th>
                            <th class="text-center">GST%</th>
                            <?php if ($invoice->is_interstate): ?>
                                <th class="text-right">IGST</th>
                            <?php else: ?>
                                <th class="text-right">CGST</th>
                                <th class="text-right">SGST</th>
                            <?php endif; ?>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($item->product_name) ?></td>
                            <td><small><?= htmlspecialchars($item->hsn_code ?? '') ?></small></td>
                            <td class="text-center"><?= (float)$item->qty ?> <?= htmlspecialchars($item->unit ?? '') ?></td>
                            <td class="text-right"><?= number_format($item->rate, 2) ?></td>
                            <td class="text-right"><?= number_format($item->discount ?? 0, 2) ?></td>
                            <td class="text-right"><?= number_format($item->taxable_amt ?? ($item->qty * $item->rate - ($item->discount ?? 0)), 2) ?></td>
                            <td class="text-center"><?= (float)($item->gst_percent ?? 0) ?>%</td>
                            <?php if ($invoice->is_interstate): ?>
                                <td class="text-right"><?= number_format($item->igst_amount ?? 0, 2) ?></td>
                            <?php else: ?>
                                <td class="text-right"><?= number_format($item->cgst_amount ?? 0, 2) ?></td>
                                <td class="text-right"><?= number_format($item->sgst_amount ?? 0, 2) ?></td>
                            <?php endif; ?>
                            <td class="text-right font-weight-bold"><?= number_format($item->total ?? 0, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Totals -->
    <div class="row">
        <div class="col-md-4 offset-md-8">
            <table class="table table-sm">
                <tr><td>Taxable Amount</td><td class="text-right"><?= number_format($invoice->taxable_amount, 2) ?></td></tr>
                <?php if ($invoice->is_interstate): ?>
                    <tr><td>IGST</td><td class="text-right"><?= number_format($invoice->igst_amount, 2) ?></td></tr>
                <?php else: ?>
                    <tr><td>CGST</td><td class="text-right"><?= number_format($invoice->cgst_amount, 2) ?></td></tr>
                    <tr><td>SGST</td><td class="text-right"><?= number_format($invoice->sgst_amount, 2) ?></td></tr>
                <?php endif; ?>
                <?php if ((float)$invoice->round_off != 0): ?>
                <tr><td>Round Off</td><td class="text-right"><?= number_format($invoice->round_off, 2) ?></td></tr>
                <?php endif; ?>
                <tr class="font-weight-bold" style="font-size:1.1em;">
                    <td>Grand Total</td>
                    <td class="text-right">&#8377; <?= number_format($invoice->total_amount, 2) ?></td>
                </tr>
                <?php if ((float)$invoice->paid_amount > 0): ?>
                <tr><td>Paid</td><td class="text-right"><?= number_format($invoice->paid_amount, 2) ?></td></tr>
                <?php endif; ?>
                <?php if ((float)$invoice->due_amount > 0): ?>
                <tr class="text-danger"><td>Due</td><td class="text-right"><?= number_format($invoice->due_amount, 2) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- E-Way Bill details popup -->
<div class="modal fade" id="ewbModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <form class="modal-content" id="ewbForm" onsubmit="submitEwb(event)">
      <div class="modal-header py-2">
        <h5 class="modal-title"><i class="fas fa-truck"></i> Generate E-Way Bill — <?= htmlspecialchars($invoice->invoice_no) ?></h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="alert alert-light border py-2 mb-3" style="font-size:.83rem;">
          <strong>From:</strong> <?= htmlspecialchars($__d['place']) ?> — PIN <?= htmlspecialchars($__d['pin'] ?: '?') ?>
          &nbsp;&rarr;&nbsp;
          <strong>To:</strong> <?= htmlspecialchars($__s ? $__s['place'] : $__b['place']) ?> — PIN <?= htmlspecialchars($__s ? $__s['pin'] : ($__b['pin'] ?: '?')) ?>
          <?= $__s ? '<span class="badge badge-info ml-1">Ship-To address</span>' : '' ?>
          <?php if (!empty($addr['errors'])): ?><div class="text-danger mt-1">Address problems are listed on the page — fix them first.</div><?php endif; ?>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label>Transport Mode <span class="text-danger">*</span></label>
            <select name="transport_mode" id="ewbMode" class="form-control form-control-sm" onchange="ewbModeChanged()">
              <?php foreach ([1 => 'Road', 2 => 'Rail', 3 => 'Air', 4 => 'Ship'] as $__k => $__v): ?>
              <option value="<?= $__k ?>" <?= (int)($invoice->transport_mode ?: 1) === $__k ? 'selected' : '' ?>><?= $__v ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-4 ewb-road">
            <label>Vehicle No <span class="text-danger" id="vehReq">*</span></label>
            <input type="text" name="vehicle_no" class="form-control form-control-sm" style="text-transform:uppercase;" placeholder="e.g. HR26AB1234" value="<?= htmlspecialchars($invoice->vehicle_no ?? '') ?>">
          </div>
          <div class="form-group col-md-4 ewb-road">
            <label>Vehicle Type</label>
            <select name="vehicle_type" class="form-control form-control-sm">
              <option value="R" <?= ($invoice->vehicle_type ?? 'R') !== 'O' ? 'selected' : '' ?>>Regular</option>
              <option value="O" <?= ($invoice->vehicle_type ?? '') === 'O' ? 'selected' : '' ?>>Over Dimensional Cargo</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label>Distance (KM)</label>
            <input type="number" name="transport_distance" class="form-control form-control-sm" min="0" max="4000" value="<?= (int)($invoice->transport_distance ?? 0) ?>">
            <small class="text-muted">0 = portal calculates from the PIN codes (recommended). A typed distance may not exceed the portal PIN-to-PIN distance by more than 10%.</small>
          </div>
          <div class="form-group col-md-4">
            <label>Transporter ID <small class="text-muted">(GSTIN)</small></label>
            <input type="text" name="transporter_id" class="form-control form-control-sm" maxlength="15" style="text-transform:uppercase;" value="<?= htmlspecialchars($invoice->transporter_id ?? '') ?>">
          </div>
          <div class="form-group col-md-4">
            <label>Transporter Name</label>
            <input type="text" name="transporter_name" class="form-control form-control-sm" value="<?= htmlspecialchars($invoice->transporter_name ?? '') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>Transport Doc No <span class="text-danger ewb-doc-req" style="display:none;">*</span> <small class="text-muted">(LR / RR / Airway bill / Bill of lading)</small></label>
            <input type="text" name="transport_doc_no" class="form-control form-control-sm" maxlength="15" value="<?= htmlspecialchars($invoice->transport_doc_no ?? '') ?>">
          </div>
          <div class="form-group col-md-6">
            <label>Transport Doc Date <span class="text-danger ewb-doc-req" style="display:none;">*</span></label>
            <input type="date" name="transport_doc_date" class="form-control form-control-sm" value="<?= htmlspecialchars($invoice->transport_doc_date ?? '') ?>">
          </div>
        </div>
        <small class="text-muted">Road: Vehicle No is required — or only a Transporter ID if the transporter will add the vehicle later.</small>
        <div id="ewbFormMsg" class="mt-2"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-info btn-sm" id="ewbSubmit"><i class="fas fa-truck"></i> Save &amp; Generate E-Way Bill</button>
      </div>
    </form>
  </div>
</div>

<?php if (!$__locked): ?>
<!-- Delivery (ship-to) address popup -->
<div class="modal fade" id="shipModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <form class="modal-content" method="post" action="<?= base_url('WholesaleBilling/update_ship/' . (int)$invoice->id) ?>">
      <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
      <div class="modal-header py-2">
        <h5 class="modal-title"><i class="fas fa-map-marker-alt"></i> Delivery (Ship-To) Address</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="custom-control custom-switch mb-2">
          <input type="hidden" name="ship_to_different" value="0">
          <input type="checkbox" class="custom-control-input" id="mShipDiff" name="ship_to_different" value="1" <?= $__s ? 'checked' : '' ?> onchange="document.getElementById('mShipFields').style.display=this.checked?'':'none'">
          <label class="custom-control-label" for="mShipDiff">Ship to a different address</label>
        </div>
        <div id="mShipFields" style="<?= $__s ? '' : 'display:none;' ?>">
          <div class="form-group"><label>Name *</label><input type="text" name="ship_name" class="form-control form-control-sm" maxlength="100" value="<?= htmlspecialchars($invoice->ship_name ?? '') ?>"></div>
          <div class="form-group"><label>GSTIN <small class="text-muted">(if registered)</small></label><input type="text" name="ship_gstin" class="form-control form-control-sm" maxlength="15" style="text-transform:uppercase;" value="<?= htmlspecialchars($invoice->ship_gstin ?? '') ?>"></div>
          <div class="form-group"><label>Address *</label><input type="text" name="ship_address" class="form-control form-control-sm" maxlength="200" value="<?= htmlspecialchars($invoice->ship_address ?? '') ?>"></div>
          <div class="form-row">
            <div class="form-group col-md-6"><label>City / Place *</label><input type="text" name="ship_place" class="form-control form-control-sm" maxlength="100" value="<?= htmlspecialchars($invoice->ship_place ?? '') ?>"></div>
            <div class="form-group col-md-6"><label>PIN Code *</label><input type="text" name="ship_pincode" class="form-control form-control-sm" maxlength="6" value="<?= htmlspecialchars($invoice->ship_pincode ?? '') ?>"></div>
          </div>
          <div class="form-group"><label>State *</label>
            <select name="ship_state_code" class="form-control form-control-sm">
              <option value="">-- Select State --</option>
              <?php foreach ($states as $__c => $__n): ?><option value="<?= $__c ?>" <?= ($invoice->ship_state_code ?? '') === $__c ? 'selected' : '' ?>><?= $__c ?> - <?= htmlspecialchars($__n) ?></option><?php endforeach; ?>
            </select>
          </div>
          <small class="text-muted">A delivery address in another state changes the place of supply (IGST vs CGST+SGST) — that is blocked here; create a new invoice instead.</small>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary btn-sm">Save</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
var invoiceId = <?= (int)$invoice->id ?>;

function apiCall(url, data, onSuccess) {
    var msgEl = document.getElementById('apiMsg');
    msgEl.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Processing...</div>';
    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: data,
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            msgEl.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + d.message + '</div>';
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            var extra = d.api_response ? '<br><small>API response: ' + JSON.stringify(d.api_response).substring(0, 300) + '</small>' : '';
            msgEl.innerHTML = '<div class="alert alert-danger" style="white-space:pre-line;"><i class="fas fa-times-circle"></i> ' + d.message + extra + '</div>';
        }
    })
    .catch(function(e) {
        msgEl.innerHTML = '<div class="alert alert-danger">Network error: ' + e.message + '</div>';
    });
}

function generateIrn() {
    if (!confirm('Generate E-Invoice (IRN) for this invoice?')) return;
    var btn = document.getElementById('btnIrn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...'; }
    apiCall('<?= base_url('wholesale_billing/generate_irn_ajax') ?>', 'invoice_id=' + invoiceId);
}

function generateEwb() {
    document.getElementById('ewbFormMsg').innerHTML = '';
    ewbModeChanged();
    $('#ewbModal').modal('show');
}
function ewbModeChanged() {
    var road = document.getElementById('ewbMode').value === '1';
    document.querySelectorAll('.ewb-road').forEach(function (el) { el.style.display = road ? '' : 'none'; });
    document.querySelectorAll('.ewb-doc-req').forEach(function (el) { el.style.display = road ? 'none' : ''; });
}
function submitEwb(e) {
    e.preventDefault();
    var f = document.getElementById('ewbForm'), msg = document.getElementById('ewbFormMsg'), btn = document.getElementById('ewbSubmit');
    var road = f.transport_mode.value === '1';
    var errs = [];
    if (road && !f.vehicle_no.value.trim() && !f.transporter_id.value.trim()) errs.push('Enter the Vehicle No (or a Transporter ID).');
    if (!road && (!f.transport_doc_no.value.trim() || !f.transport_doc_date.value)) errs.push('Rail / Air / Ship needs the Transport Doc No and Date.');
    if (f.transporter_id.value.trim() && f.transporter_id.value.trim().length !== 15) errs.push('Transporter ID must be 15 characters.');
    if (errs.length) { msg.innerHTML = '<div class="alert alert-danger py-2 mb-0">' + errs.join('<br>') + '</div>'; return; }
    var body = new URLSearchParams(new FormData(f));
    body.append('invoice_id', invoiceId);
    if (!road) { body.set('vehicle_no', ''); }
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    msg.innerHTML = '';
    fetch('<?= base_url('wholesale_billing/generate_ewb_ajax') ?>', {
        method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: body.toString(), credentials: 'same-origin'
    }).then(function (r) { return r.json(); }).then(function (d) {
        var box = document.createElement('div');
        box.className = 'alert py-2 mb-0 ' + (d.success ? 'alert-success' : 'alert-danger');
        box.style.whiteSpace = 'pre-line';
        box.textContent = d.message || (d.success ? 'Done' : 'Failed');
        msg.innerHTML = ''; msg.appendChild(box);
        if (d.success) { setTimeout(function () { location.reload(); }, 1500); }
        else { btn.disabled = false; btn.innerHTML = '<i class="fas fa-truck"></i> Save &amp; Generate E-Way Bill'; }
    }).catch(function (err) {
        msg.innerHTML = '<div class="alert alert-danger py-2 mb-0">Network error</div>';
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-truck"></i> Save &amp; Generate E-Way Bill';
    });
}

function verifyIrn() {
    var msgEl = document.getElementById('apiMsg');
    msgEl.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Verifying IRN with NIC...</div>';
    fetch('<?= base_url('wholesale_billing/verify_irn_ajax') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'invoice_id=' + invoiceId,
        credentials: 'same-origin'
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            var det = d.details || {};
            var html = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> <strong>' + d.message + '</strong>';
            html += '<table class="table table-sm table-bordered mt-2 mb-0" style="font-size:.8rem;background:#fff;">';
            if (det.Status)      html += '<tr><td class="text-muted" style="width:120px;">Status</td><td><strong>' + det.Status + '</strong></td></tr>';
            if (det.AckNo)       html += '<tr><td class="text-muted">Ack No</td><td>' + det.AckNo + '</td></tr>';
            if (det.AckDt)       html += '<tr><td class="text-muted">Ack Date</td><td>' + det.AckDt + '</td></tr>';
            if (det.DocNo)       html += '<tr><td class="text-muted">Doc No</td><td>' + det.DocNo + '</td></tr>';
            if (det.SellerGstin) html += '<tr><td class="text-muted">Seller GSTIN</td><td>' + det.SellerGstin + '</td></tr>';
            if (det.BuyerGstin)  html += '<tr><td class="text-muted">Buyer GSTIN</td><td>' + det.BuyerGstin + '</td></tr>';
            if (det.TotInvVal)   html += '<tr><td class="text-muted">Invoice Value</td><td>₹ ' + det.TotInvVal + '</td></tr>';
            html += '</table></div>';
            msgEl.innerHTML = html;
        } else {
            msgEl.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + d.message + '</div>';
        }
    })
    .catch(function(e) {
        msgEl.innerHTML = '<div class="alert alert-danger">Network error: ' + e.message + '</div>';
    });
}

function cancelIrn() {
    var reason = prompt('Reason for cancelling IRN:', 'Order cancelled');
    if (!reason) return;
    apiCall('<?= base_url('wholesale_billing/cancel_irn_ajax') ?>', 'invoice_id=' + invoiceId + '&reason=' + encodeURIComponent(reason));
}

function cancelEwb() {
    var reason = prompt('Reason for cancelling E-Way Bill:', 'Order cancelled');
    if (!reason) return;
    apiCall('<?= base_url('wholesale_billing/cancel_ewb_ajax') ?>', 'invoice_id=' + invoiceId + '&reason=' + encodeURIComponent(reason));
}
</script>
