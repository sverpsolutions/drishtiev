<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Auto-detect base URL (works on local development and behind reverse proxies on the cloud)
$_base_path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

// Detect protocol, checking standard HTTPS and reverse proxy/load balancer headers
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

$_host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$config['base_url'] = $_protocol . '://' . $_host . $_base_path . '/';
$config['index_page']           = '';
$config['uri_protocol']         = 'AUTO';
$config['url_suffix']           = '';
$config['language']             = 'english';
$config['charset']              = 'UTF-8';
$config['enable_hooks']         = FALSE;
$config['subclass_prefix']      = 'MY_';
$config['composer_autoload']    = FCPATH . 'vendor/autoload.php';
$config['permitted_uri_chars']  = 'a-z 0-9~%.:_\-';
$config['allow_get_array']      = TRUE;
$config['enable_query_strings'] = FALSE;
$config['controller_trigger']   = 'c';
$config['function_trigger']     = 'm';
$config['directory_trigger']    = 'd';
$config['error_reporting']      = 1;
$config['log_threshold']        = 1;
$config['log_path']             = '';
$config['log_file_extension']   = '';
$config['log_file_permissions'] = 0644;
$config['log_date_format']      = 'Y-m-d H:i:s';
$config['cache_path']           = '';
$config['cache_query_string']   = FALSE;
$config['encryption_key']       = 'ezee_needs_secret_key_2024_delhi';
$config['sess_driver']          = 'database';
$config['sess_cookie_name']     = 'suvasan_session';
$config['sess_expiration']      = 7200;
$config['sess_save_path']       = 'ci_sessions';
$config['sess_match_ip']        = FALSE;
$config['sess_time_to_update']  = 300;
$config['sess_regenerate_destroy'] = FALSE;
$config['cookie_prefix']        = '';
$config['cookie_domain']        = '';
$config['cookie_path']          = '/';
$config['cookie_secure']        = $is_https; // TEMP-TESTING note: was hardcoded TRUE, which silently breaks sessions/CSRF over plain HTTP (e.g. local XAMPP) since browsers drop Secure cookies on non-HTTPS. Reusing $is_https keeps prod behavior identical (already HTTPS there) while fixing local dev.
$config['cookie_httponly']      = TRUE;
$config['standardize_newlines'] = FALSE;
$config['global_xss_filtering'] = FALSE;
$config['csrf_protection']      = TRUE;
$config['csrf_token_name']      = 'csrf_test_name';
$config['csrf_cookie_name']     = 'suvasan_csrf';
$config['csrf_expire']          = 7200;
$config['csrf_regenerate']      = FALSE;
$config['csrf_exclude_uris']    = [
    'auth/login',
    'login',
    'owner/login',
    'vendor-portal/login',
    'vendor-portal/send_message',
    'vendor-portal/send-message',
    'api/.*',
    'offline_sync/.*',
    'whatsapp/webhook',
    'billing/get_customer_by_phone_ajax',
    'billing/create_customer_ajax',
    'billing/hold_bill_ajax',
    'schemes/evaluate_ajax',
    'billing/get_product_ajax/.*',
    'products/search_ajax',
    'billing/get_by_barcode_ajax',
    'billing/get_plu_ajax/.*',
    'billing/get_customer_ajax/.*',
    'portal/.*_ajax',
    'webhook/razorpay',          // server-to-server, HMAC-verified in Shop::razorpay_webhook
    'portal/search_ajax',
    // Serial capture and billing scan fire from FormData posts with no token —
    // same pattern as the stock-take scan endpoints below.
    'serials/check_ajax',
    'serials/lookup_ajax',
    'serials/available_ajax',
    'serials/dispose_ajax',
    'loyalty/customer_ajax',
    'loyalty/enrol_ajax',
    'smsmanager/test_send',
    'smsmanager/send_otp',
    'smsmanager/verify_otp',
    'stock_take/lookup_ajax',
    'stock_take/scan_save_ajax',
    'stock_take/zero_uncounted_ajax',
    'stock_take/import_excel/.*',
    'products/bulk_price_update_ajax',
    'billing/verify_manager_pin_ajax',
    'customers/update_mobile_ajax',
    'customers/mobile_lookup_ajax',
    'customers/quick_add_ajax',
    'quick_search/search_ajax',
    'purchases/autosave_ajax',
    'purchases/check_draft_ajax',
    'purchases/resume_draft_ajax/.*',
    'purchases/heartbeat_ajax/.*',
    'purchases/delete_draft_ajax/.*',
    'grn/autosave_ajax',
    'grn/check_draft_ajax',
    'grn/resume_draft_ajax/.*',
    'grn/heartbeat_ajax/.*',
    'grn/delete_draft_ajax/.*',
    'store/.*_ajax',
    'store/search_ajax',
    'schemes/evaluate_ajax',
    'schemes/simulate',
    'products/next_item_code_ajax',
    'auth/check_device_status',
    'auth/get_user_location_ajax',
    'device_access/pending_count',
    'device_access/approve_ajax',
    'device_access/set_label/.*',
    'plu/check_mrp_ajax',
    'grn/get_by_barcode_ajax',
    'stock_transfers/get_by_barcode_ajax',
    'reports/device_action_ajax',
    'wholesale_billing/create_ajax',
    'wholesale_billing/generate_ewb_ajax',
    'wholesale_billing/generate_irn_ajax',
    'wholesale_billing/cancel_ewb_ajax',
    'wholesale_billing/cancel_irn_ajax',
    'wholesale_billing/verify_irn_ajax',
    'wholesale_billing/test_connection_ajax',
];
$config['compress_output']      = FALSE;
$config['time_reference']       = 'local';
$config['rewrite_short_tags']   = FALSE;
$config['reverse_proxy_ips']    = '';
