<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Billing-style product search: when the query has 2+ words, every word must
// appear somewhere (any order) in one of the given columns — "maggi noodle"
// finds "MAGGI AATA NOODLE". Returns a raw SQL condition, or NULL for a
// single-word query so callers keep their existing single-word matching.
if (!function_exists('product_words_sql')) {
    function product_words_sql($q, array $cols)
    {
        // '?' is dropped: some callers splice this into queries that use ? bindings
        $words = preg_split('/\s+/', trim(str_replace('?', ' ', (string)$q)), -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) < 2) return NULL;
        $db  = get_instance()->db;
        $and = [];
        foreach ($words as $w) {
            $like = "'%" . $db->escape_like_str($w) . "%' ESCAPE '!'";
            $or = [];
            foreach ($cols as $c) $or[] = "{$c} LIKE {$like}";
            $and[] = '(' . implode(' OR ', $or) . ')';
        }
        return '(' . implode(' AND ', $and) . ')';
    }
}
