<?php
// ============================================================
// FILE: application/models/Army_company_model.php
// Army Quotation Management — Company Master
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Army_company_model extends CI_Model {

    public function get_all() {
        return $this->db->order_by('company_name', 'ASC')->get('Army_CompanyMaster')->result();
    }

    public function get_active() {
        return $this->db->where('is_active', 1)->order_by('company_name', 'ASC')->get('Army_CompanyMaster')->result();
    }

    public function get_by_id($id) {
        return $this->db->where('id', $id)->get('Army_CompanyMaster')->row();
    }

    public function create($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('Army_CompanyMaster', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update('Army_CompanyMaster', $data);
    }

    public function set_active($id, $active) {
        return $this->db->where('id', $id)->update('Army_CompanyMaster', [
            'is_active'  => $active ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function count_quotations_for_company($id) {
        return $this->db->where('company_id', $id)->count_all_results('Army_Quotation');
    }

    public function delete($id) {
        return $this->db->where('id', $id)->delete('Army_CompanyMaster');
    }
}
