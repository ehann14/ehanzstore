{{--
    Menjaga token CSRF tetap segar di HP.
    Dipasang di <head> layout admin, layout publik, login, dan 2FA.
    - Saat halaman dipulihkan dari cache (tombol back) -> muat ulang halaman
    - Saat tab kembali aktif (pindah dari WhatsApp ke browser) -> ambil token baru
    - Setiap 10 menit -> ambil token baru (sekaligus memperpanjang sesi)
--}}
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
    (function () {
        var url = '{{ route('csrf.token', [], false) }}';
        var busy = false;
        var lastRun = Date.now();

        function applyToken(token) {
            if (!token) return;
            document.querySelectorAll('input[name="_token"]').forEach(function (el) { el.value = token; });
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) meta.setAttribute('content', token);
        }

        function refreshToken() {
            if (busy) return;
            busy = true;
            fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) { if (d && d.token) { applyToken(d.token); lastRun = Date.now(); } })
                .catch(function () { /* offline: abaikan */ })
                .finally(function () { busy = false; });
        }

        // Halaman dipulihkan dari back/forward cache -> paksa muat ulang agar token baru
        window.addEventListener('pageshow', function (e) {
            var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
            if (e.persisted || (nav && nav.type === 'back_forward')) {
                window.location.reload();
            }
        });

        // Kembali ke tab setelah > 1 menit -> segarkan token
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible' && Date.now() - lastRun > 60000) {
                refreshToken();
            }
        });

        // Berkala tiap 10 menit selama tab terbuka
        setInterval(function () {
            if (document.visibilityState === 'visible') refreshToken();
        }, 10 * 60 * 1000);

        // Sebelum submit, pastikan token tidak terlalu tua (> 5 menit)
        document.addEventListener('submit', function () {
            if (Date.now() - lastRun > 5 * 60 * 1000) refreshToken();
        }, true);
    })();
</script>