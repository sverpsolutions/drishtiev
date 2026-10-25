<?php
// TEMPORARY DEBUG FILE — DELETE AFTER FIXING
// Access: https://sandalhouse.sverpsolutions.com/debug_check.php

echo "<h2>Server Debug Check</h2>";
echo "<b>PHP Version:</b> " . PHP_VERSION . "<br>";
echo "<b>Server:</b> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "<br>";
echo "<b>Document Root:</b> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "<br>";
echo "<b>Script Path:</b> " . __FILE__ . "<br><br>";

// Check writable dirs
$dirs = [
    'application/logs',
    'application/cache',
    'application/cache/views',
    'assets/uploads',
];
echo "<h3>Writable Directories</h3>";
foreach ($dirs as $d) {
    $path = __DIR__ . '/' . $d;
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    echo "<b>$d</b>: " . ($exists ? "EXISTS" : "<span style='color:red'>MISSING</span>")
         . " | " . ($writable ? "<span style='color:green'>WRITABLE</span>" : "<span style='color:red'>NOT WRITABLE</span>") . "<br>";
}

// Check DB connection
echo "<h3>Database Connection</h3>";
$host = 'localhost';
$user = 'u153068796_sandalhouse';
$pass = 'Mci8yo@257';
$db   = 'u153068796_sandalhouse';
$conn = @new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo "<span style='color:red'>FAILED: " . htmlspecialchars($conn->connect_error) . "</span><br>";
} else {
    echo "<span style='color:green'>SUCCESS — Connected to $db</span><br>";

    // Check key tables
    $tables = ['users','invoices','products','customers','locations','company_settings'];
    echo "<br><b>Tables:</b><br>";
    foreach ($tables as $t) {
        $r = $conn->query("SHOW TABLES LIKE '$t'");
        echo "$t: " . ($r && $r->num_rows > 0 ? "<span style='color:green'>OK</span>" : "<span style='color:red'>MISSING</span>") . "<br>";
    }
    $conn->close();
}

// Check mod_rewrite
echo "<h3>mod_rewrite</h3>";
echo function_exists('apache_get_modules')
    ? (in_array('mod_rewrite', apache_get_modules()) ? "<span style='color:green'>ENABLED</span>" : "<span style='color:red'>DISABLED</span>")
    : "Cannot check (not Apache or function disabled)<br>";

// PHP extensions
echo "<h3>Required PHP Extensions</h3>";
$exts = ['mysqli','mbstring','json','openssl','xml','curl'];
foreach ($exts as $e) {
    echo "$e: " . (extension_loaded($e) ? "<span style='color:green'>OK</span>" : "<span style='color:red'>MISSING</span>") . "<br>";
}

// Check CI log for recent errors
echo "<h3>Recent CI Log Errors</h3>";
$logDir = __DIR__ . '/application/logs/';
if (is_dir($logDir)) {
    $files = glob($logDir . 'log-*.php');
    if ($files) {
        rsort($files);
        $latest = file_get_contents($files[0]);
        $lines = array_slice(array_filter(explode("\n", $latest)), -30);
        echo "<pre style='background:#111;color:#0f0;padding:10px;font-size:11px;max-height:300px;overflow:auto'>";
        echo htmlspecialchars(implode("\n", $lines));
        echo "</pre>";
    } else {
        echo "No log files yet.<br>";
    }
} else {
    echo "<span style='color:red'>Logs directory missing!</span><br>";
}

echo "<br><hr><small style='color:red'>DELETE this file after debugging!</small>";
