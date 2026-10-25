<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Boxed warranty summary printed at the foot of the bill, so the receipt itself
 * is the customer's warranty card.
 *
 * Expects $items plus $serials / $item_warranty from the controller. Renders
 * NOTHING when the bill carries no warranty, so ordinary bills are unchanged
 * and no paper is wasted.
 *
 * $wc_narrow = true for the 80mm thermal layout, false for A4.
 */
$wc_narrow = $wc_narrow ?? true;
$wc_lines  = [];

foreach ($items as $wc_it) {
    $rows = $serials['by_item'][$wc_it->id]
         ?? ($serials['by_product'][$wc_it->product_id] ?? []);

    if ($rows) {
        foreach ($rows as $r) {
            if (empty($r->warranty_expiry_date)) continue;   // serial without warranty
            $wc_lines[] = [
                'name'   => $wc_it->product_name,
                'serial' => $r->serial_no,
                'days'   => (int)$r->warranty_days,
                'expiry' => $r->warranty_expiry_date,
            ];
        }
    } elseif (!empty($item_warranty[$wc_it->product_id])) {
        $w = $item_warranty[$wc_it->product_id];
        $wc_lines[] = [
            'name'   => $wc_it->product_name,
            'serial' => null,
            'days'   => (int)$w->warranty_days,
            'expiry' => $w->warranty_expiry_date,
        ];
    }
}

if (empty($wc_lines)) return;   // nothing under warranty on this bill

$wc_fs    = $wc_narrow ? '8px'   : '10px';
$wc_head  = $wc_narrow ? '9px'   : '11.5px';
$wc_pad   = $wc_narrow ? '4px'   : '8px';
?>
<div style="border: 1.5px solid #000; padding: <?= $wc_pad ?>; margin: 8px 0 6px;
            font-size: <?= $wc_fs ?>; line-height: 1.4; page-break-inside: avoid;">

    <div style="text-align: center; font-weight: bold; font-size: <?= $wc_head ?>;
                letter-spacing: .08em; border-bottom: 1px dashed #000;
                padding-bottom: 3px; margin-bottom: 4px;">
        WARRANTY CARD
    </div>

    <?php foreach ($wc_lines as $wc): ?>
    <div style="margin-bottom: 4px;<?= count($wc_lines) > 1 ? 'border-bottom:1px dotted #bbb;padding-bottom:3px;' : '' ?>">
        <div style="font-weight: bold;"><?= htmlspecialchars(strtoupper($wc['name'])) ?></div>
        <?php if ($wc['serial']): ?>
        <div>Serial No: <strong style="letter-spacing:.04em;"><?= htmlspecialchars($wc['serial']) ?></strong></div>
        <?php endif; ?>
        <div>
            Warranty: <strong><?= $wc['days'] ?> Days</strong>
            &nbsp;&middot;&nbsp; Valid till: <strong><?= date('d-M-Y', strtotime($wc['expiry'])) ?></strong>
        </div>
    </div>
    <?php endforeach; ?>

    <div style="border-top: 1px dashed #000; padding-top: 3px; margin-top: 3px;
                font-size: <?= $wc_narrow ? '7.5px' : '9px' ?>; color: #333;">
        Please retain this bill. It must be produced for any warranty claim.
        Warranty is void without the original bill and matching serial number.
    </div>
</div>
