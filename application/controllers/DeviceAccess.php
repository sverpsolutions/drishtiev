<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DeviceAccess extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        // Only superadmin can manage devices
        if ($this->session->userdata('role') !== 'superadmin') {
            $this->session->set_flashdata('error', 'Access denied.');
            redirect('dashboard');
        }
    }

    public function index() {
        $status_filter = $this->input->get('status', TRUE) ?: 'pending';
        $search        = $this->input->get('search', TRUE) ?: '';

        $this->db->select('da.*, u.name as user_name, u.username, u.role, r.name as reviewed_by_name')
                 ->from('device_authorizations da')
                 ->join('users u', 'u.id = da.user_id', 'left')
                 ->join('users r', 'r.id = da.reviewed_by', 'left');

        if ($status_filter !== 'all') $this->db->where('da.status', $status_filter);
        if ($search) $this->db->group_start()->like('u.name',$search)->or_like('u.username',$search)->or_like('da.ip_address',$search)->or_like('da.browser',$search)->group_end();

        $data['devices']        = $this->db->order_by('da.requested_at','DESC')->get()->result();
        $data['status_filter']  = $status_filter;
        $data['search']         = $search;
        $data['page_title']     = 'Device Access Management';

        // Counts
        $data['count_pending']  = $this->db->where('status','pending')->count_all_results('device_authorizations');
        $data['count_approved'] = $this->db->where('status','approved')->count_all_results('device_authorizations');
        $data['count_rejected'] = $this->db->where('status','rejected')->count_all_results('device_authorizations');

        $this->render('device_access/index', $data);
    }

    public function approve($id) {
        $this->_review($id, 'approved');
    }

    public function reject($id) {
        $this->_review($id, 'rejected');
    }

    private function _review($id, $status) {
        $device = $this->db->where('id', $id)->get('device_authorizations')->row();
        if (!$device) { redirect('device_access'); }
        $this->db->where('id', $id)->update('device_authorizations', [
            'status'      => $status,
            'reviewed_by' => $this->session->userdata('user_id'),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        $user = $this->db->where('id', $device->user_id)->get('users')->row();
        $this->log_activity('device_access', $status, ($status==='approved'?'Approved':'Rejected').' device for user "'.($user->name??'?').'" | IP: '.$device->ip_address.' | Browser: '.$device->browser, $id, $device->device_id);
        $this->session->set_flashdata('success', 'Device '.ucfirst($status).'.');
        redirect('device_access');
    }

    public function revoke($id) {
        $device = $this->db->where('id', $id)->get('device_authorizations')->row();
        if (!$device) { redirect('device_access'); }
        $this->db->where('id', $id)->update('device_authorizations', [
            'status'      => 'rejected',
            'reviewed_by' => $this->session->userdata('user_id'),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        $this->session->set_flashdata('success', 'Device revoked. User must request access again.');
        redirect('device_access');
    }

    public function delete($id) {
        $this->db->where('id', $id)->delete('device_authorizations');
        $this->session->set_flashdata('success', 'Device record deleted.');
        redirect('device_access');
    }

    public function set_label($id) {
        if (!$this->input->is_ajax_request()) show_404();
        $label = $this->input->post('label', TRUE);
        $this->db->where('id', $id)->update('device_authorizations', ['device_label' => $label]);
        echo json_encode(['ok' => true]);
    }

    // AJAX: approve from notification badge
    public function approve_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $id = (int)$this->input->post('id');
        $this->db->where('id', $id)->update('device_authorizations', [
            'status'      => 'approved',
            'reviewed_by' => $this->session->userdata('user_id'),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        echo json_encode(['ok' => true]);
    }

    // AJAX: get pending count (for navbar badge)
    public function pending_count() {
        $count = $this->db->where('status','pending')->count_all_results('device_authorizations');
        echo json_encode(['count' => $count]);
    }
}
