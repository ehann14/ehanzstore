{{--
    Halaman maintenance (dipakai oleh mode maintenance dari panel Super Admin
    dan oleh "php artisan down" lewat errors/503.blade.php).
    Sengaja berdiri sendiri (CSS & ilustrasi SVG di dalam file) supaya tetap tampil walau database/asset bermasalah.
    Variabel opsional:
      $title, $message, $until (Carbon|null)
      $image   : URL gambar kustom dari panel Super Admin ('' = pakai ilustrasi bawaan)
      $preview : true saat dibuka dari tombol "Lihat halaman maintenance" di panel Super Admin
--}}
@php
    $title   = $title   ?? \App\Support\Maintenance::DEFAULT_TITLE;
    $message = $message ?? \App\Support\Maintenance::DEFAULT_MESSAGE;
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
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} - EHANZSTORE.ID</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
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
        body.is-preview { padding-top: 76px; }
        .blob { position: fixed; border-radius: 9999px; filter: blur(90px); opacity: .35; pointer-events: none; z-index: 0; }
        .blob.a { width: 420px; height: 420px; background: var(--royal); top: -120px; left: -100px; animation: drift 14s ease-in-out infinite alternate; }
        .blob.b { width: 360px; height: 360px; background: var(--electric); bottom: -140px; right: -80px; opacity: .22; animation: drift 18s ease-in-out infinite alternate-reverse; }
        @keyframes drift { from { transform: translate(0, 0); } to { transform: translate(40px, 30px); } }

        /* Bar pratinjau (hanya tampil untuk Super Admin dari panel) */
        .previewbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 10;
            display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 6px 14px;
            padding: 12px 16px; background: #f59e0b; color: #0f172a; font-size: 13px; font-weight: 600; text-align: center;
        }
        .previewbar a { color: #0f172a; font-weight: 800; text-decoration: underline; }

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

        .brand { display: inline-flex; align-items: center; gap: 12px; margin-bottom: 26px; }
        .brand .logo { width: 46px; height: 46px; border-radius: 16px; background: #fff; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .brand .logo img { width: 38px; height: 38px; object-fit: contain; }
        .brand .name { font-family: 'Nunito', 'Inter', sans-serif; font-weight: 900; font-size: 20px; color: #fff; text-align: left; line-height: 1.1; }
        .brand .tag { font-size: 11px; color: #94a3b8; font-weight: 500; margin-top: 3px; display: block; }

        /* Gambar / ilustrasi maintenance */
        .visual { margin: 0 auto 22px; }
        .visual .hero-img {
            display: block; margin: 0 auto;
            max-width: 100%; max-height: 260px; width: auto; height: auto;
            object-fit: contain; border-radius: 22px;
            border: 1px solid rgba(255, 255, 255, .08);
            box-shadow: 0 18px 50px -20px rgba(0, 0, 0, .7);
        }
        .illus { display: block; width: 100%; max-width: 340px; height: auto; margin: 0 auto; overflow: visible; }
        /* Gerakan ilustrasi memakai animasi SMIL di dalam SVG (aman di browser/WebView lama); hanya kilau yang memakai CSS. */
        .illus .tw     { animation: tw 2.6s ease-in-out infinite; }
        .illus .tw.d1  { animation-delay: .5s; }
        .illus .tw.d2  { animation-delay: 1.1s; }
        .illus .tw.d3  { animation-delay: 1.7s; }
        @keyframes tw    { 0%, 100% { opacity: .15; } 50% { opacity: 1; } }

        .pill {
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 14px;
            padding: 6px 14px; border-radius: 9999px;
            background: rgba(245, 158, 11, .1); border: 1px solid rgba(245, 158, 11, .28);
            color: #fbbf24; font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase;
        }
        .pill i { width: 7px; height: 7px; border-radius: 9999px; background: #f59e0b; animation: blink 1.4s ease-in-out infinite; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: .25; } }

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
<body class="{{ $preview ? 'is-preview' : '' }}">
    @if($preview)
        <div class="previewbar">
            <span>Pratinjau: ini yang dilihat pengunjung saat mode maintenance aktif.</span>
            <a href="{{ route('super-admin.maintenance') }}">&larr; Kembali ke pengaturan</a>
        </div>
    @endif

    <div class="blob a"></div>
    <div class="blob b"></div>

    <main class="card" role="main">
        <div class="brand">
            <div class="logo"><img src="{{ asset('images/logo.png') }}" alt="EHANZSTORE" onerror="this.style.display='none'"></div>
            <div class="name">EHANZSTORE<span class="tag">Marketplace COC</span></div>
        </div>

        <div class="visual">
            @if($image)
                {{-- Gambar kustom dari panel Super Admin. Bila file gagal dimuat, otomatis pakai ilustrasi bawaan. --}}
                <img class="hero-img" src="{{ $image }}" alt="Website sedang maintenance"
                     onerror="this.style.display='none';document.getElementById('illus-fallback').style.display='block'">
            @endif

            <div id="illus-fallback" @if($image) style="display:none" @endif>
                <svg class="illus" viewBox="0 0 360 240" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Ilustrasi website sedang diperbaiki">
                    <defs>
                        <linearGradient id="mWin" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#172554"/><stop offset="1" stop-color="#0b1220"/>
                        </linearGradient>
                        <linearGradient id="mBar" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0" stop-color="#0645AD"/><stop offset=".6" stop-color="#3b82f6"/><stop offset="1" stop-color="#93c5fd"/>
                        </linearGradient>
                        <linearGradient id="mGear" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#60a5fa"/><stop offset="1" stop-color="#2563eb"/>
                        </linearGradient>
                        <linearGradient id="mSteel" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#e2e8f0"/><stop offset="1" stop-color="#94a3b8"/>
                        </linearGradient>
                        <radialGradient id="mGlow" cx=".5" cy=".5" r=".5">
                            <stop offset="0" stop-color="#3b82f6" stop-opacity=".35"/><stop offset="1" stop-color="#3b82f6" stop-opacity="0"/>
                        </radialGradient>
                    </defs>

                    <ellipse cx="180" cy="120" rx="170" ry="110" fill="url(#mGlow)"/>
                    <ellipse cx="180" cy="221" rx="140" ry="9" fill="#000" opacity=".35"/>

                    {{-- Jendela browser + roda gigi --}}
                    <g>
                        <animateTransform attributeName="transform" type="translate" values="0 0;0 -5;0 0" dur="5s" repeatCount="indefinite" calcMode="spline" keyTimes="0;.5;1" keySplines=".45 0 .55 1;.45 0 .55 1"/>
                        <rect x="52" y="34" width="256" height="164" rx="18" fill="url(#mWin)" stroke="#3b82f6" stroke-opacity=".45" stroke-width="2"/>
                        <path d="M52 52A18 18 0 0 1 70 34H290A18 18 0 0 1 308 52V62H52Z" fill="#0b1220"/>
                        <circle cx="72" cy="48" r="4" fill="#ef4444"/>
                        <circle cx="86" cy="48" r="4" fill="#f59e0b"/>
                        <circle cx="100" cy="48" r="4" fill="#22c55e"/>
                        <rect x="120" y="41" width="130" height="14" rx="7" fill="#1e293b"/>
                        <text x="185" y="51" text-anchor="middle" font-size="8" fill="#64748b" font-family="Inter, system-ui, sans-serif">ehanzstore.id</text>

                        <rect x="76" y="82" width="112" height="12" rx="6" fill="#334155"/>
                        <rect x="76" y="102" width="168" height="8" rx="4" fill="#1e293b"/>
                        <rect x="76" y="118" width="136" height="8" rx="4" fill="#1e293b"/>

                        <rect x="76" y="146" width="208" height="12" rx="6" fill="#0b1220" stroke="#1e293b"/>
                        <rect x="77" y="147" width="60" height="10" rx="5" fill="url(#mBar)">
                            <animate attributeName="width" values="60;176;60" dur="6s" repeatCount="indefinite" calcMode="spline" keyTimes="0;.5;1" keySplines=".45 0 .55 1;.45 0 .55 1"/>
                        </rect>

                        <g transform="translate(300 14)">
                            <g>
                                <animateTransform attributeName="transform" type="rotate" from="360 21 21" to="0 21 21" dur="6s" repeatCount="indefinite"/>
                                <path transform="scale(1.75)" d="{{ $gear }}" fill="#60a5fa"/>
                            </g>
                        </g>
                        <g transform="translate(254 138)">
                            <g>
                                <animateTransform attributeName="transform" type="rotate" from="0 38.4 38.4" to="360 38.4 38.4" dur="9s" repeatCount="indefinite"/>
                                <path transform="scale(3.2)" d="{{ $gear }}" fill="url(#mGear)"/>
                            </g>
                        </g>
                    </g>

                    {{-- Kunci inggris --}}
                    <g transform="translate(104 180) rotate(24)">
                        <g>
                            <animateTransform attributeName="transform" type="rotate" values="-7 0 0;7 0 0;-7 0 0" dur="2.6s" repeatCount="indefinite" calcMode="spline" keyTimes="0;.5;1" keySplines=".45 0 .55 1;.45 0 .55 1"/>
                            <path d="M-12 -12A17 17 0 1 1 -12 12" fill="none" stroke="url(#mSteel)" stroke-width="10" stroke-linecap="round"/>
                            <rect x="12" y="-6" width="78" height="12" rx="6" fill="url(#mSteel)"/>
                            <circle cx="82" cy="0" r="2.4" fill="#475569"/>
                        </g>
                    </g>

                    {{-- Kerucut pengaman --}}
                    <g transform="translate(36 216)">
                        <g>
                            <animateTransform attributeName="transform" type="rotate" values="-2 0 -7;2 0 -7;-2 0 -7" dur="3.4s" repeatCount="indefinite" calcMode="spline" keyTimes="0;.5;1" keySplines=".45 0 .55 1;.45 0 .55 1"/>
                            <rect x="-22" y="-7" width="44" height="8" rx="2.5" fill="#c2410c"/>
                            <path d="M-16 -7L-7 -53H7L16 -7Z" fill="#fb923c"/>
                            <path d="M-12.9 -23H12.9L11.3 -31H-11.3Z" fill="#fff"/>
                            <rect x="-8" y="-56" width="16" height="4" rx="2" fill="#ea580c"/>
                        </g>
                    </g>

                    {{-- Kilau --}}
                    <g fill="#93c5fd">
                        <circle class="tw" cx="30" cy="70" r="2.5"/>
                        <circle class="tw d1" cx="340" cy="104" r="2"/>
                        <circle class="tw d2" cx="22" cy="118" r="1.8"/>
                        <path class="tw d3" d="M344 138v10M339 143h10" stroke="#93c5fd" stroke-width="2" stroke-linecap="round" fill="none"/>
                    </g>
                </svg>
            </div>
        </div>

        <span class="pill"><i></i> Sedang dalam perbaikan</span>

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

        <p class="foot">
            @unless($preview)<b>Halaman ini akan dimuat ulang otomatis.</b>@endunless
            &copy; {{ date('Y') }} EHANZSTORE.ID
        </p>
    </main>

    <script>
        (function () {
            // Animasi SMIL tidak ikut aturan CSS reduced-motion, jadi dihentikan manual.
            try {
                if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    var sv = document.querySelector('.illus');
                    if (sv && sv.pauseAnimations) { sv.pauseAnimations(); }
                }
            } catch (e) {}

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

            @unless($preview)
            // Cek ulang tiap 60 detik: begitu maintenance dimatikan, halaman otomatis berganti ke website.
            setTimeout(function () { location.reload(); }, 60000);
            @endunless
        })();
    </script>
</body>
</html>