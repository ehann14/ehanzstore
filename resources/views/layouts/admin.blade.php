<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - EHANZSTORE Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 500: '#3b82f6', 600: '#2563eb', 900: '#0f172a', 950: '#020617' }
                    },
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-panel { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); }
    </style>
</head>
<body class="bg-brand-950 text-slate-200 min-h-screen">
    
    <!-- Simple Admin Navbar -->
    <nav class="fixed top-0 w-full z-50 glass-panel border-b border-white/5 h-16 flex items-center px-6 justify-between">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" class="h-8 brightness-0 invert opacity-80">
            <span class="font-bold text-white tracking-tight">ADMIN PANEL</span>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-xs text-slate-400 hidden sm:block">{{ auth()->user()->name }}</span>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs text-red-400 hover:text-red-300 font-medium border border-red-500/20 px-3 py-1.5 rounded-lg hover:bg-red-500/10 transition">Logout</button>
            </form>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="pt-24 px-4 md:px-8 max-w-7xl mx-auto">
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/20 text-green-300 text-sm flex items-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif
        
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>