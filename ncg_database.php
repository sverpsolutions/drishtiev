<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

// ─── Environment-based DB config ────────────────────────────────────────────
// On localhost:  hostname=localhost, user=root, pass='', db=ezee_needs_db
// On Hostinger:  set ENVIRONMENT to 'production' in index.php
//                and fill in your Hostinger DB credentials below
// ─────────────────────────────────────────────────────────────────────────────

if (ENVIRONMENT === 'production') {
    // ★ HOSTINGER — fill these with your actual Hostinger DB details
    $db['default'] = array(
        'dsn'          => '',
        'hostname'     => 'localhost',
        'username'     => 'u153068796_ncg',
        'password'     => 'Mci8yo@257',
        'database'     => 'u153068796_ncg',
        'dbdriver'     => 'mysqli',
        'dbprefix'     => '',
        'pconnect'     => FALSE,
        'reconnect'    => TRUE,
        'db_debug'     => FALSE,
        'cache_on'     => FALSE,
        'cachedir'     => '',
        'char_set'     => 'utf8',
        'dbcollat'     => 'utf8_general_ci',
        'swap_pre'     => '',
        'encrypt'      => FALSE,
        'compress'     => FALSE,
        'stricton'     => FALSE,
        'failover'     => array(),
        'save_queries' => FALSE,
    );
} else {
    // ★ LOCAL (XAMPP)
    $db['default'] = array(
        'dsn'          => '',
        'hostname'     => 'localhost',
        'username'     => 'root',
        'password'     => '',
        'database'     => 'suvasanclothes',
        'dbdriver'     => 'mysqli',
        'dbprefix'     => '',
        'pconnect'     => FALSE,
        'reconnect'    => TRUE,
        'db_debug'     => (ENVIRONMENT !== 'production'),
        'cache_on'     => FALSE,
        'cachedir'     => '',
        'char_set'     => 'utf8',
        'dbcollat'     => 'utf8_general_ci',
        'swap_pre'     => '',
        'encrypt'      => FALSE,
        'compress'     => FALSE,
        'stricton'     => FALSE,
        'failover'     => array(),
        'save_queries' => TRUE,
    );
}
