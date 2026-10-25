<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Financial_year_model extends CI_Model {

    public function get_all()
    {
        return $this->db->order_by('start_date', 'DESC')->get('financial_years')->result();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('financial_years', ['id' => $id])->row();
    }

    public function get_by_label($label)
    {
        return $this->db->get_where('financial_years', ['label' => $label])->row();
    }

    public function create($data)
    {
        $this->db->insert('financial_years', $data);
        return $this->db->insert_id();
    }

    public function set_status($id, $status)
    {
        $this->db->where('id', $id)->update('financial_years', ['status' => $status]);
        return $this->db->affected_rows();
    }

    // Whether any document has actually been numbered under this FY yet — used to
    // decide whether it's still safe to consider the year "empty" for admin purposes.
    public function has_transactions($label)
    {
        return $this->db->where('financial_year', $label)->count_all_results('document_sequences') > 0;
    }
}
