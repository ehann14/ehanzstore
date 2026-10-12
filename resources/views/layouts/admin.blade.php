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
                    colors: { midnight: '#020617', royal: '#0645AD', electric: '#3b82f6' },
                    fontFamily: { sans: ['Inter', 'sans-serif'], display: ['Space Grotesk', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { background-color: #020617; color: #e2e8f0; }
        .glass { background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .sidebar-link { @apply flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition-all; }
        .sidebar-link.active { @apply bg-electric/10 text-electric border border-electric/20; }
    </style>
</head>
<body class="antialiased min-h-screen flex">
    
    <!-- Sidebar -->
    <aside class="w-72 fixed h-full glass-strong border-r border-white/5 hidden lg:flex flex-col z-40">
        <div class="p-8 flex items-center gap-3 border-b border-white/5">
            <div class="w-10 h-10 bg-gradient-to-br from-royal to-electric rounded-xl flex items-center justify-center">
                <img src="{{ asset('images/logo.png') }}" class="w-6 h-6 brightness-0 invert">
            </div>
            <div>
                <h1 class="font-display font-bold text-white leading-none">ADMIN</h1>
                <p class="text-[10px] text-slate-500 uppercase tracking-widest">Panel Kontrol</p>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="{{ route(auth()->user()->isSuperAdmin() ? 'super-admin.dashboard' : 'admin.dashboard') }}" 
               class="sidebar-link {{ request()->routeIs('*.dashboard') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>
            <a href="{{ route('admin.accounts.index') }}" 
               class="sidebar-link {{ request()->routeIs('admin.accounts.*') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                Kelola Akun
            </a>
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('super-admin.sales') }}" class="sidebar-link {{ request()->routeIs('super-admin.sales') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Transaksi
            </a>
            @endif
        </nav>

        <div class="p-4 border-t border-white/5">
            <button onclick="document.getElementById('logoutModal').classList.remove('hidden')" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Keluar Sistem
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 lg:ml-72 min-h-screen p-4 lg:p-8 relative">
        <!-- Mobile Header -->
        <div class="lg:hidden flex justify-between items-center mb-6 glass p-4 rounded-2xl">
            <span class="font-display font-bold text-white">EHANZ ADMIN</span>
            <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/20 text-green-300 text-sm flex items-center gap-3 animate-pulse">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif
        
        @yield('content')
    </main>

    <!-- Logout Modal (Shared) -->
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