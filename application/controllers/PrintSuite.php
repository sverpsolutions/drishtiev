<?php
// ============================================================
// FILE: application/controllers/PrintSuite.php
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class PrintSuite extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Location_model');
    }

    public function index() {
        $data['page_title'] = 'Print Suite';
        $this->render('print_suite/index', $data);
    }

    public function search_ajax() {
        if (!$this->input->is_ajax_request()) return;

        $type  = $this->input->post('type');
        $query = $this->input->post('query');
        $from  = $this->input->post('from_date');
        $to    = $this->input->post('to_date');

        $results = [];

        switch ($type) {
            case 'grn':
                $this->db->select('p.id, p.purchase_no as doc_no, p.invoice_date as doc_date, s.name as entity_name, p.total_amount as amount, p.status')
                         ->from('purchases p')
                         ->join('suppliers s', 's.id = p.supplier_id', 'left');
                if ($query) {
                    $this->db->group_start()
                             ->like('p.purchase_no', $query)
                             ->or_like('p.invoice_no', $query)
                             ->or_like('s.name', $query)
                             ->group_end();
                }
                if ($from) $this->db->where('p.invoice_date >=', $from);
                if ($to) $this->db->where('p.invoice_date <=', $to);
                $rows = $this->db->order_by('p.id', 'DESC')->limit(100)->get()->result();
                
                foreach($rows as $r) {
                    $results[] = [
                        'id' => $r->id,
                        'doc_no' => $r->doc_no,
                        'doc_date' => date('d M Y', strtotime($r->doc_date)),
                        'entity_name' => $r->entity_name,
                        'amount' => number_format($r->amount, 2),
                        'status' => ucfirst($r->status),
                        'print_url' => base_url("purchases/print_po/{$r->id}")
                    ];
                }
                break;

            case 'prn':
                $this->db->select('pr.id, pr.return_no as doc_no, pr.return_date as doc_date, s.name as entity_name, pr.total_amount as amount, pr.status')
                         ->from('purchase_returns pr')
                         ->join('suppliers s', 's.id = pr.supplier_id', 'left');
                if ($query) {
                    $this->db->group_start()
                             ->like('pr.return_no', $query)
                             ->or_like('s.name', $query)
                             ->group_end();
                }
                if ($from) $this->db->where('pr.return_date >=', $from);
                if ($to) $this->db->where('pr.return_date <=', $to);
                $rows = $this->db->order_by('pr.id', 'DESC')->limit(100)->get()->result();
                
                foreach($rows as $r) {
                    $results[] = [
                        'id' => $r->id,
                        'doc_no' => $r->doc_no,
                        'doc_date' => date('d M Y', strtotime($r->doc_date)),
                        'entity_name' => $r->entity_name,
                        'amount' => number_format($r->amount, 2),
                        'status' => ucfirst($r->status),
                        'print_url' => base_url("PurchaseReturns/print_debit_note/{$r->id}")
                    ];
                }
                break;

            case 'sales':
                $this->db->select('i.id, i.invoice_no as doc_no, i.invoice_date as doc_date, c.name as entity_name, i.grand_total as amount, i.payment_status as status')
                         ->from('invoices i')
                         ->join('customers c', 'c.id = i.customer_id', 'left');
                if ($query) {
                    $this->db->group_start()
                             ->like('i.invoice_no', $query)
                             ->or_like('c.name', $query)
                             ->or_like('c.phone', $query)
                             ->group_end();
                }
                if ($from) $this->db->where('DATE(i.invoice_date) >=', $from);
                if ($to) $this->db->where('DATE(i.invoice_date) <=', $to);
                $rows = $this->db->order_by('i.id', 'DESC')->limit(100)->get()->result();
                
                foreach($rows as $r) {
                    $results[] = [
                        'id' => $r->id,
                        'doc_no' => $r->doc_no,
                        'doc_date' => date('d M Y h:i A', strtotime($r->doc_date)),
                        'entity_name' => $r->entity_name ?? 'Walk-in Customer',
                        'amount' => number_format($r->amount, 2),
                        'status' => ucfirst($r->status),
                        'print_url' => base_url("billing/print_invoice/{$r->id}")
                    ];
                }
                break;

            case 'sales_return':
                $this->db->select('sr.id, sr.return_no as doc_no, sr.return_date as doc_date, c.name as entity_name, sr.total_amount as amount, sr.status')
                         ->from('sales_returns sr')
                         ->join('customers c', 'c.id = sr.customer_id', 'left');
                if ($query) {
                    $this->db->group_start()
                             ->like('sr.return_no', $query)
                             ->or_like('c.name', $query)
                             ->group_end();
                }
                if ($from) $this->db->where('sr.return_date >=', $from);
                if ($to) $this->db->where('sr.return_date <=', $to);
                $rows = $this->db->order_by('sr.id', 'DESC')->limit(100)->get()->result();
                
                foreach($rows as $r) {
                    $results[] = [
                        'id' => $r->id,
                        'doc_no' => $r->doc_no,
                        'doc_date' => date('d M Y', strtotime($r->doc_date)),
                        'entity_name' => $r->entity_name ?? 'Walk-in Customer',
                        'amount' => number_format($r->amount, 2),
                        'status' => ucfirst($r->status),
                        'print_url' => base_url("SalesReturns/print_credit_note/{$r->id}")
                    ];
                }
                break;

            case 'transfer':
                $this->db->select('t.id, t.transfer_no as doc_no, t.transfer_date as doc_date, lfrom.name as from_loc, lto.name as to_loc, t.status')
                         ->from('stock_transfers t')
                         ->join('locations lfrom', 'lfrom.id = t.from_location_id', 'left')
                         ->join('locations lto', 'lto.id = t.to_location_id', 'left');
                if ($query) {
                    $this->db->like('t.transfer_no', $query);
                }
                if ($from) $this->db->where('t.transfer_date >=', $from);
                if ($to) $this->db->where('t.transfer_date <=', $to);
                $rows = $this->db->order_by('t.id', 'DESC')->limit(100)->get()->result();
                
                foreach($rows as $r) {
                    $results[] = [
                        'id' => $r->id,
                        'doc_no' => $r->doc_no,
                        'doc_date' => date('d M Y', strtotime($r->doc_date)),
                        'entity_name' => "From: {$r->from_loc} <br> To: {$r->to_loc}",
                        'amount' => '-',
                        'status' => ucfirst($r->status),
                        'print_url' => base_url("StockTransfers/print_transfer/{$r->id}")
                    ];
                }
                break;
        }

        echo json_encode(['status'=>'success', 'data'=>$results]);
    }
}
