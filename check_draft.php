<?php
define('ENVIRONMENT', 'development');
define('BASEPATH', '1');
require 'application/config/database.php';
$conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);
if ($conn->connect_error) die("Connection failed");

$result = $conn->query("SELECT * FROM entry_drafts ORDER BY id DESC LIMIT 5");
$rows = [];
while($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
echo json_encode($rows, JSON_PRETTY_PRINT);
