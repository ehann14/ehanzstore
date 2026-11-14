{{--
    Halaman maintenance (dipakai oleh mode maintenance dari panel Super Admin
    dan oleh "php artisan down" lewat errors/503.blade.php).
    Sengaja berdiri sendiri (CSS & ilustrasi SVG di dalam file, tanpa font/skrip luar) supaya tetap tampil walau database/asset bermasalah.
    Ringan: tanpa blur/backdrop-filter, animasi hanya transform/opacity (GPU), font bawaan perangkat.
    Variabel opsional:
      $title, $message, $until (Carbon|null)
      $theme   : 'dev' (perbaikan sistem) atau 'promo' (persiapan promo besar); kosong = tema tersimpan di pengaturan
      $image   : URL gambar kustom dari panel Super Admin ('' = pakai ilustrasi bawaan)
      $preview : true saat dibuka dari tombol "Lihat halaman maintenance" di panel Super Admin
--}}
@php
    $theme = $theme ?? null;

    // Dipanggil tanpa tema (mis. dari errors/503 saat "php artisan down"): ambil dari pengaturan bila bisa.
    if ($theme === null) {
        try {
            $theme = \App\Support\Maintenance::settings()['theme'];
        } catch (\Throwable $e) {
            $theme = 'dev';
        }
    }

    $theme = isset(\App\Support\Maintenance::THEMES[$theme]) ? $theme : 'dev';
    $t     = \App\Support\Maintenance::THEMES[$theme];

    $title   = $title   ?? $t['title'];
    $message = $message ?? $t['message'];
    $until   = $until   ?? null;
    $preview = $preview ?? false;
    $image   = $image   ?? null;

    // Dipanggil tanpa variabel (mis. dari errors/503 saat "php artisan down"): ambil gambar dari pengaturan bila bisa.
    if ($image === null) {
        try {
            $image = \App\Support\Maintenance::settings()['image_url'];
        } catch (\Throwable $e) {
            $image = '';
        }
    }

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

    $gear = 'M19.14 12.94c.04-.31.06-.63.06-.94s-.02-.63-.07-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.49.49 0 00-.59-.22l-2.39.96a7 7 0 00-1.62-.94l-.36-2.54a.48.48 0 00-.48-.41h-3.84a.48.48 0 00-.48.41l-.36 2.54c-.59.24-1.13.56-1.62.94l-2.39-.96a.49.49 0 00-.59.22L2.74 8.87a.48.48 0 00.12.61l2.03 1.58c-.05.31-.07.63-.07.94s.02.63.07.94l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.48-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32a.48.48 0 00-.12-.61l-2.01-1.58zM12 15.6A3.6 3.6 0 1115.6 12 3.6 3.6 0 0112 15.6z';
@endphp
<!DOCTYPE html>
<html lang="id" class="theme-{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="{{ $t['color'] }}">
    <title>{{ $title }} - EHANZSTORE.ID</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #020617; --card: #0b1220; --line: #1e293b; --blue: #3b82f6; --royal: #0645AD;
            --glow1: rgba(6, 69, 173, .38); --glow2: rgba(59, 130, 246, .2);
            --pill-bg: rgba(245, 158, 11, .1); --pill-line: rgba(245, 158, 11, .28); --pill-ink: #fbbf24; --pill-dot: #f59e0b;
            --bar1: var(--royal); --bar2: var(--blue);
            --eta-bg: rgba(59, 130, 246, .08); --eta-line: rgba(59, 130, 246, .22); --eta-ink: #93c5fd;
        }
        /* Tema promo: nuansa hangat oranye-pink */
        :root.theme-promo {
            --bg: #0a0510; --card: #140b1c; --line: #2d1b33;
            --glow1: rgba(236, 72, 153, .30); --glow2: rgba(249, 115, 22, .22);
            --pill-bg: rgba(236, 72, 153, .12); --pill-line: rgba(236, 72, 153, .36); --pill-ink: #f9a8d4; --pill-dot: #ec4899;
            --bar1: #f97316; --bar2: #ec4899;
            --eta-bg: rgba(249, 115, 22, .09); --eta-line: rgba(249, 115, 22, .3); --eta-ink: #fdba74;
        }
        html { background: var(--bg); }
        body {
            min-height: 100vh; min-height: 100dvh;
            display: flex; align-items: center; justify-content: center;
            padding: 24px 16px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #e2e8f0;
            -webkit-font-smoothing: antialiased;
            /* Latar statis (tanpa blur/animasi) supaya tidak membebani GPU */
            background:
                radial-gradient(520px 320px at 12% -6%, var(--glow1), transparent 70%),
                radial-gradient(460px 300px at 100% 108%, var(--glow2), transparent 70%),
                var(--bg);
        }
        body.is-preview { padding-top: 76px; }

        .previewbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 10;
            display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 6px 14px;
            padding: 12px 16px; background: #f59e0b; color: #0f172a; font-size: 13px; font-weight: 600; text-align: center;
        }
        .previewbar a { color: #0f172a; font-weight: 800; text-decoration: underline; }

        .card {
            width: 100%; max-width: 520px; text-align: center;
            padding: 36px 24px 28px;
            border-radius: 28px;
            background: var(--card);
            border: 1px solid var(--line);
            box-shadow: 0 24px 60px -28px rgba(0, 0, 0, .85);
        }
        @media (min-width: 640px) { .card { padding: 44px 44px 32px; } }

        .brand { display: inline-flex; align-items: center; gap: 12px; margin-bottom: 22px; }
        .logo { width: 44px; height: 44px; border-radius: 14px; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .logo img { width: 36px; height: 36px; object-fit: contain; }
        .name { font-weight: 800; font-size: 19px; color: #fff; text-align: left; line-height: 1.1; letter-spacing: .01em; }
        .tag { display: block; margin-top: 3px; font-size: 11px; font-weight: 500; color: #94a3b8; letter-spacing: 0; }

        /* Ilustrasi: hanya transform (GPU), tanpa filter/gradient animasi */
        .visual { margin: 0 auto 20px; contain: layout paint; }
        .hero-img {
            display: block; margin: 0 auto; max-width: 100%; max-height: 240px; width: auto; height: auto;
            object-fit: contain; border-radius: 20px; border: 1px solid var(--line);
        }
        .illus { display: block; width: 100%; max-width: 260px; height: auto; margin: 0 auto; }
        .gear { transform-box: fill-box; transform-origin: center; will-change: transform; }
        .gear.big   { animation: spin 10s linear infinite; }
        .gear.small { animation: spin 6s linear infinite reverse; }
        @keyframes spin { to { transform: rotate(360deg); } }
        /* Tema promo: hadiah melayang dan kilau berkedip (hanya transform/opacity) */
        .float { animation: floaty 3s ease-in-out infinite; will-change: transform; }
        @keyframes floaty { 50% { transform: translateY(-5px); } }
        .spark { transform-box: fill-box; transform-origin: center; animation: twinkle 2.2s ease-in-out infinite; }
        @keyframes twinkle { 0%, 100% { transform: scale(.45); opacity: .3; } 50% { transform: scale(1); opacity: 1; } }

        .pill {
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 12px;
            padding: 6px 14px; border-radius: 9999px;
            background: var(--pill-bg); border: 1px solid var(--pill-line);
            color: var(--pill-ink); font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
        }
        .pill i { width: 7px; height: 7px; border-radius: 9999px; background: var(--pill-dot); animation: blink 1.4s ease-in-out infinite; }
        @keyframes blink { 50% { opacity: .25; } }

        h1 { font-weight: 800; font-size: 28px; line-height: 1.15; color: #fff; letter-spacing: -.01em; }
        @media (min-width: 640px) { h1 { font-size: 34px; } }
        .msg { margin-top: 12px; font-size: 15px; line-height: 1.7; color: #94a3b8; white-space: pre-line; }

        .bar { position: relative; margin: 26px auto 0; height: 6px; width: 100%; max-width: 260px; border-radius: 9999px; background: #1e293b; overflow: hidden; }
        .bar i { position: absolute; inset: 0; width: 100%; border-radius: 9999px; background: linear-gradient(90deg, var(--bar1), var(--bar2)); transform: translateX(-100%); animation: load 1.8s ease-in-out infinite; will-change: transform; }
        @keyframes load { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

        .eta { margin-top: 24px; padding: 14px 16px; border-radius: 20px; background: var(--eta-bg); border: 1px solid var(--eta-line); }
        .eta small { display: block; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--eta-ink); font-weight: 700; }
        .eta strong { display: block; margin-top: 4px; font-weight: 800; font-size: 22px; color: #fff; font-variant-numeric: tabular-nums; }
        .eta em { display: block; margin-top: 2px; font-style: normal; font-size: 12px; color: #64748b; }

        .actions { margin-top: 26px; display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 9999px; font-size: 14px; font-weight: 600; text-decoration: none; }
        .btn:active { opacity: .85; }
        .btn.wa { background: #16a34a; color: #fff; }
        .btn.ig { background: #111a2e; color: #e2e8f0; border: 1px solid var(--line); }
        .btn svg { width: 18px; height: 18px; flex: none; }
        @media (hover: hover) { .btn.wa:hover { background: #22c55e; } .btn.ig:hover { background: #172036; } }

        .foot { margin-top: 26px; font-size: 12px; color: #475569; }
        .foot b { color: #64748b; font-weight: 500; }

        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; } }
    </style>
</head>
<body class="{{ $preview ? 'is-preview' : '' }}">
    @if($preview)
        <div class="previewbar">
            <span>Pratinjau: ini yang dilihat pengunjung saat mode maintenance aktif.</span>
            <a href="{{ route('super-admin.maintenance') }}">&larr; Kembali ke pengaturan</a>
        </div>
    @endif

    <main class="card" role="main">
        <div class="brand">
            <div class="logo"><img src="{{ asset('images/logo.png') }}" alt="EHANZSTORE" width="36" height="36" decoding="async" onerror="this.style.display='none'"></div>
            <div class="name">EHANZSTORE<span class="tag">Marketplace COC</span></div>
        </div>

        <div class="visual">
            @if($image)
                {{-- Gambar kustom dari panel Super Admin. Bila file gagal dimuat, otomatis pakai ilustrasi bawaan. --}}
                <img class="hero-img" src="{{ $image }}" alt="Website sedang maintenance" decoding="async"
                     onerror="this.style.display='none';document.getElementById('illus-fallback').style.display='block'">
            @endif

            <div id="illus-fallback" @if($image) style="display:none" @endif>
                @if($theme === 'promo')
                <svg class="illus" viewBox="0 0 220 150" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ilustrasi hadiah dan label diskon">
                    <ellipse cx="110" cy="138" rx="52" ry="6" fill="#000" opacity=".35"/>
                    <g class="float">
                        <rect x="68" y="72" width="84" height="58" rx="9" fill="#ea580c"/>
                        <rect x="60" y="56" width="100" height="24" rx="8" fill="#fb923c"/>
                        <rect x="103" y="56" width="14" height="74" fill="#fde68a"/>
                        <path d="M110 56c-8-18-30-16-26-6 3 7 18 6 26 6z" fill="#fbbf24"/>
                        <path d="M110 56c8-18 30-16 26-6-3 7-18 6-26 6z" fill="#fcd34d"/>
                        <circle cx="110" cy="56" r="5" fill="#f59e0b"/>
                    </g>
                    <g transform="translate(172 46) rotate(14)">
                        <rect x="-26" y="-17" width="52" height="34" rx="9" fill="#ec4899"/>
                        <circle cx="-17" cy="0" r="3.2" fill="#140b1c"/>
                        <text x="7" y="8" text-anchor="middle" font-size="24" font-weight="800" fill="#fff" font-family="system-ui, sans-serif">%</text>
                    </g>
                    <g transform="translate(42 40)"><path class="spark" d="M0-9L2.4-2.4 9 0 2.4 2.4 0 9-2.4 2.4-9 0-2.4-2.4Z" fill="#fde68a"/></g>
                    <g transform="translate(30 98)"><path class="spark" style="animation-delay:.7s" d="M0-6L1.6-1.6 6 0 1.6 1.6 0 6-1.6 1.6-6 0-1.6-1.6Z" fill="#f9a8d4"/></g>
                    <g transform="translate(88 22)"><path class="spark" style="animation-delay:1.2s" d="M0-7L1.9-1.9 7 0 1.9 1.9 0 7-1.9 1.9-7 0-1.9-1.9Z" fill="#fdba74"/></g>
                    <g transform="translate(196 104)"><path class="spark" style="animation-delay:.3s" d="M0-8L2.1-2.1 8 0 2.1 2.1 0 8-2.1 2.1-8 0-2.1-2.1Z" fill="#fde68a"/></g>
                </svg>
                @else
                <svg class="illus" viewBox="0 0 220 150" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ilustrasi website sedang diperbaiki">
                    <rect x="16" y="14" width="188" height="122" rx="16" fill="#0f1a31" stroke="#1d3a78" stroke-width="2"/>
                    <path d="M16 30a16 16 0 0 1 16-16h156a16 16 0 0 1 16 16v10H16z" fill="#0b1220"/>
                    <circle cx="34" cy="27" r="3.5" fill="#ef4444"/><circle cx="46" cy="27" r="3.5" fill="#f59e0b"/><circle cx="58" cy="27" r="3.5" fill="#22c55e"/>
                    <rect x="34" y="56" width="84" height="10" rx="5" fill="#334155"/>
                    <rect x="34" y="74" width="116" height="7" rx="3.5" fill="#1e293b"/>
                    <rect x="34" y="88" width="92" height="7" rx="3.5" fill="#1e293b"/>
                    <rect x="34" y="110" width="100" height="9" rx="4.5" fill="#0b1220" stroke="#1e293b"/>
                    <rect x="35" y="111" width="46" height="7" rx="3.5" fill="#3b82f6"/>
                    <g transform="translate(130 66)"><g class="gear big"><path transform="translate(-30 -30) scale(2.5)" d="{{ $gear }}" fill="#3b82f6"/></g></g>
                    <g transform="translate(176 40)"><g class="gear small"><path transform="translate(-14 -14) scale(1.17)" d="{{ $gear }}" fill="#60a5fa"/></g></g>
                </svg>
                @endif
            </div>
        </div>

        <span class="pill"><i></i> {{ $t['pill'] }}</span>

        <h1>{{ $title }}</h1>
        <p class="msg">{{ $message }}</p>

        <div class="bar" aria-hidden="true"><i></i></div>

        @if($untilFuture)
            <div class="eta">
                <small>{{ $t['eta'] }}</small>
                <strong id="eta-count" data-until="{{ $until->toIso8601String() }}">{{ $untilLabel }}</strong>
                <em>{{ $untilLabel }}</em>
            </div>
        @endif

        <div class="actions">
            <a class="btn wa" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($t['wa_text']) }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                {{ $t['wa_label'] }}
            </a>
            <a class="btn ig" href="https://instagram.com/{{ $ig }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
                &#64;{{ $ig }}
            </a>
        </div>

        <p class="foot">
            @unless($preview)<b>Halaman ini akan dimuat ulang otomatis.</b>@endunless
            &copy; {{ date('Y') }} EHANZSTORE.ID
        </p>
    </main>

    <script>
        (function () {
            var el = document.getElementById('eta-count');

            if (el) {
                var end = Date.parse(el.getAttribute('data-until'));
                var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
                var tick = function () {
                    var left = Math.floor((end - Date.now()) / 1000);
                    if (left <= 0) { el.textContent = @json($t['eta_done']); return; }
                    var d = Math.floor(left / 86400), h = Math.floor((left % 86400) / 3600),
                        m = Math.floor((left % 3600) / 60), s = left % 60;
                    el.textContent = (d > 0 ? d + ' hari ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
                };
                tick();
                // Hitung mundur berhenti saat tab tidak terlihat (hemat baterai/CPU)
                var timer = setInterval(function () { if (!document.hidden) { tick(); } }, 1000);
            }

            @unless($preview)
            // Cek ulang tiap 60 detik: begitu maintenance dimatikan, halaman otomatis berganti ke website.
            // Tab yang tersembunyi tidak memuat ulang; saat dibuka lagi dan sudah >60 dtk, langsung dimuat ulang.
            var last = Date.now();
            setInterval(function () {
                if (!document.hidden && Date.now() - last >= 60000) { location.reload(); }
            }, 5000);
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden && Date.now() - last >= 60000) { location.reload(); }
            });
            @endunless
        })();
    </script>
</body>
</html>