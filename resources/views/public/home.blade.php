@extends('layouts.app')

@section('content')
    <!-- Hero Section: Fluid & Glowing -->
    <section class="relative pt-40 pb-24 overflow-hidden">
        <!-- Background Glow Effects -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[800px] bg-brand-600/10 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-[600px] h-[600px] bg-blue-500/5 rounded-full blur-[100px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-brand-400 text-xs font-semibold mb-8 backdrop-blur-md animate-fade-in-up">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                Marketplace Terpercaya Sejak 2024
            </div>
            
            <h1 class="text-5xl md:text-7xl font-bold text-white mb-8 leading-[1.1] tracking-tight">
                Upgrade Pasukanmu dengan <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-400 via-blue-300 to-white">Akun Sultan Terjamin</span>
            </h1>
            
            <p class="text-lg md:text-xl text-slate-400 mb-12 max-w-2xl mx-auto font-light leading-relaxed">
                Temukan akun Clash of Clans TH Max, Hero Full, dan CN Original. Proses instan, aman, dan bergaransi resmi EHANZSTORE.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('catalog') }}" class="group bg-white text-brand-950 px-8 py-4 rounded-full font-bold hover:bg-brand-50 transition-all shadow-[0_0_40px_rgba(255,255,255,0.1)] hover:shadow-[0_0_60px_rgba(255,255,255,0.2)] flex items-center justify-center gap-2">
                    Lihat Katalog
                    <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
                <a href="https://wa.me/6282130559126" class="px-8 py-4 rounded-full font-bold text-slate-300 hover:text-white border border-white/10 hover:bg-white/5 transition-all backdrop-blur-sm">
                    Konsultasi Gratis
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Bar: Floating Glass Cards -->
    <div class="max-w-5xl mx-auto px-4 -mt-10 relative z-20 mb-24">
        <div class="glass-panel rounded-4xl p-2 grid grid-cols-2 md:grid-cols-4 gap-2">
            @php $stats = [['val'=>'500+','lbl'=>'Terjual'],['val'=>'100%','lbl'=>'Garansi'],['val'=>'24/7','lbl'=>'Support'],['val'=>'4.9★','lbl'=>'Rating']]; @endphp
            @foreach($stats as $stat)
            <div class="bg-brand-950/50 rounded-3xl p-6 text-center hover:bg-white/5 transition-colors duration-300">
                <p class="text-3xl font-bold text-white mb-1">{{ $stat['val'] }}</p>
                <p class="text-slate-500 text-xs font-medium uppercase tracking-wider">{{ $stat['lbl'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Featured Accounts: Clean Grid -->
    <section class="py-16 max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-end mb-12">
            <div>
                <h2 class="text-3xl font-bold text-white mb-2">Rekomendasi Minggu Ini</h2>
                <p class="text-slate-500">Pilihan terbaik dengan harga spesial</p>
            </div>
            <a href="{{ route('catalog') }}" class="text-brand-400 hover:text-brand-300 font-medium text-sm flex items-center gap-1 transition">
                Lihat Semua <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($featuredAccounts as $account)
                @include('components.account-card', ['account' => $account])
            @empty
                <div class="col-span-3 glass-panel rounded-4xl p-16 text-center text-slate-500">Belum ada akun tersedia.</div>
            @endforelse
        </div>
    </section>
@endsection