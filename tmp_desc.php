<?php \ = new PDO('mysql:host=localhost;dbname=ncg;charset=utf8mb4', 'root', ''); \ = \->query('DESCRIBE invoices'); print_r(\->fetchAll(PDO::FETCH_ASSOC)); ?>
