<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Address + PIN rules shared by wholesale invoice creation, e-invoice (IRN)
 * and e-way bill generation, so every step validates the same way.
 *
 * - Dispatch-from (seller address) = the invoice's LOCATION (locations master),
 *   falling back to the company address. Its PIN is compulsory and must belong
 *   to the seller GSTIN's state.
 * - Bill-to = customer master; PIN compulsory, must match the buyer GSTIN state.
 * - Ship-to = optional per-invoice delivery address (invoices.ship_*); when set,
 *   its PIN is compulsory and must match the chosen ship-to state. The place of
 *   supply (and so IGST vs CGST+SGST) follows the ship-to state.
 */
class GstAddress {

    const STATES = [
        '01' => 'Jammu and Kashmir', '02' => 'Himachal Pradesh', '03' => 'Punjab', '04' => 'Chandigarh',
        '05' => 'Uttarakhand', '06' => 'Haryana', '07' => 'Delhi', '08' => 'Rajasthan', '09' => 'Uttar Pradesh',
        '10' => 'Bihar', '11' => 'Sikkim', '12' => 'Arunachal Pradesh', '13' => 'Nagaland', '14' => 'Manipur',
        '15' => 'Mizoram', '16' => 'Tripura', '17' => 'Meghalaya', '18' => 'Assam', '19' => 'West Bengal',
        '20' => 'Jharkhand', '21' => 'Odisha', '22' => 'Chhattisgarh', '23' => 'Madhya Pradesh', '24' => 'Gujarat',
        '26' => 'Dadra and Nagar Haveli and Daman and Diu', '27' => 'Maharashtra', '29' => 'Karnataka', '30' => 'Goa',
        '31' => 'Lakshadweep', '32' => 'Kerala', '33' => 'Tamil Nadu', '34' => 'Puducherry',
        '35' => 'Andaman and Nicobar Islands', '36' => 'Telangana', '37' => 'Andhra Pradesh', '38' => 'Ladakh',
    ];

    // First two digits of a PIN code that belong to each state (India Post zones).
    const PIN_PREFIX = [
        '01' => ['18', '19'], '02' => ['17'], '03' => ['14', '15', '16'], '04' => ['16'], '05' => ['24', '26'],
        '06' => ['12', '13'], '07' => ['11'], '08' => ['30', '31', '32', '33', '34'],
        '09' => ['20', '21', '22', '23', '24', '25', '26', '27', '28'], '10' => ['80', '81', '82', '84', '85'],
        '11' => ['73'], '12' => ['79'], '13' => ['79'], '14' => ['79'], '15' => ['79'], '16' => ['79'], '17' => ['79'],
        '18' => ['78'], '19' => ['70', '71', '72', '73', '74'], '20' => ['81', '82', '83', '84', '85'],
        '21' => ['75', '76', '77'], '22' => ['49'], '23' => ['45', '46', '47', '48'], '24' => ['36', '37', '38', '39'],
        '26' => ['36', '39'], '27' => ['40', '41', '42', '43', '44'], '29' => ['56', '57', '58', '59'], '30' => ['40'],
        '31' => ['68'], '32' => ['67', '68', '69'], '33' => ['60', '61', '62', '63', '64'], '34' => ['53', '60', '67'],
        '35' => ['74'], '36' => ['50'], '37' => ['50', '51', '52', '53'], '38' => ['19'],
    ];

    public static function state_name($code) {
        return self::STATES[str_pad((string)$code, 2, '0', STR_PAD_LEFT)] ?? '';
    }

    // GST state code from a state name ("HARYANA", "haryana ", "Delhi") or '' if unknown
    public static function state_code_from_name($name) {
        $n = strtolower(preg_replace('/[^a-z]/i', '', (string)$name));
        if ($n === '') return '';
        foreach (self::STATES as $code => $label) {
            if (strtolower(preg_replace('/[^a-z]/i', '', $label)) === $n) return $code;
        }
        $alias = ['newdelhi' => '07', 'nctofdelhi' => '07', 'orissa' => '21', 'pondicherry' => '34', 'jammukashmir' => '01',
                  'uttaranchal' => '05', 'up' => '09', 'mp' => '23', 'damananddiu' => '26', 'dadranagarhaveli' => '26'];
        return $alias[$n] ?? '';
    }

    public static function state_from_gstin($gstin) {
        $gstin = strtoupper(trim((string)$gstin));
        return (strlen($gstin) === 15 && isset(self::STATES[substr($gstin, 0, 2)])) ? substr($gstin, 0, 2) : '';
    }

    /**
     * NULL if the PIN is fine for the state, else a plain-English problem.
     * $what names the field for the message, e.g. "Location 'WH' PIN code".
     */
    public static function pin_error($pin, $state_code, $what) {
        $pin = trim((string)$pin);
        if ($pin === '') return "{$what} is missing — it is compulsory for E-Invoice / E-Way Bill.";
        if (!preg_match('/^[1-9][0-9]{5}$/', $pin)) return "{$what} '{$pin}' is not a valid 6-digit PIN code.";
        $allowed = self::PIN_PREFIX[$state_code] ?? null;
        if ($allowed && !in_array(substr($pin, 0, 2), $allowed, true)) {
            return "{$what} {$pin} does not belong to " . self::state_name($state_code)
                 . " (PINs there start with " . implode(' / ', $allowed) . ").";
        }
        return NULL;
    }

    /**
     * Resolve and validate every address an invoice needs.
     * Returns ['dispatch' => [...], 'buyer' => [...], 'ship' => [...]|null,
     *          'seller_state' => '06', 'pos' => '07', 'errors' => [...]]
     * $invoice may be the saved row or an array/object of posted values with
     * location_id + ship_* fields.
     */
    public static function resolve($invoice, $customer, $company, $db) {
        $inv = (object)$invoice;
        $errors = [];

        $seller_state = self::state_from_gstin($company['company_gstin'] ?? '');
        if ($seller_state === '') $errors[] = 'Company GSTIN (Settings) is missing or invalid.';

        // Dispatch from: the invoice location, else company address
        $loc = !empty($inv->location_id) ? $db->where('id', (int)$inv->location_id)->get('locations')->row() : null;
        $loc_label = $loc ? "Location '" . $loc->name . "'" : 'Company';
        // A location's own PIN / address / city are compulsory — no silent fallback
        // to the company address, which may be a different place (or state).
        $dispatch = $loc ? [
            'name'  => $company['company_name'] ?? '',
            'addr1' => trim((string)$loc->address),
            'place' => trim((string)$loc->city),
            'pin'   => trim((string)$loc->pincode),
            'state' => $seller_state,
            'source'=> $loc_label,
        ] : [
            'name'  => $company['company_name'] ?? '',
            'addr1' => trim((string)($company['company_address'] ?? '')),
            'place' => trim((string)($company['company_city'] ?? '')),
            'pin'   => trim((string)($company['company_pincode'] ?? '')),
            'state' => $seller_state,
            'source'=> $loc_label,
        ];
        $pin_from = $loc ? "{$loc_label} PIN code (Locations master)" : 'Company PIN code (Settings → E-Invoice API → Company Details)';
        if ($seller_state && ($e = self::pin_error($dispatch['pin'], $seller_state, $pin_from))) $errors[] = $e;
        $where = $loc ? ' (Locations master)' : ' (Settings)';
        if (strlen($dispatch['addr1']) < 3) $errors[] = "{$loc_label} address is missing{$where} — needed as the dispatch address.";
        if ($dispatch['place'] === '') $errors[] = "{$loc_label} city is missing{$where} — needed as the dispatch place.";

        // Bill to: customer master
        $buyer_state = self::state_from_gstin($customer->gst_number ?? '');
        if ($buyer_state === '') $buyer_state = self::state_code_from_name($customer->state ?? '');
        $buyer = [
            'gstin' => strtoupper(trim((string)($customer->gst_number ?? ''))),
            'name'  => $customer->name ?? '',
            'addr1' => trim((string)($customer->address ?? '')),
            'place' => trim((string)($customer->city ?? '')),
            'pin'   => trim((string)($customer->pincode ?? '')),
            'state' => $buyer_state,
        ];
        $cname = "Customer '" . ($customer->name ?? '') . "'";
        if ($buyer_state === '') $errors[] = "{$cname} has no valid GSTIN / state.";
        elseif ($e = self::pin_error($buyer['pin'], $buyer_state, "{$cname} PIN code (Customers master)")) $errors[] = $e;
        if (strlen($buyer['addr1']) < 3) $errors[] = "{$cname} address is missing (Customers master).";
        if ($buyer['place'] === '') $errors[] = "{$cname} city is missing (Customers master).";

        // Ship to: optional delivery address on the invoice
        $ship = null;
        if (!empty($inv->ship_to_different)) {
            $ship = [
                'gstin' => strtoupper(trim((string)($inv->ship_gstin ?? ''))),
                'name'  => trim((string)($inv->ship_name ?? '')),
                'addr1' => trim((string)($inv->ship_address ?? '')),
                'place' => trim((string)($inv->ship_place ?? '')),
                'pin'   => trim((string)($inv->ship_pincode ?? '')),
                'state' => str_pad((string)($inv->ship_state_code ?? ''), 2, '0', STR_PAD_LEFT),
            ];
            if (!isset(self::STATES[$ship['state']])) $errors[] = 'Ship-to state is not selected.';
            elseif ($e = self::pin_error($ship['pin'], $ship['state'], 'Ship-to PIN code')) $errors[] = $e;
            if ($ship['name'] === '')        $errors[] = 'Ship-to name is missing.';
            if (strlen($ship['addr1']) < 3)  $errors[] = 'Ship-to address is missing.';
            if ($ship['place'] === '')       $errors[] = 'Ship-to city / place is missing.';
            if ($ship['gstin'] !== '' && !self::state_from_gstin($ship['gstin'])) $errors[] = "Ship-to GSTIN '{$ship['gstin']}' is not a valid 15-character GSTIN.";
            if ($ship['gstin'] !== '' && self::state_from_gstin($ship['gstin']) && self::state_from_gstin($ship['gstin']) !== $ship['state']) {
                $errors[] = 'Ship-to GSTIN belongs to ' . self::state_name(substr($ship['gstin'], 0, 2)) . ' but the ship-to state is ' . self::state_name($ship['state']) . '.';
            }
        }

        return [
            'dispatch'     => $dispatch,
            'buyer'        => $buyer,
            'ship'         => $ship,
            'seller_state' => $seller_state,
            'pos'          => $ship ? $ship['state'] : $buyer_state,   // place of supply
            'errors'       => $errors,
        ];
    }
}
