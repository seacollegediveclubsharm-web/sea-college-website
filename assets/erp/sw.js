const CACHE_NAME = 'sc-erp-shell-v2';
const SHELL_URLS = ['/app', '/assets/erp/manifest.json', '/assets/erp/qrcode.js'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE_NAME).then((c) => c.addAll(SHELL_URLS)).catch(() => {}));
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (e) => {
  if (e.request.method !== 'GET' || e.request.url.includes('/handlers/')) return;
  e.respondWith(
    fetch(e.request).catch(() => caches.match(e.request))
  );
});
