<?php
// Safe and Reversible Migration for GST Tax Engine
$mysqli = new mysqli('localhost', 'root', '', 'suvasanclothes');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

function hasColumn($m, $table, $column) {
    $res = $m->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && $res->num_rows > 0;
}

function hasTable($m, $table) {
    $res = $m->query("SHOW TABLES LIKE '$table'");
    return $res && $res->num_rows > 0;
}

echo "=== 1. Creating gst_tax_rules table ===\n";
if (!hasTable($mysqli, 'gst_tax_rules')) {
    $sql = "CREATE TABLE `gst_tax_rules` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `rule_code` VARCHAR(50) NOT NULL UNIQUE,
      `rule_name` VARCHAR(100) NOT NULL,
      `tax_category` VARCHAR(50) NOT NULL DEFAULT 'garment_slab',
      `hsn_prefix` VARCHAR(100) NULL,
      `threshold_amount` DECIMAL(10,2) NOT NULL DEFAULT 2500.00,
      `rate_below_or_equal` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
      `rate_above` DECIMAL(5,2) NOT NULL DEFAULT 18.00,
      `effective_from` DATE NOT NULL DEFAULT '2017-07-01',
      `effective_to` DATE NULL,
      `is_active` TINYINT(1) NOT NULL DEFAULT 1,
      `description` TEXT NULL,
      `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX `idx_rule_cat_date` (`tax_category`, `is_active`, `effective_from`, `effective_to`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $mysqli->query($sql) or die("Error creating gst_tax_rules: " . $mysqli->error);
    echo "  Table gst_tax_rules created.\n";

    // Seed default rule
    $seed = "INSERT INTO `gst_tax_rules` (`rule_code`, `rule_name`, `tax_category`, `hsn_prefix`, `threshold_amount`, `rate_below_or_equal`, `rate_above`, `effective_from`, `effective_to`, `is_active`, `description`) VALUES
    ('GARMENT_SLAB_2500', 'Indian Readymade Garment Threshold Slab', 'garment_slab', '61,62,63,64,65', 2500.00, 5.00, 18.00, '2017-07-01', NULL, 1, 'Sale value <= Rs. 2,500: 5% GST; Sale value > Rs. 2,500: 18% GST per piece'),
    ('STANDARD_GOODS', 'Standard General Goods', 'standard', NULL, 0.00, 0.00, 0.00, '2017-07-01', NULL, 1, 'Uses product default master GST rate for FMCG and general goods')";
    $mysqli->query($seed) or die("Error seeding gst_tax_rules: " . $mysqli->error);
    echo "  Seeded default rules into gst_tax_rules.\n";
} else {
    echo "  gst_tax_rules table already exists.\n";
}

echo "\n=== 2. Extending products table ===\n";
if (!hasColumn($mysqli, 'products', 'tax_category')) {
    $mysqli->query("ALTER TABLE `products` ADD COLUMN `tax_category` VARCHAR(50) NOT NULL DEFAULT 'garment_slab' AFTER `product_type`");
    echo "  Added products.tax_category\n";
}
if (!hasColumn($mysqli, 'products', 'tax_inclusive_sales')) {
    $mysqli->query("ALTER TABLE `products` ADD COLUMN `tax_inclusive_sales` TINYINT(1) NOT NULL DEFAULT 1 AFTER `selling_price`");
    echo "  Added products.tax_inclusive_sales\n";
}
if (!hasColumn($mysqli, 'products', 'tax_inclusive_purchase')) {
    $mysqli->query("ALTER TABLE `products` ADD COLUMN `tax_inclusive_purchase` TINYINT(1) NOT NULL DEFAULT 0 AFTER `purchase_price`");
    echo "  Added products.tax_inclusive_purchase\n";
}
if (!hasColumn($mysqli, 'products', 'purchase_gst_rate')) {
    $mysqli->query("ALTER TABLE `products` ADD COLUMN `purchase_gst_rate` DECIMAL(5,2) NULL DEFAULT NULL AFTER `tax_inclusive_purchase`");
    echo "  Added products.purchase_gst_rate\n";
}
if (!hasColumn($mysqli, 'products', 'sales_gst_rate')) {
    $mysqli->query("ALTER TABLE `products` ADD COLUMN `sales_gst_rate` DECIMAL(5,2) NULL DEFAULT NULL AFTER `tax_inclusive_sales`");
    echo "  Added products.sales_gst_rate\n";
}
// Set initial categories
$mysqli->query("UPDATE `products` SET `tax_category` = 'garment_slab' WHERE `is_garment` = 1");
$mysqli->query("UPDATE `products` SET `tax_category` = 'standard' WHERE `is_garment` = 0");

echo "\n=== 3. Extending garment_variants table ===\n";
if (!hasColumn($mysqli, 'garment_variants', 'purchase_gst_rate')) {
    $mysqli->query("ALTER TABLE `garment_variants` ADD COLUMN `purchase_gst_rate` DECIMAL(5,2) NULL DEFAULT NULL AFTER `purchase_price`");
    echo "  Added garment_variants.purchase_gst_rate\n";
}
if (!hasColumn($mysqli, 'garment_variants', 'sales_gst_rate')) {
    $mysqli->query("ALTER TABLE `garment_variants` ADD COLUMN `sales_gst_rate` DECIMAL(5,2) NULL DEFAULT NULL AFTER `selling_price`");
    echo "  Added garment_variants.sales_gst_rate\n";
}
if (!hasColumn($mysqli, 'garment_variants', 'tax_inclusive_sales')) {
    $mysqli->query("ALTER TABLE `garment_variants` ADD COLUMN `tax_inclusive_sales` TINYINT(1) NOT NULL DEFAULT 1 AFTER `sales_gst_rate`");
    echo "  Added garment_variants.tax_inclusive_sales\n";
}
if (!hasColumn($mysqli, 'garment_variants', 'tax_inclusive_purchase')) {
    $mysqli->query("ALTER TABLE `garment_variants` ADD COLUMN `tax_inclusive_purchase` TINYINT(1) NOT NULL DEFAULT 0 AFTER `purchase_gst_rate`");
    echo "  Added garment_variants.tax_inclusive_purchase\n";
}

echo "\n=== 4. Extending purchase_items table ===\n";
if (!hasColumn($mysqli, 'purchase_items', 'cgst_percent')) {
    $mysqli->query("ALTER TABLE `purchase_items` ADD COLUMN `cgst_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `gst_amount`");
    echo "  Added purchase_items.cgst_percent\n";
}
if (!hasColumn($mysqli, 'purchase_items', 'sgst_percent')) {
    $mysqli->query("ALTER TABLE `purchase_items` ADD COLUMN `sgst_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `cgst_percent`");
    echo "  Added purchase_items.sgst_percent\n";
}
if (!hasColumn($mysqli, 'purchase_items', 'igst_percent')) {
    $mysqli->query("ALTER TABLE `purchase_items` ADD COLUMN `igst_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `sgst_percent`");
    echo "  Added purchase_items.igst_percent\n";
}
if (!hasColumn($mysqli, 'purchase_items', 'cgst_amount')) {
    $mysqli->query("ALTER TABLE `purchase_items` ADD COLUMN `cgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `igst_percent`");
    echo "  Added purchase_items.cgst_amount\n";
}
if (!hasColumn($mysqli, 'purchase_items', 'sgst_amount')) {
    $mysqli->query("ALTER TABLE `purchase_items` ADD COLUMN `sgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `cgst_amount`");
    echo "  Added purchase_items.sgst_amount\n";
}
if (!hasColumn($mysqli, 'purchase_items', 'igst_amount')) {
    $mysqli->query("ALTER TABLE `purchase_items` ADD COLUMN `igst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `sgst_amount`");
    echo "  Added purchase_items.igst_amount\n";
}
if (!hasColumn($mysqli, 'purchase_items', 'itc_eligibility')) {
    $mysqli->query("ALTER TABLE `purchase_items` ADD COLUMN `itc_eligibility` ENUM('eligible','ineligible','blocked') NOT NULL DEFAULT 'eligible' AFTER `igst_amount`");
    echo "  Added purchase_items.itc_eligibility\n";
}
if (!hasColumn($mysqli, 'purchase_items', 'itc_amount')) {
    $mysqli->query("ALTER TABLE `purchase_items` ADD COLUMN `itc_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `itc_eligibility`");
    echo "  Added purchase_items.itc_amount\n";
}
if (!hasColumn($mysqli, 'purchase_items', 'tax_inclusive')) {
    $mysqli->query("ALTER TABLE `purchase_items` ADD COLUMN `tax_inclusive` TINYINT(1) NOT NULL DEFAULT 0 AFTER `itc_amount`");
    echo "  Added purchase_items.tax_inclusive\n";
}
if (!hasColumn($mysqli, 'purchase_items', 'tax_rule_applied')) {
    $mysqli->query("ALTER TABLE `purchase_items` ADD COLUMN `tax_rule_applied` VARCHAR(50) NULL DEFAULT NULL AFTER `tax_inclusive`");
    echo "  Added purchase_items.tax_rule_applied\n";
}

echo "\n=== 5. Extending purchases table ===\n";
if (!hasColumn($mysqli, 'purchases', 'cgst_amount')) {
    $mysqli->query("ALTER TABLE `purchases` ADD COLUMN `cgst_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `total_gst`");
    echo "  Added purchases.cgst_amount\n";
}
if (!hasColumn($mysqli, 'purchases', 'sgst_amount')) {
    $mysqli->query("ALTER TABLE `purchases` ADD COLUMN `sgst_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `cgst_amount`");
    echo "  Added purchases.sgst_amount\n";
}
if (!hasColumn($mysqli, 'purchases', 'igst_amount')) {
    $mysqli->query("ALTER TABLE `purchases` ADD COLUMN `igst_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `sgst_amount`");
    echo "  Added purchases.igst_amount\n";
}
if (!hasColumn($mysqli, 'purchases', 'is_interstate')) {
    $mysqli->query("ALTER TABLE `purchases` ADD COLUMN `is_interstate` TINYINT(1) NOT NULL DEFAULT 0 AFTER `igst_amount`");
    echo "  Added purchases.is_interstate\n";
}
if (!hasColumn($mysqli, 'purchases', 'supplier_invoice_no')) {
    $mysqli->query("ALTER TABLE `purchases` ADD COLUMN `supplier_invoice_no` VARCHAR(50) NULL DEFAULT NULL AFTER `invoice_no`");
    echo "  Added purchases.supplier_invoice_no\n";
}
if (!hasColumn($mysqli, 'purchases', 'supplier_invoice_date')) {
    $mysqli->query("ALTER TABLE `purchases` ADD COLUMN `supplier_invoice_date` DATE NULL DEFAULT NULL AFTER `supplier_invoice_no`");
    echo "  Added purchases.supplier_invoice_date\n";
}
if (!hasColumn($mysqli, 'purchases', 'total_itc_eligible')) {
    $mysqli->query("ALTER TABLE `purchases` ADD COLUMN `total_itc_eligible` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `is_interstate`");
    echo "  Added purchases.total_itc_eligible\n";
}
if (!hasColumn($mysqli, 'purchases', 'total_itc_ineligible')) {
    $mysqli->query("ALTER TABLE `purchases` ADD COLUMN `total_itc_ineligible` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `total_itc_eligible`");
    echo "  Added purchases.total_itc_ineligible\n";
}

echo "\n=== 6. Extending invoice_items table ===\n";
if (!hasColumn($mysqli, 'invoice_items', 'tax_inclusive')) {
    $mysqli->query("ALTER TABLE `invoice_items` ADD COLUMN `tax_inclusive` TINYINT(1) NOT NULL DEFAULT 1 AFTER `total`");
    echo "  Added invoice_items.tax_inclusive\n";
}
if (!hasColumn($mysqli, 'invoice_items', 'tax_rule_applied')) {
    $mysqli->query("ALTER TABLE `invoice_items` ADD COLUMN `tax_rule_applied` VARCHAR(50) NULL DEFAULT NULL AFTER `tax_inclusive`");
    echo "  Added invoice_items.tax_rule_applied\n";
}

echo "\n=== 7. Extending purchase_return_items table ===\n";
if (!hasColumn($mysqli, 'purchase_return_items', 'taxable_amount')) {
    $mysqli->query("ALTER TABLE `purchase_return_items` ADD COLUMN `taxable_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `total`");
    echo "  Added purchase_return_items.taxable_amount\n";
}
if (!hasColumn($mysqli, 'purchase_return_items', 'gst_percent')) {
    $mysqli->query("ALTER TABLE `purchase_return_items` ADD COLUMN `gst_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `taxable_amount`");
    echo "  Added purchase_return_items.gst_percent\n";
}
if (!hasColumn($mysqli, 'purchase_return_items', 'gst_amount')) {
    $mysqli->query("ALTER TABLE `purchase_return_items` ADD COLUMN `gst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `gst_percent`");
    echo "  Added purchase_return_items.gst_amount\n";
}
if (!hasColumn($mysqli, 'purchase_return_items', 'cgst_amount')) {
    $mysqli->query("ALTER TABLE `purchase_return_items` ADD COLUMN `cgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `gst_amount`");
    echo "  Added purchase_return_items.cgst_amount\n";
}
if (!hasColumn($mysqli, 'purchase_return_items', 'sgst_amount')) {
    $mysqli->query("ALTER TABLE `purchase_return_items` ADD COLUMN `sgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `cgst_amount`");
    echo "  Added purchase_return_items.sgst_amount\n";
}
if (!hasColumn($mysqli, 'purchase_return_items', 'igst_amount')) {
    $mysqli->query("ALTER TABLE `purchase_return_items` ADD COLUMN `igst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `sgst_amount`");
    echo "  Added purchase_return_items.igst_amount\n";
}
if (!hasColumn($mysqli, 'purchase_return_items', 'itc_reversed')) {
    $mysqli->query("ALTER TABLE `purchase_return_items` ADD COLUMN `itc_reversed` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `igst_amount`");
    echo "  Added purchase_return_items.itc_reversed\n";
}

echo "\n=== 8. Extending purchase_returns table ===\n";
if (!hasColumn($mysqli, 'purchase_returns', 'taxable_amount')) {
    $mysqli->query("ALTER TABLE `purchase_returns` ADD COLUMN `taxable_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`");
    echo "  Added purchase_returns.taxable_amount\n";
}
if (!hasColumn($mysqli, 'purchase_returns', 'total_gst')) {
    $mysqli->query("ALTER TABLE `purchase_returns` ADD COLUMN `total_gst` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `taxable_amount`");
    echo "  Added purchase_returns.total_gst\n";
}
if (!hasColumn($mysqli, 'purchase_returns', 'cgst_amount')) {
    $mysqli->query("ALTER TABLE `purchase_returns` ADD COLUMN `cgst_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `total_gst`");
    echo "  Added purchase_returns.cgst_amount\n";
}
if (!hasColumn($mysqli, 'purchase_returns', 'sgst_amount')) {
    $mysqli->query("ALTER TABLE `purchase_returns` ADD COLUMN `sgst_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `cgst_amount`");
    echo "  Added purchase_returns.sgst_amount\n";
}
if (!hasColumn($mysqli, 'purchase_returns', 'igst_amount')) {
    $mysqli->query("ALTER TABLE `purchase_returns` ADD COLUMN `igst_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `sgst_amount`");
    echo "  Added purchase_returns.igst_amount\n";
}
if (!hasColumn($mysqli, 'purchase_returns', 'is_interstate')) {
    $mysqli->query("ALTER TABLE `purchase_returns` ADD COLUMN `is_interstate` TINYINT(1) NOT NULL DEFAULT 0 AFTER `igst_amount`");
    echo "  Added purchase_returns.is_interstate\n";
}

echo "\n=== 9. Populating historical purchase GST splits & ITC ===\n";
// Backfill existing purchases cgst/sgst amounts and itc_amount for past data safety
$mysqli->query("UPDATE `purchase_items` SET `cgst_percent` = ROUND(gst_percent / 2, 2), `sgst_percent` = ROUND(gst_percent / 2, 2), `cgst_amount` = ROUND(gst_amount / 2, 2), `sgst_amount` = ROUND(gst_amount - ROUND(gst_amount / 2, 2), 2), `itc_amount` = gst_amount WHERE gst_amount > 0 AND cgst_amount = 0 AND igst_amount = 0");
$mysqli->query("UPDATE `purchases` SET `cgst_amount` = ROUND(total_gst / 2, 2), `sgst_amount` = ROUND(total_gst - ROUND(total_gst / 2, 2), 2), `total_itc_eligible` = total_gst WHERE total_gst > 0 AND cgst_amount = 0 AND igst_amount = 0");

echo "Migration finished successfully!\n";
