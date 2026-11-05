{{--
    PWA: membuat panel admin bisa di-install (HP & laptop) lewat Chrome/Edge.
    Dipasang di <head> layout admin, halaman login, dan 2FA.
    File pendukung: public/manifest.json, public/sw.js, public/offline.html, public/images/logo.png
--}}
<link rel="manifest" href="{{ asset('manifest.json') }}">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="EHANZ Admin">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

{{--
    Layar pembuka aplikasi. Dua hal:
    1) Latar gelap langsung dari baris pertama, jadi tidak ada kilat putih sebelum Tailwind selesai dimuat.
    2) Saat aplikasi dibuka dari ikon (mode terpasang), tampilkan logo + indikator muat sampai halaman siap.
       Hanya sekali per pembukaan aplikasi (bukan di setiap pindah halaman). Dibuat lewat pseudo-element <html>,
       jadi tidak butuh markup di <body>. Ada pengaman CSS: tertutup sendiri maksimal 6 detik walau JS gagal.
--}}
<style>
    html { background: #020617; color-scheme: dark; }
    html.app-launching::before {
        content: ''; position: fixed; inset: 0; z-index: 2147483646;
        background: #020617 url('{{ asset('images/pwa-192.png') }}') center calc(50% - 22px) / 88px 88px no-repeat;
        animation: launch-failsafe .35s ease 6s forwards;
        transition: opacity .25s ease;
    }
    html.app-launching::after {
        content: ''; position: fixed; z-index: 2147483647; left: 50%; top: calc(50% + 52px);
        width: 26px; height: 26px; margin-left: -13px; border-radius: 50%;
        border: 3px solid rgba(148, 163, 184, .25); border-top-color: #3b82f6;
        animation: launch-spin .8s linear infinite, launch-failsafe .35s ease 6s forwards;
        transition: opacity .25s ease;
    }
    html.app-launching.launch-done::before, html.app-launching.launch-done::after { opacity: 0; pointer-events: none; }
    @keyframes launch-spin { to { transform: rotate(360deg); } }
    @keyframes launch-failsafe { to { opacity: 0; visibility: hidden; pointer-events: none; } }
</style>
<script>
    (function () {
        var root = document.documentElement;
        var standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
        var seen = false;

        try { seen = sessionStorage.getItem('ehanzLaunched') === '1'; sessionStorage.setItem('ehanzLaunched', '1'); } catch (e) {}

        // Layar pembuka hanya saat aplikasi terpasang baru dibuka, bukan di setiap pindah halaman / tab browser biasa.
        if (!standalone || seen) return;

        root.classList.add('app-launching');

        function done() {
            // Dua frame supaya Tailwind (CDN) sempat menerapkan gayanya sebelum layar pembuka diangkat.
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    root.classList.add('launch-done');
                    setTimeout(function () { root.classList.remove('app-launching', 'launch-done'); }, 400);
                });
            });
        }

        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', done); } else { done(); }
    })();
</script>

<script>
    (function () {
        // 1) Daftarkan service worker (wajib agar bisa di-install; butuh HTTPS atau localhost)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('{{ asset('sw.js') }}?v={{ @filemtime(public_path('sw.js')) ?: 1 }}', { scope: '/' }).catch(function () {});
            });
        }

        // 2) Tombol "Pasang Aplikasi" (muncul hanya bila Chrome menganggap situs bisa di-install)
        let deferred = null;
        const btnId = 'pwaInstallBtn';

        function removeBtn() {
            const b = document.getElementById(btnId);
            if (b) b.remove();
        }

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferred = e;
            if (document.getElementById(btnId)) return;

            const b = document.createElement('button');
            b.id = btnId;
            b.type = 'button';
            b.textContent = '⬇ Pasang Aplikasi';
            b.style.cssText = 'position:fixed;right:16px;bottom:16px;z-index:9999;background:#0645AD;color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:14px;padding:12px 18px;font:700 13px system-ui,sans-serif;box-shadow:0 10px 30px rgba(0,0,0,.5);cursor:pointer';
            b.addEventListener('click', function () {
                if (!deferred) return;
                deferred.prompt();
                deferred.userChoice.finally(function () { deferred = null; removeBtn(); });
            });
            document.body.appendChild(b);
        });

        window.addEventListener('appinstalled', function () { deferred = null; removeBtn(); });
    })();
</script>