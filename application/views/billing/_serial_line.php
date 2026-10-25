<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Serial + EV vehicle + Warranty details printed under an invoice item line.
 * Supports:
 *  - EV Scooters: Chassis/VIN, Motor No, Colour, Battery Serial(s), Charger Serial, Warranty terms
 *  - Separate Batteries, Chargers, Motors, Parts: Serial No and specific component warranty
 *  - Standard serialized items
 *
 * $sl_size adjusts for Thermal (e.g. 8.5px) vs A4 layout (e.g. 9.5px).
 */
$sl_size = $sl_size ?? '8.5px';
$sl_fmt  = function ($d) { return $d ? date('d-M-Y', strtotime($d)) : ''; };

$has_ev_fields = !empty($item->chassis_no) || !empty($item->motor_no) || !empty($item->battery_serial) || !empty($item->charger_serial);

$sl_rows = [];
if (!empty($serials['by_item'][$item->id])) {
    $sl_rows = $serials['by_item'][$item->id];
} elseif (!empty($serials['by_product'][$item->product_id])) {
    $sl_rows = $serials['by_product'][$item->product_id];
}
?>

<?php if ($has_ev_fields): ?>
    <div class="ev-item-details" style="font-size: <?= $sl_size ?>; margin-top: 3px; padding: 4px 6px; background: rgba(0,0,0,0.02); border-left: 2px solid #2563eb; border-radius: 2px; line-height: 1.45;">
        <table style="width: 100%; border: none; border-collapse: collapse; font-size: inherit;">
            <?php if (!empty($item->chassis_no)): ?>
            <tr>
                <td style="border:none; padding:1px 4px 1px 0; width:35%; color:#333;"><strong>Chassis / VIN:</strong></td>
                <td style="border:none; padding:1px 0; font-family:monospace; font-weight:bold; color:#111; letter-spacing:0.04em;">
                    <?= htmlspecialchars($item->chassis_no) ?>
                </td>
            </tr>
            <?php endif; ?>

            <?php if (!empty($item->motor_no)): ?>
            <tr>
                <td style="border:none; padding:1px 4px 1px 0; color:#333;"><strong>Motor No:</strong></td>
                <td style="border:none; padding:1px 0; font-family:monospace; font-weight:bold; color:#111;">
                    <?= htmlspecialchars($item->motor_no) ?>
                </td>
            </tr>
            <?php endif; ?>

            <?php if (!empty($item->colour)): ?>
            <tr>
                <td style="border:none; padding:1px 4px 1px 0; color:#333;"><strong>Vehicle Colour:</strong></td>
                <td style="border:none; padding:1px 0; color:#111;"><?= htmlspecialchars($item->colour) ?></td>
            </tr>
            <?php endif; ?>

            <?php if (!empty($item->battery_serial)): ?>
            <tr>
                <td style="border:none; padding:1px 4px 1px 0; color:#333;"><strong>Battery S/N:</strong></td>
                <td style="border:none; padding:1px 0; font-family:monospace; font-weight:bold; color:#111;">
                    <?= htmlspecialchars($item->battery_serial) ?>
                </td>
            </tr>
            <?php endif; ?>

            <?php if (!empty($item->charger_serial)): ?>
            <tr>
                <td style="border:none; padding:1px 4px 1px 0; color:#333;"><strong>Charger S/N:</strong></td>
                <td style="border:none; padding:1px 0; font-family:monospace; font-weight:bold; color:#111;">
                    <?= htmlspecialchars($item->charger_serial) ?>
                </td>
            </tr>
            <?php endif; ?>

            <?php if (!empty($item->warranty_terms)): ?>
            <tr>
                <td style="border:none; padding:1px 4px 1px 0; color:#15803d;"><strong>Warranty:</strong></td>
                <td style="border:none; padding:1px 0; color:#15803d; font-weight:600;">
                    <i class="fas fa-shield-alt"></i> <?= htmlspecialchars($item->warranty_terms) ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>

        <?php if (!empty($sl_rows)): ?>
            <div style="margin-top: 2px; color: #555; font-size: calc(<?= $sl_size ?> - 0.5px);">
                <?php foreach ($sl_rows as $sl): ?>
                    <?php if (!empty($sl->warranty_expiry_date)): ?>
                        <span>• S/N: <strong><?= htmlspecialchars($sl->serial_no) ?></strong> &mdash; Valid till: <strong><?= $sl_fmt($sl->warranty_expiry_date) ?></strong> (<?= (int)$sl->warranty_days ?> Days)</span><br>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php elseif (!empty($sl_rows)): ?>
    <div style="margin-top: 2px; line-height: 1.4;">
        <?php foreach ($sl_rows as $sl): ?>
            <div style="font-size: <?= $sl_size ?>; color: #111; font-weight: bold; letter-spacing: .02em;">
                S/N: <?= htmlspecialchars($sl->serial_no) ?>
            </div>
            <?php if (!empty($sl->warranty_expiry_date)): ?>
            <div style="font-size: <?= $sl_size ?>; color: #15803d; font-weight: 500;">
                Warranty: <?= (int)$sl->warranty_days ?> Days &nbsp;&middot;&nbsp;
                Valid till <strong><?= $sl_fmt($sl->warranty_expiry_date) ?></strong>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

<?php elseif (!empty($item_warranty[$item->product_id])): ?>
    <?php $sl_w = $item_warranty[$item->product_id]; ?>
    <div style="font-size: <?= $sl_size ?>; color: #15803d; font-weight: 500; margin-top: 2px;">
        Warranty: <?= (int)$sl_w->warranty_days ?> Days &nbsp;&middot;&nbsp;
        Valid till <strong><?= $sl_fmt($sl_w->warranty_expiry_date) ?></strong>
    </div>
<?php endif; ?>
