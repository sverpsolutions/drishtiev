<?php
$host = 'localhost';
$db   = 'testncg';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Fetch company settings
    $stmt = $pdo->query("SELECT * FROM company_settings");
    $settings = $stmt->fetchAll();
    echo "--- COMPANY SETTINGS ---\n";
    print_r($settings);
    
} catch (\PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
