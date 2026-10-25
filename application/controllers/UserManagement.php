<?php
// FILE: application/controllers/UserManagement.php
// SuperAdmin only — manage ERP users + module permissions
defined('BASEPATH') OR exit('No direct script access allowed');

class UserManagement extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->require_superadmin();
        $this->load->model('User_model');
    }

    public function index() {
        $data['page_title'] = 'User Management';
        $data['users']      = $this->db->order_by('id','ASC')->get('users')->result();
        $this->render('user_management/index', $data);
    }

    public function add() {
        $data['page_title']   = 'Add User';
        $data['edit_user']    = null;
        $data['modules']      = ERP_MODULES;
        $data['user_perms']   = array_fill_keys(array_keys(ERP_MODULES), 1);
        $data['form_modules'] = FORM_PERMISSIONS;
        $data['user_form_perms'] = array_fill_keys(array_keys(FORM_PERMISSIONS), 1); // all on by default
        $data['locations']    = $this->db->where('status',1)->order_by('sort_order')->get('locations')->result();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('user_management/form', $data);
            return;
        }

        $name     = $this->input->post('name', TRUE);
        $username = $this->input->post('username', TRUE);
        $email    = $this->input->post('email', TRUE);
        $role     = $this->input->post('role', TRUE);
        $password = $this->input->post('password');

        if (empty($name) || empty($username) || empty($email) || empty($password)) {
            $this->session->set_flashdata('error', 'All fields are required.');
            $this->render('user_management/form', $data);
            return;
        }
        if (!in_array($role, ['owner', 'superadmin', 'manager', 'cashier'])) $role = 'cashier';

        // A blank location_id means "unrestricted — every location" elsewhere in the
        // app (see is_owner()/active_location_id()). That's correct for owner/superadmin,
        // but leaving it blank on a Manager or Cashier account would silently grant
        // that account full multi-location access with no further check — require it.
        $loc_id = (int)$this->input->post('location_id') ?: null;
        if (!in_array($role, ['owner', 'superadmin']) && !$loc_id) {
            $this->session->set_flashdata('error', 'A location is required for Manager and Cashier accounts.');
            $this->render('user_management/form', $data);
            return;
        }

        $exists = $this->db->where('username', $username)->count_all_results('users');
        if ($exists) {
            $this->session->set_flashdata('error', 'Username already taken.');
            $this->render('user_management/form', $data);
            return;
        }

        $this->db->insert('users', [
            'name'        => $name,
            'username'    => $username,
            'email'       => $email,
            'password'    => password_hash($password, PASSWORD_DEFAULT),
            'role'        => $role,
            'location_id' => $loc_id,
            'status'      => 1,
        ]);
        $new_id = $this->db->insert_id();

        // Save module + form + user-level permissions
        $this->_save_permissions($new_id);
        $this->_save_form_permissions($new_id);
        $this->_save_user_permissions($new_id);

        $this->log_activity('user_management', 'create_user', 'Created user "'.$name.'" | Username: '.$username.' | Role: '.$role, $new_id, $username);
        $this->session->set_flashdata('success', 'User created successfully.');
        redirect('user_management');
    }

    public function edit($id = null) {
        if (!$id) redirect('user_management');
        $edit_user = $this->db->where('id', $id)->get('users')->row();
        if (!$edit_user) {
            $this->session->set_flashdata('error', 'Cannot edit this user.');
            redirect('user_management');
        }

        // Load existing module permissions
        $perms_rows = $this->db->where('user_id', $id)->get('module_permissions')->result();
        $user_perms = array_fill_keys(array_keys(ERP_MODULES), 1);
        foreach ($perms_rows as $r) {
            $user_perms[$r->module_key] = (int)$r->is_enabled;
        }

        // Load existing form permissions
        $user_form_perms = array_fill_keys(array_keys(FORM_PERMISSIONS), 1);
        if ($this->db->table_exists('form_permissions')) {
            $frows = $this->db->where('user_id', $id)->get('form_permissions')->result();
            foreach ($frows as $r) {
                $user_form_perms[$r->form_key] = (int)$r->is_enabled;
            }
        }

        // Load user-level permissions (price override, due bills, etc.)
        $user_perm_row = $this->db->where('user_id', $id)->get('user_permissions')->row();
        $data['user_perm_row'] = $user_perm_row;

        $data['page_title']      = 'Edit User: ' . $edit_user->name;
        $data['edit_user']       = $edit_user;
        $data['modules']         = ERP_MODULES;
        $data['user_perms']      = $user_perms;
        $data['form_modules']    = FORM_PERMISSIONS;
        $data['user_form_perms'] = $user_form_perms;
        $data['locations']       = $this->db->where('status',1)->order_by('sort_order')->get('locations')->result();

        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            $this->render('user_management/form', $data);
            return;
        }

        $name   = $this->input->post('name', TRUE);
        $email  = $this->input->post('email', TRUE);
        $role   = $this->input->post('role', TRUE);
        $status = (int)$this->input->post('status');
        if (!in_array($role, ['owner', 'superadmin', 'manager', 'cashier'])) $role = 'cashier';

        // See add() for why this is required for non-owner/superadmin roles.
        $loc_id = (int)$this->input->post('location_id') ?: null;
        if (!in_array($role, ['owner', 'superadmin']) && !$loc_id) {
            $this->session->set_flashdata('error', 'A location is required for Manager and Cashier accounts.');
            $this->render('user_management/form', $data);
            return;
        }

        $update = ['name'=>$name,'email'=>$email,'role'=>$role,'status'=>$status,'location_id'=>$loc_id];
        $pw = $this->input->post('password');
        if (!empty($pw)) {
            $update['password'] = password_hash($pw, PASSWORD_DEFAULT);
        }
        $this->db->where('id', $id)->update('users', $update);

        // Save module + form permissions
        $this->_save_permissions($id);
        $this->_save_form_permissions($id);

        // Save user-level permissions (price override etc.)
        $this->_save_user_permissions($id);

        $pw_changed = !empty($this->input->post('password')) ? ' | Password changed' : '';
        $this->log_activity('user_management', 'edit_user', 'Updated user "'.$name.'" (ID #'.$id.') | Role: '.$role.' | Status: '.($status?'Active':'Inactive').$pw_changed, $id, $email ?? '');
        $this->session->set_flashdata('success', 'User updated.');
        redirect('user_management');
    }

    public function toggle_status($id = null) {
        if (!$id) redirect('user_management');
        $user = $this->db->where('id', $id)->get('users')->row();
        if (!$user || $user->role === 'superadmin') redirect('user_management');
        $new_status = $user->status ? 0 : 1;
        $this->db->where('id', $id)->update('users', ['status' => $new_status]);
        $this->log_activity('user_management', 'toggle_status', ($new_status ? 'Activated' : 'Deactivated').' user "'.$user->name.'" (ID #'.$id.')', $id, $user->username ?? '');
        $this->session->set_flashdata('success', 'User status updated.');
        redirect('user_management');
    }

    public function delete($id = null) {
        if (!$id) redirect('user_management');
        $user = $this->db->where('id', $id)->get('users')->row();
        if (!$user || $user->role === 'superadmin') {
            $this->session->set_flashdata('error', 'Cannot delete this user.');
        } else {
            $this->db->where('id', $id)->delete('users');
            $this->session->set_flashdata('success', 'User deleted.');
        }
        redirect('user_management');
    }

    private function _save_permissions($user_id) {
        $this->db->where('user_id', $user_id)->delete('module_permissions');
        $inserts = [];
        foreach (array_keys(ERP_MODULES) as $key) {
            $inserts[] = [
                'user_id'    => $user_id,
                'module_key' => $key,
                'is_enabled' => (int)(bool)$this->input->post('perm_' . $key),
            ];
        }
        if ($inserts) $this->db->insert_batch('module_permissions', $inserts);
    }

    private function _save_form_permissions($user_id) {
        if (!$this->db->table_exists('form_permissions')) return;
        $this->db->where('user_id', $user_id)->delete('form_permissions');
        $inserts = [];
        foreach (array_keys(FORM_PERMISSIONS) as $key) {
            $inserts[] = [
                'user_id'    => $user_id,
                'form_key'   => $key,
                'is_enabled' => (int)(bool)$this->input->post('fperm_' . $key),
            ];
        }
        if ($inserts) $this->db->insert_batch('form_permissions', $inserts);
    }

    private function _save_user_permissions($user_id) {
        if (!$this->db->table_exists('user_permissions')) return;
        $row = [];
        if ($this->db->field_exists('allow_price_override', 'user_permissions')) {
            $row['allow_price_override'] = (int)$this->input->post('allow_price_override');
        }
        if ($this->db->field_exists('allow_payment_mode_change', 'user_permissions')) {
            $row['allow_payment_mode_change'] = (int)$this->input->post('allow_payment_mode_change');
        }
        if ($this->db->field_exists('allow_due_bills', 'user_permissions')) {
            $row['allow_due_bills'] = (int)$this->input->post('allow_due_bills');
        }
        if (empty($row)) return;
        $existing = $this->db->where('user_id', $user_id)->get('user_permissions')->row();
        if ($existing) {
            $this->db->where('user_id', $user_id)->update('user_permissions', $row);
        } else {
            $row['user_id'] = $user_id;
            $this->db->insert('user_permissions', $row);
        }
    }
}
