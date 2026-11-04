/*
 * Service worker EHANZSTORE Admin.
 * Sengaja SEDERHANA dan aman: halaman admin (login, CSRF, data penjualan) TIDAK di-cache.
 * Fungsinya hanya: membuat situs bisa di-install, dan menampilkan halaman "offline"
 * bila internet mati. Naikkan VERSION bila file ini diubah.
 */
const VERSION = 'ehanz-admin-v3';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(VERSION)
            .then((cache) => cache.addAll([OFFLINE_URL, '/images/logo.png', '/images/pwa-192.png']))
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


/* ------------------------------------------------------------------
 * Notifikasi push (booking baru, ulasan baru) - muncul di HP walau panel tidak dibuka.
 *
 * Server hanya mengirim "ketukan" kosong. Isi notifikasinya diambil dari server lewat
 * /admin/notifications/push/pending (memakai login yang tersimpan di HP), jadi isi pesan
 * tidak melewati layanan push pihak ketiga.
 * ------------------------------------------------------------------ */
const PENDING_URL = '/admin/notifications/push/pending';
const ICON = '/images/pwa-192.png';

self.addEventListener('push', (event) => {
    event.waitUntil((async () => {
        // Panel sedang terbuka dan terlihat: lonceng di halaman yang menampilkan, tidak perlu notifikasi HP.
        const wins = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const visible = wins.filter((c) => c.visibilityState === 'visible' && c.focused);
        if (visible.length) {
            visible.forEach((c) => c.postMessage({ type: 'push-refresh' }));
            return;
        }

        let items = [];
        try {
            const res = await fetch(PENDING_URL, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' },
            });
            if (res.ok && (res.headers.get('content-type') || '').includes('json')) {
                items = (await res.json()).items || [];
            }
        } catch (e) { /* offline / sesi habis: pakai notifikasi umum di bawah */ }

        if (!items.length) {
            // Chrome mewajibkan setiap push menampilkan notifikasi.
            await self.registration.showNotification('EHANZSTORE', {
                body: 'Ada notifikasi baru di panel admin.',
                icon: ICON,
                tag: 'ehanz-general',
                data: { url: '/admin/dashboard' },
            });
            return;
        }

        for (const n of items) {
            const shown = await self.registration.getNotifications({ tag: n.id });
            if (shown.length) continue;          // sudah pernah ditampilkan (mis. dua push beruntun)

            await self.registration.showNotification(n.title, {
                body: n.message,
                icon: ICON,
                tag: n.id,
                renotify: true,
                vibrate: [200, 100, 200],
                data: { url: n.url || '/admin/dashboard' },
            });
        }
    })());
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL((event.notification.data && event.notification.data.url) || '/admin/dashboard', self.location.origin).href;

    event.waitUntil((async () => {
        const wins = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });

        for (const c of wins) {
            if (new URL(c.url).origin === self.location.origin && 'focus' in c) {
                await c.focus();
                if ('navigate' in c) { try { await c.navigate(target); } catch (e) {} }
                return;
            }
        }

        await self.clients.openWindow(target);
    })());
});