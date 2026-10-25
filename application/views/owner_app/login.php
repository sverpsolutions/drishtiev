<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>Owner Login</title>
<meta name="theme-color" content="#0f172a">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{min-height:100vh;background:linear-gradient(135deg,#0f172a 0%,#1e293b 60%,#0f172a 100%);display:flex;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;}
.card{background:#1e293b;border-radius:20px;padding:36px 28px;width:90%;max-width:360px;box-shadow:0 20px 60px rgba(0,0,0,.5);}
.logo{text-align:center;margin-bottom:24px;}
.logo-ring{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#ef4444);display:inline-flex;align-items:center;justify-content:center;font-size:32px;margin-bottom:10px;}
.logo h2{color:#f8fafc;font-size:18px;font-weight:700;}
.logo p{color:#94a3b8;font-size:12px;margin-top:2px;}
label{display:block;color:#94a3b8;font-size:12px;font-weight:600;margin-bottom:5px;letter-spacing:.5px;text-transform:uppercase;}
input{width:100%;background:#0f172a;border:1.5px solid #334155;border-radius:10px;padding:13px 15px;color:#f8fafc;font-size:15px;margin-bottom:16px;outline:none;transition:border-color .2s;}
input:focus{border-color:#f59e0b;}
.btn{width:100%;background:linear-gradient(135deg,#f59e0b,#ef4444);border:none;border-radius:10px;padding:14px;color:#fff;font-size:16px;font-weight:700;cursor:pointer;letter-spacing:.5px;margin-top:4px;}
.btn:active{opacity:.9;}
.error{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.4);color:#fca5a5;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;}
.store{text-align:center;color:#475569;font-size:11px;margin-top:18px;}
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-ring">👑</div>
        <h2><?= htmlspecialchars($_company['company_name'] ?? 'Owner Dashboard') ?></h2>
        <p>Owner Access Only</p>
    </div>
    <?php if ($error): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
        <label>Username</label>
        <input type="text" name="phone" placeholder="Enter username" autocomplete="username" required autofocus>
        <label>Password</label>
        <input type="password" name="password" placeholder="Password" autocomplete="current-password" required>
        <button class="btn" type="submit">Login →</button>
    </form>
    <div class="store"><?= htmlspecialchars($_company['company_address'] ?? '') ?></div>
</div>
</body>
</html>
