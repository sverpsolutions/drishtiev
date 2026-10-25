<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GarmentReports Controller
 * Clothing & Apparel Analytics, Size-wise Sales Matrix, Colour-wise Matrix & Stock Valuation
 */
class GarmentReports extends Auth_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Garment_model', 'Product_model', 'Master_model', 'Location_model']);
    }

    // ─── Main Reports Hub ─────────────────────────────────────────────────────
    public function index() {
        $data['page_title'] = 'Garments & Clothing Reports';
        $this->render('garment_reports/index', $data);
    }

    // ─── 1. Size-wise Sales & Stock Matrix (Product × Size Grid) ──────────────
    public function size_matrix() {
        $data['page_title'] = 'Size-wise Sales & Stock Matrix';

        $filters = [
            'from'        => $this->input->get('from') ?: date('Y-m-01'),
            'to'          => $this->input->get('to')   ?: date('Y-m-d'),
            'location_id' => $this->input->get('location_id') ?: $this->active_location_id(),
            'brand'       => $this->input->get('brand') ?: '',
            'category'    => $this->input->get('category') ?: '',
        ];

        $matrix_data = $this->Garment_model->get_size_sales_matrix($filters);

        $data['filters']   = $filters;
        $data['sizes']     = $matrix_data['size_columns'];
        $data['styles']    = $matrix_data['styles'];
        $data['sales_map'] = $matrix_data['sales_map'];
        $data['stock_map'] = $matrix_data['stock_map'];
        $data['locations'] = $this->Location_model->get_all();
        $data['brands']    = $this->Master_model->get_all_brands();

        if ($this->input->get('export') === 'csv') {
            $this->_export_size_matrix_csv($data);
            return;
        }

        $this->render('garment_reports/size_matrix', $data);
    }

    // ─── 2. Colour-wise Sales & Stock Matrix ──────────────────────────────────
    public function colour_matrix() {
        $data['page_title'] = 'Colour-wise Sales & Stock Matrix';

        $filters = [
            'from'        => $this->input->get('from') ?: date('Y-m-01'),
            'to'          => $this->input->get('to')   ?: date('Y-m-d'),
            'location_id' => $this->input->get('location_id') ?: $this->active_location_id(),
        ];

        $data['colours_data'] = $this->Garment_model->get_colour_sales_matrix($filters);
        $data['filters']      = $filters;
        $data['locations']    = $this->Location_model->get_all();

        if ($this->input->get('export') === 'csv') {
            $this->_export_colour_matrix_csv($data);
            return;
        }

        $this->render('garment_reports/colour_matrix', $data);
    }

    // ─── 3. Garment Stock Valuation & Reorder Report ──────────────────────────
    public function stock_report() {
        $data['page_title'] = 'Garment Stock Valuation & Reorder Report';

        $filters = [
            'location_id'  => $this->input->get('location_id') !== null && $this->input->get('location_id') !== '' ? $this->input->get('location_id') : '',
            'category'     => $this->input->get('category') ?: '',
            'brand'        => $this->input->get('brand') ?: '',
            'gender'       => $this->input->get('gender') ?: '',
            'size'         => $this->input->get('size') ?: '',
            'colour'       => $this->input->get('colour') ?: '',
            'search'       => $this->input->get('search') ?: '',
            'stock_filter' => $this->input->get('stock_filter') ?: '', // 'all', 'zero', 'low', 'available'
        ];

        // Items matching all current filters
        $data['items'] = $this->Garment_model->get_stock_report($filters);

        // Size-wise breakdown computed respecting all other filters EXCEPT size
        // so user can see piece counts across all available sizes and click to filter!
        $breakdown_filters = $filters;
        unset($breakdown_filters['size']);
        $data['size_breakdown'] = $this->Garment_model->get_size_wise_stock_breakdown($breakdown_filters);

        // Master lists for filters
        $data['filters']    = $filters;
        $data['locations']  = $this->Location_model->get_all();
        $data['brands']     = $this->Master_model->get_all_brands();
        $data['categories'] = $this->Garment_model->get_garment_categories();
        $data['sizes']      = $this->Garment_model->get_distinct_sizes();
        $data['colours']    = $this->Garment_model->get_colours();
        $data['genders']    = ['Men', 'Women', 'Boys', 'Girls', 'Unisex', 'Kids'];

        if ($this->input->get('export') === 'csv') {
            $this->_export_stock_report_csv($data);
            return;
        }

        $this->render('garment_reports/stock_report', $data);
    }

    // ─── CSV Exporters ────────────────────────────────────────────────────────
    private function _export_size_matrix_csv($data) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="size_sales_stock_matrix_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');

        $headers = ['Product / Style Name', 'Style Code', 'Category', 'Brand'];
        foreach ($data['sizes'] as $sz) {
            $headers[] = $sz . ' Sold';
            $headers[] = $sz . ' Stock';
        }
        $headers[] = 'Total Sold Qty';
        $headers[] = 'Total Current Stock';
        fputcsv($out, $headers);

        foreach ($data['styles'] as $st) {
            $row = [
                $st->product_name,
                $st->style_code ?: $st->design_no ?: $st->item_code,
                $st->category,
                $st->brand
            ];
            $t_sold = 0;
            $t_stk  = 0;
            foreach ($data['sizes'] as $sz) {
                $sold = (float)($data['sales_map'][$st->product_id][$sz]['qty'] ?? 0);
                $stk  = (float)($data['stock_map'][$st->product_id][$sz] ?? 0);
                $row[] = $sold;
                $row[] = $stk;
                $t_sold += $sold;
                $t_stk  += $stk;
            }
            $row[] = $t_sold;
            $row[] = $t_stk;
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    private function _export_colour_matrix_csv($data) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="colour_sales_stock_matrix_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Colour Name', 'Variants Count', 'Current Stock Qty', 'Sold Qty', 'Sold Value (INR)']);
        foreach ($data['colours_data'] as $c) {
            fputcsv($out, [
                $c->colour_name,
                $c->total_variants,
                $c->current_stock,
                $c->sold_qty,
                $c->sold_val
            ]);
        }
        fclose($out);
        exit;
    }

    private function _export_stock_report_csv($data) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="garment_stock_valuation_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Product Name',
            'Style Code',
            'Design / Article No',
            'Category',
            'Brand',
            'Gender',
            'Size',
            'Colour',
            'Barcode',
            'Variant SKU',
            'Current Stock (Pcs)',
            'Min Reorder Stock',
            'Cost Price (INR)',
            'Cost Valuation (INR)',
            'MRP (INR)',
            'MRP Valuation (INR)'
        ]);
        foreach ($data['items'] as $item) {
            fputcsv($out, [
                $item->product_name,
                $item->style_code ?: '',
                $item->mfr_article_no ?: ($item->design_no ?: $item->item_code),
                $item->garment_category ?: $item->category,
                $item->brand ?: '',
                $item->gender ?: '',
                $item->size_name,
                $item->colour_name,
                $item->barcode,
                $item->variant_sku,
                $item->stock_qty,
                $item->min_stock,
                $item->cost_price,
                $item->stock_val_cost,
                $item->mrp,
                $item->stock_val_mrp
            ]);
        }
        fclose($out);
        exit;
    }
}
