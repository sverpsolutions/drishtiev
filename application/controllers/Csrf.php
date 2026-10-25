<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Lightweight endpoint so JS can recover from a stale CSRF token without a
// full page reload. GET requests never verify/rotate the token (CI3 only
// checks CSRF on POST) — they just reflect whatever the current valid cookie
// is, so this is a safe way for an AJAX caller to re-sync after a 403 caused
// by the cookie changing since the page was rendered (e.g. a background
// poll's Set-Cookie landing between page load and the user's first action).
class Csrf extends Auth_Controller {
    public function refresh() {
        header('Content-Type: application/json');
        echo json_encode([
            'name' => $this->security->get_csrf_token_name(),
            'hash' => $this->security->get_csrf_hash(),
        ]);
    }
}
