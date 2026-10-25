<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Vendor Login — Supplier Portal</title>
<link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">
<style>body{background:#1a1a2e;min-height:100vh;display:flex;align-items:center;justify-content:center;}</style>
</head>
<body>
<div class="card border-0 shadow" style="width:360px;border-radius:12px;overflow:hidden;">
    <div class="bg-dark text-white text-center p-4">
        <i class="fas fa-building fa-2x mb-2"></i>
        <h5 class="mb-0">Supplier / Vendor Portal</h5>
        <small class="text-muted">Sign in with your portal credentials</small>
    </div>
    <div class="card-body p-4">
        <?php if ($error ?? null): ?><div class="alert alert-danger py-2 mb-3 text-sm"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?= form_open('vendor-portal/login') ?>
        <div class="form-group">
            <label class="small font-weight-bold">Username</label>
            <input name="username" class="form-control" placeholder="Portal username" required autofocus>
        </div>
        <div class="form-group">
            <label class="small font-weight-bold">Password</label>
            <input name="password" type="password" class="form-control" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-dark btn-block">Sign In <i class="fas fa-arrow-right ml-1"></i></button>
        <?= form_close() ?>
    </div>
    <div class="card-footer text-center py-2 bg-light"><small class="text-muted">Contact your purchase team for credentials</small></div>
</div>
<link rel="stylesheet" href="<?= base_url('assets/css/all.min.css') ?>">
</body></html>
