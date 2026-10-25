<?php
// ============================================================
// FILE: application/controllers/Schemes.php  (REBUILT)
// Complete FMCG Scheme Management
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Schemes extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Scheme_model');
        $this->load->model('Product_model');
        $this->load->model('Customer_model');
        $this->load->library('SchemeEngine');
        $this->load->library('form_validation');
    }

    public function index() {
        $filters = [
            'is_active' => $this->input->get('active', TRUE),
            'type_id'   => $this->input->get('type_id', TRUE),
            'search'    => $this->input->get('q', TRUE),
        ];
        if ($filters['is_active'] === '') unset($filters['is_active']);

        $data['page_title'] = 'Scheme Management';
        $data['schemes']    = $this->Scheme_model->get_all($filters);
        $data['types']      = $this->Scheme_model->get_types();
        $data['filters']    = $filters;
        $this->render('schemes/index', $data);
    }

    public function create() {
        $data['page_title'] = 'Create New Scheme';
        $data['types']      = $this->Scheme_model->get_types();
        $data['products']   = $this->Product_model->get_all_for_billing();
        $data['customers']  = $this->Customer_model->get_all_for_select();
        $data['brands']     = $this->db->order_by('name')->get('brands')->result();
        $data['categories'] = $this->db->order_by('name')->get('categories')->result();
        $this->render('schemes/create', $data);
    }

    /**
     * Guards the "Buy N — Cheapest Free" type, which needs a group size and a
     * free count to mean anything. Returns an error string, or NULL when fine.
     * Every other scheme type is left alone.
     */
    private function _check_cheapest_free() {
        $type = $this->db->select('code')->where('id', (int)$this->input->post('scheme_type_id', TRUE))
                         ->get('scheme_types')->row();
        if (!$type || $type->code !== 'CHEAPEST_FREE') return null;

        $group = (int)$this->input->post('min_qty', TRUE);
        $free  = (int)$this->input->post('free_qty', TRUE);

        if ($group < 2) {
            return 'For a "Cheapest Free" scheme, set <strong>Minimum Quantity</strong> to the number of '
                 . 'items the customer must pick up — 3 for a 2+1 offer. It cannot be 0 or 1.';
        }
        if ($free < 1) {
            return 'For a "Cheapest Free" scheme, set <strong>Free Qty</strong> to how many of the '
                 . 'cheapest items are free — 1 for a 2+1 offer.';
        }
        if ($free >= $group) {
            return 'Free Qty (' . $free . ') must be less than Minimum Quantity (' . $group
                 . '), otherwise every item would be free. For 2+1 use Minimum Quantity 3, Free Qty 1.';
        }
        return null;
    }

    public function save() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('schemes');

        $this->form_validation->set_rules('name',           'Scheme Name', 'required|trim|max_length[150]');
        $this->form_validation->set_rules('scheme_type_id', 'Scheme Type', 'required|numeric');
        $this->form_validation->set_rules('start_date',     'Start Date',  'required');
        $this->form_validation->set_rules('end_date',       'End Date',    'required');

        if (!$this->form_validation->run()) {
            $this->session->set_flashdata('error', validation_errors(' ', ' | '));
            redirect('schemes/create');
        }

        // A "cheapest free" scheme is meaningless without a group size, and the
        // engine would silently award nothing. Refuse it at save time instead of
        // letting someone create a scheme that looks Active but never fires.
        if ($msg = $this->_check_cheapest_free()) {
            $this->session->set_flashdata('error', $msg);
            redirect('schemes/create');
        }

        $this->db->trans_start();

        $header = [
            'name'                    => $this->input->post('name', TRUE),
            'scheme_type_id'          => (int)$this->input->post('scheme_type_id', TRUE),
            'apply_on'                => $this->input->post('apply_on', TRUE) ?: 'item',
            'priority'                => (int)$this->input->post('priority', TRUE) ?: 5,
            'start_date'              => $this->input->post('start_date', TRUE),
            'end_date'                => $this->input->post('end_date', TRUE),
            'is_active'               => (int)$this->input->post('is_active', TRUE),
            'applicable_parties'      => $this->input->post('applicable_parties', TRUE) ?: 'all',
            'min_qty'                 => (float)$this->input->post('min_qty', TRUE),
            'min_qty_uom'             => $this->input->post('min_qty_uom', TRUE) ?: 'pcs',
            'min_value'               => (float)$this->input->post('min_value', TRUE),
            'discount_percent'        => (float)$this->input->post('discount_percent', TRUE),
            'discount_percent2'       => (float)$this->input->post('discount_percent2', TRUE),
            'discount_value'          => (float)$this->input->post('discount_value', TRUE),
            'free_qty'                => (float)$this->input->post('free_qty', TRUE),
            'free_item_id'            => (int)$this->input->post('free_item_id', TRUE) ?: null,
            'is_recursive'            => (int)$this->input->post('is_recursive', TRUE),
            'is_combination_allowed'  => (int)$this->input->post('is_combination_allowed', TRUE),
            'max_benefit_per_invoice' => (float)$this->input->post('max_benefit_per_invoice', TRUE),
            'target_amount'           => (float)$this->input->post('target_amount', TRUE),
            'target_period'           => $this->input->post('target_period', TRUE) ?: 'monthly',
            'credit_note_percent'     => (float)$this->input->post('credit_note_percent', TRUE),
            'credit_note_timing'      => $this->input->post('credit_note_timing', TRUE) ?: 'immediate',
            'carton_size'             => (int)$this->input->post('carton_size', TRUE) ?: 1,
            'start_time'              => $this->input->post('start_time', TRUE) ?: null,
            'end_time'                => $this->input->post('end_time', TRUE) ?: null,
            'expiry_days_trigger'     => $this->input->post('expiry_days_trigger', TRUE) !== '' ? (int)$this->input->post('expiry_days_trigger', TRUE) : null,
            'notes'                   => $this->input->post('notes', TRUE),
            'created_by'              => $this->session->userdata('user_id'),
        ];

        $scheme_id = $this->Scheme_model->create($header);

        // Slabs
        $slabs_raw = $this->input->post('slabs', TRUE) ?? [];
        $slabs = [];
        foreach ($slabs_raw as $s) {
            if ((float)($s['min_qty']??0) > 0 || (float)($s['min_value']??0) > 0) {
                $slabs[] = ['min_qty'=>(float)($s['min_qty']??0),'max_qty'=>(float)($s['max_qty']??0),'min_value'=>(float)($s['min_value']??0),'max_value'=>(float)($s['max_value']??0),'free_qty'=>(float)($s['free_qty']??0),'discount_percent'=>(float)($s['disc_pct']??0),'discount_percent2'=>(float)($s['disc_pct2']??0),'discount_value'=>(float)($s['disc_val']??0),'free_item_id'=>(int)($s['free_item_id']??0)?:null];
            }
        }
        if (!empty($slabs)) $this->Scheme_model->save_slabs($scheme_id, $slabs);

        // Item map
        $product_ids = $this->input->post('product_ids', TRUE) ?? [];
        $brand_id    = (int)$this->input->post('brand_id', TRUE) ?: null;
        $category_id = (int)$this->input->post('category_id', TRUE) ?: null;
        if ($product_ids || $brand_id || $category_id) $this->Scheme_model->save_item_map($scheme_id, (array)$product_ids, $brand_id, $category_id);

        // Party map
        $customer_ids = $this->input->post('customer_ids', TRUE) ?? [];
        if (!empty($customer_ids)) $this->Scheme_model->save_party_map($scheme_id, (array)$customer_ids);

        // Free items
        $fi_pids = $this->input->post('fi_product_id', TRUE) ?? [];
        $fi_qtys = $this->input->post('fi_qty', TRUE) ?? [];
        $fi_uoms = $this->input->post('fi_uom', TRUE) ?? [];
        $free_items = [];
        foreach ($fi_pids as $i => $pid) {
            if ($pid) $free_items[] = ['free_product_id'=>(int)$pid,'free_qty'=>(float)($fi_qtys[$i]??1),'free_uom'=>$fi_uoms[$i]??'pcs'];
        }
        if (!empty($free_items)) $this->Scheme_model->save_free_items($scheme_id, $free_items);

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->session->set_flashdata('success', 'Scheme created successfully!');
            redirect('schemes/view/' . $scheme_id);
        } else {
            $err = $this->db->error();
            $this->session->set_flashdata('error', 'Save failed. ' . ($err['message'] ?? ''));
            redirect('schemes/create');
        }
    }

    public function view($id) {
        $scheme = $this->Scheme_model->get_by_id($id);
        if (!$scheme) show_404();
        $data['page_title'] = 'Scheme: ' . $scheme->name;
        $data['scheme']     = $scheme;
        $data['log']        = $this->Scheme_model->get_log(['scheme_id' => $id, 'limit' => 20]);
        $this->render('schemes/view', $data);
    }

    public function edit($id) {
        $scheme = $this->Scheme_model->get_by_id($id);
        if (!$scheme) { $this->session->set_flashdata('error', 'Scheme not found.'); redirect('schemes'); }

        $data['page_title'] = 'Edit Scheme: ' . $scheme->name;
        $data['scheme']     = $scheme;
        $data['types']      = $this->Scheme_model->get_types();
        $data['products']   = $this->Product_model->get_all_for_billing();
        $data['customers']  = $this->Customer_model->get_all_for_select();
        $data['brands']     = $this->db->order_by('name')->get('brands')->result();
        $data['categories'] = $this->db->order_by('name')->get('categories')->result();
        $this->render('schemes/edit', $data);
    }

    public function update($id) {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') redirect('schemes/edit/' . $id);

        $this->form_validation->set_rules('name',           'Scheme Name', 'required|trim|max_length[150]');
        $this->form_validation->set_rules('scheme_type_id', 'Scheme Type', 'required|numeric');
        $this->form_validation->set_rules('start_date',     'Start Date',  'required');
        $this->form_validation->set_rules('end_date',       'End Date',    'required');

        if (!$this->form_validation->run()) {
            $this->session->set_flashdata('error', validation_errors(' ', ' | '));
            redirect('schemes/edit/' . $id);
        }

        if ($msg = $this->_check_cheapest_free()) {
            $this->session->set_flashdata('error', $msg);
            redirect('schemes/edit/' . $id);
        }

        $this->db->trans_start();

        $header = [
            'name'                    => $this->input->post('name', TRUE),
            'scheme_type_id'          => (int)$this->input->post('scheme_type_id', TRUE),
            'apply_on'                => $this->input->post('apply_on', TRUE) ?: 'item',
            'priority'                => (int)$this->input->post('priority', TRUE) ?: 5,
            'start_date'              => $this->input->post('start_date', TRUE),
            'end_date'                => $this->input->post('end_date', TRUE),
            'is_active'               => (int)$this->input->post('is_active', TRUE),
            'applicable_parties'      => $this->input->post('applicable_parties', TRUE) ?: 'all',
            'min_qty'                 => (float)$this->input->post('min_qty', TRUE),
            'min_qty_uom'             => $this->input->post('min_qty_uom', TRUE) ?: 'pcs',
            'min_value'               => (float)$this->input->post('min_value', TRUE),
            'discount_percent'        => (float)$this->input->post('discount_percent', TRUE),
            'discount_percent2'       => (float)$this->input->post('discount_percent2', TRUE),
            'discount_value'          => (float)$this->input->post('discount_value', TRUE),
            'free_qty'                => (float)$this->input->post('free_qty', TRUE),
            'free_item_id'            => (int)$this->input->post('free_item_id', TRUE) ?: null,
            'is_recursive'            => (int)$this->input->post('is_recursive', TRUE),
            'is_combination_allowed'  => (int)$this->input->post('is_combination_allowed', TRUE),
            'max_benefit_per_invoice' => (float)$this->input->post('max_benefit_per_invoice', TRUE),
            'target_amount'           => (float)$this->input->post('target_amount', TRUE),
            'target_period'           => $this->input->post('target_period', TRUE) ?: 'monthly',
            'credit_note_percent'     => (float)$this->input->post('credit_note_percent', TRUE),
            'credit_note_timing'      => $this->input->post('credit_note_timing', TRUE) ?: 'immediate',
            'carton_size'             => (int)$this->input->post('carton_size', TRUE) ?: 1,
            'start_time'              => $this->input->post('start_time', TRUE) ?: null,
            'end_time'                => $this->input->post('end_time', TRUE) ?: null,
            'expiry_days_trigger'     => $this->input->post('expiry_days_trigger', TRUE) !== '' ? (int)$this->input->post('expiry_days_trigger', TRUE) : null,
            'notes'                   => $this->input->post('notes', TRUE),
        ];

        $this->Scheme_model->update($id, $header);

        $slabs_raw = $this->input->post('slabs', TRUE) ?? [];
        $slabs = [];
        foreach ($slabs_raw as $s) {
            if ((float)($s['min_qty']??0) > 0 || (float)($s['min_value']??0) > 0) {
                $slabs[] = ['min_qty'=>(float)($s['min_qty']??0),'max_qty'=>(float)($s['max_qty']??0),'min_value'=>(float)($s['min_value']??0),'max_value'=>(float)($s['max_value']??0),'free_qty'=>(float)($s['free_qty']??0),'discount_percent'=>(float)($s['disc_pct']??0),'discount_percent2'=>(float)($s['disc_pct2']??0),'discount_value'=>(float)($s['disc_val']??0),'free_item_id'=>(int)($s['free_item_id']??0)?:null];
            }
        }
        $this->Scheme_model->save_slabs($id, $slabs);

        $product_ids = $this->input->post('product_ids', TRUE) ?? [];
        $brand_id    = (int)$this->input->post('brand_id', TRUE) ?: null;
        $category_id = (int)$this->input->post('category_id', TRUE) ?: null;
        $this->Scheme_model->save_item_map($id, (array)$product_ids, $brand_id, $category_id);

        $customer_ids = $this->input->post('customer_ids', TRUE) ?? [];
        $this->Scheme_model->save_party_map($id, (array)$customer_ids);

        $fi_pids = $this->input->post('fi_product_id', TRUE) ?? [];
        $fi_qtys = $this->input->post('fi_qty', TRUE) ?? [];
        $fi_uoms = $this->input->post('fi_uom', TRUE) ?? [];
        $free_items = [];
        foreach ($fi_pids as $i => $pid) {
            if ($pid) $free_items[] = ['free_product_id'=>(int)$pid,'free_qty'=>(float)($fi_qtys[$i]??1),'free_uom'=>$fi_uoms[$i]??'pcs'];
        }
        $this->Scheme_model->save_free_items($id, $free_items);

        $this->db->trans_complete();

        if ($this->db->trans_status()) {
            $this->session->set_flashdata('success', 'Scheme updated successfully!');
        } else {
            $this->session->set_flashdata('error', 'Update failed.');
        }
        redirect('schemes/view/' . $id);
    }

    public function toggle($id) {
        $this->Scheme_model->toggle_active($id);
        $this->session->set_flashdata('success', 'Scheme status updated.');
        redirect('schemes');
    }

    public function clone_scheme($id) {
        $new_id = $this->Scheme_model->clone_scheme($id);
        if ($new_id) { $this->session->set_flashdata('success', 'Scheme cloned. Edit and activate.'); redirect('schemes/view/' . $new_id); }
        else { $this->session->set_flashdata('error', 'Clone failed.'); redirect('schemes'); }
    }

    public function delete($id) {
        $this->require_admin();
        $this->Scheme_model->delete($id);
        $this->session->set_flashdata('success', 'Scheme deleted.');
        redirect('schemes');
    }

    public function simulate() {
        $data['page_title'] = 'Scheme Simulator';
        $data['products']   = $this->Product_model->get_all_for_billing();
        $data['customers']  = $this->Customer_model->get_all_for_select();
        $data['result']     = null;
        $data['sim_items']   = [];
        $data['bill_total']  = 0;
        $data['gross_total'] = 0;

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $customer_id  = (int)$this->input->post('customer_id', TRUE);
            $payment_mode = $this->input->post('payment_mode', TRUE) ?: 'cash';
            $items_raw    = $this->input->post('items', TRUE) ?? [];
            $items = [];
            foreach ($items_raw as $i) {
                if (empty($i['product_id'])) continue;
                $p = $this->db->where('id', $i['product_id'])->get('products')->row();
                if (!$p) continue;
                $qty     = (float)($i['qty'] ?? 1);
                $rate    = (float)($p->selling_price ?? 0);
                $gst     = (float)($p->gst_percent ?? 0);
                $taxable = round($qty * $rate / (1 + $gst/100), 2);
                $items[] = ['product_id'=>(int)$i['product_id'],'product_name'=>$p->name,'qty'=>$qty,'uom'=>$i['uom']??'pcs','rate'=>$rate,'mrp'=>(float)($p->mrp??0),'taxable_amt'=>$taxable,'gst_percent'=>$gst,'brand_id'=>$p->brand_id??0,'category_id'=>$p->category_id??0,'carton_size'=>$p->carton_size??1,'expiry_date'=>$p->expiry_date??null];
            }
            $invoice_date = $this->input->post('invoice_date', TRUE) ?: null;
            $invoice_time = $this->input->post('invoice_time', TRUE) ?: null;
            // Schemes are evaluated on the TAXABLE total, matching what
            // Billing::create() passes — the two must agree or the simulator
            // would predict a different discount from the real till.
            $bill_total = array_sum(array_column($items, 'taxable_amt'));
            // What the customer actually pays. Selling prices are GST-inclusive,
            // so this is the figure to show as "Bill Total"; the taxable value is
            // shown separately rather than being labelled as the bill.
            $gross_total = 0;
            foreach ($items as $it) $gross_total += (float)$it['qty'] * (float)$it['rate'];
            $gross_total = round($gross_total, 2);

            $result = $this->schemeengine->evaluate($customer_id, $items, $bill_total, $payment_mode, $invoice_date, $invoice_time);
            $data['result']      = $result;
            $data['sim_items']   = $items;
            $data['bill_total']  = $bill_total;
            $data['gross_total'] = $gross_total;
            $data['customer_id'] = $customer_id;
        }
        $this->render('schemes/simulate', $data);
    }

    public function evaluate_ajax() {
        if (!$this->input->is_ajax_request()) show_404();
        $customer_id  = (int)$this->input->post('customer_id', TRUE);
        $payment_mode = $this->input->post('payment_mode', TRUE) ?: 'credit';
        $bill_total   = (float)$this->input->post('bill_total', TRUE);
        $invoice_date = $this->input->post('invoice_date', TRUE) ?: null;
        $invoice_time = $this->input->post('invoice_time', TRUE) ?: null;

        // Accept items as PHP array (form-array POST) or JSON-encoded string
        $items_raw = $this->input->post('items', TRUE) ?? [];
        $items = is_string($items_raw) ? (json_decode($items_raw, true) ?? []) : $items_raw;

        foreach ($items as &$item) {
            $item['product_id']  = (int)($item['product_id'] ?? 0);
            $item['qty']         = (float)($item['qty'] ?? 0);
            $item['taxable_amt'] = (float)($item['taxable_amt'] ?? 0);
            $item['uom']         = $item['uom'] ?? 'pcs';
            $item['carton_size'] = (int)($item['carton_size'] ?? 1);
            $item['brand_id']    = (int)($item['brand_id'] ?? 0);
            $item['category_id'] = (int)($item['category_id'] ?? 0);
            // Ensure expiry_date is preserved if passed
            if (isset($item['expiry_date'])) {
                $item['expiry_date'] = $item['expiry_date'];
            }
        }
        $result = $this->schemeengine->evaluate($customer_id, $items, $bill_total, $payment_mode, $invoice_date, $invoice_time);
        echo json_encode(['success' => true, 'schemes' => $result]);
    }

    // List which products every scheme (or one selected scheme) applies to
    public function item_list() {
        $scheme_id = (int)$this->input->get('scheme_id', TRUE) ?: null;
        $data['page_title']  = 'Scheme Item List';
        $data['schemes']     = $this->Scheme_model->get_all([]);
        $data['scheme_id']   = $scheme_id;
        $data['rows']        = $this->Scheme_model->get_scheme_items($scheme_id);
        $this->render('schemes/item_list', $data);
    }

    // Excel export of the same scheme → item list
    public function item_list_excel() {
        $scheme_id = (int)$this->input->get('scheme_id', TRUE) ?: null;
        $rows = $this->Scheme_model->get_scheme_items($scheme_id);

        $autoload = APPPATH . '../vendor/autoload.php';
        if (!file_exists($autoload)) {
            $this->session->set_flashdata('error', 'PhpSpreadsheet not installed.');
            redirect('schemes/item_list' . ($scheme_id ? '?scheme_id=' . $scheme_id : ''));
        }
        require_once $autoload;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()->setTitle('Scheme Item List');
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Scheme Items');

        $headers = ['Scheme Name', 'Scheme Type', 'Applies On', 'Status', 'Start Date', 'End Date', 'Item Code', 'Product Name', 'Brand', 'Category'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
        }

        $r = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $r, $row->scheme_name);
            $sheet->setCellValue('B' . $r, $row->type_label);
            $sheet->setCellValue('C' . $r, $row->apply_on === 'brand_category' ? 'Brand & Category' : ucfirst($row->apply_on));
            $sheet->setCellValue('D' . $r, $row->is_active ? 'Active' : 'Inactive');
            $sheet->setCellValue('E' . $r, $row->start_date);
            $sheet->setCellValue('F' . $r, $row->end_date);
            $sheet->setCellValue('G' . $r, $row->item_code);
            $sheet->setCellValue('H' . $r, $row->product_name);
            $sheet->setCellValue('I' . $r, $row->brand_name);
            $sheet->setCellValue('J' . $r, $row->category_name);
            $r++;
        }
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        while (ob_get_level() > 0) { @ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Scheme_Item_List_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function report() {
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to',   TRUE) ?: date('Y-m-d');
        $data['page_title']  = 'Scheme Performance Report';
        $data['from']        = $from;
        $data['to']          = $to;
        $data['performance'] = $this->Scheme_model->get_performance($from, $to);
        $data['recent_log']  = $this->Scheme_model->get_log(['from'=>$from,'to'=>$to,'limit'=>50]);
        $this->render('schemes/report', $data);
    }
}
