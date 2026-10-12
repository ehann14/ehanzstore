@extends('layouts.app')

@section('content')
    <!-- Hero Section -->
    <section class="relative pt-40 pb-24 overflow-hidden">
        <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-royal/20 rounded-full blur-[120px] animate-pulse-slow"></div>
        <div class="absolute bottom-0 right-1/4 w-[600px] h-[600px] bg-electric/10 rounded-full blur-[150px] animate-float"></div>

        <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full glass border-electric/30 text-electric text-xs font-bold mb-8">
                <span class="w-2 h-2 rounded-full bg-green-400 shadow-[0_0_10px_#4ade80]"></span>
                STOK AKUN TERVERIFIKASI HARI INI
            </div>
            
            <h1 class="font-display text-5xl md:text-7xl font-bold text-white mb-6 leading-[1.1] tracking-tight">
                Akun TH Max & <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-electric via-blue-300 to-white glow-text">Hero Full Siap Pakai</span>
            </h1>
            
            <p class="text-lg text-slate-400 mb-10 max-w-2xl mx-auto font-light">
                Koleksi akun Clash of Clans CN Original, Email Bisa Ganti, dan Garansi Personal 7 Hari. Transaksi langsung via WhatsApp tanpa ribet.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('catalog') }}" class="group relative px-8 py-4 bg-white text-midnight rounded-full font-bold overflow-hidden transition-all hover:scale-105">
                    <div class="absolute inset-0 bg-electric opacity-0 group-hover:opacity-10 transition-opacity"></div>
                    Lihat Stok Tersedia
                    <svg class="inline-block w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
                <a href="https://wa.me/6282130559126" class="px-8 py-4 rounded-full font-bold text-slate-300 hover:text-white glass hover:bg-white/10 transition-all border border-white/10">
                    Tanya Admin Dulu
                </a>
            </div>
        </div>
    </section>

    <!-- Floating Stats Bar -->
    <div class="max-w-5xl mx-auto px-4 -mt-12 relative z-20 mb-24">
        <div class="glass-strong rounded-3xl p-2 grid grid-cols-2 md:grid-cols-4 gap-2 shadow-2xl shadow-black/50">
            @php
                $stats = [
                    ['v' => '500+', 'l' => 'Terjual Aman'],
                    ['v' => '100%', 'l' => 'CN Original'],
                    ['v' => '7 Hari', 'l' => 'Garansi'],
                    ['v' => 'Fast Respon', 'l' => 'Admin WA']
                ];
            @endphp
            
            @foreach($stats as $s)
            <div class="bg-midnight/50 rounded-2xl p-6 text-center hover:bg-white/5 transition-colors duration-300 border border-white/5">
                <p class="text-3xl font-display font-bold text-white mb-1">{{ $s['v'] }}</p>
                <p class="text-slate-500 text-xs font-bold uppercase tracking-widest">{{ $s['l'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Featured Accounts Grid -->
    <section class="py-16 max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-end mb-12">
            <div>
                <h2 class="font-display text-3xl font-bold text-white mb-2">Stok Terbaru Masuk</h2>
                <div class="h-1 w-20 bg-electric rounded-full"></div>
            </div>
            <a href="{{ route('catalog') }}" class="text-electric hover:text-white font-medium text-sm flex items-center gap-2 transition">
                Lihat Semua 
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($featuredAccounts as $account)
                @include('components.account-card', ['account' => $account])
            @empty
                <div class="col-span-3 glass rounded-3xl p-16 text-center text-slate-500">
                    Sedang restock akun berkualitas. Cek lagi nanti atau chat admin untuk request.
                </div>
            @endforelse
        </div>
    </section>
@endsection