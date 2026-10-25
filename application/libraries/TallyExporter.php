<?php
// ============================================================
// FILE: application/libraries/TallyExporter.php
// Builds Tally-native XML ("Item Invoice" style) for direct import via
// Gateway of Tally -> Import -> Data. No SDK/library exists for this —
// it's plain XML built by hand against Tally's documented schema, matching
// this codebase's style elsewhere (WhatsAppClient, AiCopywriter: no
// external dependency, just structured output).
//
// Ledger/voucher design (confirmed with the user before building):
//   - Item-wise invoices: every product becomes a Tally STOCKITEM, so
//     Tally's own stock/inventory reports stay usable after import.
//   - B2C/walk-in customers (no GSTIN) all post against one shared
//     "Cash Sales" party ledger. B2B customers (have a GSTIN) each get
//     their own individual ledger. Every supplier gets its own ledger.
//   - GST is posted as three flat ledgers — CGST / SGST / IGST — not
//     split by rate slab. Amounts are pre-computed from this system's
//     own stored tax figures and posted directly; Tally's own GST-rate
//     engine is not invoked, so these ledgers don't need GST
//     classification configured on the Tally side to import cleanly.
//   - Sales Returns import as Credit Note vouchers, Purchase Returns as
//     Debit Note vouchers — Tally's standard voucher types for reversals,
//     posted against the same Sales/Purchase ledgers as the originals
//     (not separate "returns" ledgers), so Tally nets them naturally.
//
// Tally debit/credit convention used throughout: AMOUNT is positive for a
// debit-natured entry and negative for a credit-natured entry, and
// ISDEEMEDPOSITIVE mirrors that ("Yes" for debit, "No" for credit) — this
// is Tally's own documented double-entry convention, not a choice made
// here. Getting a sign wrong doesn't fail the import silently; it posts a
// backwards entry into the user's real books, so every voucher builder
// below states which side each line is on and why.
// ============================================================
defined('BASEPATH') OR exit('No direct script access allowed');

class TallyExporter {

    private $units_seen = [];
    private $stock_items_seen = [];
    private $ledgers_seen = [];

    // ─── Envelope ───────────────────────────────────────────────────────

    // $report_name mirrors Tally's own two import modes: "All Masters" for a
    // masters-only file (Gateway of Tally -> Import -> Masters) and "Vouchers"
    // for a transactions file (-> Import -> Transactions/Vouchers). A
    // transactions file may still carry the masters its vouchers reference —
    // Tally accepts masters inside a Vouchers request and creates them on the
    // fly — so both blocks stay available in either mode; empty blocks are
    // dropped rather than emitted as an empty TALLYMESSAGE.
    public function wrap_envelope($company_name, $masters_xml, $vouchers_xml, $report_name = 'All Masters') {
        $company_esc = $this->esc($company_name);
        $body = '';
        if ($masters_xml !== '')  $body .= '<TALLYMESSAGE xmlns:UDF="TallyUDF">' . $masters_xml . '</TALLYMESSAGE>';
        if ($vouchers_xml !== '') $body .= '<TALLYMESSAGE xmlns:UDF="TallyUDF">' . $vouchers_xml . '</TALLYMESSAGE>';
        return '<ENVELOPE>'
            . '<HEADER><TALLYREQUEST>Import Data</TALLYREQUEST></HEADER>'
            . '<BODY><IMPORTDATA>'
            . '<REQUESTDESC><REPORTNAME>' . $this->esc($report_name) . '</REPORTNAME>'
            . '<STATICVARIABLES><SVCURRENTCOMPANY>' . $company_esc . '</SVCURRENTCOMPANY></STATICVARIABLES>'
            . '</REQUESTDESC>'
            . '<REQUESTDATA>' . $body . '</REQUESTDATA>'
            . '</IMPORTDATA></BODY></ENVELOPE>';
    }

    // ─── Masters ────────────────────────────────────────────────────────

    // Party ledger (supplier, B2B customer, or the shared Cash Sales
    // ledger). $parent is "Sundry Creditors" or "Sundry Debtors".
    public function ledger_party($name, $parent, $opening_balance, $gstin, $address, $state, $pincode) {
        if (isset($this->ledgers_seen[$name])) return '';
        $this->ledgers_seen[$name] = true;

        $addr_lines = '';
        foreach (array_filter(array_map('trim', explode("\n", (string)$address))) as $line) {
            $addr_lines .= '<ADDRESS>' . $this->esc($line) . '</ADDRESS>';
        }

        // Opening balance sign follows the same debit/credit convention as
        // everything else: a Sundry Debtor who owes us is a debit (positive);
        // a Sundry Creditor we owe is a credit (negative).
        $ob = (float)$opening_balance;
        if ($parent === 'Sundry Creditors') $ob = -abs($ob);
        else $ob = abs($ob);

        return '<LEDGER NAME="' . $this->esc($name) . '" ACTION="Create">'
            . '<PARENT>' . $this->esc($parent) . '</PARENT>'
            . $addr_lines
            . '<STATENAME>' . $this->esc($state ?: 'Unknown') . '</STATENAME>'
            . '<PINCODE>' . $this->esc($pincode) . '</PINCODE>'
            . ($gstin ? '<PARTYGSTIN>' . $this->esc($gstin) . '</PARTYGSTIN><GSTREGISTRATIONTYPE>Regular</GSTREGISTRATIONTYPE>' : '<GSTREGISTRATIONTYPE>Unregistered</GSTREGISTRATIONTYPE>')
            . '<ISBILLWISEON>Yes</ISBILLWISEON>'
            . '<OPENINGBALANCE>' . number_format($ob, 2, '.', '') . '</OPENINGBALANCE>'
            . '</LEDGER>';
    }

    // Flat, non-rate-classified GST ledger under Duties & Taxes.
    public function ledger_simple($name, $parent) {
        if (isset($this->ledgers_seen[$name])) return '';
        $this->ledgers_seen[$name] = true;
        return '<LEDGER NAME="' . $this->esc($name) . '" ACTION="Create">'
            . '<PARENT>' . $this->esc($parent) . '</PARENT>'
            . '<ISBILLWISEON>No</ISBILLWISEON>'
            . '<OPENINGBALANCE>0.00</OPENINGBALANCE>'
            . '</LEDGER>';
    }

    public function unit_master($symbol) {
        $symbol = $symbol ?: 'PCS';
        if (isset($this->units_seen[$symbol])) return '';
        $this->units_seen[$symbol] = true;
        return '<UNIT NAME="' . $this->esc($symbol) . '" ACTION="Create">'
            . '<ISSIMPLEUNIT>Yes</ISSIMPLEUNIT>'
            . '<SYMBOL>' . $this->esc($symbol) . '</SYMBOL>'
            . '<FORMALNAME>' . $this->esc($symbol) . '</FORMALNAME>'
            . '<DECIMALPLACES>2</DECIMALPLACES>'
            . '</UNIT>';
    }

    // $opening_qty/$opening_rate are only passed by the masters-only export
    // when the user explicitly asks for opening stock; voucher exports always
    // leave them null so an item created on the fly starts at zero and the
    // vouchers themselves build the balance.
    public function stock_item_master($name, $unit, $hsn_code, $opening_qty = null, $opening_rate = null) {
        $unit = $unit ?: 'PCS';
        $key = $name . '|' . $unit;
        if (isset($this->stock_items_seen[$key])) return '';
        $this->stock_items_seen[$key] = true;

        $opening = '';
        if ($opening_qty !== null && (float)$opening_qty != 0.0) {
            $qty  = (float)$opening_qty;
            $rate = (float)$opening_rate;
            $opening = '<OPENINGBALANCE>' . number_format($qty, 2, '.', '') . ' ' . $this->esc($unit) . '</OPENINGBALANCE>'
                . '<OPENINGRATE>' . number_format($rate, 2, '.', '') . '/' . $this->esc($unit) . '</OPENINGRATE>'
                . '<OPENINGVALUE>' . number_format($qty * $rate, 2, '.', '') . '</OPENINGVALUE>';
        }

        return '<STOCKITEM NAME="' . $this->esc($name) . '" ACTION="Create">'
            . '<PARENT>Primary</PARENT>'
            . '<BASEUNITS>' . $this->esc($unit) . '</BASEUNITS>'
            . ($hsn_code ? '<GSTHSNNAME>' . $this->esc($hsn_code) . '</GSTHSNNAME><HSNCODE>' . $this->esc($hsn_code) . '</HSNCODE>' : '')
            . $opening
            . '</STOCKITEM>';
    }

    // ─── Vouchers ───────────────────────────────────────────────────────
    // $items: array of ['stock_item'=>, 'unit'=>, 'qty'=>, 'rate'=>,
    //   'taxable'=>, 'cgst'=>, 'sgst'=>, 'igst'=>]
    // $is_debit_side: true for Sales/Debit Note (party debited... wait see
    // per-method doc below — kept explicit per call site, not inferred here).

    // SALES voucher. Party (customer) is debited (they owe us more);
    // Sales + GST ledgers are credited (revenue/liability increase).
    public function voucher_sales($voucher_no, $date_ymd, $party_ledger, array $items, $total_amount, $narration = '') {
        return $this->_voucher('Sales', $voucher_no, $date_ymd, $party_ledger, $items, $total_amount, $narration,
            /* party debit */ true, /* inventory positive (out) */ false);
    }

    // CREDIT NOTE (sales return). Reverses a Sales voucher: party is
    // credited (they owe us less), Sales + GST ledgers are debited.
    public function voucher_credit_note($voucher_no, $date_ymd, $party_ledger, array $items, $total_amount, $narration = '') {
        return $this->_voucher('Credit Note', $voucher_no, $date_ymd, $party_ledger, $items, $total_amount, $narration,
            /* party debit */ false, /* inventory positive (in) */ true);
    }

    // PURCHASE voucher. Party (supplier) is credited (we owe them more);
    // Purchase + GST(input) ledgers are debited (expense/asset increase).
    public function voucher_purchase($voucher_no, $date_ymd, $party_ledger, array $items, $total_amount, $narration = '') {
        return $this->_voucher('Purchase', $voucher_no, $date_ymd, $party_ledger, $items, $total_amount, $narration,
            /* party debit */ false, /* inventory positive (in) */ true);
    }

    // DEBIT NOTE (purchase return). Reverses a Purchase voucher: party is
    // debited (we owe them less), Purchase + GST ledgers are credited.
    public function voucher_debit_note($voucher_no, $date_ymd, $party_ledger, array $items, $total_amount, $narration = '') {
        return $this->_voucher('Debit Note', $voucher_no, $date_ymd, $party_ledger, $items, $total_amount, $narration,
            /* party debit */ true, /* inventory positive (out) */ false);
    }

    private function _voucher($vch_type, $voucher_no, $date_ymd, $party_ledger, array $items, $total_amount, $narration, $party_is_debit, $inventory_is_positive) {
        $sales_purchase_ledger = in_array($vch_type, ['Sales', 'Credit Note'], true) ? 'Sales' : 'Purchase';

        // Line items: an inventory entry is a credit (negative) whenever it
        // represents stock going OUT (Sales, Debit Note) and a debit
        // (positive) whenever stock comes IN (Purchase, Credit Note) — the
        // opposite sign from the accounting allocations it carries, because
        // physically the item is leaving/entering regardless of who's
        // financially debited. $inventory_is_positive already encodes this.
        $inv_deemed = $inventory_is_positive ? 'Yes' : 'No';
        $inv_sign   = $inventory_is_positive ? 1 : -1;

        // The Sales/Purchase + GST ledger allocations move the OPPOSITE way
        // from the party ledger (that's just double-entry): if the party is
        // debited, everything else on the voucher is credited, and vice versa.
        $other_deemed = $party_is_debit ? 'No' : 'Yes';
        $other_sign   = $party_is_debit ? -1 : 1;

        $inventory_xml = '';
        $taxable_total = 0.0; $cgst_total = 0.0; $sgst_total = 0.0; $igst_total = 0.0;

        foreach ($items as $it) {
            $qty = (float)$it['qty'];
            $rate = (float)$it['rate'];
            $taxable = (float)$it['taxable'];
            $cgst = (float)($it['cgst'] ?? 0);
            $sgst = (float)($it['sgst'] ?? 0);
            $igst = (float)($it['igst'] ?? 0);
            $taxable_total += $taxable; $cgst_total += $cgst; $sgst_total += $sgst; $igst_total += $igst;

            $unit = $it['unit'] ?: 'PCS';
            $accounting = '<ACCOUNTINGALLOCATIONS.LIST>'
                . '<LEDGERNAME>' . $this->esc($sales_purchase_ledger) . '</LEDGERNAME>'
                . '<ISDEEMEDPOSITIVE>' . $other_deemed . '</ISDEEMEDPOSITIVE>'
                . '<AMOUNT>' . number_format($other_sign * $taxable, 2, '.', '') . '</AMOUNT>'
                . '</ACCOUNTINGALLOCATIONS.LIST>';

            $inventory_xml .= '<ALLINVENTORYENTRIES.LIST>'
                . '<STOCKITEMNAME>' . $this->esc($it['stock_item']) . '</STOCKITEMNAME>'
                . '<ISDEEMEDPOSITIVE>' . $inv_deemed . '</ISDEEMEDPOSITIVE>'
                . '<RATE>' . number_format($rate, 2, '.', '') . '/' . $this->esc($unit) . '</RATE>'
                . '<AMOUNT>' . number_format($inv_sign * $taxable, 2, '.', '') . '</AMOUNT>'
                . '<ACTUALQTY>' . number_format($qty, 2, '.', '') . ' ' . $this->esc($unit) . '</ACTUALQTY>'
                . '<BILLEDQTY>' . number_format($qty, 2, '.', '') . ' ' . $this->esc($unit) . '</BILLEDQTY>'
                . $accounting
                . '</ALLINVENTORYENTRIES.LIST>';
        }

        $ledger_entries = '';
        if (round($cgst_total, 2) != 0.0) {
            $ledger_entries .= '<LEDGERENTRIES.LIST><LEDGERNAME>CGST</LEDGERNAME><ISDEEMEDPOSITIVE>' . $other_deemed . '</ISDEEMEDPOSITIVE><AMOUNT>' . number_format($other_sign * $cgst_total, 2, '.', '') . '</AMOUNT></LEDGERENTRIES.LIST>';
        }
        if (round($sgst_total, 2) != 0.0) {
            $ledger_entries .= '<LEDGERENTRIES.LIST><LEDGERNAME>SGST</LEDGERNAME><ISDEEMEDPOSITIVE>' . $other_deemed . '</ISDEEMEDPOSITIVE><AMOUNT>' . number_format($other_sign * $sgst_total, 2, '.', '') . '</AMOUNT></LEDGERENTRIES.LIST>';
        }
        if (round($igst_total, 2) != 0.0) {
            $ledger_entries .= '<LEDGERENTRIES.LIST><LEDGERNAME>IGST</LEDGERNAME><ISDEEMEDPOSITIVE>' . $other_deemed . '</ISDEEMEDPOSITIVE><AMOUNT>' . number_format($other_sign * $igst_total, 2, '.', '') . '</AMOUNT></LEDGERENTRIES.LIST>';
        }

        $party_deemed = $party_is_debit ? 'Yes' : 'No';
        $party_sign   = $party_is_debit ? 1 : -1;
        $ledger_entries .= '<LEDGERENTRIES.LIST><LEDGERNAME>' . $this->esc($party_ledger) . '</LEDGERNAME>'
            . '<ISDEEMEDPOSITIVE>' . $party_deemed . '</ISDEEMEDPOSITIVE>'
            . '<AMOUNT>' . number_format($party_sign * abs((float)$total_amount), 2, '.', '') . '</AMOUNT>'
            . '</LEDGERENTRIES.LIST>';

        return '<VOUCHER VCHTYPE="' . $this->esc($vch_type) . '" ACTION="Create" OBJVIEW="Invoice Voucher View">'
            . '<DATE>' . $this->esc($date_ymd) . '</DATE>'
            . '<EFFECTIVEDATE>' . $this->esc($date_ymd) . '</EFFECTIVEDATE>'
            . '<VOUCHERTYPENAME>' . $this->esc($vch_type) . '</VOUCHERTYPENAME>'
            . '<VOUCHERNUMBER>' . $this->esc($voucher_no) . '</VOUCHERNUMBER>'
            . '<PARTYLEDGERNAME>' . $this->esc($party_ledger) . '</PARTYLEDGERNAME>'
            . '<PERSISTEDVIEW>Invoice Voucher View</PERSISTEDVIEW>'
            . '<ISINVOICE>Yes</ISINVOICE>'
            . ($narration !== '' ? '<NARRATION>' . $this->esc($narration) . '</NARRATION>' : '')
            . $inventory_xml
            . $ledger_entries
            . '</VOUCHER>';
    }

    public function esc($val) {
        return htmlspecialchars((string)$val, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
