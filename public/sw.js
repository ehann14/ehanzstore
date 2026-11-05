/*
 * Service worker EHANZSTORE Admin.
 * Sengaja SEDERHANA dan aman: halaman admin (login, CSRF, data penjualan) TIDAK di-cache.
 * Fungsinya:
 *  - membuat situs bisa di-install
 *  - menampilkan halaman "offline" bila internet mati
 *  - mempercepat pembukaan aplikasi: pustaka & font pihak ketiga (Tailwind, Alpine, Google Fonts)
 *    disimpan di HP dan diperbarui diam-diam di belakang (stale-while-revalidate)
 *  - navigation preload: permintaan halaman berjalan paralel dengan service worker yang sedang bangun
 * Naikkan VERSION bila file ini diubah.
 */
const VERSION = 'ehanz-admin-v4';          // halaman offline + ikon (aset kecil, wajib ada)
const STATIC_CACHE = 'ehanz-static-v1';    // pustaka / font pihak ketiga (diperbarui di belakang)
const OFFLINE_URL = '/offline.html';

// Hanya aset statis dari host ini yang disimpan. Halaman admin tidak pernah masuk daftar ini.
const STATIC_HOSTS = [
    'cdn.tailwindcss.com',
    'unpkg.com',
    'cdn.jsdelivr.net',
    'cdnjs.cloudflare.com',
    'fonts.googleapis.com',
    'fonts.gstatic.com',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(VERSION)
            .then((cache) => cache.addAll([OFFLINE_URL, '/images/logo.png', '/images/pwa-192.png']))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        // Navigation preload: halaman mulai diunduh sementara service worker masih bangun (lebih cepat saat aplikasi dibuka).
        if (self.registration.navigationPreload) {
            try { await self.registration.navigationPreload.enable(); } catch (e) { /* tidak didukung: abaikan */ }
        }

        const keep = [VERSION, STATIC_CACHE];
        const keys = await caches.keys();
        await Promise.all(keys.filter((k) => !keep.includes(k)).map((k) => caches.delete(k)));
        await self.clients.claim();
    })());
});

// Aset statis: tampilkan dari simpanan dulu (instan), lalu perbarui di belakang untuk pembukaan berikutnya.
async function staleWhileRevalidate(event) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(event.request);

    const refresh = fetch(event.request)
        .then((res) => {
            // Simpan jawaban normal atau "opaque" (skrip lintas-domain). Jangan simpan error server.
            if (res && (res.ok || res.type === 'opaque')) {
                cache.put(event.request, res.clone());
            }
            return res;
        })
        .catch(() => null);

    if (cached) {
        event.waitUntil(refresh);   // biarkan pembaruan selesai walau halaman sudah tampil
        return cached;
    }

    return (await refresh) || Response.error();
}

self.addEventListener('fetch', (event) => {
    const req = event.request;

    if (req.method !== 'GET') return;

    // Halaman (navigasi): selalu dari server (tidak pernah di-cache). Offline -> halaman offline.
    if (req.mode === 'navigate') {
        event.respondWith((async () => {
            try {
                const preloaded = await event.preloadResponse;
                if (preloaded) return preloaded;

                return await fetch(req);
            } catch (e) {
                return (await caches.match(OFFLINE_URL)) || Response.error();
            }
        })());
        return;
    }

    // Pustaka & font pihak ketiga + ikon/logo toko: instan dari simpanan, diperbarui di belakang.
    const url = new URL(req.url);
    const isStatic = STATIC_HOSTS.includes(url.hostname)
        || (url.origin === self.location.origin && url.pathname.startsWith('/images/'));

    if (isStatic) {
        event.respondWith(staleWhileRevalidate(event));
    }
    // Selain itu (form, API, upload, dll) lewat normal.
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