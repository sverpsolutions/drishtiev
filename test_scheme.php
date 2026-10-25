<?php
$c = new mysqli('127.0.0.1','root','','testncg');
if($c->connect_error) { echo 'Error'; exit; }
$r = $c->query('SHOW COLUMNS FROM scheme_master');
while($row = $r->fetch_assoc()) { echo $row['Field']." "; }
echo "\n";
$r = $c->query('SELECT id, discount_percent, discount_value FROM scheme_master ORDER BY id DESC LIMIT 5');
while($row = $r->fetch_assoc()) { print_r($row); }
