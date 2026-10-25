<?php
// ============================================================
// FILE: application/controllers/Inventory.php  (UPDATED)
// Changes: adjust() now logs to stock_adjustments table,
//          movements() also shows manual adjustments.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory extends Auth_Controller {

    const PAGE_LIMIT = 500;

    public function __construct() {
        parent::__construct();
        $this->load->model('Inventory_model');
    }

    public function index() {
        $data['page_title']    = 'Inventory';
        $data['products']      = $this->Inventory_model->get_stock_status(self::PAGE_LIMIT);
        $data['low_stock']     = $this->Inventory_model->count_low_stock();
        $data['out_of_stock']  = $this->Inventory_model->count_out_of_stock();
        $data['expiring_soon'] = $this->Inventory_model->count_expiring_soon(30);
        $data['total_value']   = $this->Inventory_model->total_stock_value();
        $this->render('inventory/index', $data);
    }

    public function list_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $search = trim($this->input->get('q', TRUE));
        $stock_filter = trim($this->input->get('stock', TRUE));
        $products = $this->Inventory_model->get_stock_status(self::PAGE_LIMIT, $search, $stock_filter);
        $data['products'] = $products;
        $data['is_admin'] = ($this->session->userdata('role') === 'admin');
        $this->load->view('inventory/_rows', $data);
    }

    // Stock movements ledger for a product
    public function movements($product_id = null) {
        if (!$product_id) redirect('inventory');
        $this->load->model('Product_model');
        $loc_id = $this->active_location_id();
        $data['page_title'] = 'Stock Movements';
        $data['product']    = $this->Product_model->get_by_id($product_id);
        $data['movements']  = $this->Inventory_model->get_movements($product_id, $loc_id);
        $data['adjustments']= $this->Inventory_model->get_adjustments($product_id);
        $data['location_id']= $loc_id;
        if ($loc_id) {
            $loc_stock = $this->db->where('product_id', $product_id)->where('location_id', $loc_id)
                ->get('product_location_stock')->row();
            $data['location_stock'] = $loc_stock ? (float)$loc_stock->stock_qty : 0;
            $loc_row = $this->db->select('name')->where('id', $loc_id)->get('locations')->row();
            $data['location_name'] = $loc_row ? $loc_row->name : '';
        }
        
        $export = $this->input->get('export');
        if ($export === 'excel') {
            require_once APPPATH . '../vendor/autoload.php';
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Stock Movements');

            $sheet->setCellValue('A1', 'Stock Movements: ' . $data['product']->name);
            $sheet->mergeCells('A1:G1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $sheet->setCellValue('A3', 'Date');
            $sheet->setCellValue('B3', 'Type');
            $sheet->setCellValue('C3', 'Reference');
            $sheet->setCellValue('D3', 'Party');
            $sheet->setCellValue('E3', 'In (+)');
            $sheet->setCellValue('F3', 'Out (-)');
            $sheet->setCellValue('G3', 'Balance');
            $sheet->getStyle('A3:G3')->getFont()->setBold(true);

            $current_stock = isset($data['location_stock']) ? $data['location_stock'] : (float)$data['product']->stock_qty;
            $total_in  = array_sum(array_column($data['movements'], 'qty_in'));
            $total_out = array_sum(array_column($data['movements'], 'qty_out'));
            $ob = $current_stock - ($total_in - $total_out);

            $row = 4;
            $sheet->setCellValue('A'.$row, 'Opening Balance');
            $sheet->setCellValue('G'.$row, $ob);
            $sheet->getStyle('A'.$row.':G'.$row)->getFont()->setBold(true);
            $row++;

            $bal = $ob;
            foreach ($data['movements'] as $m) {
                $bal += ($m->qty_in - $m->qty_out);
                $sheet->setCellValue('A'.$row, date('d M Y', strtotime($m->date)));
                $sheet->setCellValue('B'.$row, $m->type);
                $sheet->setCellValue('C'.$row, $m->reference);
                $sheet->setCellValue('D'.$row, $m->party);
                $sheet->setCellValue('E'.$row, $m->qty_in > 0 ? $m->qty_in : '-');
                $sheet->setCellValue('F'.$row, $m->qty_out > 0 ? $m->qty_out : '-');
                $sheet->setCellValue('G'.$row, $bal);
                $row++;
            }
            $sheet->setCellValue('A'.$row, 'Closing Stock');
            $sheet->setCellValue('E'.$row, $total_in);
            $sheet->setCellValue('F'.$row, $total_out);
            $sheet->setCellValue('G'.$row, $current_stock);
            $sheet->getStyle('A'.$row.':G'.$row)->getFont()->setBold(true);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Stock_Movements_' . date('Ymd') . '.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } elseif ($export === 'pdf') {
            $this->load->model('Invoice_model');
            $data['company'] = $this->Invoice_model->get_company_settings();
            $this->load->library('pdf_generator');
            $html = $this->load->view('inventory/movements_pdf', $data, TRUE);
            $this->pdf_generator->generate($html, 'Stock_Movements_' . date('Ymd'), 'download', 'A4');
            exit;
        }

        $this->render('inventory/movements', $data);
    }

    // Adjust stock manually (admin only) — logs to stock_adjustments
    public function adjust($product_id = null) {
        $this->require_admin();

        if (!$product_id || $this->input->server('REQUEST_METHOD') !== 'POST') {
            redirect('inventory');
        }

        $adj_qty = (int)$this->input->post('adjust_qty', TRUE);
        $reason  = $this->input->post('reason', TRUE);

        if ($adj_qty === 0) {
            $this->session->set_flashdata('error', 'Adjustment quantity cannot be zero.');
            redirect('inventory');
        }

        $this->load->model('Product_model');
        $product = $this->Product_model->get_by_id($product_id);
        if (!$product) redirect('inventory');

        // Adjust the REAL balance of the active location (it may stay negative:
        // -5 + 3 = -2); screens show MAX(stock, 0). Going through Location_model
        // re-syncs the product total and the movement is written to the ledger —
        // writing products.stock_qty alone was lost at the next sale's re-sync.
        $this->load->model('Location_model');
        $adj_loc    = $this->active_location_id() ?: 1;
        $before_qty = $this->Location_model->get_stock($product_id, $adj_loc);
        $after_qty  = $before_qty + $adj_qty;

        $this->db->trans_start();

        if ($adj_qty > 0) $this->Location_model->increase_stock($product_id, $adj_loc, $adj_qty);
        else              $this->Location_model->decrease_stock($product_id, $adj_loc, abs($adj_qty));
        $this->db->where('id', $product_id)->update('products', ['updated_at' => date('Y-m-d H:i:s')]);
        $adj_loc_row = $this->Location_model->get_by_id($adj_loc);
        $this->Location_model->log_movement([
            'product_id'     => $product_id,
            'product_name'   => $product->name,
            'location_id'    => $adj_loc,
            'location_name'  => $adj_loc_row ? $adj_loc_row->name : '',
            'movement_type'  => $adj_qty > 0 ? 'adjustment_in' : 'adjustment_out',
            'quantity'       => abs($adj_qty),
            'balance_before' => $before_qty,
            'balance_after'  => $after_qty,
            'reference_type' => 'stock_adjustment',
            'reference_no'   => mb_substr((string)$reason, 0, 50),
            'user_id'        => $this->session->userdata('user_id'),
            'user_name'      => $this->session->userdata('name'),
            'notes'          => mb_substr((string)$reason, 0, 255),
        ]);

        // Log the adjustment
        $this->db->insert('stock_adjustments', [
            'product_id' => $product_id,
            'before_qty' => $before_qty,
            'adj_qty'    => $adj_qty,
            'after_qty'  => $after_qty,
            'reason'     => $reason,
            'created_by' => $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() !== FALSE) {
            $this->session->set_flashdata('success', 'Stock adjusted successfully.');
            redirect('inventory/movements/' . $product_id);
        } else {
            $this->session->set_flashdata('error', 'DB error during adjustment.');
            redirect('inventory');
        }
    }
    
    public function email_movements($product_id = null) {
        if (!$product_id) redirect('inventory');
        $this->load->model('Product_model');
        
        $to_email  = $this->input->post('to_email', TRUE);
        $cc_email  = $this->input->post('cc_email', TRUE);
        $subject   = $this->input->post('email_subject', TRUE) ?: 'Stock Movements';
        $message   = $this->input->post('email_message', FALSE) ?: '';
        
        if (!$to_email || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Invalid email address.');
            redirect('inventory/movements/' . $product_id);
        }
        
        $data['product']    = $this->Product_model->get_by_id($product_id);
        $data['movements']  = $this->Inventory_model->get_movements($product_id, $this->active_location_id());
        $data['adjustments']= $this->Inventory_model->get_adjustments($product_id);

        $this->load->model('Invoice_model');
        $data['company'] = $this->Invoice_model->get_company_settings();

        // Generate PDF
        $this->load->library('pdf_generator');
        $html = $this->load->view('inventory/movements_pdf', $data, TRUE);
        $pdf_filename = 'Stock_Movements_' . date('Ymd') . '.pdf';
        $pdf_string = $this->pdf_generator->as_string($html, 'Stock_Movements_' . date('Ymd'));
        
        $pdf_tmp_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $pdf_filename;
        file_put_contents($pdf_tmp_path, $pdf_string);

        $this->load->library('email');
        $this->email->clear(TRUE);
        $this->email->to($to_email);
        if ($cc_email) $this->email->cc($cc_email);
        $this->email->subject($subject);
        $this->email->message($message);
        $this->email->attach($pdf_tmp_path);

        if ($this->email->send()) {
            $this->session->set_flashdata('success', 'Email sent successfully!');
        } else {
            $this->session->set_flashdata('error', 'Failed to send email. Check error logs.');
            log_message('error', 'Email Error: ' . $this->email->print_debugger());
        }
        
        if (file_exists($pdf_tmp_path)) @unlink($pdf_tmp_path);
        
        redirect('inventory/movements/' . $product_id);
    }

    // AJAX: adjustment log for a product (used in movements view)
    public function adjustment_log($product_id = null) {
        if (!$product_id || !$this->input->is_ajax_request()) show_404();
        $log = $this->Inventory_model->get_adjustments($product_id);
        header('Content-Type: application/json');
        echo json_encode($log);
    }

    // Location-wise inventory report
    const INV_REPORT_PER_PAGE = 500;

    public function report() {
        $this->load->model(['Location_model', 'Invoice_model']);
        $page     = max(1, (int)$this->input->get('page', TRUE));
        $per_page = self::INV_REPORT_PER_PAGE;
        $data['page_title']  = 'Inventory Report';
        $data['locations']   = $this->Inventory_model->get_active_locations();
        $data['categories']  = $this->db->order_by('name')->get_where('categories', ['status' => 1])->result();
        $data['brands']      = $this->db->order_by('name')->get('brands')->result();
        $data['products']    = $this->Inventory_model->get_location_stock_report($per_page, ($page - 1) * $per_page);
        $data['company']     = $this->Invoice_model->get_company_settings();
        $data['total_rows']  = $this->Inventory_model->count_location_stock_report();
        $data['current_page'] = $page;
        $data['per_page']    = $per_page;
        $this->render('inventory/report', $data);
    }
}
