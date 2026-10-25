<?php
header('Content-Type: text/plain');

echo "--- SERVER VARIABLES ---\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'not set') . "\n";
echo "HTTP_X_FORWARDED_PROTO: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'not set') . "\n";
echo "HTTP_X_FORWARDED_SSL: " . ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? 'not set') . "\n";
echo "HTTP_FRONT_END_HTTPS: " . ($_SERVER['HTTP_FRONT_END_HTTPS'] ?? 'not set') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'not set') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'not set') . "\n";

echo "\n--- PATH DETECTION TEST ---\n";
$_sf = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? __FILE__);
$_dr = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
echo "Normalized SCRIPT_FILENAME: $_sf\n";
echo "Normalized DOCUMENT_ROOT: $_dr\n";
$pos = strpos($_sf, $_dr);
echo "strpos(sf, dr): " . ($pos === false ? 'false' : $pos) . "\n";

if ($_dr && $pos === 0) {
    $_base_path = dirname(substr($_sf, strlen($_dr)));
    $_base_path = ($_base_path === '.' || $_base_path === '/') ? '' : $_base_path;
    echo "Detected base path (original logic): '$_base_path'\n";
} else {
    $_base_path_fallback = '/ezee_needs';
    echo "Fallback triggered (original logic): '$_base_path_fallback'\n";
}

$_base_path_new = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
echo "Detected base path (new logic): '$_base_path_new'\n";

// Protocol detection
$is_https = false;
if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) === 'on' || $_SERVER['HTTPS'] == 1)) {
    $is_https = true;
} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $is_https = (strpos(strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']), 'https') !== false);
} elseif (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
    $is_https = true;
} elseif (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on') {
    $is_https = true;
}
$_protocol = $is_https ? 'https' : 'http';
echo "Protocol: $_protocol\n";

$final_original_base = $_protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_dr && $pos === 0 ? $_base_path : '/ezee_needs') . '/';
$final_new_base = $_protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_base_path_new . '/';

echo "\n--- FINAL BASE URLS ---\n";
echo "Original logic Base URL: $final_original_base\n";
echo "New logic Base URL: $final_new_base\n";
