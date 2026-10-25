<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_take_model extends CI_Model {

    // ─── Generate a unique take number ───────────────────────────
    public function generate_take_no()
    {
        $year = date('Y');
        $prefix = 'STK-' . $year . '-';
        $row = $this->db
            ->select_max('id')
            ->get('stock_takes')
            ->row();
        $next = $row && $row->id ? (int)$row->id + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // ─── Create a new stock take ──────────────────────────────────
    public function create($location_id, $location_name, $user_id, $user_name, $notes = '')
    {
        // Cancel any existing in_progress for this location
        $this->db->where('location_id', $location_id)
                 ->where('status', 'in_progress')
                 ->update('stock_takes', [
                     'status' => 'cancelled',
                 ]);

        $take_no = $this->generate_take_no();

        // Get all active products with their per-location stock
        $products = $this->db
            ->select('p.id, p.name, p.item_code, p.unit, COALESCE(pls.stock_qty, 0) AS stock_qty')
            ->from('products p')
            ->join('product_location_stock pls', "pls.product_id = p.id AND pls.location_id = " . (int)$location_id, 'left')
            ->where('p.status', 1)
            ->order_by('p.name', 'ASC')
            ->get()->result();

        $total_items = count($products);

        // Insert header
        $this->db->insert('stock_takes', [
            'take_no'          => $take_no,
            'location_id'      => $location_id,
            'location_name'    => $location_name,
            'status'           => 'in_progress',
            'notes'            => $notes,
            'started_by'       => $user_id,
            'started_by_name'  => $user_name,
            'started_at'       => date('Y-m-d H:i:s'),
            'total_items'      => $total_items,
            'counted_items'    => 0,
        ]);
        $stock_take_id = $this->db->insert_id();

        // Bulk insert items
        if ($total_items > 0) {
            $rows = [];
            foreach ($products as $p) {
                $rows[] = [
                    'stock_take_id' => $stock_take_id,
                    'product_id'    => $p->id,
                    'product_name'  => $p->name,
                    'item_code'     => $p->item_code,
                    'unit'          => $p->unit ?: 'PCS',
                    'system_qty'    => (float)$p->stock_qty,
                    'counted_qty'   => null,
                    'saved_at'      => null,
                ];
            }
            $this->db->insert_batch('stock_take_items', $rows);
        }

        return $stock_take_id;
    }

    // ─── Get single stock take ────────────────────────────────────
    public function get_by_id($id)
    {
        return $this->db->get_where('stock_takes', ['id' => $id])->row();
    }

    // ─── Get all stock takes with optional filters ────────────────
    public function get_all($filters = [])
    {
        $this->db->from('stock_takes');
        if (!empty($filters['location_id'])) {
            $this->db->where('location_id', $filters['location_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }
        return $this->db->order_by('id', 'DESC')->get()->result();
    }

    // ─── Get items for a stock take ───────────────────────────────
    public function get_items($stock_take_id)
    {
        return $this->db
            ->select('sti.*, p.cost_price')
            ->from('stock_take_items sti')
            ->join('products p', 'p.id = sti.product_id', 'left')
            ->where('sti.stock_take_id', $stock_take_id)
            ->order_by('sti.product_name', 'ASC')
            ->get()->result();
    }

    // ─── UPSERT counted qty for one item ─────────────────────────
    public function save_item_count($stock_take_id, $product_id, $counted_qty)
    {
        $item = $this->db->get_where('stock_take_items', [
            'stock_take_id' => $stock_take_id,
            'product_id'    => $product_id,
        ])->row();

        if (!$item) {
            return ['success' => false, 'message' => 'Item not found in this stock take'];
        }

        $was_counted_before = ($item->counted_qty !== null);

        $this->db->where('stock_take_id', $stock_take_id)
                 ->where('product_id', $product_id)
                 ->update('stock_take_items', [
                     'counted_qty' => $counted_qty,
                     'saved_at'    => date('Y-m-d H:i:s'),
                 ]);

        // Recount counted_items in the parent
        $counted_row = $this->db
            ->select('COUNT(*) AS cnt')
            ->where('stock_take_id', $stock_take_id)
            ->where('counted_qty IS NOT NULL', null, false)
            ->get('stock_take_items')->row();
        $counted = (int)($counted_row ? $counted_row->cnt : 0);

        $take = $this->get_by_id($stock_take_id);

        $this->db->where('id', $stock_take_id)->update('stock_takes', [
            'counted_items' => $counted,
        ]);

        $variance = (float)$counted_qty - (float)$item->system_qty;

        return [
            'success'       => true,
            'variance'      => round($variance, 3),
            'counted'       => $counted,
            'total'         => $take ? (int)$take->total_items : 0,
        ];
    }

    // ─── Scan-mode: ADD qty to item (cumulative, multi-device) ──────
    public function scan_add_qty($stock_take_id, $product_id, $add_qty)
    {
        // Try to find existing row in stock_take_items
        $item = $this->db->get_where('stock_take_items', [
            'stock_take_id' => $stock_take_id,
            'product_id'    => $product_id,
        ])->row();

        if ($item) {
            $new_qty = (float)($item->counted_qty ?? 0) + (float)$add_qty;
            $this->db->where('stock_take_id', $stock_take_id)
                     ->where('product_id', $product_id)
                     ->update('stock_take_items', [
                         'counted_qty' => $new_qty,
                         'saved_at'    => date('Y-m-d H:i:s'),
                     ]);
        } else {
            // Product not in pre-loaded list (added later) — insert on the fly
            $p = $this->db->get_where('products', ['id' => $product_id])->row();
            $new_qty = (float)$add_qty;
            $this->db->insert('stock_take_items', [
                'stock_take_id' => $stock_take_id,
                'product_id'    => $product_id,
                'product_name'  => $p ? $p->name : '',
                'item_code'     => $p ? $p->item_code : '',
                'unit'          => $p ? ($p->unit ?: 'PCS') : 'PCS',
                'system_qty'    => $p ? (float)$p->stock_qty : 0,
                'counted_qty'   => $new_qty,
                'saved_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        // Update counted_items count on header
        $cnt = $this->db->select('COUNT(*) AS c')
                        ->where('stock_take_id', $stock_take_id)
                        ->where('counted_qty IS NOT NULL', null, false)
                        ->get('stock_take_items')->row();
        $this->db->where('id', $stock_take_id)
                 ->update('stock_takes', ['counted_items' => (int)($cnt ? $cnt->c : 0)]);

        $system_qty = $item ? (float)$item->system_qty : 0;
        return [
            'success'    => true,
            'new_qty'    => $new_qty,
            'system_qty' => $system_qty,
            'variance'   => round($new_qty - $system_qty, 3),
        ];
    }

    // ─── Import counted quantities from Excel rows ───────────────────
    // Each row: ['item_code' => '...', 'mrp' => ..., 'qty' => ...]
    //
    // Matching runs in three passes, most-certain first, and stops at the
    // first hit:
    //   1. exact item_code
    //   2. the value read as a barcode/EAN — people paste scanner output into
    //      the item_code column, and a barcode is unambiguous when it hits
    //   3. leading zeros ignored on both sides — Excel silently turns a text
    //      item code like "0012345" into the number 12345 on save, which is
    //      the single most common reason a row "isn't found"
    // Pass 3 is skipped for any key that two different products both collapse
    // onto: a wrong match writes a count against the wrong product, which is
    // worse than reporting the row as unmatched.
    //
    // Returns the unmatched codes themselves (not just a count) so the user
    // can fix the file instead of guessing which rows were dropped.
    public function import_excel_rows($stock_take_id, $rows)
    {
        $matched   = 0;
        $not_found = 0;
        $by_method = ['item_code' => 0, 'barcode' => 0, 'zero_padded' => 0];
        $not_found_codes = [];
        $invalid_qty_codes = [];
        $duplicate_codes = [];

        $items = $this->db
            ->select('id, product_id, product_name, item_code, counted_qty, system_qty')
            ->from('stock_take_items')
            ->where('stock_take_id', $stock_take_id)
            ->get()->result();

        $by_code = [];      // exact item_code → item
        $by_loose = [];     // item_code with leading zeros stripped → item
        $loose_ambiguous = [];
        $by_product = [];

        // Two different products carrying the same item_code is a master-data
        // fault, not a file fault — only one of them can ever receive a count,
        // so the pair is recorded and named in the report.
        $code_dupes = [];

        foreach ($items as $item) {
            $by_product[$item->product_id] = $item;
            $code = strtoupper(trim((string)$item->item_code));
            if ($code === '') continue;
            $code_dupes[$code][] = $item;
            $by_code[$code] = $item;

            $loose = ltrim($code, '0');
            if ($loose === '') continue;
            if (isset($by_loose[$loose]) && $by_loose[$loose]->id !== $item->id) {
                $loose_ambiguous[$loose] = true;
            }
            $by_loose[$loose] = $item;
        }

        // Barcodes for the products in this take only. A barcode shared by two
        // products is dropped rather than guessed at, same rule as above.
        $by_barcode = [];
        $bc_rows = $this->db->query(
            "SELECT pb.product_id, pb.barcode
               FROM product_barcodes pb
               JOIN stock_take_items sti
                 ON sti.product_id = pb.product_id AND sti.stock_take_id = ?",
            [$stock_take_id]
        )->result();
        foreach ($bc_rows as $b) {
            $bk = strtoupper(trim((string)$b->barcode));
            if ($bk === '' || !isset($by_product[$b->product_id])) continue;
            $item = $by_product[$b->product_id];
            if (!array_key_exists($bk, $by_barcode)) { $by_barcode[$bk] = $item; continue; }
            if ($by_barcode[$bk] === false) continue;   // already ruled ambiguous
            if ($by_barcode[$bk]->id !== $item->id) $by_barcode[$bk] = false;
        }

        $counted_before = 0;
        foreach ($items as $item) {
            if ($item->counted_qty !== null) $counted_before++;
        }

        $now = date('Y-m-d H:i:s');
        $seen = [];
        $shared_reported = [];
        $issue_rows = [];   // one entry per problem row, for the downloadable report

        foreach ($rows as $row) {
            $raw = trim((string)$row['item_code']);
            $key = strtoupper($raw);
            if ($key === '') continue;

            $sti = null; $how = null;
            if (isset($by_code[$key])) {
                $sti = $by_code[$key]; $how = 'item_code';

                // Reported once per code, not once per row — the operator needs
                // to fix the product master, not the spreadsheet.
                if (count($code_dupes[$key]) > 1 && !isset($shared_reported[$key])) {
                    $shared_reported[$key] = true;
                    $names = [];
                    foreach ($code_dupes[$key] as $d) $names[] = '#' . $d->product_id . ' ' . $d->product_name;
                    $issue_rows[] = [
                        'row'          => $row['_row'] ?? null,
                        'item_code'    => $raw,
                        'mrp'          => $row['mrp'] ?? null,
                        'qty'          => $row['qty'] ?? null,
                        'issue'        => 'Item code shared by ' . count($code_dupes[$key]) . ' products',
                        'product_id'   => $sti->product_id,
                        'product_name' => $sti->product_name,
                        'detail'       => 'DUPLICATE ITEM CODE IN THE PRODUCT MASTER: ' . implode('  |  ', $names)
                                        . '. Only "' . $sti->product_name . '" can receive a count from this code — '
                                        . 'the other product(s) stay uncounted and will show a false shortage. '
                                        . 'Fix by giving each product its own item code in Products, then re-run the stock take.',
                    ];
                }
            } elseif (!empty($by_barcode[$key])) {
                $sti = $by_barcode[$key]; $how = 'barcode';
            } else {
                $loose = ltrim($key, '0');
                if ($loose !== '' && isset($by_loose[$loose]) && !isset($loose_ambiguous[$loose])) {
                    $sti = $by_loose[$loose]; $how = 'zero_padded';
                }
            }

            if (!$sti) {
                $not_found++;
                $not_found_codes[$raw] = true;
                $issue_rows[] = [
                    'row'       => $row['_row'] ?? null,
                    'item_code' => $raw,
                    'mrp'       => $row['mrp'] ?? null,
                    'qty'       => $row['qty'] ?? null,
                    'issue'     => 'Item code not found',
                ];
                continue;
            }

            // A blank or non-numeric qty means "not counted", not "counted
            // zero" — writing 0 here would zero the product's stock on
            // finalize, so the row is reported back instead of applied.
            $qty_raw = is_string($row['qty']) ? trim($row['qty']) : $row['qty'];
            if ($qty_raw === '' || $qty_raw === null || !is_numeric($qty_raw)) {
                $invalid_qty_codes[$raw] = true;
                $issue_rows[] = [
                    'row'          => $row['_row'] ?? null,
                    'item_code'    => $raw,
                    'mrp'          => $row['mrp'] ?? null,
                    'qty'          => $row['qty'] ?? null,
                    'issue'        => 'Blank / non-numeric qty',
                    'product_id'   => $sti->product_id,
                    'product_name' => $sti->product_name ?? '',
                    'detail'       => 'Item matched, but the qty cell is empty or not a number — left uncounted.',
                ];
                continue;
            }

            if (isset($seen[$sti->id])) {
                $duplicate_codes[$raw] = true;
                $issue_rows[] = [
                    'row'          => $row['_row'] ?? null,
                    'item_code'    => $raw,
                    'mrp'          => $row['mrp'] ?? null,
                    'qty'          => $row['qty'] ?? null,
                    'issue'        => 'Duplicate row',
                    'product_id'   => $sti->product_id,
                    'product_name' => $sti->product_name ?? '',
                    'detail'       => (isset($code_dupes[$key]) && count($code_dupes[$key]) > 1)
                        ? 'This code is shared by ' . count($code_dupes[$key]) . ' products, so both file rows '
                          . 'landed on the same one and only the last qty survived — see the "Item code shared by" '
                          . 'row above for the same code.'
                        : 'This item appears more than once in the file — the last qty was used.',
                ];
            }
            $seen[$sti->id] = true;

            $was_counted = ($sti->counted_qty !== null);

            $this->db->where('id', $sti->id)
                     ->update('stock_take_items', [
                         'counted_qty' => (float)$qty_raw,
                         'saved_at'    => $now,
                     ]);

            if (!$was_counted) {
                $counted_before++;
                $sti->counted_qty = (float)$qty_raw; // keep the map honest for repeat rows
            }

            $by_method[$how]++;
            $matched++;
        }

        // Update counted_items count in header
        $this->db->where('id', $stock_take_id)
                 ->update('stock_takes', ['counted_items' => $counted_before]);

        // Say *why* each unmatched code missed, so the report is actionable
        // instead of just a list of codes to re-check by hand.
        $this->explain_unmatched($issue_rows, $loose_ambiguous);

        // Items the file never mentions — they stay uncounted, and get zeroed
        // if "zero uncounted" is used before finalize. Worth stating up front.
        $file_keys = [];
        foreach ($rows as $row) {
            $k = strtoupper(trim((string)$row['item_code']));
            if ($k !== '') $file_keys[$k] = true;
        }
        $missing_from_file = 0;
        foreach ($items as $item) {
            $c = strtoupper(trim((string)$item->item_code));
            if ($c === '' || !isset($file_keys[$c])) $missing_from_file++;
        }

        return [
            'missing_from_file' => $missing_from_file,
            'matched'         => $matched,
            'not_found'       => $not_found,
            'by_method'       => $by_method,
            'not_found_codes' => array_keys($not_found_codes),
            'invalid_qty'     => count($invalid_qty_codes),
            'invalid_codes'   => array_keys($invalid_qty_codes),
            'duplicates'      => count($duplicate_codes),
            'duplicate_codes' => array_keys($duplicate_codes),
            'issue_rows'      => $issue_rows,
        ];
    }

    // ─── Diagnose the rows that failed to match ─────────────────────
    // Looks each unmatched code up in the products master (and barcodes) so
    // the report can name the real cause: the product is inactive, it was
    // created after the take started, the code collides with another item,
    // or it genuinely doesn't exist anywhere.
    private function explain_unmatched(&$issue_rows, $loose_ambiguous)
    {
        $codes = [];
        foreach ($issue_rows as $r) {
            if ($r['issue'] === 'Item code not found') $codes[strtoupper($r['item_code'])] = true;
        }
        if (!$codes) return;

        $codes = array_keys($codes);
        $loose_wanted = [];
        foreach ($codes as $c) {
            $l = ltrim($c, '0');
            if ($l !== '') $loose_wanted[$l] = true;
        }

        // Pull every master product that could relate to these codes
        $p_by_code = []; $p_by_loose = []; $p_by_barcode = [];

        // where_in() would quote the TRIM() expression as a column name, so the
        // loose half of this lookup is built as an escaped raw clause.
        $esc = function ($vals) { return implode(',', array_map([$this->db, 'escape'], $vals)); };
        $sql = "SELECT id, name, item_code, status FROM products
                 WHERE UPPER(TRIM(item_code)) IN (" . $esc($codes) . ")";
        if ($loose_wanted) {
            $sql .= " OR TRIM(LEADING '0' FROM UPPER(TRIM(item_code))) IN ("
                  . $esc(array_keys($loose_wanted)) . ")";
        }
        $prods = $this->db->query($sql)->result();

        foreach ($prods as $p) {
            $c = strtoupper(trim((string)$p->item_code));
            if ($c === '') continue;
            $p_by_code[$c] = $p;
            $l = ltrim($c, '0');
            if ($l !== '') $p_by_loose[$l][] = $p;
        }

        if ($codes) {
            $bcs = $this->db
                ->select('pb.barcode, p.id, p.name, p.item_code, p.status')
                ->from('product_barcodes pb')
                ->join('products p', 'p.id = pb.product_id')
                ->where_in('pb.barcode', $codes)
                ->get()->result();
            foreach ($bcs as $b) {
                $p_by_barcode[strtoupper(trim((string)$b->barcode))] = $b;
            }
        }

        foreach ($issue_rows as &$r) {
            if ($r['issue'] !== 'Item code not found') continue;

            $key   = strtoupper(trim((string)$r['item_code']));
            $loose = ltrim($key, '0');
            $hit   = null;

            if (isset($p_by_code[$key]))            $hit = $p_by_code[$key];
            elseif (isset($p_by_barcode[$key]))     $hit = $p_by_barcode[$key];
            elseif ($loose !== '' && !empty($p_by_loose[$loose]) && count($p_by_loose[$loose]) === 1) {
                $hit = $p_by_loose[$loose][0];
            } elseif ($loose !== '' && !empty($p_by_loose[$loose])) {
                $names = [];
                foreach ($p_by_loose[$loose] as $p) $names[] = $p->item_code . ' — ' . $p->name;
                $r['detail'] = 'Ambiguous: ' . count($p_by_loose[$loose])
                             . ' products share this code once leading zeros are ignored ('
                             . implode('; ', array_slice($names, 0, 4))
                             . '). Not matched on purpose — fix the item code in the file or in the product master.';
                continue;
            }

            if (!$hit) {
                $r['detail'] = 'No product in the master has this item code or barcode. '
                             . 'Either the code is mistyped/reformatted in Excel, or the product was never created.';
                continue;
            }

            $r['product_id']   = $hit->id;
            $r['product_name'] = $hit->name;

            if ((int)$hit->status !== 1) {
                $r['detail'] = 'Product exists but is INACTIVE (status = 0), so it was never added to this stock take. '
                             . 'Re-activate it and start a new take, or count it manually.';
            } elseif (isset($loose_ambiguous[$loose])) {
                $r['detail'] = 'Product is active, but its code collides with another item in this take once '
                             . 'leading zeros are ignored — matching was skipped to avoid writing the wrong count.';
            } else {
                $r['detail'] = 'Product is active in the master but is NOT part of this stock take — '
                             . 'it was most likely created after the take was started. Start a fresh take to include it.';
            }
        }
        unset($r);
    }

    // ─── Zero all uncounted items (before finalize if user wants) ───
    public function zero_uncounted($stock_take_id)
    {
        $this->db->where('stock_take_id', $stock_take_id)
                 ->where('counted_qty IS NULL', null, false)
                 ->update('stock_take_items', [
                     'counted_qty' => 0,
                     'saved_at'    => date('Y-m-d H:i:s'),
                 ]);

        $cnt = $this->db->select('COUNT(*) AS c')
                        ->where('stock_take_id', $stock_take_id)
                        ->where('counted_qty IS NOT NULL', null, false)
                        ->get('stock_take_items')->row();
        $this->db->where('id', $stock_take_id)
                 ->update('stock_takes', ['counted_items' => (int)($cnt ? $cnt->c : 0)]);
        return true;
    }

    // ─── Lookup product for scan mode ─────────────────────────────
    public function lookup_product($q, $stock_take_id)
    {
        $q      = trim($q);
        $q_esc  = $this->db->escape($q);   // 'value' — for exact match
        $like   = $this->db->escape('%' . $this->db->escape_like_str($q) . '%'); // '%val%'

        $base   = "SELECT p.id, p.name, p.item_code,
                          COALESCE(p.mrp,0)           AS mrp,
                          COALESCE(p.selling_price,0)  AS selling_price,
                          COALESCE(p.unit,'PCS')        AS unit,
                          COALESCE(p.stock_qty,0)       AS stock_qty";

        // 1. Exact item_code
        $r = $this->db->query("$base FROM products p WHERE p.status = 1 AND p.item_code = $q_esc LIMIT 1");
        $results = ($r && $r->num_rows() > 0) ? $r->result() : [];

        // 2. Exact barcode
        if (empty($results)) {
            $r = $this->db->query(
                "$base FROM products p
                 INNER JOIN product_barcodes pb ON pb.product_id = p.id
                 WHERE p.status = 1 AND pb.barcode = $q_esc LIMIT 1"
            );
            $results = ($r && $r->num_rows() > 0) ? $r->result() : [];
        }

        // 3. Name LIKE — use escape() directly, not ? bind, to avoid CI3 LIKE bind issues
        if (empty($results)) {
            $this->load->helper('product_search');
            $ws = product_words_sql($q ?? '', ['p.name', 'p.item_code']);
            $r = $this->db->query(
                "$base FROM products p WHERE p.status = 1 AND " . ($ws ?: "p.name LIKE $like") . " LIMIT 8"
            );
            $results = ($r && $r->num_rows() > 0) ? $r->result() : [];
        }

        // Attach already-counted qty and location system_qty for this take
        foreach ($results as &$row) {
            $sti = $this->db->get_where('stock_take_items', [
                'stock_take_id' => $stock_take_id,
                'product_id'    => $row->id,
            ])->row();
            $row->counted_qty = $sti ? $sti->counted_qty : null;
            $row->system_qty  = $sti ? (float)$sti->system_qty : (float)$row->stock_qty;
        }
        return $results;
    }

    // ─── Get any in_progress take for a location ──────────────────
    public function get_pending_for_location($location_id)
    {
        return $this->db->get_where('stock_takes', [
            'location_id' => $location_id,
            'status'      => 'in_progress',
        ])->row();
    }

    // ─── Finalize stock take ──────────────────────────────────────
    public function finalize($id, $user_id, $user_name, $adjust_stock, $location_model)
    {
        $take = $this->get_by_id($id);
        if (!$take || $take->status !== 'in_progress') {
            return false;
        }

        if ($adjust_stock) {
            $items = $this->get_items($id);
            foreach ($items as $item) {
                if ($item->counted_qty !== null) {
                    $before = $location_model->get_stock($item->product_id, $take->location_id);
                    $location_model->set_stock($item->product_id, $take->location_id, (float)$item->counted_qty);
                    // Sync global stock
                    $this->_sync_global_stock($item->product_id);
                    // Log movement
                    $variance_qty = (float)$item->counted_qty - (float)$item->system_qty;
                    $location_model->log_movement([
                        'product_id'     => $item->product_id,
                        'product_name'   => $item->product_name,
                        'location_id'    => $take->location_id,
                        'location_name'  => $take->location_name,
                        'movement_type'  => $variance_qty >= 0 ? 'adjustment_in' : 'adjustment_out',
                        'quantity'       => abs($variance_qty),
                        'balance_before' => $before,
                        'balance_after'  => (float)$item->counted_qty,
                        'reference_type' => 'stock_take',
                        'reference_id'   => $id,
                        'reference_no'   => $take->take_no,
                        'user_id'        => $user_id,
                        'user_name'      => $user_name,
                        'notes'          => 'Stock Take Adjustment: ' . $take->take_no,
                    ]);
                }
            }
        }

        $this->db->where('id', $id)->update('stock_takes', [
            'status'              => 'completed',
            'finalized_by'        => $user_id,
            'finalized_by_name'   => $user_name,
            'finalized_at'        => date('Y-m-d H:i:s'),
            'adjust_stock'        => $adjust_stock ? 1 : 0,
        ]);

        return true;
    }

    // ─── Cancel ───────────────────────────────────────────────────
    public function cancel($id)
    {
        return $this->db->where('id', $id)->update('stock_takes', ['status' => 'cancelled']);
    }

    // ─── Sync global stock_qty (sum across locations) ─────────────
    private function _sync_global_stock($product_id)
    {
        // products.stock_qty is the DISPLAY total: each location's balance capped
        // at 0, then summed — a location that sold past zero shows 0, and its
        // shortfall never hides another location's real stock. The true (possibly
        // negative) balance stays in product_location_stock + stock_movement_log.
        $row = $this->db->select('SUM(GREATEST(stock_qty, 0)) as total', FALSE)
                        ->where('product_id', $product_id)
                        ->get('product_location_stock')->row();
        $total = $row ? (float)$row->total : 0;
        $this->db->where('id', $product_id)->update('products', ['stock_qty' => $total]);
    }
}
