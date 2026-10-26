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

<script>
    (function () {
        // 1) Daftarkan service worker (wajib agar bisa di-install; butuh HTTPS atau localhost)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('{{ asset('sw.js') }}', { scope: '/' }).catch(function () {});
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