<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - EHANZ Admin</title>
    @include('layouts.partials.favicon')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        midnight: '#020617',
                        royal: '#0645AD',
                        electric: '#3b82f6',
                        brand: { 300: '#93c5fd', 400: '#60a5fa', 500: '#3b82f6', 600: '#0645AD', 950: '#0b1220' },
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'], display: ['Space Grotesk', 'sans-serif'] }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        html, body { max-width: 100%; overflow-x: clip; }
        body { background-color: #020617; color: #e2e8f0; }
        .glass { background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .glass-strong { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(24px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .glass-panel { background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .sidebar-link { @apply flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm text-slate-400 border border-transparent hover:text-white hover:bg-white/5 transition-all; }
        .sidebar-link.active { @apply bg-electric/10 text-electric border-electric/20 font-medium; }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #020617; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #3b82f6; }
    </style>
</head>
<body class="antialiased min-h-screen selection:bg-electric selection:text-white">

    @php
        $user    = auth()->user();
        $isSuper = $user->isSuperAdmin();

        // Jumlah stok lama (belum laku > N hari) untuk lencana di menu
        try {
            $staleBadge = \App\Models\CocAccount::stale()
                ->when(! $isSuper, fn ($q) => $q->where('created_by', $user->id))
                ->count();
        } catch (\Throwable $e) {
            $staleBadge = 0;
        }

        $sections = [
            'Menu' => [
                ['href' => route($isSuper ? 'super-admin.dashboard' : 'admin.dashboard'), 'label' => 'Dashboard', 'active' => request()->routeIs('*.dashboard'),
                 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                ['href' => route('admin.accounts.index'), 'label' => 'Kelola Akun', 'active' => request()->routeIs('admin.accounts.*'),
                 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                ['href' => route('admin.stale.index'), 'label' => 'Stok Lama', 'active' => request()->routeIs('admin.stale.*'), 'badge' => $staleBadge,
                 'icon' => 'M5 3h14M5 21h14M7 3v3a5 5 0 002 4l3 2-3 2a5 5 0 00-2 4v3m10-18v3a5 5 0 01-2 4l-3 2 3 2a5 5 0 012 4v3'],
                ['href' => route('admin.banners.index'), 'label' => 'Banner Beranda', 'active' => request()->routeIs('admin.banners.*'),
                 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ],
        ];

        if ($isSuper) {
            $sections['Super Admin'] = [
                ['href' => route('super-admin.analytics'), 'label' => 'Analitik', 'active' => request()->routeIs('super-admin.analytics'),
                 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                ['href' => route('super-admin.sales'), 'label' => 'Transaksi', 'active' => request()->routeIs('super-admin.sales'),
                 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['href' => route('super-admin.reports.monthly'), 'label' => 'Laporan Bulanan', 'active' => request()->routeIs('super-admin.reports.*'),
                 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['href' => route('super-admin.reviews'), 'label' => 'Ulasan Pembeli', 'active' => request()->routeIs('super-admin.reviews*'),
                 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                ['href' => route('super-admin.admins'), 'label' => 'Manajemen Admin', 'active' => request()->routeIs('super-admin.admins'),
                 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                ['href' => route('super-admin.activity-logs'), 'label' => 'Riwayat Aktivitas', 'active' => request()->routeIs('super-admin.activity-logs'),
                 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['href' => route('super-admin.security'), 'label' => 'Keamanan', 'active' => request()->routeIs('super-admin.security*'),
                 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
            ];
        }

        $flatNav = collect($sections)->flatten(1);
    @endphp

    <!-- Sidebar (desktop) : fixed di kiri -->
    <aside class="hidden lg:flex flex-col fixed inset-y-0 left-0 w-72 glass-strong border-r border-white/5 z-40">
        <div class="p-6 flex items-center gap-3 border-b border-white/5">
            <div class="relative w-11 h-11 shrink-0 flex items-center justify-center bg-white rounded-xl overflow-hidden shadow-lg shadow-electric/20">
                <img src="{{ asset('images/logo.png') }}" class="w-10 h-10 object-contain" alt="EHANZSTORE"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                <div class="hidden absolute inset-0 bg-electric text-white font-display font-bold text-xl items-center justify-center">E</div>
            </div>
            <div>
                <h1 class="font-display font-bold text-white leading-none">EHANZSTORE</h1>
                <p class="text-[10px] text-slate-500 uppercase tracking-widest mt-1">{{ $isSuper ? 'Super Admin' : 'Panel Admin' }}</p>
            </div>
        </div>

        <nav class="flex-1 min-h-0 p-4 overflow-y-auto custom-scrollbar">
            @foreach($sections as $title => $items)
                <p class="px-4 mt-4 first:mt-0 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-600">{{ $title }}</p>
                <div class="space-y-1">
                    @foreach($items as $item)
                        <a href="{{ $item['href'] }}" class="sidebar-link {{ $item['active'] ? 'active' : '' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                            {{ $item['label'] }}
                            @if(!empty($item['badge']))
                                <span class="ml-auto min-w-[1.25rem] h-5 px-1.5 rounded-full bg-amber-500/90 text-[10px] font-bold text-slate-900 flex items-center justify-center">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach

            <p class="px-4 mt-6 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-600">Lainnya</p>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="sidebar-link">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Lihat Website
            </a>
        </nav>

        <div class="p-4 border-t border-white/5 space-y-3 shrink-0">
            <!-- Jam realtime (WIB) -->
            <div class="px-4 py-3 rounded-xl bg-electric/10 border border-electric/20 text-center">
                <p class="font-mono text-xl font-bold text-white tracking-wider leading-none" data-live-clock="time">{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}</p>
                <p class="text-[11px] text-slate-400 mt-1.5" data-live-clock="date">{{ now()->timezone('Asia/Jakarta')->format('d M Y') }}</p>
                <p class="text-[9px] uppercase tracking-widest text-electric/80 mt-1">WIB</p>
            </div>
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/5">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-royal to-electric flex items-center justify-center text-white text-sm font-bold shrink-0">
                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ $user->name }}</p>
                    <p class="text-[11px] text-slate-500 truncate">{{ $isSuper ? 'Super Administrator' : 'Administrator' }}</p>
                </div>
            </div>
            <button onclick="openLogout()" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm text-red-400 hover:bg-red-500/10 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Keluar Sistem
            </button>
        </div>
    </aside>

    <!-- Konten: diberi padding kiri selebar sidebar di desktop -->
    <main class="min-w-0 w-full lg:pl-72 relative">
        <div class="p-4 sm:p-6 lg:px-10 lg:py-8">
            <!-- Header mobile -->
            <div class="lg:hidden mb-6 sticky top-3 z-30">
                <div class="flex justify-between items-center glass-strong p-4 rounded-2xl">
                    <span class="font-display font-bold text-white">EHANZ {{ $isSuper ? 'SUPER ADMIN' : 'ADMIN' }}</span>
                    <span class="ml-auto mr-3 font-mono text-xs text-electric" data-live-clock="time">{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}</span>
                    <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="text-white" aria-label="Menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
                <div id="mobileMenu" class="hidden glass-strong mt-2 p-3 rounded-2xl space-y-1 max-h-[70vh] overflow-y-auto">
                    @foreach($flatNav as $item)
                        <a href="{{ $item['href'] }}" class="sidebar-link {{ $item['active'] ? 'active' : '' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                            {{ $item['label'] }}
                            @if(!empty($item['badge']))
                                <span class="ml-auto min-w-[1.25rem] h-5 px-1.5 rounded-full bg-amber-500/90 text-[10px] font-bold text-slate-900 flex items-center justify-center">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="sidebar-link">Lihat Website</a>
                    <button onclick="openLogout()" class="w-full text-left px-4 py-2.5 rounded-xl text-sm text-red-400 hover:bg-red-500/10">Keluar Sistem</button>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/20 text-green-300 text-sm flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Modal keluar -->
    <div id="logoutModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="glass-strong w-full max-w-md rounded-3xl p-8 border border-white/10 shadow-2xl">
            <div class="w-14 h-14 bg-red-500/10 rounded-full flex items-center justify-center mb-4 text-red-400">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </div>
            <h3 class="text-xl font-display font-bold text-white mb-2">Konfirmasi Keluar</h3>
            <p class="text-sm text-slate-400 mb-6">Apakah Anda yakin ingin mengakhiri sesi admin?</p>
            <div class="flex gap-3">
                <button onclick="closeLogout()" class="flex-1 py-3 rounded-xl border border-white/10 text-slate-300 hover:bg-white/5 transition">Batal</button>
                <form action="{{ route('logout') }}" method="POST" class="flex-1">@csrf<button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold transition">Ya, Keluar</button></form>
            </div>
        </div>
    </div>

    <script>
        function openLogout()  { var m = document.getElementById('logoutModal'); m.classList.remove('hidden'); m.classList.add('flex'); }
        function closeLogout() { var m = document.getElementById('logoutModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
        document.getElementById('logoutModal').addEventListener('click', function (e) { if (e.target === this) closeLogout(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeLogout(); });
    </script>

    @stack('outside-forms')
    <script src="{{ asset('js/live-time.js') }}?v={{ @filemtime(public_path('js/live-time.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>