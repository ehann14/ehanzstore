/*
 * Service worker EHANZSTORE Admin.
 * Sengaja SEDERHANA dan aman: halaman admin (login, CSRF, data penjualan) TIDAK di-cache.
 * Fungsinya hanya: membuat situs bisa di-install, dan menampilkan halaman "offline"
 * bila internet mati. Naikkan VERSION bila file ini diubah.
 */
const VERSION = 'ehanz-admin-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(VERSION)
            .then((cache) => cache.addAll([OFFLINE_URL, '/images/logo.png']))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Hanya halaman (navigasi) yang ditangani; form POST, gambar, API, dll lewat normal.
    if (req.mode !== 'navigate' || req.method !== 'GET') return;

    event.respondWith(
        fetch(req).catch(() => caches.match(OFFLINE_URL))
    );
});