<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - EHANZ Admin</title>
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
                        brand: { 400: '#60a5fa', 500: '#3b82f6', 600: '#0645AD', 950: '#0b1220' },
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
        .sidebar-link { @apply flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition-all; }
        .sidebar-link.active { @apply bg-electric/10 text-electric border border-electric/20; }
    </style>
</head>
<body class="antialiased min-h-screen flex">

    @php
        $isSuper = auth()->user()->isSuperAdmin();
        $nav = [
            ['href' => route($isSuper ? 'super-admin.dashboard' : 'admin.dashboard'), 'label' => 'Dashboard', 'active' => request()->routeIs('*.dashboard'),
             'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
            ['href' => route('admin.accounts.index'), 'label' => 'Kelola Akun', 'active' => request()->routeIs('admin.accounts.*'),
             'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
            ['href' => route('admin.banners.index'), 'label' => 'Banner Beranda', 'active' => request()->routeIs('admin.banners.*'),
             'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
        ];
        if ($isSuper) {
            $nav[] = ['href' => route('super-admin.sales'), 'label' => 'Transaksi', 'active' => request()->routeIs('super-admin.sales'),
                      'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];
        }
    @endphp

    <!-- Sidebar (desktop) -->
    <aside class="w-72 fixed h-full glass-strong border-r border-white/5 hidden lg:flex flex-col z-40">
        <div class="p-8 flex items-center gap-3 border-b border-white/5">
            <div class="w-10 h-10 bg-gradient-to-br from-royal to-electric rounded-xl flex items-center justify-center">
                <img src="{{ asset('images/logo.png') }}" class="w-6 h-6 brightness-0 invert" alt="">
            </div>
            <div>
                <h1 class="font-display font-bold text-white leading-none">ADMIN</h1>
                <p class="text-[10px] text-slate-500 uppercase tracking-widest">Panel Kontrol</p>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            @foreach($nav as $item)
                <a href="{{ $item['href'] }}" class="sidebar-link {{ $item['active'] ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
            <a href="{{ route('home') }}" target="_blank" class="sidebar-link">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Lihat Website
            </a>
        </nav>

        <div class="p-4 border-t border-white/5">
            <button onclick="document.getElementById('logoutModal').classList.remove('hidden')" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Keluar Sistem
            </button>
        </div>
    </aside>

    <!-- Konten -->
    <main class="flex-1 lg:ml-72 min-h-screen p-4 lg:p-8 relative min-w-0">
        <!-- Header mobile -->
        <div class="lg:hidden mb-6">
            <div class="flex justify-between items-center glass p-4 rounded-2xl">
                <span class="font-display font-bold text-white">EHANZ ADMIN</span>
                <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="text-white" aria-label="Menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
            <div id="mobileMenu" class="hidden glass mt-2 p-3 rounded-2xl space-y-1">
                @foreach($nav as $item)
                    <a href="{{ $item['href'] }}" class="sidebar-link {{ $item['active'] ? 'active' : '' }}">{{ $item['label'] }}</a>
                @endforeach
                <button onclick="document.getElementById('logoutModal').classList.remove('hidden')" class="w-full text-left px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10">Keluar Sistem</button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/20 text-green-300 text-sm flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Modal keluar -->
    <div id="logoutModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div class="glass-strong w-full max-w-md rounded-3xl p-8 border border-white/10 shadow-2xl">
            <h3 class="text-xl font-display font-bold text-white mb-2">Konfirmasi Keluar</h3>
            <p class="text-sm text-slate-400 mb-6">Apakah Anda yakin ingin mengakhiri sesi admin?</p>
            <div class="flex gap-3">
                <button onclick="document.getElementById('logoutModal').classList.add('hidden')" class="flex-1 py-3 rounded-xl border border-white/10 text-slate-300 hover:bg-white/5 transition">Batal</button>
                <form action="{{ route('logout') }}" method="POST" class="flex-1">@csrf<button type="submit" class="w-full py-3 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold transition">Ya, Keluar</button></form>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>