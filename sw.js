/**
 * sw.js — Service Worker for Sandal House Offline POS
 * Caches static assets and the billing page for offline use.
 * Scope: determined by registration location (application root)
 */

const CACHE_NAME = 'sandalhouse-pos-v2';

// Static assets to pre-cache during install
const PRECACHE_ASSETS = [
    'assets/css/bootstrap.min.css',
    'assets/css/font-awesome.min.css',
    'assets/css/bootstrap-icons.css',
    'assets/css/style.css',
    'assets/js/jquery.min.js',
    'assets/js/bootstrap.bundle.min.js',
    'assets/js/main.js',
    'assets/js/offline-db.js',
    'assets/js/offline-sync.js',
];

// Derive base path from SW script location
const BASE = self.registration.scope; // e.g. http://localhost/sandalhouse/

// ─── Install: pre-cache static assets ────────────────────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            const urls = PRECACHE_ASSETS.map(p => BASE + p);
            return Promise.allSettled(urls.map(url =>
                cache.add(url).catch(() => {/* skip missing */})
            ));
        }).then(() => self.skipWaiting())
    );
});

// ─── Activate: clean old caches ──────────────────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys
                .filter(k => k !== CACHE_NAME)
                .map(k  => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

// ─── Fetch interception ───────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
    const req = event.request;
    const url = new URL(req.url);

    // Only handle same-origin requests
    if (url.origin !== location.origin) return;

    const path = url.pathname;

    // ── POST requests (invoice submission) ──
    // These must reach the server. When offline they're handled by client-side JS.
    if (req.method !== 'GET') return;

    // ── Billing create page: Network First, fallback to cache ──
    if (path.endsWith('/billing/create') || path.endsWith('/billing/create/')) {
        event.respondWith(networkFirstPage(req));
        return;
    }

    // ── Static assets: Cache First ──
    if (
        path.includes('/assets/css/') ||
        path.includes('/assets/js/')  ||
        path.includes('/assets/webfonts/')
    ) {
        event.respondWith(cacheFirst(req));
        return;
    }

    // ── Product images: Cache First (best-effort) ──
    if (path.includes('/assets/uploads/products/') || path.includes('/assets/products/')) {
        event.respondWith(cacheFirstOptional(req));
        return;
    }

    // All other requests: network only (don't interfere with server navigation)
});

// ─── Strategies ───────────────────────────────────────────────────────────────

// Network first, fall back to cache — for the billing page HTML.
// `cache: 'no-store'` is required, not cosmetic: a plain fetch(req) is still allowed to
// be satisfied from the browser's own HTTP disk cache (independent of this Service
// Worker's cache) whenever the PHP response doesn't send explicit no-cache headers —
// so a cashier's terminal could keep "successfully" loading a months-old billing page
// on every navigation, silently missing every JS fix deployed since, with no offline
// error to signal anything was wrong. Forcing a real network round-trip here is the
// fix; this is what made deployed billing-screen fixes fail to reach an already-running
// terminal even after normal reloads.
async function networkFirstPage(req) {
    try {
        const resp = await fetch(req, { cache: 'no-store' });
        if (resp.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(req, resp.clone());
        }
        return resp;
    } catch {
        const cached = await caches.match(req);
        if (cached) return cached;
        // If even cache misses, return an offline fallback page
        return offlineFallback();
    }
}

// Cache first, fall back to network — for static assets
async function cacheFirst(req) {
    const cached = await caches.match(req);
    if (cached) return cached;
    try {
        const resp  = await fetch(req);
        const cache = await caches.open(CACHE_NAME);
        cache.put(req, resp.clone());
        return resp;
    } catch {
        return new Response('', { status: 503 });
    }
}

// Cache first optional — for images (serve blank if missing)
async function cacheFirstOptional(req) {
    const cached = await caches.match(req);
    if (cached) return cached;
    try {
        const resp  = await fetch(req);
        if (resp.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(req, resp.clone());
        }
        return resp;
    } catch {
        // Transparent 1x1 placeholder
        return new Response(
            atob('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='),
            { headers: { 'Content-Type': 'image/png' } }
        );
    }
}

// Minimal offline fallback page
function offlineFallback() {
    const html = `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Offline — Sandal House POS</title>
<style>
body{font-family:sans-serif;background:#FAF6EB;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
.box{background:#fff;border-radius:12px;padding:40px;max-width:420px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.1);}
h2{color:#3B2314;margin-bottom:8px;}
p{color:#666;}
.badge{display:inline-block;background:#dc3545;color:#fff;padding:4px 12px;border-radius:20px;font-size:.85rem;margin-bottom:16px;}
button{background:#3B2314;color:#fff;border:none;padding:10px 24px;border-radius:8px;cursor:pointer;font-size:1rem;}
button:hover{background:#5E3E26;}
</style>
</head>
<body>
<div class="box">
  <div class="badge">🔴 Offline</div>
  <h2>POS is Working Offline</h2>
  <p>The billing page is loading from your local cache.<br>All data is saved locally and will sync when internet returns.</p>
  <button onclick="location.reload()">Retry</button>
</div>
</body>
</html>`;
    return new Response(html, { headers: { 'Content-Type': 'text/html' } });
}
