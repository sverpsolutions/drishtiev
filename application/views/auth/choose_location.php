<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Select Location — <?= htmlspecialchars($company['company_name'] ?? 'Sandal House') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php
        if (!function_exists('admin_contrast_text')) {
            function admin_contrast_text($hex) {
                $hex = ltrim((string)$hex, '#');
                if (strlen($hex) !== 6 || !ctype_xdigit($hex)) return '#3B2314';
                $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
                $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
                return $luminance > 0.55 ? '#3B2314' : '#FFFFFF';
            }
        }
        $__admin_primary = htmlspecialchars($company['admin_primary_color'] ?? '#F6CD3C');
        $__admin_accent  = htmlspecialchars($company['admin_accent_color']  ?? '#E5A823');
        $__admin_text    = admin_contrast_text($company['admin_primary_color'] ?? '#F6CD3C');
    ?>
    <style>
        body {
            background: #FAF6EB;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            color: #2D1C13;
        }
        .loc-card {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #E6DCBF;
            box-shadow: 0 8px 30px rgba(59,35,20,0.08);
            overflow: hidden;
        }
        .loc-header {
            background: <?= $__admin_primary ?>;
            border-bottom: 2px solid <?= $__admin_accent ?>;
            color: <?= $__admin_text ?>;
            padding: 26px 32px 18px;
            text-align: center;
        }
        .loc-header h4 {
            font-weight: 750;
            font-family: 'Georgia', serif;
            margin-bottom: 2px;
            font-size: 1.25rem;
            color: <?= $__admin_text ?>;
        }
        .loc-header p {
            font-size: 0.8rem;
            color: <?= $__admin_text ?>;
            font-weight: 600;
            margin: 4px 0 0;
            opacity: 0.85;
        }
        .loc-body { padding: 24px 28px 28px; }
        .loc-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 10px;
            border: 1px solid #E6DCBF;
            border-radius: 8px;
            background: #FFF9E6;
            color: #3B2314;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: left;
            transition: all 0.15s;
        }
        .loc-btn:hover, .loc-btn:focus {
            background: #3B2314;
            border-color: #3B2314;
            color: #FFF9E6;
        }
        .loc-btn .loc-code {
            font-weight: 500;
            font-size: 0.78rem;
            opacity: 0.7;
        }
        .loc-btn-all {
            background: #3B2314;
            color: #FFF9E6;
            border-color: #3B2314;
        }
        .loc-btn-all:hover, .loc-btn-all:focus {
            background: #5E3E26;
            border-color: #5E3E26;
            color: #FFF9E6;
        }
    </style>
</head>
<body>

<div class="loc-card">
    <div class="loc-header">
        <h4>Select Location</h4>
        <p>Welcome, <?= htmlspecialchars($this->session->userdata('name') ?? '') ?> &mdash; choose where you want to work</p>
    </div>
    <div class="loc-body">

        <?= form_open('auth/set_location') ?>
            <button type="submit" name="location_id" value="0" class="loc-btn loc-btn-all">
                All Locations
            </button>
        <?= form_close() ?>

        <hr class="my-3">

        <?php foreach ($locations as $loc): ?>
            <?= form_open('auth/set_location') ?>
                <button type="submit" name="location_id" value="<?= (int)$loc->id ?>" class="loc-btn">
                    <span><?= htmlspecialchars($loc->name) ?></span>
                    <span class="loc-code"><?= htmlspecialchars($loc->code) ?></span>
                </button>
            <?= form_close() ?>
        <?php endforeach; ?>

        <?php if (empty($locations)): ?>
            <p class="text-muted text-center mb-0" style="font-size:0.85rem;">No locations configured yet.</p>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
