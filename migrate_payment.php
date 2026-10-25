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
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$queries = [
    "ALTER TABLE user_permissions ADD COLUMN allow_payment_mode_change TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE user_permissions ADD COLUMN allow_due_bills TINYINT(1) NOT NULL DEFAULT 0",
    "CREATE TABLE IF NOT EXISTS audit_payment_mode_changes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        bill_no VARCHAR(50) NOT NULL,
        old_mode VARCHAR(50) NOT NULL,
        new_mode VARCHAR(50) NOT NULL,
        changed_by INT NOT NULL,
        reason TEXT NOT NULL,
        remarks TEXT NULL,
        terminal VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($queries as $q) {
    echo "Running: " . substr($q, 0, 50) . "...\n";
    try {
        $pdo->exec($q);
        echo "Success.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Column already exists.\n";
        } else {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
echo "Migration finished.\n";

