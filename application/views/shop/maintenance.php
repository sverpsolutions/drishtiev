<!doctype html>
<html lang="en-IN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= html_escape(ucwords(strtolower($company['company_name'] ?? 'Suvasan Clothes'))) ?> — Back soon</title>
<link rel="icon" href="<?= base_url('assets/web/icon-32.png') ?>">
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#2a060b;color:#ead7a4;font-family:Georgia,serif;text-align:center;padding:16px}img{max-width:min(420px,90vw);height:auto}h1{font-weight:400}</style>
</head><body><div><img src="<?= base_url('assets/web/logo.png') ?>" alt="<?= html_escape($company['company_name'] ?? '') ?>"><h1>We'll be back shortly</h1><p>Our online store is being updated. Please visit again soon.</p><?php if (!empty($company['company_phone'])): ?><p>Call us: <?= html_escape($company['company_phone']) ?></p><?php endif; ?></div></body></html>
