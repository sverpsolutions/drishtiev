<?php
// ============================================================
// FILE: application/models/Plu_model.php
// PLU (Price Look-Up) Management Model
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class Plu_model extends CI_Model {

    private $table = 'plu_master';

    // ── Barcode Lookup (the most critical method) ─────────────
    // Returns array of PLUs for a barcode; if 1 → auto-add; if >1 → show popup
    public function get_by_barcode($barcode, $location_id = null) {
        $this->load->model('Master_model');
        $show_zero = $this->db->where('key_name', 'plu_show_zero_stock')
            ->get('company_settings')->row();
        $include_zero_stock = $show_zero && $show_zero->key_value == '1';

        $select = 'pm.*, GREATEST(pm.stock_qty, 0) AS stock_qty, p.name AS item_name, p.item_code, p.unit,
            p.gst_percent AS default_gst, p.hsn_id,
            hm.hsn_code, hm.igst_percent, hm.cgst_percent, hm.sgst_percent,
            COALESCE(b.name, \'\') AS brand_name,
            COALESCE(c.name, \'\') AS category_name,
            s.name AS supplier_name';

        $joins = function() use ($location_id, $include_zero_stock) {
            $this->db->from('plu_master pm')
                ->join('products p',    'p.id = pm.product_id',  'left')
                ->join('hsn_master hm', 'hm.id = p.hsn_id',      'left')
                ->join('brands b',      'b.id = p.brand_id',      'left')
                ->join('categories c',  'c.id = p.category_id',   'left')
                ->join('suppliers s',   's.id = pm.supplier_id',  'left')
                ->where('pm.status', 1);
            if (!$include_zero_stock) {
                $this->db->where('pm.stock_qty >', 0);
            }
            if ($location_id) {
                $this->db->group_start()
                    ->where('pm.location_id', $location_id)
                    ->or_where('pm.location_id IS NULL')
                    ->group_end();
            }
        };

        // 1st try: match plu_master.barcode directly
        $this->db->select($select);
        $joins();
        $this->db->where('pm.barcode', $barcode);
        $rows = $this->db->order_by('pm.mrp', 'ASC')->get()->result();
        if (!empty($rows)) return $rows;

        // 2nd try: match product's item_code (item_code used as barcode)
        $this->db->select($select);
        $joins();
        $this->db->where('p.item_code', $barcode);
        $rows = $this->db->order_by('pm.mrp', 'ASC')->get()->result();
        if (!empty($rows)) return $rows;

        // 3rd try: match via product_barcodes table
        $bc = $this->db->where('barcode', $barcode)->get('product_barcodes')->row();
        if ($bc) {
            $this->db->select($select);
            $joins();
            $this->db->where('pm.product_id', $bc->product_id);
            $rows = $this->db->order_by('pm.mrp', 'ASC')->get()->result();
            if (!empty($rows)) return $rows;
        }

        return [];
    }

    // ── Look up by product_id (for purchase entry MRP check) ─
    public function get_by_product($product_id) {
        return $this->db->select('pm.*, s.name AS supplier_name')
            ->from($this->table . ' pm')
            ->join('suppliers s', 's.id = pm.supplier_id', 'left')
            ->where('pm.product_id', $product_id)
            ->where('pm.status', 1)
            ->order_by('pm.mrp', 'ASC')
            ->get()->result();
    }

    // ── Check if a PLU with same MRP already exists ───────────
    // $any_status: the importer must also see deactivated PLUs, otherwise it
    // inserts a second row for an MRP that already exists but is switched off.
    public function find_by_product_mrp($product_id, $mrp, $any_status = FALSE) {
        $this->db->where('product_id', $product_id)->where('mrp', $mrp);
        if (!$any_status) $this->db->where('status', 1);
        return $this->db->get($this->table)->row();
    }

    // ── Look up a PLU by its code (used by the Excel importer) ─
    public function find_by_code($plu_code) {
        return $this->db->where('plu_code', $plu_code)->get($this->table)->row();
    }

    // ── Resolve an Excel row to a product ────────────────────
    // Tries item_code, then any barcode we know about, then exact name.
    // Returns ['product' => object|null, 'matched_by' => string, 'ambiguous' => bool].
    public function resolve_product($item_code, $barcode, $name) {
        $item_code = trim((string)$item_code);
        $barcode   = trim((string)$barcode);
        $name      = trim((string)$name);

        if ($item_code !== '') {
            $rows = $this->db->where('item_code', $item_code)->get('products')->result();
            // Duplicate item codes are a known data problem on some instances —
            // never guess which product was meant, make the user fix the master.
            if (count($rows) > 1) return ['product' => null, 'matched_by' => 'item_code', 'ambiguous' => true];
            if (count($rows) === 1) return ['product' => $rows[0], 'matched_by' => 'item_code', 'ambiguous' => false];
        }

        if ($barcode !== '') {
            $pm = $this->db->select('product_id')->where('barcode', $barcode)
                ->group_by('product_id')->get($this->table)->result();
            if (count($pm) === 1) {
                $p = $this->db->where('id', $pm[0]->product_id)->get('products')->row();
                if ($p) return ['product' => $p, 'matched_by' => 'plu barcode', 'ambiguous' => false];
            } elseif (count($pm) > 1) {
                return ['product' => null, 'matched_by' => 'plu barcode', 'ambiguous' => true];
            }

            $pb = $this->db->select('product_id')->where('barcode', $barcode)
                ->group_by('product_id')->get('product_barcodes')->result();
            if (count($pb) === 1) {
                $p = $this->db->where('id', $pb[0]->product_id)->get('products')->row();
                if ($p) return ['product' => $p, 'matched_by' => 'product barcode', 'ambiguous' => false];
            } elseif (count($pb) > 1) {
                return ['product' => null, 'matched_by' => 'product barcode', 'ambiguous' => true];
            }
        }

        if ($name !== '') {
            $rows = $this->db->where('LOWER(name)', strtolower($name))->get('products')->result();
            if (count($rows) > 1) return ['product' => null, 'matched_by' => 'name', 'ambiguous' => true];
            if (count($rows) === 1) return ['product' => $rows[0], 'matched_by' => 'name', 'ambiguous' => false];
        }

        return ['product' => null, 'matched_by' => '', 'ambiguous' => false];
    }

    // ── Turn parsed sheet rows into a classified preview ─────
    // Pure-ish: reads the master data, writes nothing. Returns
    // ['preview' => [...rows...], 'counts' => [new, update, error, warning]].
    public function build_import_preview(array $raw, array $col) {
            $get = function ($row, $field) use ($col) {
                return isset($col[$field]) ? trim((string)($row[$col[$field]] ?? '')) : '';
            };

            // Cache the lookups — a 5,000-row sheet must not fire 15,000 queries.
            $suppliers = $locations = [];
            foreach ($this->db->select('id, name')->get('suppliers')->result() as $s) {
                $suppliers[strtolower(trim($s->name))] = (int)$s->id;
            }
            foreach ($this->db->select('id, name')->get('locations')->result() as $l) {
                $locations[strtolower(trim($l->name))] = (int)$l->id;
            }

            $preview = [];
            $seen    = [];   // catches the same product+MRP twice inside one file
            $counts  = ['new' => 0, 'update' => 0, 'error' => 0, 'warning' => 0];

            foreach ($raw as $i => $row) {
                $line = $i + 2;   // +1 for the header, +1 because Excel is 1-based

                // A row is blank when every mapped cell is empty — skip silently.
                $any = FALSE;
                foreach ($col as $ix) { if (trim((string)($row[$ix] ?? '')) !== '') { $any = TRUE; break; } }
                if (!$any) continue;

                $plu_code  = $get($row, 'plu_code');
                $item_code = $get($row, 'item_code');
                $name      = $get($row, 'name');
                $barcode   = $get($row, 'barcode');
                $mrp       = $this->_imp_num($get($row, 'mrp'));
                $sell      = $this->_imp_num($get($row, 'selling_rate'));
                $purch     = $this->_imp_num($get($row, 'purchase_rate'));
                $landing   = $this->_imp_num($get($row, 'landing_cost'));
                $gst       = $this->_imp_num($get($row, 'gst_percent'));
                $stock     = $this->_imp_num($get($row, 'stock_qty'));

                $err = []; $warn = [];
                $action = 'new'; $plu_id = null; $product = null;

                if ($plu_code !== '') {
                    $hit = $this->find_by_code($plu_code);
                    if (!$hit) {
                        $err[] = 'PLU code "' . $plu_code . '" does not exist';
                    } else {
                        $action  = 'update';
                        $plu_id  = (int)$hit->id;
                        $product = $this->_imp_product($hit->product_id);
                    }
                }

                if (!$plu_id && empty($err)) {
                    $m = $this->resolve_product($item_code, $barcode, $name);
                    if ($m['ambiguous']) {
                        $err[] = 'More than one product matches this ' . $m['matched_by'] . ' — fix the item master first';
                    } elseif (!$m['product']) {
                        $err[] = 'No product found for item code / barcode / name';
                    } else {
                        $product = $m['product'];
                    }
                }

                if ($mrp <= 0) $err[] = 'MRP is required and must be greater than 0';

                if ($product && $mrp > 0 && !$plu_id) {
                    $key = $product->id . '|' . number_format($mrp, 2, '.', '');
                    if (isset($seen[$key])) {
                        $err[] = 'Same product and MRP already appears on row ' . $seen[$key];
                    } else {
                        $seen[$key] = $line;
                        $exist = $this->find_by_product_mrp($product->id, $mrp, TRUE);
                        if ($exist) { $action = 'update'; $plu_id = (int)$exist->id; $plu_code = $exist->plu_code; }
                    }
                }

                // On a NEW PLU the selling rate has to come from somewhere; on an
                // update a blank cell just means "leave it alone".
                if ($action === 'new' && $sell <= 0) {
                    if ($mrp > 0) { $sell = $mrp; $warn[] = 'No selling rate given — using MRP'; }
                    else          { $err[]  = 'Selling rate is required'; }
                }
                if ($sell > 0 && $mrp > 0 && $sell > $mrp) $warn[] = 'Selling rate is above MRP';
                if ($purch > 0 && $sell > 0 && $purch > $sell) $warn[] = 'Purchase rate is above selling rate';
                if ($gst > 0 && !in_array((float)$gst, [0, 0.25, 3, 5, 12, 18, 28], TRUE)) {
                    $warn[] = 'Unusual GST rate ' . $gst . '%';
                }

                $mfg = $this->_imp_date($get($row, 'mfg_date'), $err, 'Mfg date');
                $exp = $this->_imp_date($get($row, 'expiry_date'), $err, 'Expiry date');
                if ($mfg && $exp && $exp < $mfg) $err[] = 'Expiry date is before the mfg date';

                $sup_id = null; $sup_txt = $get($row, 'supplier');
                if ($sup_txt !== '') {
                    $sup_id = $suppliers[strtolower($sup_txt)] ?? null;
                    if (!$sup_id) $warn[] = 'Supplier "' . $sup_txt . '" not found — left blank';
                }
                $loc_id = null; $loc_txt = $get($row, 'location');
                if ($loc_txt !== '') {
                    $loc_id = $locations[strtolower($loc_txt)] ?? null;
                    if (!$loc_id) $warn[] = 'Location "' . $loc_txt . '" not found — PLU will apply to all locations';
                }

                $status_txt = strtolower($get($row, 'status'));
                $status = 1;
                if ($status_txt !== '') {
                    $status = in_array($status_txt, ['0', 'no', 'n', 'inactive', 'disabled', 'false'], TRUE) ? 0 : 1;
                }

                if (!empty($err)) { $action = 'error'; $counts['error']++; }
                else              { $counts[$action]++; }
                if (!empty($warn) && $action !== 'error') $counts['warning']++;

                $preview[] = [
                    'line'          => $line,
                    'action'        => $action,
                    'plu_id'        => $plu_id,
                    'plu_code'      => $plu_code,
                    'product_id'    => $product ? (int)$product->id : null,
                    'product_name'  => $product ? $product->name : ($name ?: $item_code ?: $barcode),
                    'item_code'     => $product ? $product->item_code : $item_code,
                    'barcode'       => $barcode,
                    'batch_no'      => $get($row, 'batch_no'),
                    'mrp'           => $mrp,
                    'selling_rate'  => $sell,
                    'purchase_rate' => $purch,
                    'landing_cost'  => $landing,
                    'gst_percent'   => $gst,
                    'stock_qty'     => $stock,
                    'has_stock_col' => isset($col['stock_qty']),
                    'mfg_date'      => $mfg,
                    'expiry_date'   => $exp,
                    'supplier_id'   => $sup_id,
                    'location_id'   => $loc_id,
                    'status'        => $status,
                    'errors'        => $err,
                    'warnings'      => $warn,
                ];
            }

        return ['preview' => $preview, 'counts' => $counts];
    }

    // ── Get all PLUs (for listing page) ──────────────────────
    public function get_all($filters = []) {
        $this->db->select('pm.*, GREATEST(pm.stock_qty, 0) AS stock_qty, p.name AS item_name, p.item_code, p.unit,
            COALESCE(b.name, \'\') AS brand_name,
            COALESCE(c.name, \'\') AS category_name,
            s.name AS supplier_name,
            l.name AS location_name')
            ->from('plu_master pm')
            ->join('products p',    'p.id = pm.product_id',   'left')
            ->join('brands b',      'b.id = p.brand_id',       'left')
            ->join('categories c',  'c.id = p.category_id',    'left')
            ->join('suppliers s',   's.id = pm.supplier_id',   'left')
            ->join('locations l',   'l.id = pm.location_id',   'left');

        if (!empty($filters['product_id']))  $this->db->where('pm.product_id', $filters['product_id']);
        if (!empty($filters['barcode']))     $this->db->like('pm.barcode', $filters['barcode']);
        if (!empty($filters['batch_no']))    $this->db->like('pm.batch_no', $filters['batch_no']);
        if (isset($filters['status']) && $filters['status'] !== '') {
            $this->db->where('pm.status', $filters['status']);
        }
        if (!empty($filters['location_id'])) $this->db->where('pm.location_id', $filters['location_id']);
        $this->load->helper('product_search');
        $ws = !empty($filters['search']) ? product_words_sql($filters['search'], ['p.name', 'pm.plu_code', 'pm.barcode', 'pm.batch_no']) : NULL;
        if ($ws) {
            $this->db->where($ws, NULL, FALSE);
        } elseif (!empty($filters['search'])) {
            $this->db->group_start()
                ->like('p.name',      $filters['search'])
                ->or_like('pm.plu_code', $filters['search'])
                ->or_like('pm.barcode',  $filters['search'])
                ->or_like('pm.batch_no', $filters['search'])
                ->group_end();
        }

        return $this->db->order_by('pm.product_id', 'ASC')
            ->order_by('pm.mrp', 'ASC')
            ->get()->result();
    }

    public function get_by_id($id) {
        return $this->db->select('pm.*, GREATEST(pm.stock_qty, 0) AS stock_qty, p.name AS item_name, p.item_code, p.unit,
            s.name AS supplier_name, l.name AS location_name')
            ->from('plu_master pm')
            ->join('products p',  'p.id = pm.product_id',  'left')
            ->join('suppliers s', 's.id = pm.supplier_id', 'left')
            ->join('locations l', 'l.id = pm.location_id', 'left')
            ->where('pm.id', $id)
            ->get()->row();
    }

    // ── Generate next PLU code for a product ─────────────────
    public function generate_plu_code($product_id) {
        $this->db->query(
            'INSERT INTO plu_sequence (product_id, last_seq) VALUES (?, 1)
             ON DUPLICATE KEY UPDATE last_seq = last_seq + 1',
            [(int)$product_id]
        );
        $row = $this->db->where('product_id', $product_id)->get('plu_sequence')->row();
        $seq = $row ? (int)$row->last_seq : 1;
        return 'PLU' . str_pad($product_id, 4, '0', STR_PAD_LEFT) . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    // ── Create a new PLU ─────────────────────────────────────
    public function create($data) {
        if (empty($data['plu_code'])) {
            $data['plu_code'] = $this->generate_plu_code($data['product_id']);
        }
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    // ── Update PLU ───────────────────────────────────────────
    public function update($id, $data) {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    // ── Decrease stock on sale ───────────────────────────────
    public function decrease_stock($plu_id, $qty) {
        return $this->db->query(
            'UPDATE plu_master SET stock_qty = stock_qty - ? WHERE id = ?',   // may go negative; shown capped at 0
            [(float)$qty, (int)$plu_id]
        );
    }

    // ── Increase stock on purchase ───────────────────────────
    public function increase_stock($plu_id, $qty) {
        return $this->db->query(
            'UPDATE plu_master SET stock_qty = stock_qty + ? WHERE id = ?',
            [(float)$qty, (int)$plu_id]
        );
    }

    // ── Reverse decrease (on invoice cancel/return) ──────────
    public function restore_stock($plu_id, $qty) {
        return $this->db->query(
            'UPDATE plu_master SET stock_qty = stock_qty + ? WHERE id = ?',
            [(float)$qty, (int)$plu_id]
        );
    }

    // ── Get PLU for billing JSON response ────────────────────
    public function get_for_billing($plu_id, $location_id = null) {
        $plu = $this->get_by_id($plu_id);
        if (!$plu) return null;

        // Get HSN rates
        $hsn = $this->db->select('igst_percent, cgst_percent, sgst_percent')
            ->where('id', (int)$plu->hsn_id ?? 0)->get('hsn_master')->row();

        return [
            'id'           => $plu->id,
            'plu_code'     => $plu->plu_code,
            'product_id'   => $plu->product_id,
            'name'         => $plu->item_name,
            'item_code'    => $plu->item_code,
            'unit'         => $plu->unit,
            'barcode'      => $plu->barcode,
            'batch_no'     => $plu->batch_no,
            'mrp'          => (float)$plu->mrp,
            'selling_rate' => (float)$plu->selling_rate,
            'purchase_rate'=> (float)$plu->purchase_rate,
            'gst_percent'  => (float)$plu->gst_percent,
            'stock_qty'    => max(0, (float)$plu->stock_qty),   // display stock
            'expiry_date'  => $plu->expiry_date,
            'igst_percent' => $hsn ? (float)$hsn->igst_percent : (float)$plu->gst_percent,
            'cgst_percent' => $hsn ? (float)$hsn->cgst_percent : (float)$plu->gst_percent / 2,
            'sgst_percent' => $hsn ? (float)$hsn->sgst_percent : (float)$plu->gst_percent / 2,
        ];
    }

    // ── REPORTS ───────────────────────────────────────────────

    public function get_low_stock($threshold = 10) {
        return $this->db->select('pm.*, GREATEST(pm.stock_qty, 0) AS stock_qty, p.name AS item_name, p.item_code, p.unit,
            c.name AS category_name, b.name AS brand_name')
            ->from('plu_master pm')
            ->join('products p',    'p.id = pm.product_id',  'left')
            ->join('categories c',  'c.id = p.category_id',  'left')
            ->join('brands b',      'b.id = p.brand_id',     'left')
            ->where('pm.status', 1)
            ->where('pm.stock_qty <=', $threshold)
            ->where('pm.stock_qty >', 0)
            ->order_by('pm.stock_qty', 'ASC')
            ->get()->result();
    }

    public function get_out_of_stock() {
        return $this->db->select('pm.*, GREATEST(pm.stock_qty, 0) AS stock_qty, p.name AS item_name, p.item_code, p.unit')
            ->from('plu_master pm')
            ->join('products p', 'p.id = pm.product_id', 'left')
            ->where('pm.status', 1)
            ->where('pm.stock_qty <=', 0)
            ->order_by('p.name', 'ASC')
            ->get()->result();
    }

    public function get_expiring_soon($days = 30) {
        $cutoff = date('Y-m-d', strtotime("+{$days} days"));
        return $this->db->select('pm.*, GREATEST(pm.stock_qty, 0) AS stock_qty, p.name AS item_name, p.item_code')
            ->from('plu_master pm')
            ->join('products p', 'p.id = pm.product_id', 'left')
            ->where('pm.status', 1)
            ->where('pm.stock_qty >', 0)
            ->where('pm.expiry_date IS NOT NULL', null, false)
            ->where('pm.expiry_date <=', $cutoff)
            ->order_by('pm.expiry_date', 'ASC')
            ->get()->result();
    }

    // ── Total stock value across all active PLUs ─────────────
    public function total_stock_value() {
        $row = $this->db->select('SUM(GREATEST(stock_qty, 0) * purchase_rate) AS total_value', FALSE)
            ->where('status', 1)
            ->get($this->table)->row();
        return $row ? (float)$row->total_value : 0;
    }

    // ── Import value parsers ──────────────────────────────────
    // Product lookup by id, kept here so build_import_preview needs no
    // second model loaded.
    private function _imp_product($id) {
        return $this->db->where('id', (int)$id)->get('products')->row();
    }

    // "₹1,250.50" / "1 250,50" → 1250.50
    private function _imp_num($v) {
        $v = trim((string)$v);
        if ($v === '') return 0.0;
        $v = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', $v));
        return $v === '' ? 0.0 : (float)$v;
    }

    // Accepts dd/mm/yyyy, dd-mm-yyyy, yyyy-mm-dd and Excel's serial numbers.
    private function _imp_date($v, &$err, $label) {
        $v = trim((string)$v);
        if ($v === '') return null;

        // Excel serial date (25569 = days between 1900-01-01 and the unix epoch)
        if (preg_match('/^\d{5}(\.\d+)?$/', $v)) {
            return date('Y-m-d', (int)(((float)$v - 25569) * 86400));
        }
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $v, $m)) {
            if (checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
                return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
            }
            $err[] = $label . ' "' . $v . '" is not a real date';
            return null;
        }
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{2,4})#', $v, $m)) {
            $y = (int)$m[3]; if ($y < 100) $y += 2000;
            if (checkdate((int)$m[2], (int)$m[1], $y)) {
                return sprintf('%04d-%02d-%02d', $y, $m[2], $m[1]);
            }
            $err[] = $label . ' "' . $v . '" is not a real date';
            return null;
        }
        $err[] = $label . ' "' . $v . '" is not a date the system understands';
        return null;
    }
}
