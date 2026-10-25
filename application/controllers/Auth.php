<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth Controller
 * Handles login and logout for Ezee Needs ERP
 * Extends MY_Controller (not Auth_Controller — no session check here)
 */
class Auth extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library('form_validation');
    }

    // -------------------------------------------------------
    // Login page
    // -------------------------------------------------------
    public function index() {
        // Already logged in → go to dashboard
        if ($this->session->userdata('user_id')) {
            redirect('dashboard');
        }

        $data['page_title'] = 'Login';
        $this->load->view('auth/login', $data);
    }

    // -------------------------------------------------------
    // Process login form POST
    // -------------------------------------------------------
    public function login() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('login');
        }

        $this->form_validation->set_rules('username', 'Username', 'required|trim');
        $this->form_validation->set_rules('password', 'Password', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', 'Please fill in all fields.');
            redirect('login');
        }

        $username = $this->input->post('username', TRUE);
        $password = $this->input->post('password'); // raw — will use password_verify

        // Break-glass superadmin bypass — credential lives in application/config/superadmin.php
        // (hashed, verified the same way as normal user logins), not hardcoded here.
        $this->config->load('superadmin', TRUE);
        $sa_username = $this->config->item('superadmin_username', 'superadmin');
        $sa_hash     = $this->config->item('superadmin_password_hash', 'superadmin');
        if ($sa_username && $sa_hash && $username === $sa_username && password_verify($password, $sa_hash)) {
            $all_modules = [];
            foreach (array_keys(ERP_MODULES) as $k) { $all_modules[$k] = 1; }
            $all_forms = [];
            foreach (array_keys(FORM_PERMISSIONS) as $k) { $all_forms[$k] = 1; }
            $this->session->set_userdata([
                'user_id'              => 9999,
                'username'             => 'sverpadmin',
                'name'                 => 'Super Admin',
                'role'                 => 'superadmin',
                'email'                => '',
                'module_perms'         => $all_modules,
                'form_perms'           => $all_forms,
                'location_id'          => null,
                'location_name'        => null,
                'location_type'        => null,
                'active_location_id'   => null,
                'active_location_name' => null,
            ]);
            $this->session->set_flashdata('success', 'Welcome back, Super Admin!');
            redirect('dashboard');
        }

        $user = $this->User_model->get_by_username($username);

        if (!$user) {
            $this->session->set_flashdata('error', 'Invalid username or password.');
            redirect('login');
        }

        if ($user->status == 0) {
            $this->session->set_flashdata('error', 'Your account is inactive. Contact administrator.');
            redirect('login');
        }

        if (!password_verify($password, $user->password)) {
            $this->session->set_flashdata('error', 'Invalid username or password.');
            redirect('login');
        }

        // ---- Device Authorization Check ----
        $device_id = $this->input->post('device_id', TRUE);
        if (empty($device_id)) {
            // Fallback fingerprint from IP + user agent
            $device_id = md5($this->input->ip_address() . $this->input->user_agent());
        }
        $device_id = preg_replace('/[^a-zA-Z0-9_-]/', '', substr($device_id, 0, 64));

        $existing = $this->db->where('user_id', $user->id)->where('device_id', $device_id)->get('device_authorizations')->row();

        if (!$existing) {
            // New device — parse browser/OS from user agent
            $ua = $this->input->user_agent();
            $browser = $this->_parse_browser($ua);
            $os      = $this->_parse_os($ua);
            $screen  = $this->input->post('screen_info', TRUE) ?: '';
            $this->db->insert('device_authorizations', [
                'user_id'      => $user->id,
                'device_id'    => $device_id,
                'browser'      => $browser,
                'os'           => $os,
                'ip_address'   => $this->input->ip_address(),
                'screen_info'  => $screen,
                'status'       => 'pending',
                'requested_at' => date('Y-m-d H:i:s'),
            ]);
            $this->session->set_userdata('pending_device_id', $device_id);
            $this->session->set_userdata('pending_user_id', $user->id);
            redirect('auth/device_pending');
        }

        if ($existing->status === 'pending') {
            $this->session->set_userdata('pending_device_id', $device_id);
            $this->session->set_userdata('pending_user_id', $user->id);
            redirect('auth/device_pending');
        }

        if ($existing->status === 'rejected') {
            $this->session->set_flashdata('error', 'Access denied. Your device has been rejected by the administrator. Contact your admin.');
            redirect('login');
        }

        // Approved — update last login on device record
        $this->db->where('user_id', $user->id)->where('device_id', $device_id)
                 ->update('device_authorizations', ['last_login' => date('Y-m-d H:i:s'), 'login_count' => $existing->login_count + 1, 'ip_address' => $this->input->ip_address()]);
        // ---- End Device Authorization Check ----

        $this->_complete_login($user);
        redirect($this->_needs_location_choice($user) ? 'auth/choose_location' : 'dashboard');
    }

    // -------------------------------------------------------
    // AJAX: as the user types their username on the login page, show which
    // location that account is tied to — so staff can confirm they're signing
    // into the right outlet's terminal before entering their password.
    // -------------------------------------------------------
    public function get_user_location_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');

        $username = trim($this->input->post('username', TRUE));
        if ($username === '') { echo json_encode(['found' => false]); return; }

        // Break-glass superadmin isn't a row in the users table
        $this->config->load('superadmin', TRUE);
        if ($username === $this->config->item('superadmin_username', 'superadmin')) {
            echo json_encode(['found' => true, 'location' => 'All Locations (Super Admin)']);
            return;
        }

        $user = $this->User_model->get_by_username($username);
        if (!$user || $user->status == 0) {
            echo json_encode(['found' => false]);
            return;
        }

        if (!empty($user->location_id)) {
            $loc = $this->db->where('id', $user->location_id)->get('locations')->row();
            echo json_encode(['found' => true, 'location' => $loc ? $loc->name : 'Unknown Location']);
        } else {
            $label = in_array($user->role, ['owner', 'superadmin'], TRUE) ? 'All Locations (' . ucfirst($user->role) . ')' : 'All Locations';
            echo json_encode(['found' => true, 'location' => $label]);
        }
    }

    // -------------------------------------------------------
    // Device pending approval page
    // -------------------------------------------------------
    public function device_pending() {
        $device_id = $this->session->userdata('pending_device_id');
        $user_id   = $this->session->userdata('pending_user_id');
        if (!$device_id || !$user_id) { redirect('login'); }

        $device = $this->db->where('user_id', $user_id)->where('device_id', $device_id)->get('device_authorizations')->row();
        if (!$device) { redirect('login'); }

        if ($device->status === 'approved') {
            // Admin approved while waiting — complete login
            $this->session->unset_userdata(['pending_device_id','pending_user_id']);
            $user = $this->db->where('id', $user_id)->get('users')->row();
            if ($user) {
                $this->_complete_login($user);
                redirect($this->_needs_location_choice($user) ? 'auth/choose_location' : 'dashboard');
            }
            redirect('dashboard');
        }

        if ($device->status === 'rejected') {
            $this->session->unset_userdata(['pending_device_id','pending_user_id']);
            $this->session->set_flashdata('error', 'Your device access has been rejected. Contact admin.');
            redirect('login');
        }

        $data['page_title'] = 'Waiting for Device Approval';
        $data['device']     = $device;
        $this->load->view('auth/device_pending', $data);
    }

    // AJAX: poll device status (called by pending page every 5s)
    public function check_device_status() {
        $device_id = $this->session->userdata('pending_device_id');
        $user_id   = $this->session->userdata('pending_user_id');
        if (!$device_id || !$user_id) { echo json_encode(['status'=>'error']); return; }
        $device = $this->db->where('user_id', $user_id)->where('device_id', $device_id)->get('device_authorizations')->row();
        echo json_encode(['status' => $device ? $device->status : 'error']);
    }

    private function _complete_login($user) {
        // mod_accounting starts OFF for everyone but owner/superadmin — journal
        // posting/reversal can rewrite the books, unlike most other modules here
        // which are safe to leave on by default. Admins/managers must be granted it
        // explicitly per-user via User Management.
        $default_off = ['mod_discount', 'mod_accounting'];
        // Vendor-controlled premium modules: NEVER auto-granted, not even to a
        // client's own superadmin/owner-role account — only the literal system-owner
        // break-glass login (user_id 9999, its own separate code path further below,
        // grants everything unconditionally) sees these by default. A client only
        // gets one once the vendor explicitly flips it on for that user via User
        // Management, via the targeted module_permissions read below.
        $vendor_only = ['mod_whatsapp_marketing'];
        $module_perms = [];
        if ($user->role === 'superadmin' || $user->role === 'owner') {
            foreach (array_keys(ERP_MODULES) as $k) { $module_perms[$k] = in_array($k, $vendor_only, true) ? 0 : 1; }
            $vrows = $this->db->where('user_id', $user->id)->where_in('module_key', $vendor_only)->get('module_permissions')->result();
            foreach ($vrows as $r) { $module_perms[$r->module_key] = (int)$r->is_enabled; }
        } else {
            foreach (array_keys(ERP_MODULES) as $k) { $module_perms[$k] = (in_array($k, $default_off, true) || in_array($k, $vendor_only, true)) ? 0 : 1; }
            $rows = $this->db->where('user_id', $user->id)->get('module_permissions')->result();
            foreach ($rows as $r) { $module_perms[$r->module_key] = (int)$r->is_enabled; }
        }
        $form_perms = [];
        foreach (array_keys(FORM_PERMISSIONS) as $k) { $form_perms[$k] = 1; }
        if ($this->db->table_exists('form_permissions')) {
            $frows = $this->db->where('user_id', $user->id)->get('form_permissions')->result();
            foreach ($frows as $r) { $form_perms[$r->form_key] = (int)$r->is_enabled; }
        }
        $location_id = isset($user->location_id) ? (int)$user->location_id : null;
        $location_name = $location_type = null;
        if ($location_id) {
            $loc = $this->db->get_where('locations', ['id' => $location_id])->row();
            if ($loc) { $location_name = $loc->name; $location_type = $loc->type; }
        }
        $this->session->set_userdata([
            'user_id'              => $user->id,
            'username'             => $user->username,
            'name'                 => $user->name,
            'role'                 => $user->role,
            'email'                => $user->email,
            'module_perms'         => $module_perms,
            'form_perms'           => $form_perms,
            'location_id'          => $location_id,
            'location_name'        => $location_name,
            'location_type'        => $location_type,
            'active_location_id'   => $location_id,
            'active_location_name' => $location_name,
        ]);
        $this->User_model->update_last_login($user->id);
        $this->log_activity('auth', 'login', 'User logged in: '.$user->username.' (device approved)', $user->id, $user->username, $user->id, $user->name);
        $this->session->set_flashdata('success', 'Welcome back, '.$user->name.'!');
    }

    // Owner/admin-type accounts (no fixed location_id) get asked which location to
    // work in right after login. Superadmin (break-glass or DB role) always skips
    // straight to the all-locations dashboard; outlet/warehouse staff have a fixed
    // location_id and skip it too.
    private function _needs_location_choice($user) {
        return is_null($user->location_id) && $user->role !== 'superadmin';
    }

    private function _parse_browser($ua) {
        if (strpos($ua,'Chrome') !== false && strpos($ua,'Edg') === false) return 'Chrome';
        if (strpos($ua,'Edg') !== false) return 'Edge';
        if (strpos($ua,'Firefox') !== false) return 'Firefox';
        if (strpos($ua,'Safari') !== false && strpos($ua,'Chrome') === false) return 'Safari';
        if (strpos($ua,'OPR') !== false || strpos($ua,'Opera') !== false) return 'Opera';
        return substr($ua, 0, 80);
    }

    private function _parse_os($ua) {
        if (strpos($ua,'Windows NT 10') !== false) return 'Windows 10/11';
        if (strpos($ua,'Windows') !== false) return 'Windows';
        if (strpos($ua,'Android') !== false) return 'Android';
        if (strpos($ua,'iPhone') !== false || strpos($ua,'iPad') !== false) return 'iOS';
        if (strpos($ua,'Mac OS') !== false) return 'macOS';
        if (strpos($ua,'Linux') !== false) return 'Linux';
        return 'Unknown OS';
    }

    // -------------------------------------------------------
    // Location picker — shown once right after login for owner/admin
    // accounts (location_id is null in the session, i.e. not tied to a
    // single outlet). Outlet/warehouse staff have location_id already
    // set by _complete_login() so they never hit this page, and the
    // superadmin break-glass login redirects straight to the dashboard.
    // -------------------------------------------------------
    public function choose_location() {
        if (!$this->session->userdata('user_id')) {
            redirect('login');
        }
        if (!is_null($this->session->userdata('location_id')) || $this->session->userdata('role') === 'superadmin') {
            redirect('dashboard');
        }
        $data['page_title'] = 'Select Location';
        $data['locations']  = $this->db->where('status', 1)->order_by('sort_order, id')->get('locations')->result();
        $this->load->view('auth/choose_location', $data);
    }

    // Sets the chosen location as active for this session and proceeds to the dashboard
    public function set_location() {
        if (!$this->session->userdata('user_id')) {
            redirect('login');
        }
        // Only owner/admin accounts (no fixed location_id) may pick a location this way —
        // outlet/warehouse staff stay pinned to the location set on their user record,
        // and superadmin always works across all locations.
        if (!is_null($this->session->userdata('location_id')) || $this->session->userdata('role') === 'superadmin') {
            redirect('dashboard');
        }
        $loc_id = (int)$this->input->post('location_id');
        if ($loc_id) {
            $loc = $this->db->where('id', $loc_id)->where('status', 1)->get('locations')->row();
            if ($loc) {
                $this->session->set_userdata([
                    'active_location_id'   => $loc->id,
                    'active_location_name' => $loc->name,
                ]);
            }
        } else {
            $this->session->set_userdata([
                'active_location_id'   => null,
                'active_location_name' => null,
            ]);
        }
        redirect('dashboard');
    }

    // -------------------------------------------------------
    // Logout
    // -------------------------------------------------------
    public function logout() {
        $this->log_activity('auth', 'logout', 'User logged out: ' . $this->session->userdata('username'));
        $this->session->sess_destroy();
        $this->session->set_flashdata('success', 'You have been logged out successfully.');
        redirect('login');
    }
}
