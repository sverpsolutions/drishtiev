<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Extends CI's exception/error handler so every PHP error, warning, and
 * uncaught exception also gets saved to the `error_logs` table (in addition
 * to CI's normal application/logs/log-*.php file), so they can be reviewed
 * from Admin Options -> Error Logs instead of only via server file access.
 *
 * CI3 calls log_exception() from two places (system/core/Common.php):
 *   - _error_handler()      for PHP errors/warnings (severity = E_* constant)
 *   - _exception_handler()  for uncaught exceptions (severity = 'error' string)
 * Both pass only $message/$filepath/$line — no stack trace is available here.
 */
class MY_Exceptions extends CI_Exceptions {

    // Severities worth persisting to the DB — real errors/exceptions, not
    // routine notices/deprecations which would just create noise.
    private static $persist_severities = [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_WARNING, E_USER_WARNING];

    // Re-entrancy guard: if saving to the DB itself triggers a PHP warning/error,
    // don't recurse back into this handler.
    private static $logging = false;

    public function log_exception($severity, $message, $filepath, $line) {
        parent::log_exception($severity, $message, $filepath, $line);

        $is_exception = ($severity === 'error');
        if (!$is_exception && !in_array($severity, self::$persist_severities, TRUE)) {
            return;
        }

        if (self::$logging) return;
        self::$logging = true;
        $this->_save_to_db($severity, $message, $filepath, $line);
        self::$logging = false;
    }

    private function _save_to_db($severity, $message, $filepath, $line) {
        try {
            $CI =& get_instance();
            if (!isset($CI->db)) {
                $CI->load->database();
            }

            $severity_label = isset($this->levels[$severity]) ? $this->levels[$severity] : (string)$severity;

            $is_cli = (php_sapi_name() === 'cli');
            $url    = $is_cli ? ('CLI: ' . implode(' ', $_SERVER['argv'] ?? [])) : ($_SERVER['REQUEST_URI'] ?? '');
            $method = $is_cli ? 'CLI' : ($_SERVER['REQUEST_METHOD'] ?? '');

            $user_id = null;
            $user_name = null;
            if (isset($CI->session)) {
                $user_id   = $CI->session->userdata('user_id') ?: null;
                $user_name = $CI->session->userdata('name') ?: null;
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            if (isset($CI->input) && method_exists($CI->input, 'ip_address')) {
                $ip = $CI->input->ip_address();
            }

            $CI->db->insert('error_logs', [
                'severity'   => $severity_label,
                'message'    => mb_substr((string)$message, 0, 5000),
                'file'       => $filepath ? mb_substr((string)$filepath, 0, 500) : null,
                'line'       => $line !== '' ? (int)$line : null,
                'url'        => mb_substr((string)$url, 0, 500),
                'method'     => $method,
                'user_id'    => $user_id,
                'user_name'  => $user_name,
                'ip_address' => $ip,
            ]);
        } catch (\Throwable $e) {
            // Never let the logger itself take down the app.
            log_message('error', 'MY_Exceptions failed to persist error to DB: ' . $e->getMessage());
        }
    }
}
