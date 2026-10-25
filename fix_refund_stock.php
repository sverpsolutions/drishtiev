<?php
define('BASEPATH', TRUE);
define('APPPATH', __DIR__ . '/application/');
require_once __DIR__ . '/index.php';

$CI =& get_instance();
$CI->load->model(['Location_model', 'Product_model']);
$CI->load->database();

$token = $CI->input->get('token');
if ($token !== 'fix9823refund') {
    echo 'Unauthorized'; exit;
}

$dry = $CI->input->get('apply') !== '1';

echo '<pre>';
echo $dry ? "=== DRY RUN (add ?apply=1 to execute) ===\n\n" : "=== APPLYING FIXES ===\n\n";

$refunds = $CI->db->select('r.id, r.refund_no, r.created_at, i.invoice_no, i.location_id')
    ->from('refunds r')
    ->join('invoices i', 'i.id = r.invoice_id')
    ->where('r.status', 'processed')
    ->get()->result();

$adjustments = [];

foreach ($refunds as $r) {
    $items = $CI->db->where('refund_id', $r->id)->get('refund_items')->result();
    foreach ($items as $ri) {
        $key = $ri->product_id . '_' . $r->location_id;
        if (!isset($adjustments[$key])) {
            $adjustments[$key] = [
                'product_id'   => (int)$ri->product_id,
                'product_name' => $ri->product_name,
                'location_id'  => (int)$r->location_id,
                'total_qty'    => 0,
                'refund_nos'   => [],
            ];
        }
        $adjustments[$key]['total_qty'] += (float)$ri->qty;
        if (!in_array($r->refund_no, $adjustments[$key]['refund_nos'])) {
            $adjustments[$key]['refund_nos'][] = $r->refund_no;
        }
    }
}

if (empty($adjustments)) {
    echo "No refunds found to fix.\n";
    echo '</pre>'; exit;
}

$already_fixed = $CI->db->where('movement_type', 'refund_in')->count_all_results('stock_movement_log');
if ($already_fixed > 0) {
    echo "WARNING: {$already_fixed} refund_in movements already exist.\n";
    echo "Some refunds may have been processed AFTER the fix was deployed.\n";
    echo "Skipping products that already have refund_in entries.\n\n";
}

$fixed_products = [];
if ($already_fixed > 0) {
    $existing = $CI->db->select('DISTINCT product_id, reference_id')
        ->where('movement_type', 'refund_in')
        ->get('stock_movement_log')->result();
    foreach ($existing as $e) {
        $fixed_products[$e->reference_id . '_' . $e->product_id] = true;
    }
}

$count = 0;
foreach ($adjustments as $adj) {
    $current = $CI->db->select('stock_qty')
        ->where('product_id', $adj['product_id'])
        ->where('location_id', $adj['location_id'])
        ->get('product_location_stock')->row();
    $before = $current ? (float)$current->stock_qty : 0;
    $after = $before + $adj['total_qty'];

    echo sprintf("%-40s | loc=%d | +%.1f | %6.1f -> %6.1f | %s\n",
        substr($adj['product_name'], 0, 40),
        $adj['location_id'],
        $adj['total_qty'],
        $before, $after,
        implode(',', $adj['refund_nos'])
    );

    if (!$dry) {
        $CI->Location_model->increase_stock($adj['product_id'], $adj['location_id'], $adj['total_qty']);
        $new_stock = $CI->Location_model->get_stock($adj['product_id'], $adj['location_id']);
        $CI->Location_model->log_movement([
            'product_id'     => $adj['product_id'],
            'product_name'   => $adj['product_name'],
            'location_id'    => $adj['location_id'],
            'location_name'  => '',
            'movement_type'  => 'refund_in',
            'quantity'       => $adj['total_qty'],
            'balance_before' => $before,
            'balance_after'  => $new_stock,
            'reference_type' => 'refund',
            'reference_id'   => 0,
        ]);
    }
    $count++;
}

echo "\n{$count} product-location combos to fix.\n";
if ($dry) echo "\nThis is a DRY RUN. Add ?token=fix9823refund&apply=1 to execute.\n";
else echo "\nDone! All location stocks restored.\n";
echo '</pre>';
