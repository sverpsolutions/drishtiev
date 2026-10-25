<?php // FILE: application/views/settings/index.php ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="font-weight-bold mb-0">Settings</h5>
        <small class="text-muted">Company profile, GST details &amp; email configuration</small>
    </div>
</div>

<?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $this->session->flashdata('success') ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
<?php endif; ?>

<?= form_open_multipart('settings/save') ?>
<div class="row">

    <!-- Retail Store Mode / Business Classification (FMCG vs Clothing vs Both) -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm" style="border-top: 4px solid #4f46e5 !important; background: #ffffff;">
            <div class="card-header bg-white py-3 font-weight-bold d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 font-weight-bold text-dark"><i class="fas fa-store-alt text-primary mr-2"></i> Retail Store Mode &amp; Business Type</h6>
                    <small class="text-muted">Choose whether your store sells FMCG/Grocery, Clothing/Garments, or Both. This setting controls the Item Master and navigation.</small>
                </div>
                <span class="badge badge-primary px-3 py-2 font-weight-bold" style="font-size: .8rem;">Catalog &amp; Item Master Filter</span>
            </div>
            <div class="card-body">
                <?php 
                $cur_store_type = $settings['retail_business_type'] ?? 'both';
                ?>
                                <div class="row">
                    <!-- Option 1: EV Dealership (Active) -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="d-block h-100 p-3 rounded border text-decoration-none shadow-sm <?= $cur_store_type === 'ev_dealership' ? 'border-success bg-light' : 'border-light' ?>" style="cursor: pointer; border-width: 2px !important;">
                            <div class="d-flex align-items-center mb-2">
                                <input type="radio" name="retail_business_type" value="ev_dealership" <?= $cur_store_type === 'ev_dealership' ? 'checked' : '' ?> class="mr-2" style="width:18px;height:18px;">
                                <strong class="text-success" style="font-size: .95rem;"><i class="fas fa-charging-station mr-1"></i> EV Dealership</strong>
                            </div>
                            <p class="small text-muted mb-2">
                                Electric scooter showroom &amp; service center. Dedicated serial tracking (VIN, Motor, Battery), EV warranty, EV tax rules, and vehicle color master.
                            </p>
                            <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Active EV Dealership</span>
                        </label>
                    </div>

                    <!-- Option 2: Both (Hybrid) -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="d-block h-100 p-3 rounded border text-decoration-none shadow-sm <?= $cur_store_type === 'both' ? 'border-primary bg-light' : 'border-light' ?>" style="cursor: pointer; border-width: 2px !important;">
                            <div class="d-flex align-items-center mb-2">
                                <input type="radio" name="retail_business_type" value="both" <?= $cur_store_type === 'both' ? 'checked' : '' ?> class="mr-2" style="width:18px;height:18px;">
                                <strong class="text-primary" style="font-size: .95rem;"><i class="fas fa-layer-group mr-1"></i> FMCG + Clothing</strong>
                            </div>
                            <p class="small text-muted mb-2">
                                Legacy mode for stores selling packaged grocery goods and apparel.
                            </p>
                            <span class="badge badge-info"><i class="fas fa-sliders-h mr-1"></i> Dual Retail Mode</span>
                        </label>
                    </div>

                    <!-- Option 3: FMCG / Grocery Only -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="d-block h-100 p-3 rounded border text-decoration-none shadow-sm <?= $cur_store_type === 'fmcg_only' ? 'border-success bg-light' : 'border-light' ?>" style="cursor: pointer; border-width: 2px !important;">
                            <div class="d-flex align-items-center mb-2">
                                <input type="radio" name="retail_business_type" value="fmcg_only" <?= $cur_store_type === 'fmcg_only' ? 'checked' : '' ?> class="mr-2" style="width:18px;height:18px;">
                                <strong class="text-secondary" style="font-size: .95rem;"><i class="fas fa-shopping-basket mr-1"></i> Grocery Only</strong>
                            </div>
                            <p class="small text-muted mb-2">
                                Pure grocery store. All clothing fields and garment menus are hidden.
                            </p>
                            <span class="badge badge-secondary"><i class="fas fa-check mr-1"></i> Grocery Mode</span>
                        </label>
                    </div>

                    <!-- Option 4: Clothing / Garments Only -->
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="d-block h-100 p-3 rounded border text-decoration-none shadow-sm <?= $cur_store_type === 'clothing_only' ? 'border-primary bg-light' : 'border-light' ?>" style="cursor: pointer; border-width: 2px !important;">
                            <div class="d-flex align-items-center mb-2">
                                <input type="radio" name="retail_business_type" value="clothing_only" <?= $cur_store_type === 'clothing_only' ? 'checked' : '' ?> class="mr-2" style="width:18px;height:18px;">
                                <strong style="font-size: .95rem; color: #4f46e5;"><i class="fas fa-tshirt mr-1"></i> Clothing Only</strong>
                            </div>
                            <p class="small text-muted mb-2">
                                Pure apparel store with size sets and fabric specifications.
                            </p>
                            <span class="badge badge-primary" style="background:#4f46e5;"><i class="fas fa-tshirt mr-1"></i> Apparel Mode</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Company Profile -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">
                &#127970; Company Profile
            </div>
            <div class="card-body">
                <?php
                $fields = [
                    ['company_name',    'Company / Firm Name', 'text'],
                    ['company_tagline', 'Tagline / Slogan',    'text'],
                    ['company_address', 'Address',             'textarea'],
                    ['company_phone',   'Phone',               'text'],
                    ['company_email',   'Email',               'email'],
                    ['company_state',   'State',               'text'],
                    ['company_state_code','State Code (GST)', 'text'],
                    ['company_city',    'City (for e-Invoice)', 'text'],
                    ['company_pincode', 'Pincode (for e-Invoice)', 'text'],
                    ['company_annual_turnover', 'Annual Turnover (₹) — determines if e-Invoice/6-digit HSN is mandatory', 'number'],
                ];
                foreach ($fields as [$key, $label, $type]):
                    $val = htmlspecialchars($settings[$key] ?? '');
                ?>
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;"><?= $label ?></label>
                    <?php if ($type === 'textarea'): ?>
                        <textarea name="<?= $key ?>" class="form-control form-control-sm" rows="2"><?= $val ?></textarea>
                    <?php else: ?>
                        <input type="<?= $type ?>" name="<?= $key ?>" class="form-control form-control-sm" value="<?= $val ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Customer Portal Theme -->
    <?php
        $portal_primary = htmlspecialchars($settings['portal_primary_color'] ?? '#3B2314');
        $portal_accent  = htmlspecialchars($settings['portal_accent_color']  ?? '#F6CD3C');
    ?>
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">
                🎨 Customer Portal Theme
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                    💡 Pick two colours to match your logo. <strong>Primary</strong> is the main brand colour
                    (headers, buttons); <strong>Accent</strong> is the highlight colour (badges, icons).
                    Lighter/darker shades are generated automatically. Applies to the customer login &amp; shopping portal.
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label style="font-size:.83rem;font-weight:600;">Primary Colour</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <input type="color" name="portal_primary_color" value="<?= $portal_primary ?>"
                                           style="width:42px;padding:2px;cursor:pointer;border:1px solid #ced4da;border-right:0;"
                                           oninput="document.getElementById('pp_hex').value=this.value;updateThemePreview();">
                                </div>
                                <input type="text" id="pp_hex" class="form-control form-control-sm" value="<?= $portal_primary ?>"
                                       maxlength="7" placeholder="#3B2314"
                                       oninput="var c=document.querySelector('[name=portal_primary_color]');if(/^#[0-9a-fA-F]{6}$/.test(this.value)){c.value=this.value;}updateThemePreview();">
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label style="font-size:.83rem;font-weight:600;">Accent Colour</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <input type="color" name="portal_accent_color" value="<?= $portal_accent ?>"
                                           style="width:42px;padding:2px;cursor:pointer;border:1px solid #ced4da;border-right:0;"
                                           oninput="document.getElementById('pa_hex').value=this.value;updateThemePreview();">
                                </div>
                                <input type="text" id="pa_hex" class="form-control form-control-sm" value="<?= $portal_accent ?>"
                                       maxlength="7" placeholder="#F6CD3C"
                                       oninput="var c=document.querySelector('[name=portal_accent_color]');if(/^#[0-9a-fA-F]{6}$/.test(this.value)){c.value=this.value;}updateThemePreview();">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-2" style="border:1px solid #eee;border-radius:10px;overflow:hidden;">
                    <div id="tp_header" style="padding:12px 14px;color:#fff;font-weight:700;font-size:.85rem;">
                        Portal Preview
                    </div>
                    <div class="d-flex align-items-center" style="gap:8px;padding:12px 14px;background:#fff;">
                        <button type="button" id="tp_btn" class="btn btn-sm text-white" style="font-weight:600;border:0;">Primary Button</button>
                        <span id="tp_badge" class="badge" style="font-weight:700;padding:5px 10px;">Accent</span>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">Leave defaults for the classic brown &amp; gold theme. Customers may need to refresh once.</small>

                <hr class="my-3">
                <?php $portal_mobile_url = htmlspecialchars($settings['portal_mobile_url'] ?? ''); ?>
                <div class="form-group mb-0">
                    <label style="font-size:.83rem;font-weight:600;">Mobile Portal Link</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="portal_mobile_url" name="portal_mobile_url" class="form-control form-control-sm"
                               value="<?= $portal_mobile_url ?>" placeholder="https://online.ncg.sverpsolutions.com">
                        <div class="input-group-append">
                            <a href="<?= $portal_mobile_url ? $portal_mobile_url : base_url('portal') ?>" target="_blank"
                               class="btn btn-outline-dark btn-sm" id="portal_mobile_open_btn"><i class="fas fa-external-link-alt mr-1"></i>Open</a>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1">If you've set up a friendlier subdomain for customers (e.g. <code>online.ncg.sverpsolutions.com</code>), enter it here. Leave blank to keep using <code><?= base_url('portal') ?></code> — used for the Registration QR code below too.</small>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.getElementById('portal_mobile_url').addEventListener('input', function() {
        document.getElementById('portal_mobile_open_btn').href = this.value.trim() || '<?= base_url('portal') ?>';
    });
    function updateThemePreview() {
        var p = document.querySelector('[name=portal_primary_color]').value;
        var a = document.querySelector('[name=portal_accent_color]').value;
        document.getElementById('tp_header').style.background = p;
        document.getElementById('tp_btn').style.background = p;
        document.getElementById('tp_badge').style.background = a;
        document.getElementById('tp_badge').style.color = '#3B2314';
    }
    document.addEventListener('DOMContentLoaded', updateThemePreview);
    </script>

    <!-- Admin Top Bar & Login Page Theme -->
    <?php
        $admin_primary = htmlspecialchars($settings['admin_primary_color'] ?? '#F6CD3C');
        $admin_accent  = htmlspecialchars($settings['admin_accent_color']  ?? '#E5A823');
    ?>
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">
                🖥️ Admin Top Bar &amp; Login Page Theme
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                    💡 <strong>Primary</strong> is the top bar / login header background; <strong>Accent</strong> is
                    its bottom border. Text colour switches automatically between light and dark so it stays readable.
                    Applies to this ERP's own top navigation bar and login screen (not the customer portal).
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label style="font-size:.83rem;font-weight:600;">Primary Colour</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <input type="color" name="admin_primary_color" value="<?= $admin_primary ?>"
                                           style="width:42px;padding:2px;cursor:pointer;border:1px solid #ced4da;border-right:0;"
                                           oninput="document.getElementById('ap_hex').value=this.value;updateAdminThemePreview();">
                                </div>
                                <input type="text" id="ap_hex" class="form-control form-control-sm" value="<?= $admin_primary ?>"
                                       maxlength="7" placeholder="#F6CD3C"
                                       oninput="var c=document.querySelector('[name=admin_primary_color]');if(/^#[0-9a-fA-F]{6}$/.test(this.value)){c.value=this.value;}updateAdminThemePreview();">
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label style="font-size:.83rem;font-weight:600;">Accent Colour</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <input type="color" name="admin_accent_color" value="<?= $admin_accent ?>"
                                           style="width:42px;padding:2px;cursor:pointer;border:1px solid #ced4da;border-right:0;"
                                           oninput="document.getElementById('aa_hex').value=this.value;updateAdminThemePreview();">
                                </div>
                                <input type="text" id="aa_hex" class="form-control form-control-sm" value="<?= $admin_accent ?>"
                                       maxlength="7" placeholder="#E5A823"
                                       oninput="var c=document.querySelector('[name=admin_accent_color]');if(/^#[0-9a-fA-F]{6}$/.test(this.value)){c.value=this.value;}updateAdminThemePreview();">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-2" style="border:1px solid #eee;border-radius:10px;overflow:hidden;">
                    <div id="at_header" style="padding:12px 14px;font-weight:700;font-size:.85rem;border-bottom:3px solid;">
                        Top Bar / Login Header Preview
                    </div>
                </div>
                <small class="text-muted d-block mt-2">Leave defaults for the classic gold theme. Takes effect on next page load / re-login.</small>
            </div>
        </div>
    </div>
    <script>
    function _adminContrastText(hex) {
        hex = (hex || '').replace('#', '');
        if (hex.length !== 6) return '#3B2314';
        var r = parseInt(hex.substr(0,2),16), g = parseInt(hex.substr(2,2),16), b = parseInt(hex.substr(4,2),16);
        var luminance = (0.299*r + 0.587*g + 0.114*b) / 255;
        return luminance > 0.55 ? '#3B2314' : '#FFFFFF';
    }
    function updateAdminThemePreview() {
        var p = document.querySelector('[name=admin_primary_color]').value;
        var a = document.querySelector('[name=admin_accent_color]').value;
        var header = document.getElementById('at_header');
        header.style.background = p;
        header.style.borderBottomColor = a;
        header.style.color = _adminContrastText(p);
    }
    document.addEventListener('DOMContentLoaded', updateAdminThemePreview);
    </script>

    <!-- Customer Portal Banners -->
    <div class="col-md-12 mb-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem; display:flex; justify-content:space-between; align-items:center;">
                <span>&#128248; Customer Portal Banners</span>
                <span class="badge badge-info" style="font-size:0.75rem;">Recommended Size: 800 x 400 pixels (JPEG/PNG)</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php for ($i = 1; $i <= 4; $i++): $bkey = 'portal_banner_' . $i; $bval = $settings[$bkey] ?? ''; $lkey = 'portal_banner_' . $i . '_link'; $lval = $settings[$lkey] ?? ''; ?>
                    <div class="col-md-3 text-center mb-3">
                        <label class="font-weight-bold" style="font-size:.8rem;">Banner <?= $i ?></label>
                        <div class="mb-2" style="border:1px dashed #ccc; padding:5px; border-radius:6px; min-height:80px; display:flex; align-items:center; justify-content:center; background:#f9f9f9;">
                            <?php if (!empty($bval)): ?>
                                <img src="<?= base_url($bval) ?>" style="max-width:100%; max-height:80px; object-fit:contain; border-radius:4px;">
                            <?php else: ?>
                                <span class="text-muted" style="font-size:0.75rem;">No Banner</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="<?= $bkey ?>" class="form-control-file mb-2" accept="image/png, image/jpeg, image/gif" style="font-size:.75rem;">
                        <div class="input-group input-group-sm">
                            <select class="form-control" onchange="if(this.value) { this.nextElementSibling.value = this.value; this.value=''; }" style="font-size:.7rem; max-width: 32px; padding: 0 4px; border-top-right-radius: 0; border-bottom-right-radius: 0;" title="Quick Select Category/Brand">
                                <option value="">&#9881;</option>
                                <optgroup label="Categories">
                                    <?php foreach($categories ?? [] as $c): ?>
                                    <option value="?category=<?= urlencode($c->name) ?>"><?= htmlspecialchars($c->name) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Brands">
                                    <?php foreach($brands ?? [] as $b): ?>
                                    <option value="?brand=<?= urlencode($b->name) ?>"><?= htmlspecialchars($b->name) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                            <input type="text" name="<?= $lkey ?>" value="<?= htmlspecialchars($lval) ?>" class="form-control" placeholder="Link (or ?brand=X)" style="font-size:.7rem;">
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
                <small class="text-muted"><i class="fa fa-info-circle"></i> Uploading a new image will automatically replace the old one. Keep the file size under 2MB for faster loading.</small>
            </div>
        </div>
    </div>

    <!-- GST & Tax -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">
                &#129534; GST &amp; Tax Details
            </div>
            <div class="card-body">
                <?php
                $gst_fields = [
                    ['company_gstin', 'GSTIN (15 chars)', 'text'],
                    ['company_pan',   'PAN Number',       'text'],
                ];
                foreach ($gst_fields as [$key, $label, $type]):
                    $val = htmlspecialchars($settings[$key] ?? '');
                ?>
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;"><?= $label ?></label>
                    <input type="<?= $type ?>" name="<?= $key ?>" class="form-control form-control-sm"
                           value="<?= $val ?>" style="text-transform:uppercase;">
                </div>
                <?php endforeach; ?>

                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;">Invoice Terms</label>
                    <textarea name="invoice_terms" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($settings['invoice_terms'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Numbering -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">
                &#128290; Document Numbering Prefixes
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $prefix_fields = [
                        ['invoice_prefix',  'Invoice Prefix'],
                        ['purchase_prefix', 'Purchase Prefix'],
                        ['prn_prefix',      'PRN Prefix'],
                        ['payment_prefix',  'Payment Prefix'],
                    ];
                    foreach ($prefix_fields as [$key, $label]):
                    ?>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label style="font-size:.82rem;font-weight:600;"><?= $label ?></label>
                            <input type="text" name="<?= $key ?>" class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($settings[$key] ?? '') ?>" maxlength="6">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- SMTP Email Config -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold d-flex align-items-center justify-content-between" style="font-size:.85rem;">
                <div>&#128231; Email / SMTP Configuration</div>
                <a href="<?= base_url('settings/test_smtp_email') ?>" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem; font-weight:600;"><i class="fa fa-paper-plane mr-1"></i> Send Test OTP</a>
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                    &#128161; For Gmail: use App Password (not your Google password).
                    <a href="https://myaccount.google.com/apppasswords" target="_blank">Create App Password &rarr;</a>
                </div>
                <?php
                $smtp_fields = [
                    ['smtp_host', 'SMTP Host',     'text',     'smtp.gmail.com'],
                    ['smtp_port', 'SMTP Port',     'number',   '587'],
                    ['smtp_user', 'SMTP Username', 'email',    'your@gmail.com'],
                    ['smtp_pass', 'SMTP Password', 'password', ''],
                ];
                foreach ($smtp_fields as [$key, $label, $type, $placeholder]):
                    $val = $key === 'smtp_pass' ? '' : htmlspecialchars($settings[$key] ?? '');
                ?>
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;"><?= $label ?></label>
                    <input type="<?= $type ?>" name="<?= $key ?>" class="form-control form-control-sm"
                           value="<?= $val ?>" placeholder="<?= $placeholder ?>"
                           <?= $key === 'smtp_pass' ? 'autocomplete="new-password"' : '' ?>>
                    <?php if ($key === 'smtp_pass'): ?>
                    <small class="text-muted">Leave blank to keep existing password.</small>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- WhatsApp Integration -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;background:#25D366;color:#fff;">
                <i class="fab fa-whatsapp"></i> WhatsApp Integration
            </div>
            <div class="card-body">
                <div class="alert alert-success py-2 mb-3" style="font-size:.82rem;">
                    <strong>How it works:</strong> When you click "Send via WhatsApp" on any invoice, it opens WhatsApp Web with a pre-filled message. No API key required — uses <code>wa.me</code> links.
                </div>
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;">Default Country Code</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text">+</span></div>
                        <input type="number" name="whatsapp_country" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($settings['whatsapp_country'] ?? '91') ?>" placeholder="91">
                    </div>
                    <small class="text-muted">India = 91. Used when phone has only 10 digits.</small>
                </div>
                <div class="form-group mb-0">
                    <label style="font-size:.83rem;font-weight:600;">Default WhatsApp Message Footer</label>
                    <textarea name="whatsapp_footer_msg" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($settings['whatsapp_footer_msg'] ?? 'Thank you for your business!') ?></textarea>
                    <small class="text-muted">Appended to all WhatsApp invoice messages.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- WhatsApp Marketing API (premium campaign module — separate from the plain wa.me links above) -->
    <div class="col-md-6 mb-4" id="whatsapp-marketing">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 font-weight-bold d-flex align-items-center justify-content-between" style="font-size:.85rem;background:#075E54;color:#fff;">
                <div><i class="fab fa-whatsapp"></i> WhatsApp Marketing API</div>
                <a href="<?= base_url('settings/test_whatsapp_connection') ?>" class="btn btn-sm btn-outline-light" style="font-size:0.75rem; font-weight:600;"><i class="fa fa-plug mr-1"></i> Test Connection</a>
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                    Credentials for the Marketing Campaign module's automated WhatsApp Business Cloud API sending (separate from the manual <code>wa.me</code> links above). Marketing sends still require Meta-approved message templates — see Meta Business Manager.
                </div>
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;">Phone Number ID</label>
                    <input type="text" name="whatsapp_phone_number_id" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['whatsapp_phone_number_id'] ?? '') ?>" placeholder="e.g. 109876543210987">
                </div>
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;">WhatsApp Business Account ID</label>
                    <input type="text" name="whatsapp_business_account_id" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['whatsapp_business_account_id'] ?? '') ?>" placeholder="e.g. 123456789012345">
                </div>
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;">Access Token</label>
                    <input type="password" name="whatsapp_access_token" class="form-control form-control-sm"
                           value="" placeholder="<?= !empty($settings['whatsapp_access_token']) ? '••••••••••••••••' : 'Permanent access token' ?>" autocomplete="new-password">
                    <small class="text-muted">Leave blank to keep the existing token.</small>
                </div>
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;">Webhook Verify Token</label>
                    <input type="text" name="whatsapp_webhook_verify_token" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['whatsapp_webhook_verify_token'] ?? '') ?>" placeholder="Any string you choose — enter the same one in Meta's webhook setup">
                    <small class="text-muted">Webhook URL: <code><?= base_url('whatsapp/webhook') ?></code></small>
                </div>
                <div class="form-group mb-0">
                    <label style="font-size:.83rem;font-weight:600;">AI Copywriter API Key (Anthropic)</label>
                    <input type="password" name="whatsapp_ai_api_key" class="form-control form-control-sm"
                           value="" placeholder="<?= !empty($settings['whatsapp_ai_api_key']) ? '••••••••••••••••' : 'sk-ant-...'?>" autocomplete="new-password">
                    <small class="text-muted">Leave blank to keep the existing key. Used only for AI-generated campaign headlines/captions.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- GSP API — E-Invoice & E-Way Bill (Adaequare) -->
    <div class="col-md-6 mb-4" id="gsp-api">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 font-weight-bold" style="font-size:.85rem;background:#1a237e;color:#fff;">
                <i class="fas fa-file-contract"></i> E-Invoice &amp; E-Way Bill API (GSP)
            </div>
            <div class="card-body">
                <?php $__prov = ($settings['einvoice_provider'] ?? 'adaequare') === 'gstzen' ? 'gstzen' : 'adaequare'; ?>
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:700;">API Provider</label>
                    <select name="einvoice_provider" id="einvoiceProvider" class="form-control form-control-sm" onchange="toggleEinvProvider()">
                        <option value="adaequare" <?= $__prov === 'adaequare' ? 'selected' : '' ?>>Adaequare GSP</option>
                        <option value="gstzen" <?= $__prov === 'gstzen' ? 'selected' : '' ?>>GSTZen</option>
                    </select>
                    <small class="text-muted">Wholesale Billing uses this provider for E-Invoice (IRN) and E-Way Bill.</small>
                </div>

                <div id="provAdaequare" style="<?= $__prov === 'adaequare' ? '' : 'display:none;' ?>">
                <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                    Adaequare GSP credentials for direct E-Invoice (IRN) and E-Way Bill generation from Wholesale Billing. Toggle <strong>Sandbox</strong> ON for testing.
                </div>

                <h6 class="text-primary" style="font-size:.82rem;font-weight:700;border-bottom:1px solid #ddd;padding-bottom:4px;">E-Invoice (IRN)</h6>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label style="font-size:.83rem;font-weight:600;">Client ID</label>
                        <input type="text" name="gsp_einvoice_client_id" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($settings['gsp_einvoice_client_id'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label style="font-size:.83rem;font-weight:600;">Client Secret</label>
                        <input type="password" name="gsp_einvoice_client_secret" class="form-control form-control-sm"
                               value="" placeholder="<?= !empty($settings['gsp_einvoice_client_secret']) ? '••••••••••••' : '' ?>" autocomplete="new-password">
                        <small class="text-muted">Leave blank to keep existing.</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label style="font-size:.83rem;font-weight:600;">Username</label>
                        <input type="text" name="gsp_einvoice_username" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($settings['gsp_einvoice_username'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label style="font-size:.83rem;font-weight:600;">Password</label>
                        <input type="password" name="gsp_einvoice_password" class="form-control form-control-sm"
                               value="" placeholder="<?= !empty($settings['gsp_einvoice_password']) ? '••••••••••••' : '' ?>" autocomplete="new-password">
                        <small class="text-muted">Leave blank to keep existing.</small>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="hidden" name="gsp_einvoice_sandbox" value="0">
                        <input type="checkbox" class="custom-control-input" id="gsp_ei_sandbox" name="gsp_einvoice_sandbox" value="1" <?= ($settings['gsp_einvoice_sandbox'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="gsp_ei_sandbox" style="font-size:.83rem;font-weight:600;">Sandbox Mode (Testing)</label>
                    </div>
                </div>

                <h6 class="text-success mt-3" style="font-size:.82rem;font-weight:700;border-bottom:1px solid #ddd;padding-bottom:4px;">E-Way Bill</h6>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label style="font-size:.83rem;font-weight:600;">Client ID</label>
                        <input type="text" name="gsp_ewb_client_id" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($settings['gsp_ewb_client_id'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label style="font-size:.83rem;font-weight:600;">Client Secret</label>
                        <input type="password" name="gsp_ewb_client_secret" class="form-control form-control-sm"
                               value="" placeholder="<?= !empty($settings['gsp_ewb_client_secret']) ? '••••••••••••' : '' ?>" autocomplete="new-password">
                        <small class="text-muted">Leave blank to keep existing.</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label style="font-size:.83rem;font-weight:600;">Username</label>
                        <input type="text" name="gsp_ewb_username" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($settings['gsp_ewb_username'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label style="font-size:.83rem;font-weight:600;">Password</label>
                        <input type="password" name="gsp_ewb_password" class="form-control form-control-sm"
                               value="" placeholder="<?= !empty($settings['gsp_ewb_password']) ? '••••••••••••' : '' ?>" autocomplete="new-password">
                        <small class="text-muted">Leave blank to keep existing.</small>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="hidden" name="gsp_ewb_sandbox" value="0">
                        <input type="checkbox" class="custom-control-input" id="gsp_ewb_sandbox" name="gsp_ewb_sandbox" value="1" <?= ($settings['gsp_ewb_sandbox'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="gsp_ewb_sandbox" style="font-size:.83rem;font-weight:600;">Sandbox Mode (Testing)</label>
                    </div>
                </div>

                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;">Sandbox GSTIN</label>
                    <input type="text" name="gsp_sandbox_gstin" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['gsp_sandbox_gstin'] ?? '') ?>" maxlength="15" placeholder="Used instead of company GSTIN in sandbox mode">
                    <small class="text-muted">Leave blank to use company GSTIN. Sandbox: <code>02AMBPG7773M002</code></small>
                </div>
                </div><!-- /provAdaequare -->

                <div id="provGstzen" style="<?= $__prov === 'gstzen' ? '' : 'display:none;' ?>">
                    <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                        <strong>GSTZen</strong> — one auth token for both E-Invoice and E-Way Bill (E-Way Bill is generated from the IRN).
                        Get the token in your GSTZen account: click your GSTIN &rarr; E-Invoicing &rarr; Tally/SAP Integration.
                    </div>
                    <div class="form-group">
                        <label style="font-size:.83rem;font-weight:600;">Auth Token</label>
                        <input type="password" name="gstzen_token" class="form-control form-control-sm"
                               value="" placeholder="<?= !empty($settings['gstzen_token']) ? '••••••••••••' : 'Blank in sandbox = GSTZen public demo token' ?>" autocomplete="new-password">
                        <small class="text-muted">Leave blank to keep existing.</small>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="hidden" name="gstzen_sandbox" value="0">
                            <input type="checkbox" class="custom-control-input" id="gstzen_sandbox" name="gstzen_sandbox" value="1" <?= ($settings['gstzen_sandbox'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="gstzen_sandbox" style="font-size:.83rem;font-weight:600;">Sandbox Mode (Testing)</label>
                        </div>
                        <small class="text-muted">Sandbox sends invoices to the Govt <em>test</em> portal using a GSTZen demo seller GSTIN for your state (e.g. Haryana <code>06AADCG4992P1ZX</code>), so tax type (CGST/SGST vs IGST) stays as on the invoice. Turn OFF only after adding your own GSTIN in GSTZen and saving your real token.</small>
                    </div>
                    <div class="form-group">
                        <label style="font-size:.83rem;font-weight:600;">Sandbox Seller GSTIN <span class="text-muted font-weight-normal">(optional)</span></label>
                        <input type="text" name="gstzen_sandbox_gstin" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($settings['gstzen_sandbox_gstin'] ?? '') ?>" maxlength="15" placeholder="Blank = GSTZen demo GSTIN for your state">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="testGstzen()"><i class="fas fa-plug mr-1"></i> Test GSTZen Connection</button>
                    <small class="text-muted ml-1">Save settings first — the test uses the saved token.</small>
                    <div id="gstzenTestResult" class="mt-2" style="font-size:.83rem;"></div>
                </div>

                <h6 class="text-dark mt-3" style="font-size:.82rem;font-weight:700;border-bottom:1px solid #ddd;padding-bottom:4px;">Company Details (for API)</h6>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label style="font-size:.83rem;font-weight:600;">Pincode</label>
                        <input type="text" name="company_pincode" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($settings['company_pincode'] ?? '') ?>" maxlength="6">
                    </div>
                    <div class="form-group col-md-4">
                        <label style="font-size:.83rem;font-weight:600;">City</label>
                        <input type="text" name="company_city" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($settings['company_city'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-4 mb-0">
                        <label style="font-size:.83rem;font-weight:600;">Annual Turnover</label>
                        <input type="number" name="company_annual_turnover" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($settings['company_annual_turnover'] ?? '0') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Threshold -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">
                &#9888; Inventory Thresholds
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;">Low Stock Alert Threshold (units)</label>
                    <input type="number" name="low_stock_threshold" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['low_stock_threshold'] ?? '10') ?>" min="1">
                    <small class="text-muted">Products with stock ≤ this value show as low-stock alerts.</small>
                </div>
                <div class="form-group mb-0">
                    <label style="font-size:.83rem;font-weight:600;">Expiry Warning Days</label>
                    <input type="number" name="expiry_warning_days" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['expiry_warning_days'] ?? '30') ?>" min="1">
                    <small class="text-muted">Warn when expiry is within this many days.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- UPI Scan & Pay Settings -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold" style="font-size:.85rem;">
                💳 UPI Scan &amp; Pay Settings
            </div>
            <div class="card-body">
                <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                    💡 Enable QR code payments on invoice PDFs and printouts by entering your UPI ID below.
                </div>
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;">UPI ID</label>
                    <input type="text" name="company_upi_id" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['company_upi_id'] ?? '') ?>" placeholder="e.g., ezeeneeds@oksbi">
                </div>
                <div class="form-group mb-0">
                    <label style="font-size:.83rem;font-weight:600;">Merchant Name</label>
                    <input type="text" name="company_upi_name" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['company_upi_name'] ?? '') ?>" placeholder="e.g., View & Chew">
                    <small class="text-muted">If empty, defaults to Company Name.</small>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ══ Navigation Layout ══ -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 font-weight-bold" style="background:#1B3A6B;color:#fff;">
                🖥️ Navigation Layout
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Choose how the navigation menu appears for all users. Change takes effect on next page load.</p>
                <div class="row">
                    <div class="col-6">
                        <label class="d-block p-3 border rounded text-center cursor-pointer <?= ($settings['menu_layout'] ?? 'top_nav') === 'top_nav' ? 'border-primary bg-primary text-white' : 'border-light bg-light' ?>" style="cursor:pointer;">
                            <input type="radio" name="menu_layout" value="top_nav" class="d-none"
                                   <?= ($settings['menu_layout'] ?? 'top_nav') === 'top_nav' ? 'checked' : '' ?>
                                   onchange="this.form.querySelectorAll('.layout-opt').forEach(e=>e.classList.remove('border-primary','bg-primary','text-white')); this.closest('.layout-opt').classList.add('border-primary','bg-primary','text-white');">
                            <div class="mb-2" style="font-size:1.8rem;">🔝</div>
                            <div class="font-weight-bold" style="font-size:.85rem;">Top Navigation</div>
                            <div style="font-size:.72rem;opacity:.8;">Horizontal menu bar<br>(current style)</div>
                        </label>
                    </div>
                    <div class="col-6">
                        <label class="d-block p-3 border rounded text-center cursor-pointer <?= ($settings['menu_layout'] ?? 'top_nav') === 'sidebar' ? 'border-primary bg-primary text-white' : 'border-light bg-light' ?>" style="cursor:pointer;">
                            <input type="radio" name="menu_layout" value="sidebar" class="d-none"
                                   <?= ($settings['menu_layout'] ?? 'top_nav') === 'sidebar' ? 'checked' : '' ?>
                                   onchange="this.form.querySelectorAll('.layout-opt').forEach(e=>e.classList.remove('border-primary','bg-primary','text-white')); this.closest('.layout-opt').classList.add('border-primary','bg-primary','text-white');">
                            <div class="mb-2" style="font-size:1.8rem;">◀️</div>
                            <div class="font-weight-bold" style="font-size:.85rem;">Left Sidebar</div>
                            <div style="font-size:.72rem;opacity:.8;">Fixed sidebar menu<br>with collapsible groups</div>
                        </label>
                    </div>
                </div>
                <script>
                document.querySelectorAll('input[name="menu_layout"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        document.querySelectorAll('label.layout-opt, label[style*="cursor:pointer"]').forEach(l => {
                            l.classList.remove('border-primary','bg-primary','text-white');
                            l.classList.add('border-light','bg-light');
                        });
                        this.closest('label').classList.add('border-primary','bg-primary','text-white');
                        this.closest('label').classList.remove('border-light','bg-light');
                    });
                });
                </script>
            </div>
        </div>
    </div>
</div>

<!-- ══ NEW: ERP Feature Toggles ══ -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-primary text-white py-2 font-weight-bold">⚙️ Feature Controls</div>
            <div class="card-body">

                <!-- Estimate Stock Check -->
                <div class="form-group d-flex justify-content-between align-items-center">
                    <div>
                        <b>Estimate Stock Check</b>
                        <div class="text-muted small">Show ⚠ stock warning while making estimates</div>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="est_stock"
                               name="enable_estimate_stock_check" value="1"
                               <?= !empty($settings['enable_estimate_stock_check']) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="est_stock"></label>
                    </div>
                </div>
                <hr class="my-2">

                <!-- Block Estimate if No Stock -->
                <div class="form-group d-flex justify-content-between align-items-center">
                    <div>
                        <b>Block Estimate if No Stock</b>
                        <div class="text-muted small">Prevent saving estimate when stock is 0</div>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="block_est"
                               name="block_estimate_if_no_stock" value="1"
                               <?= !empty($settings['block_estimate_if_no_stock']) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="block_est"></label>
                    </div>
                </div>
                <hr class="my-2">

                <!-- Bill Modify -->
                <div class="form-group d-flex justify-content-between align-items-center">
                    <div>
                        <b>Allow Bill Modification</b>
                        <div class="text-muted small">Let admins/permitted users edit saved invoices</div>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="bill_mod"
                               name="enable_bill_modify" value="1"
                               <?= !empty($settings['enable_bill_modify']) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="bill_mod"></label>
                    </div>
                </div>
                <hr class="my-2">

                <!-- PLU Billing Mode -->
                <div class="form-group d-flex justify-content-between align-items-center">
                    <div>
                        <b>PLU Billing — Show Out-of-Stock PLUs</b>
                        <div class="text-muted small">
                            <span class="badge badge-success mr-1">ON</span> Show all PLUs (including zero-stock) — popup always appears when multiple MRPs exist<br>
                            <span class="badge badge-secondary mr-1">OFF</span> Show only PLUs with stock &gt; 0 — single-stock PLU auto-adds without popup
                        </div>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="plu_show_zero_stock"
                               name="plu_show_zero_stock" value="1"
                               <?= !empty($settings['plu_show_zero_stock']) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="plu_show_zero_stock"></label>
                    </div>
                </div>
                <hr class="my-2">

                <!-- Excel Import -->
                <div class="form-group d-flex justify-content-between align-items-center">
                    <div>
                        <b>Excel Import</b>
                        <div class="text-muted small">Allow bulk product import via Excel</div>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="excel_imp"
                               name="enable_excel_import" value="1"
                               <?= !empty($settings['enable_excel_import']) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="excel_imp"></label>
                    </div>
                </div>
                <hr class="my-2">

                <!-- GST Enable -->
                <div class="form-group d-flex justify-content-between align-items-center">
                    <div>
                        <b>Enable GST</b>
                        <div class="text-muted small">Show tax fields in estimates & invoices</div>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="enable_gst"
                               name="enable_gst" value="1"
                               <?= !empty($settings['enable_gst']) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="enable_gst"></label>
                    </div>
                </div>
                <hr class="my-2">

                <!-- Default Cash Sale Mode -->
                <div class="form-group d-flex justify-content-between align-items-center">
                    <div>
                        <b>Default: Cash Sale Mode</b>
                        <div class="text-muted small">New estimates default to cash (no bill) mode</div>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="cash_mode"
                               name="default_cash_sale_mode" value="1"
                               <?= !empty($settings['default_cash_sale_mode']) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="cash_mode"></label>
                    </div>
                </div>
                <hr class="my-2">

                <!-- Billing Screen UI Version -->
                <div class="form-group border-top pt-2">
                    <label style="font-size:.83rem;font-weight:600;">Billing Screen UI</label>
                    <select name="billing_ui_version" class="form-control form-control-sm">
                        <option value="v1" <?= ($settings['billing_ui_version'] ?? 'v1') === 'v1' ? 'selected' : '' ?>>Classic (current)</option>
                        <option value="v2" <?= ($settings['billing_ui_version'] ?? 'v1') === 'v2' ? 'selected' : '' ?>>New POS-style (beta)</option>
                    </select>
                    <small class="text-muted d-block mt-1">The new screen has the same billing features, redesigned with a dark header, an always-visible payment panel, and a function-key bar. Price Check / Hold Bill / Recall Bill are shown but not active yet.</small>
                </div>

                <!-- Default Payment Mode -->
                <div class="form-group border-top pt-2">
                    <label style="font-size:.83rem;font-weight:600;">Default Payment Mode (New Invoice)</label>
                    <select name="default_payment_mode" class="form-control form-control-sm">
                        <?php if (!empty($payment_modes)): foreach ($payment_modes as $pm): ?>
                        <option value="<?= $pm->code ?>" <?= ($settings['default_payment_mode'] ?? 'cash') === $pm->code ? 'selected' : '' ?>><?= htmlspecialchars($pm->name) ?></option>
                        <?php endforeach; else: ?>
                        <option value="cash" <?= ($settings['default_payment_mode'] ?? 'cash') === 'cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="upi" <?= ($settings['default_payment_mode'] ?? 'cash') === 'upi' ? 'selected' : '' ?>>UPI</option>
                        <option value="card" <?= ($settings['default_payment_mode'] ?? 'cash') === 'card' ? 'selected' : '' ?>>Card</option>
                        <option value="credit" <?= ($settings['default_payment_mode'] ?? 'cash') === 'credit' ? 'selected' : '' ?>>Credit / Due</option>
                        <?php endif; ?>
                    </select>
                    <small class="text-muted d-block mt-1">Every new billing screen (and every invoice after Save &amp; Print) starts with this payment mode pre-selected.</small>
                </div>

                <!-- Default Walk-in Customer -->
                <div class="form-group border-top pt-2">
                    <label style="font-size:.83rem;font-weight:600;">Default Walk-in Customer</label>
                    <select name="walkin_customer_id" class="form-control form-control-sm">
                        <option value="">-- None (use first customer) --</option>
                        <?php foreach ($customers_list as $c): ?>
                            <option value="<?= $c->id ?>" <?= ($settings['walkin_customer_id'] ?? '') == $c->id ? 'selected' : '' ?>><?= htmlspecialchars($c->name) ?> (<?= $c->phone ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted d-block mt-1">When no customer is selected during billing, this customer is auto-assigned to the invoice.</small>
                </div>

            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-warning text-dark py-2 font-weight-bold">📊 Stock Settings</div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <b>Allow Negative Stock Billing</b>
                        <div class="text-muted small">Allow billing even when item stock is 0 (stock goes negative). Turn OFF to block billing when stock is insufficient.</div>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="hidden" name="allow_negative_stock" value="0">
                        <input type="checkbox" class="custom-control-input" id="allow_neg_stock"
                               name="allow_negative_stock" value="1"
                               <?= ($settings['allow_negative_stock'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="allow_neg_stock"></label>
                    </div>
                </div>
                <hr class="my-2">
                <div class="form-group">
                    <label><b>Low Stock Threshold (Global)</b></label>
                    <input type="number" name="low_stock_threshold" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['low_stock_threshold'] ?? 5) ?>" min="0">
                    <small class="text-muted">Items at or below this qty show as low stock on dashboard</small>
                </div>
            </div>
        </div>

        <!-- Print Settings -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-info text-white py-2 font-weight-bold">🖨️ Bill Print Settings</div>
            <div class="card-body">
                <!-- Printer Type Dropdown -->
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;">Default Printer Paper Size</label>
                    <select name="printer_type" class="form-control form-control-sm">
                        <option value="A4" <?= ($settings['printer_type'] ?? 'A4') === 'A4' ? 'selected' : '' ?>>A4 Size Paper (Standard)</option>
                        <option value="A5" <?= ($settings['printer_type'] ?? 'A4') === 'A5' ? 'selected' : '' ?>>A5 Size Paper (148 x 210 mm, garment bill)</option>
                        <option value="3in" <?= ($settings['printer_type'] ?? 'A4') === '3in' ? 'selected' : '' ?>>3-inch Thermal Printer (80mm)</option>
                        <option value="2in" <?= ($settings['printer_type'] ?? 'A4') === '2in' ? 'selected' : '' ?>>2-inch Thermal Printer (58mm)</option>
                    </select>
                    <small class="text-muted">Determines the page layout when printing invoices.</small>
                </div>
                
                <div class="form-group border-top pt-2">
                    <label style="font-size:.83rem;font-weight:600;">Show Customer Portal QR on Bill</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="show_portal_qr_on_bill" name="show_portal_qr_on_bill" value="1" <?= ($settings['show_portal_qr_on_bill'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="show_portal_qr_on_bill">Print customer portal login QR code on invoices</label>
                    </div>
                    <small class="text-muted">When ON, a QR code linking to the customer portal login will be printed on each bill. Turn OFF to hide it.</small>
                </div>

                <div class="form-group border-top pt-2">
                    <label style="font-size:.83rem;font-weight:600;">Require PIN to Delete Item in Billing</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="require_pin_to_delete_item" name="require_pin_to_delete_item" value="1" <?= ($settings['require_pin_to_delete_item'] ?? '1') == '1' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="require_pin_to_delete_item">Ask manager PIN before removing an item from the bill</label>
                    </div>
                    <small class="text-muted">When ON, cashiers must enter a manager PIN to delete any item from a billing form. Turn OFF to allow direct removal without authorization.</small>
                </div>

                <div class="form-group border-top pt-2">
                    <label style="font-size:.83rem;font-weight:600;">Allow All Users to Edit Payment Mode</label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="allow_all_payment_mode_edit" name="allow_all_payment_mode_edit" value="1" <?= ($settings['allow_all_payment_mode_edit'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="allow_all_payment_mode_edit">Let every user change the payment mode on saved invoices</label>
                    </div>
                    <small class="text-muted">When ON, all users see the "Pay Mode" button on invoices. When OFF, only admins and users with the per-user permission can change it.</small>
                </div>

                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="print_hold_slip" name="print_hold_slip" value="1" <?= ($settings['print_hold_slip'] ?? '0') == '1' ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="print_hold_slip">Print Hold Slip when bill is put on hold</label>
                    </div>
                    <small class="text-muted">When ON, a thermal-style hold slip prints automatically after a bill is held (Hold #, date, user, items, customer, tax). No confirmation dialog.</small>
                </div>

                <div class="form-group border-top pt-2">
                    <label style="font-size:.83rem;font-weight:600;">Print Mode</label>
                    <select name="print_mode" class="form-control form-control-sm">
                        <option value="confirm" <?= ($settings['print_mode'] ?? 'preview') === 'confirm' ? 'selected' : '' ?>>Ask Before Printing (Yes / No Confirmation)</option>
                        <option value="direct" <?= ($settings['print_mode'] ?? '') === 'direct' ? 'selected' : '' ?>>Direct Print (Auto-print, No Confirmation)</option>
                        <option value="preview" <?= ($settings['print_mode'] ?? 'preview') === 'preview' ? 'selected' : '' ?>>Print Preview (Opens Browser Print Dialog)</option>
                    </select>
                    <small class="text-muted d-block mt-1">
                        <b>Ask Before Printing:</b> After saving a bill, a dialog will ask "Do you want to print?" with Yes/No buttons.<br>
                        <b>Direct Print:</b> Bill prints automatically after save using Chrome Kiosk Mode to your <b>Default Printer</b>.<br>
                        <b>Print Preview:</b> Opens browser's print dialog after save.
                    </small>
                </div>
                
                <!-- TSC Barcode Printer Connection -->
                <div class="form-group border-top pt-2">
                    <label style="font-size:.83rem;font-weight:600;">TSC Barcode Printer Share Name / Port</label>
                    <input type="text" name="tsc_printer_name" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['tsc_printer_name'] ?? 'TSC') ?>" placeholder="e.g. \\\\localhost\\TSC or TSC or LPT1">
                    <small class="text-muted">Enter the network shared path of your TSC Printer (e.g., <code>\\\\127.0.0.1\\TSC</code>) or local port (e.g. <code>LPT1</code>) for direct TSPL command printing. (Legacy — the server-side print path below is unreliable on shared hosting; prefer the Barcode Printing API for a local printing app.)</small>
                </div>

                <!-- Barcode Printing API (for local .NET printing app) -->
                <div class="form-group border-top pt-2" id="barcode-api">
                    <label style="font-size:.83rem;font-weight:600;">Barcode Printing API Key</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="barcode_api_key_field" class="form-control form-control-sm" readonly
                               value="<?= htmlspecialchars($settings['barcode_api_key'] ?? '') ?>">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="var f=document.getElementById('barcode_api_key_field'); f.select(); document.execCommand('copy');">Copy</button>
                            <a href="<?= base_url('settings/regenerate_barcode_api_key') ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Regenerate the key? Any local printing app using the old key will stop working until updated.');">Regenerate</a>
                        </div>
                    </div>
                    <small class="text-muted">
                        Used by an external local app (e.g. a .NET barcode-label printer) to pull product and template data over
                        <code><?= base_url('api/') ?></code>. Send this key as header <code>X-API-KEY</code> (or <code>?api_key=</code>).
                        Endpoints: <code>barcode_search</code>, <code>barcode_product/{id}</code>, <code>barcode_templates</code>,
                        <code>barcode_template/{name}</code>, <code>barcode_settings</code>, <code>barcode_log</code> (POST).
                        The app is responsible for the actual printing — this API only supplies data.
                    </small>
                </div>

                <!-- FMCG Reporting Suite Server URL -->
                <div class="form-group border-top pt-2">
                    <label style="font-size:.83rem;font-weight:600;">Reporting Server URL</label>
                    <input type="url" name="fmcg_server_url" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['fmcg_server_url'] ?? 'http://localhost:5000') ?>" placeholder="http://localhost:5000">
                    <small class="text-muted">URL where the Python/Flask Reporting app is running (e.g. <code>http://localhost:5000</code>).</small>
                </div>
                
                <!-- Tax Display Toggle -->
                <div class="form-group d-flex justify-content-between align-items-center">
                    <div>
                        <b style="font-size:.83rem;">Display Tax details in Bill Print</b>
                        <div class="text-muted small">Show CGST/SGST, HSN code, and tax columns on printout</div>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="print_show_tax"
                               name="print_show_tax" value="1"
                               <?= !isset($settings['print_show_tax']) || !empty($settings['print_show_tax']) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="print_show_tax"></label>
                    </div>
                </div>
                <hr class="my-2">

                <!-- Pending Payment Display Toggle -->
                <div class="form-group d-flex justify-content-between align-items-center">
                    <div>
                        <b style="font-size:.83rem;">Display Paid &amp; Balance Due in Bill Print</b>
                        <div class="text-muted small">Show Paid Amount and Balance Due/pending payment details on printout</div>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="print_show_pending"
                               name="print_show_pending" value="1"
                               <?= !isset($settings['print_show_pending']) || !empty($settings['print_show_pending']) ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="print_show_pending"></label>
                    </div>
                </div>
                <hr class="my-2">

                <!-- Company Logo Upload -->
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;">Company Logo</label>
                    <?php if (!empty($settings['company_logo']) && file_exists('./' . $settings['company_logo'])): ?>
                        <div class="mb-2">
                            <img src="<?= base_url($settings['company_logo']) ?>" alt="Logo Preview" style="max-height: 60px; border: 1px solid #ddd; padding: 3px; border-radius: 4px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="company_logo" class="form-control-file form-control-file-sm">
                    <small class="text-muted">Upload PNG or JPG image (max 2MB). Replaces existing logo.</small>
                    <div class="custom-control custom-checkbox mt-1"><input type="checkbox" class="custom-control-input" id="remove_company_logo" name="remove_company_logo" value="1"><label class="custom-control-label small" for="remove_company_logo">Remove logo</label></div>
                </div>
                <hr class="my-2">

                <!-- A5 Invoice Print Settings (used when Paper Size = A5) -->
                <div class="form-group">
                    <label style="font-size:.83rem;font-weight:600;">A5 Invoice Layout</label>
                    <div class="row">
                        <div class="col-md-4 mb-2"><small>Layout</small>
                            <select name="a5_layout" class="form-control form-control-sm">
                                <option value="compact" <?= ($settings['a5_layout'] ?? 'compact') === 'compact' ? 'selected' : '' ?>>Compact</option>
                                <option value="standard" <?= ($settings['a5_layout'] ?? '') === 'standard' ? 'selected' : '' ?>>Standard</option>
                            </select></div>
                        <div class="col-md-4 mb-2"><small>Orientation</small>
                            <select name="a5_orientation" class="form-control form-control-sm">
                                <option value="portrait" <?= ($settings['a5_orientation'] ?? 'portrait') === 'portrait' ? 'selected' : '' ?>>Portrait</option>
                                <option value="landscape" <?= ($settings['a5_orientation'] ?? '') === 'landscape' ? 'selected' : '' ?>>Landscape</option>
                            </select></div>
                        <div class="col-md-4 mb-2"><small>Invoice heading (blank = auto)</small>
                            <input type="text" name="a5_invoice_heading" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['a5_invoice_heading'] ?? '') ?>" placeholder="TAX INVOICE / CASH MEMO"></div>
                        <div class="col-md-4 mb-2"><small>Branch / store name</small>
                            <input type="text" name="a5_branch_name" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['a5_branch_name'] ?? '') ?>"></div>
                        <div class="col-md-4 mb-2"><small>Signatory name</small>
                            <input type="text" name="a5_signatory_name" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['a5_signatory_name'] ?? '') ?>"></div>
                        <div class="col-md-4 mb-2"><small>Signatory designation</small>
                            <input type="text" name="a5_signatory_title" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['a5_signatory_title'] ?? '') ?>"></div>
                        <div class="col-md-6 mb-2"><small>Footer message</small>
                            <input type="text" name="a5_footer_msg" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['a5_footer_msg'] ?? 'Thank you! Visit again.') ?>"></div>
                        <div class="col-md-6 mb-2"><small>Return / exchange policy</small>
                            <input type="text" name="a5_return_policy" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['a5_return_policy'] ?? '') ?>"></div>
                        <div class="col-md-6 mb-2"><small>Website</small>
                            <input type="text" name="company_website" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['company_website'] ?? '') ?>"></div>
                    </div>
                    <div class="row">
                    <?php foreach ([
                        'a5_show_logo' => ['Logo', 1], 'a5_show_contact' => ['Address, phone, GSTIN', 1], 'a5_show_customer' => ['Customer details', 1],
                        'a5_show_code' => ['Item code', 0], 'a5_show_size_colour' => ['Size & colour columns', 1], 'a5_show_tax' => ['HSN / GST columns & breakup', 1],
                        'a5_show_signature' => ['Authorised signature', 1], 'a5_show_stamp' => ['Company stamp', 1], 'a5_show_terms' => ['Terms & conditions', 1], 'a5_show_footer_msg' => ['Footer message & policy', 1],
                    ] as $k => [$lbl, $def]): ?>
                        <div class="col-md-4"><div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="<?= $k ?>" name="<?= $k ?>" value="1" <?= ($settings[$k] ?? $def) == '1' ? 'checked' : '' ?>>
                            <label class="custom-control-label small" for="<?= $k ?>"><?= $lbl ?></label></div></div>
                    <?php endforeach; ?>
                    </div>
                    <?php foreach (['company_stamp' => 'Company Stamp (transparent PNG ok)', 'company_signature' => 'Signature Image'] as $k => $lbl): ?>
                    <div class="mt-2"><small><b><?= $lbl ?></b></small>
                        <?php if (!empty($settings[$k]) && file_exists('./' . $settings[$k])): ?><img src="<?= base_url($settings[$k]) ?>" style="max-height:50px;border:1px solid #ddd;padding:2px;margin-left:6px;"><?php endif; ?>
                        <input type="file" name="<?= $k ?>" accept=".png,.jpg,.jpeg" class="form-control-file form-control-file-sm">
                        <div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="remove_<?= $k ?>" name="remove_<?= $k ?>" value="1"><label class="custom-control-label small" for="remove_<?= $k ?>">Remove</label></div>
                    </div>
                    <?php endforeach; ?>
                    <small class="text-muted d-block mt-1">PNG/JPG, max 1MB. Nothing is drawn if no image is uploaded. When printing, pick paper size A5, scale 100%.</small>
                </div>
                <hr class="my-2">

                <!-- Login Page — background image + license/support footer, shown on the
                     staff login screen (application/views/auth/login.php). -->
                <div class="form-group mb-2">
                    <label style="font-size:.83rem;font-weight:600;">Login Page Background Image</label>
                    <?php if (!empty($settings['login_bg_image']) && file_exists('./' . $settings['login_bg_image'])): ?>
                        <div class="mb-2">
                            <img src="<?= base_url($settings['login_bg_image']) ?>" alt="Login Background Preview" style="max-height: 90px; border: 1px solid #ddd; padding: 3px; border-radius: 4px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="login_bg_image" class="form-control-file form-control-file-sm">
                    <small class="text-muted">Shown on the left side of the staff login page (max 4MB). Falls back to a plain gradient if not set.</small>
                </div>
                <div class="form-row">
                    <div class="col-md-4 form-group mb-2">
                        <label style="font-size:.83rem;font-weight:600;">License Number</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="license_number" id="licenseNumberInput" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['license_number'] ?? '') ?>" placeholder="e.g. 0920260001000001">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-dark btn-sm" onclick="generateLicenseNumber()">Generate</button>
                            </div>
                        </div>
                        <small class="text-muted">Store # only needs to be set once (e.g. 0001 for your first customer, 0002 for the next). <input type="text" id="storeNumberInput" value="<?= htmlspecialchars($settings['license_store_number'] ?? '0001') ?>" maxlength="4" style="width:50px;display:inline-block;padding:0 4px;font-size:.75rem;" class="form-control form-control-sm"> then click Generate.</small>
                        <input type="hidden" name="license_store_number" id="licenseStoreNumberField" value="<?= htmlspecialchars($settings['license_store_number'] ?? '0001') ?>">
                    </div>
                    <div class="col-md-4 form-group mb-2">
                        <label style="font-size:.83rem;font-weight:600;">Free Support Valid Until</label>
                        <input type="date" name="support_valid_until" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['support_valid_until'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 form-group mb-2">
                        <label style="font-size:.83rem;font-weight:600;">Support Helpline Number</label>
                        <input type="text" name="support_helpline" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['support_helpline'] ?? '+91 98711 18688') ?>" placeholder="+91 98711 18688">
                    </div>
                </div>
                <small class="text-muted">Shown as a footer strip on the login page — visible to anyone who opens it, before signing in.</small>
                <script>
                function generateLicenseNumber() {
                    var productCode = '09';
                    var year = new Date().getFullYear().toString();
                    var storeNum = document.getElementById('storeNumberInput').value.padStart(4, '0').slice(0, 4);
                    document.getElementById('licenseStoreNumberField').value = storeNum;
                    var tail = Math.floor(Math.random() * 999999).toString().padStart(6, '0');
                    document.getElementById('licenseNumberInput').value = productCode + year + storeNum + tail;
                }
                </script>
            </div>
        </div>

        <!-- User Permissions Panel (Admin only) -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-danger text-white py-2 font-weight-bold">🔐 User Permissions</div>
            <div class="card-body p-0">
            <?php
            $has_po_col = $this->db->field_exists('allow_price_override', 'user_permissions');
            $po_select = $has_po_col ? ', COALESCE(up.allow_price_override,1) as allow_price_override' : '';
            $users = $this->db->select('u.id, u.name, u.role, COALESCE(up.can_edit_bill,0) as can_edit_bill, COALESCE(up.can_delete_estimate,0) as can_delete_estimate, COALESCE(up.can_view_reports,1) as can_view_reports, COALESCE(up.can_import_excel,0) as can_import_excel, COALESCE(up.allow_payment_mode_change,0) as allow_payment_mode_change, COALESCE(up.allow_due_bills,0) as allow_due_bills' . $po_select)
                ->from('users u')
                ->join('user_permissions up','up.user_id=u.id','left')
                ->where('u.status',1)->get()->result();
            ?>
            <table class="table table-sm mb-0" style="font-size:0.8rem;">
            <thead class="thead-dark"><tr><th>User</th><th>Edit Bill</th><th>Del Est</th><th>Reports</th><th>Excel</th><th>Pay Mode</th><th>Due Bill</th><th>Price Override</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u->name) ?> <span class="badge badge-<?= $u->role==='admin'?'danger':'secondary' ?>"><?= $u->role ?></span></td>
                <?php foreach (['can_edit_bill','can_delete_estimate','can_view_reports','can_import_excel', 'allow_payment_mode_change', 'allow_due_bills', 'allow_price_override'] as $perm): ?>
                <td>
                    <input type="hidden" name="perm[<?= $u->id ?>][<?= $perm ?>]" value="0">
                    <input type="checkbox" name="perm[<?= $u->id ?>][<?= $perm ?>]" value="1"
                           <?= (isset($u->$perm) ? $u->$perm : ($perm === 'allow_price_override' ? 1 : 0)) ? 'checked' : '' ?>
                           <?= $u->role==='admin' ? 'checked disabled' : '' ?>>
                </td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- Payment Modes Master -->
    <div class="col-12 mb-4" id="payment-modes">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white py-2 font-weight-bold d-flex align-items-center justify-content-between" style="font-size:.85rem;">
                <span>💳 Payment Modes Master</span>
                <button type="button" class="btn btn-sm btn-light py-0 px-2" style="font-size:.75rem;" data-toggle="modal" data-target="#pmMasterModal" onclick="resetPmForm()"><i class="fas fa-plus mr-1"></i>Add Mode</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:.82rem;">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th class="text-center">Icon</th>
                            <th class="text-center">Quick Btn</th>
                            <th class="text-center">Other Menu</th>
                            <th class="text-center">Split</th>
                            <th class="text-center">Cash Type</th>
                            <th class="text-center">Needs Ref</th>
                            <th class="text-center">Credit</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">System</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($payment_modes)): foreach ($payment_modes as $pm): ?>
                        <tr>
                            <td><?= $pm->sort_order ?></td>
                            <td><code><?= htmlspecialchars($pm->code) ?></code></td>
                            <td class="font-weight-bold"><?= htmlspecialchars($pm->name) ?></td>
                            <td class="text-center"><i class="<?= htmlspecialchars($pm->icon) ?>"></i></td>
                            <td class="text-center"><?= $pm->show_in_quick ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>' ?></td>
                            <td class="text-center"><?= $pm->show_in_other ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>' ?></td>
                            <td class="text-center"><?= $pm->show_in_split ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>' ?></td>
                            <td class="text-center"><?= $pm->is_cash_type ? '<span class="badge badge-warning">Cash</span>' : '' ?></td>
                            <td class="text-center"><?= $pm->needs_ref ? '<span class="badge badge-info">Yes</span>' : '' ?></td>
                            <td class="text-center"><?= $pm->is_credit ? '<span class="badge badge-danger">Yes</span>' : '' ?></td>
                            <td class="text-center"><span class="badge badge-<?= $pm->status ? 'success' : 'secondary' ?>"><?= $pm->status ? 'Active' : 'Off' ?></span></td>
                            <td class="text-center"><?= $pm->is_system ? '<i class="fas fa-lock text-muted" title="System mode"></i>' : '' ?></td>
                            <td class="text-center text-nowrap">
                                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1" style="font-size:.72rem;"
                                    onclick="editPm(<?= $pm->id ?>, '<?= addslashes($pm->code) ?>', '<?= addslashes($pm->name) ?>', '<?= addslashes($pm->icon) ?>', <?= $pm->is_cash_type ?>, <?= $pm->show_in_quick ?>, <?= $pm->show_in_other ?>, <?= $pm->show_in_split ?>, <?= $pm->needs_ref ?>, <?= $pm->is_credit ?>, <?= $pm->sort_order ?>, <?= $pm->status ?>, <?= $pm->is_system ?>)">Edit</button>
                                <?php if (!$pm->is_system): ?>
                                <a href="<?= base_url('settings/delete_payment_mode/' . $pm->id) ?>" class="btn btn-xs btn-outline-danger py-0 px-1" style="font-size:.72rem;" onclick="return confirm('Delete this payment mode?')">Del</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="13" class="text-center text-muted py-3">No payment modes found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
                <div class="px-3 py-2 bg-light" style="font-size:.75rem;">
                    <strong>Quick Btn</strong> = shown as button in billing (Cash/Card/UPI) &nbsp;|&nbsp;
                    <strong>Other Menu</strong> = shown in "Other mode" dropdown &nbsp;|&nbsp;
                    <strong>Split</strong> = available in split payment rows &nbsp;|&nbsp;
                    <strong>Cash Type</strong> = counts as physical cash in Cash Management &nbsp;|&nbsp;
                    <strong>Needs Ref</strong> = asks for reference/card number &nbsp;|&nbsp;
                    <strong>Credit</strong> = permission-gated (Due Bills)
                </div>
            </div>
        </div>
    </div>

    <!-- Delivery Charges Config -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold text-dark" style="font-size:.85rem;">
                🚚 Delivery Charges Configuration
            </div>
            <div class="card-body">
                <div class="form-group mb-2">
                    <label style="font-size:.83rem;font-weight:600;">Free Delivery Order Threshold ₹</label>
                    <input type="number" name="delivery_free_threshold" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['delivery_free_threshold'] ?? '500') ?>" min="0">
                    <small class="text-muted">Orders at or above this amount get free delivery.</small>
                </div>
                <div class="form-group mb-2">
                    <label style="font-size:.83rem;font-weight:600;">Base Delivery Charge ₹</label>
                    <input type="number" name="delivery_base_charge" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['delivery_base_charge'] ?? '30') ?>" min="0">
                    <small class="text-muted">Flat fee applied for orders below the free threshold.</small>
                </div>
                <div class="form-group mb-2">
                    <label style="font-size:.83rem;font-weight:600;">Delivery Charge per Kilometer ₹</label>
                    <input type="number" name="delivery_charge_per_km" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($settings['delivery_charge_per_km'] ?? '10') ?>" min="0">
                    <small class="text-muted">Extra fee calculated per distance kilometer.</small>
                </div>
                <div class="form-group mb-0">
                    <label style="font-size:.83rem;font-weight:600;">Delivery Time Slots & Surcharges</label>
                    <textarea name="delivery_slots" class="form-control form-control-sm" rows="3" placeholder="e.g. Standard Delivery:0&#10;Urgent Delivery:50&#10;Evening (7-10 PM):20"><?= htmlspecialchars($settings['delivery_slots'] ?? "Standard Delivery (Anytime):0\nUrgent Delivery (Within 90 mins):50\nEvening Delivery (7-10 PM):20") ?></textarea>
                    <small class="text-muted">Format: <code>Slot Name:Surcharge</code> (One per line).</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Store Registration QR Code -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold text-dark" style="font-size:.85rem;">
                🏪 Customer Portal Registration QR
            </div>
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-3">
                <div id="store-reg-qr" class="mb-3" style="background:#fff; padding: 12px; border: 1px solid #ddd; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: inline-block;"></div>
                <p class="font-weight-bold mb-1 text-dark" style="font-size: 0.9rem;">Store Registration QR Code</p>
                <p class="text-muted mb-3" style="font-size: 0.8rem; max-width: 280px; line-height: 1.4;">Print and place this QR code in your store. Scanning this allows customers to register their name, mobile, email, and address, verify via Email OTP, and log in to place orders online instantly.</p>
                <button type="button" class="btn btn-outline-dark btn-sm font-weight-bold px-3" onclick="printQr('store-reg-qr', <?= json_encode($company['company_name'] ?? 'Our Store') ?>, 'Scan QR code below to register & place orders online!')" style="border-radius: 20px;">
                    <i class="fas fa-print mr-1"></i> Print QR Code
                </button>
                <a href="<?= base_url('portal') ?>" target="_blank" class="d-block mt-2 text-muted" style="font-size:.78rem;"><?= base_url('portal') ?></a>
            </div>
        </div>
    </div>

    <!-- Vendor Portal QR Code -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold text-dark" style="font-size:.85rem;">
                🚚 Vendor Portal QR
            </div>
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-3">
                <div id="vendor-portal-qr" class="mb-3" style="background:#fff; padding: 12px; border: 1px solid #ddd; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: inline-block;"></div>
                <p class="font-weight-bold mb-1 text-dark" style="font-size: 0.9rem;">Vendor / Supplier Portal</p>
                <p class="text-muted mb-3" style="font-size: 0.8rem; max-width: 280px; line-height: 1.4;">Share this with suppliers so they can log in, view Purchase Orders, submit RFQ quotations, and track their performance.</p>
                <button type="button" class="btn btn-outline-dark btn-sm font-weight-bold px-3" onclick="printQr('vendor-portal-qr', 'Vendor Portal', 'Scan to log in and manage your Purchase Orders')" style="border-radius: 20px;">
                    <i class="fas fa-print mr-1"></i> Print QR Code
                </button>
                <a href="<?= base_url('vendor-portal') ?>" target="_blank" class="d-block mt-2 text-muted" style="font-size:.78rem;"><?= base_url('vendor-portal') ?></a>
            </div>
        </div>
    </div>

    <!-- Owner App QR Code -->
    <div class="col-md-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2 font-weight-bold text-dark" style="font-size:.85rem;">
                📱 Owner App QR
            </div>
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-3">
                <div id="owner-app-qr" class="mb-3" style="background:#fff; padding: 12px; border: 1px solid #ddd; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: inline-block;"></div>
                <p class="font-weight-bold mb-1 text-dark" style="font-size: 0.9rem;">Owner Mobile App</p>
                <p class="text-muted mb-3" style="font-size: 0.8rem; max-width: 280px; line-height: 1.4;">Scan to open the Owner App on your phone — sales, stock, and business snapshot on the go.</p>
                <button type="button" class="btn btn-outline-dark btn-sm font-weight-bold px-3" onclick="printQr('owner-app-qr', 'Owner App', 'Scan to open the Owner App on your phone')" style="border-radius: 20px;">
                    <i class="fas fa-print mr-1"></i> Print QR Code
                </button>
                <a href="<?= base_url('owner') ?>" target="_blank" class="d-block mt-2 text-muted" style="font-size:.78rem;"><?= base_url('owner') ?></a>
            </div>
        </div>
    </div>

</div>

<div class="pb-4">
    <button type="submit" name="submit" class="btn btn-dark px-4"><i class="fas fa-save"></i> Save All Settings</button>
</div>

<?= form_close() ?>

<script src="<?= base_url('assets/js/qrcode.min.js') ?>"></script>
<script>
window.addEventListener('DOMContentLoaded', function() {
    var portalUrl = <?= json_encode($settings['portal_mobile_url'] ?? '') ?> || "<?= base_url('portal') ?>";
    new QRCode(document.getElementById("store-reg-qr"), {
        text: portalUrl,
        width: 140,
        height: 140,
        colorDark : "#000000",
        colorLight : "#ffffff"
    });
    new QRCode(document.getElementById("vendor-portal-qr"), {
        text: "<?= base_url('vendor-portal') ?>",
        width: 140,
        height: 140,
        colorDark : "#000000",
        colorLight : "#ffffff"
    });
    new QRCode(document.getElementById("owner-app-qr"), {
        text: "<?= base_url('owner') ?>",
        width: 140,
        height: 140,
        colorDark : "#000000",
        colorLight : "#ffffff"
    });
});

function printQr(qrElId, heading, subtext) {
    var qrHtml = document.getElementById(qrElId).innerHTML;
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Print QR Code</title>');
    printWindow.document.write('<style>body{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;margin:0;} .container{text-align:center;border:2px solid #000;padding:40px;border-radius:20px;max-width:350px;} h2{margin-top:0;font-size:1.8rem;} p{color:#555;font-size:1rem;line-height:1.4;} .qr-img img{width:220px;height:220px;margin:20px 0; display:inline-block;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<div class="container">');
    printWindow.document.write('<h2>' + heading + '</h2>');
    printWindow.document.write('<p>' + subtext + '</p>');
    printWindow.document.write('<div class="qr-img">' + qrHtml + '</div>');
    printWindow.document.write('</div>');
    printWindow.document.write('<script>window.onload = function() { window.print(); window.close(); };<\/script>');
    printWindow.document.write('</body></html>');
    printWindow.document.close();
}
</script>

<!-- Payment Mode Modal -->
<div class="modal fade" id="pmMasterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title font-weight-bold mb-0" id="pmMasterTitle">Add Payment Mode</h6>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="post" action="<?= base_url('settings/save_payment_mode') ?>">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <input type="hidden" name="id" id="pm_id" value="0">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label style="font-size:.82rem;">Code (slug) <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="pm_code" class="form-control form-control-sm" required pattern="[a-z0-9_]+" title="lowercase letters, digits, underscore">
                        <small class="text-muted">e.g. paytm, phonepe, bnpl</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label style="font-size:.82rem;">Display Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="pm_name" class="form-control form-control-sm" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label style="font-size:.82rem;">Icon (FontAwesome class)</label>
                        <input type="text" name="icon" id="pm_icon" class="form-control form-control-sm" value="fas fa-money-bill-wave">
                        <small class="text-muted">e.g. fas fa-mobile-alt</small>
                    </div>
                    <div class="form-group col-md-3">
                        <label style="font-size:.82rem;">Sort Order</label>
                        <input type="number" name="sort_order" id="pm_sort" class="form-control form-control-sm" value="10" min="0">
                    </div>
                    <div class="form-group col-md-3">
                        <label style="font-size:.82rem;">Status</label>
                        <select name="status" id="pm_status" class="form-control form-control-sm">
                            <option value="1">Active</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                </div>
                <hr class="my-2">
                <label class="font-weight-bold" style="font-size:.82rem;">Where to Show</label>
                <div class="form-row">
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="hidden" name="show_in_quick" value="0">
                            <input type="checkbox" class="custom-control-input" id="pm_quick" name="show_in_quick" value="1">
                            <label class="custom-control-label" for="pm_quick" style="font-size:.82rem;">Quick Button</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="hidden" name="show_in_other" value="0">
                            <input type="checkbox" class="custom-control-input" id="pm_other" name="show_in_other" value="1">
                            <label class="custom-control-label" for="pm_other" style="font-size:.82rem;">Other Dropdown</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="hidden" name="show_in_split" value="0">
                            <input type="checkbox" class="custom-control-input" id="pm_split" name="show_in_split" value="1" checked>
                            <label class="custom-control-label" for="pm_split" style="font-size:.82rem;">Split Payment</label>
                        </div>
                    </div>
                </div>
                <hr class="my-2">
                <label class="font-weight-bold" style="font-size:.82rem;">Behavior</label>
                <div class="form-row">
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="hidden" name="is_cash_type" value="0">
                            <input type="checkbox" class="custom-control-input" id="pm_cash" name="is_cash_type" value="1">
                            <label class="custom-control-label" for="pm_cash" style="font-size:.82rem;">Cash Type</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="hidden" name="needs_ref" value="0">
                            <input type="checkbox" class="custom-control-input" id="pm_ref" name="needs_ref" value="1">
                            <label class="custom-control-label" for="pm_ref" style="font-size:.82rem;">Needs Ref#</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="hidden" name="is_credit" value="0">
                            <input type="checkbox" class="custom-control-input" id="pm_credit" name="is_credit" value="1">
                            <label class="custom-control-label" for="pm_credit" style="font-size:.82rem;">Credit (Due)</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="submit" class="btn btn-sm btn-dark">Save Payment Mode</button>
            </div>
            </form>
        </div>
    </div>
</div>
<script>
function resetPmForm() {
    document.getElementById('pmMasterTitle').textContent = 'Add Payment Mode';
    document.getElementById('pm_id').value = 0;
    document.getElementById('pm_code').value = '';
    document.getElementById('pm_code').readOnly = false;
    document.getElementById('pm_name').value = '';
    document.getElementById('pm_icon').value = 'fas fa-money-bill-wave';
    document.getElementById('pm_sort').value = 10;
    document.getElementById('pm_status').value = '1';
    document.getElementById('pm_quick').checked = false;
    document.getElementById('pm_other').checked = false;
    document.getElementById('pm_split').checked = true;
    document.getElementById('pm_cash').checked = false;
    document.getElementById('pm_ref').checked = false;
    document.getElementById('pm_credit').checked = false;
}
function editPm(id, code, name, icon, isCash, quick, other, split, ref, credit, sort, status, isSystem) {
    document.getElementById('pmMasterTitle').textContent = 'Edit Payment Mode';
    document.getElementById('pm_id').value = id;
    document.getElementById('pm_code').value = code;
    document.getElementById('pm_code').readOnly = !!isSystem;
    document.getElementById('pm_name').value = name;
    document.getElementById('pm_icon').value = icon;
    document.getElementById('pm_sort').value = sort;
    document.getElementById('pm_status').value = status;
    document.getElementById('pm_quick').checked = !!quick;
    document.getElementById('pm_other').checked = !!other;
    document.getElementById('pm_split').checked = !!split;
    document.getElementById('pm_cash').checked = !!isCash;
    document.getElementById('pm_ref').checked = !!ref;
    document.getElementById('pm_credit').checked = !!credit;
    $('#pmMasterModal').modal('show');
}
</script>

<script>
function toggleEinvProvider() {
    var v = document.getElementById('einvoiceProvider').value;
    document.getElementById('provAdaequare').style.display = v === 'adaequare' ? '' : 'none';
    document.getElementById('provGstzen').style.display    = v === 'gstzen'    ? '' : 'none';
}
function testGstzen() {
    var out = document.getElementById('gstzenTestResult');
    if (document.getElementById('einvoiceProvider').value !== 'gstzen') { out.innerHTML = '<span class="text-danger">Select GSTZen and save settings first.</span>'; return; }
    out.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    fetch('<?= base_url('wholesale_billing/test_connection_ajax') ?>', {
        method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'api_type=einvoice', credentials: 'same-origin'
    }).then(function (r) { return r.json(); }).then(function (d) {
        var el = document.createElement('span');
        el.className = d.success ? 'text-success' : 'text-danger';
        el.textContent = (d.success ? '\u2714 ' : '\u2716 ') + d.message;
        out.innerHTML = ''; out.appendChild(el);
    }).catch(function () { out.innerHTML = '<span class="text-danger">Connection error</span>'; });
}
</script>
