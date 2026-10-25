<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Rack QR Stickers</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: Arial, Helvetica, sans-serif; background: #fff; }
.no-print { text-align: center; padding: 15px; background: #f8f9fa; border-bottom: 1px solid #ddd; }
.no-print button { padding: 8px 24px; font-size: 14px; cursor: pointer; border: 1px solid #333; background: #333; color: #fff; border-radius: 4px; margin: 0 5px; }
.no-print button.outline { background: #fff; color: #333; }

.sticker-grid {
    display: flex; flex-wrap: wrap; justify-content: center;
    padding: 10px; gap: 12px;
}
.sticker {
    width: 60mm; height: 40mm;
    border: 1px dashed #ccc;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 3mm;
    page-break-inside: avoid;
}
.sticker .rack-code {
    font-size: 16px; font-weight: 900;
    letter-spacing: 1px; margin-bottom: 2px;
}
.sticker .rack-name {
    font-size: 9px; color: #555; margin-bottom: 4px;
    text-align: center; max-width: 100%;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.sticker .qr-img { width: 22mm; height: 22mm; }
.sticker .loc-label {
    font-size: 7px; color: #888; margin-top: 2px;
    text-align: center;
}

@media print {
    .no-print { display: none; }
    body { margin: 0; }
    .sticker { border: 1px dashed #ccc; }
}
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()"><i class="fas fa-print"></i> Print Stickers</button>
    <button class="outline" onclick="window.close()">Close</button>
</div>

<div class="sticker-grid">
<?php
$rack_list = isset($racks) ? $racks : [$rack];
foreach ($rack_list as $r):
    $scan_url = $base_url . 'masters/rack_stock/' . $r->id;
    $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($scan_url);
?>
    <div class="sticker">
        <div class="rack-code"><?= htmlspecialchars($r->rack_code) ?></div>
        <div class="rack-name"><?= htmlspecialchars($r->rack_name) ?></div>
        <img class="qr-img" src="<?= $qr_url ?>" alt="QR">
        <?php if (!empty($r->location_name)): ?>
        <div class="loc-label"><?= htmlspecialchars($r->location_name) ?></div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

</body>
</html>
