{{--
    Halaman maintenance (dipakai oleh mode maintenance dari panel Super Admin
    dan oleh "php artisan down" lewat errors/503.blade.php).
    Sengaja berdiri sendiri (CSS di dalam file) supaya tetap tampil walau database/asset bermasalah.
    Variabel opsional: $title, $message, $until (Carbon|null)
--}}
@php
    $title   = $title   ?? \App\Support\Maintenance::DEFAULT_TITLE;
    $message = $message ?? \App\Support\Maintenance::DEFAULT_MESSAGE;
    $until   = $until   ?? null;

    try {
        $wa = preg_replace('/\D+/', '', (string) \App\Models\StoreSetting::get('whatsapp_number', '6282130559126'));
        $ig = ltrim((string) \App\Models\StoreSetting::get('instagram_username', 'ehanzstore.id'), '@');
    } catch (\Throwable $e) {
        $wa = '6282130559126';
        $ig = 'ehanzstore.id';
    }

    $wa = $wa ?: '6282130559126';
    $ig = $ig ?: 'ehanzstore.id';

    $untilFuture = $until && $until->isFuture();
    $untilLabel  = $untilFuture ? $until->clone()->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} - EHANZSTORE.ID</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --midnight: #020617;
            --ink: #0b1220;
            --electric: #3b82f6;
            --royal: #0645AD;
        }
        html, body { min-height: 100%; }
        body {
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: var(--midnight);
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            overflow-x: hidden;
            position: relative;
            -webkit-font-smoothing: antialiased;
        }
        .blob { position: fixed; border-radius: 9999px; filter: blur(90px); opacity: .35; pointer-events: none; z-index: 0; }
        .blob.a { width: 420px; height: 420px; background: var(--royal); top: -120px; left: -100px; animation: drift 14s ease-in-out infinite alternate; }
        .blob.b { width: 360px; height: 360px; background: var(--electric); bottom: -140px; right: -80px; opacity: .22; animation: drift 18s ease-in-out infinite alternate-reverse; }
        @keyframes drift { from { transform: translate(0, 0); } to { transform: translate(40px, 30px); } }

        .card {
            position: relative; z-index: 1;
            width: 100%; max-width: 560px;
            text-align: center;
            padding: 44px 28px 32px;
            border-radius: 32px;
            background: rgba(15, 23, 42, .62);
            border: 1px solid rgba(255, 255, 255, .08);
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            box-shadow: 0 30px 80px -30px rgba(0, 0, 0, .8), 0 0 0 1px rgba(59, 130, 246, .06);
        }
        @media (min-width: 640px) { .card { padding: 52px 48px 36px; } }

        .brand { display: inline-flex; align-items: center; gap: 12px; margin-bottom: 30px; }
        .brand .logo { width: 46px; height: 46px; border-radius: 16px; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .brand .logo img { width: 38px; height: 38px; object-fit: contain; }
        .brand .name { font-family: 'Nunito', 'Inter', sans-serif; font-weight: 900; font-size: 20px; color: #fff; text-align: left; line-height: 1.1; }
        .brand .tag { font-size: 11px; color: #94a3b8; font-weight: 500; margin-top: 3px; display: block; }

        .gears { position: relative; width: 112px; height: 112px; margin: 0 auto 26px; }
        .gears .ring { position: absolute; inset: 0; border-radius: 9999px; background: radial-gradient(circle, rgba(59,130,246,.25), transparent 70%); animation: pulse 2.6s ease-in-out infinite; }
        .gears svg { position: absolute; color: var(--electric); }
        .gears .big { width: 74px; height: 74px; left: 8px; top: 24px; animation: spin 9s linear infinite; }
        .gears .small { width: 46px; height: 46px; right: 8px; top: 8px; color: #60a5fa; animation: spin 6s linear infinite reverse; }
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes pulse { 0%, 100% { transform: scale(.92); opacity: .7; } 50% { transform: scale(1.06); opacity: 1; } }

        h1 { font-family: 'Nunito', 'Inter', sans-serif; font-weight: 900; font-size: 30px; line-height: 1.15; color: #fff; letter-spacing: -.01em; }
        @media (min-width: 640px) { h1 { font-size: 38px; } }
        h1 span { color: var(--electric); text-shadow: 0 0 24px rgba(59, 130, 246, .45); }
        .msg { margin-top: 14px; font-size: 15px; line-height: 1.7; color: #94a3b8; white-space: pre-line; }

        .bar { margin: 28px auto 0; height: 8px; width: 100%; max-width: 280px; border-radius: 9999px; background: rgba(255,255,255,.07); overflow: hidden; }
        .bar i { display: block; height: 100%; width: 40%; border-radius: 9999px; background: linear-gradient(90deg, var(--royal), var(--electric), #93c5fd); animation: slide 1.8s ease-in-out infinite; }
        @keyframes slide { 0% { transform: translateX(-110%); } 100% { transform: translateX(300%); } }

        .eta { margin-top: 26px; padding: 16px 18px; border-radius: 22px; background: rgba(59,130,246,.08); border: 1px solid rgba(59,130,246,.2); }
        .eta small { display: block; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: #93c5fd; font-weight: 600; }
        .eta strong { display: block; margin-top: 4px; font-family: 'Nunito', 'Inter', sans-serif; font-weight: 800; font-size: 22px; color: #fff; font-variant-numeric: tabular-nums; }
        .eta em { display: block; margin-top: 2px; font-style: normal; font-size: 12px; color: #64748b; }

        .actions { margin-top: 28px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 9999px; font-size: 14px; font-weight: 600; text-decoration: none; transition: transform .15s ease, background .15s ease; }
        .btn:hover { transform: translateY(-2px); }
        .btn.wa { background: #16a34a; color: #fff; box-shadow: 0 10px 24px -10px rgba(22,163,74,.7); }
        .btn.wa:hover { background: #22c55e; }
        .btn.ig { background: rgba(255,255,255,.06); color: #e2e8f0; border: 1px solid rgba(255,255,255,.1); }
        .btn.ig:hover { background: rgba(255,255,255,.1); }
        .btn svg { width: 18px; height: 18px; }

        .foot { margin-top: 28px; font-size: 12px; color: #475569; }
        .foot b { color: #64748b; font-weight: 500; }

        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; } }
    </style>
</head>
<body>
    <div class="blob a"></div>
    <div class="blob b"></div>

    <main class="card" role="main">
        <div class="brand">
            <div class="logo"><img src="{{ asset('images/logo.png') }}" alt="EHANZSTORE" onerror="this.style.display='none'"></div>
            <div class="name">EHANZSTORE<span class="tag">Marketplace COC</span></div>
        </div>

        <div class="gears" aria-hidden="true">
            <div class="ring"></div>
            <svg class="big" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.07-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.49.49 0 00-.59-.22l-2.39.96a7 7 0 00-1.62-.94l-.36-2.54a.48.48 0 00-.48-.41h-3.84a.48.48 0 00-.48.41l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96a.49.49 0 00-.59.22L2.74 8.87a.48.48 0 00.12.61l2.03 1.58c-.05.31-.07.63-.07.94s.02.63.07.94l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.48-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.48.48 0 00-.12-.61l-2.01-1.58zM12 15.6A3.6 3.6 0 1115.6 12 3.6 3.6 0 0112 15.6z"/></svg>
            <svg class="small" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.07-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.49.49 0 00-.59-.22l-2.39.96a7 7 0 00-1.62-.94l-.36-2.54a.48.48 0 00-.48-.41h-3.84a.48.48 0 00-.48.41l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96a.49.49 0 00-.59.22L2.74 8.87a.48.48 0 00.12.61l2.03 1.58c-.05.31-.07.63-.07.94s.02.63.07.94l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.48-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.48.48 0 00-.12-.61l-2.01-1.58zM12 15.6A3.6 3.6 0 1115.6 12 3.6 3.6 0 0112 15.6z"/></svg>
        </div>

        <h1>{{ $title }}</h1>
        <p class="msg">{{ $message }}</p>

        <div class="bar" aria-hidden="true"><i></i></div>

        @if($untilFuture)
            <div class="eta">
                <small>Perkiraan selesai</small>
                <strong id="eta-count" data-until="{{ $until->toIso8601String() }}">{{ $untilLabel }}</strong>
                <em>{{ $untilLabel }}</em>
            </div>
        @endif

        <div class="actions">
            <a class="btn wa" href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Halo Admin EHANZSTORE, saya mau tanya soal akun.') }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                Chat Admin
            </a>
            <a class="btn ig" href="https://instagram.com/{{ $ig }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
                &#64;{{ $ig }}
            </a>
        </div>

        <p class="foot"><b>Halaman ini akan dimuat ulang otomatis.</b> &copy; {{ date('Y') }} EHANZSTORE.ID</p>
    </main>

    <script>
        (function () {
            var el = document.getElementById('eta-count');

            if (el) {
                var end = Date.parse(el.getAttribute('data-until'));
                var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
                var tick = function () {
                    var left = Math.floor((end - Date.now()) / 1000);
                    if (left <= 0) { el.textContent = 'Segera kembali...'; return; }
                    var d = Math.floor(left / 86400), h = Math.floor((left % 86400) / 3600),
                        m = Math.floor((left % 3600) / 60), s = left % 60;
                    el.textContent = (d > 0 ? d + ' hari ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
                };
                tick();
                setInterval(tick, 1000);
            }

            // Cek ulang tiap 60 detik: begitu maintenance dimatikan, halaman otomatis berganti ke website.
            setTimeout(function () { location.reload(); }, 60000);
        })();
    </script>
</body>
</html>