<?php // FILE: application/views/portal/order.php — Sandal House Premium Cart & Checkout ?>
<?php
$slots_raw = $company['delivery_slots'] ?? "Standard Delivery (Anytime):0\nUrgent Delivery (Within 90 mins):50\nEvening Delivery (7-10 PM):20";
$slots = [];
$lines = explode("\n", str_replace("\r", "", $slots_raw));
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, ':') === false) continue;
    list($name, $charge) = explode(':', $line, 2);
    $slots[] = ['name' => trim($name), 'charge' => (float)trim($charge)];
}
?>
<style>
:root {
<?php include_once APPPATH.'views/portal/_theme.php'; echo portal_theme_vars($company ?? null); ?>
}
.checkout-container { max-width: 900px; margin: 0 auto; padding: 0 12px; }
.checkout-item-card {
  background: #fff; border: 1.5px solid #f0f0f0;
  border-radius: 14px; padding: 14px;
  margin-bottom: 12px; display: flex; gap: 12px; align-items: flex-start;
  box-shadow: 0 2px 8px rgba(59,35,20,0.06);
  transition: all 0.2s;
}
.checkout-item-card:hover { box-shadow: 0 4px 16px rgba(59,35,20,0.1); border-color: var(--gold-light); }
.checkout-item-img {
  width: 68px; height: 68px; object-fit: cover;
  border-radius: 10px; border: 1.5px solid #e8eaf6; flex-shrink: 0;
}
.checkout-qty-control {
  display: flex; align-items: center;
  border: 2px solid var(--primary); border-radius: 8px;
  overflow: hidden; height: 32px; min-width: 90px;
}
.checkout-qty-btn {
  border: none; background: var(--primary-xlight); color: var(--primary);
  padding: 0 10px; font-weight: 700; cursor: pointer;
  font-size: 1rem; height: 100%; font-family: 'Poppins',sans-serif;
  transition: background 0.15s;
}
.checkout-qty-btn:hover { background: var(--gold); }
.checkout-qty-val {
  width: 30px; text-align: center; font-weight: 700;
  border: none; background: transparent; font-size: 0.88rem; color: var(--primary);
}
.checkout-summary-card {
  border-radius: 14px; border: 1.5px solid var(--gold-light);
  background: #fff; box-shadow: 0 2px 10px rgba(59,35,20,0.06);
}
.upi-payment-box {
  background: linear-gradient(135deg, var(--gold-light), #fff);
  border: 1.5px dashed var(--gold); border-radius: 12px;
  padding: 14px; text-align: center;
}
.bb-select {
  width: 100%; border: 1.5px solid #e0e0e0; border-radius: 10px;
  padding: 10px 14px; font-family: 'Poppins',sans-serif;
  font-size: 0.84rem; color: var(--primary); outline: none;
  background: #fafafa; transition: all 0.2s;
  -webkit-appearance: none; appearance: none;
}
.bb-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(59,35,20,0.07); }
.bb-label { display:block; font-size:0.76rem; font-weight:700; color:#37474f; margin-bottom:5px; }
.bb-place-order-btn {
  width: 100%; border: none; border-radius: 12px;
  padding: 14px; font-family: 'Poppins',sans-serif;
  font-size: 0.96rem; font-weight: 700; cursor: pointer;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: #fff; box-shadow: 0 6px 18px rgba(59,35,20,0.25);
  transition: all 0.25s;
}
.bb-place-order-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(59,35,20,0.3); }
</style>

<div class="checkout-container py-2">
    <!-- Back + Title -->
    <div style="margin-bottom:14px;">
      <a href="<?= base_url('portal/products') ?>" style="display:inline-flex;align-items:center;gap:4px;color:var(--primary);font-size:0.78rem;font-weight:600;text-decoration:none;background:var(--gold-light);border-radius:20px;padding:6px 14px;">
        ← Continue Shopping
      </a>
      <h2 style="font-size:1.05rem;font-weight:800;color:var(--primary);margin:12px 0 2px;">🛒 My Cart</h2>
      <p style="font-size:0.78rem;color:var(--text-muted);margin:0;">Review items and place your order</p>
    </div>

    <div id="checkoutEmptyState" class="d-none" style="text-align:center;padding:50px 20px;background:#fff;border-radius:16px;box-shadow:0 2px 10px rgba(59,35,20,0.06);">
        <div style="font-size:3rem;opacity:0.25;margin-bottom:12px;">🛒</div>
        <h5 style="font-size:0.96rem;font-weight:700;color:var(--primary);">Your cart is empty</h5>
        <p style="font-size:0.78rem;color:var(--text-muted);margin-bottom:16px;">Add products to your cart and they'll appear here!</p>
        <a href="<?= base_url('portal/products') ?>" style="display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;border-radius:24px;padding:10px 24px;font-size:0.82rem;font-weight:700;text-decoration:none;">🛍️ Browse Products</a>
    </div>

    <div id="checkoutMainLayout" class="row">
        <!-- Left Main checkout list -->
        <div class="col-lg-7 mb-4">
            <div id="checkoutItemsList">
                <!-- JS will render items here -->
            </div>
        </div>

        <!-- Right sidebar calculations & checkout submit -->
        <div class="col-lg-5 mb-4">
            <div class="checkout-summary-card mb-4">
                <div style="padding:14px 16px 10px;border-bottom:1px solid #f0f0f0;">
                  <div style="font-size:0.9rem;font-weight:700;color:var(--primary);display:flex;align-items:center;gap:6px;">
                    <span style="display:inline-block;width:3px;height:14px;background:linear-gradient(var(--gold),var(--primary));border-radius:2px;"></span>
                    Order Summary
                  </div>
                </div>
                <div style="padding:14px 16px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.82rem;">
                        <span style="color:#78909c;">Items Subtotal:</span>
                        <span id="summarySubtotal" style="font-weight:700;color:var(--primary);">₹0.00</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:0.82rem;">
                        <span style="color:#78909c;">Delivery Charges:</span>
                        <span id="summaryDeliveryCharge" style="font-weight:700;color:var(--primary);">₹0.00</span>
                    </div>
                    <div style="border-top:1.5px dashed var(--gold-light);margin:10px 0;"></div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                        <span style="font-size:0.9rem;font-weight:700;color:var(--primary);">Grand Total:</span>
                        <span id="summaryTotal" style="font-size:1.25rem;font-weight:800;color:var(--gold-dark);">₹0.00</span>
                    </div>

                    <!-- UPI Payment QR Code display (mimicking Amazon checkout options) -->
                    <?php 
                    $upi_id = $company['company_upi_id'] ?? '';
                    $upi_name = $company['company_upi_name'] ?? $company['company_name'] ?? 'Sandal House';
                    if (!empty($upi_id)):
                    ?>
                    <div class="upi-payment-box mb-4">
                        <h6 class="font-weight-bold text-dark mb-1" style="font-size: 0.85rem;"><i class="fas fa-qrcode mr-1 text-primary"></i> Scan &amp; Pay via UPI</h6>
                        <p class="text-muted mb-2" style="font-size: 0.75rem;">Pay securely using any UPI app (GPay, PhonePe, Paytm, BHIM...)</p>
                        <div class="d-flex justify-content-center mb-2">
                            <div id="checkout-upi-qr" style="background:#fff; padding: 6px; border: 1px solid #ddd; border-radius: 8px;"></div>
                        </div>
                        <div class="font-weight-bold text-dark" style="font-size: 0.8rem;"><?= htmlspecialchars($upi_name) ?></div>
                        <code class="small text-muted" style="font-size: 0.72rem; word-break: break-all;"><?= htmlspecialchars($upi_id) ?></code>

                        <p class="text-muted mb-0" style="font-size: 0.72rem; border-top: 1px dashed var(--gold); padding-top: 8px; margin-top: 4px;">Or tap <strong>Pay</strong> below to pay from a UPI app on your phone.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Place Order Request Form -->
                    <form method="post" action="<?= base_url('portal/order') ?>" id="checkoutForm" onsubmit="return validateCheckout()">
                        
                        <!-- Form hidden inputs target -->
                        <div id="formHiddenInputs"></div>

                        <input type="hidden" name="delivery_charge" id="hiddenDeliveryCharge" value="0">
                        <input type="hidden" name="total_amount" id="hiddenTotalAmount" value="0">
                        <input type="hidden" name="pay_app" id="hiddenPayApp" value="">

                        <!-- Delivery Options -->
                        <div style="margin-bottom:12px;">
                          <label class="bb-label">📍 Delivery Distance</label>
                          <select name="delivery_distance" id="deliveryDistance" class="bb-select" onchange="calculateTotals()">
                            <option value="1">Within 1 KM</option>
                            <option value="2">2 KM</option>
                            <option value="3">3 KM</option>
                            <option value="4">4 KM</option>
                            <option value="5">5 KM</option>
                            <option value="6">6 KM</option>
                            <option value="7">7 KM</option>
                            <option value="8">8 KM</option>
                            <option value="9">9 KM</option>
                            <option value="10">10 KM</option>
                            <option value="12">12 KM (Far)</option>
                            <option value="15">15 KM (Far)</option>
                          </select>
                        </div>

                        <div style="margin-bottom:12px;">
                          <label class="bb-label">🕐 Delivery Time Slot</label>
                          <select name="delivery_slot" id="deliverySlot" class="bb-select" onchange="calculateTotals()">
                            <?php foreach ($slots as $slot): ?>
                              <option value="<?= htmlspecialchars($slot['name']) ?>" data-charge="<?= $slot['charge'] ?>">
                                <?= htmlspecialchars($slot['name']) ?> <?= $slot['charge'] > 0 ? '(+₹' . number_format($slot['charge'], 0) . ')' : '(FREE)' ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div style="margin-bottom:12px;">
                          <label class="bb-label">📝 Delivery Instructions (Optional)</label>
                          <textarea name="notes" style="width:100%;border:1.5px solid #e0e0e0;border-radius:10px;padding:10px 14px;font-family:'Poppins',sans-serif;font-size:0.82rem;color:var(--primary);resize:none;background:#fafafa;outline:none;" rows="2" placeholder="e.g. Call before delivery, landmark..."></textarea>
                        </div>

                        <div id="checkoutError" style="display:none;background:#fce4ec;color:#c62828;border-left:3px solid #c62828;border-radius:8px;padding:10px 14px;font-size:0.8rem;margin-bottom:10px;"></div>

                        <button type="button" class="bb-place-order-btn" onclick="openPayFlow()">
                          💳 Pay <span id="payBtnAmount">₹0.00</span> &amp; Place Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ Guest Login Sheet (shown if not logged in at checkout) ══ -->
<div id="guestLoginOverlay" onclick="if(event.target===this)this.style.display='none'" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:1300;align-items:flex-end;justify-content:center;">
  <div onclick="event.stopPropagation()" style="background:#fff;width:100%;max-width:480px;border-radius:22px 22px 0 0;padding:22px 20px 32px;box-shadow:0 -8px 32px rgba(59,35,20,0.22);animation:paySheetUp .28s ease;">
    <div style="width:40px;height:4px;background:#e0d5cf;border-radius:4px;margin:0 auto 16px;"></div>
    <div style="font-size:1.05rem;font-weight:800;color:var(--primary);margin-bottom:4px;">🔐 Login to Place Order</div>
    <p style="font-size:0.78rem;color:var(--text-muted);margin:0 0 16px;">Your cart is saved. Just login to confirm your order.</p>

    <label style="display:block;font-size:0.75rem;font-weight:700;color:#37474f;margin-bottom:5px;">Mobile Number</label>
    <input id="guestPhone" type="tel" placeholder="10-digit mobile number"
      style="width:100%;border:1.5px solid #e0e0e0;border-radius:10px;padding:11px 14px;font-family:'Poppins',sans-serif;font-size:0.88rem;color:var(--primary);background:#fafafa;outline:none;margin-bottom:12px;"
      onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e0e0e0'">

    <label style="display:block;font-size:0.75rem;font-weight:700;color:#37474f;margin-bottom:5px;">Password (last 4 digits of mobile)</label>
    <input id="guestPwd" type="password" placeholder="e.g. 4321" maxlength="4"
      style="width:100%;border:1.5px solid #e0e0e0;border-radius:10px;padding:11px 14px;font-family:'Poppins',sans-serif;font-size:0.88rem;color:var(--primary);background:#fafafa;outline:none;margin-bottom:12px;"
      onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='#e0e0e0'"
      onkeydown="if(event.key==='Enter')guestLogin()">

    <div id="guestLoginErr" style="display:none;background:#fce4ec;color:#c62828;border-left:3px solid #c62828;border-radius:8px;padding:9px 13px;font-size:0.78rem;margin-bottom:12px;"></div>

    <button id="guestLoginBtn" onclick="guestLogin()"
      style="width:100%;border:none;border-radius:12px;padding:14px;font-family:'Poppins',sans-serif;font-size:0.92rem;font-weight:700;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;box-shadow:0 4px 14px rgba(59,35,20,0.2);margin-bottom:10px;">
      Login &amp; Continue
    </button>
    <p style="text-align:center;font-size:0.74rem;color:var(--text-muted);margin:0;">
      New customer? <a href="<?= base_url('portal/login') ?>" style="color:var(--primary);font-weight:700;">Register here</a>
    </p>
  </div>
</div>

<!-- ══ Payment Method Bottom Sheet ══ -->
<div id="payOverlay" onclick="closePayFlow(event)" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1200;align-items:flex-end;justify-content:center;">
  <div onclick="event.stopPropagation()" style="background:#fff;width:100%;max-width:480px;border-radius:20px 20px 0 0;padding:18px 18px 24px;box-shadow:0 -8px 30px rgba(0,0,0,0.25);animation:paySheetUp .25s ease;">
    <div style="width:42px;height:4px;background:#e0e0e0;border-radius:4px;margin:0 auto 14px;"></div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
      <div style="font-size:1rem;font-weight:800;color:var(--primary);">Choose Payment</div>
      <div style="font-size:1.05rem;font-weight:800;color:var(--gold-dark);" id="paySheetAmount">₹0.00</div>
    </div>
    <p style="font-size:0.74rem;color:var(--text-muted);margin:0 0 14px;">Pay by UPI — your app opens with the amount filled in.</p>

    <?php if (!empty($upi_id)): ?>
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:10px;">
      <button type="button" onclick="choosePay('gpay')" class="pay-opt" style="background:#fff;border:1.5px solid #e0e0e0;border-radius:12px;padding:14px 8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;font-family:'Poppins',sans-serif;">
        <span style="font-size:1.5rem;">🟢</span><span style="font-size:0.8rem;font-weight:700;color:#202124;">Google Pay</span>
      </button>
      <button type="button" onclick="choosePay('phonepe')" class="pay-opt" style="background:#fff;border:1.5px solid #e0e0e0;border-radius:12px;padding:14px 8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;font-family:'Poppins',sans-serif;">
        <span style="font-size:1.5rem;">🟣</span><span style="font-size:0.8rem;font-weight:700;color:#5f259f;">PhonePe</span>
      </button>
      <button type="button" onclick="choosePay('paytm')" class="pay-opt" style="background:#fff;border:1.5px solid #e0e0e0;border-radius:12px;padding:14px 8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;font-family:'Poppins',sans-serif;">
        <span style="font-size:1.5rem;">🔵</span><span style="font-size:0.8rem;font-weight:700;color:#00baf2;">Paytm</span>
      </button>
      <button type="button" onclick="choosePay('upi')" class="pay-opt" style="background:#fff;border:1.5px solid #e0e0e0;border-radius:12px;padding:14px 8px;display:flex;flex-direction:column;align-items:center;gap:6px;cursor:pointer;font-family:'Poppins',sans-serif;">
        <span style="font-size:1.5rem;">💸</span><span style="font-size:0.8rem;font-weight:700;color:#097939;">Any UPI App</span>
      </button>
    </div>
    <?php endif; ?>

    <button type="button" onclick="choosePay('cod')" style="width:100%;background:var(--gold-light);border:1.5px solid var(--gold);border-radius:12px;padding:13px;font-family:'Poppins',sans-serif;font-size:0.86rem;font-weight:700;color:var(--primary);cursor:pointer;">
      💵 Cash / Pay on Delivery
    </button>
  </div>
</div>
<style>
@keyframes paySheetUp { from { transform: translateY(40px); opacity:.4; } to { transform: translateY(0); opacity:1; } }
.pay-opt:active { transform: scale(0.97); border-color: var(--gold) !important; }
</style>

<script src="<?= base_url('assets/js/qrcode.min.js') ?>"></script>
<script>
var upiId = "<?= addslashes($upi_id) ?>";
var upiName = "<?= addslashes($upi_name) ?>";
var upiQr = null;

// Delivery charges configuration variables from settings
var deliveryFreeThreshold = parseFloat("<?= $company['delivery_free_threshold'] ?? 500 ?>") || 0;
var deliveryBaseCharge = parseFloat("<?= $company['delivery_base_charge'] ?? 30 ?>") || 0;
var deliveryChargePerKm = parseFloat("<?= $company['delivery_charge_per_km'] ?? 10 ?>") || 0;

var subtotalAmt = 0;

function loadCheckoutCart() {
    fetch('<?= base_url("portal/get_cart_ajax") ?>', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            renderCheckout(d);
        }
    })
    .catch(err => {
        console.error('Checkout cart load error:', err);
    });
}

function renderCheckout(data) {
    var listContainer = document.getElementById('checkoutItemsList');
    var emptyState = document.getElementById('checkoutEmptyState');
    var mainLayout = document.getElementById('checkoutMainLayout');
    var hiddenInputs = document.getElementById('formHiddenInputs');
    
    listContainer.innerHTML = '';
    hiddenInputs.innerHTML = '';

    if (data.items.length === 0) {
        mainLayout.classList.add('d-none');
        emptyState.classList.remove('d-none');
        updateHeaderBadge(0);
        return;
    }

    mainLayout.classList.remove('d-none');
    emptyState.classList.add('d-none');

    updateHeaderBadge(data.cart_count);
    
    // Set global subtotal
    subtotalAmt = parseFloat(data.total) || 0;
    document.getElementById('summarySubtotal').textContent = '₹' + subtotalAmt.toFixed(2);

    data.items.forEach(function(item) {
        // Create UI row card
        var card = document.createElement('div');
        card.className = 'checkout-item-card d-flex align-items-start gap-3';
        
        var imgHtml = item.image
            ? '<img src="<?= base_url("assets/uploads/products/") ?>' + item.image + '" class="checkout-item-img mr-3">'
            : '<div class="d-flex align-items-center justify-content-center bg-light text-muted checkout-item-img mr-3" style="font-size: 1rem; width: 65px; height: 65px; border-radius: 8px;">🍰</div>';

        card.innerHTML = imgHtml + 
            '<div class="flex-grow-1 min-w-0 mr-3">' +
                '<div class="font-weight-bold text-dark text-truncate" style="font-size: 0.95rem;" title="' + item.name + '">' + item.name + '</div>' +
                '<div class="small mb-2"><span class="text-success font-weight-bold">₹' + parseFloat(item.price ?? item.mrp).toFixed(2) + ' / ' + item.unit + '</span>' + (item.mrp && item.price && parseFloat(item.mrp) > parseFloat(item.price) ? ' <span style="text-decoration:line-through;color:#999;font-size:.85em;">₹' + parseFloat(item.mrp).toFixed(2) + '</span>' : '') + '</div>' +
                
                // Special notes input
                '<div class="form-group mb-0 mt-1">' +
                    '<input type="text" class="form-control form-control-sm" style="font-size: 0.8rem; border-radius: 6px; max-width: 320px;" ' +
                           'placeholder="Special instructions (e.g. eggless, sugarless)" value="' + item.notes + '" onchange="updateItemNotes(' + item.id + ', this.value)">' +
                '</div>' +
            '</div>' +
            '<div class="d-flex flex-column align-items-end justify-content-between" style="min-height: 70px; flex-shrink: 0;">' +
                '<div class="checkout-qty-control">' +
                    '<button type="button" class="checkout-qty-btn" onclick="updateQty(' + item.id + ', ' + (item.qty - 1) + ')">-</button>' +
                    '<span class="checkout-qty-val">' + item.qty + '</span>' +
                    '<button type="button" class="checkout-qty-btn" onclick="updateQty(' + item.id + ', ' + (item.qty + 1) + ')">+</button>' +
                '</div>' +
                '<button type="button" class="btn btn-link text-danger p-0 mt-2 font-weight-bold" style="font-size: 0.78rem; text-decoration: none;" onclick="updateQty(' + item.id + ', 0)">' +
                    '<i class="fas fa-trash-alt mr-1"></i> Remove' +
                '</button>' +
            '</div>';

        listContainer.appendChild(card);

        // Append Hidden Inputs for checkout submission
        hiddenInputs.innerHTML += '<input type="hidden" name="product_id[]" value="' + item.id + '">';
        hiddenInputs.innerHTML += '<input type="hidden" name="qty[]" value="' + item.qty + '">';
        hiddenInputs.innerHTML += '<input type="hidden" name="item_notes[]" value="' + item.notes + '">';
    });

    // Calculate delivery and final totals
    calculateTotals();
}

function calculateTotals() {
    var distSelect = document.getElementById('deliveryDistance');
    var slotSelect = document.getElementById('deliverySlot');
    
    var distance = parseFloat(distSelect.value) || 1;
    
    var selectedSlotOption = slotSelect.options[slotSelect.selectedIndex];
    var slotCharge = selectedSlotOption ? parseFloat(selectedSlotOption.getAttribute('data-charge')) || 0 : 0;

    var baseFee = 0;
    var distanceFee = 0;

    // Apply thresholds
    if (subtotalAmt < deliveryFreeThreshold && subtotalAmt > 0) {
        baseFee = deliveryBaseCharge;
        distanceFee = distance * deliveryChargePerKm;
    }

    var totalDelivery = baseFee + distanceFee + slotCharge;
    var grandTotal = subtotalAmt + totalDelivery;

    // Update delivery charges text breakdown
    var delText = "₹" + totalDelivery.toFixed(2);
    if (totalDelivery === 0) {
        delText = '<span class="text-success font-weight-bold">FREE</span>';
    } else {
        var breakdown = [];
        if (baseFee > 0) breakdown.push("Base: ₹" + baseFee);
        if (distanceFee > 0) breakdown.push("Dist: ₹" + distanceFee);
        if (slotCharge > 0) breakdown.push("Slot: +₹" + slotCharge);
        delText += " <br><small class='text-muted' style='font-size:0.75rem;'>(" + breakdown.join(', ') + ")</small>";
    }

    document.getElementById('summaryDeliveryCharge').innerHTML = delText;
    document.getElementById('summaryTotal').textContent = '₹' + grandTotal.toFixed(2);
    document.getElementById('hiddenDeliveryCharge').value = totalDelivery.toFixed(2);
    document.getElementById('hiddenTotalAmount').value = grandTotal.toFixed(2);

    // Reflect total on the Pay button + payment sheet
    var amtTxt = '₹' + grandTotal.toFixed(2);
    var payBtnAmt = document.getElementById('payBtnAmount'); if (payBtnAmt) payBtnAmt.textContent = amtTxt;
    var sheetAmt = document.getElementById('paySheetAmount'); if (sheetAmt) sheetAmt.textContent = amtTxt;

    // Render desktop scan QR code with the live amount
    if (upiId) {
        var orderAmt = grandTotal.toFixed(2);
        var upiUrl = "upi://pay?pa=" + encodeURIComponent(upiId) + "&pn=" + encodeURIComponent(upiName) + "&am=" + orderAmt + "&cu=INR&tn=SandalHouseOrder";
        var paymentQrContainer = document.getElementById("checkout-upi-qr");
        if (paymentQrContainer) {
            paymentQrContainer.innerHTML = "";
            upiQr = new QRCode(paymentQrContainer, { text: upiUrl, width: 110, height: 110, colorDark: "#000000", colorLight: "#ffffff" });
        }
    }
}

/* ── Pay flow: open chooser → place order → confirmation launches the app ── */
function openPayFlow() {
    if (!validateCheckout()) return;
    // If not logged in → show login sheet first
    if (!window.PORTAL_LOGGED_IN) {
        document.getElementById('guestLoginOverlay').style.display = 'flex';
        return;
    }
    // No UPI configured → just place the order (pay on delivery)
    if (!upiId) { choosePay('cod'); return; }
    document.getElementById('paySheetAmount').textContent = document.getElementById('summaryTotal').textContent;
    document.getElementById('payOverlay').style.display = 'flex';
}

/* ── Guest checkout login ── */
function guestLogin() {
    var phone = document.getElementById('guestPhone').value.trim();
    var pwd   = document.getElementById('guestPwd').value.trim();
    var errEl = document.getElementById('guestLoginErr');
    var btn   = document.getElementById('guestLoginBtn');
    if (!phone || !pwd) { errEl.textContent = 'Enter your mobile number and password.'; errEl.style.display='block'; return; }
    btn.disabled = true; btn.textContent = 'Logging in…';
    var fd = new FormData();
    fd.append('phone', phone); fd.append('password', pwd);
    fetch('<?= base_url("portal/login_ajax") ?>', { method:'POST', body:fd })
    .then(r => r.json())
    .then(function(d) {
        btn.disabled = false; btn.textContent = 'Login & Continue';
        if (d.success) {
            if (d.token) localStorage.setItem('portal_remember_token', d.token);
            window.PORTAL_LOGGED_IN = true;
            document.getElementById('guestLoginOverlay').style.display = 'none';
            // Continue with order placement
            if (!upiId) { choosePay('cod'); return; }
            document.getElementById('paySheetAmount').textContent = document.getElementById('summaryTotal').textContent;
            document.getElementById('payOverlay').style.display = 'flex';
        } else {
            errEl.textContent = d.error || 'Login failed. Please try again.';
            errEl.style.display = 'block';
        }
    }).catch(function() {
        btn.disabled = false; btn.textContent = 'Login & Continue';
        errEl.textContent = 'Network error. Please try again.';
        errEl.style.display = 'block';
    });
}
function closePayFlow(e) {
    if (!e || e.target === document.getElementById('payOverlay')) {
        document.getElementById('payOverlay').style.display = 'none';
    }
}
function choosePay(app) {
    document.getElementById('hiddenPayApp').value = app;
    document.getElementById('checkoutForm').submit();
}

function updateQty(pid, qty) {
    var formData = new FormData();
    formData.append('product_id', pid);
    formData.append('qty', qty);

    fetch('<?= base_url("portal/update_cart_ajax") ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            loadCheckoutCart();
        }
    });
}

function updateItemNotes(pid, notes) {
    var qtyInput = document.querySelector('input[name="product_id[]"][value="' + pid + '"]');
    if (!qtyInput) return;
    
    var itemQty = 1;
    var idxInput = Array.from(document.querySelectorAll('input[name="product_id[]"]')).indexOf(qtyInput);
    if (idxInput !== -1) {
        var qtyInputs = document.querySelectorAll('input[name="qty[]"]');
        if (qtyInputs[idxInput]) {
            itemQty = parseInt(qtyInputs[idxInput].value) || 1;
        }
    }

    var formData = new FormData();
    formData.append('product_id', pid);
    formData.append('qty', itemQty);
    formData.append('notes', notes);

    fetch('<?= base_url("portal/update_cart_ajax") ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            loadHiddenInputsOnly();
        }
    });
}

function loadHiddenInputsOnly() {
    fetch('<?= base_url("portal/get_cart_ajax") ?>', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            var hiddenInputs = document.getElementById('formHiddenInputs');
            hiddenInputs.innerHTML = '';
            data.items.forEach(function(item) {
                hiddenInputs.innerHTML += '<input type="hidden" name="product_id[]" value="' + item.id + '">';
                hiddenInputs.innerHTML += '<input type="hidden" name="qty[]" value="' + item.qty + '">';
                hiddenInputs.innerHTML += '<input type="hidden" name="item_notes[]" value="' + item.notes + '">';
            });
        }
    });
}

function updateHeaderBadge(count) {
    var badge = document.getElementById('headerCartCount');
    if (badge) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

function validateCheckout() {
    var productIds = document.querySelectorAll('input[name="product_id[]"]');
    if (productIds.length === 0) {
        var err = document.getElementById('checkoutError');
        err.textContent = 'Please add at least one item to your cart before checking out.';
        err.classList.remove('d-none');
        return false;
    }
    return true;
}

// Load cart on page ready
window.addEventListener('DOMContentLoaded', function() {
    loadCheckoutCart();
});
</script>
