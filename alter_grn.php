<?php
define('ENVIRONMENT', 'development');
define('BASEPATH', '1');
require 'application/config/database.php';
$conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->query("ALTER TABLE grn_items ADD COLUMN mrp decimal(12,2) DEFAULT 0.00");
$conn->query("ALTER TABLE grn_items ADD COLUMN selling_price decimal(12,2) DEFAULT 0.00");
echo "OK\n";
