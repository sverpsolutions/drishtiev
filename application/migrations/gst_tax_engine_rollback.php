<?php
// Rollback script for GST Tax Engine Migration
// WARNING: This script drops columns and tables added by the GST Tax Engine migration.
// Existing business records (invoices, purchases, products) will NOT be lost, but new GST columns will be removed.

$mysqli = new mysqli('localhost', 'root', '', 'suvasanclothes');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

function dropColumnIfExists($m, $table, $column) {
    $res = $m->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($res && $res->num_rows > 0) {
        $m->query("ALTER TABLE `$table` DROP COLUMN `$column`");
        echo "Dropped $table.$column\n";
    }
}

echo "=== Rolling back GST Tax Engine schema ===\n";

dropColumnIfExists($mysqli, 'purchase_returns', 'is_interstate');
dropColumnIfExists($mysqli, 'purchase_returns', 'igst_amount');
dropColumnIfExists($mysqli, 'purchase_returns', 'sgst_amount');
dropColumnIfExists($mysqli, 'purchase_returns', 'cgst_amount');
dropColumnIfExists($mysqli, 'purchase_returns', 'total_gst');
dropColumnIfExists($mysqli, 'purchase_returns', 'taxable_amount');

dropColumnIfExists($mysqli, 'purchase_return_items', 'itc_reversed');
dropColumnIfExists($mysqli, 'purchase_return_items', 'igst_amount');
dropColumnIfExists($mysqli, 'purchase_return_items', 'sgst_amount');
dropColumnIfExists($mysqli, 'purchase_return_items', 'cgst_amount');
dropColumnIfExists($mysqli, 'purchase_return_items', 'gst_amount');
dropColumnIfExists($mysqli, 'purchase_return_items', 'gst_percent');
dropColumnIfExists($mysqli, 'purchase_return_items', 'taxable_amount');

dropColumnIfExists($mysqli, 'invoice_items', 'tax_rule_applied');
dropColumnIfExists($mysqli, 'invoice_items', 'tax_inclusive');

dropColumnIfExists($mysqli, 'purchases', 'total_itc_ineligible');
dropColumnIfExists($mysqli, 'purchases', 'total_itc_eligible');
dropColumnIfExists($mysqli, 'purchases', 'supplier_invoice_date');
dropColumnIfExists($mysqli, 'purchases', 'supplier_invoice_no');
dropColumnIfExists($mysqli, 'purchases', 'is_interstate');
dropColumnIfExists($mysqli, 'purchases', 'igst_amount');
dropColumnIfExists($mysqli, 'purchases', 'sgst_amount');
dropColumnIfExists($mysqli, 'purchases', 'cgst_amount');

dropColumnIfExists($mysqli, 'purchase_items', 'tax_rule_applied');
dropColumnIfExists($mysqli, 'purchase_items', 'tax_inclusive');
dropColumnIfExists($mysqli, 'purchase_items', 'itc_amount');
dropColumnIfExists($mysqli, 'purchase_items', 'itc_eligibility');
dropColumnIfExists($mysqli, 'purchase_items', 'igst_amount');
dropColumnIfExists($mysqli, 'purchase_items', 'sgst_amount');
dropColumnIfExists($mysqli, 'purchase_items', 'cgst_amount');
dropColumnIfExists($mysqli, 'purchase_items', 'igst_percent');
dropColumnIfExists($mysqli, 'purchase_items', 'sgst_percent');
dropColumnIfExists($mysqli, 'purchase_items', 'cgst_percent');

dropColumnIfExists($mysqli, 'garment_variants', 'tax_inclusive_purchase');
dropColumnIfExists($mysqli, 'garment_variants', 'tax_inclusive_sales');
dropColumnIfExists($mysqli, 'garment_variants', 'sales_gst_rate');
dropColumnIfExists($mysqli, 'garment_variants', 'purchase_gst_rate');

dropColumnIfExists($mysqli, 'products', 'sales_gst_rate');
dropColumnIfExists($mysqli, 'products', 'purchase_gst_rate');
dropColumnIfExists($mysqli, 'products', 'tax_inclusive_purchase');
dropColumnIfExists($mysqli, 'products', 'tax_inclusive_sales');
dropColumnIfExists($mysqli, 'products', 'tax_category');

$mysqli->query("DROP TABLE IF EXISTS `gst_tax_rules`");
echo "Dropped gst_tax_rules table\n";

echo "Rollback completed.\n";
