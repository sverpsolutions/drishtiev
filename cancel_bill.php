<?php
/**
 * NCG ERP - Standalone Invoice Cancellation & Stock Reversal Tool
 * 
 * Usage:
 * 1. Place this file in public_html/ (e.g. https://ncg.sverpsolutions.com/cancel_bill.php)
 * 2. Open in browser, search by Bill / Invoice Number or ID.
 * 3. Review bill details and item stock before confirming cancellation.
 * 4. Click "Cancel Bill" to atomically mark status as 'cancelled' and restore inventory.
 */

error_reporting(E_ALL);
ini_ini_display: ini_set('display_errors', 1);

// Optional security PIN to prevent unauthorized cancellations (leave blank to disable)
define('ADMIN_PIN', '1234'); 

// ─── Database Connection ─────────────────────────────────────────────────────
function get_db() {
    $dbfile = __DIR__ . '/application/config/database.php';
    if (file_exists($dbfile)) {
        if (!defined('BASEPATH')) define('BASEPATH', true);
        if (!defined('ENVIRONMENT')) {
            $is_local = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false));
            define('ENVIRONMENT', $is_local ? 'development' : 'production');
        }
        include $dbfile;
        if (isset($db['default'])) {
            $cfg = $db['default'];
            return new PDO(
                "mysql:host={$cfg['hostname']};dbname={$cfg['database']};charset=utf8mb4",
                $cfg['username'],
                $cfg['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        }
    }

    // Direct fallback
    $is_local = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false));
    $host = 'localhost';
    $user = $is_local ? 'root' : 'u153068796_ncg';
    $pass = $is_local ? '' : 'Mci8yo@257';
    $name = $is_local ? 'testncg' : 'u153068796_ncg';
    return new PDO(
        "mysql:host=$host;dbname=$name;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
}

$pdo = null;
$db_error = null;
try {
    $pdo = get_db();
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

$query_str = trim($_GET['q'] ?? $_POST['q'] ?? '');
$invoice = null;
$items = [];
$customer = null;
$location = null;
$success_msg = null;
$error_msg = null;

// ─── Process Cancellation ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_bill') {
    $invoice_id = (int)($_POST['invoice_id'] ?? 0);
    $submitted_pin = trim($_POST['admin_pin'] ?? '');

    if (ADMIN_PIN !== '' && $submitted_pin !== ADMIN_PIN) {
        $error_msg = "Invalid Security PIN. Please enter the correct PIN to cancel.";
    } elseif ($invoice_id <= 0) {
        $error_msg = "Invalid Invoice ID.";
    } else {
        try {
            $pdo->beginTransaction();

            // Fetch latest invoice state with lock
            $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? FOR UPDATE");
            $stmt->execute([$invoice_id]);
            $inv = $stmt->fetch();

            if (!$inv) {
                throw new Exception("Invoice ID $invoice_id not found.");
            }
            if ($inv['status'] === 'cancelled') {
                throw new Exception("Invoice #{$inv['invoice_no']} is already cancelled.");
            }

            // Fetch items
            $stmt_items = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
            $stmt_items->execute([$invoice_id]);
            $inv_items = $stmt_items->fetchAll();

            $loc_id = !empty($inv['location_id']) ? (int)$inv['location_id'] : 1;
            $restored_details = [];

            // 1. Restore product stock (both products and product_location_stock)
            $stmt_p_upd = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?");
            $stmt_loc_upd = $pdo->prepare("UPDATE product_location_stock SET stock_qty = stock_qty + ?, last_updated = NOW() WHERE product_id = ? AND location_id = ?");
            
            // Check if stock_movements table exists
            $has_movements = false;
            try {
                $chk = $pdo->query("SHOW TABLES LIKE 'stock_movements'")->fetch();
                $has_movements = !empty($chk);
            } catch (Exception $e) {}

            $stmt_sm = null;
            if ($has_movements) {
                $stmt_sm = $pdo->prepare("INSERT INTO stock_movements (product_id, product_name, location_id, movement_type, quantity, balance_before, balance_after, reference_type, reference_id, reference_no, user_id, user_name, created_at) VALUES (?, ?, ?, 'cancellation_in', ?, ?, ?, 'invoice', ?, ?, ?, 'Cancel Tool', NOW())");
            }

            foreach ($inv_items as $item) {
                $pid = (int)$item['product_id'];
                $qty = (float)$item['qty'];

                // Get current stock before update
                $st_cur = $pdo->prepare("SELECT stock_qty FROM products WHERE id = ?");
                $st_cur->execute([$pid]);
                $cur_p = $st_cur->fetch();
                $before_qty = $cur_p ? (float)$cur_p['stock_qty'] : 0;
                $after_qty = $before_qty + $qty;

                // Update products table
                $stmt_p_upd->execute([$qty, $pid]);

                // Update product_location_stock table
                $stmt_loc_upd->execute([$qty, $pid, $loc_id]);

                // Log movement if table exists
                if ($stmt_sm) {
                    $stmt_sm->execute([$pid, $item['product_name'], $loc_id, $qty, $before_qty, $after_qty, $invoice_id, $inv['invoice_no'], $inv['created_by']]);
                }

                $restored_details[] = htmlspecialchars($item['product_name']) . " (+{$qty} qty restored, now {$after_qty})";
            }

            // 2. Revert customer balance if due amount > 0
            if (!empty($inv['due_amount']) && (float)$inv['due_amount'] > 0 && !empty($inv['customer_id'])) {
                $stmt_cust = $pdo->prepare("UPDATE customers SET balance = GREATEST(0, balance - ?) WHERE id = ?");
                $stmt_cust->execute([(float)$inv['due_amount'], (int)$inv['customer_id']]);
            }

            // 3. Revert customer wallet if used
            if (!empty($inv['wallet_amount_used']) && (float)$inv['wallet_amount_used'] > 0 && !empty($inv['customer_id'])) {
                $stmt_w1 = $pdo->prepare("UPDATE customers SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt_w1->execute([(float)$inv['wallet_amount_used'], (int)$inv['customer_id']]);
            }
            if (!empty($inv['wallet_amount_credited']) && (float)$inv['wallet_amount_credited'] > 0 && !empty($inv['customer_id'])) {
                $stmt_w2 = $pdo->prepare("UPDATE customers SET wallet_balance = GREATEST(0, wallet_balance - ?) WHERE id = ?");
                $stmt_w2->execute([(float)$inv['wallet_amount_credited'], (int)$inv['customer_id']]);
            }

            // 4. Mark invoice status = 'cancelled'
            $stmt_inv_cancel = $pdo->prepare("UPDATE invoices SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $stmt_inv_cancel->execute([$invoice_id]);

            // 5. Audit log
            try {
                $chk_audit = $pdo->query("SHOW TABLES LIKE 'audit_logs'")->fetch();
                if (!empty($chk_audit)) {
                    $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (module, action, record_id, record_no, old_data, new_data, old_amount, new_amount, description, user_id, user_name, location_id, created_at) VALUES ('billing', 'cancel_bill', ?, ?, ?, '{\"status\":\"cancelled\"}', ?, 0.00, ?, ?, 'Admin (via Tool)', ?, NOW())");
                    $stmt_audit->execute([
                        $invoice_id,
                        $inv['invoice_no'],
                        json_encode(['status' => $inv['status'], 'total' => $inv['total_amount']]),
                        $inv['total_amount'],
                        "Cancelled via cancel_bill.php. Restored items: " . implode(', ', $restored_details),
                        $inv['created_by'] ?: 1,
                        $loc_id
                    ]);
                }
            } catch (Exception $e) {}

            $pdo->commit();
            $success_msg = "Invoice #{$inv['invoice_no']} (ID: {$inv['id']}) has been successfully CANCELLED and stock has been restored!";
            $query_str = $inv['invoice_no']; // reload to show updated cancelled state

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_msg = "Cancellation failed: " . $e->getMessage();
        }
    }
}

// ─── Search Invoice ──────────────────────────────────────────────────────────
if ($pdo && $query_str !== '') {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM invoices 
            WHERE invoice_no = ? OR id = ? OR invoice_no LIKE ?
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$query_str, is_numeric($query_str) ? (int)$query_str : 0, '%' . $query_str . '%']);
        $invoice = $stmt->fetch();

        if ($invoice) {
            // Get items with current stock info
            $stmt_items = $pdo->prepare("
                SELECT ii.*, 
                       p.name AS p_name, 
                       p.stock_qty AS current_global_stock,
                       pls.stock_qty AS current_loc_stock
                FROM invoice_items ii
                LEFT JOIN products p ON p.id = ii.product_id
                LEFT JOIN product_location_stock pls ON pls.product_id = ii.product_id AND pls.location_id = ?
                WHERE ii.invoice_id = ?
                ORDER BY ii.id ASC
            ");
            $loc_id = !empty($invoice['location_id']) ? (int)$invoice['location_id'] : 1;
            $stmt_items->execute([$loc_id, $invoice['id']]);
            $items = $stmt_items->fetchAll();

            // Customer
            if (!empty($invoice['customer_id'])) {
                $stmt_c = $pdo->prepare("SELECT id, name, phone, address FROM customers WHERE id = ?");
                $stmt_c->execute([$invoice['customer_id']]);
                $customer = $stmt_c->fetch();
            }

            // Location
            if (!empty($invoice['location_id'])) {
                $stmt_l = $pdo->prepare("SELECT id, name FROM locations WHERE id = ?");
                $stmt_l->execute([$invoice['location_id']]);
                $location = $stmt_l->fetch();
            }
        } else {
            if (!$success_msg) {
                $error_msg = "No invoice found matching: \"" . htmlspecialchars($query_str) . "\"";
            }
        }
    } catch (Exception $e) {
        $error_msg = "Database query error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Bill & Restore Stock — NCG ERP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .card { border-radius: 10px; border: 1px solid #e2e8f0; }
        .badge-status { font-size: 0.9rem; padding: 6px 12px; border-radius: 6px; }
        .table th { background: #f8fafc; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .stock-pill { background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 12px; font-weight: 600; font-size: 0.85rem; }
        .stock-after { background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 12px; font-weight: 700; font-size: 0.85rem; }
    </style>
</head>
<body class="py-4">
<div class="container" style="max-width: 900px;">

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h4 class="font-weight-bold mb-1 text-dark">
                <i class="fas fa-undo-alt text-danger mr-2"></i> Bill Cancellation &amp; Stock Reversal Tool
            </h4>
            <div class="text-muted small">Search any bill to review items and cancel it while safely restoring inventory.</div>
        </div>
        <div>
            <a href="/billing" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Back to ERP</a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if ($db_error): ?>
        <div class="alert alert-danger shadow-sm">
            <i class="fas fa-exclamation-triangle mr-2"></i> <strong>Database Connection Failed:</strong> <?= htmlspecialchars($db_error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
        <div class="alert alert-success shadow-sm alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($success_msg) ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger shadow-sm alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error_msg) ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Search Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="form-inline d-flex justify-content-between">
                <label class="font-weight-bold mr-3 mb-2" for="q" style="font-size: 0.95rem;">
                    <i class="fas fa-receipt text-primary mr-1"></i> Bill / Invoice Number:
                </label>
                <div class="input-group flex-grow-1 mr-2 mb-2">
                    <input type="text" name="q" id="q" class="form-control" 
                           placeholder="e.g. WH/26-27/005051 or 7831" 
                           value="<?= htmlspecialchars($query_str) ?>" required autofocus>
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-search mr-1"></i> Search Bill
                        </button>
                    </div>
                </div>
                <?php if ($query_str !== ''): ?>
                    <a href="?" class="btn btn-outline-secondary mb-2">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($invoice): ?>
        <!-- Invoice Details Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small">INVOICE NUMBER:</span>
                    <h5 class="font-weight-bold text-dark mb-0"><?= htmlspecialchars($invoice['invoice_no']) ?></h5>
                    <small class="text-muted">ID: #<?= (int)$invoice['id'] ?> | Date: <?= htmlspecialchars($invoice['invoice_date']) ?> (<?= htmlspecialchars($invoice['created_at']) ?>)</small>
                </div>
                <div>
                    <?php if ($invoice['status'] === 'cancelled'): ?>
                        <span class="badge badge-danger badge-status"><i class="fas fa-ban mr-1"></i> CANCELLED</span>
                    <?php elseif ($invoice['status'] === 'paid'): ?>
                        <span class="badge badge-success badge-status"><i class="fas fa-check mr-1"></i> PAID</span>
                    <?php else: ?>
                        <span class="badge badge-warning badge-status text-dark"><?= strtoupper(htmlspecialchars($invoice['status'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Customer</small>
                        <strong><?= htmlspecialchars($customer['name'] ?? 'Walk-in Customer') ?></strong>
                        <?php if (!empty($customer['phone'])): ?>
                            <div class="small text-muted"><?= htmlspecialchars($customer['phone']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Location / Warehouse</small>
                        <strong><?= htmlspecialchars($location['name'] ?? 'Main Warehouse (1)') ?></strong>
                    </div>
                    <div class="col-sm-4 text-sm-right">
                        <small class="text-muted d-block">Payment Mode &amp; Total</small>
                        <span class="badge badge-info text-uppercase"><?= htmlspecialchars($invoice['payment_mode']) ?></span>
                        <h4 class="font-weight-bold text-success mb-0">₹<?= number_format((float)$invoice['total_amount'], 2) ?></h4>
                    </div>
                </div>

                <!-- Items Table -->
                <h6 class="font-weight-bold text-secondary mb-2 mt-4">
                    <i class="fas fa-boxes mr-1"></i> Bill Items &amp; Stock Restoration Impact
                </h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th class="text-center">Sold Qty</th>
                                <th class="text-right">Rate</th>
                                <th class="text-right">Line Total</th>
                                <th class="text-center">Current Stock</th>
                                <th class="text-center">After Reversal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-3">No line items recorded on this invoice.</td></tr>
                            <?php else: foreach ($items as $idx => $it): 
                                $cur_stk = (float)($it['current_loc_stock'] ?? $it['current_global_stock'] ?? 0);
                                $qty = (float)$it['qty'];
                                $after_stk = $cur_stk + $qty;
                            ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($it['product_name'] ?? $it['p_name'] ?? 'Item #' . $it['product_id']) ?></strong>
                                        <div class="small text-muted">ID: <?= (int)$it['product_id'] ?> | HSN: <?= htmlspecialchars($it['hsn_code'] ?? '—') ?></div>
                                    </td>
                                    <td class="text-center font-weight-bold text-danger"><?= $qty ?></td>
                                    <td class="text-right">₹<?= number_format((float)$it['rate'], 2) ?></td>
                                    <td class="text-right font-weight-bold">₹<?= number_format((float)$it['total'], 2) ?></td>
                                    <td class="text-center">
                                        <span class="stock-pill"><?= $cur_stk ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($invoice['status'] === 'cancelled'): ?>
                                            <span class="badge badge-secondary">Already Restored</span>
                                        <?php else: ?>
                                            <span class="stock-after">+<?= $qty ?> &rarr; <?= $after_stk ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Action Section -->
                <div class="mt-4 pt-3 border-top">
                    <?php if ($invoice['status'] === 'cancelled'): ?>
                        <div class="alert alert-secondary mb-0">
                            <i class="fas fa-info-circle mr-1"></i> <strong>This bill is already cancelled.</strong> No further action is required; items were already returned to stock.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to CANCEL Bill <?= htmlspecialchars($invoice['invoice_no']) ?>? Stock will be restored.');">
                            <input type="hidden" name="action" value="cancel_bill">
                            <input type="hidden" name="invoice_id" value="<?= (int)$invoice['id'] ?>">
                            <input type="hidden" name="q" value="<?= htmlspecialchars($query_str) ?>">

                            <div class="alert alert-warning mb-3">
                                <strong><i class="fas fa-shield-alt mr-1"></i> What happens when you cancel:</strong>
                                <ul class="mb-0 pl-3 mt-1 small">
                                    <li>Invoice status will change to <span class="badge badge-danger">CANCELLED</span>.</li>
                                    <li>Sold quantities will be automatically credited back to warehouse stock in <code>products</code> and <code>product_location_stock</code>.</li>
                                    <li>Sales totals, shifts, and reports will exclude this bill.</li>
                                </ul>
                            </div>

                            <div class="d-flex align-items-center justify-content-between">
                                <div class="form-group mb-0 d-flex align-items-center">
                                    <?php if (ADMIN_PIN !== ''): ?>
                                        <label for="admin_pin" class="small font-weight-bold mr-2 mb-0">Admin PIN:</label>
                                        <input type="password" name="admin_pin" id="admin_pin" class="form-control form-control-sm mr-3" style="width: 120px;" placeholder="PIN" required>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-danger font-weight-bold px-4">
                                        <i class="fas fa-times-circle mr-1"></i> Cancel Bill &amp; Restore Stock
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Help Card -->
    <div class="card shadow-sm border-0 bg-light">
        <div class="card-body py-3 small text-muted">
            <i class="fas fa-lightbulb text-warning mr-1"></i> <strong>Tip:</strong> You can paste either the full invoice number (e.g. <code>WH/26-27/005051</code>) or just the numeric ID (e.g. <code>7831</code>).
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
