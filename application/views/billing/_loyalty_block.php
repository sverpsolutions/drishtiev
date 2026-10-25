<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Loyalty summary printed on the bill (§23, §25).
 *
 * Expects $invoice. Renders NOTHING when the bill has no loyalty activity, so
 * ordinary bills and non-member customers print exactly as before.
 *
 * $lb_narrow = true for the 80mm thermal layout, false for A4.
 */
$lb_narrow = $lb_narrow ?? true;

$lb_earned   = (float)($invoice->loyalty_points_earned   ?? 0);
$lb_redeemed = (float)($invoice->loyalty_points_redeemed ?? 0);
$lb_disc     = (float)($invoice->loyalty_discount        ?? 0);
$lb_before   = (float)($invoice->loyalty_points_before   ?? 0);
$lb_after    = (float)($invoice->loyalty_points_after    ?? 0);

// Nothing earned, nothing spent, no balance to report — print nothing.
if ($lb_earned <= 0 && $lb_redeemed <= 0 && $lb_after <= 0) return;

$lb_fs   = $lb_narrow ? '8.5px' : '10px';
$lb_head = $lb_narrow ? '9.5px' : '12px';
$lb_pad  = $lb_narrow ? '4px'   : '8px';
$n = function ($v) { return number_format((float)$v, 0); };
?>
<div style="border: 1.5px solid #000; padding: <?= $lb_pad ?>; margin: 8px 0 6px;
            font-size: <?= $lb_fs ?>; line-height: 1.45; page-break-inside: avoid;">

    <div style="text-align: center; font-weight: bold; font-size: <?= $lb_head ?>;
                letter-spacing: .08em; border-bottom: 1px dashed #000;
                padding-bottom: 3px; margin-bottom: 4px;">
        LOYALTY REWARDS
    </div>

    <table style="width:100%; border-collapse: collapse; font-size: <?= $lb_fs ?>;">
        <tr>
            <td style="padding:1px 0;">Previous Points</td>
            <td style="padding:1px 0; text-align:right;"><?= $n($lb_before) ?></td>
        </tr>
        <?php if ($lb_redeemed > 0): ?>
        <tr>
            <td style="padding:1px 0;">Points Redeemed</td>
            <td style="padding:1px 0; text-align:right;">- <?= $n($lb_redeemed) ?></td>
        </tr>
        <tr>
            <td style="padding:1px 0;">Loyalty Discount</td>
            <td style="padding:1px 0; text-align:right;">&#8377; <?= number_format($lb_disc, 2) ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td style="padding:1px 0;"><strong>Points Earned This Bill</strong></td>
            <td style="padding:1px 0; text-align:right;"><strong>+ <?= $n($lb_earned) ?></strong></td>
        </tr>
        <tr>
            <td style="padding:3px 0 1px; border-top:1px dotted #666;"><strong>Current Points Balance</strong></td>
            <td style="padding:3px 0 1px; border-top:1px dotted #666; text-align:right;">
                <strong style="font-size:<?= $lb_narrow ? '10px' : '12px' ?>;"><?= $n($lb_after) ?></strong>
            </td>
        </tr>
    </table>

    <?php
    // How many lines earned nothing — the honest short version of §24 for a
    // narrow bill, instead of a per-item points column nobody can read.
    $lb_zero = 0;
    if (!empty($items)) {
        foreach ($items as $lb_it) {
            if (isset($lb_it->loyalty_points) && (float)$lb_it->loyalty_points <= 0) $lb_zero++;
        }
    }
    if ($lb_zero > 0): ?>
    <div style="font-size: <?= $lb_narrow ? '7.5px' : '9px' ?>; color:#333; margin-top:3px;">
        <?= $lb_zero ?> item(s) on this bill are not eligible for points.
    </div>
    <?php endif; ?>

    <div style="border-top: 1px dashed #000; padding-top: 3px; margin-top: 3px;
                font-size: <?= $lb_narrow ? '7.5px' : '9px' ?>; text-align:center;">
        Thank you for shopping with us!<br>Use your points on your next purchase.
    </div>
</div>
