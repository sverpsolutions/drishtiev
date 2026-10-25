<?php
header('Content-Type: text/plain');

$files = [
    'assets',
    'assets/css',
    'assets/css/bootstrap.min.css',
    'assets/css/font-awesome.min.css',
    'assets/css/bootstrap-icons.css',
    'assets/css/style.css',
    'index.php',
    '.htaccess'
];

echo "--- FILE EXISTENCE & PERMISSIONS CHECK ---\n";
foreach ($files as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $readable = is_readable($file) ? 'YES' : 'NO';
        $writable = is_writable($file) ? 'YES' : 'NO';
        $type = is_dir($file) ? 'DIR' : 'FILE';
        printf("%-35s : EXISTS (%s) | Readable: %s | Writable: %s | Perms: %o\n", $file, $type, $readable, $writable, $perms & 0777);
    } else {
        printf("%-35s : DOES NOT EXIST\n", $file);
    }
}
