// Service worker minimal (US-23). Aplikasi tetap membutuhkan koneksi untuk
// berfungsi (PRD): satu-satunya hal yang di-cache adalah halaman /offline,
// supaya navigasi yang gagal karena tidak ada koneksi menampilkan penjelasan
// alih-alih error browser mentah. Tidak ada asset lain yang di-cache.
const OFFLINE_URL = '/offline';
const CACHE_NAME = 'dompetku-offline-v1';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.add(OFFLINE_URL)),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => caches.match(OFFLINE_URL)),
        );
    }
});
