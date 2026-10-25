<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ErrorLog_model extends CI_Model {

    private function _apply_filters($filters) {
        if (!empty($filters['severity'])) $this->db->where('severity', $filters['severity']);
        if (isset($filters['is_resolved']) && $filters['is_resolved'] !== '') $this->db->where('is_resolved', (int)$filters['is_resolved']);
        if (!empty($filters['from'])) $this->db->where('DATE(created_at) >=', $filters['from']);
        if (!empty($filters['to']))   $this->db->where('DATE(created_at) <=', $filters['to']);
        if (!empty($filters['search'])) {
            $this->db->group_start()
                     ->like('message', $filters['search'])
                     ->or_like('file', $filters['search'])
                     ->or_like('url', $filters['search'])
                     ->group_end();
        }
    }

    public function get_all($filters = [], $limit = 100, $offset = 0) {
        $this->_apply_filters($filters);
        return $this->db->order_by('id', 'DESC')->limit($limit, $offset)->get('error_logs')->result();
    }

    public function count_all($filters = []) {
        $this->_apply_filters($filters);
        return $this->db->count_all_results('error_logs');
    }

    public function get_severities() {
        return $this->db->distinct()->select('severity')->order_by('severity')->get('error_logs')->result();
    }

    public function counts_by_severity($since = null) {
        $this->db->select('severity, COUNT(*) as cnt')->group_by('severity');
        if ($since) $this->db->where('created_at >=', $since);
        return $this->db->get('error_logs')->result();
    }

    public function mark_resolved($id, $resolved = 1) {
        return $this->db->where('id', $id)->update('error_logs', ['is_resolved' => $resolved ? 1 : 0]);
    }

    public function mark_all_resolved($filters = []) {
        $this->_apply_filters($filters);
        return $this->db->update('error_logs', ['is_resolved' => 1]);
    }

    public function delete($id) {
        return $this->db->where('id', $id)->delete('error_logs');
    }

    public function delete_resolved() {
        return $this->db->where('is_resolved', 1)->delete('error_logs');
    }

    public function delete_older_than($days) {
        return $this->db->where('created_at <', date('Y-m-d H:i:s', strtotime("-{$days} days")))->delete('error_logs');
    }
}
