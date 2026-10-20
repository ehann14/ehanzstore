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
<body class="antialiased min-h-screen flex selection:bg-electric selection:text-white">

    @php
        $user    = auth()->user();
        $isSuper = $user->isSuperAdmin();

        $sections = [
            'Menu' => [
                ['href' => route($isSuper ? 'super-admin.dashboard' : 'admin.dashboard'), 'label' => 'Dashboard', 'active' => request()->routeIs('*.dashboard'),
                 'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                ['href' => route('admin.accounts.index'), 'label' => 'Kelola Akun', 'active' => request()->routeIs('admin.accounts.*'),
                 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
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
                ['href' => route('super-admin.admins'), 'label' => 'Manajemen Admin', 'active' => request()->routeIs('super-admin.admins'),
                 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                ['href' => route('super-admin.activity-logs'), 'label' => 'Riwayat Aktivitas', 'active' => request()->routeIs('super-admin.activity-logs'),
                 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
            ];
        }

        $flatNav = collect($sections)->flatten(1);
    @endphp

    <!-- Sidebar (desktop) -->
    <aside class="w-72 fixed h-full glass-strong border-r border-white/5 hidden lg:flex flex-col z-40">
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

        <nav class="flex-1 p-4 overflow-y-auto custom-scrollbar">
            @foreach($sections as $title => $items)
                <p class="px-4 mt-4 first:mt-0 mb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-600">{{ $title }}</p>
                <div class="space-y-1">
                    @foreach($items as $item)
                        <a href="{{ $item['href'] }}" class="sidebar-link {{ $item['active'] ? 'active' : '' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                            {{ $item['label'] }}
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

        <div class="p-4 border-t border-white/5 space-y-3">
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

    <!-- Konten -->
    <main class="flex-1 lg:ml-72 min-h-screen p-4 lg:p-8 relative min-w-0">
        <!-- Header mobile -->
        <div class="lg:hidden mb-6 sticky top-3 z-30">
            <div class="flex justify-between items-center glass-strong p-4 rounded-2xl">
                <span class="font-display font-bold text-white">EHANZ {{ $isSuper ? 'SUPER ADMIN' : 'ADMIN' }}</span>
                <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="text-white" aria-label="Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
            <div id="mobileMenu" class="hidden glass-strong mt-2 p-3 rounded-2xl space-y-1 max-h-[70vh] overflow-y-auto">
                @foreach($flatNav as $item)
                    <a href="{{ $item['href'] }}" class="sidebar-link {{ $item['active'] ? 'active' : '' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                        {{ $item['label'] }}
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
    </main>

    <!-- Modal keluar -->
    <div id="logoutModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
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
        function openLogout()  { document.getElementById('logoutModal').classList.remove('hidden'); }
        function closeLogout() { document.getElementById('logoutModal').classList.add('hidden'); }
        document.getElementById('logoutModal').addEventListener('click', function (e) { if (e.target === this) closeLogout(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeLogout(); });
    </script>

    @stack('outside-forms')
    @stack('scripts')
</body>
</html>