<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - EHANZSTORE.ID</title>
    @include('layouts.partials.favicon')
    
    <!-- Fonts & Tailwind -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            500: '#3b82f6',
                            600: '#2563eb',
                            900: '#0f172a',
                            950: '#020617',
                        }
                    },
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card { 
            background: rgba(15, 23, 42, 0.6); 
            backdrop-filter: blur(20px); 
            border: 1px solid rgba(255, 255, 255, 0.08); 
        }
    </style>
</head>
<body class="bg-brand-950 min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    
    <!-- Background Glow -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-brand-600/10 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo Header -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-4 flex items-center justify-center bg-white rounded-2xl overflow-hidden shadow-lg shadow-electric/20">
                <img src="{{ asset('images/logo.png') }}" alt="EHANZ Logo" class="w-[72px] h-[72px] object-contain">
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Admin Dashboard</h1>
            <p class="text-slate-400 text-sm mt-2">Masuk untuk mengelola katalog akun COC</p>
        </div>

        <!-- Login Card -->
        <div class="glass-card rounded-3xl p-8 shadow-2xl">
            
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-200 text-sm flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf
                
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all"
                           placeholder="admin@ehanzstore.id">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Password</label>
                    <input type="password" name="password" required
                           class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3.5 text-white placeholder-slate-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all"
                           placeholder="••••••••">
                </div>

                <button type="submit" 
                        class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-3.5 rounded-xl transition-all shadow-[0_0_20px_rgba(37,99,235,0.3)] hover:shadow-[0_0_30px_rgba(37,99,235,0.5)] active:scale-[0.98]">
                    Masuk ke Dashboard
                </button>
            </form>

            <div class="mt-8 text-center">
                <a href="{{ route('home') }}" class="text-xs text-slate-500 hover:text-brand-400 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Beranda
                </a>
            </div>
        </div>

        <p class="text-center text-[10px] text-slate-600 mt-8">
            &copy; {{ date('Y') }} EHANZSTORE.ID. Secure Admin Access Only.
        </p>
    </div>
</body>
</html>