<?php
// ============================================================
// FILE: application/controllers/Reports.php
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Report_model');
        $this->load->model('Sales_return_model');
        $this->load->model('Invoice_model');
        $this->load->model('Location_model');
        $this->load->model('Gst2b_model');
        $this->load->model('Ledger_model');
    }

    // Gate individual report access
    private function _rpt($perm_key) {
        $role = $this->session->userdata('role');
        if ($role === 'superadmin' || $role === 'owner' || $role === 'admin') return;
        $fp = $this->session->userdata('form_perms') ?: [];
        if (empty($fp[$perm_key])) {
            $this->session->set_flashdata('error', 'You do not have access to this report.');
            redirect('dashboard');
        }
    }

    // Blanket "can this user view reports at all" flag (user_permissions.can_view_reports,
    // same table/column Billing.php reads for allow_payment_mode_change etc). Defaults to
    // allowed (matches the column's DB default of 1) when no row exists for the user yet.
    private function can_view_reports() {
        $role = $this->session->userdata('role');
        if ($role === 'superadmin' || $role === 'admin') return true;
        $perm = $this->db->where('user_id', $this->session->userdata('user_id'))->get('user_permissions')->row();
        return $perm ? (bool)$perm->can_view_reports : true;
    }

    public function index() {
        $data['page_title'] = 'Reports';
        $this->render('reports/index', $data);
    }

    // Sales report
    public function sales() {
        $this->_rpt('frm_rpt_sales');
        $from   = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to     = $this->input->get('to',   TRUE) ?: date('Y-m-d');
        $view   = $this->input->get('view', TRUE) ?: 'summary';
        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);
        $loc    = $this->_get_loc_id();
        $payment_mode = strtolower(trim($this->input->get('payment_mode', TRUE) ?: ''));

        $item_filters = [
            'item_name'    => $this->input->get('item_name', TRUE),
            'item_code'    => $this->input->get('item_code', TRUE),
            'category_id'  => $this->input->get('category_id', TRUE),
            'brand_id'     => $this->input->get('brand_id', TRUE),
            'supplier_id'  => $this->input->get('supplier_id', TRUE),
            'mrp'          => $this->input->get('mrp', TRUE),
            'payment_mode' => $payment_mode,
        ];

        if ($view === 'details') {
            $item_rows = $this->Report_model->get_sales_item_details($from, $to, $loc, $item_filters);

            // Build a readable title suffix from whichever filters are active, so an
            // exported PDF/Excel makes sense on its own without the on-screen filter bar.
            $title_bits = [];
            if (!empty($item_filters['item_name']))  $title_bits[] = 'Item: '.$item_filters['item_name'];
            if (!empty($item_filters['item_code']))  $title_bits[] = 'Code: '.$item_filters['item_code'];
            if (!empty($item_filters['category_id'])) {
                $cat = $this->db->select('name')->where('id', (int)$item_filters['category_id'])->get('categories')->row();
                if ($cat) $title_bits[] = 'Category: '.$cat->name;
            }
            if (!empty($item_filters['brand_id'])) {
                $brd = $this->db->select('name')->where('id', (int)$item_filters['brand_id'])->get('brands')->row();
                if ($brd) $title_bits[] = 'Brand: '.$brd->name;
            }
            if (!empty($item_filters['supplier_id'])) {
                $sup = $this->db->select('name')->where('id', (int)$item_filters['supplier_id'])->get('suppliers')->row();
                if ($sup) $title_bits[] = 'Supplier: '.$sup->name;
            }
            if (!empty($item_filters['mrp']) || $item_filters['mrp'] === '0') $title_bits[] = 'MRP: '.$item_filters['mrp'];
            if (!empty($payment_mode)) $title_bits[] = 'Payment: '.ucfirst($payment_mode);

            $title = 'Sales Report (Item Details) '.$from.' to '.$to;
            if (!empty($title_bits)) $title .= ' — '.implode(', ', $title_bits);
            $ep = 'from='.$from.'&to='.$to.'&view=details&'.http_build_query($item_filters);
            $headers = ['Invoice No','Date','Outlet','Item Name','Item Code','Category','Brand','Supplier','MRP','Qty','Rate','Taxable','GST','Total'];
            $rows = [];
            $t_qty = 0; $t_taxable = 0; $t_gst = 0; $t_total = 0;
            foreach ($item_rows as $r) {
                $rows[] = [$r->invoice_no, date('d-M-Y',strtotime($r->invoice_date)), $r->location_name??'', $r->product_name, $r->item_code??'',
                    $r->category_name??'', $r->brand_name??'', $r->supplier_name??'', number_format($r->mrp,2), $r->qty, number_format($r->rate,2),
                    number_format($r->taxable_amt,2), number_format($r->gst_amount,2), number_format($r->total,2)];
                $t_qty += $r->qty; $t_taxable += $r->taxable_amt; $t_gst += $r->gst_amount; $t_total += $r->total;
            }
            if (!empty($item_rows)) {
                $rows[] = ['TOTAL','','','','','','','','', $t_qty, '', number_format($t_taxable,2), number_format($t_gst,2), number_format($t_total,2)];
            }
            if ($export==='excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title,$headers,$rows); return; }
            if ($export==='pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title,$headers,$rows); return; }
            if ($export==='email') { $this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/sales?'.$ep)); return; }

            $data['page_title'] = 'Sales Report'; $data['from']=$from; $data['to']=$to; $data['view']=$view;
            $data['payment_mode'] = $payment_mode;
            $data['item_rows']  = $item_rows;
            $data['item_filters'] = $item_filters;
            $data['filter_summary'] = $title_bits;
            $data['categories'] = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('categories')->result();
            $data['brands']     = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('brands')->result();
            $data['suppliers']  = $this->db->select('id, name')->order_by('name')->get('suppliers')->result();
            $data = array_merge($data,$this->_locations_data());
            $data['export_route']='reports/sales'; $data['export_params']=$ep; $data['export_title']=$title;
            $this->render('reports/sales', $data);
            return;
        }

        $invoices = $this->Report_model->get_sales($from, $to, $loc, $payment_mode);
        $refunds  = $this->Report_model->get_refunds($from, $to, $loc, $payment_mode);

        // Merge refunds into the same date-sorted list as pseudo-invoice rows
        // (negative amounts, is_refund flag) so they show up inline — in red —
        // both on screen and in exports, instead of only affecting the summary cards.
        $combined = [];
        foreach ($invoices as $r) { $r->is_refund = false; $combined[] = $r; }
        foreach ($refunds as $rf) {
            $gst  = (float)$rf->gst_amount;
            $half = round($gst / 2, 2);
            $o = new stdClass();
            $o->is_refund       = true;
            $o->refund_id       = $rf->id;
            $o->invoice_no      = $rf->refund_no;
            $o->orig_invoice_no = $rf->invoice_no;
            $o->customer_name   = $rf->customer_name ?: 'Walk-in';
            $o->customer_type   = 'refund';
            $o->location_name   = $rf->location_name ?? '';
            $o->payment_mode    = $rf->refund_mode;
            $o->invoice_date    = date('Y-m-d', strtotime($rf->created_at));
            $o->item_count      = $rf->item_count;
            $o->taxable_amount  = -(float)$rf->taxable_amount;
            if ($rf->is_interstate) {
                $o->cgst_amount = 0; $o->sgst_amount = 0; $o->igst_amount = -$gst;
            } else {
                $o->cgst_amount = -$half; $o->sgst_amount = -round($gst - $half, 2); $o->igst_amount = 0;
            }
            $o->total_amount = -(float)$rf->refund_amount;
            $o->paid_amount  = 0;
            $o->due_amount   = 0;
            $o->status       = 'refund';
            $combined[] = $o;
        }
        usort($combined, fn($a, $b) => strcmp($a->invoice_date, $b->invoice_date));

        $title  = 'Sales Report '.$from.' to '.$to;
        if ($payment_mode) $title .= ' — '.ucfirst($payment_mode);
        $ep     = 'from='.$from.'&to='.$to . ($payment_mode ? '&payment_mode='.$payment_mode : '');
        $headers = ['#','Invoice No','Date','Customer','Location','Payment Mode','Items','Gross','Discount','GST','Net Total','Status'];
        $rows = []; $i=1;
        $t_items=0; $t_gross=0; $t_disc=0; $t_gst=0; $t_net=0;
        foreach ($combined as $r) {
            if ($r->is_refund) {
                $gst   = $r->cgst_amount + $r->sgst_amount + $r->igst_amount; // already negative
                $gross = $r->taxable_amount + $gst;
                $disc  = 0;
                $net   = $r->total_amount;
                $rows[] = [$i++, $r->invoice_no.' (Refund of '.$r->orig_invoice_no.')', date('d-M-Y',strtotime($r->invoice_date)), $r->customer_name, $r->location_name ?? '', strtoupper($r->payment_mode), $r->item_count, number_format($gross,2), number_format($disc,2), number_format($gst,2), number_format($net,2), 'Refund'];
            } else {
                $gross = $r->subtotal ?? $r->total_amount;
                $disc  = $r->discount ?? 0;
                $gst   = $r->total_gst ?? 0;
                $net   = $r->total_amount;
                $rows[] = [$i++, $r->invoice_no, date('d-M-Y',strtotime($r->invoice_date)), $r->customer_name??'Walk-in', $r->location_name ?? '', $r->payment_mode, $r->item_count??'', number_format($gross,2), number_format($disc,2), number_format($gst,2), number_format($net,2), $r->status];
            }
            $t_items += (int)($r->item_count ?? 0); $t_gross += $gross; $t_disc += $disc; $t_gst += $gst; $t_net += $net;
        }
        if (!empty($combined)) {
            $rows[] = ['TOTAL','','','','','', $t_items, number_format($t_gross,2), number_format($t_disc,2), number_format($t_gst,2), number_format($t_net,2), ''];
        }
        if ($export==='excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title,$headers,$rows); return; }
        if ($export==='pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title,$headers,$rows); return; }
        if ($export==='email') { $this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/sales?'.$ep)); return; }
        $data['page_title'] = 'Sales Report'; $data['from']=$from; $data['to']=$to; $data['view']=$view;
        $data['payment_mode'] = $payment_mode;
        $data['invoices']=$combined; $data['summary']=$this->Report_model->sales_summary($from,$to,$loc,$payment_mode);
        $data = array_merge($data,$this->_locations_data());
        $data['export_route']='reports/sales'; $data['export_params']=$ep; $data['export_title']=$title;
        $this->render('reports/sales', $data);
    }

    // Purchase report
    public function purchases() {
        $this->_rpt('frm_rpt_purchase');
        $from=$this->input->get('from',TRUE)?:date('Y-m-01'); $to=$this->input->get('to',TRUE)?:date('Y-m-d');
        $export=$this->input->get('export',TRUE)?:$this->input->post('export',TRUE); $loc=$this->_get_loc_id();
        $purchases=$this->Report_model->get_purchases($from,$to,$loc);
        $title='Purchase Report '.$from.' to '.$to; $ep='from='.$from.'&to='.$to;
        $headers=['#','GRN/Bill No','Date','Supplier','Brands','Items','Total Amount','Status'];
        $rows=[]; $i=1;
        foreach ($purchases as $r) $rows[]=[$i++,$r->bill_no??$r->id,date('d-M-Y',strtotime($r->purchase_date??$r->created_at)),$r->supplier_name??'—',$r->brands??'—',$r->item_count??'',$r->total_amount??0,$r->status??''];
        if ($export==='excel'&&$this->_can('frm_rpt_export')){$this->_export_excel($title,$headers,$rows);return;}
        if ($export==='pdf'  &&$this->_can('frm_rpt_export')){$this->_export_pdf($title,$headers,$rows);return;}
        if ($export==='email'){$this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/purchases?'.$ep));return;}
        $data=['page_title'=>'Purchase Report','from'=>$from,'to'=>$to,'purchases'=>$purchases,'summary'=>$this->Report_model->purchase_summary($from,$to,$loc),'export_route'=>'reports/purchases','export_params'=>$ep,'export_title'=>$title];
        $data=array_merge($data,$this->_locations_data()); $this->render('reports/purchases',$data);
    }

    // Purchase report (Item-wise)
    public function purchase_item_wise() {
        $this->_rpt('frm_rpt_purchase'); // Using same permission as purchase report
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to = $this->input->get('to', TRUE) ?: date('Y-m-d');
        $product_id = $this->input->get('product_id', TRUE);
        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);
        $loc = $this->_get_loc_id();
        
        $records = $this->Report_model->get_purchase_item_wise($from, $to, $product_id, $loc);
        $title = 'Purchase Report (Item-Wise) ' . $from . ' to ' . $to;
        $ep = 'from=' . $from . '&to=' . $to . '&product_id=' . $product_id;
        
        $headers = ['#', 'Date', 'GRN No', 'Invoice No', 'Supplier Name', 'Item Code', 'Item Name', 'Qty', 'Basic Cost', 'MRP', 'SP', 'Tax', 'Total Amount'];
        $rows = []; 
        $i = 1;
        
        foreach ($records as $r) {
            $rows[] = [
                $i++, 
                date('d-M-Y', strtotime($r->invoice_date)), 
                $r->purchase_no ?? '-', 
                $r->invoice_no ?? '-', 
                $r->supplier_name ?? '-', 
                $r->item_code ?? '-', 
                $r->product_name ?? '-', 
                $r->qty ?? 0, 
                $r->basic_cost ?? 0, 
                $r->mrp ?? 0, 
                $r->sp ?? 0, 
                $r->tax ?? 0, 
                $r->total_amount ?? 0
            ];
        }
        
        if ($export === 'excel' && $this->_can('frm_rpt_export')) {
            $this->_export_excel($title, $headers, $rows);
            return;
        }
        if ($export === 'pdf' && $this->_can('frm_rpt_export')) {
            $this->_export_pdf($title, $headers, $rows);
            return;
        }
        
        $products = $this->db->select('id, name, item_code')->where('status', 1)->get('products')->result();
        
        $data = [
            'page_title' => 'Purchase Report (Item-Wise)',
            'from' => $from,
            'to' => $to,
            'product_id' => $product_id,
            'records' => $records,
            'products' => $products,
            'export_route' => 'reports/purchase_item_wise',
            'export_params' => $ep,
            'export_title' => $title
        ];
        
        $data = array_merge($data, $this->_locations_data());
        $this->render('reports/purchase_item_wise', $data);
    }

    // Stock report
    const STOCK_PER_PAGE = 500;

    public function stock() {
        $this->_rpt('frm_rpt_stock');
        $export = $this->input->get('export',TRUE) ?: $this->input->post('export',TRUE);
        $view   = $this->input->get('view', TRUE) ?: 'details';

        $item_filters = [
            'item_name'   => $this->input->get('item_name', TRUE),
            'item_code'   => $this->input->get('item_code', TRUE),
            'ean_code'    => $this->input->get('ean_code', TRUE),
            'category_id' => $this->input->get('category_id', TRUE),
            'brand_id'    => $this->input->get('brand_id', TRUE),
            'supplier_id' => $this->input->get('supplier_id', TRUE),
            'location_id' => $this->input->get('location_id', TRUE),
            'qoh_op'      => $this->input->get('qoh_op', TRUE),
            'qoh_val'     => $this->input->get('qoh_val', TRUE),
        ];

        $page     = max(1, (int)$this->input->get('page', TRUE));
        $per_page = self::STOCK_PER_PAGE;

        // Exports always get the full (unbounded) dataset
        if ($export) {
            @ini_set('memory_limit', '1024M');
            @set_time_limit(600);
            $total = $this->Report_model->count_stock_report($item_filters);
            if ($total > 5000 && ($export === 'excel' || $export === 'pdf') && $this->_can('frm_rpt_export')) {
                $title = 'Stock Report as of '.date('d M Y');
                if (!empty($title_bits)) $title .= ' — '.implode(', ', $title_bits);
                $this->_export_stock_csv_stream($item_filters, $title, $total);
                return;
            }
            $products = $this->Report_model->get_stock_report($item_filters);
        } elseif ($view === 'summary') {
            $products = [];
        } else {
            $products = $this->Report_model->get_stock_report($item_filters, $per_page, ($page - 1) * $per_page);
        }

        // Readable title suffix from whichever filters are active.
        $title_bits = [];
        if (!empty($item_filters['item_name'])) $title_bits[] = 'Item: '.$item_filters['item_name'];
        if (!empty($item_filters['item_code'])) $title_bits[] = 'Code: '.$item_filters['item_code'];
        if (!empty($item_filters['ean_code'])) $title_bits[] = 'EAN: '.$item_filters['ean_code'];
        if (!empty($item_filters['category_id'])) {
            $cat = $this->db->select('name')->where('id', (int)$item_filters['category_id'])->get('categories')->row();
            if ($cat) $title_bits[] = 'Category: '.$cat->name;
        }
        if (!empty($item_filters['brand_id'])) {
            $brd = $this->db->select('name')->where('id', (int)$item_filters['brand_id'])->get('brands')->row();
            if ($brd) $title_bits[] = 'Brand: '.$brd->name;
        }
        if (!empty($item_filters['supplier_id'])) {
            $sup = $this->db->select('name')->where('id', (int)$item_filters['supplier_id'])->get('suppliers')->row();
            if ($sup) $title_bits[] = 'Supplier: '.$sup->name;
        }
        if (!empty($item_filters['location_id'])) {
            $loc = $this->db->select('name')->where('id', (int)$item_filters['location_id'])->get('locations')->row();
            if ($loc) $title_bits[] = 'Location: '.$loc->name;
        }
        if (!empty($item_filters['qoh_op']) && $item_filters['qoh_val'] !== '' && $item_filters['qoh_val'] !== null) {
            $op_labels = ['eq'=>'=','lt'=>'<','lte'=>'<=','gt'=>'>','gte'=>'>='];
            $ol = $op_labels[$item_filters['qoh_op']] ?? '?';
            $title_bits[] = 'QOH '.$ol.' '.$item_filters['qoh_val'];
        }
        $ep = http_build_query(array_merge(['view' => $view], $item_filters));
        $loc_id = !empty($item_filters['location_id']) ? (int)$item_filters['location_id'] : null;

        if ($view === 'summary') {
            $groups = $this->Report_model->get_stock_summary_grouped($item_filters);

            $title = 'Stock Summary as of '.date('d M Y');
            if (!empty($title_bits)) $title .= ' — '.implode(', ', $title_bits);
            if ($export === 'excel' || $export === 'pdf' || $export === 'email') {
                $headers = ['Group Type','Group Name','# Products','Stock Qty','Net Value','Gross Value','MRP Value'];
                $rows = [];
                $labels = ['category'=>'CATEGORY SUMMARY','brand'=>'BRAND SUMMARY','supplier'=>'SUPPLIER SUMMARY'];
                foreach ($groups as $type => $rowsByName) {
                    $rows[] = [$labels[$type]];
                    foreach ($rowsByName as $g) {
                        $rows[] = [ucfirst($type), $g->name, $g->count, $g->qty, number_format($g->net,2), number_format($g->gross,2), number_format($g->mrp,2)];
                    }
                }
                if ($export==='excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title,$headers,$rows); return; }
                if ($export==='pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title,$headers,$rows); return; }
                if ($export==='email') { $this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/stock?'.$ep)); return; }
            }

            $data['page_title'] = 'Stock Report'; $data['view'] = $view;
            $data['groups'] = $groups;
            $data['item_filters'] = $item_filters;
            $data['filter_summary'] = $title_bits;
            $data['categories'] = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('categories')->result();
            $data['brands']     = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('brands')->result();
            $data['suppliers']  = $this->db->select('id, name')->order_by('name')->get('suppliers')->result();
            $data['locations']  = $this->Location_model->get_all(true);
            $stock_vals = $this->Report_model->total_stock_value($loc_id);
            $data['total_value'] = $stock_vals['gross']; $data['net_value'] = $stock_vals['net']; $data['mrp_total'] = $stock_vals['mrp'];
            $data['export_route']='reports/stock'; $data['export_params']=$ep; $data['export_title']=$title;
            $this->render('reports/stock', $data);
            return;
        }

        $title = 'Stock Report as of '.date('d M Y');
        if (!empty($title_bits)) $title .= ' — '.implode(', ', $title_bits);
        $headers=['#','Product','Item Code','EAN Code','Category','Brand','Supplier','HSN','Unit','Stock Qty','Basic Cost','CP (incl.GST)','MRP','SP','WSP','GST%','Net Value','Gross Value','MRP Value','Expiry'];
        $rows=[]; $i=1;
        foreach ($products as $r) $rows[]=[$i++,$r->name??'',$r->item_code??'',$r->ean_code??'',$r->category_name??'',$r->brand_name??'',$r->supplier_name??'',$r->hsn_code??'',$r->unit??'PCS',$r->stock_qty??0,$r->basic_cost??0,$r->purchase_price??0,$r->mrp??0,$r->selling_price??0,$r->wsp??0,$r->gst_percent??0,round($r->net_cost_value??0,2),round($r->gross_cost_value??0,2),round($r->mrp_value??0,2),$r->expiry_date??''];
        if ($export==='excel'&&$this->_can('frm_rpt_export')){$this->_export_excel($title,$headers,$rows);return;}
        if ($export==='pdf'  &&$this->_can('frm_rpt_export')){$this->_export_pdf($title,$headers,$rows);return;}
        if ($export==='email'){$this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/stock?'.$ep));return;}
        $data['page_title']  = 'Stock Report'; $data['view'] = $view;
        $data['export_route']='reports/stock'; $data['export_params']=$ep; $data['export_title']=$title;
        $data['products']    = $products;
        $data['item_filters'] = $item_filters;
        $data['filter_summary'] = $title_bits;
        $data['categories'] = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('categories')->result();
        $data['brands']     = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('brands')->result();
        $data['suppliers']  = $this->db->select('id, name')->order_by('name')->get('suppliers')->result();
        $data['locations']  = $this->Location_model->get_all(true);
        $stock_vals          = $this->Report_model->total_stock_value($loc_id);
        $data['total_value'] = $stock_vals['gross'];
        $data['net_value']   = $stock_vals['net'];
        $data['mrp_total']   = $stock_vals['mrp'];
        if ($view === 'details') {
            $data['total_rows'] = $this->Report_model->count_stock_report($item_filters);
            $data['current_page'] = $page;
            $data['per_page'] = $per_page;
        }
        $this->render('reports/stock', $data);
    }

    // Stock by Location (Grid) — one row per product, four columns (Qty/CP/MRP/SP)
    // per location so stock levels can be compared across locations side by side.
    public function stock_location_grid() {
        $this->_rpt('frm_rpt_stock');
        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);

        $item_filters = [
            'item_name'   => $this->input->get('item_name', TRUE),
            'item_code'   => $this->input->get('item_code', TRUE),
            'category_id' => $this->input->get('category_id', TRUE),
            'brand_id'    => $this->input->get('brand_id', TRUE),
        ];

        $locations   = $this->Location_model->get_all(true);

        $title_bits = [];
        if (!empty($item_filters['item_name'])) $title_bits[] = 'Item: '.$item_filters['item_name'];
        if (!empty($item_filters['item_code'])) $title_bits[] = 'Code: '.$item_filters['item_code'];
        if (!empty($item_filters['category_id'])) {
            $cat = $this->db->select('name')->where('id', (int)$item_filters['category_id'])->get('categories')->row();
            if ($cat) $title_bits[] = 'Category: '.$cat->name;
        }
        if (!empty($item_filters['brand_id'])) {
            $brd = $this->db->select('name')->where('id', (int)$item_filters['brand_id'])->get('brands')->row();
            if ($brd) $title_bits[] = 'Brand: '.$brd->name;
        }
        $title = 'Stock by Location (Grid) as of '.date('d M Y');
        if (!empty($title_bits)) $title .= ' — '.implode(', ', $title_bits);
        $ep = http_build_query($item_filters);

        if ($export==='excel' || $export==='pdf' || $export==='email') {
            $products    = $this->Report_model->get_stock_location_grid_products($item_filters);
            $product_ids = array_column($products, 'id');
            $stock_map   = $this->Report_model->get_location_stock_map($product_ids);
            $headers = ['#', 'Item Code', 'Product'];
            foreach ($locations as $loc) {
                $headers[] = $loc->name.' - Qty';
                $headers[] = $loc->name.' - CP';
                $headers[] = $loc->name.' - MRP';
                $headers[] = $loc->name.' - SP';
            }
            $rows = []; $i = 1;
            foreach ($products as $p) {
                $row = [$i++, $p->item_code ?? '', $p->name];
                foreach ($locations as $loc) {
                    $row[] = $stock_map[$p->id][$loc->id] ?? 0;
                    $row[] = $p->purchase_price ?? 0;
                    $row[] = $p->mrp ?? 0;
                    $row[] = $p->selling_price ?? 0;
                }
                $rows[] = $row;
            }
            if ($export==='excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title,$headers,$rows); return; }
            if ($export==='pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title,$headers,$rows,'L'); return; }
            if ($export==='email') { $this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/stock_location_grid?'.$ep)); return; }
        }

        $page     = max(1, (int)$this->input->get('page', TRUE));
        $per_page = self::STOCK_PER_PAGE;
        $products    = $this->Report_model->get_stock_location_grid_products($item_filters, $per_page, ($page - 1) * $per_page);
        $product_ids = array_column($products, 'id');
        $stock_map   = $this->Report_model->get_location_stock_map($product_ids);

        $data['page_title']     = 'Stock by Location (Grid)';
        $data['locations']      = $locations;
        $data['products']       = $products;
        $data['stock_map']      = $stock_map;
        $data['item_filters']   = $item_filters;
        $data['filter_summary'] = $title_bits;
        $data['categories'] = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('categories')->result();
        $data['brands']     = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('brands')->result();
        $data['export_route']  = 'reports/stock_location_grid';
        $data['export_params'] = $ep;
        $data['export_title']  = $title;
        $data['total_rows']    = $this->Report_model->count_stock_location_grid_products($item_filters);
        $data['current_page']  = $page;
        $data['per_page']      = $per_page;
        $this->render('reports/stock_location_grid', $data);
    }

    // Profit & Loss — Summary tab plus Item/Category/Brand/Supplier-wise breakdowns
    public function profit_loss() {
        $this->_rpt('frm_rpt_profit_loss');
        $from   = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to     = $this->input->get('to',   TRUE) ?: date('Y-m-d');
        $view   = $this->input->get('view', TRUE) ?: 'summary';
        if (!in_array($view, ['summary','item','category','brand','supplier'], TRUE)) $view = 'summary';
        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);
        $loc    = $this->_get_loc_id();

        $item_filters = [
            'location_id' => $loc,
            'item_name'   => $this->input->get('item_name', TRUE),
            'item_code'   => $this->input->get('item_code', TRUE),
            'category_id' => $this->input->get('category_id', TRUE),
            'brand_id'    => $this->input->get('brand_id', TRUE),
            'supplier_id' => $this->input->get('supplier_id', TRUE),
        ];

        $data = [];
        $title_bits = [];
        if (!empty($item_filters['item_name'])) $title_bits[] = 'Item: '.$item_filters['item_name'];
        if (!empty($item_filters['item_code'])) $title_bits[] = 'Code: '.$item_filters['item_code'];
        if (!empty($item_filters['category_id'])) { $cat=$this->db->select('name')->where('id',(int)$item_filters['category_id'])->get('categories')->row(); if($cat) $title_bits[]='Category: '.$cat->name; }
        if (!empty($item_filters['brand_id']))    { $brd=$this->db->select('name')->where('id',(int)$item_filters['brand_id'])->get('brands')->row(); if($brd) $title_bits[]='Brand: '.$brd->name; }
        if (!empty($item_filters['supplier_id'])) { $sup=$this->db->select('name')->where('id',(int)$item_filters['supplier_id'])->get('suppliers')->row(); if($sup) $title_bits[]='Supplier: '.$sup->name; }

        if ($view === 'summary') {
            // get_executive_summary() is a superset of get_profit_loss() — same revenue/
            // COGS/GST figures, plus bills/qty/discount/cash-vs-credit, so the Summary tab
            // can show a full breakdown without a second, differently-scoped query.
            $pl = $this->Report_model->get_executive_summary($from, $to, $loc);
            $title = 'Profit & Loss Summary — '.$from.' to '.$to;
            $ep = http_build_query(['view'=>$view,'from'=>$from,'to'=>$to,'location_id'=>$loc]);
            $headers = ['Metric','Amount (Rs.)'];
            $rows = [
                ['Gross Sales (excl. GST)',   round($pl['gross_sales'],2)],
                ['Sales Returns',             round(-$pl['sales_return_value'],2)],
                ['Other Refunds',             round(-$pl['direct_refunds'],2)],
                ['Net Revenue (excl. GST)',   round($pl['revenue'],2)],
                ['Cost of Goods Sold',        round($pl['cogs'],2)],
                ['Gross Profit',              round($pl['gross_profit'],2)],
                ['Gross Margin %',            $pl['gross_margin']],
                ['Total Sales Value (incl. GST)', round($pl['total_sales_value'],2)],
                ['Net Sales (incl. GST)',     round($pl['net_sales_value'],2)],
                ['Total Bills',               $pl['bill_count']],
                ['Average Bill Value (excl. GST)', round($pl['avg_bill_value'],2)],
                ['Total Qty Sold',            $pl['qty_sold']],
                ['Total Discount Given',      round($pl['discount_given'],2)],
                ['Cash Sales (incl. GST)',    round($pl['cash_sales'],2)],
                ['Credit Sales (incl. GST)',  round($pl['credit_sales'],2)],
                ['GST Collected',             round($pl['gst_collected'],2)],
                ['GST Paid (Input)',          round($pl['gst_paid'],2)],
                ['Net GST Payable',           round($pl['gst_payable'],2)],
            ];
            if ($export==='excel'&&$this->_can('frm_rpt_export')){$this->_export_excel($title,$headers,$rows);return;}
            if ($export==='pdf'  &&$this->_can('frm_rpt_export')){$this->_export_pdf($title,$headers,$rows,'P');return;}
            if ($export==='email'){$this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/profit_loss?'.$ep));return;}
            $data['pl'] = $pl;
            $data['gross_profit_total'] = $pl['gross_profit'];
        } else {
            $items = $this->Report_model->get_profit_loss_items($from, $to, $item_filters);
            $ep = http_build_query(array_merge(['view'=>$view,'from'=>$from,'to'=>$to], $item_filters));

            if ($view === 'item') {
                $title = 'Profit & Loss — Item-wise — '.$from.' to '.$to;
                if ($title_bits) $title .= ' — '.implode(', ', $title_bits);
                if ($export==='excel'||$export==='pdf'||$export==='email') {
                    $headers=['#','Item Code','Product','Category','Brand','Supplier','Unit','Qty Sold','Qty Returned','Net Qty','Revenue','COGS','Gross Profit','Margin %'];
                    $rows=[]; $i=1;
                    foreach ($items as $r) $rows[]=[$i++,$r->item_code??'',$r->name,$r->category_name??'',$r->brand_name??'',$r->supplier_name??'—',$r->unit??'PCS',$r->qty_sold,$r->qty_returned,$r->net_qty,round($r->revenue,2),round($r->cogs,2),round($r->gross_profit,2),$r->margin_pct];
                    if ($export==='excel'&&$this->_can('frm_rpt_export')){$this->_export_excel($title,$headers,$rows);return;}
                    if ($export==='pdf'  &&$this->_can('frm_rpt_export')){$this->_export_pdf($title,$headers,$rows,'L');return;}
                    if ($export==='email'){$this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/profit_loss?'.$ep));return;}
                }
                $data['items'] = $items;
                $data['gross_profit_total'] = array_sum(array_column($items, 'gross_profit'));
            } else {
                $group_map = [
                    'category' => ['category_id','category_name','Uncategorized'],
                    'brand'    => ['brand_id','brand_name','No Brand'],
                    'supplier' => ['supplier_id','supplier_name','Unassigned'],
                ];
                [$id_field, $name_field, $fallback] = $group_map[$view];
                $groups = [];
                foreach ($items as $r) {
                    $gname = $r->$name_field ?: $fallback;
                    if (!isset($groups[$gname])) {
                        $groups[$gname] = (object)['id'=>$r->$id_field, 'name'=>$gname, 'count'=>0, 'qty_sold'=>0, 'net_qty'=>0, 'revenue'=>0, 'cogs'=>0, 'gross_profit'=>0];
                    }
                    $groups[$gname]->count++;
                    $groups[$gname]->qty_sold     += $r->qty_sold;
                    $groups[$gname]->net_qty      += $r->net_qty;
                    $groups[$gname]->revenue      += $r->revenue;
                    $groups[$gname]->cogs         += $r->cogs;
                    $groups[$gname]->gross_profit += $r->gross_profit;
                }
                foreach ($groups as $g) $g->margin_pct = $g->revenue > 0 ? round(($g->gross_profit / $g->revenue) * 100, 2) : 0;
                ksort($groups);

                $title = 'Profit & Loss — '.ucfirst($view).'-wise — '.$from.' to '.$to;
                if ($title_bits) $title .= ' — '.implode(', ', $title_bits);
                if ($export==='excel'||$export==='pdf'||$export==='email') {
                    $headers=['#',ucfirst($view),'# Products','Qty Sold','Net Qty','Revenue','COGS','Gross Profit','Margin %'];
                    $rows=[]; $i=1;
                    foreach ($groups as $g) $rows[]=[$i++,$g->name,$g->count,$g->qty_sold,$g->net_qty,round($g->revenue,2),round($g->cogs,2),round($g->gross_profit,2),$g->margin_pct];
                    if ($export==='excel'&&$this->_can('frm_rpt_export')){$this->_export_excel($title,$headers,$rows);return;}
                    if ($export==='pdf'  &&$this->_can('frm_rpt_export')){$this->_export_pdf($title,$headers,$rows,'L');return;}
                    if ($export==='email'){$this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/profit_loss?'.$ep));return;}
                }
                $data['groups'] = $groups;
                $data['gross_profit_total'] = array_sum(array_map(fn($g) => $g->gross_profit, $groups));
            }
        }

        $data['page_title']     = 'Profit & Loss';
        $data['view']           = $view;
        $data['from']           = $from;
        $data['to']             = $to;
        $data['item_filters']   = $item_filters;
        $data['filter_summary'] = $title_bits;
        $data['categories'] = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('categories')->result();
        $data['brands']     = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('brands')->result();
        $data['suppliers']  = $this->db->select('id, name')->order_by('name')->get('suppliers')->result();
        $data = array_merge($data, $this->_locations_data());
        $data['export_route']  = 'reports/profit_loss';
        $data['export_params'] = $ep;
        $data['export_title']  = $title;
        $this->render('reports/profit_loss', $data);
    }

    // Executive Summary — single-page monthly dashboard: KPIs vs last month, sales/GP
    // trend charts, category/top-product breakdowns, branch comparison, and alerts.
    // Management-level report (owner/admin/superadmin/manager), not gated by form_perms.
    public function executive_summary() {
        $this->require_admin();

        $month = $this->input->get('month', TRUE) ?: date('Y-m');
        $from  = $month . '-01';
        $to    = date('Y-m-t', strtotime($from));
        if ($to > date('Y-m-d')) $to = date('Y-m-d'); // don't project into days that haven't happened yet

        $prev_from = date('Y-m-01', strtotime($from . ' -1 month'));
        $prev_to   = date('Y-m-t', strtotime($prev_from));

        $loc = $this->_get_loc_id();

        $cur  = $this->Report_model->get_executive_summary($from, $to, $loc);
        $prev = $this->Report_model->get_executive_summary($prev_from, $prev_to, $loc);

        $opening_stock = $this->Report_model->get_stock_value_asof(date('Y-m-d', strtotime($from . ' -1 day')), $loc);
        $closing_stock = $this->Report_model->get_stock_value_asof($to, $loc);

        $export = $this->input->get('export', TRUE);
        if ($export === 'excel' && $this->_can('frm_rpt_export')) {
            $title = 'Executive Summary — ' . date('F Y', strtotime($from));
            $headers = ['KPI', 'This Month', 'Last Month', 'Difference', '% Growth'];
            $kpi_rows = [
                ['Total Sales',            $cur['revenue']],
                ['Total Purchases',        $cur['purchases']],
                ['Opening Stock Value',    $opening_stock['value']],
                ['Closing Stock Value',    $closing_stock['value']],
                ['Gross Profit',           $cur['gross_profit']],
                ['Gross Margin %',         $cur['gross_margin']],
                ['Total Bills',            $cur['bill_count']],
                ['Average Bill Value',     $cur['avg_bill_value']],
                ['Total Quantity Sold',    $cur['qty_sold']],
                ['Purchase Return',        $cur['purchase_return']],
                ['Sales Return',           $cur['sales_return']],
                ['Total Discount Given',   $cur['discount_given']],
                ['GST Collected',          $cur['gst_collected']],
                ['Cash Sales',             $cur['cash_sales']],
                ['Credit Sales',           $cur['credit_sales']],
                ['Total Customers',        $cur['total_customers']],
                ['New Customers',          $cur['new_customers']],
                ['Outstanding Receivable', array_sum(array_column($this->Report_model->get_customer_dues(), 'balance'))],
                ['Outstanding Payable',    array_sum(array_column($this->Report_model->get_supplier_dues(), 'total_due'))],
            ];
            $prev_lookup = [
                'Total Sales' => $prev['revenue'], 'Total Purchases' => $prev['purchases'],
                'Gross Profit' => $prev['gross_profit'], 'Gross Margin %' => $prev['gross_margin'],
                'Total Bills' => $prev['bill_count'], 'Average Bill Value' => $prev['avg_bill_value'],
                'Total Quantity Sold' => $prev['qty_sold'], 'Purchase Return' => $prev['purchase_return'],
                'Sales Return' => $prev['sales_return'], 'Total Discount Given' => $prev['discount_given'],
                'GST Collected' => $prev['gst_collected'], 'Cash Sales' => $prev['cash_sales'],
                'Credit Sales' => $prev['credit_sales'], 'Total Customers' => $prev['total_customers'],
                'New Customers' => $prev['new_customers'],
            ];
            $rows = [];
            foreach ($kpi_rows as [$label, $val]) {
                $pval = $prev_lookup[$label] ?? null;
                $diff = $pval !== null ? $val - $pval : '';
                $pct  = ($pval !== null && $pval != 0) ? round((($val - $pval) / abs($pval)) * 100, 1) : '';
                $rows[] = [$label, round($val, 2), $pval !== null ? round($pval, 2) : '—', $diff !== '' ? round($diff, 2) : '—', $pct !== '' ? $pct.'%' : '—'];
            }
            $this->_export_excel($title, $headers, $rows);
            return;
        }

        $branches = $this->Location_model->get_all(true);

        $data = array_merge($this->_locations_data(), [
            'page_title'      => 'Executive Summary',
            'month'           => $month,
            'from'            => $from,
            'to'              => $to,
            'prev_from'       => $prev_from,
            'prev_to'         => $prev_to,
            'cur'             => $cur,
            'prev'            => $prev,
            'opening_stock'   => $opening_stock,
            'closing_stock'   => $closing_stock,
            'daily_sales'     => $this->Report_model->get_daily_sales($from, $to, $loc),
            'monthly_trend'   => $this->Report_model->get_monthly_trend(6, $loc),
            'category_sales'  => $this->Report_model->get_category_sales($from, $to, $loc, 8),
            'top_products'    => $this->Report_model->get_top_products($from, $to, $loc, 10),
            'payment_modes'   => $this->Report_model->get_payment_mode_breakdown($from, $to, $loc),
            'dead_slow_stock' => $this->Report_model->get_dead_slow_stock($loc, 15),
            'branches'        => $branches,
            'branch_perf'     => count($branches) > 1 ? $this->Report_model->get_branch_performance($from, $to) : [],
        ]);

        // Alerts panel
        $cust_dues = $this->Report_model->get_customer_dues();
        $supp_dues = $this->Report_model->get_supplier_dues();
        $data['outstanding_receivable'] = array_sum(array_column($cust_dues, 'balance'));
        $data['outstanding_payable']    = array_sum(array_column($supp_dues, 'total_due'));
        $data['low_stock_count'] = (int)$this->db->query("
            SELECT COUNT(*) AS cnt FROM products WHERE status = 1 AND low_stock_threshold > 0 AND stock_qty <= low_stock_threshold
        ")->row()->cnt;
        // Real negative balances per location (display stock is capped at 0)
        $data['negative_stock_count'] = (int)$this->db->query("SELECT COUNT(*) AS cnt FROM product_location_stock WHERE stock_qty < 0")->row()->cnt;
        $data['pending_grn_count'] = (int)$this->db->where('status', 'pending')->count_all_results('grn');
        $data['pending_po_count']  = (int)$this->db->where_not_in('status', ['received', 'invoiced', 'paid', 'rejected', 'cancelled'])->count_all_results('purchase_orders');

        $this->render('reports/executive_summary', $data);
    }

    // GST report (GSTR-1 summary)
    public function gst() {
        $this->_rpt('frm_rpt_gst');
        $data['page_title'] = 'GST Report';
        $month = $this->input->get('month', TRUE) ?: date('Y-m');
        $loc   = $this->_get_loc_id();
        $data['month']        = $month;
        $data = array_merge($data, $this->_locations_data());
        $data['gst_summary']  = $this->Report_model->get_gst_summary($month, $loc);
        $data['b2b_invoices'] = $this->Report_model->get_b2b_invoices($month, $loc);
        $data['b2c_invoices'] = $this->Report_model->get_b2c_invoices($month, $loc);
        $this->render('reports/gst', $data);
    }

    // ============================================================
    // PARTY LEDGER (Customer / Supplier)
    // ============================================================
    public function party_ledger() {
        $this->_rpt('frm_rpt_party_ledger');
        $data['page_title'] = 'Party Ledger';
        $party_type = $this->input->get('party_type', TRUE) ?: 'customer';
        $party_id   = (int)$this->input->get('party_id', TRUE);
        $from       = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to         = $this->input->get('to',   TRUE) ?: date('Y-m-d');

        $data['party_type'] = $party_type;
        $data['party_id']   = $party_id;
        $data['from']       = $from;
        $data['to']         = $to;
        $data['customers']  = $this->db->select('id, name, phone, balance')->where('status',1)->order_by('name')->get('customers')->result();
        $data['suppliers']  = $this->db->select('id, name, phone')->where('status',1)->order_by('name')->get('suppliers')->result();
        $data['ledger']          = [];
        $data['party']           = null;
        $data['opening_balance'] = 0;
        $data['company']         = $this->Report_model->get_company_settings() ?? [];

        if ($party_id) {
            if ($party_type === 'customer') {
                $data['party'] = $this->db->where('id',$party_id)->get('customers')->row();
                $data['ledger'] = $this->Report_model->get_party_ledger_customer($party_id, $from, $to);
                $data['opening_balance'] = $this->Report_model->get_customer_opening_balance($party_id, $from);
            } else {
                $data['party'] = $this->db->where('id',$party_id)->get('suppliers')->row();
                $data['ledger'] = $this->Report_model->get_party_ledger_supplier($party_id, $from, $to);
                $data['opening_balance'] = $this->Report_model->get_supplier_opening_balance($party_id, $from);
            }
        }
        $this->render('reports/party_ledger', $data);
    }

    // Print/PDF version of party ledger (no layout)
    public function party_ledger_print() {
        $party_type = $this->input->get('party_type', TRUE) ?: 'customer';
        $party_id   = (int)$this->input->get('party_id', TRUE);
        $from       = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to         = $this->input->get('to',   TRUE) ?: date('Y-m-d');

        if (!$party_id) redirect('reports/party_ledger');

        $data['party_type'] = $party_type;
        $data['from'] = $from; $data['to'] = $to;
        $data['company'] = $this->Report_model->get_company_settings() ?? [];

        if ($party_type === 'customer') {
            $data['party'] = $this->db->where('id',$party_id)->get('customers')->row();
            $data['ledger'] = $this->Report_model->get_party_ledger_customer($party_id, $from, $to);
            $data['opening_balance'] = $this->Report_model->get_customer_opening_balance($party_id, $from);
        } else {
            $data['party'] = $this->db->where('id',$party_id)->get('suppliers')->row();
            $data['ledger'] = $this->Report_model->get_party_ledger_supplier($party_id, $from, $to);
            $data['opening_balance'] = $this->Report_model->get_supplier_opening_balance($party_id, $from);
        }
        $this->load->view('reports/party_ledger_print', $data);
    }

    // Outstanding dues
    public function outstanding() {
        $this->_rpt('frm_rpt_outstanding');
        $export=$this->input->get('export',TRUE)?:$this->input->post('export',TRUE);
        // Outlet filter: a fixed-location user is locked to their outlet; everyone
        // else defaults to All Outlets (0) and may pick one.
        $fixed_loc = (int)$this->session->userdata('location_id');
        $loc_id    = $fixed_loc ?: (int)$this->input->get('location_id');
        $locdata   = $this->_locations_data();
        $loc_name  = '';
        foreach ($locdata['locations'] as $l) if ((int)$l->id === $loc_id) $loc_name = $l->name;

        $cust=$this->Report_model->get_customer_dues_by_location($loc_id); $supp=$this->Report_model->get_supplier_dues_by_location($loc_id);
        $title='Outstanding Dues as of '.date('d M Y').($loc_name!==''?' - '.$loc_name:' - All Outlets'); $ep=$loc_id?'location_id='.$loc_id:'';
        $headers=['Type','Name','Phone','Outlet','Total Billed','Paid','Outstanding'];
        $rows=[];
        foreach ($cust as $r) $rows[]=['Customer',$r->name??'',$r->phone??'',$r->location_name??'',$r->total_billed??'',$r->paid??'',$r->outstanding??0];
        if (!empty($cust)&&!empty($supp)) $rows[]=['--- Supplier Dues ---'];
        foreach ($supp as $r) $rows[]=['Supplier',$r->name??'',$r->phone??'',$r->location_name??'','','',$r->total_due??0];
        if ($export==='excel'&&$this->_can('frm_rpt_export')){$this->_export_excel($title,$headers,$rows);return;}
        if ($export==='pdf'  &&$this->_can('frm_rpt_export')){$this->_export_pdf($title,$headers,$rows,'P');return;}
        if ($export==='email'){$this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/outstanding'));return;}
        $data=['page_title'=>'Outstanding Dues','customer_dues'=>$cust,'supplier_dues'=>$supp,'export_route'=>'reports/outstanding','export_params'=>$ep,'export_title'=>$title,
               'locations'=>$locdata['locations'],'location_id'=>$loc_id,'fixed_loc'=>$fixed_loc];
        $this->render('reports/outstanding',$data);
    }

    // Trial Balance — every leaf account's closing balance as of a date, from the
    // accounting module's journal_entry_lines. Must always net to zero (debit ===
    // credit); Ledger_model::get_trial_balance() is the single source of that math.
    public function trial_balance() {
        $this->_rpt('frm_rpt_trial_balance');
        $as_of  = $this->input->get('as_of', TRUE) ?: date('Y-m-d');
        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);

        $tb = $this->Ledger_model->get_trial_balance($as_of);
        $title = 'Trial Balance as of ' . date('d M Y', strtotime($as_of));
        $headers = ['Code', 'Account', 'Debit', 'Credit'];
        $rows = [];
        foreach ($tb['rows'] as $r) {
            $rows[] = [$r['account']->account_code, $r['account']->account_name, $r['debit'], $r['credit']];
        }
        $rows[] = ['', 'TOTAL', $tb['total_debit'], $tb['total_credit']];

        if ($export === 'excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title, $headers, $rows); return; }
        if ($export === 'pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title, $headers, $rows, 'P'); return; }
        if ($export === 'email') { $this->_send_report_email($this->input->post('email_to', TRUE), $title, $headers, $rows, base_url('reports/trial_balance')); return; }

        $data = ['page_title' => 'Trial Balance', 'as_of' => $as_of, 'tb' => $tb, 'export_title' => $title];
        $this->render('reports/trial_balance', $data);
    }

    // General Ledger — any single account's dated transaction list with a running
    // balance, drilling into the source document via ref_type/ref_id.
    public function general_ledger() {
        $this->_rpt('frm_rpt_general_ledger');
        $account_id = (int)$this->input->get('account_id', TRUE);
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to', TRUE) ?: date('Y-m-d');
        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);

        $data['page_title'] = 'General Ledger';
        $data['accounts']   = $this->Ledger_model->get_leaf_accounts();
        $data['account_id'] = $account_id;
        $data['from']       = $from;
        $data['to']         = $to;
        $data['ledger']     = $account_id ? $this->Ledger_model->get_ledger($account_id, $from, $to) : null;

        if ($account_id && $data['ledger']) {
            $title = $data['ledger']['account']->account_name . ' — ' . date('d M Y', strtotime($from)) . ' to ' . date('d M Y', strtotime($to));
            $headers = ['Date', 'Entry No', 'Narration', 'Debit', 'Credit', 'Balance'];
            $rows = [];
            foreach ($data['ledger']['lines'] as $l) {
                $rows[] = [date('d-m-Y', strtotime($l->entry_date)), $l->entry_no, $l->line_narration ?: $l->entry_narration, $l->debit, $l->credit, $l->running_balance];
            }
            if ($export === 'excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title, $headers, $rows); return; }
            if ($export === 'pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title, $headers, $rows, 'P'); return; }
            if ($export === 'email') { $this->_send_report_email($this->input->post('email_to', TRUE), $title, $headers, $rows, base_url('reports/general_ledger')); return; }
        }

        $this->render('reports/general_ledger', $data);
    }

    // Scheme Sales Report — how much of a period's revenue came from scheme-touched
    // invoices, a per-scheme discount/free-unit breakdown, and a two-period "impact"
    // comparison (e.g. the 15 days a scheme ran vs. the 15 days before it didn't) so
    // you can see whether running a scheme actually moved total sales.
    public function scheme_sales() {
        $this->_rpt('frm_rpt_schemes');
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to', TRUE) ?: date('Y-m-d');

        // Comparison periods default to "last 15 days" vs "the 15 days before that" —
        // editable, and auto-filled from a scheme's own start/end date via the picker.
        $cmp_a_to   = $this->input->get('cmp_a_to', TRUE) ?: date('Y-m-d');
        $cmp_a_from = $this->input->get('cmp_a_from', TRUE) ?: date('Y-m-d', strtotime($cmp_a_to . ' -14 days'));
        $days       = max(1, (strtotime($cmp_a_to) - strtotime($cmp_a_from)) / 86400 + 1);
        $cmp_b_to   = $this->input->get('cmp_b_to', TRUE) ?: date('Y-m-d', strtotime($cmp_a_from . ' -1 day'));
        $cmp_b_from = $this->input->get('cmp_b_from', TRUE) ?: date('Y-m-d', strtotime($cmp_b_to . ' -' . ($days - 1) . ' days'));

        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);

        $summary   = $this->Report_model->get_scheme_sales_summary($from, $to);
        $breakdown = $this->Report_model->get_scheme_wise_breakdown($from, $to);
        $period_a  = $this->Report_model->get_scheme_sales_summary($cmp_a_from, $cmp_a_to);
        $period_b  = $this->Report_model->get_scheme_sales_summary($cmp_b_from, $cmp_b_to);
        $schemes_list = $this->Report_model->get_all_schemes_for_select();

        $title = 'Scheme Sales Report ' . date('d M Y', strtotime($from)) . ' to ' . date('d M Y', strtotime($to));
        $headers = ['Scheme', 'Type', 'Invoices', 'Customers', 'Discount Given', 'Free Units'];
        $rows = [];
        foreach ($breakdown as $r) {
            $rows[] = [$r->scheme_name, $r->scheme_type, $r->invoices, $r->customers, $r->discount_given, $r->free_units];
        }
        $rows[] = ['', '', '', 'TOTAL', $summary['total_discount'], $summary['total_free_units']];

        if ($export === 'excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title, $headers, $rows); return; }
        if ($export === 'pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title, $headers, $rows, 'L'); return; }
        if ($export === 'email') { $this->_send_report_email($this->input->post('email_to', TRUE), $title, $headers, $rows, base_url('reports/scheme_sales')); return; }

        $data = [
            'page_title'   => 'Scheme Sales Report',
            'from'         => $from,
            'to'           => $to,
            'summary'      => $summary,
            'breakdown'    => $breakdown,
            'cmp_a_from'   => $cmp_a_from,
            'cmp_a_to'     => $cmp_a_to,
            'cmp_b_from'   => $cmp_b_from,
            'cmp_b_to'     => $cmp_b_to,
            'period_a'     => $period_a,
            'period_b'     => $period_b,
            'schemes_list' => $schemes_list,
            'export_title' => $title,
        ];
        $this->render('reports/scheme_sales', $data);
    }

    // Credit Sales Report (invoices with payment_mode = credit or split credit)
    public function credit_sales() {
        $from = $this->input->get('date_from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('date_to',   TRUE) ?: date('Y-m-d');
        $loc  = $this->_get_loc_id();
        $this->db->select('invoices.*, customers.name as customer_name, customers.phone as customer_phone');
        $this->db->join('customers', 'customers.id = invoices.customer_id');
        $this->db->group_start();
        $this->db->where('invoices.payment_mode', 'credit');
        $this->db->or_where('invoices.due_amount >', 0);
        $this->db->group_end();
        $this->db->where('DATE(invoices.invoice_date) >=', $from);
        $this->db->where('DATE(invoices.invoice_date) <=', $to);
        $this->db->where_in('invoices.status', ['unpaid', 'partial']);
        if ($loc) $this->db->where('invoices.location_id', $loc);
        $data['page_title']    = 'Credit Sales Report';
        $data['invoices']      = $this->db->order_by('invoices.invoice_date', 'DESC')->get('invoices')->result();
        $data['date_from']     = $from;
        $data['date_to']       = $to;
        $this->render('reports/credit_sales', $data);
    }

    // Refund / Return Report
    public function refunds_report() {
        $this->load->model('Refund_model');
        $from = $this->input->get('date_from', TRUE) ?: $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('date_to',   TRUE) ?: $this->input->get('to', TRUE) ?: date('Y-m-d');
        $view = $this->input->get('view', TRUE) ?: 'summary';
        $loc  = $this->_get_loc_id();
        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);

        $item_filters = [
            'item_name'    => $this->input->get('item_name', TRUE),
            'item_code'    => $this->input->get('item_code', TRUE),
            'category_id'  => $this->input->get('category_id', TRUE),
            'brand_id'     => $this->input->get('brand_id', TRUE),
            'supplier_id'  => $this->input->get('supplier_id', TRUE),
            'refund_mode'  => $this->input->get('refund_mode', TRUE),
        ];

        if ($view === 'details') {
            $item_rows = $this->Report_model->get_refund_item_details($from, $to, $loc, $item_filters);

            $title_bits = [];
            if (!empty($item_filters['item_name']))  $title_bits[] = 'Item: '.$item_filters['item_name'];
            if (!empty($item_filters['item_code']))  $title_bits[] = 'Code: '.$item_filters['item_code'];
            if (!empty($item_filters['category_id'])) {
                $cat = $this->db->select('name')->where('id', (int)$item_filters['category_id'])->get('categories')->row();
                if ($cat) $title_bits[] = 'Category: '.$cat->name;
            }
            if (!empty($item_filters['brand_id'])) {
                $brd = $this->db->select('name')->where('id', (int)$item_filters['brand_id'])->get('brands')->row();
                if ($brd) $title_bits[] = 'Brand: '.$brd->name;
            }
            if (!empty($item_filters['supplier_id'])) {
                $sup = $this->db->select('name')->where('id', (int)$item_filters['supplier_id'])->get('suppliers')->row();
                if ($sup) $title_bits[] = 'Supplier: '.$sup->name;
            }
            if (!empty($item_filters['refund_mode'])) $title_bits[] = 'Mode: '.ucfirst($item_filters['refund_mode']);

            $title = 'Refund Report (Item Details) '.$from.' to '.$to;
            if (!empty($title_bits)) $title .= ' — '.implode(', ', $title_bits);
            $ep = 'from='.$from.'&to='.$to.'&view=details&'.http_build_query($item_filters);
            $headers = ['Refund #','Invoice #','Date','Outlet','Customer','Item Name','Item Code','Category','Brand','Supplier','MRP','Qty','Rate','Taxable','GST','Total','Mode'];
            $rows = [];
            $t_qty = 0; $t_taxable = 0; $t_gst = 0; $t_total = 0;
            foreach ($item_rows as $r) {
                $rows[] = [$r->refund_no, $r->invoice_no, date('d-M-Y',strtotime($r->refund_date)),
                    $r->location_name??'', $r->customer_name??'', $r->product_name, $r->item_code??'',
                    $r->category_name??'', $r->brand_name??'', $r->supplier_name??'',
                    number_format($r->mrp??0,2), $r->qty, number_format($r->rate,2),
                    number_format($r->taxable_amt,2), number_format($r->gst_amount,2), number_format($r->total,2),
                    strtoupper($r->refund_mode)];
                $t_qty += $r->qty; $t_taxable += $r->taxable_amt; $t_gst += $r->gst_amount; $t_total += $r->total;
            }
            if (!empty($item_rows)) {
                $rows[] = ['TOTAL','','','','','','','','','','', $t_qty, '', number_format($t_taxable,2), number_format($t_gst,2), number_format($t_total,2), ''];
            }
            if ($export==='excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title,$headers,$rows); return; }
            if ($export==='pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title,$headers,$rows); return; }
            if ($export==='email') { $this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/refunds_report?'.$ep)); return; }

            $data['page_title'] = 'Refunds & Returns Report';
            $data['date_from']=$from; $data['date_to']=$to; $data['view']=$view;
            $data['item_rows']  = $item_rows;
            $data['item_filters'] = $item_filters;
            $data['filter_summary'] = $title_bits;
            $data['categories'] = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('categories')->result();
            $data['brands']     = $this->db->select('id, name')->where('status', 1)->order_by('name')->get('brands')->result();
            $data['suppliers']  = $this->db->select('id, name')->order_by('name')->get('suppliers')->result();
            $data = array_merge($data,$this->_locations_data());
            $data['export_route']='reports/refunds_report'; $data['export_params']=$ep; $data['export_title']=$title;
            $this->render('reports/refunds_report', $data);
            return;
        }

        $filters = ['date_from' => $from, 'date_to' => $to];
        $data['page_title'] = 'Refunds & Returns Report';
        $data['refunds']    = $this->Refund_model->search($filters);
        $data['date_from']  = $from;
        $data['date_to']    = $to;
        $data['view']       = $view;
        $data = array_merge($data,$this->_locations_data());
        $data['export_route']='reports/refunds_report'; $data['export_params']='from='.$from.'&to='.$to; $data['export_title']='Refunds & Returns Report';
        $this->render('reports/refunds_report', $data);
    }

    // Customer Wallet Ledger — mirrors party_ledger() but reads the stored
    // opening_balance/closing_balance columns on customer_wallet_transactions
    // directly rather than recomputing a running balance.
    public function wallet_ledger() {
        $this->_rpt('frm_rpt_wallet_ledger');
        $data['page_title'] = 'Wallet Ledger';
        $customer_id = (int)$this->input->get('customer_id', TRUE);
        $from        = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to          = $this->input->get('to',   TRUE) ?: date('Y-m-d');

        $data['customer_id'] = $customer_id;
        $data['from']        = $from;
        $data['to']          = $to;
        $data['customers']   = $this->db->select('id, name, phone')->where('status', 1)->order_by('name')->get('customers')->result();
        $data['ledger']      = [];
        $data['customer']    = null;

        if ($customer_id) {
            $data['customer'] = $this->db->where('id', $customer_id)->get('customers')->row();
            $data['ledger']   = $this->Report_model->get_wallet_ledger($customer_id, $from, $to);
        }
        $this->render('reports/wallet_ledger', $data);
    }

    // Portal Order Funnel — B2B/B2C order_requests lifecycle (pending -> confirmed
    // -> converted/cancelled). Does NOT join through to the resulting invoice:
    // order_requests.invoice_id exists but is never populated by
    // OrderRequests::convert()/Billing.php today, so it's always NULL. Correlating
    // via customer_id + timestamp proximity was considered but rejected as unreliable;
    // this report sticks to what order_requests itself reliably records.
    public function portal_order_funnel() {
        $this->_rpt('frm_rpt_portal_funnel');
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to',   TRUE) ?: date('Y-m-d');

        $summary = $this->Report_model->get_portal_order_status_summary($from, $to);
        $total_count = array_sum(array_column($summary, 'cnt'));
        $converted_count = 0;
        foreach ($summary as $s) { if ($s['status'] === 'converted') $converted_count = (int)$s['cnt']; }

        $data['page_title']       = 'Portal Order Funnel';
        $data['from']             = $from;
        $data['to']               = $to;
        $data['summary']          = $summary;
        $data['total_count']      = $total_count;
        $data['conversion_rate']  = $total_count > 0 ? ($converted_count / $total_count) * 100 : 0;
        $data['orders']          = $this->Report_model->get_portal_order_detail($from, $to);
        $data['top_pending']     = $this->Report_model->get_portal_top_pending_customers();
        $this->render('reports/portal_order_funnel', $data);
    }

    // PO vs GRN Variance — line-level ordered/accepted/rejected/pending quantities
    // across the purchase-to-pay pipeline.
    public function po_grn_variance() {
        $this->_rpt('frm_rpt_po_grn_variance');
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to',   TRUE) ?: date('Y-m-d');
        $pending_only = $this->input->get('show_all', TRUE) !== '1';

        $data['page_title']    = 'PO vs GRN Variance';
        $data['from']          = $from;
        $data['to']            = $to;
        $data['pending_only']  = $pending_only;
        $data['lines']         = $this->Report_model->get_po_grn_variance($from, $to, $pending_only);
        $this->render('reports/po_grn_variance', $data);
    }

    // Stock-Take Variance (cross date-range) — aggregates the per-take shortage/
    // surplus value impact already computed per-take in StockTake::variance_report(),
    // but rolled up across every completed take in a period instead of just one.
    public function stock_take_variance() {
        $this->_rpt('frm_rpt_stock_take_variance');
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to',   TRUE) ?: date('Y-m-d');
        $loc  = $this->_get_loc_id();

        $data['page_title'] = 'Stock-Take Variance';
        $data['from']       = $from;
        $data['to']         = $to;
        $data              = array_merge($data, $this->_locations_data());
        $data['takes']      = $this->Report_model->get_stock_take_variance_summary($from, $to, $loc);
        $data['total_surplus_value']  = array_sum(array_column($data['takes'], 'surplus_value'));
        $data['total_shortage_value'] = array_sum(array_column($data['takes'], 'shortage_value'));
        $this->render('reports/stock_take_variance', $data);
    }

    // ============================================================
    // GSTR-1 Full (B2B + B2C + HSN Summary + Tax Summary)
    // ============================================================
    public function gstr1() {
        $this->_rpt('frm_rpt_gst');
        $data['page_title'] = 'GSTR-1 Report';
        $month  = $this->input->get('month', TRUE) ?: date('Y-m');
        $from   = $month . '-01';
        $to     = date('Y-m-t', strtotime($from));
        $loc    = $this->_get_loc_id();
        $data['month']        = $month;
        $data['from']         = $from;
        $data['to']           = $to;
        $data = array_merge($data, $this->_locations_data());
        $data['b2b']          = $this->Report_model->get_b2b_invoices($month, $loc);
        $data['b2c']          = $this->Report_model->get_b2c_invoices($month, $loc);
        $data['hsn_summary']  = $this->Report_model->get_hsn_summary($from, $to, $loc);
        $data['tax_summary']  = $this->Report_model->get_gst_summary($month, $loc);
        $data['gst_summary']  = $data['tax_summary'];
        // Section 9B â€” Credit Notes (Sales Returns)
        $data['credit_notes'] = $this->Sales_return_model->get_for_gstr1($from, $to);
        $data['cn_hsn']       = $this->Sales_return_model->get_hsn_summary($from, $to);
        $this->render('reports/gstr1', $data);
    }

    // ============================================================
    // Standalone HSN Summary Report — same "Summary For HSN(12)" layout/
    // math as the GSTR-1 offline-tool export (_build_gstr1_hsn_sheet /
    // _hsn_summary_rows below), but viewable on screen with its own date
    // range, location, and B2B/B2C/All filters, independent of the full
    // GSTR-1 workflow.
    //
    // Deliberately GROSS sales only (same convention as the existing GSTR-1
    // HSN export) — it does NOT net out sales_returns/credit notes. Table 12
    // of the actual GSTR-1 return is filed as gross outward supply; credit
    // notes are reported separately (see gstr1.php's "Section 9B — Credit
    // Notes" tab). Netting them into this table would make it disagree with
    // the GSTR-1 export this app already produces for the same period.
    //
    // Cess is always 0 here — this app has no field anywhere that captures
    // cess, so there is nothing honest to report; showing a real-looking
    // number would be fabricating data that doesn't exist in this system.
    // ============================================================
    public function hsn_summary_report() {
        $this->_rpt('frm_rpt_gst');
        $data['page_title'] = 'HSN Summary Report';
        $from     = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to       = $this->input->get('to', TRUE)   ?: date('Y-m-d');
        $gst_type = $this->input->get('gst_type', TRUE) ?: 'all';
        $loc      = $this->_get_loc_id();

        $data['from']     = $from;
        $data['to']       = $to;
        $data['gst_type'] = $gst_type;
        $data = array_merge($data, $this->_locations_data());

        $summary = $this->_hsn_summary_rows($from, $to, $loc, $gst_type);
        $data['rows']   = $summary['rows'];
        $data['totals'] = $summary['totals'];

        $this->render('reports/hsn_summary_report', $data);
    }

    public function export_hsn_summary_report() {
        $this->_rpt('frm_rpt_gst');
        if (!$this->_can('frm_rpt_export')) { $this->session->set_flashdata('error', 'Export not allowed.'); redirect('reports/hsn_summary_report'); return; }

        $from     = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to       = $this->input->get('to', TRUE)   ?: date('Y-m-d');
        $gst_type = $this->input->get('gst_type', TRUE) ?: 'all';
        $loc      = $this->_get_loc_id();

        $autoload = APPPATH . '../vendor/autoload.php';
        if (!file_exists($autoload)) {
            $this->session->set_flashdata('error', 'PhpSpreadsheet not installed.');
            redirect('reports/hsn_summary_report');
            return;
        }
        require_once $autoload;

        $summary = $this->_hsn_summary_rows($from, $to, $loc, $gst_type);
        $rows    = $summary['rows'];
        $totals  = $summary['totals'];
        $title_suffix = ['b2b' => 'B2B Sales', 'b2c' => 'B2C Sales', 'all' => 'All Sales'][$gst_type] ?? 'All Sales';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('HSN Summary');
        $sheet->setCellValue('A1', 'Summary For HSN(12) - ' . $title_suffix . ' (' . $from . ' to ' . $to . ')');
        $sheet->setCellValue('A2', 'No. of HSN');
        $sheet->setCellValue('E2', 'Total Value');
        $sheet->setCellValue('G2', 'Total Taxable Value');
        $sheet->setCellValue('H2', 'Total Integrated Tax');
        $sheet->setCellValue('I2', 'Total Central Tax');
        $sheet->setCellValue('J2', 'Total State/UT Tax');
        $sheet->setCellValue('K2', 'Total Cess');
        $sheet->setCellValue('A3', count($rows));
        $sheet->setCellValue('E3', round($totals['value'], 2));
        $sheet->setCellValue('G3', round($totals['taxable'], 2));
        $sheet->setCellValue('H3', round($totals['igst'], 2));
        $sheet->setCellValue('I3', round($totals['cgst'], 2));
        $sheet->setCellValue('J3', round($totals['sgst'], 2));
        $sheet->setCellValue('K3', 0);

        $headers = ['HSN', 'Description', 'UQC', 'Total Quantity', 'Total Value', 'Rate', 'Taxable Value',
                    'Integrated Tax Amount', 'Central Tax Amount', 'State/UT Tax Amount', 'Cess Amount'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '4', $h);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
        }

        $r = 5;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $r, $row->hsn_code);
            $sheet->setCellValue('B' . $r, $row->hsn_desc);
            $sheet->setCellValue('C' . $r, $row->uqc);
            $sheet->setCellValue('D' . $r, (float)$row->qty);
            $sheet->setCellValue('E' . $r, round((float)$row->total_value, 2));
            $sheet->setCellValue('F' . $r, (float)$row->rate);
            $sheet->setCellValue('G' . $r, round((float)$row->taxable_value, 2));
            $sheet->setCellValue('H' . $r, round((float)$row->igst, 2));
            $sheet->setCellValue('I' . $r, round((float)$row->cgst, 2));
            $sheet->setCellValue('J' . $r, round((float)$row->sgst, 2));
            $sheet->setCellValue('K' . $r, 0);
            $r++;
        }
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="HSN_Summary_' . $from . '_to_' . $to . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    // Shared query for hsn_summary_report()'s on-screen table and its Excel
    // export — same grouping/rate-slab/IGST-CGST-SGST-split logic as
    // _build_gstr1_hsn_sheet(), plus an optional location filter and a
    // b2b/b2c/all toggle (customer has a GSTIN => B2B, matching the same
    // rule the GSTR-1 offline export already uses to split the two sheets).
    private function _hsn_summary_rows($from, $to, $location_id, $gst_type) {
        $uqc_map = ['PCS' => 'PCS-PIECES', 'KG' => 'KGS-KILOGRAMS', 'LTR' => 'LTR-LITRE', 'PKT' => 'PAC-PACKS', 'BOX' => 'BOX-BOX', 'CRT' => 'BOX-BOX'];

        $gst_filter = '';
        if ($gst_type === 'b2b') $gst_filter = "AND c.gst_number IS NOT NULL AND c.gst_number != ''";
        elseif ($gst_type === 'b2c') $gst_filter = "AND (c.gst_number IS NULL OR c.gst_number = '')";

        $loc_filter = $location_id ? "AND i.location_id = " . (int)$location_id : '';

        $rows = $this->db->query("
            SELECT COALESCE(NULLIF(ii.hsn_code,''),'0000') AS hsn_code,
                   COALESCE(h.description, ii.hsn_code, '') AS hsn_desc,
                   COALESCE(ii.unit, 'PCS') AS unit,
                   ii.gst_percent AS rate,
                   SUM(ii.qty) AS qty,
                   SUM(ii.total) AS total_value,
                   SUM(ii.taxable_amt) AS taxable_value,
                   SUM(CASE WHEN i.is_interstate=1 THEN ii.gst_amount ELSE 0 END) AS igst,
                   SUM(CASE WHEN i.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END) AS cgst,
                   SUM(CASE WHEN i.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END) AS sgst
            FROM invoice_items ii
            JOIN invoices i ON i.id = ii.invoice_id
            JOIN customers c ON c.id = i.customer_id
            LEFT JOIN hsn_master h ON h.hsn_code = ii.hsn_code
            WHERE i.invoice_date BETWEEN ? AND ?
              AND i.status != 'cancelled'
              {$gst_filter}
              {$loc_filter}
            GROUP BY ii.hsn_code, ii.gst_percent
            ORDER BY ii.hsn_code, ii.gst_percent
        ", [$from, $to])->result();

        $totals = ['value' => 0.0, 'taxable' => 0.0, 'igst' => 0.0, 'cgst' => 0.0, 'sgst' => 0.0];
        foreach ($rows as $row) {
            $row->uqc = $uqc_map[strtoupper($row->unit)] ?? 'OTH-OTHERS';
            $totals['value']   += (float)$row->total_value;
            $totals['taxable'] += (float)$row->taxable_value;
            $totals['igst']    += (float)$row->igst;
            $totals['cgst']    += (float)$row->cgst;
            $totals['sgst']    += (float)$row->sgst;
        }

        return ['rows' => $rows, 'totals' => $totals];
    }

    // ============================================================
    // Tally Export — two files, matching Tally's own import menu:
    // Masters (ledgers / stock items / units) and Transactions (Sales, Sales
    // Return, Purchase, Purchase Return vouchers), as native Tally XML
    // (Gateway of Tally -> Import -> Data). See TallyExporter.php for the
    // XML-building conventions and the design decisions confirmed with the
    // user before this was built (item-wise invoices, shared "Cash Sales"
    // ledger for B2C, flat non-rate-classified CGST/SGST/IGST ledgers).
    // ============================================================
    public function tally_export() {
        $this->_rpt('frm_rpt_gst');
        $data['page_title'] = 'Tally Export';
        $data['from'] = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $data['to']   = $this->input->get('to', TRUE)   ?: date('Y-m-d');
        $data['mode'] = $this->input->get('mode', TRUE) === 'masters' ? 'masters' : 'transactions';
        $data = array_merge($data, $this->_locations_data());
        $this->render('reports/tally_export', $data);
    }

    // Two export modes, mirroring Tally's own import menu:
    //   masters      → ledgers / stock items / units only, no date range
    //                  (REPORTNAME "All Masters")
    //   transactions → Sales / Sales Return / Purchase / Purchase Return
    //                  vouchers for a date range (REPORTNAME "Vouchers"),
    //                  optionally carrying the masters those vouchers
    //                  reference so a transactions-first import still works.
    public function export_tally_xml() {
        $this->_rpt('frm_rpt_gst');
        if (!$this->_can('frm_rpt_export')) { $this->session->set_flashdata('error', 'Export not allowed.'); redirect('reports/tally_export'); return; }

        $company_name = trim($this->input->get('tally_company', TRUE));
        if ($company_name === '') {
            $this->session->set_flashdata('error', 'Enter the exact Tally company name to import into.');
            redirect('reports/tally_export');
            return;
        }

        if ($this->input->get('mode', TRUE) === 'masters') {
            $this->_export_tally_masters($company_name);
            return;
        }
        $this->_export_tally_transactions($company_name);
    }

    // ── Masters only ────────────────────────────────────────────────────
    private function _export_tally_masters($company_name) {
        $inc = [
            'suppliers'      => (bool)$this->input->get('m_suppliers'),
            'customers'      => (bool)$this->input->get('m_customers'),
            'customers_b2c'  => (bool)$this->input->get('m_customers_b2c'),
            'products'       => (bool)$this->input->get('m_products'),
            'opening_stock'  => (bool)$this->input->get('m_opening_stock'),
            'accounts'       => (bool)$this->input->get('m_accounts'),
        ];
        if (!array_filter([$inc['suppliers'], $inc['customers'], $inc['products'], $inc['accounts']])) {
            $this->session->set_flashdata('error', 'Select at least one master type to export.');
            redirect('reports/tally_export?mode=masters');
            return;
        }

        $company_row = $this->db->where('key_name', 'company_state')->get('company_settings')->row();
        $company_state = trim($company_row->key_value ?? '');

        $this->load->library('TallyExporter');
        $tx = $this->tallyexporter;
        $masters = '';

        // Accounting ledgers the voucher export posts against, so a masters
        // import lays down the same chart of accounts up front.
        if ($inc['accounts']) {
            $masters .= $tx->ledger_simple('CGST', 'Duties & Taxes');
            $masters .= $tx->ledger_simple('SGST', 'Duties & Taxes');
            $masters .= $tx->ledger_simple('IGST', 'Duties & Taxes');
            $masters .= $tx->ledger_simple('Sales', 'Sales Accounts');
            $masters .= $tx->ledger_simple('Purchase', 'Purchase Accounts');
            $masters .= $tx->ledger_party('Cash Sales', 'Sundry Debtors', 0, null, '', $company_state, '');
        }

        if ($inc['suppliers']) {
            foreach ($this->_tally_suppliers() as $s) {
                $masters .= $tx->ledger_party($s->name, 'Sundry Creditors', $s->opening_balance ?? 0, $s->gst_number, $s->address, $s->state, $s->pincode);
            }
        }

        if ($inc['customers']) {
            // B2C/walk-in customers only get their own ledger if explicitly
            // asked for — vouchers post them to the shared "Cash Sales"
            // ledger, so by default exporting them would create ledgers
            // nothing ever posts to.
            foreach ($this->_tally_customers($inc['customers_b2c']) as $c) {
                $masters .= $tx->ledger_party($c->name, 'Sundry Debtors', $c->opening_balance ?? 0, $c->gst_number, $c->address, $c->state, $c->pincode);
            }
        }

        if ($inc['products']) {
            foreach ($this->_tally_products() as $p) {
                $masters .= $tx->unit_master($p->unit);
                $masters .= $tx->stock_item_master(
                    $p->name, $p->unit, $p->hsn_code,
                    $inc['opening_stock'] ? $p->stock_qty : null,
                    $inc['opening_stock'] ? $p->purchase_price : null
                );
            }
        }

        $xml = $tx->wrap_envelope($company_name, $masters, '', 'All Masters');

        header('Content-Type: text/xml; charset=utf-8');
        header('Content-Disposition: attachment;filename="Tally_Masters_' . date('Y-m-d') . '.xml"');
        header('Cache-Control: max-age=0');
        echo $xml;
    }

    // ── Transactions (vouchers) ─────────────────────────────────────────
    private function _export_tally_transactions($company_name) {
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to', TRUE)   ?: date('Y-m-d');
        $loc  = $this->_get_loc_id();

        $sections = [
            'sales' => (bool)$this->input->get('inc_sales'),
            'sales_return' => (bool)$this->input->get('inc_sales_return'),
            'purchase' => (bool)$this->input->get('inc_purchase'),
            'purchase_return' => (bool)$this->input->get('inc_purchase_return'),
        ];
        if (!array_filter($sections)) {
            $this->session->set_flashdata('error', 'Select at least one voucher type to export.');
            redirect('reports/tally_export');
            return;
        }
        // When off, the file carries vouchers alone — for the case where the
        // masters file has already been imported into the same Tally company.
        $with_masters = (bool)$this->input->get('inc_masters');

        $company_row = $this->db->where('key_name', 'company_state')->get('company_settings')->row();
        $company_state = trim($company_row->key_value ?? '');

        $this->load->library('TallyExporter');
        $tx = $this->tallyexporter;
        $masters = '';
        $vouchers = '';

        $need_gst_ledgers = $sections['sales'] || $sections['sales_return'] || $sections['purchase'] || $sections['purchase_return'];
        if ($need_gst_ledgers) {
            $masters .= $tx->ledger_simple('CGST', 'Duties & Taxes');
            $masters .= $tx->ledger_simple('SGST', 'Duties & Taxes');
            $masters .= $tx->ledger_simple('IGST', 'Duties & Taxes');
        }
        if ($sections['sales'] || $sections['sales_return']) {
            $masters .= $tx->ledger_simple('Sales', 'Sales Accounts');
            $masters .= $tx->ledger_party('Cash Sales', 'Sundry Debtors', 0, null, '', $company_state, '');
        }
        if ($sections['purchase'] || $sections['purchase_return']) {
            $masters .= $tx->ledger_simple('Purchase', 'Purchase Accounts');
        }

        if ($sections['sales']) {
            foreach ($this->_tally_sales($from, $to, $loc) as $inv) {
                $party = !empty($inv->customer_gstin) ? $inv->customer_name : 'Cash Sales';
                if (!empty($inv->customer_gstin)) {
                    $masters .= $tx->ledger_party($inv->customer_name, 'Sundry Debtors', 0, $inv->customer_gstin, $inv->customer_address, $inv->customer_state, $inv->customer_pincode);
                }
                $items = [];
                foreach ($inv->items as $it) {
                    $masters .= $tx->unit_master($it->unit);
                    $masters .= $tx->stock_item_master($it->product_name, $it->unit, $it->hsn_code);
                    $items[] = ['stock_item' => $it->product_name, 'unit' => $it->unit, 'qty' => $it->qty, 'rate' => $it->rate,
                        'taxable' => $it->taxable_amt, 'cgst' => $it->cgst_amount, 'sgst' => $it->sgst_amount, 'igst' => $it->igst_amount];
                }
                $vouchers .= $tx->voucher_sales($inv->invoice_no, str_replace('-', '', $inv->invoice_date), $party, $items, $inv->total_amount, 'Auto-exported from ERP');
            }
        }

        if ($sections['sales_return']) {
            foreach ($this->_tally_sales_returns($from, $to) as $ret) {
                $party = !empty($ret->customer_gstin) ? $ret->customer_name : 'Cash Sales';
                if (!empty($ret->customer_gstin)) {
                    $masters .= $tx->ledger_party($ret->customer_name, 'Sundry Debtors', 0, $ret->customer_gstin, $ret->customer_address, $ret->customer_state, $ret->customer_pincode);
                }
                $items = [];
                foreach ($ret->items as $it) {
                    $masters .= $tx->unit_master($it->unit);
                    $masters .= $tx->stock_item_master($it->product_name, $it->unit, $it->hsn_code);
                    $items[] = ['stock_item' => $it->product_name, 'unit' => $it->unit, 'qty' => $it->qty, 'rate' => $it->rate,
                        'taxable' => $it->taxable_amt, 'cgst' => $it->cgst_amount, 'sgst' => $it->sgst_amount, 'igst' => $it->igst_amount];
                }
                $vouchers .= $tx->voucher_credit_note($ret->return_number, str_replace('-', '', $ret->return_date), $party, $items, $ret->total_amount, 'Sales return, auto-exported from ERP');
            }
        }

        if ($sections['purchase']) {
            foreach ($this->_tally_purchases($from, $to, $loc, $company_state) as $pur) {
                $masters .= $tx->ledger_party($pur->supplier_name, 'Sundry Creditors', 0, $pur->supplier_gstin, $pur->supplier_address, $pur->supplier_state, $pur->supplier_pincode);
                $items = [];
                foreach ($pur->items as $it) {
                    $masters .= $tx->unit_master($it->unit);
                    $masters .= $tx->stock_item_master($it->product_name, $it->unit, $it->hsn_code);
                    $items[] = ['stock_item' => $it->product_name, 'unit' => $it->unit, 'qty' => $it->qty, 'rate' => $it->rate,
                        'taxable' => $it->taxable_amt, 'cgst' => $it->cgst_amount, 'sgst' => $it->sgst_amount, 'igst' => $it->igst_amount];
                }
                $vouchers .= $tx->voucher_purchase($pur->purchase_no, str_replace('-', '', $pur->invoice_date), $pur->supplier_name, $items, $pur->total_amount, 'Auto-exported from ERP');
            }
        }

        if ($sections['purchase_return']) {
            foreach ($this->_tally_purchase_returns($from, $to, $company_state) as $ret) {
                $masters .= $tx->ledger_party($ret->supplier_name, 'Sundry Creditors', 0, $ret->supplier_gstin, $ret->supplier_address, $ret->supplier_state, $ret->supplier_pincode);
                $items = [];
                foreach ($ret->items as $it) {
                    $masters .= $tx->unit_master($it->unit);
                    $masters .= $tx->stock_item_master($it->product_name, $it->unit, $it->hsn_code);
                    $items[] = ['stock_item' => $it->product_name, 'unit' => $it->unit, 'qty' => $it->qty, 'rate' => $it->rate,
                        'taxable' => $it->taxable_amt, 'cgst' => $it->cgst_amount, 'sgst' => $it->sgst_amount, 'igst' => $it->igst_amount];
                }
                $vouchers .= $tx->voucher_debit_note($ret->prn_no, str_replace('-', '', $ret->return_date), $ret->supplier_name, $items, $ret->total_amount, 'Purchase return, auto-exported from ERP (GST split inferred from original purchase)');
            }
        }

        // $masters was accumulated above regardless (the builders dedupe by
        // name as they go); it is only emitted if the user wants the
        // referenced masters carried along with the vouchers.
        $xml = $tx->wrap_envelope($company_name, $with_masters ? $masters : '', $vouchers, 'Vouchers');

        header('Content-Type: text/xml; charset=utf-8');
        header('Content-Disposition: attachment;filename="Tally_Transactions_' . $from . '_to_' . $to . '.xml"');
        header('Cache-Control: max-age=0');
        echo $xml;
    }

    private function _tally_suppliers() {
        return $this->db->where('status', 1)->order_by('name', 'ASC')->get('suppliers')->result();
    }

    // $include_b2c: when false, only customers carrying a GSTIN — the same
    // ones the voucher export gives an individual ledger to.
    private function _tally_customers($include_b2c = false) {
        $this->db->where('status', 1);
        if (!$include_b2c) $this->db->where('gst_number IS NOT NULL', NULL, FALSE)->where("TRIM(gst_number) !=", '');
        return $this->db->order_by('name', 'ASC')->get('customers')->result();
    }

    private function _tally_products() {
        return $this->db->where('status', 1)->order_by('name', 'ASC')->get('products')->result();
    }

    // Groups sales rows into one object per invoice with an ->items[] array.
    private function _tally_sales($from, $to, $location_id) {
        $this->db->select("i.id AS invoice_id, i.invoice_no, i.invoice_date, i.total_amount, i.customer_id,
                c.name AS customer_name, c.gst_number AS customer_gstin, c.state AS customer_state,
                c.address AS customer_address, c.pincode AS customer_pincode,
                ii.product_name, ii.hsn_code, ii.unit, ii.qty, ii.rate, ii.taxable_amt, ii.cgst_amount, ii.sgst_amount, ii.igst_amount")
            ->from('invoices i')
            ->join('customers c', 'c.id = i.customer_id')
            ->join('invoice_items ii', 'ii.invoice_id = i.id')
            ->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)
            ->where('i.status !=', 'cancelled')
            ->order_by('i.invoice_date, i.id');
        if ($location_id) $this->db->where('i.location_id', $location_id);
        $rows = $this->db->get()->result();
        return $this->_group_by($rows, 'invoice_id');
    }

    private function _tally_sales_returns($from, $to) {
        $rows = $this->db->select("sr.id AS return_id, sr.return_number, sr.return_date, sr.total_amount, sr.customer_id,
                c.name AS customer_name, c.gst_number AS customer_gstin, c.state AS customer_state,
                c.address AS customer_address, c.pincode AS customer_pincode,
                sri.product_name, sri.hsn_code, sri.unit, sri.qty, sri.rate, sri.taxable_amt, sri.cgst_amount, sri.sgst_amount, sri.igst_amount")
            ->from('sales_returns sr')
            ->join('customers c', 'c.id = sr.customer_id')
            ->join('sales_return_items sri', 'sri.sales_return_id = sr.id')
            ->where('sr.return_date >=', $from)->where('sr.return_date <=', $to)
            ->where('sr.status', 'confirmed')
            ->order_by('sr.return_date, sr.id')
            ->get()->result();
        return $this->_group_by($rows, 'return_id');
    }

    private function _tally_purchases($from, $to, $location_id, $company_state) {
        $this->db->select("p.id AS purchase_id, p.purchase_no, p.invoice_date, p.total_amount, p.supplier_id,
                s.name AS supplier_name, s.gst_number AS supplier_gstin, s.state AS supplier_state,
                s.address AS supplier_address, s.pincode AS supplier_pincode,
                prod.name AS product_name, prod.hsn_code, pi.unit, pi.qty, pi.price AS rate, pi.taxable_amount AS taxable_amt, pi.gst_amount")
            ->from('purchases p')
            ->join('suppliers s', 's.id = p.supplier_id')
            ->join('purchase_items pi', 'pi.purchase_id = p.id')
            ->join('products prod', 'prod.id = pi.product_id')
            ->where('p.invoice_date >=', $from)->where('p.invoice_date <=', $to)
            ->order_by('p.invoice_date, p.id');
        if ($location_id) $this->db->where('p.location_id', $location_id);
        $rows = $this->db->get()->result();

        // purchases only stores a combined gst_amount (no CGST/SGST/IGST
        // split) — infer the split the same way GST law does: compare
        // supplier state to company state.
        foreach ($rows as $r) {
            $interstate = $this->_is_interstate($r->supplier_state, $company_state);
            if ($interstate) { $r->cgst_amount = 0; $r->sgst_amount = 0; $r->igst_amount = (float)$r->gst_amount; }
            else { $r->cgst_amount = round((float)$r->gst_amount / 2, 2); $r->sgst_amount = round((float)$r->gst_amount / 2, 2); $r->igst_amount = 0; }
        }
        return $this->_group_by($rows, 'purchase_id');
    }

    private function _tally_purchase_returns($from, $to, $company_state) {
        $rows = $this->db->select("pr.id AS return_id, pr.prn_no, pr.return_date, pr.total_amount, pr.supplier_id, pr.purchase_id,
                s.name AS supplier_name, s.gst_number AS supplier_gstin, s.state AS supplier_state,
                s.address AS supplier_address, s.pincode AS supplier_pincode,
                prod.name AS product_name, prod.hsn_code, prod.unit AS product_unit, pri.qty, pri.price AS rate, pri.total,
                MAX(orig.gst_percent) AS orig_gst_percent")
            ->from('purchase_returns pr')
            ->join('suppliers s', 's.id = pr.supplier_id')
            ->join('purchase_return_items pri', 'pri.prn_id = pr.id')
            ->join('products prod', 'prod.id = pri.product_id')
            ->join('purchase_items orig', 'orig.purchase_id = pr.purchase_id AND orig.product_id = pri.product_id', 'left')
            ->where('pr.return_date >=', $from)->where('pr.return_date <=', $to)
            ->group_by('pri.id')
            ->order_by('pr.return_date, pr.id')
            ->get()->result();

        // purchase_return_items has no GST columns at all — infer taxable/
        // GST split from the ORIGINAL purchase's rate for the same product
        // (falling back to the product master's current rate if that
        // product wasn't on the original purchase, e.g. a data-entry
        // mismatch), then split CGST/SGST vs IGST the same way as purchases.
        foreach ($rows as $r) {
            $gst_pct = (float)($r->orig_gst_percent ?? 0);
            $total = (float)$r->total;
            $taxable = $gst_pct > 0 ? round($total / (1 + $gst_pct / 100), 2) : $total;
            $gst_amt = round($total - $taxable, 2);
            $r->taxable_amt = $taxable;
            $r->unit = $r->product_unit ?: 'PCS';
            $interstate = $this->_is_interstate($r->supplier_state, $company_state);
            if ($interstate) { $r->cgst_amount = 0; $r->sgst_amount = 0; $r->igst_amount = $gst_amt; }
            else { $r->cgst_amount = round($gst_amt / 2, 2); $r->sgst_amount = round($gst_amt / 2, 2); $r->igst_amount = 0; }
        }
        return $this->_group_by($rows, 'return_id');
    }

    private function _is_interstate($party_state, $company_state) {
        return strtolower(trim((string)$party_state)) !== strtolower(trim((string)$company_state));
    }

    // Collapses a flat header+line-item result set into one object per
    // header id, with an ->items[] array of the line rows.
    private function _group_by(array $rows, $id_field) {
        $grouped = [];
        foreach ($rows as $row) {
            $id = $row->$id_field;
            if (!isset($grouped[$id])) {
                $header = clone $row;
                $header->items = [];
                $grouped[$id] = $header;
            }
            $grouped[$id]->items[] = $row;
        }
        return array_values($grouped);
    }

    // ============================================================
    // GSTR-2 (Purchase/Input Tax)
    // ============================================================
    public function gstr2() {
        $this->_rpt('frm_rpt_gst');
        $data['page_title'] = 'GSTR-2 Report (Purchase / Input Tax)';
        $month = $this->input->get('month', TRUE) ?: date('Y-m');
        $from  = $month . '-01';
        $to    = date('Y-m-t', strtotime($from));
        $data['month']    = $month;
        $data['from']     = $from;
        $data['to']       = $to;
        $data = array_merge($data, $this->_locations_data());
        $data['purchases'] = $this->Report_model->get_gstr2_purchases($from, $to);
        $data['summary']   = $this->Report_model->get_gstr2_summary($from, $to);
        $this->render('reports/gstr2', $data);
    }

    // ============================================================
    // GSTR-3B (Summary: Output - Input = Net Payable)
    // ============================================================
    public function gstr3b() {
        $this->_rpt('frm_rpt_gst');
        $data['page_title'] = 'GSTR-3B Summary';
        $month = $this->input->get('month', TRUE) ?: date('Y-m');
        $from  = $month . '-01';
        $to    = date('Y-m-t', strtotime($from));
        $data['month']   = $month;
        $data['from']    = $from;
        $data['to']      = $to;
        $data = array_merge($data, $this->_locations_data());
        $data['output']  = $this->Report_model->get_gstr3b_output($from, $to);
        $data['input']   = $this->Report_model->get_gstr3b_input($from, $to);
        // Credit notes reduce output tax (GSTR-3B Table 4(B))
        $cn_summary = $this->Sales_return_model->get_summary_for_gstr3b($from, $to);
        $data['credit_note_adj'] = $cn_summary;
        $eligible_itc_total = $data['input']['eligible_itc'] ?? ($data['input']['total'] ?? 0);
        $data['net']     = [
            'cgst' => ($data['output']['cgst'] ?? 0) - ($data['input']['cgst'] ?? 0) - (float)($cn_summary->cgst_amount ?? 0),
            'sgst' => ($data['output']['sgst'] ?? 0) - ($data['input']['sgst'] ?? 0) - (float)($cn_summary->sgst_amount ?? 0),
            'igst' => ($data['output']['igst'] ?? 0) - ($data['input']['igst'] ?? 0) - (float)($cn_summary->igst_amount ?? 0),
            'total'=> ($data['output']['total'] ?? 0) - (float)$eligible_itc_total - (float)($cn_summary->total_amount ?? 0),
        ];
        $this->render('reports/gstr3b', $data);
    }

    // ============================================================
    // Tax Breakup Report — date-wise sales split by GST slab (0/5/18/40%),
    // net of refunds (matches the "Net Sales" basis every other report in this
    // system settled on) — a refund reduces the slab total on the date the
    // refund itself was processed, not the original invoice's date.
    // Sourced from invoice_items/refund_items so every row foots against the
    // per-line GST already stored on each bill; cancelled invoices excluded.
    // ============================================================
    public function tax_breakup() {
        $this->_rpt('frm_rpt_gst');
        $from   = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to     = $this->input->get('to',   TRUE) ?: date('Y-m-d');
        $loc    = $this->_get_loc_id();
        $export = $this->input->get('export', TRUE);

        $sales_raw   = $this->Report_model->get_tax_breakup($from, $to, $loc);
        $refunds_raw = $this->Report_model->get_tax_breakup_refunds($from, $to, $loc);
        $bill_sales   = $this->Report_model->get_tax_breakup_invoice_totals($from, $to, $loc);
        $bill_refunds = $this->Report_model->get_tax_breakup_refund_totals($from, $to, $loc);
        $bill_sales_by_date = [];   foreach ($bill_sales as $r)   { $bill_sales_by_date[$r->dt]   = (float)$r->total; }
        $bill_refunds_by_date = []; foreach ($bill_refunds as $r) { $bill_refunds_by_date[$r->dt] = (float)$r->total; }

        // Any rate outside 0/5/18/40 (e.g. legacy 12%/28% lines) is folded into
        // Total Sales / Basic Cost so those two figures always foot correctly,
        // even though it isn't broken out into its own column below.
        $by_date = [];
        $other_txbl = 0; $other_tax = 0;
        $apply = function($rows, $sign) use (&$by_date, &$other_txbl, &$other_tax) {
            foreach ($rows as $r) {
                $d = $r->dt;
                if (!isset($by_date[$d])) {
                    $by_date[$d] = ['date'=>$d,'gst0'=>0,'txbl5'=>0,'tax5'=>0,'txbl18'=>0,'tax18'=>0,'txbl40'=>0,'tax40'=>0,'other_txbl'=>0,'other_tax'=>0];
                }
                $rate = round((float)$r->gst_percent, 2);
                $taxable = $sign * (float)$r->taxable;
                $tax = $sign * (float)$r->tax;
                if ($rate == 0.0)       { $by_date[$d]['gst0']  += $taxable; }
                elseif ($rate == 5.0)   { $by_date[$d]['txbl5'] += $taxable; $by_date[$d]['tax5']  += $tax; }
                elseif ($rate == 18.0)  { $by_date[$d]['txbl18']+= $taxable; $by_date[$d]['tax18'] += $tax; }
                elseif ($rate == 40.0)  { $by_date[$d]['txbl40']+= $taxable; $by_date[$d]['tax40'] += $tax; }
                else {
                    $by_date[$d]['other_txbl'] += $taxable; $by_date[$d]['other_tax'] += $tax;
                    $other_txbl += $taxable; $other_tax += $tax;
                }
            }
        };
        $apply($sales_raw, 1);
        $apply($refunds_raw, -1);
        // A date can have bill-level sales/refunds but (rarely) no item rows —
        // make sure it still gets a row instead of silently vanishing.
        foreach (array_keys($bill_sales_by_date + $bill_refunds_by_date) as $d) {
            if (!isset($by_date[$d])) {
                $by_date[$d] = ['date'=>$d,'gst0'=>0,'txbl5'=>0,'tax5'=>0,'txbl18'=>0,'tax18'=>0,'txbl40'=>0,'tax40'=>0,'other_txbl'=>0,'other_tax'=>0];
            }
        }
        ksort($by_date);

        $rows_data = [];
        $t = ['gst0'=>0,'txbl5'=>0,'tax5'=>0,'txbl18'=>0,'tax18'=>0,'txbl40'=>0,'tax40'=>0,'basic_cost'=>0,'total_sales'=>0];
        foreach ($by_date as $d => $row) {
            $basic_cost = $row['gst0'] + $row['txbl5'] + $row['txbl18'] + $row['txbl40'] + $row['other_txbl'];
            // "Total Sales" intentionally uses the same bill-level basis (invoice
            // total_amount, net of refunds) as the Sales Report's "Net Sales" card,
            // not basic_cost+tax — that item-level sum excludes invoice round-off
            // and bill-level discounts, which don't belong to any single GST slab
            // but do belong in a headline sales figure.
            $total_sales = ($bill_sales_by_date[$d] ?? 0) - ($bill_refunds_by_date[$d] ?? 0);
            $row['basic_cost']  = $basic_cost;
            $row['total_sales'] = $total_sales;
            $rows_data[] = $row;
            $t['gst0'] += $row['gst0']; $t['txbl5'] += $row['txbl5']; $t['tax5'] += $row['tax5'];
            $t['txbl18'] += $row['txbl18']; $t['tax18'] += $row['tax18'];
            $t['txbl40'] += $row['txbl40']; $t['tax40'] += $row['tax40'];
            $t['basic_cost'] += $basic_cost; $t['total_sales'] += $total_sales;
        }

        $title = 'Tax Breakup Report ' . $from . ' to ' . $to;
        if ($export === 'excel' || $export === 'pdf' || $export === 'email') {
            $headers = ['Date','Total Sales','GST 0%','Taxable 5%','Tax Amount 5%','Taxable 18%','Tax Amount 18%','Taxable 40%','Tax Amount 40%','Basic Cost'];
            $rows = [];
            foreach ($rows_data as $row) {
                $rows[] = [
                    date('d-M-Y', strtotime($row['date'])),
                    number_format($row['total_sales'],2), number_format($row['gst0'],2),
                    number_format($row['txbl5'],2), number_format($row['tax5'],2),
                    number_format($row['txbl18'],2), number_format($row['tax18'],2),
                    number_format($row['txbl40'],2), number_format($row['tax40'],2),
                    number_format($row['basic_cost'],2),
                ];
            }
            if (!empty($rows_data)) {
                $rows[] = ['TOTAL', number_format($t['total_sales'],2), number_format($t['gst0'],2),
                    number_format($t['txbl5'],2), number_format($t['tax5'],2),
                    number_format($t['txbl18'],2), number_format($t['tax18'],2),
                    number_format($t['txbl40'],2), number_format($t['tax40'],2),
                    number_format($t['basic_cost'],2)];
            }
            if ($export==='excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title,$headers,$rows); return; }
            if ($export==='pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title,$headers,$rows); return; }
            if ($export==='email') { $this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/tax_breakup?from='.$from.'&to='.$to)); return; }
        }

        $data['page_title'] = 'Tax Breakup Report';
        $data['from'] = $from; $data['to'] = $to;
        $data['rows'] = $rows_data;
        $data['totals'] = $t;
        $data['other_txbl'] = $other_txbl;
        $data['other_tax']  = $other_tax;
        $data = array_merge($data, $this->_locations_data());
        $data['export_route'] = 'reports/tax_breakup';
        $data['export_params'] = 'from='.$from.'&to='.$to.'&location_id='.$loc;
        $data['export_title'] = $title;
        $this->render('reports/tax_breakup', $data);
    }

    // ============================================================
    // GSTR-2B Reconciliation — match our purchases against the vendor-filed
    // GSTR-2B JSON (downloaded from the GST portal) to catch suppliers who
    // billed us but never filed, so their payment can be held.
    // ============================================================
    public function gst2b_reconciliation() {
        $this->_rpt('frm_rpt_gst');
        $data['page_title'] = 'GSTR-2B Reconciliation';

        // Upload a new month's JSON
        if ($this->input->server('REQUEST_METHOD') === 'POST' && !empty($_FILES['gst2b_file']['tmp_name'])) {
            $this->_handle_gst2b_upload();
            return;
        }

        $loc    = $this->_get_loc_id();
        $period = $this->input->get('period', TRUE) ?: date('Y-m');
        $export = $this->input->get('export', TRUE);

        $data['uploads']  = $this->Gst2b_model->get_uploads();
        $data['period']   = $period;
        $data['upload']   = $this->Gst2b_model->get_upload_by_period($period);
        $data = array_merge($data, $this->_locations_data());

        if ($data['upload']) {
            $result = $this->Gst2b_model->reconcile($period, $loc);
            $data['rows']    = $result['rows'];
            $data['summary'] = $result['summary'];
            // Note: supplier flagging happens once, at upload time (see
            // _handle_gst2b_upload) — not on every view of this report. If it
            // ran here too, a manual "Clear Flag" would be silently re-flagged
            // the next time anyone just looked at this page.
        } else {
            $data['rows'] = []; $data['summary'] = null;
        }

        $title = 'GSTR-2B Reconciliation ' . $period;
        if ($export === 'excel' || $export === 'pdf') {
            $headers = ['Status','Supplier','GSTIN','Our Invoice No','Our Date','Our Taxable','Our GST','Our Total','Filed Invoice No','Filed Date','Filed Taxable','Filed GST','Supplier Filed On'];
            $rows = [];
            foreach ($data['rows'] as $r) {
                $rows[] = [
                    strtoupper(str_replace('_',' ',$r['status'])),
                    $r['supplier_name'], $r['gstin'],
                    $r['our_invoice_no'], $r['our_date'] ? date('d-M-Y', strtotime($r['our_date'])) : '',
                    $r['our_taxable']!==null ? number_format($r['our_taxable'],2) : '',
                    $r['our_gst']!==null ? number_format($r['our_gst'],2) : '',
                    $r['our_total']!==null ? number_format($r['our_total'],2) : '',
                    $r['filed_invoice_no'], $r['filed_date'] ? date('d-M-Y', strtotime($r['filed_date'])) : '',
                    $r['filed_taxable']!==null ? number_format($r['filed_taxable'],2) : '',
                    $r['filed_gst']!==null ? number_format($r['filed_gst'],2) : '',
                    $r['supfildt'] ? date('d-M-Y', strtotime($r['supfildt'])) : '',
                ];
            }
            if ($export==='excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title,$headers,$rows); return; }
            if ($export==='pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title,$headers,$rows); return; }
        }

        $this->render('reports/gst2b_reconciliation', $data);
    }

    private function _handle_gst2b_upload() {
        $tmp = $_FILES['gst2b_file']['tmp_name'];
        $name = $_FILES['gst2b_file']['name'];
        $json = json_decode(file_get_contents($tmp), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->session->set_flashdata('error', 'Could not read that file — it is not valid JSON.');
            redirect('reports/gst2b_reconciliation');
        }

        $parsed = $this->Gst2b_model->parse_json($json);
        if (!$parsed) {
            $this->session->set_flashdata('error', 'That does not look like a GSTR-2B JSON file (expected data.docdata.b2b and data.gstin).');
            redirect('reports/gst2b_reconciliation');
        }

        $upload_id = $this->Gst2b_model->save_upload($parsed, $name, $this->session->userdata('user_id'));
        if ($upload_id === false) {
            $this->session->set_flashdata('error', 'Database error while saving the GSTR-2B data.');
            redirect('reports/gst2b_reconciliation');
        }

        // Flag suppliers now, once, based on this fresh data — not every time
        // someone views the report afterward (that would undo manual clears).
        $result = $this->Gst2b_model->reconcile($parsed['period'], $this->_get_loc_id());
        if (!empty($result['by_supplier'])) {
            $this->Gst2b_model->flag_suppliers($parsed['period'], $result['by_supplier']);
        }

        $this->log_activity('reports', 'gst2b_upload', 'Uploaded GSTR-2B for period ' . $parsed['period'] . ' (' . count($parsed['records']) . ' invoice records)', $upload_id, $parsed['period']);
        $this->session->set_flashdata('success', 'GSTR-2B for ' . $parsed['period'] . ' uploaded — ' . count($parsed['records']) . ' vendor invoice(s) loaded.');
        redirect('reports/gst2b_reconciliation?period=' . $parsed['period']);
    }

    // Manually clear a supplier's GST non-compliance flag once resolved.
    public function gst2b_clear_flag($supplier_id) {
        $this->_rpt('frm_rpt_gst');
        $this->Gst2b_model->clear_flag((int)$supplier_id, $this->session->userdata('user_id'));
        $this->log_activity('suppliers', 'gst_flag_cleared', 'Cleared GST non-compliance flag', (int)$supplier_id);
        $this->session->set_flashdata('success', 'Flag cleared.');
        redirect($this->input->server('HTTP_REFERER') ?: 'reports/gst2b_reconciliation');
    }

    // ============================================================
    // Export GST to Excel
    // ============================================================
    public function export_gst_excel() {
        $this->load->helper('download');
        $month = $this->input->get('month', TRUE) ?: date('Y-m');
        $from  = $month . '-01';
        $to    = date('Y-m-t', strtotime($from));
        $type  = $this->input->get('type', TRUE) ?: 'gstr1';

        $autoload = APPPATH . '../vendor/autoload.php';
        if (!file_exists($autoload)) {
            $this->session->set_flashdata('error', 'PhpSpreadsheet not installed.');
            redirect('reports/gstr1?month=' . $month);
        }
        require_once $autoload;

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()->setTitle("$type Report - $month");

        if ($type === 'gstr1') {
            $this->_excel_gstr1($spreadsheet, $month, $from, $to);
        } elseif ($type === 'gstr2') {
            $this->_excel_gstr2($spreadsheet, $month, $from, $to);
        } elseif ($type === 'gstr3b') {
            $this->_excel_gstr3b($spreadsheet, $month, $from, $to);
        }

        while (ob_get_level() > 0) { @ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . strtoupper($type) . '_' . $month . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function _excel_gstr1($spreadsheet, $month, $from, $to) {
        $sheet = $spreadsheet->getActiveSheet()->setTitle('B2B');
        $headers = ['Invoice No','Date','Customer','GSTIN','Taxable Amt','CGST','SGST','IGST','Total'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1);
            $sheet->setCellValue($col.'1', $h);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
        }
        $b2b = $this->Report_model->get_b2b_invoices($month);
        $r = 2;
        foreach ($b2b as $row) {
            $sheet->setCellValue('A'.$r, $row->invoice_no);
            $sheet->setCellValue('B'.$r, $row->invoice_date);
            $sheet->setCellValue('C'.$r, $row->customer_name??'');
            $sheet->setCellValue('D'.$r, $row->gst_number??'');
            $sheet->setCellValue('E'.$r, $row->taxable_amount);
            $sheet->setCellValue('F'.$r, $row->cgst??0);
            $sheet->setCellValue('G'.$r, $row->sgst??0);
            $sheet->setCellValue('H'.$r, $row->igst??0);
            $sheet->setCellValue('I'.$r, $row->total_amount);
            $r++;
        }
        // B2C sheet
        $spreadsheet->createSheet()->setTitle('B2C');
        $s2 = $spreadsheet->getSheetByName('B2C');
        $headers2 = ['Invoice No','Date','Customer','Mobile','Taxable Amt','GST','Total'];
        foreach ($headers2 as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1);
            $s2->setCellValue($col.'1', $h);
            $s2->getStyle($col.'1')->getFont()->setBold(true);
        }
        $b2c = $this->Report_model->get_b2c_invoices($month);
        $r2 = 2;
        foreach ($b2c as $row) {
            $s2->setCellValue('A'.$r2, $row->invoice_no);
            $s2->setCellValue('B'.$r2, $row->invoice_date);
            $s2->setCellValue('C'.$r2, $row->customer_name??'');
            $s2->setCellValue('D'.$r2, $row->phone??'');
            $s2->setCellValue('E'.$r2, $row->taxable_amount);
            $s2->setCellValue('F'.$r2, $row->total_gst);
            $s2->setCellValue('G'.$r2, $row->total_amount);
            $r2++;
        }
    }

    private function _excel_gstr2($spreadsheet, $month, $from, $to) {
        $sheet = $spreadsheet->getActiveSheet()->setTitle('GSTR2 Purchases');
        $headers = ['Purchase No','Date','Supplier','GSTIN','Taxable Amt','GST Amt','Total'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1);
            $sheet->setCellValue($col.'1', $h);
            $sheet->getStyle($col.'1')->getFont()->setBold(true);
        }
        $rows = $this->Report_model->get_gstr2_purchases($from, $to);
        $r = 2;
        foreach ($rows as $row) {
            $sheet->setCellValue('A'.$r, $row->purchase_no);
            $sheet->setCellValue('B'.$r, $row->invoice_date);
            $sheet->setCellValue('C'.$r, $row->supplier_name??'');
            $sheet->setCellValue('D'.$r, $row->gst_number??'');
            $sheet->setCellValue('E'.$r, ($row->total_amount-$row->total_gst));
            $sheet->setCellValue('F'.$r, $row->total_gst);
            $sheet->setCellValue('G'.$r, $row->total_amount);
            $r++;
        }
    }

    private function _excel_gstr3b($spreadsheet, $month, $from, $to) {
        $sheet = $spreadsheet->getActiveSheet()->setTitle('GSTR-3B');
        $output = $this->Report_model->get_gstr3b_output($from, $to);
        $input  = $this->Report_model->get_gstr3b_input($from, $to);
        $rows = [
            ['Description','CGST','SGST','IGST','Total'],
            ['Output Tax', $output['cgst']??0, $output['sgst']??0, $output['igst']??0, $output['total']??0],
            ['Input Tax Credit', $input['cgst']??0, $input['sgst']??0, $input['igst']??0, $input['total']??0],
            ['Net Payable', ($output['cgst']??0)-($input['cgst']??0), ($output['sgst']??0)-($input['sgst']??0), ($output['igst']??0)-($input['igst']??0), ($output['total']??0)-($input['total']??0)],
        ];
        foreach ($rows as $ri => $row) {
            foreach ($row as $ci => $val) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci+1);
                $sheet->setCellValue($col.($ri+1), $val);
                if ($ri===0 || $ri===3) $sheet->getStyle($col.($ri+1))->getFont()->setBold(true);
            }
        }
    }

    // ============================================================
    // GSTR-1 JSON Export (GST Portal / Tally format)
    // ============================================================
    public function gstr1_json() {
        $month = $this->input->get('month', TRUE) ?: date('Y-m');
        $from  = $month . '-01';
        $to    = date('Y-m-t', strtotime($from));

        // Company settings
        $company  = $this->Invoice_model->get_company_settings();
        $gstin    = $company['company_gstin'] ?? '';
        $fp       = date('m', strtotime($from)) . date('Y', strtotime($from)); // MMYYYY

        // â”€â”€ State code map (GST portal codes) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $state_codes = [
            'jammu'        => '01','himachal'     => '02','punjab'       => '03','chandigarh'   => '04',
            'uttarakhand'  => '05','haryana'      => '06','delhi'        => '07','rajasthan'    => '08',
            'uttar pradesh'=> '09','bihar'        => '10','sikkim'       => '11','arunachal'    => '12',
            'nagaland'     => '13','manipur'      => '14','mizoram'      => '15','tripura'      => '16',
            'meghalaya'    => '17','assam'        => '18','west bengal'  => '19','jharkhand'    => '20',
            'odisha'       => '21','chhattisgarh' => '22','madhya pradesh'=> '23','gujarat'    => '24',
            'dadra'        => '26','maharashtra'  => '27','karnataka'   => '29','goa'          => '30',
            'lakshadweep'  => '31','kerala'       => '32','tamil nadu'   => '33','puducherry'   => '34',
            'andaman'      => '35','telangana'    => '36','andhra pradesh'=> '37','ladakh'      => '38',
        ];

        // Helper: extract 2-digit state code from GSTIN or state name
        $get_pos = function($gstin_val, $state_name = '') use ($state_codes) {
            if ($gstin_val && strlen($gstin_val) >= 2) {
                return substr($gstin_val, 0, 2);
            }
            $lower = strtolower(trim($state_name));
            foreach ($state_codes as $key => $code) {
                if (strpos($lower, $key) !== false) return $code;
            }
            return '07'; // default Delhi
        };

        // â”€â”€ B2B: invoices to GST-registered customers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $b2b_rows = $this->db->query("
            SELECT i.invoice_no, i.invoice_date, i.total_amount, i.is_interstate,
                   c.gst_number AS ctin, c.state AS cust_state,
                   ii.hsn_code, ii.gst_percent,
                   SUM(ii.taxable_amt)                                                   AS txval,
                   SUM(CASE WHEN i.is_interstate=1 THEN ii.gst_amount ELSE 0 END)       AS iamt,
                   SUM(CASE WHEN i.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END)     AS camt,
                   SUM(CASE WHEN i.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END)     AS samt
            FROM invoice_items ii
            JOIN invoices i  ON i.id  = ii.invoice_id
            JOIN customers c ON c.id  = i.customer_id
            WHERE i.invoice_date BETWEEN ? AND ?
              AND i.status != 'cancelled'
              AND c.gst_number IS NOT NULL AND c.gst_number != ''
            GROUP BY i.id, ii.gst_percent
            ORDER BY c.gst_number, i.invoice_date, i.invoice_no
        ", [$from, $to])->result();

        // Build b2b array grouped by CTIN â†’ invoice â†’ rate-wise items
        $b2b_map = [];
        foreach ($b2b_rows as $r) {
            $ctin = strtoupper(trim($r->ctin));
            $inum = $r->invoice_no;
            if (!isset($b2b_map[$ctin])) $b2b_map[$ctin] = [];
            if (!isset($b2b_map[$ctin][$inum])) {
                $pos = $get_pos($ctin, $r->cust_state);
                $b2b_map[$ctin][$inum] = [
                    'inum'     => $inum,
                    'idt'      => date('d-m-Y', strtotime($r->invoice_date)),
                    'val'      => (float)round($r->total_amount, 2),
                    'pos'      => $pos,
                    'rchrg'    => 'N',
                    'inv_typ'  => 'R',
                    'itms'     => [],
                ];
            }
            $b2b_map[$ctin][$inum]['itms'][] = [
                'num'     => count($b2b_map[$ctin][$inum]['itms']) + 1,
                'itm_det' => [
                    'txval' => (float)round($r->txval, 2),
                    'rt'    => (float)$r->gst_percent,
                    'iamt'  => (float)round($r->iamt, 2),
                    'camt'  => (float)round($r->camt, 2),
                    'samt'  => (float)round($r->samt, 2),
                    'csamt' => 0,
                ],
            ];
        }

        $b2b = [];
        foreach ($b2b_map as $ctin => $invs) {
            $b2b[] = ['ctin' => $ctin, 'inv' => array_values($invs)];
        }

        // â”€â”€ B2CS: B2C small (intrastate + interstate â‰¤ 2.5L) â”€â”€â”€â”€â”€â”€â”€
        $b2cs_rows = $this->db->query("
            SELECT i.is_interstate, ii.gst_percent,
                   SUM(ii.taxable_amt)                                                   AS txval,
                   SUM(CASE WHEN i.is_interstate=1 THEN ii.gst_amount ELSE 0 END)       AS iamt,
                   SUM(CASE WHEN i.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END)     AS camt,
                   SUM(CASE WHEN i.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END)     AS samt,
                   c.state
            FROM invoice_items ii
            JOIN invoices i  ON i.id  = ii.invoice_id
            JOIN customers c ON c.id  = i.customer_id
            WHERE i.invoice_date BETWEEN ? AND ?
              AND i.status != 'cancelled'
              AND (c.gst_number IS NULL OR c.gst_number = '')
              AND i.total_amount <= 250000
            GROUP BY i.is_interstate, ii.gst_percent, c.state
        ", [$from, $to])->result();

        $b2cs = [];
        foreach ($b2cs_rows as $r) {
            $pos = $get_pos('', $r->state);
            $b2cs[] = [
                'sply_ty' => $r->is_interstate ? 'INTER' : 'INTRA',
                'pos'     => $pos,
                'typ'     => 'OE',
                'rt'      => (float)$r->gst_percent,
                'txval'   => (float)round($r->txval, 2),
                'iamt'    => (float)round($r->iamt, 2),
                'camt'    => (float)round($r->camt, 2),
                'samt'    => (float)round($r->samt, 2),
                'csamt'   => 0,
            ];
        }

        // â”€â”€ B2CL: B2C large (interstate > 2.5L) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $b2cl_rows = $this->db->query("
            SELECT i.invoice_no, i.invoice_date, i.total_amount,
                   ii.gst_percent, c.state,
                   SUM(ii.taxable_amt)                                               AS txval,
                   SUM(ii.gst_amount)                                                AS iamt
            FROM invoice_items ii
            JOIN invoices i  ON i.id  = ii.invoice_id
            JOIN customers c ON c.id  = i.customer_id
            WHERE i.invoice_date BETWEEN ? AND ?
              AND i.status != 'cancelled'
              AND i.is_interstate = 1
              AND i.total_amount > 250000
              AND (c.gst_number IS NULL OR c.gst_number = '')
            GROUP BY i.id, ii.gst_percent
            ORDER BY i.invoice_date
        ", [$from, $to])->result();

        // Group b2cl by POS
        $b2cl_map = [];
        foreach ($b2cl_rows as $r) {
            $pos = $get_pos('', $r->state);
            if (!isset($b2cl_map[$pos])) $b2cl_map[$pos] = [];
            $inum = $r->invoice_no;
            if (!isset($b2cl_map[$pos][$inum])) {
                $b2cl_map[$pos][$inum] = [
                    'inum' => $inum,
                    'idt'  => date('d-m-Y', strtotime($r->invoice_date)),
                    'val'  => (float)round($r->total_amount, 2),
                    'itms' => [],
                ];
            }
            $b2cl_map[$pos][$inum]['itms'][] = [
                'num'     => count($b2cl_map[$pos][$inum]['itms']) + 1,
                'itm_det' => [
                    'txval' => (float)round($r->txval, 2),
                    'rt'    => (float)$r->gst_percent,
                    'iamt'  => (float)round($r->iamt, 2),
                    'csamt' => 0,
                ],
            ];
        }
        $b2cl = [];
        foreach ($b2cl_map as $pos => $invs) {
            $b2cl[] = ['pos' => $pos, 'inv' => array_values($invs)];
        }

        // â”€â”€ CDNR: Credit notes for registered buyers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $cdnr_rows = $this->db->query("
            SELECT sr.credit_note_no, sr.return_date, sr.total_amount, sr.is_interstate,
                   c.gst_number AS ctin,
                   sri.gst_percent,
                   SUM(sri.taxable_amt)                                                   AS txval,
                   SUM(CASE WHEN sr.is_interstate=1 THEN sri.gst_amount ELSE 0 END)      AS iamt,
                   SUM(CASE WHEN sr.is_interstate=0 THEN sri.gst_amount/2 ELSE 0 END)    AS camt,
                   SUM(CASE WHEN sr.is_interstate=0 THEN sri.gst_amount/2 ELSE 0 END)    AS samt
            FROM sales_return_items sri
            JOIN sales_returns sr ON sr.id = sri.sales_return_id
            JOIN customers c      ON c.id  = sr.customer_id
            WHERE sr.return_date BETWEEN ? AND ?
              AND sr.status != 'cancelled'
              AND c.gst_number IS NOT NULL AND c.gst_number != ''
            GROUP BY sr.id, sri.gst_percent
            ORDER BY c.gst_number, sr.return_date
        ", [$from, $to])->result();

        $cdnr_map = [];
        foreach ($cdnr_rows as $r) {
            $ctin = strtoupper(trim($r->ctin));
            $ntNum = $r->credit_note_no ?? $r->return_date;
            if (!isset($cdnr_map[$ctin])) $cdnr_map[$ctin] = [];
            if (!isset($cdnr_map[$ctin][$ntNum])) {
                $pos = $get_pos($ctin);
                $cdnr_map[$ctin][$ntNum] = [
                    'nt_num' => $ntNum,
                    'nt_dt'  => date('d-m-Y', strtotime($r->return_date)),
                    'ntty'   => 'C',
                    'rsn'    => 'Sales Return',
                    'val'    => (float)round($r->total_amount, 2),
                    'itms'   => [],
                ];
            }
            $cdnr_map[$ctin][$ntNum]['itms'][] = [
                'num'     => count($cdnr_map[$ctin][$ntNum]['itms']) + 1,
                'itm_det' => [
                    'txval' => (float)round($r->txval, 2),
                    'rt'    => (float)$r->gst_percent,
                    'iamt'  => (float)round($r->iamt, 2),
                    'camt'  => (float)round($r->camt, 2),
                    'samt'  => (float)round($r->samt, 2),
                    'csamt' => 0,
                ],
            ];
        }
        $cdnr = [];
        foreach ($cdnr_map as $ctin => $notes) {
            $cdnr[] = ['ctin' => $ctin, 'nt' => array_values($notes)];
        }

        // â”€â”€ HSN Summary â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $hsn_rows = $this->db->query("
            SELECT COALESCE(NULLIF(ii.hsn_code,''),'0000')         AS hsn_sc,
                   COALESCE(h.description, ii.hsn_code, '')        AS desc_val,
                   COALESCE(ii.unit, 'PCS')                        AS uqc,
                   ii.gst_percent                                  AS rt,
                   SUM(ii.qty)                                     AS qty,
                   ROUND(SUM(ii.taxable_amt),2)                    AS txval,
                   ROUND(SUM(CASE WHEN i.is_interstate=1 THEN ii.gst_amount ELSE 0 END),2)   AS iamt,
                   ROUND(SUM(CASE WHEN i.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END),2) AS camt,
                   ROUND(SUM(CASE WHEN i.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END),2) AS samt
            FROM invoice_items ii
            JOIN invoices i ON i.id = ii.invoice_id
            LEFT JOIN hsn_master h ON h.hsn_code = ii.hsn_code
            WHERE i.invoice_date BETWEEN ? AND ?
              AND i.status != 'cancelled'
            GROUP BY ii.hsn_code, ii.gst_percent
            ORDER BY ii.hsn_code
        ", [$from, $to])->result();

        $hsn_b2b = [];
        foreach ($hsn_rows as $idx => $r) {
            $hsn_b2b[] = [
                'num'       => $idx + 1,
                'hsn_sc'    => $r->hsn_sc,
                'desc'      => $r->desc_val,
                'user_desc' => $r->hsn_sc,
                'uqc'       => strtoupper($r->uqc),
                'qty'       => (float)$r->qty,
                'rt'        => (float)$r->rt,
                'txval'     => (float)$r->txval,
                'iamt'      => (float)$r->iamt,
                'camt'      => (float)$r->camt,
                'samt'      => (float)$r->samt,
                'csamt'     => 0,
            ];
        }

        // â”€â”€ Doc Issue Summary â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $doc_rows = $this->db->query("
            SELECT invoice_no FROM invoices
            WHERE invoice_date BETWEEN ? AND ? AND status != 'cancelled'
            ORDER BY invoice_no ASC
        ", [$from, $to])->result_array();

        $doc_issue = [];
        if (!empty($doc_rows)) {
            $inv_nos   = array_column($doc_rows, 'invoice_no');
            $cancelled = (int)$this->db->where('invoice_date >=', $from)
                ->where('invoice_date <=', $to)->where('status', 'cancelled')
                ->count_all_results('invoices');
            $doc_issue = [
                'doc_det' => [[
                    'doc_num' => 1,
                    'docs'    => [[
                        'num'       => 1,
                        'from'      => reset($inv_nos),
                        'to'        => end($inv_nos),
                        'totnum'    => count($inv_nos),
                        'cancel'    => $cancelled,
                        'net_issue' => count($inv_nos) - $cancelled,
                    ]],
                ]],
            ];
        }

        // â”€â”€ Assemble final JSON â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $gstr1 = ['gstin' => strtoupper($gstin), 'fp' => $fp];
        if (!empty($b2b))  $gstr1['b2b']  = $b2b;
        if (!empty($b2cs)) $gstr1['b2cs'] = $b2cs;
        if (!empty($b2cl)) $gstr1['b2cl'] = $b2cl;
        if (!empty($cdnr)) $gstr1['cdnr'] = $cdnr;
        if (!empty($hsn_b2b)) $gstr1['hsn'] = ['hsn_b2b' => $hsn_b2b];
        if (!empty($doc_issue)) $gstr1['doc_issue'] = $doc_issue;

        $json     = json_encode($gstr1, JSON_UNESCAPED_UNICODE);
        $filename = strtoupper($gstin) . '_GSTR1_' . strtoupper(date('M-Y', strtotime($from))) . '_' . date('dmY') . '.json';

        $this->load->helper('download');
        force_download($filename, $json);
    }

    // ============================================================
    // GSTR-1 Excel Export in "GST Returns Offline Tool" format
    // Sheets: b2b,sez,de | b2cs | hsn(b2c) | hsn(b2b) | docs
    // ============================================================
    public function export_gstr1_offline() {
        $month = $this->input->get('month', TRUE) ?: date('Y-m');
        $from  = $month . '-01';
        $to    = date('Y-m-t', strtotime($from));

        $autoload = APPPATH . '../vendor/autoload.php';
        if (!file_exists($autoload)) {
            $this->session->set_flashdata('error', 'PhpSpreadsheet not installed.');
            redirect('reports/gstr1?month=' . $month);
        }
        require_once $autoload;

        $company = $this->Invoice_model->get_company_settings();
        $gstin   = strtoupper($company['company_gstin'] ?? '');

        $state_codes = [
            'jammu'        => '01','himachal'     => '02','punjab'       => '03','chandigarh'   => '04',
            'uttarakhand'  => '05','haryana'      => '06','delhi'        => '07','rajasthan'    => '08',
            'uttar pradesh'=> '09','bihar'        => '10','sikkim'       => '11','arunachal'    => '12',
            'nagaland'     => '13','manipur'      => '14','mizoram'      => '15','tripura'      => '16',
            'meghalaya'    => '17','assam'        => '18','west bengal'  => '19','jharkhand'    => '20',
            'odisha'       => '21','chhattisgarh' => '22','madhya pradesh'=> '23','gujarat'    => '24',
            'dadra'        => '26','maharashtra'  => '27','karnataka'   => '29','goa'          => '30',
            'lakshadweep'  => '31','kerala'       => '32','tamil nadu'   => '33','puducherry'   => '34',
            'andaman'      => '35','telangana'    => '36','andhra pradesh'=> '37','ladakh'      => '38',
        ];
        $state_names = [
            '01'=>'Jammu and Kashmir','02'=>'Himachal Pradesh','03'=>'Punjab','04'=>'Chandigarh','05'=>'Uttarakhand',
            '06'=>'Haryana','07'=>'Delhi','08'=>'Rajasthan','09'=>'Uttar Pradesh','10'=>'Bihar','11'=>'Sikkim',
            '12'=>'Arunachal Pradesh','13'=>'Nagaland','14'=>'Manipur','15'=>'Mizoram','16'=>'Tripura','17'=>'Meghalaya',
            '18'=>'Assam','19'=>'West Bengal','20'=>'Jharkhand','21'=>'Odisha','22'=>'Chhattisgarh','23'=>'Madhya Pradesh',
            '24'=>'Gujarat','26'=>'Dadra and Nagar Haveli and Daman and Diu','27'=>'Maharashtra','29'=>'Karnataka',
            '30'=>'Goa','31'=>'Lakshadweep','32'=>'Kerala','33'=>'Tamil Nadu','34'=>'Puducherry',
            '35'=>'Andaman and Nicobar Islands','36'=>'Telangana','37'=>'Andhra Pradesh','38'=>'Ladakh',
        ];
        $company_pos_code = ($gstin && strlen($gstin) >= 2) ? substr($gstin, 0, 2) : '06';
        $get_pos_code = function($gstin_val, $state_name = '') use ($state_codes, $company_pos_code) {
            if ($gstin_val && strlen($gstin_val) >= 2) return substr($gstin_val, 0, 2);
            $lower = strtolower(trim($state_name));
            foreach ($state_codes as $key => $code) {
                if (strpos($lower, $key) !== false) return $code;
            }
            return $company_pos_code;
        };
        $pos_label = function($code) use ($state_names) {
            return $code . '-' . ($state_names[$code] ?? 'Other Territory');
        };

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        // ---------- b2b,sez,de ----------
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('b2b,sez,de');
        $sheet->setCellValue('A1', 'Summary For B2B, SEZ, DE (4A, 4B, 6B, 6C)');
        $sheet->setCellValue('A2', 'No. of Recipients');
        $sheet->setCellValue('C2', 'No. of Invoices');
        $sheet->setCellValue('E2', 'Total Invoice Value');
        $sheet->setCellValue('L2', 'Total Taxable Value');
        $sheet->setCellValue('M2', 'Total Cess');

        $b2b_headers = ['GSTIN/UIN of Recipient','Receiver Name','Invoice Number','Invoice date','Invoice Value',
                    'Place Of Supply','Reverse Charge','Applicable % of Tax Rate','Invoice Type','E-Commerce GSTIN','Rate','Taxable Value','Cess Amount'];
        foreach ($b2b_headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1);
            $sheet->setCellValue($col.'4', $h);
            $sheet->getStyle($col.'4')->getFont()->setBold(true);
        }

        $b2b_rows = $this->db->query("
            SELECT i.id AS invoice_id, i.invoice_no, i.invoice_date, i.total_amount AS invoice_value,
                   c.name AS customer_name, c.gst_number AS ctin, c.state AS cust_state,
                   ii.gst_percent AS rate, SUM(ii.taxable_amt) AS taxable_value
            FROM invoice_items ii
            JOIN invoices i  ON i.id = ii.invoice_id
            JOIN customers c ON c.id = i.customer_id
            WHERE i.invoice_date BETWEEN ? AND ?
              AND i.status != 'cancelled'
              AND c.gst_number IS NOT NULL AND c.gst_number != ''
            GROUP BY i.id, ii.gst_percent
            ORDER BY c.gst_number, i.id, ii.gst_percent
        ", [$from, $to])->result();

        $r = 5;
        $recipients = []; $invoice_ids = []; $total_invoice_value = 0.0; $total_taxable = 0.0;
        foreach ($b2b_rows as $row) {
            $pos = $pos_label($get_pos_code($row->ctin, $row->cust_state));
            $sheet->setCellValue('A'.$r, strtoupper($row->ctin));
            $sheet->setCellValue('B'.$r, $row->customer_name);
            $sheet->setCellValue('C'.$r, $row->invoice_no);
            $sheet->setCellValue('D'.$r, date('d-M-Y', strtotime($row->invoice_date)));
            $sheet->setCellValue('E'.$r, (float)round($row->invoice_value, 2));
            $sheet->setCellValue('F'.$r, $pos);
            $sheet->setCellValue('G'.$r, 'N');
            $sheet->setCellValue('H'.$r, '');
            $sheet->setCellValue('I'.$r, 'Regular');
            $sheet->setCellValue('J'.$r, '');
            $sheet->setCellValue('K'.$r, (float)$row->rate);
            $sheet->setCellValue('L'.$r, (float)round($row->taxable_value, 2));
            $sheet->setCellValue('M'.$r, 0);
            $r++;
            $recipients[strtoupper($row->ctin)] = true;
            if (!isset($invoice_ids[$row->invoice_id])) {
                $invoice_ids[$row->invoice_id] = true;
                $total_invoice_value += (float)$row->invoice_value;
            }
            $total_taxable += (float)$row->taxable_value;
        }
        $sheet->setCellValue('A3', count($recipients));
        $sheet->setCellValue('C3', count($invoice_ids));
        $sheet->setCellValue('E3', round($total_invoice_value, 2));
        $sheet->setCellValue('L3', round($total_taxable, 2));
        $sheet->setCellValue('M3', 0);
        $this->_style_gstr1_sheet($sheet, 'M', max($r-1, 4), ['E'=>'#,##0.00','K'=>'0.00','L'=>'#,##0.00','M'=>'#,##0.00']);

        // ---------- b2cs ----------
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('b2cs');
        $sheet->setCellValue('A1', 'Summary For B2CS(7)');
        $sheet->setCellValue('E2', 'Total Taxable Value');
        $sheet->setCellValue('F2', 'Total Cess');

        $b2cs_headers = ['Type','Place Of Supply','Applicable % of Tax Rate','Rate','Taxable Value','Cess Amount','E-Commerce GSTIN'];
        foreach ($b2cs_headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1);
            $sheet->setCellValue($col.'4', $h);
            $sheet->getStyle($col.'4')->getFont()->setBold(true);
        }

        $b2cs_rows = $this->db->query("
            SELECT i.is_interstate, c.state AS cust_state, ii.gst_percent AS rate,
                   SUM(ii.taxable_amt) AS taxable_value
            FROM invoice_items ii
            JOIN invoices i  ON i.id = ii.invoice_id
            JOIN customers c ON c.id = i.customer_id
            WHERE i.invoice_date BETWEEN ? AND ?
              AND i.status != 'cancelled'
              AND (c.gst_number IS NULL OR c.gst_number = '')
            GROUP BY i.is_interstate, c.state, ii.gst_percent
        ", [$from, $to])->result();

        $merged = [];
        foreach ($b2cs_rows as $row) {
            $pos_code = $row->is_interstate ? $get_pos_code('', $row->cust_state) : $company_pos_code;
            $key = $pos_code . '|' . $row->rate;
            if (!isset($merged[$key])) $merged[$key] = ['pos'=>$pos_code, 'rate'=>$row->rate, 'taxable'=>0.0];
            $merged[$key]['taxable'] += (float)$row->taxable_value;
        }
        ksort($merged);
        $r = 5;
        $total_taxable2 = 0.0;
        foreach ($merged as $m) {
            $sheet->setCellValue('A'.$r, 'OE');
            $sheet->setCellValue('B'.$r, $pos_label($m['pos']));
            $sheet->setCellValue('C'.$r, '');
            $sheet->setCellValue('D'.$r, (float)$m['rate']);
            $sheet->setCellValue('E'.$r, round($m['taxable'], 2));
            $sheet->setCellValue('F'.$r, 0);
            $sheet->setCellValue('G'.$r, '');
            $r++;
            $total_taxable2 += $m['taxable'];
        }
        $sheet->setCellValue('E3', round($total_taxable2, 2));
        $sheet->setCellValue('F3', 0);
        $this->_style_gstr1_sheet($sheet, 'G', max($r-1, 4), ['D'=>'0.00','E'=>'#,##0.00','F'=>'#,##0.00']);

        // ---------- hsn(b2c) & hsn(b2b) ----------
        $this->_build_gstr1_hsn_sheet($spreadsheet, 'b2c', 'B2C Sales', $from, $to);
        $this->_build_gstr1_hsn_sheet($spreadsheet, 'b2b', 'B2B Sales', $from, $to);

        // ---------- docs ----------
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('docs');
        $sheet->setCellValue('A1', 'Summary of documents issued during the tax period (13)');
        $sheet->setCellValue('D2', 'Total Number');
        $sheet->setCellValue('E2', 'Total Cancelled');
        $doc_headers = ['Nature of Document','Sr. No. From','Sr. No. To','Total Number','Cancelled'];
        foreach ($doc_headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1);
            $sheet->setCellValue($col.'4', $h);
            $sheet->getStyle($col.'4')->getFont()->setBold(true);
        }
        $doc_rows = $this->db->query("
            SELECT invoice_no FROM invoices
            WHERE invoice_date BETWEEN ? AND ? AND status != 'cancelled'
            ORDER BY id ASC
        ", [$from, $to])->result_array();
        $total_docs = count($doc_rows);
        $cancelled  = (int)$this->db->where('invoice_date >=', $from)
            ->where('invoice_date <=', $to)->where('status', 'cancelled')
            ->count_all_results('invoices');
        if ($total_docs > 0) {
            $inv_nos = array_column($doc_rows, 'invoice_no');
            $sheet->setCellValue('A5', 'Invoices for outward supply');
            $sheet->setCellValue('B5', reset($inv_nos));
            $sheet->setCellValue('C5', end($inv_nos));
            $sheet->setCellValue('D5', $total_docs);
            $sheet->setCellValue('E5', $cancelled);
        }
        $sheet->setCellValue('D3', $total_docs);
        $sheet->setCellValue('E3', $cancelled);
        $this->_style_gstr1_sheet($sheet, 'E', max($total_docs > 0 ? 5 : 4, 4), ['D'=>'#,##0','E'=>'#,##0']);

        // Reorder + activate the first sheet so the workbook opens on b2b,sez,de
        $spreadsheet->setActiveSheetIndex(0);

        while (ob_get_level() > 0) { @ob_end_clean(); }
        $filename = ($gstin ?: 'GSTR1') . '_Offline_' . strtoupper(date('M-Y', strtotime($from))) . '_' . date('dmY') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // Builds one HSN summary sheet ('hsn(b2c)' or 'hsn(b2b)') for export_gstr1_offline()
    private function _build_gstr1_hsn_sheet($spreadsheet, $key, $title_suffix, $from, $to) {
        $uqc_map = ['PCS'=>'PCS-PIECES','KG'=>'KGS-KILOGRAMS','LTR'=>'LTR-LITRE','PKT'=>'PAC-PACKS','BOX'=>'BOX-BOX','CRT'=>'BOX-BOX'];

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('hsn('.$key.')');
        $sheet->setCellValue('A1', 'Summary For HSN(12) - '.$title_suffix);
        $sheet->setCellValue('A2', 'No. of HSN');
        $sheet->setCellValue('E2', 'Total Value');
        $sheet->setCellValue('G2', 'Total Taxable Value');
        $sheet->setCellValue('H2', 'Total Integrated Tax');
        $sheet->setCellValue('I2', 'Total Central Tax');
        $sheet->setCellValue('J2', 'Total State/UT Tax');
        $sheet->setCellValue('K2', 'Total Cess');

        $headers = ['HSN','Description','UQC','Total Quantity','Total Value','Rate','Taxable Value',
                    'Integrated Tax Amount','Central Tax Amount','State/UT Tax Amount','Cess Amount'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1);
            $sheet->setCellValue($col.'4', $h);
            $sheet->getStyle($col.'4')->getFont()->setBold(true);
        }

        $gst_filter = ($key === 'b2b')
            ? "AND c.gst_number IS NOT NULL AND c.gst_number != ''"
            : "AND (c.gst_number IS NULL OR c.gst_number = '')";

        $rows = $this->db->query("
            SELECT COALESCE(NULLIF(ii.hsn_code,''),'0000') AS hsn_code,
                   COALESCE(h.description, ii.hsn_code, '') AS hsn_desc,
                   COALESCE(ii.unit, 'PCS') AS unit,
                   ii.gst_percent AS rate,
                   SUM(ii.qty) AS qty,
                   SUM(ii.total) AS total_value,
                   SUM(ii.taxable_amt) AS taxable_value,
                   SUM(CASE WHEN i.is_interstate=1 THEN ii.gst_amount ELSE 0 END) AS igst,
                   SUM(CASE WHEN i.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END) AS cgst,
                   SUM(CASE WHEN i.is_interstate=0 THEN ii.gst_amount/2 ELSE 0 END) AS sgst
            FROM invoice_items ii
            JOIN invoices i ON i.id = ii.invoice_id
            JOIN customers c ON c.id = i.customer_id
            LEFT JOIN hsn_master h ON h.hsn_code = ii.hsn_code
            WHERE i.invoice_date BETWEEN ? AND ?
              AND i.status != 'cancelled'
              {$gst_filter}
            GROUP BY ii.hsn_code, ii.gst_percent
            ORDER BY ii.hsn_code, ii.gst_percent
        ", [$from, $to])->result();

        $r = 5;
        $tot_value = 0.0; $tot_taxable = 0.0; $tot_igst = 0.0; $tot_cgst = 0.0; $tot_sgst = 0.0;
        foreach ($rows as $row) {
            $uqc = $uqc_map[strtoupper($row->unit)] ?? 'OTH-OTHERS';
            $sheet->setCellValue('A'.$r, $row->hsn_code);
            $sheet->setCellValue('B'.$r, $row->hsn_desc);
            $sheet->setCellValue('C'.$r, $uqc);
            $sheet->setCellValue('D'.$r, (float)$row->qty);
            $sheet->setCellValue('E'.$r, round((float)$row->total_value, 2));
            $sheet->setCellValue('F'.$r, (float)$row->rate);
            $sheet->setCellValue('G'.$r, round((float)$row->taxable_value, 2));
            $sheet->setCellValue('H'.$r, round((float)$row->igst, 2));
            $sheet->setCellValue('I'.$r, round((float)$row->cgst, 2));
            $sheet->setCellValue('J'.$r, round((float)$row->sgst, 2));
            $sheet->setCellValue('K'.$r, 0);
            $r++;
            $tot_value   += (float)$row->total_value;
            $tot_taxable += (float)$row->taxable_value;
            $tot_igst    += (float)$row->igst;
            $tot_cgst    += (float)$row->cgst;
            $tot_sgst    += (float)$row->sgst;
        }
        $sheet->setCellValue('A3', count($rows));
        $sheet->setCellValue('E3', round($tot_value, 2));
        $sheet->setCellValue('G3', round($tot_taxable, 2));
        $sheet->setCellValue('H3', round($tot_igst, 2));
        $sheet->setCellValue('I3', round($tot_cgst, 2));
        $sheet->setCellValue('J3', round($tot_sgst, 2));
        $sheet->setCellValue('K3', 0);
        $this->_style_gstr1_sheet($sheet, 'K', max($r-1, 4), [
            'D'=>'#,##0.000','E'=>'#,##0.00','F'=>'0.00','G'=>'#,##0.00','H'=>'#,##0.00','I'=>'#,##0.00','J'=>'#,##0.00','K'=>'#,##0.00',
        ]);
    }

    // Applies a consistent professional theme (title banner, summary band, header band,
    // banded rows, number formats, frozen header, autosize) to a GSTR-1 offline-tool sheet.
    private function _style_gstr1_sheet($sheet, $lastCol, $lastRow, array $colFormats = []) {
        $fill  = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;
        $thin  = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;
        $hCtr  = \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER;
        $vCtr  = \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER;

        // Title banner (row 1)
        $sheet->mergeCells('A1:'.$lastCol.'1');
        $sheet->getStyle('A1:'.$lastCol.'1')->getFill()->setFillType($fill)->getStartColor()->setRGB('1F3864');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1')->getAlignment()->setVertical($vCtr);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Summary band (rows 2-3: labels then values)
        $sheet->getStyle('A2:'.$lastCol.'3')->getFill()->setFillType($fill)->getStartColor()->setRGB('D9E2F3');
        $sheet->getStyle('A2:'.$lastCol.'2')->getFont()->setBold(true)->getColor()->setRGB('1F3864');
        $sheet->getStyle('A3:'.$lastCol.'3')->getFont()->setBold(true)->getColor()->setRGB('203864');
        $sheet->getStyle('A3:'.$lastCol.'3')->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM)->getColor()->setRGB('1F3864');

        // Column header band (row 4)
        $sheet->getStyle('A4:'.$lastCol.'4')->getFill()->setFillType($fill)->getStartColor()->setRGB('2E5395');
        $sheet->getStyle('A4:'.$lastCol.'4')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A4:'.$lastCol.'4')->getAlignment()->setHorizontal($hCtr)->setVertical($vCtr)->setWrapText(true);
        $sheet->getRowDimension(4)->setRowHeight(28);

        // Data rows: borders + banding
        if ($lastRow >= 5) {
            $sheet->getStyle('A5:'.$lastCol.$lastRow)->getBorders()->getAllBorders()->setBorderStyle($thin)->getColor()->setRGB('D0D7E5');
            for ($rr = 5; $rr <= $lastRow; $rr++) {
                if ($rr % 2 === 0) {
                    $sheet->getStyle('A'.$rr.':'.$lastCol.$rr)->getFill()->setFillType($fill)->getStartColor()->setRGB('F4F7FC');
                }
            }
        }

        // Number formats (summary value row + data rows)
        foreach ($colFormats as $col => $format) {
            $sheet->getStyle($col.'3:'.$col.max(3, $lastRow))->getNumberFormat()->setFormatCode($format);
        }

        $sheet->freezePane('A5');
        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
    }

    public function run_report($type = null) {
        if (!$type) redirect('reports');

        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to',   TRUE) ?: date('Y-m-d');
        $cashier_id = (int)$this->input->get('cashier_id', TRUE);
        $location_id = $this->_get_loc_id();

        $data['type']        = $type;
        $data['from']        = $from;
        $data['to']          = $to;
        $data['cashier_id']  = $cashier_id;
        $data['location_id'] = $location_id;
        $data['cashiers']    = $this->db->where('role', 'cashier')->get('users')->result();
        $data['company']     = $this->Report_model->get_company_settings();
        $loc_data = $this->_locations_data();
        $data['locations']   = $loc_data['locations'];

        // Fetch data based on report type
        $title = '';
        $headers = [];
        $rows = [];
        
        switch ($type) {
            case 'day_open':
                $title = 'Day Open Report';
                $headers = ['Business Date', 'Opened By', 'Opened At', 'Status'];
                $q = $this->db->select('b.business_date, u.name as opened_by_name, b.opened_at, b.status')
                              ->from('business_days b')
                              ->join('users u', 'u.id = b.opened_by')
                              ->where('b.business_date >=', $from)
                              ->where('b.business_date <=', $to)
                              ->order_by('b.business_date', 'DESC')
                              ->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['business_date'], $r['opened_by_name'], $r['opened_at'], strtoupper($r['status'])];
                }
                break;

            case 'day_close':
                $title = 'Day Close Report';
                $headers = ['Business Date', 'Closed By', 'Closed At', 'Total Sales (₹)', 'Total Cash (₹)', 'Total UPI (₹)', 'Total Card (₹)', 'Shortage (₹)', 'Excess (₹)'];
                $q = $this->db->select('b.business_date, u.name as closed_by_name, b.closed_at, b.total_sales, b.total_cash, b.total_upi, b.total_card, b.total_shortage, b.total_excess')
                              ->from('business_days b')
                              ->join('users u', 'u.id = b.closed_by')
                              ->where('b.status', 'closed')
                              ->where('b.business_date >=', $from)
                              ->where('b.business_date <=', $to)
                              ->order_by('b.business_date', 'DESC')
                              ->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['business_date'], $r['closed_by_name'], $r['closed_at'], number_format($r['total_sales'], 2), number_format($r['total_cash'], 2), number_format($r['total_upi'], 2), number_format($r['total_card'], 2), number_format($r['total_shortage'], 2), number_format($r['total_excess'], 2)];
                }
                break;

            case 'shift_open':
                $title = 'Shift Open Report';
                $headers = ['Shift ID', 'Cashier Name', 'Business Date', 'Opened At', 'Opening Cash (₹)', 'Remarks'];
                $this->db->select('s.id, u.name as cashier_name, s.business_date, s.opened_at, s.opening_cash, s.remarks')
                         ->from('cashier_shifts s')
                         ->join('users u', 'u.id = s.cashier_id')
                         ->where('s.business_date >=', $from)
                         ->where('s.business_date <=', $to);
                if ($cashier_id) $this->db->where('s.cashier_id', $cashier_id);
                if ($location_id) $this->db->where('s.location_id', $location_id);
                $q = $this->db->order_by('s.id', 'DESC')->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = ['Shift #' . $r['id'], $r['cashier_name'], $r['business_date'], $r['opened_at'], number_format($r['opening_cash'], 2), $r['remarks']];
                }
                break;

            case 'shift_close':
                $title = 'Shift Close Report';
                $headers = ['Shift ID', 'Cashier Name', 'Business Date', 'Closed At', 'System Cash (₹)', 'Actual Cash (₹)', 'Difference (₹)', 'Shortage (₹)', 'Excess (₹)'];
                $this->db->select('s.id, u.name as cashier_name, s.business_date, s.closed_at, s.system_cash, s.actual_cash, s.diff_cash, s.short_amount, s.excess_amount')
                         ->from('cashier_shifts s')
                         ->join('users u', 'u.id = s.cashier_id')
                         ->where('s.status', 'closed')
                         ->where('s.business_date >=', $from)
                         ->where('s.business_date <=', $to);
                if ($cashier_id) $this->db->where('s.cashier_id', $cashier_id);
                if ($location_id) $this->db->where('s.location_id', $location_id);
                $q = $this->db->order_by('s.id', 'DESC')->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = ['Shift #' . $r['id'], $r['cashier_name'], $r['business_date'], $r['closed_at'], number_format($r['system_cash'], 2), number_format($r['actual_cash'], 2), number_format($r['diff_cash'], 2), number_format($r['short_amount'], 2), number_format($r['excess_amount'], 2)];
                }
                break;

            case 'cashier_collection':
                $title = 'Cashier Collection Report';
                $headers = ['Cashier Name', 'Shifts Worked', 'Total Opening (₹)', 'Actual Cash (₹)', 'Actual UPI (₹)', 'Actual Card (₹)', 'Total Short (₹)', 'Total Excess (₹)'];
                $this->db->select('u.name as cashier_name, COUNT(s.id) as total_shifts, SUM(s.opening_cash) as total_opening, SUM(s.actual_cash) as total_actual_cash, SUM(s.actual_upi) as total_actual_upi, SUM(s.actual_card) as total_actual_card, SUM(s.short_amount) as total_short, SUM(s.excess_amount) as total_excess')
                         ->from('cashier_shifts s')
                         ->join('users u', 'u.id = s.cashier_id')
                         ->where('s.status', 'closed')
                         ->where('s.business_date >=', $from)
                         ->where('s.business_date <=', $to);
                if ($cashier_id) $this->db->where('s.cashier_id', $cashier_id);
                if ($location_id) $this->db->where('s.location_id', $location_id);
                $q = $this->db->group_by('s.cashier_id')->order_by('cashier_name', 'ASC')->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['cashier_name'], $r['total_shifts'], number_format($r['total_opening'], 2), number_format($r['total_actual_cash'], 2), number_format($r['total_actual_upi'], 2), number_format($r['total_actual_card'], 2), number_format($r['total_short'], 2), number_format($r['total_excess'], 2)];
                }
                break;

            case 'short_excess':
                $title = 'Short/Excess Report';
                $headers = ['Shift ID', 'Cashier Name', 'Business Date', 'Shortage (₹)', 'Excess (₹)', 'Closed At'];
                $this->db->select('s.id, u.name as cashier_name, s.business_date, s.short_amount, s.excess_amount, s.closed_at')
                         ->from('cashier_shifts s')
                         ->join('users u', 'u.id = s.cashier_id')
                         ->where('s.status', 'closed')
                         ->group_start()
                            ->where('s.short_amount >', 0)
                            ->or_where('s.excess_amount >', 0)
                         ->group_end()
                         ->where('s.business_date >=', $from)
                         ->where('s.business_date <=', $to);
                if ($cashier_id) $this->db->where('s.cashier_id', $cashier_id);
                if ($location_id) $this->db->where('s.location_id', $location_id);
                $q = $this->db->order_by('s.id', 'DESC')->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = ['Shift #' . $r['id'], $r['cashier_name'], $r['business_date'], number_format($r['short_amount'], 2), number_format($r['excess_amount'], 2), $r['closed_at']];
                }
                break;

            case 'payment_mode_summary':
                $title = 'Payment Mode Summary';
                $modes = ['cash' => 'Cash', 'upi' => 'UPI', 'card' => 'Card', 'neft' => 'Bank/NEFT', 'cheque' => 'Cheque', 'wallet' => 'Wallet', 'credit' => 'Credit'];
                $headers = ['Date'];
                foreach ($modes as $lbl) { $headers[] = "{$lbl} (₹)"; }
                $headers[] = 'Total Collected (₹)';

                // Invoice paid amounts by date × payment_mode — excludes 'split', which is
                // just a marker on the invoice row; its real per-mode breakdown lives in
                // invoice_payment_splits and is pulled in separately below. This is the same
                // fix already applied to run_fmcg_report()'s sales_payment case, which this
                // report duplicates almost exactly — previously 'split' invoices' money was
                // silently dropped from every total here too, since 'split' isn't one of
                // $modes' known keys.
                $this->db->select('invoice_date as dt, payment_mode, SUM(paid_amount) as amt')
                               ->where('invoice_date >=', $from)
                               ->where('invoice_date <=', $to)
                               ->where('status !=', 'cancelled')
                               ->where('payment_mode !=', 'split');
                if ($location_id) $this->db->where('location_id', $location_id);
                $qi = $this->db->group_by('invoice_date, payment_mode')
                               ->get('invoices')->result_array();

                // Split-payment invoices' actual mode breakdown
                $this->db->select('DATE(inv.invoice_date) as dt, ips.payment_mode, SUM(ips.amount) as amt')
                               ->from('invoice_payment_splits ips')
                               ->join('invoices inv', 'inv.id = ips.invoice_id')
                               ->where('inv.invoice_date >=', $from)
                               ->where('inv.invoice_date <=', $to)
                               ->where('inv.status !=', 'cancelled')
                               ->where('inv.payment_mode', 'split');
                if ($location_id) $this->db->where('inv.location_id', $location_id);
                $qs = $this->db->group_by('DATE(inv.invoice_date), ips.payment_mode')
                               ->get()->result_array();

                // Direct payments received by date × payment_mode
                $this->db->select('p.payment_date as dt, p.payment_mode, SUM(p.amount) as amt')
                               ->from('payments p')
                               ->where('p.payment_date >=', $from)
                               ->where('p.payment_date <=', $to)
                               ->where('p.type', 'received');
                if ($location_id) {
                    $this->db->join('invoices pi', 'pi.id = p.ref_id AND p.ref_type = "invoice"', 'left')
                             ->where('pi.location_id', $location_id);
                }
                $qp = $this->db->group_by('p.payment_date, p.payment_mode')
                               ->get()->result_array();

                // Build pivot: $dates[date][mode] = amount
                $dates = [];
                foreach (array_merge($qi, $qs, $qp) as $r) {
                    $d = $r['dt'];
                    if (!isset($dates[$d])) $dates[$d] = array_fill_keys(array_keys($modes), 0.0);
                    if (isset($dates[$d][$r['payment_mode']])) $dates[$d][$r['payment_mode']] += (float)$r['amt'];
                }
                ksort($dates);

                $grand = array_fill_keys(array_keys($modes), 0.0);
                foreach ($dates as $d => $vals) {
                    $day_total = array_sum($vals);
                    $row = [date('d-m-Y', strtotime($d))];
                    foreach ($modes as $m => $lbl) {
                        $grand[$m] += $vals[$m];
                        $row[] = $vals[$m] > 0 ? number_format($vals[$m], 2) : '-';
                    }
                    $row[] = number_format($day_total, 2);
                    $rows[] = $row;
                }
                // Grand total row for the whole selected period (marker __GT__ detected
                // in the view/exporters for bold styling, same convention as sales_payment)
                if (!empty($rows)) {
                    $gt_total = array_sum($grand);
                    $gt_row = ['__GT__GRAND TOTAL'];
                    foreach ($modes as $m => $lbl) { $gt_row[] = $grand[$m] > 0 ? number_format($grand[$m], 2) : '-'; }
                    $gt_row[] = number_format($gt_total, 2);
                    $rows[] = $gt_row;
                }
                break;

            case 'user_activity_log':
                $title = 'User Activity Log (Audit Trail)';
                $headers = ['Timestamp', 'Username', 'Action', 'Module', 'IP Address', 'Description'];
                $q = $this->db->select('created_at, user_name, action, module, ip_address, description')
                              ->where('DATE(created_at) >=', $from)
                              ->where('DATE(created_at) <=', $to)
                              ->order_by('id', 'DESC')
                              ->get('audit_logs')->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['created_at'], $r['user_name'], strtoupper($r['action']), strtoupper($r['module']), $r['ip_address'], $r['description']];
                }
                break;

            case 'billing_summary':
                $title = 'Billing Summary Report';
                $headers = ['Invoice No', 'Date', 'Customer', 'Subtotal (₹)', 'Discount (₹)', 'Tax (₹)', 'Grand Total (₹)', 'Paid (₹)', 'Refund (₹)', 'Net Amount (₹)', 'Due (₹)'];
                $this->db->select('i.id, i.invoice_no, i.invoice_date, c.name as customer_name, i.subtotal, i.discount, i.total_gst, i.total_amount, i.paid_amount, i.due_amount')
                              ->from('invoices i')
                              ->join('customers c', 'c.id = i.customer_id')
                              ->where('i.invoice_date >=', $from)
                              ->where('i.invoice_date <=', $to)
                              ->where('i.status !=', 'cancelled');
                if ($location_id) $this->db->where('i.location_id', $location_id);
                $q = $this->db->order_by('i.invoice_no', 'ASC')->get()->result_array();
                // Total refunded per invoice (any mode — this report tracks the invoice's
                // full money lifecycle, unlike Collection Summary which only nets cash-mode
                // refunds against a day's till totals).
                $refund_totals = [];
                if (!empty($q)) {
                    $inv_ids = array_column($q, 'id');
                    $ref_rows = $this->db->select('invoice_id, SUM(refund_amount) as total')
                                          ->where_in('invoice_id', $inv_ids)
                                          ->where('status', 'processed')
                                          ->group_by('invoice_id')
                                          ->get('refunds')->result_array();
                    foreach ($ref_rows as $rr) $refund_totals[$rr['invoice_id']] = (float)$rr['total'];
                }
                foreach ($q as $r) {
                    $refund = $refund_totals[$r['id']] ?? 0;
                    $net = $r['total_amount'] - $refund;
                    $rows[] = [$r['invoice_no'], $r['invoice_date'], $r['customer_name'], number_format($r['subtotal'], 2), number_format($r['discount'], 2), number_format($r['total_gst'], 2), number_format($r['total_amount'], 2), number_format($r['paid_amount'], 2), number_format($refund, 2), number_format($net, 2), number_format($r['due_amount'], 2)];
                }
                break;

            case 'collection_summary':
                $title = 'Collection Summary Report';
                $headers = ['Date', 'Cash Collections (₹)', 'UPI Collections (₹)', 'Card Collections (₹)', 'Bank Collections (₹)', 'Wallet Collections (₹)', 'Total Collected (₹)'];
                
                // Get daily invoices cash, upi, card, bank, wallet paid amounts — excludes
                // 'split', whose real per-mode breakdown lives in invoice_payment_splits and
                // is pulled in separately below (same fix as sales_payment/payment_mode_summary:
                // 'split' isn't a bucket here, so that money was silently dropped before).
                $this->db->select('invoice_date, payment_mode, SUM(paid_amount) as total')
                                 ->where('invoice_date >=', $from)
                                 ->where('invoice_date <=', $to)
                                 ->where('status !=', 'cancelled')
                                 ->where('payment_mode !=', 'split');
                if ($location_id) $this->db->where('location_id', $location_id);
                $invs = $this->db->group_by('invoice_date, payment_mode')
                                 ->get('invoices')->result_array();
                // Split-payment invoices' actual mode breakdown
                $this->db->select('DATE(inv.invoice_date) as dt, ips.payment_mode, SUM(ips.amount) as total')
                                 ->from('invoice_payment_splits ips')
                                 ->join('invoices inv', 'inv.id = ips.invoice_id')
                                 ->where('inv.invoice_date >=', $from)
                                 ->where('inv.invoice_date <=', $to)
                                 ->where('inv.status !=', 'cancelled')
                                 ->where('inv.payment_mode', 'split');
                if ($location_id) $this->db->where('inv.location_id', $location_id);
                $splits = $this->db->group_by('DATE(inv.invoice_date), ips.payment_mode')
                                 ->get()->result_array();
                // Get daily payments received
                $this->db->select('p.payment_date, p.payment_mode, SUM(p.amount) as total')
                                ->from('payments p')
                                ->where('p.payment_date >=', $from)
                                ->where('p.payment_date <=', $to)
                                ->where('p.type', 'received');
                if ($location_id) {
                    $this->db->join('invoices pi', 'pi.id = p.ref_id AND p.ref_type = "invoice"', 'left')
                             ->where('pi.location_id', $location_id);
                }
                $pms = $this->db->group_by('p.payment_date, p.payment_mode')
                                ->get()->result_array();
                // Refunds paid back out — must be netted off collections, otherwise a
                // refunded invoice still shows as full "collected" money on this report.
                // credit_note refunds are excluded: no cash actually left any till/account,
                // it's just store credit against a future invoice.
                $refs = $this->db->select('DATE(created_at) as dt, refund_mode, SUM(refund_amount) as total')
                                 ->where('DATE(created_at) >=', $from)
                                 ->where('DATE(created_at) <=', $to)
                                 ->where('status', 'processed')
                                 ->where('refund_mode !=', 'credit_note')
                                 ->group_by('DATE(created_at), refund_mode')
                                 ->get('refunds')->result_array();

                $dates = [];
                // Group by date
                foreach ($invs as $i) {
                    $d = $i['invoice_date'];
                    if (!isset($dates[$d])) $dates[$d] = ['cash'=>0,'upi'=>0,'card'=>0,'bank'=>0,'wallet'=>0];
                    $mode = $i['payment_mode'];
                    if ($mode === 'cash') $dates[$d]['cash'] += $i['total'];
                    elseif ($mode === 'upi') $dates[$d]['upi'] += $i['total'];
                    elseif ($mode === 'card') $dates[$d]['card'] += $i['total'];
                    elseif ($mode === 'neft' || $mode === 'cheque') $dates[$d]['bank'] += $i['total'];
                    elseif ($mode === 'wallet') $dates[$d]['wallet'] += $i['total'];
                }
                foreach ($splits as $s) {
                    $d = $s['dt'];
                    if (!isset($dates[$d])) $dates[$d] = ['cash'=>0,'upi'=>0,'card'=>0,'bank'=>0,'wallet'=>0];
                    $mode = $s['payment_mode'];
                    if ($mode === 'cash') $dates[$d]['cash'] += $s['total'];
                    elseif ($mode === 'upi') $dates[$d]['upi'] += $s['total'];
                    elseif ($mode === 'card') $dates[$d]['card'] += $s['total'];
                    elseif ($mode === 'neft' || $mode === 'cheque') $dates[$d]['bank'] += $s['total'];
                }
                foreach ($pms as $p) {
                    $d = $p['payment_date'];
                    if (!isset($dates[$d])) $dates[$d] = ['cash'=>0,'upi'=>0,'card'=>0,'bank'=>0,'wallet'=>0];
                    $mode = $p['payment_mode'];
                    if ($mode === 'cash') $dates[$d]['cash'] += $p['total'];
                    elseif ($mode === 'upi') $dates[$d]['upi'] += $p['total'];
                    elseif ($mode === 'card') $dates[$d]['card'] += $p['total'];
                    elseif ($mode === 'neft' || $mode === 'cheque') $dates[$d]['bank'] += $p['total'];
                    elseif ($mode === 'wallet') $dates[$d]['wallet'] += $p['total'];
                }
                foreach ($refs as $r) {
                    $d = $r['dt'];
                    if (!isset($dates[$d])) $dates[$d] = ['cash'=>0,'upi'=>0,'card'=>0,'bank'=>0,'wallet'=>0];
                    $mode = $r['refund_mode'];
                    if ($mode === 'cash') $dates[$d]['cash'] -= $r['total'];
                    elseif ($mode === 'upi') $dates[$d]['upi'] -= $r['total'];
                    elseif ($mode === 'card') $dates[$d]['card'] -= $r['total'];
                    elseif ($mode === 'neft' || $mode === 'cheque') $dates[$d]['bank'] -= $r['total'];
                }

                ksort($dates);
                $grand = ['cash'=>0,'upi'=>0,'card'=>0,'bank'=>0,'wallet'=>0];
                foreach ($dates as $d => $v) {
                    $total = $v['cash'] + $v['upi'] + $v['card'] + $v['bank'] + $v['wallet'];
                    foreach ($grand as $m => $val) { $grand[$m] += $v[$m]; }
                    $rows[] = [date('d-m-Y', strtotime($d)), number_format($v['cash'], 2), number_format($v['upi'], 2), number_format($v['card'], 2), number_format($v['bank'], 2), number_format($v['wallet'], 2), number_format($total, 2)];
                }
                // Grand total row for the whole selected period (marker __GT__ detected
                // in the view/exporters for bold styling, same convention as sales_payment)
                if (!empty($rows)) {
                    $grand_total = array_sum($grand);
                    $rows[] = ['__GT__GRAND TOTAL', number_format($grand['cash'], 2), number_format($grand['upi'], 2), number_format($grand['card'], 2), number_format($grand['bank'], 2), number_format($grand['wallet'], 2), number_format($grand_total, 2)];
                }
                break;

            case 'cash_register':
                $title = 'Cash Register Flow Report';
                $headers = ['Date', 'Type of Entry', 'Reference No', 'Opening Float (₹)', 'Cash Received (₹)', 'Cash Paid (₹)', 'Net Cash (₹)'];
                
                // Shifts opened cash
                $shifts = $this->db->select('business_date as date, "Shift Opening Float" as entry, id as ref_id, "shift" as ref, opening_cash as cash_in, 0 as cash_out')
                                   ->where('business_date >=', $from)
                                   ->where('business_date <=', $to)
                                   ->get('cashier_shifts')->result_array();
                // Invoices cash
                $invs = $this->db->select('invoice_date as date, "Cash Invoice Sale" as entry, invoice_no as ref_id, "invoice" as ref, paid_amount as cash_in, 0 as cash_out')
                                 ->where('invoice_date >=', $from)
                                 ->where('invoice_date <=', $to)
                                 ->where('payment_mode', 'cash')
                                 ->where('status !=', 'cancelled')
                                 ->get('invoices')->result_array();
                // Payments cash in
                $pms_in = $this->db->select('payment_date as date, "Customer Payment (Cash)" as entry, payment_no as ref_id, "payment" as ref, amount as cash_in, 0 as cash_out')
                                    ->where('payment_date >=', $from)
                                    ->where('payment_date <=', $to)
                                    ->where('type', 'received')
                                    ->where('payment_mode', 'cash')
                                    ->get('payments')->result_array();
                // Payments cash out
                $pms_out = $this->db->select('payment_date as date, "Supplier Payment (Cash)" as entry, payment_no as ref_id, "payment" as ref, 0 as cash_in, amount as cash_out')
                                     ->where('payment_date >=', $from)
                                     ->where('payment_date <=', $to)
                                     ->where('type', 'paid')
                                     ->where('payment_mode', 'cash')
                                     ->get('payments')->result_array();
                
                $all = array_merge($shifts, $invs, $pms_in, $pms_out);
                usort($all, fn($a, $b) => strcmp($a['date'], $b['date']));
                
                $cash_balance = 0.0;
                foreach ($all as $item) {
                    $opening = $cash_balance;
                    $cash_balance += (float)$item['cash_in'] - (float)$item['cash_out'];
                    $rows[] = [$item['date'], $item['entry'], $item['ref_id'], number_format($opening, 2), number_format($item['cash_in'], 2), number_format($item['cash_out'], 2), number_format($cash_balance, 2)];
                }
                break;

            case 'wallet_transactions':
                $title = 'Wallet Transactions Ledger Report';
                $headers = ['Date & Time', 'Customer', 'Type', 'Amount (₹)', 'Opening Bal (₹)', 'Closing Bal (₹)', 'Reference', 'Remarks', 'Processed By'];
                
                $this->db->select('t.*, c.name as customer_name, u.name as user_name')
                         ->from('customer_wallet_transactions t')
                         ->join('customers c', 'c.id = t.customer_id', 'left')
                         ->join('users u', 'u.id = t.created_by', 'left')
                         ->where('DATE(t.created_at) >=', $from)
                         ->where('DATE(t.created_at) <=', $to);
                $q = $this->db->order_by('t.id', 'DESC')->get()->result_array();
                
                foreach ($q as $r) {
                    $ref = 'Adjustment';
                    if (($r['reference_type'] === 'sale_payment' || $r['reference_type'] === 'sale_excess') && $r['reference_id']) {
                        $ref = 'Invoice ' . ($r['reference_no'] ?: '#' . $r['reference_id']);
                    } else if ($r['reference_type'] === 'refund' && $r['reference_id']) {
                        $ref = 'Return ' . ($r['reference_no'] ?: '#' . $r['reference_id']);
                    }
                    $rows[] = [
                        $r['created_at'],
                        $r['customer_name'] ?? 'Unknown',
                        strtoupper($r['transaction_type']),
                        number_format($r['amount'], 2),
                        number_format($r['opening_balance'], 2),
                        number_format($r['closing_balance'], 2),
                        $ref,
                        $r['remarks'],
                        $r['user_name'] ?: 'System'
                    ];
                }
                break;

            case 'discount_report':
                $title = 'Discount Report';
                $headers = ['Sr', 'Date', 'Invoice #', 'Location', 'Customer', 'Item', 'Qty', 'Rate (₹)', 'Discount (₹)', 'Net Total (₹)', 'Billed By'];
                $this->db->select('ii.product_name, ii.qty, ii.rate, ii.discount, ii.total, i.invoice_no, i.invoice_date, i.discount as bill_discount, c.name as customer_name, l.name as location_name, u.name as user_name')
                         ->from('invoice_items ii')
                         ->join('invoices i', 'i.id = ii.invoice_id')
                         ->join('customers c', 'c.id = i.customer_id', 'left')
                         ->join('locations l', 'l.id = i.location_id', 'left')
                         ->join('users u', 'u.id = i.created_by', 'left')
                         ->where('i.invoice_date >=', $from)
                         ->where('i.invoice_date <=', $to)
                         ->where('i.status !=', 'cancelled')
                         ->where('ii.discount >', 0);
                if ($location_id) $this->db->where('i.location_id', $location_id);
                if ($cashier_id) $this->db->where('i.created_by', $cashier_id);
                $this->db->order_by('i.invoice_date', 'DESC')->order_by('i.id', 'DESC');
                $q = $this->db->get()->result_array();
                $sr = 1;
                foreach ($q as $r) {
                    $rows[] = [
                        $sr++,
                        $r['invoice_date'],
                        $r['invoice_no'],
                        $r['location_name'] ?: '-',
                        $r['customer_name'] ?: '-',
                        $r['product_name'],
                        $r['qty'],
                        number_format($r['rate'], 2),
                        number_format($r['discount'], 2),
                        number_format($r['total'], 2),
                        $r['user_name'] ?: '-',
                    ];
                }
                break;

            case 'price_override_report':
                $title = 'Price Override Report';
                $headers = ['Sr', 'Date', 'Invoice #', 'Location', 'Customer', 'Item', 'Qty', 'Selling Price (₹)', 'Billed Rate (₹)', 'Difference (₹)', 'Billed By'];
                $this->db->select('ii.product_id, ii.product_name, ii.qty, ii.rate, p.selling_price, i.invoice_no, i.invoice_date, c.name as customer_name, l.name as location_name, u.name as user_name')
                         ->from('invoice_items ii')
                         ->join('invoices i', 'i.id = ii.invoice_id')
                         ->join('products p', 'p.id = ii.product_id', 'left')
                         ->join('customers c', 'c.id = i.customer_id', 'left')
                         ->join('locations l', 'l.id = i.location_id', 'left')
                         ->join('users u', 'u.id = i.created_by', 'left')
                         ->where('i.invoice_date >=', $from)
                         ->where('i.invoice_date <=', $to)
                         ->where('i.status !=', 'cancelled')
                         ->where('ROUND(ii.rate,2) != ROUND(p.selling_price,2)', NULL, FALSE);
                if ($location_id) $this->db->where('i.location_id', $location_id);
                if ($cashier_id) $this->db->where('i.created_by', $cashier_id);
                $this->db->order_by('i.invoice_date', 'DESC')->order_by('i.id', 'DESC');
                $q = $this->db->get()->result_array();
                $sr = 1;
                foreach ($q as $r) {
                    $sp = (float)($r['selling_price'] ?? 0);
                    $br = (float)$r['rate'];
                    $diff = round($br - $sp, 2);
                    $rows[] = [
                        $sr++,
                        $r['invoice_date'],
                        $r['invoice_no'],
                        $r['location_name'] ?: '-',
                        $r['customer_name'] ?: '-',
                        $r['product_name'],
                        $r['qty'],
                        number_format($sp, 2),
                        number_format($br, 2),
                        ($diff >= 0 ? '+' : '') . number_format($diff, 2),
                        $r['user_name'] ?: '-',
                    ];
                }
                break;
        }

        $data['report_title'] = $title;
        $data['headers']      = $headers;
        $data['rows']         = $rows;

        // Check if PDF download or Excel export
        $export = $this->input->get('export', TRUE);
        if ($export === 'pdf') {
            $autoload = APPPATH . '../vendor/autoload.php';
            if (file_exists($autoload)) {
                $this->load->library('pdf_generator');
                $html = $this->load->view('reports/report_viewer_pdf', $data, TRUE);
                $this->pdf_generator->generate($html, str_replace(' ', '_', $title) . '_' . date('Ymd'), 'download', 'A4');
                return;
            }
        } elseif ($export === 'excel') {
            $this->_export_excel($title, $headers, $rows);
            return;
        }

        $this->render('reports/report_viewer', $data);
    }

    // Large stock export (>5000 rows, e.g. 56k products): PhpSpreadsheet would run
    // out of memory, so rows are streamed into a real .xlsx (XlsxStream) with the
    // same company heading as every other export: logo, name, address,
    // phone | GSTIN, report title. Falls back to CSV (with a text heading) only
    // when ZipArchive is missing.
    private function _export_stock_csv_stream($filters, $title, $total) {
        $headers = ['#','Product','Item Code','EAN Code','Category','Brand','Supplier','HSN','Unit','Stock Qty','Basic Cost','CP (incl.GST)','MRP','SP','WSP','GST%','Net Value','Gross Value','MRP Value','Expiry'];
        $co = [];
        foreach ($this->db->get('company_settings')->result_array() as $cr) $co[$cr['key_name']] = $cr['key_value'];
        $co_name  = $co['company_name'] ?? 'ERP';
        $co_addr  = trim(preg_replace('/\s*(\\\\n|\r?\n)\s*/', ', ', $co['company_address'] ?? ''), ', ');
        $co_line  = trim(implode('   |   ', array_filter([
            !empty($co['company_phone']) ? 'Phone: ' . $co['company_phone'] : '',
            !empty($co['company_gstin']) ? 'GSTIN: ' . $co['company_gstin'] : '',
        ])));
        $xlsx = class_exists('ZipArchive');
        $sum = ['qty' => 0, 'net' => 0, 'gross' => 0, 'mrp' => 0];
        if ($xlsx) {
            $this->load->library('XlsxStream');
            $xs = new XlsxStream();
            $xs->start([6, 34, 11, 16, 16, 14, 18, 10, 7, 10, 11, 12, 10, 10, 10, 7, 13, 13, 13, 11]);
            if (!empty($co['company_logo'])) $xs->logo(FCPATH . ltrim($co['company_logo'], '/'), 45);
            $xs->banner($co_name, 1);
            if ($co_addr !== '') $xs->banner($co_addr, 2);
            if ($co_line !== '') $xs->banner($co_line, 2);
            $xs->banner($title, 5);
            $xs->banner('Generated: ' . date('d M Y, h:i A') . '   |   ' . number_format($total) . ' items   |   Stock shown never below 0', 2);
            $xs->blank();
            $xs->header($headers);
        } else {
            while (ob_get_level() > 0) { @ob_end_clean(); }
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $title) . '_' . date('Ymd') . '.csv"');
            header('Cache-Control: max-age=0');
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            foreach (array_filter([$co_name, $co_addr, $co_line, $title, 'Generated: ' . date('d M Y, h:i A')]) as $line) fputcsv($out, [$line]);
            fputcsv($out, []);
            fputcsv($out, $headers);
        }

        $batch = 2000;
        $location_id = !empty($filters['location_id']) ? (int)$filters['location_id'] : null;
        $qty_expr = $location_id ? 'GREATEST(COALESCE(pls.stock_qty, 0), 0)' : 'GREATEST(p.stock_qty, 0)';
        $seq = 0;

        for ($offset = 0; $offset < $total; $offset += $batch) {
            $this->db->select("
                p.id, p.name, p.item_code, p.category_id, p.brand_id,
                p.hsn_code, {$qty_expr} AS stock_qty, p.unit,
                p.basic_cost, p.purchase_price, p.selling_price, p.wsp, p.mrp, p.gst_percent, p.expiry_date,
                ({$qty_expr} * p.basic_cost)     AS net_cost_value,
                ({$qty_expr} * p.purchase_price) AS gross_cost_value,
                ({$qty_expr} * p.selling_price)  AS mrp_value,
                COALESCE(c.name, p.category) AS category_name,
                COALESCE(b.name, p.brand)    AS brand_name
            ", false)
            ->from('products p')
            ->join('categories c', 'c.id = p.category_id', 'left')
            ->join('brands b', 'b.id = p.brand_id', 'left');
            if ($location_id) {
                $this->db->join('product_location_stock pls', "pls.product_id = p.id AND pls.location_id = {$location_id}", 'left');
            }
            if (!empty($filters['item_name'])) {
                $this->load->helper('product_search');
                $ws = product_words_sql($filters['item_name'], ['p.name']);
                if ($ws) $this->db->where($ws, NULL, FALSE); else $this->db->like('p.name', $filters['item_name']);
            }
            if (!empty($filters['item_code'])) $this->db->like('p.item_code', $filters['item_code']);
            if (!empty($filters['category_id'])) $this->db->where('p.category_id', (int)$filters['category_id']);
            if (!empty($filters['brand_id'])) $this->db->where('p.brand_id', (int)$filters['brand_id']);
            if (!empty($filters['supplier_id'])) {
                $sid = (int)$filters['supplier_id'];
                $this->db->where("p.id IN (SELECT product_id FROM supplier_products WHERE supplier_id = {$sid})", null, false);
            }
            if (!empty($filters['qoh_op']) && $filters['qoh_val'] !== '' && $filters['qoh_val'] !== null) {
                $qty_col = $location_id ? 'GREATEST(COALESCE(pls.stock_qty, 0), 0)' : 'GREATEST(p.stock_qty, 0)';
                $qval = (float)$filters['qoh_val'];
                $ops = ['eq'=>'=','lt'=>'<','lte'=>'<=','gt'=>'>','gte'=>'>='];
                $op = $ops[$filters['qoh_op']] ?? null;
                if ($op) $this->db->where("{$qty_col} {$op} {$qval}", null, false);
            }
            if (!empty($filters['ean_code'])) {
                $ean = $this->db->escape_like_str($filters['ean_code']);
                $this->db->where("EXISTS (SELECT 1 FROM product_barcodes pbf WHERE pbf.product_id = p.id AND pbf.barcode LIKE '%{$ean}%')", null, false);
            }
            $this->db->order_by('p.category', 'ASC')->order_by('p.name', 'ASC');
            $this->db->limit($batch, $offset);
            $products = $this->db->get()->result();
            if (empty($products)) break;

            $pids = array_map(function($p) { return (int)$p->id; }, $products);
            $ean_map = [];
            $ean_rows = $this->db->select('product_id, barcode')
                ->where('unit_type', 'PCS')
                ->where_in('product_id', $pids)
                ->order_by('id', 'ASC')
                ->get('product_barcodes')->result();
            foreach ($ean_rows as $r) {
                if (!isset($ean_map[$r->product_id])) $ean_map[$r->product_id] = $r->barcode;
            }
            $sup_map = [];
            $sup_rows = $this->db->select('sp.product_id, s.name as supplier_name')
                ->from('supplier_products sp')
                ->join('suppliers s', 's.id = sp.supplier_id')
                ->where_in('sp.product_id', $pids)
                ->order_by('sp.is_primary', 'DESC')->order_by('sp.id', 'ASC')
                ->get()->result();
            foreach ($sup_rows as $r) {
                if (!isset($sup_map[$r->product_id])) $sup_map[$r->product_id] = $r->supplier_name;
            }

            foreach ($products as $r) {
                $seq++;
                $line = [
                    $seq, $r->name ?? '', (string)($r->item_code ?? ''), (string)($ean_map[$r->id] ?? ''),
                    $r->category_name ?? '', $r->brand_name ?? '', $sup_map[$r->id] ?? '',
                    (string)($r->hsn_code ?? ''), $r->unit ?? 'PCS', (float)($r->stock_qty ?? 0),
                    (float)($r->basic_cost ?? 0), (float)($r->purchase_price ?? 0), (float)($r->mrp ?? 0),
                    (float)($r->selling_price ?? 0), (float)($r->wsp ?? 0), (float)($r->gst_percent ?? 0),
                    round((float)($r->net_cost_value ?? 0), 2), round((float)($r->gross_cost_value ?? 0), 2),
                    round((float)($r->mrp_value ?? 0), 2), $r->expiry_date ?? ''
                ];
                $sum['qty'] += $line[9]; $sum['net'] += $line[16]; $sum['gross'] += $line[17]; $sum['mrp'] += $line[18];
                if ($xlsx) $xs->row($line, 0, 4, [1, 3, 4, 8]);   // codes stay text
                else fputcsv($out, $line);
            }
            if (!$xlsx) flush();
            unset($products, $pids, $ean_map, $ean_rows, $sup_map, $sup_rows);
        }
        $totals = ['', 'TOTAL', '', '', '', '', '', '', '', round($sum['qty'], 3), '', '', '', '', '', '',
                   round($sum['net'], 2), round($sum['gross'], 2), round($sum['mrp'], 2), ''];
        if (!$xlsx) {
            fputcsv($out, $totals);
            fclose($out);
            exit;
        }
        $xs->row($totals, 6, 7);
        $file = $xs->finish('Stock Report');
        while (ob_get_level() > 0) { @ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9_\-]+/', '_', $title) . '_' . date('Ymd') . '.xlsx"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: max-age=0');
        readfile($file);
        $xs->cleanup();
        exit;
    }

    private function _export_excel($title, $headers, $rows) {
        $company_q = $this->db->get('company_settings')->result_array();
        $company = [];
        foreach ($company_q as $row) {
            $company[$row['key_name']] = $row['key_value'];
        }
        $company_name    = $company['company_name']    ?? 'ERP';
        $company_address = trim(preg_replace('/\s*(\\\\n|\r?\n)\s*/', ', ', $company['company_address'] ?? ''), ', ');
        $company_phone   = $company['company_phone']   ?? '';
        $company_gstin   = $company['company_gstin']   ?? '';

        $autoload = APPPATH . '../vendor/autoload.php';
        if (!file_exists($autoload)) {
            $this->_export_csv_fallback($title, $headers, $rows, $company_name, $company_address, $company_phone, $company_gstin);
            return;
        }
        require_once $autoload;

        $lite = count($rows) > 5000;

        $col_count = max(1, count($headers));
        $last_col  = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col_count);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet_name = substr(preg_replace('/[^A-Za-z0-9 _-]/', '', $title), 0, 31);
        $sheet->setTitle($sheet_name ?: 'Report');

        $r = 1;

        // Logo gets its own row (tall enough to fit it) so it never overlaps the
        // company name/address text merged across the row below — mirrors the
        // header block already used in the PDF export and the print view.
        $company_logo = $company['company_logo'] ?? '';
        if (!empty($company_logo) && file_exists('./' . $company_logo)) {
            $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            $drawing->setName('Logo');
            $drawing->setPath(realpath('./' . $company_logo));
            $drawing->setHeight(45);
            $drawing->setCoordinates("A{$r}");
            $drawing->setOffsetX(2);
            $drawing->setOffsetY(2);
            $drawing->setWorksheet($sheet);
            $sheet->getRowDimension($r)->setRowHeight(36);
            $r++;
        }

        $sheet->setCellValue("A{$r}", $company_name);
        $sheet->mergeCells("A{$r}:{$last_col}{$r}");
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(14);
        $r++;

        if (!empty($company_address)) {
            $sheet->setCellValue("A{$r}", $company_address);
            $sheet->mergeCells("A{$r}:{$last_col}{$r}");
            $sheet->getStyle("A{$r}")->getFont()->setSize(9)->getColor()->setRGB('666666');
            $r++;
        }
        if (!empty($company_phone) || !empty($company_gstin)) {
            $sheet->setCellValue("A{$r}", trim("Phone: {$company_phone}   |   GSTIN: {$company_gstin}"));
            $sheet->mergeCells("A{$r}:{$last_col}{$r}");
            $sheet->getStyle("A{$r}")->getFont()->setSize(9)->getColor()->setRGB('666666');
            $r++;
        }
        $r++;

        $sheet->setCellValue("A{$r}", strtoupper($title));
        $sheet->mergeCells("A{$r}:{$last_col}{$r}");
        $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(12);
        $r++;

        $sheet->setCellValue("A{$r}", 'Printed on: ' . date('d M Y H:i'));
        $sheet->mergeCells("A{$r}:{$last_col}{$r}");
        $sheet->getStyle("A{$r}")->getFont()->setSize(9)->getColor()->setRGB('666666');
        $r += 2;

        $header_row = $r;
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}{$header_row}", strtoupper($h));
        }
        $sheet->getStyle("A{$header_row}:{$last_col}{$header_row}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '343A40']],
            'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ]);
        $r++;

        // Columns that hold identifiers rather than quantities — even when the value
        // is all-digits (e.g. item code "111175"), it must stay text or Excel's
        // auto-numeric formatting turns it into "1,11,175.00".
        // 'ean'/'ean code' matter especially here: a 13-digit EAN would otherwise
        // be written as a float and come back out of Excel as 8.90123E+12.
        $text_only_headers = ['item code', 'barcode', 'ean', 'ean code', 'hsn', 'hsn code', 'gstin', 'phone', 'pincode'];
        $text_only_cols = [];
        foreach ($headers as $i => $h) {
            if (in_array(strtolower(trim($h)), $text_only_headers, true)) $text_only_cols[$i] = true;
        }

        foreach ($rows as $row) {
            foreach ($row as $i => $v) {
                if ($i === 0) $v = str_replace('__GT__', '', (string)$v);
                $col    = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $clean  = trim(strip_tags(str_replace('₹', '', (string)$v)));
                $numval = str_replace(',', '', $clean);
                if ($i > 0 && $clean !== '' && $clean !== '—' && is_numeric($numval) && empty($text_only_cols[$i])) {
                    $sheet->setCellValue("{$col}{$r}", (float)$numval);
                    if (!$lite) {
                        $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
                        $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    }
                } else {
                    $sheet->setCellValueExplicit("{$col}{$r}", $clean, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
            }
            if (!$lite) {
                $row_label = isset($row[0]) ? strtoupper(trim((string)$row[0])) : '';
                $is_total  = $row_label === 'TOTAL' || strpos($row_label, '__GT__') === 0;
                $is_refund = !empty($row) && strtolower(trim((string)end($row))) === 'refund';
                foreach ($row as $i => $v) {
                    if (!$is_total && stripos($headers[$i] ?? '', 'refund') !== false) {
                        $numval = str_replace(',', '', trim(strip_tags(str_replace('₹', '', (string)$v))));
                        if (is_numeric($numval) && (float)$numval > 0) {
                            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                            $sheet->getStyle("{$col}{$r}")->getFont()->setBold(true)->getColor()->setRGB('C0392B');
                        }
                    }
                }
                $sheet->getStyle("A{$r}:{$last_col}{$r}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
                ]);
                if ($is_total) {
                    $sheet->getStyle("A{$r}:{$last_col}{$r}")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDE68A']],
                    ]);
                } elseif ($is_refund) {
                    $sheet->getStyle("A{$r}:{$last_col}{$r}")->applyFromArray([
                        'font' => ['color' => ['rgb' => 'C0392B']],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDEDEC']],
                    ]);
                }
            }
            $r++;
        }

        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getColumnDimension($col)->setWidth($i === 0 ? max(22, strlen($h) + 6) : min(20, max(12, strlen($h) + 4)));
        }

        // Discard any buffered output (CI's own output buffer, stray whitespace from
        // included files, etc.) before writing raw binary — a zip/xlsx file is not
        // tolerant of leading bytes the way HTML/CSV/PDF are, so even one leaked
        // byte here corrupts the file for Excel even though it still "looks fine".
        while (ob_get_level() > 0) { @ob_end_clean(); }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $title) . '_' . date('Ymd') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // Fallback if PhpSpreadsheet isn't installed — plain CSV, same content as before.
    private function _export_csv_fallback($title, $headers, $rows, $company_name, $company_address, $company_phone, $company_gstin) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $title) . '_' . date('Ymd') . '.csv"');
        header('Cache-Control: max-age=0');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel doesn't mangle non-ASCII chars (e.g. ₹)
        fputcsv($out, [$company_name]);
        if (!empty($company_address)) fputcsv($out, [$company_address]);
        if (!empty($company_phone) || !empty($company_gstin)) fputcsv($out, ["Phone: {$company_phone} | GSTIN: {$company_gstin}"]);
        fputcsv($out, []);
        fputcsv($out, [$title]);
        fputcsv($out, ['Printed on: ' . date('d M Y H:i')]);
        fputcsv($out, []);
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, array_map(fn($v) => strip_tags(str_replace(['₹', '__GT__'], '', $v)), $row));
        }
        fclose($out);
        exit;
    }

    // Check export/email/print permission
    private function _can($perm) {
        $role = $this->session->userdata('role');
        if (in_array($role, ['superadmin','owner','admin'])) return true;
        $fp = $this->session->userdata('form_perms') ?: [];
        return !empty($fp[$perm]);
    }

    // Generic PDF export using mPDF
    private function _export_pdf($title, $headers, $rows, $orient = 'L') {
        if (!$this->_can('frm_rpt_export')) { $this->session->set_flashdata('error','Export not allowed.'); return false; }
        $autoload = APPPATH . '../vendor/autoload.php';
        if (!file_exists($autoload)) { $this->session->set_flashdata('error','mPDF not installed.'); return false; }
        require_once $autoload;
        $co = $this->db->get('company_settings')->result_array();
        $c = []; foreach ($co as $r) $c[$r['key_name']] = $r['key_value'];
        $rows_html = '';
        foreach ($rows as $row) {
            $row_label = isset($row[0]) ? strtoupper(trim((string)$row[0])) : '';
            $is_total  = $row_label === 'TOTAL' || strpos($row_label, '__GT__') === 0;
            $is_refund = !empty($row) && strtolower(trim((string)end($row))) === 'refund';
            if (count($row) === 1) {
                $rows_html .= '<tr><td colspan="'.count($headers).'" style="background:#e8ecf0;font-weight:bold;padding:6px;border:1px solid #ddd;">'.htmlspecialchars(str_replace('__GT__', '', $row[0])).'</td></tr>';
            } else {
                $row_style = $is_total ? 'background:#fde68a;font-weight:bold;' : ($is_refund ? 'background:#fdedec;color:#c0392b;' : '');
                $rows_html .= '<tr>';
                foreach ($row as $i => $v) {
                    if ($i === 0) $v = str_replace('__GT__', '', (string)$v);
                    // Refund column: colour the value itself red when non-zero, same
                    // treatment as the on-screen report table, even outside a "refund row".
                    $cell_style = $row_style;
                    if (!$is_total && stripos($headers[$i] ?? '', 'refund') !== false && (float)str_replace(',', '', (string)$v) > 0) {
                        $cell_style .= 'color:#c0392b;font-weight:bold;';
                    }
                    $rows_html .= '<td style="padding:3px 5px;border:1px solid #ddd;'.$cell_style.'">'.htmlspecialchars(strip_tags((string)$v)).'</td>';
                }
                $rows_html .= '</tr>';
            }
        }
        $heads = '<tr>'; foreach ($headers as $h) $heads .= '<th style="background:#343a40;color:#fff;padding:5px;border:1px solid #333;font-size:8pt;text-transform:uppercase;">'.htmlspecialchars($h).'</th>'; $heads .= '</tr>';
        $logo_html = '';
        $company_logo = $c['company_logo'] ?? '';
        if (!empty($company_logo) && file_exists('./' . $company_logo)) {
            $logo_html = '<img src="./'.$company_logo.'" style="max-height:40px;max-width:130px;object-fit:contain;margin-bottom:4px;">';
        }
        $html = '<html><head><meta charset="UTF-8"><style>body{font-family:DejaVu Sans,sans-serif;font-size:8.5pt;}table{width:100%;border-collapse:collapse;}</style></head><body>'
            .$logo_html
            .'<p style="margin:0;font-size:11pt;font-weight:bold;">'.htmlspecialchars($c['company_name']??'ERP').'</p>'
            .'<p style="margin:0 0 4px;font-size:7.5pt;color:#555;">'.htmlspecialchars($c['company_address']??'').' | GSTIN: '.htmlspecialchars($c['company_gstin']??'').'</p>'
            .'<p style="margin:0 0 6px;font-size:9pt;font-weight:bold;border-bottom:1px solid #ccc;padding-bottom:4px;">'.htmlspecialchars($title).' — Printed: '.date('d M Y H:i').'</p>'
            .'<table>'.$heads.'<tbody>'.$rows_html.'</tbody></table></body></html>';
        $mpdf = new \Mpdf\Mpdf(['orientation'=>$orient,'margin_left'=>8,'margin_right'=>8,'margin_top'=>10,'margin_bottom'=>8]);
        $mpdf->SetTitle($title); $mpdf->WriteHTML($html);
        while (ob_get_level() > 0) { @ob_end_clean(); }
        $mpdf->Output(str_replace([' ','/',':'],['_','_',''],$title).'_'.date('Ymd').'.pdf','D');
        exit;
    }

    // Generic email sender — attaches CSV
    private function _send_report_email($to_email, $title, $headers, $rows, $redirect_url) {
        if (!$this->_can('frm_rpt_email')) { $this->session->set_flashdata('error','Email export not allowed.'); redirect($redirect_url); }
        $co = $this->db->get('company_settings')->result_array();
        $c = []; foreach ($co as $r) $c[$r['key_name']] = $r['key_value'];
        $csv = implode(',', array_map(fn($h)=>'"'.str_replace('"','""',$h).'"', $headers))."\n";
        foreach ($rows as $row) {
            if (is_array($row)) $csv .= implode(',', array_map(fn($v)=>'"'.str_replace('"','""',strip_tags(str_replace('__GT__', '', (string)$v))).'"', $row))."\n";
        }
        $this->load->library('email');
        $this->email->initialize([
            'protocol'=>'smtp','smtp_host'=>$c['smtp_host']??'smtp.gmail.com',
            'smtp_port'=>$c['smtp_port']??587,'smtp_user'=>$c['smtp_user']??'',
            'smtp_pass'=>$c['smtp_pass']??'','smtp_crypto'=>$c['smtp_crypto']??'tls',
            'mailtype'=>'html','charset'=>'utf-8',
        ]);
        $from_addr = !empty($c['smtp_user']) ? $c['smtp_user'] : ($c['company_email'] ?? 'noreply@erp.local');
        $this->email->from($from_addr, $c['company_name']??'ERP');
        $this->email->to($to_email);
        $this->email->subject($title.' — '.date('d M Y'));
        $this->email->message('<p>Please find <b>'.$title.'</b> attached.</p><p>'.($c['company_name']??'ERP').'</p>');
        $tmp = tempnam(sys_get_temp_dir(),'rpt_').'.csv';
        file_put_contents($tmp, $csv);
        $this->email->attach($tmp,'attachment',str_replace(' ','_',$title).'_'.date('Ymd').'.csv','text/csv');
        $ok = $this->email->send();
        @unlink($tmp);
        $this->session->set_flashdata($ok?'success':'error', $ok?'Report emailed to '.$to_email.'.':'Email failed — check SMTP settings.');
        redirect($redirect_url);
    }

    // ─── Reports Suite Dashboard ───────────────────────────────────────────────
    public function fmcg_suite() {
        $data['page_title'] = 'Reports Suite';

        // Load settings to get custom fmcg_server_url if configured
        $settings_row = $this->db->where('key_name', 'fmcg_server_url')->get('company_settings')->row();
        $data['fmcg_server_url'] = $settings_row ? $settings_row->key_value : 'http://localhost:5000';

        $this->render('reports/fmcg_suite', $data);
    }

    // â”€â”€ Native PHP/MySQL FMCG Report Runner â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function run_fmcg_report($key) {
        $from = $this->input->get('from', TRUE) ? $this->input->get('from') : date('Y-m-01');
        $to   = $this->input->get('to', TRUE) ? $this->input->get('to') : date('Y-m-t');
        $dept = $this->input->get('dept', TRUE) ? $this->input->get('dept') : '';
        $location_id = $this->_get_loc_id();

        $title = 'Report';
        $headers = [];
        $rows = [];
        
        switch ($key) {
            case 'sales_summary':
                $title = 'Sales Summary Report';
                $headers = ['Date', 'Invoices Count', 'Subtotal (₹)', 'Discount (₹)', 'Tax/GST (₹)', 'Total Value (₹, incl. GST)', 'Paid (₹, incl. GST)', 'Balance Due (₹)'];
                $this->db->select('invoice_date, COUNT(id) as total_bills, SUM(subtotal) as sub, SUM(discount) as disc, SUM(total_gst) as gst, SUM(total_amount) as total, SUM(paid_amount) as paid, SUM(due_amount) as due');
                $this->db->where('invoice_date >=', $from)->where('invoice_date <=', $to)->where('status !=', 'cancelled');
                if ($location_id) $this->db->where('location_id', $location_id);
                $q = $this->db->group_by('invoice_date')->order_by('invoice_date', 'ASC')->get('invoices')->result_array();
                // Refunds and confirmed sales returns both have no location_id, so only net
                // them out for the all-locations view. Sales returns weren't netted here
                // before — Executive Summary/P&L net both, so this report's "Total Value"
                // could disagree with theirs by exactly the returned amount for the period.
                $refund_by_date = [];
                if (!$location_id) {
                    $rr = $this->db->select("DATE(created_at) as dt, SUM(refund_amount) as amt")
                        ->where('DATE(created_at) >=', $from)->where('DATE(created_at) <=', $to)
                        ->where('status', 'processed')->get('refunds')->result_array();
                    $refund_by_date = array_column($rr, 'amt', 'dt');

                    $rs = $this->db->select("return_date as dt, SUM(total_amount) as amt")
                        ->where('return_date >=', $from)->where('return_date <=', $to)
                        ->where('status', 'confirmed')->get('sales_returns')->result_array();
                    foreach ($rs as $row) {
                        $refund_by_date[$row['dt']] = ($refund_by_date[$row['dt']] ?? 0) + (float)$row['amt'];
                    }
                }
                $gt_bills = $gt_sub = $gt_disc = $gt_gst = $gt_total = $gt_paid = $gt_due = 0;
                foreach ($q as $r) {
                    $day_refund = (float)($refund_by_date[$r['invoice_date']] ?? 0);
                    $line_total = $r['total'] - $day_refund;
                    $rows[] = [$r['invoice_date'], $r['total_bills'], number_format($r['sub'], 2), number_format($r['disc'], 2), number_format($r['gst'], 2), number_format($line_total, 2), number_format($r['paid'], 2), number_format($r['due'], 2)];
                    $gt_bills += $r['total_bills']; $gt_sub += $r['sub']; $gt_disc += $r['disc']; $gt_gst += $r['gst'];
                    $gt_total += $line_total; $gt_paid += $r['paid']; $gt_due += $r['due'];
                }
                if (!empty($rows)) {
                    $rows[] = ['TOTAL', $gt_bills, number_format($gt_sub, 2), number_format($gt_disc, 2), number_format($gt_gst, 2), number_format($gt_total, 2), number_format($gt_paid, 2), number_format($gt_due, 2)];
                }
                break;
                
            case 'sales_detail':
                $title = 'Item Sales Detail';
                $headers = ['Bill No', 'Date', 'Item Code', 'Item Name', 'Category', 'Brand', 'Qty Sold', 'Selling Price (₹)', 'MRP (₹)', 'GST %', 'Subtotal (₹)', 'Discount (₹)', 'Net Total (₹, incl. GST)'];
                $this->db->select('i.invoice_no, i.invoice_date, p.item_code, ii.product_name as item_name, p.category, p.brand, ii.qty, ii.rate as price, ii.mrp, ii.gst_percent, ii.taxable_amt as subtotal, ii.discount, ii.total');
                $this->db->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('products p', 'p.id = ii.product_id', 'left');
                $this->db->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)->where('i.status !=', 'cancelled');
                if ($location_id) $this->db->where('i.location_id', $location_id);
                $this->db->order_by('i.invoice_date', 'ASC');
                $q = $this->db->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['invoice_no'], $r['invoice_date'], $r['item_code'] ?? 'N/A', $r['item_name'], $r['category'] ?? 'N/A', $r['brand'] ?? 'N/A', $r['qty'], number_format($r['price'], 2), number_format($r['mrp'], 2), number_format($r['gst_percent'], 2), number_format($r['subtotal'], 2), number_format($r['discount'], 2), number_format($r['total'], 2)];
                }

                // Item-wise summary — the detail rows above are one row per bill-line
                // (repeats per invoice); this groups the same data by item across the
                // whole period so "how much of X sold in total" doesn't require adding
                // up dozens of individual bill rows. No Bill No/Date here since it's a
                // cross-bill aggregate, not a transaction log.
                $item_group = [];
                foreach ($q as $r) {
                    $ikey = ($r['item_code'] ?? 'N/A') . '|' . $r['item_name'];
                    if (!isset($item_group[$ikey])) {
                        $item_group[$ikey] = [
                            'item_code' => $r['item_code'] ?? 'N/A', 'item_name' => $r['item_name'],
                            'category' => $r['category'] ?? 'N/A', 'brand' => $r['brand'] ?? 'N/A',
                            'qty' => 0, 'subtotal' => 0, 'discount' => 0, 'total' => 0,
                        ];
                    }
                    $item_group[$ikey]['qty']      += (float)$r['qty'];
                    $item_group[$ikey]['subtotal'] += (float)$r['subtotal'];
                    $item_group[$ikey]['discount'] += (float)$r['discount'];
                    $item_group[$ikey]['total']    += (float)$r['total'];
                }
                usort($item_group, fn($a, $b) => $b['total'] <=> $a['total']);
                $data['item_summary_headers'] = ['Item Code', 'Item Name', 'Category', 'Brand', 'Qty Sold', 'Subtotal (₹)', 'Discount (₹)', 'Net Total (₹, incl. GST)'];
                $data['item_summary_rows'] = array_map(fn($g) => [
                    $g['item_code'], $g['item_name'], $g['category'], $g['brand'],
                    number_format($g['qty'], 0), number_format($g['subtotal'], 2), number_format($g['discount'], 2), number_format($g['total'], 2),
                ], $item_group);
                break;

            case 'sales_payment':
                $title = 'Payment Mode Breakdown';
                $pmodes = ['cash'=>'Cash','upi'=>'UPI','card'=>'Card','neft'=>'Bank/NEFT','cheque'=>'Cheque','wallet'=>'Wallet','credit'=>'Credit'];
                $headers = ['Date', 'Cash (₹)', 'UPI (₹)', 'Card (₹)', 'Bank/NEFT (₹)', 'Cheque (₹)', 'Wallet (₹)', 'Credit (₹)', 'Day Total (₹)'];

                // Invoice paid amounts by date × payment_mode — excludes 'split', which is
                // just a marker on the invoice row; its real per-mode breakdown lives in
                // invoice_payment_splits and is pulled in separately below. Previously this
                // included 'split' invoices too, but since 'split' isn't one of $pmodes'
                // known keys, that money was silently dropped from every total on this report.
                $qi = $this->db->select('DATE(invoice_date) as dt, payment_mode, SUM(paid_amount) as amt')
                    ->where('invoice_date >=', $from)->where('invoice_date <=', $to)
                    ->where('status !=', 'cancelled')
                    ->where('payment_mode !=', 'split');
                if ($location_id) $qi = $qi->where('location_id', $location_id);
                $qi = $qi->group_by('DATE(invoice_date), payment_mode')->order_by('dt','ASC')->get('invoices')->result_array();

                // Split-payment invoices' actual mode breakdown
                $qs = $this->db->select('DATE(inv.invoice_date) as dt, ips.payment_mode, SUM(ips.amount) as amt')
                    ->from('invoice_payment_splits ips')
                    ->join('invoices inv', 'inv.id = ips.invoice_id')
                    ->where('inv.invoice_date >=', $from)->where('inv.invoice_date <=', $to)
                    ->where('inv.status !=', 'cancelled')
                    ->where('inv.payment_mode', 'split');
                if ($location_id) $qs = $qs->where('inv.location_id', $location_id);
                $qs = $qs->group_by('DATE(inv.invoice_date), ips.payment_mode')->order_by('dt','ASC')->get()->result_array();

                // Direct payments received by date × payment_mode
                $qp = $this->db->select('DATE(payment_date) as dt, payment_mode, SUM(amount) as amt')
                    ->where('payment_date >=', $from)->where('payment_date <=', $to)->where('type','received');
                if ($location_id) $qp = $qp->where('location_id', $location_id);
                $qp = $qp->group_by('DATE(payment_date), payment_mode')->order_by('dt','ASC')->get('payments')->result_array();

                // Build pivot: $dates[date][mode] = amount
                $dates = [];
                foreach (array_merge($qi, $qs, $qp) as $r) {
                    $d = $r['dt'];
                    if (!isset($dates[$d])) $dates[$d] = array_fill_keys(array_keys($pmodes), 0.0);
                    if (isset($dates[$d][$r['payment_mode']])) $dates[$d][$r['payment_mode']] += (float)$r['amt'];
                }

                // Refunds paid back reduce the relevant mode (no location_id on refunds,
                // so only net them out for the all-locations view)
                if (!$location_id) {
                    $qr = $this->db->select("DATE(created_at) as dt, refund_mode, SUM(refund_amount) as amt")
                        ->where('DATE(created_at) >=', $from)->where('DATE(created_at) <=', $to)
                        ->where('status', 'processed')->group_by('DATE(created_at), refund_mode')->get('refunds')->result_array();
                    foreach ($qr as $r) {
                        $d = $r['dt'];
                        if (!isset($dates[$d])) $dates[$d] = array_fill_keys(array_keys($pmodes), 0.0);
                        if (isset($dates[$d][$r['refund_mode']])) $dates[$d][$r['refund_mode']] -= (float)$r['amt'];
                    }
                }
                ksort($dates);

                $grand = array_fill_keys(array_keys($pmodes), 0.0);
                foreach ($dates as $date => $vals) {
                    $day_total = array_sum($vals);
                    $row = [date('d-m-Y', strtotime($date))];
                    foreach ($pmodes as $m => $lbl) {
                        $grand[$m] += $vals[$m];
                        $row[] = $vals[$m] > 0 ? number_format($vals[$m], 2) : '-';
                    }
                    $row[] = number_format($day_total, 2);
                    $rows[] = $row;
                }
                // Grand total row (marker __GT__ detected in view for bold styling)
                if (!empty($rows)) {
                    $gt_total = array_sum($grand);
                    $gt_row = ['__GT__GRAND TOTAL'];
                    foreach ($pmodes as $m => $lbl) { $gt_row[] = $grand[$m] > 0 ? number_format($grand[$m], 2) : '-'; }
                    $gt_row[] = number_format($gt_total, 2);
                    $rows[] = $gt_row;
                }
                break;
                
            case 'sales_returns':
                $title = 'Sales Returns & Credit Notes';
                $headers = ['Date', 'Return No', 'Original Invoice', 'Customer', 'Reason Note', 'Taxable Amt (₹)', 'Tax GST (₹)', 'Total Refund (₹)'];
                $this->db->select('sr.return_date, sr.return_number as return_no, i.invoice_no, c.name as customer_name, sr.reason as refund_mode, sr.taxable_amount as refund_amount, sr.total_gst as tax_amount, sr.total_amount');
                $this->db->from('sales_returns sr')->join('invoices i', 'i.id = sr.invoice_id', 'left')->join('customers c', 'c.id = sr.customer_id', 'left');
                $this->db->where('sr.return_date >=', $from)->where('sr.return_date <=', $to)->order_by('sr.return_date', 'ASC');
                $q = $this->db->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['return_date'], $r['return_no'], $r['invoice_no'] ? $r['invoice_no'] : 'N/A', $r['customer_name'] ?? 'N/A', $r['refund_mode'] ? $r['refund_mode'] : 'N/A', number_format($r['refund_amount'], 2), number_format($r['tax_amount'], 2), number_format($r['total_amount'], 2)];
                }
                break;
                
            case 'sales_smart':
                $title = 'Smart Sales Query Report';
                $headers = ['Bill No', 'Date', 'Customer', 'Item Name', 'Category', 'Brand', 'Qty', 'Selling Price (₹)', 'Net Total (₹)'];
                $this->db->select('i.invoice_no, i.invoice_date, c.name as customer_name, ii.product_name as item_name, p.category, p.brand, ii.qty, ii.rate as price, ii.total');
                $this->db->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('customers c', 'c.id = i.customer_id', 'left')->join('products p', 'p.id = ii.product_id', 'left');
                $this->db->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)->where('i.status !=', 'cancelled');
                if ($location_id) $this->db->where('i.location_id', $location_id);
                $q = $this->db->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['invoice_no'], $r['invoice_date'], $r['customer_name'] ?? 'Walk-in', $r['item_name'], $r['category'] ?? 'N/A', $r['brand'] ?? 'N/A', $r['qty'], number_format($r['price'], 2), number_format($r['total'], 2)];
                }
                break;
                
            case 'customer_list':
                $title = 'Customer Master Directory';
                $headers = ['ID', 'Name', 'Phone', 'Email', 'State/GST Code', 'GSTIN', 'Outstanding Balance (₹)', 'Created At'];
                $q = $this->db->select('id, name, phone, email, state, gst_number, balance, created_at')->get('customers')->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['id'], $r['name'], $r['phone'], $r['email'] ? $r['email'] : 'N/A', $r['state'] ? $r['state'] : 'N/A', $r['gst_number'] ? $r['gst_number'] : 'N/A', number_format($r['balance'], 2), $r['created_at']];
                }
                break;
                
            case 'customer_ledger':
                $title = 'Customer Ledger Statement';
                $headers = ['Date', 'Reference / Doc', 'Transaction Type', 'Debit Amount (₹)', 'Credit Amount (₹)', 'Status'];
                // Get all invoices
                $invs = $this->db->select('invoice_date as date, invoice_no as ref, "Invoice Sale" as type, total_amount as debit, paid_amount as credit, status')
                    ->where('invoice_date >=', $from)->where('invoice_date <=', $to)->get('invoices')->result_array();
                // Get all payments
                $pyms = $this->db->select('payment_date as date, payment_no as ref, "Payment Received" as type, 0 as debit, amount as credit, "success" as status')
                    ->where('payment_date >=', $from)->where('payment_date <=', $to)->where('type', 'received')->get('payments')->result_array();
                
                $merged = array_merge($invs, $pyms);
                usort($merged, fn($a, $b) => strcmp($a['date'], $b['date']));
                foreach ($merged as $m) {
                    $rows[] = [$m['date'], $m['ref'], $m['type'], number_format($m['debit'], 2), number_format($m['credit'], 2), strtoupper($m['status'])];
                }
                break;
                
            case 'customer_loyalty':
                $title = 'Loyalty Points Ledger';
                $headers = ['Customer Name', 'Phone', 'Total Orders', 'Total Spent (₹)', 'Loyalty Points Accrued', 'Points Redeemed', 'Current Points Balance'];
                $q = $this->db->select('c.name, c.phone, COUNT(i.id) as bills, SUM(i.total_amount) as spent')
                    ->from('customers c')->join('invoices i', 'i.customer_id = c.id')->where('i.status !=', 'cancelled')
                    ->group_by('c.id')->get()->result_array();
                foreach ($q as $r) {
                    $accrued = round((float)$r['spent'] * 0.01, 0); // 1% points
                    $rows[] = [$r['name'], $r['phone'], $r['bills'], number_format($r['spent'], 2), $accrued, 0, $accrued];
                }
                break;
                
            case 'customer_segments':
                $title = 'RFM Customer Segmentation';
                $headers = ['Customer Name', 'Total Sales (₹)', 'Visits (Invoices)', 'Last Visit Date', 'Segmentation Category'];
                $q = $this->db->select('c.name, SUM(i.total_amount) as spent, COUNT(i.id) as visits, MAX(i.invoice_date) as last_visit')
                    ->from('customers c')->join('invoices i', 'i.customer_id = c.id')->where('i.status !=', 'cancelled')
                    ->group_by('c.id')->get()->result_array();
                foreach ($q as $r) {
                    $seg = 'Standard Regular';
                    if ($r['spent'] > 10000 && $r['visits'] >= 5) {
                        $seg = 'â­ VIP / Loyal';
                    } elseif (strtotime($r['last_visit']) < strtotime('-60 days')) {
                        $seg = 'âš ï¸ Dormant / At Risk';
                    }
                    $rows[] = [$r['name'], number_format($r['spent'], 2), $r['visits'], $r['last_visit'], $seg];
                }
                break;
                
            case 'customer_trend':
                $title = 'Customer Monthly Transaction Trend';
                $headers = ['Month', 'Active Billing Customers', 'Billing Count', 'Total Sales Revenue (₹)', 'Average Invoice Size (₹)'];
                $q = $this->db->select('DATE_FORMAT(invoice_date, "%Y-%m") as month, COUNT(DISTINCT customer_id) as active_c, COUNT(id) as bills, SUM(total_amount) as rev')
                    ->where('invoice_date >=', $from)->where('invoice_date <=', $to)->where('status !=', 'cancelled')
                    ->group_by('month')->order_by('month', 'ASC')->get('invoices')->result_array();
                foreach ($q as $r) {
                    $avg = $r['bills'] > 0 ? $r['rev'] / $r['bills'] : 0;
                    $rows[] = [$r['month'], $r['active_c'], $r['bills'], number_format($r['rev'], 2), number_format($avg, 2)];
                }
                break;
                
            case 'customer_promotions':
                $title = 'Promotional Offers & Recommendations';
                $headers = ['Customer Name', 'Preferred Shopping Category', 'Total Spent in Category (₹)', 'Recommended Product', 'Suggested Discount Campaign'];
                $q = $this->db->select('c.name, p.category, SUM(ii.total) as cat_spent')
                    ->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('customers c', 'c.id = i.customer_id')
                    ->join('products p', 'p.id = ii.product_id')->group_by('c.id, p.category')->order_by('cat_spent', 'DESC')->get()->result_array();
                // Filter top category per customer
                $custs = [];
                foreach ($q as $r) {
                    if (!isset($custs[$r['name']])) {
                        $custs[$r['name']] = $r;
                    }
                }
                foreach ($custs as $name => $c) {
                    $rows[] = [$name, $c['category'], number_format($c['cat_spent'], 2), 'Best selling in ' . $c['category'], '10% OFF ' . $c['category']];
                }
                break;
                
            case 'purchase_summary':
                $title = 'Purchase GRN Summary';
                $headers = ['Date', 'GRN No', 'Supplier Vendor', 'Bill Invoice No', 'Subtotal (₹)', 'Tax GST (₹)', 'Total Amount (₹)', 'Paid (₹)', 'Balance Due (₹)'];
                $this->db->select('pr.invoice_date as purchase_date, pr.purchase_no, s.name as supplier_name, pr.invoice_no, pr.subtotal, pr.total_gst as tax_amount, pr.total_amount, pr.paid_amount, pr.due_amount');
                $this->db->from('purchases pr')->join('suppliers s', 's.id = pr.supplier_id', 'left')->where('pr.invoice_date >=', $from)->where('pr.invoice_date <=', $to);
                $q = $this->db->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['purchase_date'], $r['purchase_no'], $r['supplier_name'] ?? 'N/A', $r['invoice_no'] ? $r['invoice_no'] : 'N/A', number_format($r['subtotal'], 2), number_format($r['tax_amount'], 2), number_format($r['total_amount'], 2), number_format($r['paid_amount'], 2), number_format($r['due_amount'], 2)];
                }
                break;
                
            case 'purchase_detail':
                $title = 'GRN Line Item Details';
                $headers = ['GRN No', 'Date', 'Item Code', 'Item Name', 'Supplier Vendor', 'Qty Purchased', 'Cost Price (₹)', 'Tax GST %', 'Net Total (₹)'];
                $this->db->select('p.purchase_no, p.invoice_date as purchase_date, p_item.item_code, p_item.name as item_name, s.name as supplier_name, pi.qty, pi.price as cost_price, pi.gst_percent, pi.total');
                $this->db->from('purchase_items pi')->join('purchases p', 'p.id = pi.purchase_id')->join('suppliers s', 's.id = p.supplier_id', 'left')->join('products p_item', 'p_item.id = pi.product_id', 'left')->where('p.invoice_date >=', $from)->where('p.invoice_date <=', $to);
                $q = $this->db->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['purchase_no'], $r['purchase_date'], $r['item_code'] ?? 'N/A', $r['item_name'], $r['supplier_name'] ?? 'N/A', $r['qty'], number_format($r['cost_price'], 2), number_format($r['gst_percent'], 2), number_format($r['total'], 2)];
                }
                break;
                
            case 'purchase_returns':
                $title = 'Purchase Returns & Debit Notes';
                $headers = ['Return Date', 'Debit Note No', 'Supplier Vendor', 'Original Purchase Ref', 'Reason / Mode', 'Refund Amount (₹)', 'Tax GST (₹)', 'Total Refund (₹)'];
                $this->db->select('pr.return_date, pr.prn_no as return_no, s.name as supplier_name, p.purchase_no, pr.reason as refund_mode, pr.total_amount as refund_amount, 0 as tax_amount, pr.total_amount');
                $this->db->from('purchase_returns pr')->join('purchases p', 'p.id = pr.purchase_id', 'left')->join('suppliers s', 's.id = pr.supplier_id', 'left')->where('pr.return_date >=', $from)->where('pr.return_date <=', $to);
                $q = $this->db->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['return_date'], $r['return_no'], $r['supplier_name'] ?? 'N/A', $r['purchase_no'] ? $r['purchase_no'] : 'N/A', $r['refund_mode'] ? $r['refund_mode'] : 'N/A', number_format($r['refund_amount'], 2), number_format($r['tax_amount'], 2), number_format($r['total_amount'], 2)];
                }
                break;
                
            case 'inventory_stock':
                $title = 'Inventory Stock Alert Report';
                $headers = ['Item Code', 'Product Name', 'Category', 'Brand', 'Cost (₹)', 'Retail Price (₹)', 'Available Qty', 'Min Threshold', 'Stock Status'];
                $q = $this->db->select('item_code, name, category, brand, cost_price, selling_price, stock_qty, low_stock_threshold')->order_by('stock_qty', 'ASC')->get('products')->result_array();
                foreach ($q as $r) {
                    $status = $r['stock_qty'] <= 0 ? 'ðŸ”´ OUT OF STOCK' : ($r['stock_qty'] <= $r['low_stock_threshold'] ? '🟡 LOW STOCK' : '🟢 OK');
                    $rows[] = [$r['item_code'], $r['name'], $r['category'], $r['brand'] ? $r['brand'] : 'N/A', number_format($r['cost_price'], 2), number_format($r['selling_price'], 2), $r['stock_qty'], $r['low_stock_threshold'], $status];
                }
                break;
                
            case 'inventory_transfer':
                $title = 'Inventory Adjustments & Stock Logs';
                $headers = ['Timestamp', 'Doc ID', 'Item Code', 'Item Name', 'Before Qty', 'Qty Adjusted', 'After Qty', 'Reason Notes', 'Logged By'];
                $this->db->select('sa.created_at, sa.id, p.item_code, p.name as item_name, sa.before_qty, sa.adj_qty, sa.after_qty, sa.reason, u.name as user_name');
                $this->db->from('stock_adjustments sa')->join('products p', 'p.id = sa.product_id')->join('users u', 'u.id = sa.created_by', 'left')->where('DATE(sa.created_at) >=', $from)->where('DATE(sa.created_at) <=', $to);
                $q = $this->db->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['created_at'], 'ADJ-' . $r['id'], $r['item_code'], $r['item_name'], $r['before_qty'], $r['adj_qty'], $r['after_qty'], $r['reason'] ? $r['reason'] : 'Manual Override', $r['user_name'] ? $r['user_name'] : 'System'];
                }
                break;
                
            case 'inventory_valuation':
                $title = 'Stock Asset Valuation';
                $headers = ['Item Category', 'Unique SKU count', 'Total Units in Stock', 'Valuation at Cost (₹)', 'Valuation at Retail (₹)', 'Potential Profit Margin (₹)'];
                $q = $this->db->select('category, COUNT(id) as skus, SUM(GREATEST(stock_qty, 0)) as total_qty, SUM(GREATEST(stock_qty, 0) * cost_price) as cost_val, SUM(GREATEST(stock_qty, 0) * selling_price) as retail_val', FALSE)->group_by('category')->get('products')->result_array();
                foreach ($q as $r) {
                    $profit = (float)$r['retail_val'] - (float)$r['cost_val'];
                    $rows[] = [$r['category'], $r['skus'], $r['total_qty'], number_format($r['cost_val'], 2), number_format($r['retail_val'], 2), number_format($profit, 2)];
                }
                break;
                
            case 'analytics_dashboard':
                $title = 'Key Performance Indicators Dashboard';
                $headers = ['Analytics Business Metric', 'Value Summary', 'Status / Evaluation'];
                // Today sales
                $sales_today = $this->db->select('SUM(total_amount) as total')->where('invoice_date', date('Y-m-d'))->where('status !=', 'cancelled')->get('invoices')->row();
                if ($sales_today) $sales_today->total -= get_refund_summary_for_date(date('Y-m-d'))['total'];
                $total_bills = $this->db->where('invoice_date', date('Y-m-d'))->where('status !=', 'cancelled')->count_all_results('invoices');
                // Low stock
                $low_stock = $this->db->query("SELECT COUNT(*) as count FROM products WHERE stock_qty <= low_stock_threshold")->row();
                // Customers
                $total_customers = $this->db->count_all_results('customers');
                
                $rows[] = ['Today\'s Gross Revenue (incl. GST)', '₹' . number_format($sales_today ? $sales_today->total : 0, 2), ($sales_today && $sales_today->total > 0 ? '🟢 ACTIVE SALES' : '⚪ NO SALES YET')];
                $rows[] = ['Today\'s Invoices Count', $total_bills . ' invoices', ($total_bills > 0 ? '🟢 TRANSACTION FLOWING' : '⚪ WAITING BILLS')];
                $rows[] = ['Low Stock SKUs Warnings', $low_stock->count . ' products', ($low_stock->count > 0 ? '🟡 REPLENISHMENT REQUIRED' : '🟢 STOCK ADEQUATE')];
                $rows[] = ['Total Registered Customers', $total_customers . ' customers', '🟢 MASTER DIRECTORY SYNCED'];
                break;
                
            case 'analytics_trend':
                $title = 'Sales Revenue Trends (incl. GST)';
                $headers = ['Billing Month', 'Transactions Count', 'Gross Revenue (₹, incl. GST)', 'Average Invoice Amount (₹, incl. GST)'];
                $this->db->select('DATE_FORMAT(invoice_date, "%Y-%m") as month, COUNT(id) as bills, SUM(total_amount) as sales')
                    ->where('invoice_date >=', $from)->where('invoice_date <=', $to)->where('status !=', 'cancelled');
                if ($location_id) $this->db->where('location_id', $location_id);
                $q = $this->db->group_by('month')->order_by('month', 'ASC')->get('invoices')->result_array();
                $trend_refund_by_month = [];
                if (!$location_id) {
                    $rr = $this->db->select("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(refund_amount) as amt")
                        ->where('DATE(created_at) >=', $from)->where('DATE(created_at) <=', $to)
                        ->where('status', 'processed')->group_by('month')->get('refunds')->result_array();
                    $trend_refund_by_month = array_column($rr, 'amt', 'month');
                }
                foreach ($q as $r) {
                    $sales = $r['sales'] - (float)($trend_refund_by_month[$r['month']] ?? 0);
                    $avg = $r['bills'] > 0 ? $sales / $r['bills'] : 0;
                    $rows[] = [$r['month'], $r['bills'], number_format($sales, 2), number_format($avg, 2)];
                }
                break;
                
            case 'analytics_top_items':
                $title = 'Top 20 Best Selling Items (excl. GST)';
                $headers = ['Rank', 'Item Code', 'Product Name', 'Category', 'Qty Sold', 'Revenue Contribution (₹, excl. GST)'];
                // taxable_amt (excl. GST), not ii.total (incl. GST) — matches the "Revenue"
                // basis every other sales/analytics report in the system uses (Executive
                // Summary, Profit & Loss, Category Sales), so this stops disagreeing with them.
                $this->db->select('p.item_code, ii.product_name as item_name, p.category, SUM(ii.qty) as qty, SUM(ii.taxable_amt) as revenue')
                    ->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('products p', 'p.id = ii.product_id', 'left')
                    ->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)->where('i.status !=', 'cancelled');
                if ($location_id) $this->db->where('i.location_id', $location_id);
                $q = $this->db->group_by('ii.product_id')->order_by('qty', 'DESC')->limit(20)->get()->result_array();
                $rank = 1;
                foreach ($q as $r) {
                    $rows[] = [$rank++, $r['item_code'] ?? 'N/A', $r['item_name'], $r['category'] ?? 'N/A', $r['qty'], number_format($r['revenue'], 2)];
                }
                break;
                
            case 'analytics_category':
                $title = 'Category Performance Summary (excl. GST)';
                $headers = ['Category Name', 'Products count', 'Items Sold', 'Revenue Contribution (₹, excl. GST)'];
                // taxable_amt (excl. GST) — same basis fix as analytics_top_items above
                $this->db->select('p.category, COUNT(DISTINCT p.id) as products, SUM(ii.qty) as qty, SUM(ii.taxable_amt) as revenue')
                    ->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('products p', 'p.id = ii.product_id')
                    ->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)->where('i.status !=', 'cancelled');
                if ($location_id) $this->db->where('i.location_id', $location_id);
                $q = $this->db->group_by('p.category')->order_by('revenue', 'DESC')->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['category'], $r['products'], $r['qty'], number_format($r['revenue'], 2)];
                }
                break;
                
            case 'analytics_supplier':
                $title = 'Supplier Business Volume Report';
                $headers = ['Supplier Vendor', 'GRN Invoices count', 'Total Purchase value (₹)', 'Remaining Dues (₹)'];
                $q = $this->db->select('s.name, COUNT(p.id) as count, SUM(p.total_amount) as purchases, SUM(p.due_amount) as due')
                    ->from('purchases p')->join('suppliers s', 's.id = p.supplier_id')
                    ->where('p.invoice_date >=', $from)->where('p.invoice_date <=', $to)->group_by('p.supplier_id')->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['name'], $r['count'], number_format($r['purchases'], 2), number_format($r['due'], 2)];
                }
                break;
                
            case 'analytics_velocity':
                $title = 'Inventory Turnover Velocity';
                $headers = ['Item Code', 'Product Name', 'Current Stock Qty', 'Qty Sold (Period)', 'Stock Turnover Status'];
                $q = $this->db->select('p.item_code, p.name, GREATEST(p.stock_qty, 0) AS stock_qty, COALESCE(SUM(ii.qty), 0) as sold', FALSE)
                    ->from('products p')->join('invoice_items ii', 'ii.product_id = p.id', 'left')->group_by('p.id')->get()->result_array();
                foreach ($q as $r) {
                    $vel = 'Slow (Low Turn)';
                    if ($r['sold'] > 50) {
                        $vel = 'ðŸš€ Fast Moving';
                    } elseif ($r['sold'] > 10) {
                        $vel = 'Medium';
                    }
                    $rows[] = [$r['item_code'], $r['name'], $r['stock_qty'], $r['sold'], $vel];
                }
                break;
                
            case 'analytics_profitability':
                $title = 'Profit margins Analysis';
                $headers = ['Item Name', 'Category', 'Selling Price (₹)', 'Cost Price (₹)', 'Gross Margin Amount (₹)', 'Margin Profit %'];
                $q = $this->db->select('name, category, cost_price, selling_price')->get('products')->result_array();
                foreach ($q as $r) {
                    $margin = (float)$r['selling_price'] - (float)$r['cost_price'];
                    $pct = $r['cost_price'] > 0 ? ($margin / $r['cost_price']) * 100 : 0;
                    $rows[] = [$r['name'], $r['category'], number_format($r['selling_price'], 2), number_format($r['cost_price'], 2), number_format($margin, 2), number_format($pct, 2) . '%'];
                }
                break;
                
            case 'analytics_sales_vs_purchase':
                $title = 'Revenue Sales vs Inventory Procurement Match (incl. GST)';
                $headers = ['Month', 'Sales Revenue (₹, incl. GST)', 'Purchase Spend (₹, incl. GST)', 'Spend Ratio %'];
                $sales = $this->db->select('DATE_FORMAT(invoice_date, "%Y-%m") as month, SUM(total_amount) as amount')
                    ->where('invoice_date >=', $from)->where('invoice_date <=', $to)->where('status !=', 'cancelled')->group_by('month')->get('invoices')->result_array();
                $purch = $this->db->select('DATE_FORMAT(invoice_date, "%Y-%m") as month, SUM(total_amount) as amount')
                    ->where('invoice_date >=', $from)->where('invoice_date <=', $to)->group_by('month')->get('purchases')->result_array();
                
                $timeline = [];
                foreach ($sales as $s) { $timeline[$s['month']]['sales'] = (float)$s['amount']; }
                foreach ($purch as $p) { $timeline[$p['month']]['purch'] = (float)$p['amount']; }
                $svp_refund_by_month = $this->db->select("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(refund_amount) as amt")
                    ->where('DATE(created_at) >=', $from)->where('DATE(created_at) <=', $to)
                    ->where('status', 'processed')->group_by('month')->get('refunds')->result_array();
                $svp_refund_map = array_column($svp_refund_by_month, 'amt', 'month');
                foreach ($svp_refund_map as $m => $amt) {
                    if (isset($timeline[$m]['sales'])) $timeline[$m]['sales'] -= (float)$amt;
                }
                ksort($timeline);
                foreach ($timeline as $m => $v) {
                    $s_val = $v['sales'] ?? 0.0;
                    $p_val = $v['purch'] ?? 0.0;
                    $ratio = $s_val > 0 ? ($p_val / $s_val) * 100 : 0;
                    $rows[] = [$m, number_format($s_val, 2), number_format($p_val, 2), number_format($ratio, 2) . '%'];
                }
                break;
                
            case 'analytics_exceptions':
                $title = 'Audit logs & Exception overrides';
                $headers = ['Timestamp', 'Username', 'Action Overrides', 'Module affected', 'Audit Details'];
                $this->db->select('created_at, user_name, action, module, description');
                $this->db->where('DATE(created_at) >=', $from)->where('DATE(created_at) <=', $to);
                $this->db->group_start()->like('action', 'delete')->or_like('action', 'update')->or_like('description', 'cancelled')->group_end();
                $q = $this->db->order_by('id', 'DESC')->get('audit_logs')->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['created_at'], $r['user_name'], strtoupper($r['action']), strtoupper($r['module']), $r['description']];
                }
                break;
                
            case 'gst_hsn_sales':
                $title = 'GSTR-1 Table 12 HSN outward sales';
                $headers = ['HSN Code', 'Item Description', 'GST tax rate %', 'Taxable Value (₹)', 'CGST collected (₹)', 'SGST collected (₹)', 'Total Tax (₹)'];
                $this->db->select('p.hsn_code, p.name as description, ii.gst_percent, SUM(ii.taxable_amt) as taxable, SUM(ii.total - ii.taxable_amt) as tax');
                $this->db->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('products p', 'p.id = ii.product_id', 'left');
                $this->db->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)->where('i.status !=', 'cancelled');
                if ($location_id) $this->db->where('i.location_id', $location_id);
                $q = $this->db->group_by('p.hsn_code, ii.gst_percent')->get()->result_array();
                foreach ($q as $r) {
                    if (empty($r['hsn_code'])) continue;
                    $tax = (float)$r['tax'];
                    $rows[] = [$r['hsn_code'], $r['description'], number_format($r['gst_percent'], 2), number_format($r['taxable'], 2), number_format($tax / 2, 2), number_format($tax / 2, 2), number_format($tax, 2)];
                }
                break;
                
            case 'gst_hsn_purchase':
                $title = 'HSN inward purchases summary';
                $headers = ['HSN Code', 'Item Name', 'GST rate %', 'Taxable Value (₹)', 'CGST input (₹)', 'SGST input (₹)', 'Total ITC (₹)'];
                $this->db->select('p.hsn_code, p.name as description, pi.gst_percent, SUM(pi.taxable_amount) as taxable, SUM(pi.total - pi.taxable_amount) as tax');
                $this->db->from('purchase_items pi')->join('purchases pr', 'pr.id = pi.purchase_id')->join('products p', 'p.id = pi.product_id', 'left');
                $this->db->where('pr.invoice_date >=', $from)->where('pr.invoice_date <=', $to);
                $q = $this->db->group_by('p.hsn_code, pi.gst_percent')->get()->result_array();
                foreach ($q as $r) {
                    if (empty($r['hsn_code'])) continue;
                    $tax = (float)$r['tax'];
                    $rows[] = [$r['hsn_code'], $r['description'], number_format($r['gst_percent'], 2), number_format($r['taxable'], 2), number_format($tax / 2, 2), number_format($tax / 2, 2), number_format($tax, 2)];
                }
                break;
                
            case 'gst_hsn_comparison':
                $title = 'HSN Sales vs Purchase Audit verification';
                $headers = ['HSN Code', 'Sales Taxable (₹)', 'Sales Tax (₹)', 'Purchase Taxable (₹)', 'Purchase Tax (₹)', 'Net tax liability (₹)'];
                // Query sales
                $sales_hsn = $this->db->select('p.hsn_code, SUM(ii.taxable_amt) as taxable, SUM(ii.total - ii.taxable_amt) as tax')->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('products p', 'p.id = ii.product_id', 'left')
                    ->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)->where('i.status !=', 'cancelled')->group_by('p.hsn_code')->get()->result_array();
                // Query purchases
                $purch_hsn = $this->db->select('p.hsn_code, SUM(pi.taxable_amount) as taxable, SUM(pi.total - pi.taxable_amount) as tax')->from('purchase_items pi')->join('purchases pr', 'pr.id = pi.purchase_id')->join('products p', 'p.id = pi.product_id', 'left')
                    ->where('pr.invoice_date >=', $from)->where('pr.invoice_date <=', $to)->group_by('p.hsn_code')->get()->result_array();
                
                $hsn_map = [];
                foreach ($sales_hsn as $s) {
                    if (empty($s['hsn_code'])) continue;
                    $hsn_map[$s['hsn_code']]['s_taxable'] = (float)$s['taxable'];
                    $hsn_map[$s['hsn_code']]['s_tax'] = (float)$s['tax'];
                }
                foreach ($purch_hsn as $p) {
                    if (empty($p['hsn_code'])) continue;
                    $hsn_map[$p['hsn_code']]['p_taxable'] = (float)$p['taxable'];
                    $hsn_map[$p['hsn_code']]['p_tax'] = (float)$p['tax'];
                }
                foreach ($hsn_map as $hsn => $v) {
                    $s_taxable = $v['s_taxable'] ?? 0.0;
                    $s_tax     = $v['s_tax'] ?? 0.0;
                    $p_taxable = $v['p_taxable'] ?? 0.0;
                    $p_tax     = $v['p_tax'] ?? 0.0;
                    $liab      = $s_tax - $p_tax;
                    $rows[] = [$hsn, number_format($s_taxable, 2), number_format($s_tax, 2), number_format($p_taxable, 2), number_format($p_tax, 2), number_format($liab, 2)];
                }
                break;
                
            case 'gst_gstr1':
                $title = 'GSTR-1 Sales Compliance Assistant';
                $headers = ['Invoice No', 'Date', 'Customer Name', 'GSTIN / State', 'Taxable Amount (₹)', 'Tax rate %', 'CGST (₹)', 'SGST (₹)', 'Grand Total (₹)'];
                $this->db->select('i.invoice_no, i.invoice_date, c.name, c.gst_number, c.state, ii.taxable_amt as subtotal, ii.gst_percent, ii.total');
                $this->db->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('customers c', 'c.id = i.customer_id', 'left');
                $this->db->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)->where('i.status !=', 'cancelled');
                if ($location_id) $this->db->where('i.location_id', $location_id);
                $q = $this->db->get()->result_array();
                foreach ($q as $r) {
                    $tax = (float)$r['total'] - (float)$r['subtotal'];
                    $rows[] = [$r['invoice_no'], $r['invoice_date'], $r['name'] ?? 'Walk-in', $r['gst_number'] ? $r['gst_number'] : ($r['state'] ? $r['state'] : 'Local state'), number_format($r['subtotal'], 2), number_format($r['gst_percent'], 2), number_format($tax / 2, 2), number_format($tax / 2, 2), number_format($r['total'], 2)];
                }
                break;
                
            case 'gst_gstr3b':
                $title = 'GSTR-3B monthly summary';
                $headers = ['GST Form Table section', 'Taxable Supplies Value (₹)', 'IGST liability (₹)', 'CGST liability (₹)', 'SGST liability (₹)', 'Total Tax liability (₹)'];
                // Delegates to the same model methods the dedicated GSTR-3B report (reports/gstr3b)
                // uses, so the two never disagree. This used to reimplement its own version that:
                // (a) used pre-discount `subtotal` instead of post-discount `taxable_amount` for the
                // taxable value line, and (b) always split tax into CGST+SGST even for interstate
                // sales, which are IGST-only — a real GST-filing bug once any interstate sale occurs.
                $output = $this->Report_model->get_gstr3b_output($from, $to);
                $input  = $this->Report_model->get_gstr3b_input($from, $to);

                $net_cgst = max(0, (float)$output['cgst'] - (float)$input['cgst']);
                $net_sgst = max(0, (float)$output['sgst'] - (float)$input['sgst']);
                $net_igst = max(0, (float)$output['igst'] - (float)$input['igst']);

                $rows[] = ['Table 3.1(a) Outward Taxable Supplies', number_format($output['taxable_value'], 2), number_format($output['igst'], 2), number_format($output['cgst'], 2), number_format($output['sgst'], 2), number_format($output['total'], 2)];
                $rows[] = ['Table 4(A)(5) All Other Eligible ITC Inputs', number_format($input['taxable_value'], 2), number_format($input['igst'], 2), number_format($input['cgst'], 2), number_format($input['sgst'], 2), number_format($input['total'], 2)];
                $rows[] = ['Table 6.1 Net Tax Payable in Cash', '0.00', number_format($net_igst, 2), number_format($net_cgst, 2), number_format($net_sgst, 2), number_format($net_igst + $net_cgst + $net_sgst, 2)];
                break;
                
            case 'gst_itc':
                $title = 'Input Tax Credit (ITC) tracker';
                $headers = ['Purchase Date', 'GRN Ref', 'Supplier Name', 'Supplier GSTIN', 'Taxable Cost Value (₹)', 'Input CGST (₹)', 'Input SGST (₹)', 'Total Input ITC (₹)'];
                $this->db->select('pr.invoice_date as purchase_date, pr.purchase_no, s.name, s.gst_number, pr.subtotal, pr.total_gst as tax_amount');
                $this->db->from('purchases pr')->join('suppliers s', 's.id = pr.supplier_id', 'left')->where('pr.invoice_date >=', $from)->where('pr.invoice_date <=', $to);
                $q = $this->db->get()->result_array();
                foreach ($q as $r) {
                    $tax = (float)$r['tax_amount'];
                    $rows[] = [$r['purchase_date'], $r['purchase_no'], $r['name'] ?? 'N/A', $r['gst_number'] ? $r['gst_number'] : 'N/A', number_format($r['subtotal'], 2), number_format($tax / 2, 2), number_format($tax / 2, 2), number_format($tax, 2)];
                }
                break;
                
            case 'gst_cdnr':
                $title = 'GSTR-1 Credit & Debit notes';
                $headers = ['Credit Note No', 'Return Date', 'Original invoice', 'Customer Name', 'Customer GSTIN', 'Refund Amount (₹)', 'Tax Amount (₹)', 'Total Refund Value (₹)'];
                $this->db->select('sr.return_date, sr.return_number as return_no, i.invoice_no, c.name, c.gst_number, sr.total_amount as refund_amount, sr.total_gst as tax_amount, sr.total_amount');
                $this->db->from('sales_returns sr')->join('invoices i', 'i.id = sr.invoice_id', 'left')->join('customers c', 'c.id = sr.customer_id', 'left')->where('sr.return_date >=', $from)->where('sr.return_date <=', $to);
                $q = $this->db->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['return_no'], $r['return_date'], $r['invoice_no'] ? $r['invoice_no'] : 'N/A', $r['name'] ?? 'N/A', $r['gst_number'] ? $r['gst_number'] : 'N/A', number_format($r['refund_amount'], 2), number_format($r['tax_amount'], 2), number_format($r['total_amount'], 2)];
                }
                break;

            case 'expiry_alert':
                $days = (int)($this->input->get('days', TRUE) ?: 30);
                $title = "Near-Expiry Alert (Next {$days} Days)";
                $headers = ['Item Code', 'Product', 'Location', 'Qty in Stock', 'Expiry Date', 'Days Left'];
                $this->db->select('p.item_code, p.name as product_name, l.name as location_name, GREATEST(pls.stock_qty, 0) AS stock_qty, p.expiry_date, DATEDIFF(p.expiry_date, CURDATE()) as days_left');
                $this->db->from('product_location_stock pls');
                $this->db->join('products p', 'p.id = pls.product_id');
                $this->db->join('locations l', 'l.id = pls.location_id');
                $this->db->where('p.expiry_date IS NOT NULL');
                $this->db->where('p.expiry_date <=', date('Y-m-d', strtotime("+{$days} days")));
                $this->db->where('pls.stock_qty >', 0);
                if ($location_id) $this->db->where('pls.location_id', $location_id);
                $this->db->order_by('p.expiry_date', 'ASC');
                $q = $this->db->get()->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['item_code'] ?? 'N/A', $r['product_name'], $r['location_name'], number_format($r['stock_qty'], 2), date('d-m-Y', strtotime($r['expiry_date'])), $r['days_left'] < 0 ? 'EXPIRED (' . abs($r['days_left']) . 'd ago)' : $r['days_left']];
                }
                break;

            case 'vendor_scorecard':
                $title = 'Vendor Scorecard';
                $headers = ['Supplier', 'Tier', 'Total Orders', 'On-Time %', 'Fill Rate %', 'Avg Delivery Days', 'Quality Rating', 'Return %', 'Overall Score', 'Avg RFQ Response (hrs)'];
                $q = $this->db->query("
                    SELECT s.name,
                           COALESCE(vp.tier, s.vendor_tier, 'unrated') as tier,
                           COALESCE(vp.total_orders, 0) as total_orders,
                           COALESCE(vp.ontime_delivery_pct, 0) as ontime_pct,
                           COALESCE(vp.fill_rate_pct, 0) as fill_rate_pct,
                           COALESCE(vp.avg_delivery_days, 0) as avg_delivery_days,
                           COALESCE(vp.quality_rating, 0) as quality_rating,
                           COALESCE(vp.return_pct, 0) as return_pct,
                           COALESCE(vp.overall_score, 0) as overall_score,
                           (SELECT AVG(TIMESTAMPDIFF(HOUR, rs.sent_at, rq.submitted_at))
                            FROM rfq_suppliers rs JOIN rfq_quotations rq ON rq.rfq_id = rs.rfq_id AND rq.supplier_id = rs.supplier_id
                            WHERE rs.supplier_id = s.id AND rq.submitted_at IS NOT NULL) as avg_response_hrs
                    FROM suppliers s
                    LEFT JOIN vendor_performance vp ON vp.supplier_id = s.id
                    WHERE s.status = 1
                    ORDER BY overall_score DESC
                ")->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['name'], ucfirst($r['tier']), $r['total_orders'], number_format($r['ontime_pct'], 1), number_format($r['fill_rate_pct'], 1), number_format($r['avg_delivery_days'], 1), number_format($r['quality_rating'], 1), number_format($r['return_pct'], 1), number_format($r['overall_score'], 1), $r['avg_response_hrs'] !== null ? number_format($r['avg_response_hrs'], 1) : 'N/A'];
                }
                break;

            case 'offline_sync_health':
                $title = 'Offline Sync Health';
                $headers = ['Device', 'Total Queued', 'Synced', 'Failed', 'Pending', 'Avg Retry Count', 'Last Synced', 'Last Error', 'Stuck (>6h Pending)'];
                $q = $this->db->query("
                    SELECT osq.device_id,
                           COALESCE(da.device_label, osq.device_id) as device_label,
                           COUNT(*) as total,
                           SUM(CASE WHEN osq.status='synced' THEN 1 ELSE 0 END) as synced_cnt,
                           SUM(CASE WHEN osq.status='failed' THEN 1 ELSE 0 END) as failed_cnt,
                           SUM(CASE WHEN osq.status='pending' THEN 1 ELSE 0 END) as pending_cnt,
                           AVG(osq.retry_count) as avg_retry,
                           MAX(osq.synced_at) as last_synced,
                           MAX(CASE WHEN osq.status='failed' THEN osq.last_error END) as last_error,
                           SUM(CASE WHEN osq.status='pending' AND osq.created_at < DATE_SUB(NOW(), INTERVAL 6 HOUR) THEN 1 ELSE 0 END) as stuck_cnt
                    FROM offline_sync_queue osq
                    LEFT JOIN device_authorizations da ON da.device_id COLLATE utf8mb4_general_ci = osq.device_id COLLATE utf8mb4_general_ci
                    WHERE osq.created_at BETWEEN ? AND ?
                    GROUP BY osq.device_id
                    ORDER BY failed_cnt DESC, stuck_cnt DESC
                ", [$from . ' 00:00:00', $to . ' 23:59:59'])->result_array();
                foreach ($q as $r) {
                    $rows[] = [$r['device_label'], $r['total'], $r['synced_cnt'], $r['failed_cnt'], $r['pending_cnt'], number_format($r['avg_retry'], 1), $r['last_synced'] ?: 'Never', $r['last_error'] ? mb_strimwidth($r['last_error'], 0, 60, '...') : '-', $r['stuck_cnt'] > 0 ? "⚠ {$r['stuck_cnt']}" : '-'];
                }
                break;

            case 'scheme_roi':
                $title = 'Scheme ROI';
                $headers = ['Scheme', 'Benefit Type', 'Invoices Applied', 'Total Discount (₹)', 'Influenced Revenue (₹)', 'Discount % of Revenue'];
                $q = $this->db->query("
                    SELECT sal.scheme_id, sal.scheme_name, sal.benefit_type,
                           COUNT(DISTINCT sal.invoice_id) as invoice_count,
                           SUM(sal.discount_given) as total_discount,
                           (SELECT COALESCE(SUM(i.total_amount),0) FROM invoices i
                            WHERE i.id IN (SELECT DISTINCT sal2.invoice_id FROM scheme_application_log sal2
                                           WHERE sal2.scheme_id = sal.scheme_id AND sal2.applied_at BETWEEN ? AND ?)
                           ) as influenced_revenue
                    FROM scheme_application_log sal
                    WHERE sal.applied_at BETWEEN ? AND ?
                    GROUP BY sal.scheme_id, sal.scheme_name, sal.benefit_type
                    ORDER BY total_discount DESC
                ", [$from . ' 00:00:00', $to . ' 23:59:59', $from . ' 00:00:00', $to . ' 23:59:59'])->result_array();
                foreach ($q as $r) {
                    $pct = $r['influenced_revenue'] > 0 ? ($r['total_discount'] / $r['influenced_revenue']) * 100 : 0;
                    $rows[] = [$r['scheme_name'], ucwords(str_replace('_', ' ', $r['benefit_type'])), $r['invoice_count'], number_format($r['total_discount'], 2), number_format($r['influenced_revenue'], 2), number_format($pct, 2) . '%'];
                }
                break;
        }

        $data['report_title'] = $title;
        $data['headers']      = $headers;
        $data['rows']         = $rows;
        $data['from']         = $from;
        $data['to']           = $to;
        $data['type']         = $key;
        $data['cashier_id']   = '';
        $data['is_fmcg']      = TRUE;
        $data['location_id']  = $location_id;
        $loc_data = $this->_locations_data();
        $data['locations']    = $loc_data['locations'];

        // Retrieve company settings for print templates
        $data['company'] = $this->db->get('company_settings')->result_array();
        $company_mapped = [];
        foreach ($data['company'] as $row) {
            $company_mapped[$row['key_name']] = $row['key_value'];
        }
        $data['company'] = $company_mapped;
        
        // Handle standard CodeIgniter CSV export
        $export = $this->input->get('export', TRUE);
        if ($export === 'excel') {
            $this->_export_excel($title, $headers, $rows);
            return;
        } elseif ($export === 'pdf') {
            $autoload = APPPATH . '../vendor/autoload.php';
            if (file_exists($autoload)) {
                $this->load->library('pdf_generator');
                $html = $this->load->view('reports/report_viewer_pdf', $data, TRUE);
                $this->pdf_generator->generate($html, str_replace(' ', '_', $title) . '_' . date('Ymd'), 'download', 'A4');
                return;
            }
        }
        
        $this->render('reports/report_viewer', $data);
    }

    // â”€â”€ GSTR-1 Native JSON Exporter â”€â”€
    public function export_gstr1_json() {
        $year  = $this->input->get('year', TRUE) ? (int)$this->input->get('year') : (int)date('Y');
        $month = $this->input->get('month', TRUE) ? (int)$this->input->get('month') : (int)date('m');
        
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        
        // Fetch company GSTIN
        $settings_row = $this->db->where('key_name', 'company_gstin')->get('company_settings')->row();
        $gstin = $settings_row ? strtoupper($settings_row->key_value) : '06AAAAA1111A1Z1';
        
        // Query unregistered small sales (B2CS)
        $b2cs = $this->db->select('c.state, ii.gst_percent, SUM(ii.taxable_amt) as taxable, SUM(ii.total - ii.taxable_amt) as tax')
            ->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('customers c', 'c.id = i.customer_id', 'left')
            ->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)->where('i.status !=', 'cancelled')->where('i.total_amount <', 250000)
            ->group_start()->where('c.gst_number', NULL)->or_where('c.gst_number', '')->group_end()
            ->group_by('c.state, ii.gst_percent')->get()->result_array();
            
        $b2cs_data = [];
        foreach ($b2cs as $b) {
            $b2cs_data[] = [
                "rt" => (float)$b['gst_percent'],
                "typ" => "OE",
                "txval" => round((float)$b['taxable'], 2),
                "iamt" => 0.0,
                "camt" => round((float)$b['tax'] / 2, 2),
                "samt" => round((float)$b['tax'] / 2, 2),
                "csamt" => 0.0
            ];
        }
        
        // Query HSN summary
        $hsn = $this->db->select('p.hsn_code, p.name, p.unit, SUM(ii.qty) as qty, SUM(ii.total) as val, SUM(ii.taxable_amt) as taxable, SUM(ii.total - ii.taxable_amt) as tax')
            ->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('products p', 'p.id = ii.product_id', 'left')
            ->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)->where('i.status !=', 'cancelled')
            ->group_by('p.hsn_code')->get()->result_array();
            
        $hsn_data = [];
        $num = 1;
        foreach ($hsn as $h) {
            if (empty($h['hsn_code'])) continue;
            $hsn_data[] = [
                "num" => $num++,
                "hsn_sc" => str_replace(' ', '', $h['hsn_code']),
                "desc" => $h['name'],
                "uqc" => $h['unit'] === 'KG' ? 'KGS' : ($h['unit'] === 'LTR' ? 'LTR' : 'NOS'),
                "qty" => (float)$h['qty'],
                "val" => round((float)$h['val'], 2),
                "txval" => round((float)$h['taxable'], 2),
                "iamt" => 0.0,
                "camt" => round((float)$h['tax'] / 2, 2),
                "samt" => round((float)$h['tax'] / 2, 2),
                "csamt" => 0.0
            ];
        }
        
        // Query Doc summary
        $total_bills = $this->db->where('invoice_date >=', $from)->where('invoice_date <=', $to)->where('status !=', 'cancelled')->count_all_results('invoices');
        $cancelled_bills = $this->db->where('invoice_date >=', $from)->where('invoice_date <=', $to)->where('status', 'cancelled')->count_all_results('invoices');
        $first_bill = $this->db->select('MIN(invoice_no) as first')->where('invoice_date >=', $from)->where('invoice_date <=', $to)->get('invoices')->row();
        $last_bill = $this->db->select('MAX(invoice_no) as last')->where('invoice_date >=', $from)->where('invoice_date <=', $to)->get('invoices')->row();
        
        $gstr1_json = [
            "version" => "GST3.0.4",
            "hash" => "hash",
            "gstin" => $gstin,
            "fp" => sprintf('%02d%04d', $month, $year),
            "gt" => 0.0,
            "cur_gt" => 0.0,
            "b2b" => [],
            "b2cl" => [],
            "b2cs" => $b2cs_data,
            "cdnr" => [],
            "cdnur" => [],
            "exp" => [],
            "nil" => ["inv" => []],
            "hsn" => ["data" => $hsn_data],
            "doc" => [
                "doc_det" => [
                    [
                        "doc_num" => 1,
                        "docs" => [
                            [
                                "num" => 1,
                                "from" => $first_bill ? $first_bill->first : '0',
                                "to" => $last_bill ? $last_bill->last : '0',
                                "totnum" => $total_bills + $cancelled_bills,
                                "cancel" => $cancelled_bills,
                                "net_issue" => $total_bills
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="GSTR1_' . $gstin . '_' . sprintf('%02d%04d', $month, $year) . '.json"');
        echo json_encode($gstr1_json, JSON_PRETTY_PRINT);
        exit;
    }

    // â”€â”€ GSTR-1 Native Excel Exporter using PhpSpreadsheet â”€â”€
    public function export_gstr1_excel() {
        $year  = $this->input->get('year', TRUE) ? (int)$this->input->get('year') : (int)date('Y');
        $month = $this->input->get('month', TRUE) ? (int)$this->input->get('month') : (int)date('m');
        
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));
        
        // Fetch registered customer sales (B2B)
        $b2b_data = $this->db->select('c.gst_number, c.name, i.invoice_no, i.invoice_date, i.total_amount, c.state, ii.gst_percent, ii.taxable_amt as subtotal')
            ->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('customers c', 'c.id = i.customer_id')
            ->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)->where('i.status !=', 'cancelled')
            ->where('c.gst_number !=', NULL)->where('c.gst_number !=', '')->get()->result_array();
            
        // Query unregistered small sales (B2CS)
        $b2cs_data = $this->db->select('c.state, ii.gst_percent, SUM(ii.taxable_amt) as taxable, SUM(ii.total - ii.taxable_amt) as tax')
            ->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('customers c', 'c.id = i.customer_id', 'left')
            ->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)->where('i.status !=', 'cancelled')
            ->group_start()->where('c.gst_number', NULL)->or_where('c.gst_number', '')->group_end()
            ->group_by('c.state, ii.gst_percent')->get()->result_array();
            
        // Query HSN summary
        $hsn_data = $this->db->select('p.hsn_code, p.name, p.unit, SUM(ii.qty) as qty, SUM(ii.total) as val, SUM(ii.taxable_amt) as taxable, SUM(ii.total - ii.taxable_amt) as tax')
            ->from('invoice_items ii')->join('invoices i', 'i.id = ii.invoice_id')->join('products p', 'p.id = ii.product_id', 'left')
            ->where('i.invoice_date >=', $from)->where('i.invoice_date <=', $to)->where('i.status !=', 'cancelled')
            ->group_by('p.hsn_code')->get()->result_array();
            
        // Query Doc summary
        $total_bills = $this->db->where('invoice_date >=', $from)->where('invoice_date <=', $to)->where('status !=', 'cancelled')->count_all_results('invoices');
        $cancelled_bills = $this->db->where('invoice_date >=', $from)->where('invoice_date <=', $to)->where('status', 'cancelled')->count_all_results('invoices');
        $first_bill = $this->db->select('MIN(invoice_no) as first')->where('invoice_date >=', $from)->where('invoice_date <=', $to)->get('invoices')->row();
        $last_bill = $this->db->select('MAX(invoice_no) as last')->where('invoice_date >=', $from)->where('invoice_date <=', $to)->get('invoices')->row();

        // Create Spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        
        // â”€â”€â”€ Sheet 1: b2b â”€â”€â”€
        $sheet_b2b = $spreadsheet->createSheet();
        $sheet_b2b->setTitle('b2b');
        $sheet_b2b->setCellValue('A1', 'GSTIN/UIN of Recipient')
                  ->setCellValue('B1', 'Receiver Name')
                  ->setCellValue('C1', 'Invoice Number')
                  ->setCellValue('D1', 'Invoice date')
                  ->setCellValue('E1', 'Invoice Value')
                  ->setCellValue('F1', 'Place Of Supply')
                  ->setCellValue('G1', 'Reverse Charge')
                  ->setCellValue('H1', 'Applicable % of Tax Rate')
                  ->setCellValue('I1', 'Invoice Type')
                  ->setCellValue('J1', 'E-Commerce GSTIN')
                  ->setCellValue('K1', 'Rate')
                  ->setCellValue('L1', 'Taxable Value')
                  ->setCellValue('M1', 'Cess Amount');
                  
        $row_idx = 2;
        foreach ($b2b_data as $b) {
            $sheet_b2b->setCellValue('A' . $row_idx, $b['gst_number'])
                      ->setCellValue('B' . $row_idx, $b['name'])
                      ->setCellValue('C' . $row_idx, $b['invoice_no'])
                      ->setCellValue('D' . $row_idx, $b['invoice_date'])
                      ->setCellValue('E' . $row_idx, (float)$b['total_amount'])
                      ->setCellValue('F' . $row_idx, $b['state'] ? $b['state'] : '06-Haryana')
                      ->setCellValue('G' . $row_idx, 'N')
                      ->setCellValue('H' . $row_idx, '')
                      ->setCellValue('I' . $row_idx, 'Regular')
                      ->setCellValue('J' . $row_idx, '')
                      ->setCellValue('K' . $row_idx, (float)$b['gst_percent'])
                      ->setCellValue('L' . $row_idx, (float)$b['subtotal'])
                      ->setCellValue('M' . $row_idx, 0.0);
            $row_idx++;
        }
        
        // â”€â”€â”€ Sheet 2: b2cs â”€â”€â”€
        $sheet_b2cs = $spreadsheet->createSheet();
        $sheet_b2cs->setTitle('b2cs');
        $sheet_b2cs->setCellValue('A1', 'Type')
                   ->setCellValue('B1', 'Place Of Supply')
                   ->setCellValue('C1', 'Applicable % of Tax Rate')
                   ->setCellValue('D1', 'Rate')
                   ->setCellValue('E1', 'Taxable Value')
                   ->setCellValue('F1', 'Cess Amount')
                   ->setCellValue('G1', 'E-Commerce GSTIN');
                   
        $row_idx = 2;
        foreach ($b2cs_data as $b) {
            $sheet_b2cs->setCellValue('A' . $row_idx, 'OE')
                       ->setCellValue('B' . $row_idx, $b['state'] ? $b['state'] : '06-Haryana')
                       ->setCellValue('C' . $row_idx, '')
                       ->setCellValue('D' . $row_idx, (float)$b['gst_percent'])
                       ->setCellValue('E' . $row_idx, (float)$b['taxable'])
                       ->setCellValue('F' . $row_idx, 0.0)
                       ->setCellValue('G' . $row_idx, '');
            $row_idx++;
        }
        
        // â”€â”€â”€ Sheet 3: hsn â”€â”€â”€
        $sheet_hsn = $spreadsheet->createSheet();
        $sheet_hsn->setTitle('hsn');
        $sheet_hsn->setCellValue('A1', 'HSN')
                  ->setCellValue('B1', 'Description')
                  ->setCellValue('C1', 'UQC')
                  ->setCellValue('D1', 'Total Quantity')
                  ->setCellValue('E1', 'Total Value')
                  ->setCellValue('F1', 'Rate')
                  ->setCellValue('G1', 'Taxable Value')
                  ->setCellValue('H1', 'Integrated Tax Amount')
                  ->setCellValue('I1', 'Central Tax Amount')
                  ->setCellValue('J1', 'State/UT Tax Amount')
                  ->setCellValue('K1', 'Cess Amount');
                  
        $row_idx = 2;
        foreach ($hsn_data as $h) {
            if (empty($h['hsn_code'])) continue;
            $sheet_hsn->setCellValue('A' . $row_idx, $h['hsn_code'])
                      ->setCellValue('B' . $row_idx, $h['name'])
                      ->setCellValue('C' . $row_idx, $h['unit'] === 'KG' ? 'KGS-KILOGRAMS' : ($h['unit'] === 'LTR' ? 'LTR-LITRES' : 'NOS-NUMBERS'))
                      ->setCellValue('D' . $row_idx, (float)$h['qty'])
                      ->setCellValue('E' . $row_idx, (float)$h['val'])
                      ->setCellValue('F' . $row_idx, 0.0)
                      ->setCellValue('G' . $row_idx, (float)$h['taxable'])
                      ->setCellValue('H' . $row_idx, 0.0)
                      ->setCellValue('I' . $row_idx, (float)$h['tax'] / 2)
                      ->setCellValue('J' . $row_idx, (float)$h['tax'] / 2)
                      ->setCellValue('K' . $row_idx, 0.0);
            $row_idx++;
        }
        
        // â”€â”€â”€ Sheet 4: docs â”€â”€â”€
        $sheet_docs = $spreadsheet->createSheet();
        $sheet_docs->setTitle('docs');
        $sheet_docs->setCellValue('A1', 'Nature of Document')
                   ->setCellValue('B1', 'Sr. No. From')
                   ->setCellValue('C1', 'Sr. No. To')
                   ->setCellValue('D1', 'Total Number')
                   ->setCellValue('E1', 'Cancelled');
                   
        $sheet_docs->setCellValue('A2', 'Invoices for outward supply')
                   ->setCellValue('B2', $first_bill ? $first_bill->first : '')
                   ->setCellValue('C2', $last_bill ? $last_bill->last : '')
                   ->setCellValue('D2', $total_bills + $cancelled_bills)
                   ->setCellValue('E2', $cancelled_bills);
                   
        // Output file
        while (ob_get_level() > 0) { @ob_end_clean(); }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="GSTR1_Filing_Report_' . $year . '_' . sprintf('%02d', $month) . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // â”€â”€ Location Stock Report â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function location_stock()
    {
        $location_id = $this->_get_loc_id() ?: (int)$this->input->get('location_id');
        $locations   = $this->_locations_data()['locations'];
        if (!$location_id && !empty($locations)) {
            $location_id = $locations[0]->id;
        }
        $location = $location_id ? $this->Location_model->get_by_id($location_id) : null;
        $stock    = $location ? $this->Location_model->get_location_stock($location_id) : [];

        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);
        $title  = 'Location Stock Report' . ($location ? ' — ' . $location->name : '');
        $ep     = 'location_id=' . $location_id;
        $headers = ['#','Product','SKU','Unit','Stock Qty','Min Stock','Sale Price','Status'];
        $rows = []; $i = 1;
        foreach ($stock as $p) {
            $status = $p->stock_qty <= 0 ? 'Out of Stock' : ($p->stock_qty <= $p->min_stock ? 'Low' : 'OK');
            $rows[] = [$i++, $p->name, $p->item_code, $p->unit, number_format($p->stock_qty,3), $p->min_stock, number_format($p->selling_price,2), $status];
        }
        if ($export==='excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title,$headers,$rows); return; }
        if ($export==='pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title,$headers,$rows); return; }
        if ($export==='email') { $this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/location_stock?'.$ep)); return; }

        $data['locations']        = $locations;
        $data['current_location'] = $location;
        $data['stock']            = $stock;
        $data['stock_value']      = $location ? $this->Location_model->get_stock_value($location_id) : 0;
        $data['page_title']       = 'Location Stock Report';
        $data['export_route']     = 'reports/location_stock';
        $data['export_params']    = $ep;
        $data['export_title']     = $title;
        $this->render('reports/location_stock', $data);
    }

    // â”€â”€ Stock Movement Report â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function stock_movement()
    {
        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);
        $view   = $this->input->get('view', TRUE) ?: 'details';
        $filters = [
            'location_id'    => $this->_get_loc_id() ?: null,
            'movement_type'  => $this->input->get('movement_type'),
            'date_from'      => $this->input->get('date_from') ?: date('Y-m-01'),
            'date_to'        => $this->input->get('date_to')   ?: date('Y-m-d'),
            'product_id'     => $this->input->get('product_id'),
            'item_code'      => $this->input->get('item_code', TRUE),
            'ean_code'       => $this->input->get('ean_code', TRUE),
        ];

        $title = 'Stock Movement Report';
        $ep = http_build_query(array_merge(['view' => $view], array_filter($filters)));

        if ($view === 'summary') {
            $summary = $this->Location_model->get_movement_summary($filters);

            if ($export) {
                $headers = ['Item Code', 'Product Name', 'Unit', 'MRP', 'Opening', 'Stock In', 'Stock Out', 'Closing'];
                $rows = [];
                foreach ($summary as $s) {
                    $rows[] = [
                        $s->item_code,
                        $s->product_name,
                        $s->unit ?? 'PCS',
                        number_format($s->mrp, 2),
                        number_format($s->opening_stock, 3),
                        number_format($s->stock_in, 3),
                        number_format($s->stock_out, 3),
                        number_format($s->closing_stock, 3),
                    ];
                }
                if ($export === 'excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title.' (Summary)', $headers, $rows); return; }
                if ($export === 'pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title.' (Summary)', $headers, $rows); return; }
            }

            $data['summary'] = $summary;
        } else {
            $movements = $this->Location_model->get_movements($filters);

            if ($export) {
                $headers = ['Date', 'Product', 'Location', 'Type', 'Qty', 'Before', 'After', 'Reference', 'User'];
                $rows = [];
                foreach ($movements as $m) {
                    $rows[] = [
                        date('d M Y H:i', strtotime($m->created_at)),
                        $m->product_name ?? '',
                        $m->location_name ?? '',
                        ucwords(str_replace('_', ' ', $m->movement_type ?? '')),
                        number_format($m->quantity ?? 0, 3),
                        number_format($m->balance_before ?? 0, 3),
                        number_format($m->balance_after ?? 0, 3),
                        $m->reference_no ?? '-',
                        $m->user_name ?? ''
                    ];
                }
                if ($export === 'excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title.' (Details)', $headers, $rows); return; }
                if ($export === 'pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title.' (Details)', $headers, $rows); return; }
            }

            $data['movements'] = $movements;
        }

        $data['view']       = $view;
        $data['locations']  = $this->_locations_data()['locations'];
        $data['products']   = $this->db->select('id, name, item_code')->where('status', 1)->order_by('name', 'ASC')->get('products')->result();
        $data['filters']    = $filters;
        $data['page_title'] = $title;
        $data['export_route'] = 'reports/stock_movement';
        $data['export_params']= $ep;
        $data['export_title'] = $title;
        $this->render('reports/stock_movement', $data);
    }

    // ── Daily Sales Requisition Report ─────────────────────────────────
    public function sales_requisition_report()
    {
        $this->load->model('Stock_transfer_model');

        $date_from   = $this->input->get('date_from')   ?: date('Y-m-01');
        $date_to     = $this->input->get('date_to')     ?: date('Y-m-d');
        $location_id = (int)$this->input->get('location_id');
        $status      = $this->input->get('status');

        $this->db->select('
            st.id, st.transfer_no, st.status, st.created_at,
            st.from_location_id, st.to_location_id,
            fl.name AS from_location_name, tl.name AS to_location_name,
            sti.product_id, sti.product_name, sti.unit,
            sti.prev_day_sales_qty, sti.suggested_qty, sti.requested_qty,
            sti.approved_qty, sti.suggestion_source,
            p.item_code
        ');
        $this->db->from('stock_transfer_items sti');
        $this->db->join('stock_transfers st', 'st.id = sti.transfer_id');
        $this->db->join('locations fl', 'fl.id = st.from_location_id', 'left');
        $this->db->join('locations tl', 'tl.id = st.to_location_id', 'left');
        $this->db->join('products p', 'p.id = sti.product_id', 'left');
        $this->db->where('sti.prev_day_sales_qty IS NOT NULL');
        $this->db->where('DATE(st.created_at) >=', $date_from);
        $this->db->where('DATE(st.created_at) <=', $date_to);

        if ($location_id) {
            $this->db->group_start();
            $this->db->where('st.from_location_id', $location_id);
            $this->db->or_where('st.to_location_id', $location_id);
            $this->db->group_end();
        }
        if ($status) {
            $this->db->where('st.status', $status);
        }
        $this->db->order_by('st.created_at', 'DESC');
        $this->db->order_by('sti.id', 'ASC');

        $data['rows']      = $this->db->get()->result();
        $data['locations']  = $this->Location_model->get_all(true);
        $data['filters']    = [
            'date_from'   => $date_from,
            'date_to'     => $date_to,
            'location_id' => $location_id,
            'status'      => $status,
        ];
        $data['page_title'] = 'Daily Sales Requisition Report';
        $this->render('reports/sales_requisition', $data);
    }

    // â”€â”€ Transfer Report â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // ── Negative Stock (Audit) ───────────────────────────────────────────
    // Operational screens show stock as MAX(balance, 0). This lists the REAL
    // per-location balances that went below zero through negative-stock billing,
    // with the last movement, so Accounts/Admin can follow them up.
    public function negative_stock_audit()
    {
        if (!$this->is_owner() && !in_array($this->session->userdata('role'), ['admin', 'superadmin'], true)) {
            $this->session->set_flashdata('error', 'Only admin / owner can view the negative stock audit.');
            redirect('reports');
        }
        $loc = (int)$this->session->userdata('location_id') ?: (int)$this->input->get('location_id');
        $this->db->select('p.id, p.item_code, p.name, p.unit, COALESCE(cat.name, p.category) AS category_name,
                           l.id AS location_id, l.name AS location_name, pls.stock_qty AS actual_qty,
                           p.purchase_price', FALSE)
                 ->from('product_location_stock pls')
                 ->join('products p', 'p.id = pls.product_id')
                 ->join('locations l', 'l.id = pls.location_id', 'left')
                 ->join('categories cat', 'cat.id = p.category_id', 'left')
                 ->where('pls.stock_qty <', 0);
        if ($loc) $this->db->where('pls.location_id', $loc);
        $rows = $this->db->order_by('pls.stock_qty', 'ASC')->get()->result();

        // Last movement per product+location (one query, no N+1)
        $last = [];
        if ($rows && $this->db->table_exists('stock_movement_log')) {
            $pids = array_values(array_unique(array_map(function ($r) { return (int)$r->id; }, $rows)));
            $lm = $this->db->query("
                SELECT m.product_id, m.location_id, m.movement_type, m.quantity, m.balance_after, m.reference_no, m.created_at
                FROM stock_movement_log m
                JOIN (SELECT product_id, location_id, MAX(id) AS mid FROM stock_movement_log
                      WHERE product_id IN (" . implode(',', $pids) . ") GROUP BY product_id, location_id) x ON x.mid = m.id
            ")->result();
            foreach ($lm as $m) $last[$m->product_id . '_' . $m->location_id] = $m;
        }
        foreach ($rows as $r) $r->last = $last[$r->id . '_' . $r->location_id] ?? null;

        $export = $this->input->get('export', TRUE);
        if ($export === 'excel') {
            $headers = ['Item Code', 'Item', 'Category', 'Location', 'Actual Balance', 'Display Stock', 'Unit', 'Last Movement', 'Last Qty', 'Last Ref', 'Last Date'];
            $out = [];
            foreach ($rows as $r) {
                $out[] = [$r->item_code, $r->name, $r->category_name, $r->location_name, (float)$r->actual_qty, 0, $r->unit,
                          $r->last->movement_type ?? '', $r->last ? (float)$r->last->quantity : '', $r->last->reference_no ?? '', $r->last->created_at ?? ''];
            }
            $this->_export_excel('Negative Stock Audit ' . date('d-m-Y'), $headers, $out);
            return;
        }
        $data['rows']       = $rows;
        $data['page_title'] = 'Negative Stock (Audit)';
        $data = array_merge($data, $this->_locations_data());
        $this->render('reports/negative_stock_audit', $data);
    }

    public function transfers_report()
    {
        $this->load->model('Stock_transfer_model');
        $filters = [
            'status'      => $this->input->get('status'),
            'date_from'   => $this->input->get('date_from') ?: date('Y-m-01'),
            'date_to'     => $this->input->get('date_to')   ?: date('Y-m-d'),
            'tro_no'      => trim((string)$this->input->get('tro_no', TRUE)),
            'tri_no'      => trim((string)$this->input->get('tri_no', TRUE)),
            'item'        => trim((string)$this->input->get('item', TRUE)),
            'from_location_id' => (int)$this->input->get('from_location_id') ?: null,
            'to_location_id'   => (int)$this->input->get('to_location_id') ?: null,
        ];
        // A manager fixed to one outlet only sees transfers touching that outlet
        $fixed_loc = (int)$this->session->userdata('location_id');
        if ($fixed_loc && !$this->is_owner()) $filters['location_id'] = $fixed_loc;
        $data['transfers']  = $this->Stock_transfer_model->get_all($filters);
        $data['locations']  = $this->Location_model->get_all(true);
        $data['filters']    = $filters;
        $data['page_title'] = 'Transfer Report';
        $this->render('reports/transfers_report', $data);
    }

    // â”€â”€ Outlet-wise Sales Report â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function outlet_sales() {
        $data['page_title'] = 'Outlet-wise Sales Report';
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to',   TRUE) ?: date('Y-m-d');
        $loc  = $this->_get_loc_id();
        $data['from'] = $from;
        $data['to']   = $to;
        $data = array_merge($data, $this->_locations_data());

        $where_loc_l = $loc ? "AND l.id = $loc" : '';
        $data['outlet_summary'] = $this->db->query("
            SELECT l.id, l.name AS location_name, l.code, l.type,
                   COUNT(i.id) AS invoice_count,
                   COALESCE(SUM(i.subtotal), 0) AS subtotal,
                   COALESCE(SUM(i.discount), 0) AS total_discount,
                   COALESCE(SUM(i.total_gst), 0) AS total_gst,
                   COALESCE(SUM(i.total_amount), 0) AS total_amount,
                   COALESCE(SUM(i.paid_amount), 0) AS paid_amount,
                   COALESCE(SUM(i.due_amount), 0) AS due_amount
            FROM locations l
            LEFT JOIN invoices i ON i.location_id = l.id
                AND i.invoice_date >= ? AND i.invoice_date <= ?
                AND i.status != 'cancelled'
            WHERE l.status = 1 $where_loc_l
            GROUP BY l.id ORDER BY l.sort_order, l.id
        ", [$from, $to])->result();

        $where_loc_i = $loc ? "AND i.location_id = $loc" : '';
        $data['outlet_top'] = $this->db->query("
            SELECT l.name AS location_name, l.code,
                   ii.product_name, COALESCE(p.item_code,'') AS item_code,
                   SUM(ii.qty) AS total_qty, SUM(ii.total) AS total_val
            FROM invoice_items ii
            JOIN invoices i ON i.id = ii.invoice_id
            JOIN locations l ON l.id = i.location_id
            LEFT JOIN products p ON p.id = ii.product_id
            WHERE i.invoice_date >= ? AND i.invoice_date <= ? AND i.status != 'cancelled' $where_loc_i
            GROUP BY l.id, ii.product_id
            ORDER BY l.id, total_val DESC
        ", [$from, $to])->result();

        $this->render('reports/outlet_sales', $data);
    }

    // â”€â”€ Audit Log Report â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    public function payment_mode_changes() {
        if (!$this->can_view_reports()) {
            $this->session->set_flashdata('error', 'Access denied.');
            redirect('dashboard');
        }

        $data['page_title'] = 'Payment Mode Changes Audit Log';
        $from = $this->input->get('from', TRUE) ?: date('Y-m-d');
        $to   = $this->input->get('to', TRUE)   ?: date('Y-m-d');
        $data['from'] = $from;
        $data['to']   = $to;

        $this->db->select('a.*, u.name as user_name');
        $this->db->from('audit_payment_mode_changes a');
        $this->db->join('users u', 'u.id = a.changed_by', 'left');
        $this->db->where('DATE(a.created_at) >=', $from);
        $this->db->where('DATE(a.created_at) <=', $to);
        $this->db->order_by('a.created_at', 'DESC');
        $data['logs'] = $this->db->get()->result();

        // Support export to Excel
        if ($this->input->get('export') === 'excel') {
            $headers = ['Date/Time', 'Invoice #', 'User', 'Terminal', 'Old Mode', 'New Mode', 'Reason', 'Remarks'];
            $rows = [];
            foreach ($data['logs'] as $l) {
                $rows[] = [
                    date('d/m/Y h:i A', strtotime($l->created_at)),
                    $l->bill_no,
                    $l->user_name,
                    $l->terminal,
                    strtoupper($l->old_mode),
                    strtoupper($l->new_mode),
                    $l->reason,
                    $l->remarks,
                ];
            }
            $this->_export_excel('Payment Mode Changes', $headers, $rows);
            return;
        }

        $this->render('reports/payment_mode_changes', $data);
    }

    // Items scanned into a bill and then deleted (with manager PIN authorization)
    // before the sale was saved — see Billing::verify_manager_pin_ajax() / the
    // billing_item_removals table. Catches the "scan it, hand it over, delete it
    // before checkout" shrinkage pattern that a saved-invoice-only report can't see.
    public function removed_items() {
        $this->_rpt('frm_rpt_audit_log');

        $from    = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to      = $this->input->get('to',   TRUE) ?: date('Y-m-d');
        $user_id = (int)$this->input->get('user_id');
        $loc_id  = $this->_get_loc_id();

        $base = function() use ($from, $to, $loc_id, $user_id) {
            $this->db->where('DATE(removed_at) >=', $from)->where('DATE(removed_at) <=', $to);
            if ($loc_id)  $this->db->where('location_id', $loc_id);
            if ($user_id) $this->db->where('user_id', $user_id);
        };

        $base();
        $data['logs'] = $this->db->order_by('removed_at', 'DESC')->get('billing_item_removals')->result();

        $base();
        $totals = $this->db->select('COUNT(*) as cnt, COALESCE(SUM(line_value),0) as value_sum')
            ->get('billing_item_removals')->row();

        $data['page_title']  = 'Removed Items (Deleted After Scan)';
        $data['from']        = $from;
        $data['to']          = $to;
        $data['user_id']     = $user_id;
        $data['total_count'] = $totals ? (int)$totals->cnt : 0;
        $data['total_value'] = $totals ? (float)$totals->value_sum : 0;
        $data['users']       = $this->db->select('id, name')->order_by('name')->get('users')->result();
        $data                = array_merge($data, $this->_locations_data());

        if ($this->input->get('export') === 'excel') {
            $headers = ['Date/Time', 'Item', 'Item Code', 'Qty', 'Rate', 'Value', 'Removed By', 'Authorized By', 'Customer', 'Terminal'];
            $rows = [];
            foreach ($data['logs'] as $l) {
                $rows[] = [
                    date('d/m/Y h:i A', strtotime($l->removed_at)),
                    $l->product_name,
                    $l->item_code,
                    $l->qty,
                    $l->rate,
                    $l->line_value,
                    $l->user_name,
                    $l->authorized_by,
                    $l->customer_name,
                    $l->device_label,
                ];
            }
            $this->_export_excel('Removed Items', $headers, $rows);
            return;
        }

        $this->render('reports/removed_items', $data);
    }

    public function audit_log() {
        $this->_rpt('frm_rpt_audit_log');
        $from        = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to          = $this->input->get('to',   TRUE) ?: date('Y-m-d');
        $user_id     = (int)$this->input->get('user_id');
        $module      = $this->input->get('module', TRUE) ?: '';
        $action_f    = $this->input->get('action_f', TRUE) ?: '';
        $search      = $this->input->get('search', TRUE) ?: '';
        $page        = max(1, (int)$this->input->get('page'));
        $per_page    = 200;
        $loc_id      = $this->_get_loc_id();

        // Build base query helper
        $base = function() use ($from,$to,$loc_id,$user_id,$module,$action_f,$search) {
            $this->db->where('DATE(created_at) >=', $from)->where('DATE(created_at) <=', $to);
            if ($loc_id)   $this->db->where('location_id', $loc_id);
            if ($user_id)  $this->db->where('user_id', $user_id);
            if ($module)   $this->db->where('module', $module);
            if ($action_f) $this->db->where('action', $action_f);
            if ($search)   $this->db->group_start()->like('description',$search)->or_like('record_no',$search)->or_like('user_name',$search)->group_end();
        };

        $base(); $total_count = $this->db->count_all_results('audit_logs');
        $base(); $data['logs'] = $this->db->order_by('id','DESC')->limit($per_page, ($page-1)*$per_page)->get('audit_logs')->result();

        // Summary stats for the period
        $base();
        $stats = $this->db->select('module, action, COUNT(*) as cnt')
                          ->group_by('module, action')->order_by('cnt','DESC')
                          ->get('audit_logs')->result();

        // Activity by user
        $base();
        $by_user = $this->db->select('user_name, COUNT(*) as cnt')
                            ->group_by('user_id')->order_by('cnt','DESC')->limit(10)
                            ->get('audit_logs')->result();

        // Sensitive actions (deletes, cancels, price changes, password changes)
        $this->db->where('DATE(created_at) >=', $from)->where('DATE(created_at) <=', $to)
                 ->group_start()
                    ->like('action','delete')->or_like('action','cancel')->or_like('action','price_change')
                    ->or_like('action','wallet_adjust')->or_like('action','toggle_status')->or_like('description','Password changed')
                 ->group_end();
        $data['sensitive_logs'] = $this->db->order_by('id','DESC')->limit(50)->get('audit_logs')->result();

        $data['page_title']   = 'Audit Log';
        $data['from']         = $from;
        $data['to']           = $to;
        $data['user_id']      = $user_id;
        $data['sel_module']   = $module;
        $data['sel_action']   = $action_f;
        $data['search']       = $search;
        $data['total_count']  = $total_count;
        $data['page']         = $page;
        $data['per_page']     = $per_page;
        $data['total_pages']  = ceil($total_count / $per_page);
        $data['stats']        = $stats;
        $data['by_user']      = $by_user;
        $data['users']        = $this->db->select('id, name')->order_by('name')->get('users')->result();
        $data['modules']      = $this->db->query("SELECT DISTINCT module FROM audit_logs WHERE module != '' ORDER BY module")->result();
        $data['actions']      = $this->db->query("SELECT DISTINCT action FROM audit_logs WHERE action != '' ORDER BY action")->result();
        $data               = array_merge($data, $this->_locations_data());

        if ($this->input->get('export') === 'csv') {
            // For CSV export all matching rows without pagination
            $base();
            $all_logs = $this->db->order_by('id','DESC')->get('audit_logs')->result();
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="audit_log_' . date('Ymd') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['#','Timestamp','User','Location','Module','Action','Reference','Description','IP']);
            foreach ($all_logs as $i => $log) {
                fputcsv($out, [$i+1, $log->created_at, $log->user_name ?? '', $log->location_name ?? '', $log->module, $log->action, $log->record_no ?? '', $log->description, $log->ip_address]);
            }
            fclose($out);
            exit;
        }

        $this->render('reports/audit_log', $data);
    }

    // â”€â”€ Private helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    private function _get_loc_id() {
        // Outlet/warehouse staff have a fixed location (session 'location_id') and
        // must never be able to view another outlet's reports by tampering with the
        // ?location_id= query param — so their fixed location always wins over it.
        $fixed = $this->session->userdata('location_id');
        if ($fixed) return (int)$fixed;
        $loc = (int)$this->input->get('location_id');
        return $loc ?: ($this->active_location_id() ?: 0);
    }

    private function _locations_data() {
        $locations = $this->Location_model->get_all(true);
        $fixed = $this->session->userdata('location_id');
        if ($fixed) {
            $locations = array_values(array_filter($locations, function($l) use ($fixed) {
                return (int)$l->id === (int)$fixed;
            }));
        }
        return [
            'locations'   => $locations,
            'location_id' => $this->_get_loc_id(),
        ];
    }

    // ── Inventory Analytics ──────────────────────────────────
    public function inventory_analytics() {
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to   = $this->input->get('to')   ?: date('Y-m-d');
        $days = (int)($this->input->get('dead_days') ?: 90);
        $loc  = $this->_get_loc_id();
        $this->_rpt('frm_rpt_inventory');
        $data['page_title']   = 'Inventory Analytics';
        $data['from']         = $from;
        $data['to']           = $to;
        $data['dead_days']    = $days;
        $data['fast_moving']  = $this->Report_model->get_fast_moving($from, $to, 30, $loc);
        $data['slow_moving']  = $this->Report_model->get_slow_moving($from, $to, 30, $loc);
        $data['dead_stock']   = $this->Report_model->get_dead_stock($days, $loc);
        $data['abc_analysis'] = $this->Report_model->get_abc_analysis($from, $to);
        $data['turnover']     = $this->Report_model->get_inventory_turnover($from, $to);
        $this->render('reports/inventory_analytics', $data);
    }

    // ── Fast & Slow Movement Report ───────────────────────────────────────────
    public function fast_slow_report() {
        $this->_rpt('frm_rpt_inventory');
        $from        = $this->input->get('from')        ?: date('Y-m-01');
        $to          = $this->input->get('to')          ?: date('Y-m-d');
        $category_id = (int)$this->input->get('category_id');
        $brand_id    = (int)$this->input->get('brand_id');
        $fast_thresh = (int)($this->input->get('fast_thresh') ?: 50);
        $slow_thresh = (int)($this->input->get('slow_thresh') ?: 10);
        $loc         = $this->_get_loc_id();

        $filters = [
            'location_id' => $loc,
            'category_id' => $category_id,
            'brand_id'    => $brand_id,
            'fast_thresh' => $fast_thresh,
            'slow_thresh' => $slow_thresh,
        ];

        $report = $this->Report_model->get_movement_report($from, $to, $filters);

        $data['page_title']   = 'Fast & Slow Movement Report';
        $data['from']         = $from;
        $data['to']           = $to;
        $data['fast_thresh']  = $fast_thresh;
        $data['slow_thresh']  = $slow_thresh;
        $data['category_id']  = $category_id;
        $data['brand_id']     = $brand_id;
        $data['fast']         = $report['fast'];
        $data['slow']         = $report['slow'];
        $data['no_movement']  = $report['no_movement'];
        $data['categories']   = $this->db->where('status', 1)->order_by('name')->get('categories')->result();
        $data['brands']       = $this->db->where('status', 1)->order_by('name')->get('brands')->result();
        $data = array_merge($data, $this->_locations_data());

        $ep='from='.$from.'&to='.$to.'&fast_thresh='.$fast_thresh.'&slow_thresh='.$slow_thresh;
        $export_action=$this->input->get('export')?:$this->input->post('export',TRUE);
        $data['export_route']='reports/fast_slow_report'; $data['export_params']=$ep; $data['export_title']='Fast & Slow Movement '.$from.' to '.$to;
        if ($export_action==='csv'||$export_action==='excel') { $this->_export_movement_csv($data); return; }
        if ($export_action==='pdf'&&$this->_can('frm_rpt_export')) {
            $headers=['#','Product','Item Code','Category','Brand','Qty Sold','Revenue','Stock','Last Sale']; $rows=[];
            foreach ($report['fast'] as $i=>$r) $rows[]=[$i+1,$r->product_name,$r->item_code,$r->category_name,$r->brand_name??'',$r->total_sold,$r->total_revenue,$r->current_stock,$r->last_sale_date];
            $rows[]=['--- SLOW MOVING ---'];
            foreach ($report['slow'] as $i=>$r) $rows[]=[$i+1,$r->product_name,$r->item_code,$r->category_name,$r->brand_name??'',$r->total_sold,$r->stock_value,$r->current_stock,$r->last_sale_date?:'Never'];
            $rows[]=['--- NO MOVEMENT ---'];
            foreach ($report['no_movement'] as $i=>$r) $rows[]=[$i+1,$r->product_name,$r->item_code,$r->category_name,$r->brand_name??'','0','—',$r->current_stock,$r->last_sale_date?:'Never'];
            $this->_export_pdf('Fast & Slow Movement '.$from.' to '.$to,$headers,$rows); return;
        }
        if ($export_action==='email') {
            $headers=['#','Type','Product','Item Code','Category','Qty Sold','Revenue/StockVal','Stock','Last Sale']; $rows=[];
            foreach ($report['fast'] as $i=>$r) $rows[]=[$i+1,'FAST',$r->product_name,$r->item_code,$r->category_name,$r->total_sold,$r->total_revenue,$r->current_stock,$r->last_sale_date];
            foreach ($report['slow'] as $i=>$r) $rows[]=[$i+1,'SLOW',$r->product_name,$r->item_code,$r->category_name,$r->total_sold,$r->stock_value,$r->current_stock,$r->last_sale_date?:'Never'];
            foreach ($report['no_movement'] as $i=>$r) $rows[]=[$i+1,'NO MOVE',$r->product_name,$r->item_code,$r->category_name,'0','—',$r->current_stock,$r->last_sale_date?:'Never'];
            $this->_send_report_email($this->input->post('email_to',TRUE),'Fast & Slow Movement '.$from.' to '.$to,$headers,$rows,base_url('reports/fast_slow_report?'.$ep)); return;
        }

        $this->render('reports/fast_slow_report', $data);
    }

    private function _export_movement_csv($data) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="fast_slow_movement_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');

        fputcsv($out, ['Fast & Slow Movement Report', $data['from'] . ' to ' . $data['to']]);
        fputcsv($out, []);

        fputcsv($out, ['=== FAST MOVING (>= ' . $data['fast_thresh'] . ' units) ===']);
        fputcsv($out, ['#', 'Product', 'Item Code', 'Category', 'Brand', 'Qty Sold', 'Revenue', 'Transactions', 'Current Stock', 'Last Sale']);
        foreach ($data['fast'] as $i => $r) {
            fputcsv($out, [$i+1, $r->product_name, $r->item_code, $r->category_name, $r->brand_name, $r->total_sold, number_format($r->total_revenue, 2), $r->txn_count, $r->current_stock, $r->last_sale_date]);
        }
        fputcsv($out, []);

        fputcsv($out, ['=== SLOW MOVING (< ' . $data['slow_thresh'] . ' units) ===']);
        fputcsv($out, ['#', 'Product', 'Item Code', 'Category', 'Brand', 'Qty Sold', 'Stock Value', 'Current Stock', 'Last Sale', 'Days Since Sale']);
        foreach ($data['slow'] as $i => $r) {
            fputcsv($out, [$i+1, $r->product_name, $r->item_code, $r->category_name, $r->brand_name, $r->total_sold, number_format($r->stock_value, 2), $r->current_stock, $r->last_sale_date ?: 'Never', $r->days_since_last_sale]);
        }
        fputcsv($out, []);

        fputcsv($out, ['=== NO MOVEMENT (0 units sold) ===']);
        fputcsv($out, ['#', 'Product', 'Item Code', 'Category', 'Brand', 'Current Stock', 'Stock Value', 'Last Sale']);
        foreach ($data['no_movement'] as $i => $r) {
            fputcsv($out, [$i+1, $r->product_name, $r->item_code, $r->category_name, $r->brand_name, $r->current_stock, number_format($r->stock_value, 2), $r->last_sale_date ?: 'Never sold']);
        }
        fclose($out);
        exit;
    }

    // ============================================================
    // #1 Store-wise Media Report
    // ============================================================
    public function media_report() {
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to   = $this->input->get('to')   ?: date('Y-m-d');
        $data['page_title'] = 'Store-wise Media Report';
        $data['from'] = $from; $data['to'] = $to;
        $data = array_merge($data, $this->_locations_data());
        $loc  = $this->_get_loc_id();

        $where_loc = $loc ? "AND i.location_id = $loc" : '';

        // Non-split invoices grouped by their payment_mode + location
        $non_split = $this->db->query("
            SELECT i.payment_mode, l.name as location_name, i.location_id,
                   COUNT(i.id) as bill_count, SUM(i.paid_amount) as total_amount
            FROM invoices i
            LEFT JOIN locations l ON l.id = i.location_id
            WHERE DATE(i.invoice_date) BETWEEN ? AND ?
              AND i.status != 'cancelled' AND i.payment_mode != 'split' $where_loc
            GROUP BY i.payment_mode, i.location_id
            ORDER BY l.name, i.payment_mode
        ", [$from, $to])->result();

        // Split invoices: distribute their component amounts into the actual modes
        $split_rows = $this->db->query("
            SELECT ips.payment_mode, l.name as location_name, i.location_id,
                   COUNT(DISTINCT i.id) as bill_count, SUM(ips.amount) as total_amount
            FROM invoice_payment_splits ips
            JOIN invoices i ON i.id = ips.invoice_id
            LEFT JOIN locations l ON l.id = i.location_id
            WHERE DATE(i.invoice_date) BETWEEN ? AND ?
              AND i.status != 'cancelled' AND i.payment_mode = 'split' $where_loc
            GROUP BY ips.payment_mode, i.location_id
            ORDER BY l.name, ips.payment_mode
        ", [$from, $to])->result();

        // Merge non-split + split component rows by (location, mode)
        $summary_map = [];
        foreach ($non_split as $r) {
            $key = ($r->location_id ?: 0) . '|' . $r->payment_mode;
            $summary_map[$key] = $r;
        }
        foreach ($split_rows as $r) {
            $key = ($r->location_id ?: 0) . '|' . $r->payment_mode;
            if (isset($summary_map[$key])) {
                $summary_map[$key]->bill_count    += $r->bill_count;
                $summary_map[$key]->total_amount  += $r->total_amount;
            } else {
                $summary_map[$key] = $r;
            }
        }
        $data['media_summary'] = array_values($summary_map);

        // Grand totals — same merge logic
        $non_split_totals = $this->db->query("
            SELECT i.payment_mode, COUNT(i.id) as bill_count, SUM(i.paid_amount) as total_amount
            FROM invoices i
            WHERE DATE(i.invoice_date) BETWEEN ? AND ? AND i.status != 'cancelled'
              AND i.payment_mode != 'split' $where_loc
            GROUP BY i.payment_mode
        ", [$from, $to])->result();
        $split_totals = $this->db->query("
            SELECT ips.payment_mode, COUNT(DISTINCT i.id) as bill_count, SUM(ips.amount) as total_amount
            FROM invoice_payment_splits ips
            JOIN invoices i ON i.id = ips.invoice_id
            WHERE DATE(i.invoice_date) BETWEEN ? AND ? AND i.status != 'cancelled'
              AND i.payment_mode = 'split' $where_loc
            GROUP BY ips.payment_mode
        ", [$from, $to])->result();
        $totals_map = [];
        foreach ($non_split_totals as $t) { $totals_map[$t->payment_mode] = $t; }
        foreach ($split_totals as $t) {
            if (isset($totals_map[$t->payment_mode])) {
                $totals_map[$t->payment_mode]->bill_count   += $t->bill_count;
                $totals_map[$t->payment_mode]->total_amount += $t->total_amount;
            } else {
                $totals_map[$t->payment_mode] = $t;
            }
        }
        $data['totals'] = array_values($totals_map);
        
        $export = $this->input->get('export');
        if ($export === 'excel') {
            $modes = ['cash'=>'Cash','upi'=>'UPI','card'=>'Card','neft'=>'NEFT','cheque'=>'Cheque','wallet'=>'Wallet','credit'=>'Credit'];
            $headers = ['Location', 'Payment Mode', 'Bills', 'Amount (Rs)'];
            $rows = [];
            foreach ($data['media_summary'] as $r) {
                $rows[] = [$r->location_name ?: 'Main Store', $modes[$r->payment_mode] ?? ucfirst($r->payment_mode), $r->bill_count, $r->total_amount];
            }
            $rows[] = ['GRAND TOTALS', '', '', ''];
            foreach ($data['totals'] as $t) {
                $rows[] = ['', $modes[$t->payment_mode] ?? ucfirst($t->payment_mode), $t->bill_count, $t->total_amount];
            }
            $this->_export_excel('Store-wise Media Report (' . $from . ' to ' . $to . ')', $headers, $rows);
            return;
        } elseif ($export === 'pdf') {
            $this->load->library('pdf_generator');
            $html = $this->load->view('reports/media_report_pdf', $data, TRUE);
            $this->pdf_generator->generate($html, 'Store_Media_Report_' . date('Ymd_His'), 'download', 'A4');
            exit;
        }

        $this->render('reports/media_report', $data);
    }

    public function email_media_report() {
        if (!$this->can_view_reports()) {
            $this->session->set_flashdata('error', 'Access denied.');
            redirect('dashboard');
        }
        
        $from = $this->input->post('from') ?: date('Y-m-01');
        $to   = $this->input->post('to')   ?: date('Y-m-d');
        $to_email  = $this->input->post('to_email', TRUE);
        $cc_email  = $this->input->post('cc_email', TRUE);
        $subject   = $this->input->post('email_subject', TRUE) ?: 'Store-wise Media Report';
        $message   = $this->input->post('email_message', FALSE) ?: '';

        if (!$to_email || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Invalid email address.');
            redirect('reports/media_report?from=' . $from . '&to=' . $to);
        }

        $loc  = $this->_get_loc_id() ?: (int)$this->input->post('location_id');
        $where_loc = $loc ? "AND i.location_id = $loc" : '';

        $data['from'] = $from;
        $data['to']   = $to;
        $data['media_summary'] = $this->db->query("
            SELECT i.payment_mode, l.name as location_name, COUNT(i.id) as bill_count, SUM(i.paid_amount) as total_amount
            FROM invoices i LEFT JOIN locations l ON l.id = i.location_id
            WHERE DATE(i.invoice_date) BETWEEN ? AND ? AND i.status != 'cancelled' $where_loc
            GROUP BY i.payment_mode, i.location_id ORDER BY l.name, i.payment_mode
        ", [$from, $to])->result();
        
        $data['totals'] = $this->db->query("
            SELECT i.payment_mode, COUNT(i.id) as bill_count, SUM(i.paid_amount) as total_amount
            FROM invoices i WHERE DATE(i.invoice_date) BETWEEN ? AND ? AND i.status != 'cancelled' $where_loc
            GROUP BY i.payment_mode
        ", [$from, $to])->result();
        
        $this->load->model('Invoice_model');
        $data['company'] = $this->Invoice_model->get_company_settings();
        
        // Generate PDF
        $this->load->library('pdf_generator');
        $html = $this->load->view('reports/media_report_pdf', $data, TRUE);
        $pdf_string = $this->pdf_generator->as_string($html, 'Store_Media_Report_' . date('Ymd_His'));
        $pdf_filename = 'Store_Media_Report_' . date('Ymd_His') . '.pdf';
        
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
        
        redirect('reports/media_report?from=' . $from . '&to=' . $to . '&location_id=' . $loc);
    }

    // ============================================================
    // #2 Counter-wise Media Report
    // ============================================================
    public function counter_media_report() {
        $from = $this->input->get('from') ?: date('Y-m-01');
        $to   = $this->input->get('to')   ?: date('Y-m-d');
        $loc  = $this->_get_loc_id();
        $data['page_title'] = 'Counter-wise Media Report';
        $data['from'] = $from; $data['to'] = $to;
        if ($loc) {
            $data['cashiers'] = $this->db->select('id, name')->where('status', 1)
                ->group_start()->where('location_id', $loc)->or_where('location_id IS NULL')->group_end()
                ->get('users')->result();
        } else {
            $data['cashiers'] = $this->db->select('id, name')->where('status', 1)->get('users')->result();
        }
        $cashier_id = (int)$this->input->get('cashier_id');
        $data['cashier_id'] = $cashier_id;
        $mode_filter = strtolower($this->input->get('payment_mode', TRUE) ?: '');
        $data['mode_filter'] = $mode_filter;

        $where_cashier = $cashier_id ? "AND cs.cashier_id = $cashier_id" : '';
        $where_loc     = $loc ? "AND i.location_id = $loc" : '';
        $invoices = $this->db->query("
            SELECT i.id, i.payment_mode, i.paid_amount,
                   u.name AS cashier_name,
                   COALESCE(NULLIF(i.device_label,''), NULLIF(cs.system_name,''), cs.terminal_no, 'Counter') AS counter_name
            FROM invoices i
            LEFT JOIN cashier_shifts cs ON cs.id = i.shift_id
            LEFT JOIN users u ON u.id = cs.cashier_id
            WHERE DATE(i.invoice_date) BETWEEN ? AND ?
              AND i.status != 'cancelled' $where_cashier $where_loc
        ", [$from, $to])->result();

        // Split is not a real payment mode — pull the component lines for every
        // split invoice so its amount lands in the actual cash/upi/card/etc
        // buckets instead of its own "Split" column (matches Shifts::shift_close()).
        $split_ids = array_column(array_filter($invoices, function($i) { return $i->payment_mode === 'split'; }), 'id');
        $splits_by_invoice = [];
        if (!empty($split_ids)) {
            $splits = $this->db->where_in('invoice_id', $split_ids)->get('invoice_payment_splits')->result();
            foreach ($splits as $sp) {
                $splits_by_invoice[$sp->invoice_id][] = $sp;
            }
        }

        $counter_media  = [];   // per counter+cashier+mode breakdown (decomposed)
        $counter_totals = [];   // per counter+cashier bill/amount totals — reflects only
                                 // whatever was actually counted below (all modes, or just
                                 // the filtered mode when $mode_filter is set)
        foreach ($invoices as $inv) {
            $counter = $inv->counter_name;
            $cashier = $inv->cashier_name ?: '—';

            if ($inv->payment_mode === 'split' && !empty($splits_by_invoice[$inv->id])) {
                foreach ($splits_by_invoice[$inv->id] as $sp) {
                    if ($mode_filter && strtolower($sp->payment_mode) !== $mode_filter) continue;

                    $tkey = $counter . '|' . $cashier;
                    if (!isset($counter_totals[$tkey])) {
                        $counter_totals[$tkey] = (object)['counter_name'=>$counter, 'cashier_name'=>$cashier, 'bill_count'=>0, 'total_amount'=>0];
                    }
                    $counter_totals[$tkey]->bill_count++;
                    $counter_totals[$tkey]->total_amount += (float)$sp->amount;

                    $mkey = $counter . '|' . $cashier . '|' . $sp->payment_mode;
                    if (!isset($counter_media[$mkey])) {
                        $counter_media[$mkey] = (object)['counter_name'=>$counter, 'cashier_name'=>$cashier, 'payment_mode'=>$sp->payment_mode, 'bill_count'=>0, 'total_amount'=>0];
                    }
                    $counter_media[$mkey]->bill_count++;
                    $counter_media[$mkey]->total_amount += (float)$sp->amount;
                }
            } else {
                $mode = $inv->payment_mode ?: 'other';
                if ($mode_filter && strtolower($mode) !== $mode_filter) continue;

                $tkey = $counter . '|' . $cashier;
                if (!isset($counter_totals[$tkey])) {
                    $counter_totals[$tkey] = (object)['counter_name'=>$counter, 'cashier_name'=>$cashier, 'bill_count'=>0, 'total_amount'=>0];
                }
                $counter_totals[$tkey]->bill_count++;
                $counter_totals[$tkey]->total_amount += (float)$inv->paid_amount;

                $mkey = $counter . '|' . $cashier . '|' . $mode;
                if (!isset($counter_media[$mkey])) {
                    $counter_media[$mkey] = (object)['counter_name'=>$counter, 'cashier_name'=>$cashier, 'payment_mode'=>$mode, 'bill_count'=>0, 'total_amount'=>0];
                }
                $counter_media[$mkey]->bill_count++;
                $counter_media[$mkey]->total_amount += (float)$inv->paid_amount;
            }
        }

        $data['counter_media']  = array_values($counter_media);
        $data['counter_totals'] = array_values($counter_totals);

        // Flatten the same pivot the view renders into a plain table, so
        // Excel/PDF/Email exports show exactly what's on screen.
        $pivot = []; $modes_found = []; $pivot_cashiers = [];
        foreach ($data['counter_media'] as $row) {
            $counter = $row->counter_name ?: 'Counter';
            $cashier = $row->cashier_name ?: '—';
            $mode    = strtolower($row->payment_mode ?: 'other');
            if (!isset($pivot[$counter])) { $pivot[$counter] = []; $pivot_cashiers[$counter] = []; }
            if (!isset($pivot[$counter][$mode])) { $pivot[$counter][$mode] = ['bills' => 0, 'amount' => 0]; }
            $pivot[$counter][$mode]['bills']  += $row->bill_count;
            $pivot[$counter][$mode]['amount'] += $row->total_amount;
            $pivot_cashiers[$counter][$cashier] = true;
            $modes_found[$mode] = true;
        }
        $row_totals = [];
        foreach ($data['counter_totals'] as $row) {
            $counter = $row->counter_name ?: 'Counter';
            if (!isset($row_totals[$counter])) { $row_totals[$counter] = ['bills' => 0, 'amount' => 0]; }
            $row_totals[$counter]['bills']  += $row->bill_count;
            $row_totals[$counter]['amount'] += $row->total_amount;
        }
        $mode_order = array_values(array_filter(
            ['cash', 'upi', 'card', 'neft', 'cheque', 'wallet', 'credit', 'other'],
            function($m) use ($modes_found) { return isset($modes_found[$m]); }
        ));
        foreach (array_keys($modes_found) as $m) {
            if (!in_array($m, $mode_order)) $mode_order[] = $m;
        }

        $headers = ['Counter / Cashier'];
        foreach ($mode_order as $m) { $headers[] = ucfirst($m) . ' Bills'; $headers[] = ucfirst($m) . ' Amount'; }
        $headers[] = 'Total Bills'; $headers[] = 'Total Amount';

        $rows = [];
        $col_grand = [];
        foreach ($mode_order as $m) $col_grand[$m] = ['bills' => 0, 'amount' => 0];
        $grand = ['bills' => 0, 'amount' => 0];
        foreach ($pivot as $counterName => $modeData) {
            $cashier_list = implode(', ', array_keys($pivot_cashiers[$counterName]));
            $out_row = [$counterName . ' (' . $cashier_list . ')'];
            foreach ($mode_order as $m) {
                $cell = $modeData[$m] ?? ['bills' => 0, 'amount' => 0];
                $out_row[] = $cell['bills'];
                $out_row[] = number_format($cell['amount'], 2);
                $col_grand[$m]['bills']  += $cell['bills'];
                $col_grand[$m]['amount'] += $cell['amount'];
            }
            $tb = $row_totals[$counterName]['bills']  ?? 0;
            $ta = $row_totals[$counterName]['amount'] ?? 0;
            $out_row[] = $tb; $out_row[] = number_format($ta, 2);
            $grand['bills'] += $tb; $grand['amount'] += $ta;
            $rows[] = $out_row;
        }
        $total_row = ['TOTAL'];
        foreach ($mode_order as $m) { $total_row[] = $col_grand[$m]['bills']; $total_row[] = number_format($col_grand[$m]['amount'], 2); }
        $total_row[] = $grand['bills']; $total_row[] = number_format($grand['amount'], 2);
        $rows[] = $total_row;

        $title = 'Counter-wise Media Report';
        $ep = http_build_query(['from' => $from, 'to' => $to, 'cashier_id' => $cashier_id, 'payment_mode' => $mode_filter]);
        // company_settings is a key_name/key_value table — a plain row_array() would
        // grab row #1 (whichever setting happens to be first), not the actual
        // company_name/logo/address, which is why exports looked hardcoded/blank.
        $data['company']       = $this->Report_model->get_company_settings();
        $data['export_route']  = 'reports/counter_media_report';
        $data['export_params'] = $ep;
        $data['export_title']  = $title;

        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);
        if ($export === 'excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title, $headers, $rows); return; }
        if ($export === 'pdf' && $this->_can('frm_rpt_export')) {
            $autoload = APPPATH . '../vendor/autoload.php';
            if (file_exists($autoload)) {
                $this->load->library('pdf_generator');
                $html = $this->load->view('reports/report_viewer_pdf', [
                    'report_title' => $title, 'headers' => $headers, 'rows' => $rows,
                    'company' => $data['company'], 'from' => $from, 'to' => $to,
                ], TRUE);
                $this->pdf_generator->generate($html, str_replace(' ', '_', $title) . '_' . date('Ymd'), 'download', 'A4');
                return;
            }
        }
        if ($export === 'email') {
            $this->_send_report_email($this->input->post('email_to', TRUE), $title, $headers, $rows, base_url('reports/counter_media_report?' . $ep));
            return;
        }

        $this->render('reports/counter_media_report', $data);
    }

    // ============================================================
    // #3 Day End Metrics Dashboard
    // ============================================================
    public function day_end_metrics() {
        $date = $this->input->get('date') ?: date('Y-m-d');
        $data['page_title'] = 'Day End Metrics: ' . date('d-M-Y', strtotime($date));
        $data['date'] = $date;

        $loc = $this->_get_loc_id();

        $this->db->where('DATE(invoice_date)', $date)->where('status !=', 'cancelled');
        if ($loc) $this->db->where('location_id', $loc);
        $invoices = $this->db->get('invoices')->result();

        $total_sales = array_sum(array_column($invoices, 'total_amount'));
        $bill_count  = count($invoices);
        $customer_ids = array_unique(array_column($invoices, 'customer_id'));
        $payment_modes = [];
        $split_invoice_ids = [];
        foreach ($invoices as $inv) {
            $mode = $inv->payment_mode ?? 'cash';
            if ($mode === 'split') {
                $split_invoice_ids[] = $inv->id;
                continue;
            }
            if (!isset($payment_modes[$mode])) $payment_modes[$mode] = ['count'=>0,'amount'=>0];
            $payment_modes[$mode]['count']++;
            $payment_modes[$mode]['amount'] += $inv->paid_amount;
        }
        if (!empty($split_invoice_ids)) {
            $splits = $this->db->where_in('invoice_id', $split_invoice_ids)
                               ->get('invoice_payment_splits')->result();
            foreach ($splits as $sp) {
                $m = $sp->payment_mode;
                if (!isset($payment_modes[$m])) $payment_modes[$m] = ['count'=>0,'amount'=>0];
                $payment_modes[$m]['count']++;
                $payment_modes[$m]['amount'] += $sp->amount;
            }
        }

        $this->db->from('sales_returns sr')->where('DATE(sr.return_date)', $date)->where('sr.status !=', 'cancelled');
        if ($loc) $this->db->join('invoices inv_r', 'inv_r.id = sr.invoice_id', 'left')->where('inv_r.location_id', $loc);
        $returns = $this->db->select('sr.*')->get()->result();
        $total_returns = array_sum(array_column($returns, 'total_amount'));
        $this->db->where('DATE(invoice_date)', $date)->where('status', 'cancelled');
        if ($loc) $this->db->where('location_id', $loc);
        $cancelled_count = $this->db->count_all_results('invoices');

        // Direct cash refunds also reduce real net sales for the day, and
        // reduce the relevant payment-mode bucket (credit notes move no cash).
        // The helper doesn't support location filtering — when locked to a
        // location, skip the global refund adjustment (refunds are a tiny
        // fraction; a per-location helper can be added later).
        $day_refunds = $loc ? ['total'=>0,'cash'=>0,'upi'=>0,'card'=>0] : get_refund_summary_for_date($date);
        $total_sales -= ($total_returns + $day_refunds['total']);
        foreach (['cash', 'upi', 'card'] as $mode) {
            if (isset($payment_modes[$mode]) && $day_refunds[$mode] > 0) {
                $payment_modes[$mode]['amount'] -= $day_refunds[$mode];
            }
        }

        $data['total_sales']    = $total_sales;
        $data['bill_count']     = $bill_count;
        $data['avg_basket']     = $bill_count > 0 ? $total_sales / $bill_count : 0;
        $data['avg_bill']       = $bill_count > 0 ? $total_sales / $bill_count : 0;
        $data['customer_count'] = count($customer_ids);
        $data['payment_modes']  = $payment_modes;
        $data['total_returns']  = $total_returns;
        $data['returns_count']  = count($returns);
        $data['cancelled_count']= $cancelled_count;
        $data['invoices']       = $invoices;

        $export = $this->input->get('export');
        if ($export === 'excel') {
            $headers = ['Metric', 'Value', 'Bills'];
            $rows = [
                ['Total Sales', $total_sales, ''],
                ['Bills Generated', $bill_count, ''],
                ['Avg Bill Value', $data['avg_bill'], ''],
                ['Customers', $data['customer_count'], ''],
                ['Returns Value', $total_returns, ''],
                ['Cancellations', $cancelled_count, ''],
                ['', '', ''],
                ['Payment Mode Summary', '', ''],
            ];
            $modes_label = ['cash'=>'Cash','upi'=>'UPI','card'=>'Card','neft'=>'NEFT','cheque'=>'Cheque','wallet'=>'Wallet','credit'=>'Credit','split'=>'Split'];
            foreach ($payment_modes as $mode => $vals) {
                $rows[] = [$modes_label[$mode] ?? ucfirst($mode), $vals['amount'], $vals['count']];
            }
            $this->_export_excel('Day End Metrics (' . date('d-M-Y', strtotime($date)) . ')', $headers, $rows);
            return;
        } elseif ($export === 'pdf') {
            $this->load->model('Invoice_model');
            $data['company'] = $this->Invoice_model->get_company_settings();
            $this->load->library('pdf_generator');
            $html = $this->load->view('reports/day_end_metrics_pdf', $data, TRUE);
            $this->pdf_generator->generate($html, 'Day_End_Metrics_' . $date, 'download', 'A4');
            exit;
        }

        $this->render('reports/day_end_metrics', $data);
    }

    // ============================================================
    // #9 Counter-wise & Bill-wise Summary Report
    // ============================================================
    public function counter_summary() {
        $from = $this->input->get('from') ?: date('Y-m-d');
        $to   = $this->input->get('to')   ?: date('Y-m-d');
        $cashier_id = (int)$this->input->get('cashier_id');
        $data['page_title'] = 'Counter-wise & Bill-wise Summary';
        $data['from'] = $from; $data['to'] = $to;
        $data['cashier_id'] = $cashier_id;
        $loc = $this->_get_loc_id();

        // Cashier dropdown: only show users from this location for locked-down managers
        if ($loc) {
            $data['cashiers'] = $this->db->select('id, name')->where('status', 1)
                ->group_start()->where('location_id', $loc)->or_where('location_id IS NULL')->group_end()
                ->get('users')->result();
        } else {
            $data['cashiers'] = $this->db->select('id, name')->where('status', 1)->get('users')->result();
        }

        $where_loc       = $loc ? "AND i.location_id = $loc" : '';
        $where_loc_user  = $loc ? "AND (u.location_id = $loc OR u.location_id IS NULL)" : '';
        $where_cashier   = $cashier_id ? "AND cs.cashier_id = $cashier_id" : '';
        $where_shiftless = $cashier_id ? "AND i.created_by = $cashier_id" : '';
        $data['counter_summary'] = $this->db->query("
            SELECT u.name as cashier_name,
                   u.id as cashier_id,
                   cs.id as shift_id,
                   cs.opened_at, cs.closed_at,
                   COUNT(i.id) as bill_count,
                   SUM(i.total_amount) as total_sales,
                   SUM(i.paid_amount) as total_collected,
                   SUM(i.due_amount) as total_due,
                   SUM(i.discount) as total_discount,
                   AVG(i.total_amount) as avg_bill_value
            FROM cashier_shifts cs
            LEFT JOIN users u ON u.id = cs.cashier_id
            LEFT JOIN invoices i ON i.shift_id = cs.id AND i.status != 'cancelled' $where_loc
            WHERE DATE(cs.opened_at) BETWEEN ? AND ? $where_cashier $where_loc_user
            GROUP BY cs.id
            HAVING bill_count > 0

            UNION ALL

            SELECT u.name as cashier_name,
                   u.id as cashier_id,
                   NULL as shift_id,
                   MIN(i.invoice_date) as opened_at, MAX(i.invoice_date) as closed_at,
                   COUNT(i.id) as bill_count,
                   SUM(i.total_amount) as total_sales,
                   SUM(i.paid_amount) as total_collected,
                   SUM(i.due_amount) as total_due,
                   SUM(i.discount) as total_discount,
                   AVG(i.total_amount) as avg_bill_value
            FROM invoices i
            LEFT JOIN users u ON u.id = i.created_by
            WHERE (i.shift_id IS NULL OR i.shift_id = 0)
              AND DATE(i.invoice_date) BETWEEN ? AND ?
              AND i.status != 'cancelled' $where_shiftless $where_loc
            GROUP BY i.created_by

            ORDER BY opened_at DESC
        ", [$from, $to, $from, $to])->result();

        // Refunds have no shift_id, so attribute them by time window AND by
        // the cashier who processed them (same filter as the bill-wise list
        // below) so a refund isn't double-counted across overlapping shifts
        foreach ($data['counter_summary'] as $c) {
            if (!$c->opened_at) { $c->total_refunds = 0; $c->net_sales = $c->total_sales ?? 0; continue; }
            $c_end = $c->closed_at ?: date('Y-m-d H:i:s');
            $refund_q = $this->db->select_sum('refund_amount', 'total')
                                 ->where('created_at >=', $c->opened_at)
                                 ->where('created_at <=', $c_end)
                                 ->where('status', 'processed');
            if ($c->cashier_id) $refund_q->where('created_by', $c->cashier_id);
            $refund_row = $refund_q->get('refunds')->row();
            $c->total_refunds = $refund_row->total ?? 0;
            $c->net_sales     = ($c->total_sales ?? 0) - $c->total_refunds;
        }

        // Bill-wise details for each counter
        $data['bills'] = $this->db->query("
            SELECT i.*, u.name as cashier_name, c.name as customer_name
            FROM invoices i
            LEFT JOIN cashier_shifts cs ON cs.id = i.shift_id
            LEFT JOIN users u ON u.id = cs.cashier_id
            LEFT JOIN customers c ON c.id = i.customer_id
            WHERE DATE(i.invoice_date) BETWEEN ? AND ?
              AND i.status != 'cancelled' $where_cashier $where_loc
            ORDER BY cs.cashier_id, i.invoice_date
        ", [$from, $to])->result();

        // Refunds within the period (attributed by the user who processed them,
        // since refunds have no shift_id)
        $where_refund_cashier = $cashier_id ? "AND r.created_by = $cashier_id" : '';
        $data['refunds'] = $this->db->query("
            SELECT r.*, r.created_by_name as cashier_name, c.name as customer_name
            FROM refunds r
            LEFT JOIN customers c ON c.id = r.customer_id
            WHERE DATE(r.created_at) BETWEEN ? AND ?
              AND r.status = 'processed' $where_refund_cashier
            ORDER BY r.created_at
        ", [$from, $to])->result();

        // Merge invoices + refunds into a single chronological row list for
        // the bill-wise table
        $bill_rows = [];
        foreach ($data['bills'] as $b) {
            $bill_rows[] = (object)[
                'row_type'     => 'invoice',
                'id'           => $b->id,
                'ref_no'       => $b->invoice_no,
                'ts'           => $b->created_at ?: $b->invoice_date,
                'date'         => $b->invoice_date,
                'cashier_name' => $b->cashier_name,
                'customer_name'=> $b->customer_name,
                'mode'         => $b->payment_mode,
                'amount'       => $b->total_amount,
                'paid'         => $b->paid_amount,
                'due'          => $b->due_amount,
                'status'       => $b->status,
            ];
        }
        foreach ($data['refunds'] as $r) {
            $bill_rows[] = (object)[
                'row_type'     => 'refund',
                'id'           => $r->id,
                'ref_no'       => $r->refund_no . ' (for ' . $r->invoice_no . ')',
                'ts'           => $r->created_at,
                'date'         => $r->created_at,
                'cashier_name' => $r->cashier_name,
                'customer_name'=> $r->customer_name,
                'mode'         => $r->refund_mode,
                'amount'       => -$r->refund_amount,
                'paid'         => null,
                'due'          => null,
                'status'       => 'refund',
            ];
        }
        usort($bill_rows, function($a, $b) { return strtotime($a->ts) <=> strtotime($b->ts); });
        $data['bill_rows'] = $bill_rows;

        $export = $this->input->get('export', TRUE);
        if (($export === 'excel' || $export === 'csv') && $this->_can('frm_rpt_export')) {
            $title   = 'Counter-wise & Bill-wise Summary ' . $from . ' to ' . $to;
            $headers = ['Cashier','Shift','Opened At','Closed At','Bills','Sales (Rs)','Refund (Rs)','Net Sales (Rs)','Collected (Rs)','Due (Rs)','Discount (Rs)','Avg Bill (Rs)'];
            $rows    = [];
            foreach ($data['counter_summary'] as $cs) {
                $rows[] = [
                    $cs->cashier_name ?? 'Unknown',
                    $cs->shift_id ? '#' . $cs->shift_id : '',
                    $cs->opened_at ? date('d-M-Y H:i', strtotime($cs->opened_at)) : '',
                    $cs->closed_at ? date('d-M-Y H:i', strtotime($cs->closed_at)) : 'Open',
                    $cs->bill_count ?? 0,
                    number_format($cs->total_sales ?? 0, 2),
                    number_format($cs->total_refunds ?? 0, 2),
                    number_format($cs->net_sales ?? 0, 2),
                    number_format($cs->total_collected ?? 0, 2),
                    number_format($cs->total_due ?? 0, 2),
                    number_format($cs->total_discount ?? 0, 2),
                    number_format($cs->avg_bill_value ?? 0, 2),
                ];
            }
            if (!empty($data['counter_summary'])) {
                $rows[] = [
                    'TOTAL', '', '', '',
                    array_sum(array_column($data['counter_summary'], 'bill_count')),
                    number_format(array_sum(array_column($data['counter_summary'], 'total_sales')), 2),
                    number_format(array_sum(array_column($data['counter_summary'], 'total_refunds')), 2),
                    number_format(array_sum(array_column($data['counter_summary'], 'net_sales')), 2),
                    number_format(array_sum(array_column($data['counter_summary'], 'total_collected')), 2),
                    number_format(array_sum(array_column($data['counter_summary'], 'total_due')), 2),
                    number_format(array_sum(array_column($data['counter_summary'], 'total_discount')), 2),
                    '',
                ];
            }

            $rows[] = [];
            $rows[] = ['BILL-WISE DETAILS'];
            $rows[] = ['#','Invoice No','Date','Cashier','Customer','Mode','Total (Rs)','Paid (Rs)','Due (Rs)','Status'];
            foreach ($data['bills'] as $i => $b) {
                $rows[] = [
                    $i + 1,
                    $b->invoice_no,
                    date('d-M-Y', strtotime($b->invoice_date)) . ' ' . date('H:i', strtotime($b->created_at)),
                    $b->cashier_name ?? '-',
                    $b->customer_name ?? '-',
                    ucfirst($b->payment_mode),
                    number_format($b->total_amount, 2),
                    number_format($b->paid_amount, 2),
                    number_format($b->due_amount, 2),
                    ucfirst($b->status),
                ];
            }

            if ($export === 'excel') { $this->_export_excel($title, $headers, $rows); return; }

            $company_q = $this->db->get('company_settings')->result_array();
            $company = []; foreach ($company_q as $row) $company[$row['key_name']] = $row['key_value'];
            $this->_export_csv_fallback(
                $title, $headers, $rows,
                $company['company_name'] ?? 'ERP',
                trim(str_replace("\n", ', ', $company['company_address'] ?? ''), ', '),
                $company['company_phone'] ?? '',
                $company['company_gstin'] ?? ''
            );
            return;
        }

        $this->render('reports/counter_summary', $data);
    }

    public function category_report() {
        $this->_rpt('frm_rpt_category');
        $from=$this->input->get('from')?:date('Y-m-01'); $to=$this->input->get('to')?:date('Y-m-d');
        $export=$this->input->get('export',TRUE)?:$this->input->post('export',TRUE); $loc=$this->_get_loc_id();
        $categories=$this->Report_model->get_category_report($from,$to,$loc);
        $stock_by_cat=$this->db->query("SELECT COALESCE(cat.name, p.category, 'Uncategorized') AS category_name,SUM(GREATEST(p.stock_qty,0)) as current_stock,SUM(GREATEST(p.stock_qty,0)*p.purchase_price) as stock_value,COUNT(p.id) as product_count FROM products p LEFT JOIN categories cat ON cat.id=p.category_id WHERE p.status=1 GROUP BY category_name")->result();
        $stock_map=[]; foreach ($stock_by_cat as $s) $stock_map[$s->category_name]=$s;
        $total_s=array_sum(array_column((array)$categories,'total_sales'));
        foreach ($categories as &$cat) { $s=$stock_map[$cat->category_name]??null; $cat->current_stock=$s->current_stock??0; $cat->stock_value=$s->stock_value??0; $cat->product_count=$s->product_count??0; $cat->contribution_pct=$total_s>0?round($cat->total_sales/$total_s*100,1):0; }
        $title='Category Report '.$from.' to '.$to; $ep='from='.$from.'&to='.$to;
        $headers=['#','Category','Qty Sold','Sales','Profit','Margin%','Contribution%','Stock Qty','Stock Value','SKUs'];
        $rows=[]; $i=1;
        $t_qty=0; $t_sales=0; $t_profit=0; $t_stock_qty=0; $t_stock_value=0; $t_skus=0;
        foreach ($categories as $r) {
            $m=$r->total_sales>0?round($r->gross_profit/$r->total_sales*100,1):0;
            $rows[]=[$i++,$r->category_name,$r->total_qty,$r->total_sales,$r->gross_profit,$m.'%',($r->contribution_pct??0).'%',$r->current_stock,$r->stock_value,$r->product_count];
            $t_qty+=$r->total_qty; $t_sales+=$r->total_sales; $t_profit+=$r->gross_profit;
            $t_stock_qty+=$r->current_stock; $t_stock_value+=$r->stock_value; $t_skus+=$r->product_count;
        }
        if (!empty($categories)) {
            $t_margin = $t_sales>0 ? round($t_profit/$t_sales*100,1) : 0;
            $rows[]=['TOTAL','',$t_qty,$t_sales,$t_profit,$t_margin.'%','100%',$t_stock_qty,$t_stock_value,$t_skus];
        }
        if ($export==='excel'&&$this->_can('frm_rpt_export')){$this->_export_excel($title,$headers,$rows);return;}
        if ($export==='pdf'  &&$this->_can('frm_rpt_export')){$this->_export_pdf($title,$headers,$rows);return;}
        if ($export==='email'){$this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/category_report?'.$ep));return;}
        $data=['page_title'=>'Category Sales & Stock Report','from'=>$from,'to'=>$to,'categories'=>$categories,'export_route'=>'reports/category_report','export_params'=>$ep,'export_title'=>$title];
        $data=array_merge($data,$this->_locations_data()); $this->render('reports/category_report',$data);
    }

    // Shared data builder for brand report (used by main view + exports)
    private function _brand_report_data($from, $to, $sel_brand, $loc) {
        $brands = $this->Report_model->get_brand_report($from, $to, $loc);
        $stock_by_brand = $this->db->query("
            SELECT COALESCE(p.brand,'Unbranded') AS brand_name,
                   SUM(GREATEST(p.stock_qty, 0)) as current_stock,
                   SUM(GREATEST(p.stock_qty, 0) * p.purchase_price) as stock_value,
                   COUNT(p.id) as product_count
            FROM products p WHERE p.status = 1 GROUP BY p.brand
        ")->result();
        $stock_map = [];
        foreach ($stock_by_brand as $s) $stock_map[$s->brand_name] = $s;
        $total_sales_all = array_sum(array_map(fn($b)=>(float)$b->total_sales, $brands));
        $brand_names = [];
        foreach ($brands as &$brand) {
            $s = $stock_map[$brand->brand_name] ?? null;
            $brand->current_stock    = $s->current_stock ?? 0;
            $brand->stock_value      = $s->stock_value ?? 0;
            $brand->product_count    = $s->product_count ?? 0;
            $brand->contribution_pct = $total_sales_all > 0 ? round($brand->total_sales / $total_sales_all * 100, 1) : 0;
            $brand_names[] = $brand->brand_name;
        }
        $detail_products = $detail_items = [];
        if ($sel_brand !== '') {
            $detail_products = $this->db->query("
                SELECT ii.product_name, COALESCE(p.brand,'Unbranded') as brand_name, p.item_code,
                       SUM(ii.qty) as total_qty, SUM(ii.total) as total_sales,
                       SUM(ii.qty * COALESCE(p.purchase_price,0)) as total_cost,
                       SUM(ii.total) - SUM(ii.qty * COALESCE(p.purchase_price,0)) as gross_profit,
                       GREATEST(p.stock_qty, 0) as current_stock
                FROM invoice_items ii
                JOIN invoices inv ON inv.id = ii.invoice_id
                LEFT JOIN products p ON p.id = ii.product_id
                WHERE DATE(inv.invoice_date) BETWEEN ? AND ? AND inv.status != 'cancelled'
                  AND COALESCE(p.brand,'Unbranded') = ?
                GROUP BY ii.product_id, ii.product_name ORDER BY total_sales DESC
            ", [$from, $to, $sel_brand])->result();
            $detail_items = $this->db->query("
                SELECT inv.invoice_no, DATE(inv.invoice_date) as inv_date, inv.invoice_date,
                       c.name as customer_name, ii.product_name, p.item_code,
                       ii.qty, ii.rate, ii.total, ii.gst_percent
                FROM invoice_items ii
                JOIN invoices inv ON inv.id = ii.invoice_id
                LEFT JOIN products p ON p.id = ii.product_id
                LEFT JOIN customers c ON c.id = inv.customer_id
                WHERE DATE(inv.invoice_date) BETWEEN ? AND ? AND inv.status != 'cancelled'
                  AND COALESCE(p.brand,'Unbranded') = ?
                ORDER BY inv.invoice_date DESC, inv.id DESC
            ", [$from, $to, $sel_brand])->result();
        }
        return compact('brands','brand_names','detail_products','detail_items');
    }

    public function brand_report() {
        $this->_rpt('frm_rpt_category');
        $from       = $this->input->get('from') ?: date('Y-m-01');
        $to         = $this->input->get('to')   ?: date('Y-m-d');
        $sel_brand  = $this->input->get('brand', TRUE) ?: '';
        $export     = $this->input->get('export', TRUE) ?: '';
        $loc        = $this->_get_loc_id();
        $d = $this->_brand_report_data($from, $to, $sel_brand, $loc);

        // ---- EXPORTS ----
        $title = 'Brand Report ' . $from . ' to ' . $to . ($sel_brand ? ' — '.$sel_brand : '');

        if ($export === 'excel') {
            // Summary sheet
            $headers = ['#','Brand','Qty Sold','Sales Value','Gross Profit','Margin%','Contribution%','Stock Qty','Stock Value','SKUs'];
            $rows = [];
            $i = 1;
            $t_qty=0; $t_sales=0; $t_profit=0; $t_stock_qty=0; $t_stock_value=0; $t_skus=0;
            foreach ($d['brands'] as $r) {
                $margin = $r->total_sales > 0 ? round($r->gross_profit/$r->total_sales*100,1) : 0;
                $rows[] = [$i++, $r->brand_name, $r->total_qty, $r->total_sales, $r->gross_profit, $margin.'%', $r->contribution_pct.'%', $r->current_stock, $r->stock_value, $r->product_count];
                $t_qty+=$r->total_qty; $t_sales+=$r->total_sales; $t_profit+=$r->gross_profit;
                $t_stock_qty+=$r->current_stock; $t_stock_value+=$r->stock_value; $t_skus+=$r->product_count;
            }
            if (!empty($d['brands'])) {
                $t_margin = $t_sales>0 ? round($t_profit/$t_sales*100,1) : 0;
                $rows[] = ['TOTAL','',$t_qty,$t_sales,$t_profit,$t_margin.'%','100%',$t_stock_qty,$t_stock_value,$t_skus];
            }
            if ($sel_brand && !empty($d['detail_items'])) {
                $rows[] = [];
                $rows[] = ['--- Bill-wise Detail: '.$sel_brand.' ---'];
                $rows[] = ['Bill No','Date','Customer','Product','Item Code','Qty','Rate','GST%','Amount'];
                $t_amount = 0;
                foreach ($d['detail_items'] as $x) {
                    $rows[] = [$x->invoice_no, $x->inv_date, $x->customer_name??'Walk-in', $x->product_name, $x->item_code??'', $x->qty, $x->rate, $x->gst_percent.'%', $x->total];
                    $t_amount += $x->total;
                }
                $rows[] = ['TOTAL','','','','','','','', number_format($t_amount,2)];
            }
            $this->_export_excel($title, $headers, $rows);
            return;
        }

        if ($export === 'pdf') {
            $autoload = APPPATH . '../vendor/autoload.php';
            if (!file_exists($autoload)) {
                $this->session->set_flashdata('error', 'mPDF not installed.');
                redirect('reports/brand_report');
            }
            require_once $autoload;
            $company = $this->Report_model->get_company_settings();
            $html = $this->load->view('reports/brand_report_pdf', array_merge($d, [
                'from'=>$from,'to'=>$to,'sel_brand'=>$sel_brand,'company'=>$company,'title'=>$title
            ]), TRUE);
            $mpdf = new \Mpdf\Mpdf(['orientation'=>'L','margin_left'=>8,'margin_right'=>8,'margin_top'=>10,'margin_bottom'=>8]);
            $mpdf->SetTitle($title);
            $mpdf->WriteHTML($html);
            $mpdf->Output(str_replace([' ','—'],['_',''],$title).'.pdf', 'D');
            exit;
        }

        if ($export === 'email') {
            $to_email = $this->input->post('email_to', TRUE);
            if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
                $this->session->set_flashdata('error', 'Invalid email address.');
                redirect('reports/brand_report?from='.$from.'&to='.$to.'&brand='.urlencode($sel_brand));
            }
            // Build CSV attachment in memory
            $csv = implode(',', ['#','Brand','Qty Sold','Sales','Profit','Margin%','Contribution%','Stock Qty','Stock Value','SKUs'])."\n";
            $i = 1;
            foreach ($d['brands'] as $r) {
                $margin = $r->total_sales > 0 ? round($r->gross_profit/$r->total_sales*100,1) : 0;
                $csv .= implode(',', [$i++, '"'.$r->brand_name.'"', $r->total_qty, $r->total_sales, $r->gross_profit, $margin.'%', $r->contribution_pct.'%', $r->current_stock, $r->stock_value, $r->product_count])."\n";
            }
            $this->load->library('email');
            $company = $this->Report_model->get_company_settings();
            $cfg = [
                'protocol'  => 'smtp',
                'smtp_host' => $company['smtp_host'] ?? 'smtp.gmail.com',
                'smtp_port' => $company['smtp_port'] ?? 587,
                'smtp_user' => $company['smtp_user'] ?? '',
                'smtp_pass' => $company['smtp_pass'] ?? '',
                'smtp_crypto'=> $company['smtp_crypto'] ?? 'tls',
                'mailtype'  => 'html',
                'charset'   => 'utf-8',
            ];
            $this->email->initialize($cfg);
            $this->email->from($company['company_email'] ?? $cfg['smtp_user'], $company['company_name'] ?? 'ERP System');
            $this->email->to($to_email);
            $this->email->subject($title);
            $this->email->message('<p>Please find the Brand Report attached.</p><p>Period: <b>'.$from.'</b> to <b>'.$to.'</b>'.($sel_brand?' | Brand: <b>'.$sel_brand.'</b>':'').'</p><br><p>'.$company['company_name'].' ERP</p>');
            $tmp = tempnam(sys_get_temp_dir(), 'brand_rpt_') . '.csv';
            file_put_contents($tmp, $csv);
            $this->email->attach($tmp, 'attachment', 'Brand_Report_'.date('Ymd').'.csv', 'text/csv');
            if ($this->email->send()) {
                $this->session->set_flashdata('success', 'Report emailed to '.$to_email.' successfully.');
            } else {
                $this->session->set_flashdata('error', 'Email failed. Check SMTP settings in Settings page.');
            }
            @unlink($tmp);
            redirect('reports/brand_report?from='.$from.'&to='.$to.'&brand='.urlencode($sel_brand));
        }
        // ---- END EXPORTS ----

        $data = array_merge($d, [
            'page_title' => 'Brand Sales & Stock Report',
            'from'       => $from,
            'to'         => $to,
            'sel_brand'  => $sel_brand,
        ]);
        $data = array_merge($data, $this->_locations_data());
        $this->render('reports/brand_report', $data);
    }

    public function supplier_report() {
        $this->_rpt('frm_rpt_category');
        $from=$this->input->get('from')?:date('Y-m-01'); $to=$this->input->get('to')?:date('Y-m-d');
        $export=$this->input->get('export',TRUE)?:$this->input->post('export',TRUE);
        $suppliers=$this->Report_model->get_supplier_report($from,$to);
        $title='Supplier Report '.$from.' to '.$to; $ep='from='.$from.'&to='.$to;
        $headers=['#','Supplier','GSTIN','Phone','Orders','Total Purchased','Paid','Balance'];
        $rows=[]; $i=1;
        foreach ($suppliers as $r) $rows[]=[$i++,$r->supplier_name??'',$r->gst_number??'',$r->phone??'',$r->purchase_count??0,$r->total_bills??0,$r->total_paid??0,$r->outstanding??0];
        if ($export==='excel'&&$this->_can('frm_rpt_export')){$this->_export_excel($title,$headers,$rows);return;}
        if ($export==='pdf'  &&$this->_can('frm_rpt_export')){$this->_export_pdf($title,$headers,$rows);return;}
        if ($export==='email'){$this->_send_report_email($this->input->post('email_to',TRUE),$title,$headers,$rows,base_url('reports/supplier_report?'.$ep));return;}
        $data=['page_title'=>'Supplier Report','from'=>$from,'to'=>$to,'suppliers'=>$suppliers,'export_route'=>'reports/supplier_report','export_params'=>$ep,'export_title'=>$title];
        $this->render('reports/supplier_report',$data);
    }

    // -------------------------------------------------------
    // CLOSING STOCK SUMMARY
    // -------------------------------------------------------
    public function closing_stock_summary() {
        $this->_rpt('frm_rpt_stock'); // Using stock report permission
        
        $from = $this->input->get('from', TRUE) ?: date('Y-m-01');
        $to   = $this->input->get('to', TRUE) ?: date('Y-m-d');
        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);
        $loc = $this->_get_loc_id();

        $filters = [
            'from_date'       => $from,
            'to_date'         => $to,
            'location_id'     => $loc,
            'department_id'   => $this->input->get('department_id', TRUE),
            'category_id'     => $this->input->get('category_id', TRUE),
            'brand_id'        => $this->input->get('brand_id', TRUE),
            'supplier_id'     => $this->input->get('supplier_id', TRUE),
            'item_code'       => $this->input->get('item_code', TRUE),
            'status'          => $this->input->get('status', TRUE) !== '' ? $this->input->get('status', TRUE) : 1,
            'show_zero_stock' => $this->input->get('show_zero_stock', TRUE) !== null ? $this->input->get('show_zero_stock', TRUE) : '1',
        ];

        $items = $this->Report_model->get_closing_stock_report($filters);

        $title = 'Closing Stock Report As On : ' . date('d-m-Y', strtotime($to));
        if ($from != $to) {
            $title .= ' (From: ' . date('d-m-Y', strtotime($from)) . ')';
        }
        
        $ep = http_build_query($this->input->get());
        
        $headers = ['Sr', 'Item Code', 'Barcode', 'Item Name', 'UOM', 'Opening Qty', 'Purchase', 'Purchase Return', 'Transfer In', 'Transfer Out', 'Sales', 'Sales Return', 'Stock Take', 'Adjustment (+/-)', 'Closing Qty', 'Basic Cost', 'CP Rate', 'MRP', 'Tax %', 'Closing Value (Basic)', 'Closing Value (CP)', 'Closing Value (MRP)', 'Tax Value'];
        
        $rows = [];
        $i = 1;
        $tot_basic = 0; $tot_cp = 0; $tot_mrp = 0; $tot_tax = 0;
        
        foreach ($items as $r) {
            $val_basic = $r->closing_qty * $r->basic_cost;
            $val_cp    = $r->closing_qty * $r->cp_rate;
            $val_mrp   = $r->closing_qty * $r->mrp;
            $tax_val   = ($val_cp * $r->tax_percent) / 100; // Calculate tax on CP value
            
            $tot_basic += $val_basic;
            $tot_cp    += $val_cp;
            $tot_mrp   += $val_mrp;
            $tot_tax   += $tax_val;
            
            $rows[] = [
                $i++, 
                $r->item_code, 
                $r->barcode, 
                $r->item_name, 
                $r->uom,
                round($r->opening_qty, 3), 
                round($r->purchase_qty, 3), 
                round($r->purchase_return_qty, 3),
                round($r->transfer_in_qty, 3), 
                round($r->transfer_out_qty, 3), 
                round($r->sale_qty, 3),
                round($r->sale_return_qty, 3), 
                round($r->stock_take_qty, 3), 
                round($r->adjustment_qty, 3),
                round($r->closing_qty, 3),
                number_format($r->basic_cost, 2, '.', ''), 
                number_format($r->cp_rate, 2, '.', ''), 
                number_format($r->mrp, 2, '.', ''), 
                $r->tax_percent . '%',
                number_format($val_basic, 2, '.', ''),
                number_format($val_cp, 2, '.', ''),
                number_format($val_mrp, 2, '.', ''),
                number_format($tax_val, 2, '.', '')
            ];
        }
        
        if ($export === 'excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title, $headers, $rows); return; }
        if ($export === 'csv'   && $this->_can('frm_rpt_export')) { $this->_export_excel($title, $headers, $rows); return; }
        if ($export === 'pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title, $headers, $rows); return; }
        if ($export === 'email') { $this->_send_report_email($this->input->post('email_to', TRUE), $title, $headers, $rows, base_url('reports/closing_stock_summary?'.$ep)); return; }

        $data = [
            'page_title' => 'Closing Stock Summary',
            'title'      => $title,
            'filters'    => $filters,
            'items'      => $items,
            'export_route' => 'reports/closing_stock_summary',
            'export_params' => $ep,
            'export_title' => $title,
            'totals'     => [
                'basic' => $tot_basic,
                'cp'    => $tot_cp,
                'mrp'   => $tot_mrp,
                'tax'   => $tot_tax
            ],
            'categories' => $this->db->select('id, name')->where('status', 1)->order_by('name')->get('categories')->result(),
            'brands'     => $this->db->select('id, name')->where('status', 1)->order_by('name')->get('brands')->result(),
            'suppliers'  => $this->db->select('id, name')->order_by('name')->get('suppliers')->result(),
            'departments'=> $this->db->select('id, name')->order_by('name')->get('departments')->result()
        ];
        $data = array_merge($data, $this->_locations_data());
        $this->render('reports/closing_stock', $data);
    }

    // Item Margin Report
    public function margin_report() {
        $this->_rpt('frm_rpt_stock'); // Using stock report permission

        $export = $this->input->get('export', TRUE) ?: $this->input->post('export', TRUE);

        $filters = [
            'item_name'   => $this->input->get('item_name', TRUE),
            'brand_id'    => $this->input->get('brand_id', TRUE),
            'category_id' => $this->input->get('category_id', TRUE),
            'status'      => $this->input->get('status', TRUE) !== '' ? $this->input->get('status', TRUE) : 1,
        ];

        $products = $this->Report_model->get_margin_report($filters);

        $title = 'All Item Margin Report';
        $ep = http_build_query($this->input->get());

        $headers = ['Sr', 'Item Code', 'Item Name', 'Brand', 'Category', 'Basic Cost', 'CP', 'MRP', 'SP', 'Basic Margin %', 'CP Margin %', 'MRP Margin %', 'SP Margin %', 'Net Margin %'];

        $rows = [];
        $i = 1;
        foreach ($products as $p) {
            // Basic Margin: profit over the tax-exclusive purchase cost
            $p->basic_margin_pct = $p->basic_cost > 0 ? (($p->selling_price - $p->basic_cost) / $p->basic_cost) * 100 : 0;
            // CP Margin: profit over the tax-inclusive landed cost
            $p->cp_margin_pct    = $p->cp_rate > 0 ? (($p->selling_price - $p->cp_rate) / $p->cp_rate) * 100 : 0;
            // MRP Margin: headroom between CP and MRP, as % of MRP
            $p->mrp_margin_pct   = $p->mrp > 0 ? (($p->mrp - $p->cp_rate) / $p->mrp) * 100 : 0;
            // SP Margin: profit as % of actual selling price
            $p->sp_margin_pct    = $p->selling_price > 0 ? (($p->selling_price - $p->cp_rate) / $p->selling_price) * 100 : 0;
            // Net Margin: strips GST out of SP first (GST collected is a pass-through, not profit),
            // then compares against the tax-exclusive basic cost — the "real" business margin.
            $sp_excl_tax = $p->gst_percent > 0 ? ($p->selling_price / (1 + $p->gst_percent / 100)) : $p->selling_price;
            $p->net_margin_pct   = $p->basic_cost > 0 ? (($sp_excl_tax - $p->basic_cost) / $p->basic_cost) * 100 : 0;

            $rows[] = [
                $i++,
                $p->item_code,
                $p->item_name,
                $p->brand_name,
                $p->category_name,
                number_format($p->basic_cost, 2, '.', ''),
                number_format($p->cp_rate, 2, '.', ''),
                number_format($p->mrp, 2, '.', ''),
                number_format($p->selling_price, 2, '.', ''),
                number_format($p->basic_margin_pct, 2, '.', '') . '%',
                number_format($p->cp_margin_pct, 2, '.', '') . '%',
                number_format($p->mrp_margin_pct, 2, '.', '') . '%',
                number_format($p->sp_margin_pct, 2, '.', '') . '%',
                number_format($p->net_margin_pct, 2, '.', '') . '%',
            ];
        }

        if ($export === 'excel' && $this->_can('frm_rpt_export')) { $this->_export_excel($title, $headers, $rows); return; }
        if ($export === 'csv'   && $this->_can('frm_rpt_export')) { $this->_export_excel($title, $headers, $rows); return; }
        if ($export === 'pdf'   && $this->_can('frm_rpt_export')) { $this->_export_pdf($title, $headers, $rows); return; }
        if ($export === 'email') { $this->_send_report_email($this->input->post('email_to', TRUE), $title, $headers, $rows, base_url('reports/margin_report?'.$ep)); return; }

        $data = [
            'page_title'    => 'Item Margin Report',
            'title'         => $title,
            'filters'       => $filters,
            'items'         => $products,
            'export_route'  => 'reports/margin_report',
            'export_params' => $ep,
            'export_title'  => $title,
            'categories'    => $this->db->select('id, name')->where('status', 1)->order_by('name')->get('categories')->result(),
            'brands'        => $this->db->select('id, name')->where('status', 1)->order_by('name')->get('brands')->result(),
        ];
        $this->render('reports/margin_report', $data);
    }

    public function login_dashboard() {
        $role = $this->session->userdata('role');
        if (!in_array($role, ['admin', 'superadmin', 'owner'])) {
            $this->session->set_flashdata('error', 'Access denied.');
            redirect('dashboard');
        }

        $data['page_title'] = 'Login Dashboard';

        $today = date('Y-m-d');
        $data['logins_today'] = (int)$this->db
            ->where('module', 'auth')->where('action', 'login')
            ->where('DATE(created_at)', $today)
            ->count_all_results('audit_logs');

        $data['users'] = $this->db->select('id, name, username, role, location_id, last_login, status')
            ->order_by('last_login', 'DESC')->get('users')->result();

        $data['devices'] = $this->db->select('da.*, u.name as user_name, u.username')
            ->from('device_authorizations da')
            ->join('users u', 'u.id = da.user_id', 'left')
            ->order_by('da.last_login', 'DESC')
            ->get()->result();

        $data['device_counts'] = ['approved' => 0, 'pending' => 0, 'rejected' => 0];
        foreach ($data['devices'] as $d) {
            $data['device_counts'][$d->status] = ($data['device_counts'][$d->status] ?? 0) + 1;
        }

        $data['recent_logins'] = $this->db->select('user_name, ip_address, created_at')
            ->where('module', 'auth')->where('action', 'login')
            ->order_by('created_at', 'DESC')->limit(50)
            ->get('audit_logs')->result();

        $data['locations'] = [];
        $locs = $this->db->select('id, name')->get('locations')->result();
        foreach ($locs as $l) $data['locations'][$l->id] = $l->name;

        $this->render('reports/login_dashboard', $data);
    }

    public function device_action_ajax() {
        $role = $this->session->userdata('role');
        if (!in_array($role, ['admin', 'superadmin', 'owner'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        $id     = (int)$this->input->post('id');
        $action = $this->input->post('action', TRUE);
        if (!$id || !in_array($action, ['approved', 'rejected'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        $this->db->where('id', $id)->update('device_authorizations', [
            'status'      => $action,
            'reviewed_by' => $this->session->userdata('user_id'),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        echo json_encode(['success' => true]);
    }

    // ── Rack-wise Stock Report ─────────────────────────────
    public function rack_stock_report() {
        $this->_rpt('frm_rpt_stock');
        if (!$this->db->table_exists('racks')) {
            $this->session->set_flashdata('error', 'Rack system not set up. Run /masters/migrate_racks first.');
            redirect('dashboard');
        }

        $rack_id = $this->input->get('rack_id', TRUE);
        $export  = $this->input->get('export', TRUE);

        $racks = $this->db->select('racks.*, l.name AS location_name')
            ->from('racks')
            ->join('locations l', 'l.id = racks.location_id', 'left')
            ->where('racks.status', 1)
            ->order_by('racks.sort_order')->order_by('racks.rack_code')
            ->get()->result();

        $products = [];
        $rack_summary = [];
        foreach ($racks as $r) {
            $this->db->select('products.id, products.name, products.item_code, GREATEST(products.stock_qty, 0) AS stock_qty, products.mrp, products.selling_price, products.purchase_price, products.basic_cost, products.gst_percent, products.unit, c.name AS category_name, b.name AS brand_name')
                ->from('products')
                ->join('categories c', 'c.id = products.category_id', 'left')
                ->join('brands b', 'b.id = products.brand_id', 'left')
                ->where('products.rack_id', $r->id)
                ->where('products.status', 1)
                ->order_by('products.name');
            $items = $this->db->get()->result();

            $total_qty = 0; $total_value = 0; $total_mrp_val = 0;
            foreach ($items as $it) {
                $it->net_value = round((float)$it->basic_cost * (float)$it->stock_qty, 2);
                $it->mrp_value = round((float)$it->mrp * (float)$it->stock_qty, 2);
                $total_qty += (float)$it->stock_qty;
                $total_value += $it->net_value;
                $total_mrp_val += $it->mrp_value;
            }

            $rack_summary[] = (object)[
                'rack_code'     => $r->rack_code,
                'rack_name'     => $r->rack_name,
                'location_name' => $r->location_name ?? 'All',
                'item_count'    => count($items),
                'total_qty'     => $total_qty,
                'net_value'     => $total_value,
                'mrp_value'     => $total_mrp_val,
                'id'            => $r->id,
            ];

            if (!$rack_id || $rack_id == $r->id) {
                $products[$r->id] = ['rack' => $r, 'items' => $items];
            }
        }

        $unassigned_count = $this->db->where('rack_id IS NULL', null, false)->where('status', 1)->count_all_results('products');

        $title = 'Rack-wise Stock Report — ' . date('d M Y');
        if ($rack_id) {
            $sel = $this->db->where('id', $rack_id)->get('racks')->row();
            if ($sel) $title = 'Stock Report — Rack ' . $sel->rack_code . ' — ' . date('d M Y');
        }

        if ($export === 'excel' && $this->_can('frm_rpt_export')) {
            $headers = ['Rack Code','Rack Name','Location','Item Code','Product','Category','Brand','Unit','Stock Qty','Basic Cost','MRP','SP','Net Value','MRP Value'];
            $rows = [];
            foreach ($products as $grp) {
                foreach ($grp['items'] as $it) {
                    $rows[] = [$grp['rack']->rack_code, $grp['rack']->rack_name, $grp['rack']->location_name ?? 'All',
                        $it->item_code, $it->name, $it->category_name ?? '', $it->brand_name ?? '',
                        $it->unit ?? 'PCS', $it->stock_qty, $it->basic_cost, $it->mrp, $it->selling_price,
                        $it->net_value, $it->mrp_value];
                }
            }
            $this->_export_excel($title, $headers, $rows);
            return;
        }

        $ep = http_build_query(array_filter(['rack_id' => $rack_id]));
        $data['page_title']       = 'Rack-wise Stock Report';
        $data['racks']            = $racks;
        $data['rack_summary']     = $rack_summary;
        $data['products']         = $products;
        $data['selected_rack']    = $rack_id;
        $data['unassigned_count'] = $unassigned_count;
        $data['export_route']     = 'reports/rack_stock_report';
        $data['export_params']    = $ep;
        $data['export_title']     = $title;
        $this->render('reports/rack_stock', $data);
    }
}