<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Verifikasi Dua Langkah - EHANZSTORE.ID</title>
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

    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-brand-600/10 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10" x-data="{ recovery: false }">
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-4 flex items-center justify-center bg-white rounded-2xl overflow-hidden shadow-lg shadow-blue-500/20">
                <img src="{{ asset('images/logo.png') }}" alt="EHANZ Logo" class="w-[72px] h-[72px] object-contain">
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Verifikasi Dua Langkah</h1>
            <p class="text-slate-400 text-sm mt-2" x-show="!recovery">Buka aplikasi authenticator di HP Anda, lalu masukkan kode 6 digit.</p>
            <p class="text-slate-400 text-sm mt-2" x-show="recovery" x-cloak>Masukkan salah satu kode pemulihan. Setiap kode hanya berlaku sekali.</p>
        </div>

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

            <form action="{{ route('two-factor.verify') }}" method="POST" class="space-y-5" autocomplete="off">
                @csrf

                <div>
                    <label for="code" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">
                        <span x-show="!recovery">Kode Authenticator</span>
                        <span x-show="recovery" x-cloak>Kode Pemulihan</span>
                    </label>
                    <input id="code" type="text" name="code" required autofocus
                           :inputmode="recovery ? 'text' : 'numeric'"
                           :maxlength="recovery ? 11 : 7"
                           autocomplete="one-time-code"
                           :placeholder="recovery ? 'abcde-12345' : '123 456'"
                           class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3.5 text-white text-center text-2xl tracking-[0.3em] font-mono placeholder-slate-600 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all"
                           :class="recovery ? '!text-lg !tracking-widest' : ''">
                </div>

                <button type="submit"
                        class="w-full bg-brand-600 hover:bg-brand-500 text-white font-bold py-3.5 rounded-xl transition-all shadow-[0_0_20px_rgba(37,99,235,0.3)] hover:shadow-[0_0_30px_rgba(37,99,235,0.5)] active:scale-[0.98]">
                    Verifikasi &amp; Masuk
                </button>
            </form>

            <div class="mt-6 flex flex-col items-center gap-3 text-xs">
                <button type="button" @click="recovery = !recovery; $nextTick(() => document.getElementById('code').focus())"
                        class="text-slate-400 hover:text-brand-500 transition-colors">
                    <span x-show="!recovery">HP hilang? Pakai kode pemulihan</span>
                    <span x-show="recovery" x-cloak>Kembali ke kode authenticator</span>
                </button>

                <form action="{{ route('two-factor.cancel') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-slate-500 hover:text-slate-300 transition-colors">Batal, kembali ke login</button>
                </form>
            </div>
        </div>

        <p class="text-center text-[10px] text-slate-600 mt-8">
            &copy; {{ date('Y') }} EHANZSTORE.ID. Secure Admin Access Only.
        </p>
    </div>

    <script src="https://unpkg.com/alpinejs@3.14.8/dist/cdn.min.js" defer></script>
    <style>[x-cloak]{display:none !important}</style>
</body>
</html>