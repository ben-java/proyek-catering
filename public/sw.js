const CACHE = 'cateringku-assets-v3';
// Naikkan versi cache ini setiap kali aset statis (terutama /css/app.css)
// berubah, agar browser lama tidak menampilkan CSS lama walau server sudah
// diperbarui (Tahap F, mobile-review). Navigasi/halaman TIDAK di-cache
// agar data privat tidak pernah tersaji dari cache sesudah logout (BUG-01),
// dan agar perubahan data langsung tercermin (BUG-06).
const SHELL = [
  '/css/app.css',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
  '/manifest.json',
];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  if (e.request.method !== 'GET') return;
  const url = new URL(e.request.url);
  if (url.origin !== self.location.origin) return;

  const isAsset =
    e.request.destination === 'style' ||
    e.request.destination === 'image' ||
    e.request.destination === 'font' ||
    url.pathname === '/manifest.json';

  if (isAsset) {
    // cache-first untuk aset statis
    e.respondWith(
      caches.match(e.request).then(
        (cached) => cached || fetch(e.request).then((res) => {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(e.request, copy));
          return res;
        })
      )
    );
    return;
  }

  // Navigasi/halaman: network-only. Server tetap menerapkan auth & CSRF.
  e.respondWith(fetch(e.request));
});
