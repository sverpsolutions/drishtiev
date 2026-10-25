<?php
define('ENVIRONMENT', 'development');
define('BASEPATH', __DIR__ . '/system/');
require __DIR__ . '/application/config/database.php';
$cfg = $db['default'];
$c = mysqli_connect($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
if (!$c) {
    die("Connection failed: " . mysqli_connect_error() . "\n");
}
$res = $c->query("SELECT count(*) as total FROM information_schema.tables WHERE table_schema='" . $cfg['database'] . "'");
$row = $res->fetch_assoc();
echo "Connected successfully to database: " . $cfg['database'] . " (Tables: " . $row['total'] . ")\n";

