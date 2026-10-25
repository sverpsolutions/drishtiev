<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<meta name="theme-color" content="#0f172a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title><?= htmlspecialchars($page_title ?? 'Owner App') ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html,body{height:100%;background:#0f172a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#f8fafc;overflow-x:hidden;}
/* top bar */
.topbar{position:sticky;top:0;z-index:100;background:#1e293b;padding:12px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #334155;}
.topbar-back{color:#f59e0b;font-size:20px;text-decoration:none;line-height:1;}
.topbar-title{font-size:16px;font-weight:700;flex:1;}
.topbar-sub{font-size:11px;color:#64748b;}
.topbar-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#ef4444);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;}
/* content */
.content{padding:14px;padding-bottom:80px;}
/* bottom nav */
.bottom-nav{position:fixed;bottom:0;left:0;right:0;background:#1e293b;border-top:1px solid #334155;display:flex;z-index:100;}
.nav-item{flex:1;display:flex;flex-direction:column;align-items:center;padding:10px 0 8px;text-decoration:none;color:#64748b;font-size:10px;font-weight:600;letter-spacing:.3px;transition:color .15s;}
.nav-item.active{color:#f59e0b;}
.nav-item .icon{font-size:22px;margin-bottom:3px;line-height:1;}
/* card */
.card{background:#1e293b;border-radius:14px;padding:16px;margin-bottom:12px;}
.card-title{font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.6px;font-weight:600;margin-bottom:10px;}
/* stat grid */
.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px;}
.stat-card{background:#1e293b;border-radius:14px;padding:14px 12px;position:relative;overflow:hidden;}
.stat-card::before{content:'';position:absolute;top:-10px;right:-10px;width:60px;height:60px;border-radius:50%;opacity:.12;}
.stat-card.green::before{background:#22c55e;}
.stat-card.blue::before{background:#3b82f6;}
.stat-card.amber::before{background:#f59e0b;}
.stat-card.purple::before{background:#a855f7;}
.stat-card.red::before{background:#ef4444;}
.stat-label{font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;}
.stat-value{font-size:20px;font-weight:800;line-height:1.1;}
.stat-value.big{font-size:26px;}
.stat-sub{font-size:10px;color:#94a3b8;margin-top:4px;}
.green .stat-value{color:#4ade80;}
.blue .stat-value{color:#60a5fa;}
.amber .stat-value{color:#fbbf24;}
.purple .stat-value{color:#c084fc;}
.red .stat-value{color:#f87171;}
/* wide card */
.stat-wide{background:#1e293b;border-radius:14px;padding:14px 16px;margin-bottom:12px;}
/* mode chips */
.mode-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;}
.mode-chip{background:#0f172a;border-radius:10px;padding:10px 12px;flex:1;min-width:80px;text-align:center;}
.mode-chip .mc-label{font-size:10px;color:#64748b;margin-bottom:4px;}
.mode-chip .mc-val{font-size:15px;font-weight:700;color:#f8fafc;}
.mode-chip .mc-bills{font-size:10px;color:#475569;margin-top:2px;}
/* table */
.mob-table{width:100%;border-collapse:collapse;font-size:13px;}
.mob-table th{color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.4px;padding:6px 4px;border-bottom:1px solid #334155;text-align:left;}
.mob-table td{padding:10px 4px;border-bottom:1px solid #1a2744;vertical-align:middle;}
.mob-table tr:last-child td{border-bottom:none;}
.badge{display:inline-block;border-radius:6px;padding:2px 8px;font-size:10px;font-weight:700;}
.badge-green{background:rgba(34,197,94,.15);color:#4ade80;}
.badge-blue{background:rgba(59,130,246,.15);color:#60a5fa;}
.badge-amber{background:rgba(245,158,11,.15);color:#fbbf24;}
.badge-purple{background:rgba(168,85,247,.15);color:#c084fc;}
.badge-gray{background:rgba(100,116,139,.15);color:#94a3b8;}
/* date filter */
.date-filter{display:flex;gap:8px;margin-bottom:12px;align-items:flex-end;}
.date-filter label{font-size:10px;color:#64748b;display:block;margin-bottom:3px;}
.date-filter input{background:#1e293b;border:1.5px solid #334155;border-radius:8px;color:#f8fafc;padding:8px 10px;font-size:13px;width:100%;outline:none;}
.date-filter input:focus{border-color:#f59e0b;}
.date-filter button{background:#f59e0b;color:#0f172a;border:none;border-radius:8px;padding:9px 16px;font-weight:700;font-size:13px;cursor:pointer;white-space:nowrap;height:38px;}
/* section heading */
.sec-head{font-size:12px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin:16px 0 8px;}
/* amount right */
.amt{text-align:right;font-weight:700;color:#4ade80;}
/* chart bars */
.chart-wrap{display:flex;align-items:flex-end;gap:4px;height:70px;margin-top:8px;}
.bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;}
.bar{background:linear-gradient(180deg,#f59e0b,#ef4444);border-radius:3px 3px 0 0;width:100%;min-height:2px;transition:height .3s;}
.bar-lbl{font-size:8px;color:#475569;}
/* logout */
.logout-btn{width:100%;background:#334155;border:none;border-radius:10px;color:#94a3b8;padding:13px;font-size:14px;font-weight:600;cursor:pointer;margin-top:8px;}
</style>
