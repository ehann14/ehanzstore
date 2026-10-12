@extends('layouts.app')

@section('content')
    <!-- Hero Section -->
    <section class="hero-gradient pt-32 pb-20 relative overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
            <div class="absolute top-10 left-10 w-64 h-64 bg-blue-400 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-indigo-500 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
            <span class="inline-block py-1 px-3 rounded-full bg-white/10 text-blue-200 text-xs font-semibold mb-6 border border-white/20 backdrop-blur-sm">
                 Marketplace Akun COC Terpercaya Sejak 2024
            </span>
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
                Upgrade Pasukanmu dengan <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 to-white">Akun Sultan Terjamin</span>
            </h1>
            <p class="text-lg md:text-xl text-blue-100 mb-10 max-w-2xl mx-auto font-light">
                Temukan akun Clash of Clans dengan TH tinggi, hero max, dan keamanan terjamin. Proses cepat, aman, dan bergaransi resmi EHANZSTORE.
            </p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('catalog') }}" class="bg-white text-brand-navy px-8 py-4 rounded-full font-bold hover:bg-blue-50 transition shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
                    Lihat Katalog Akun
                </a>
                <a href="https://wa.me/6282130559126" class="bg-transparent border-2 border-white/30 text-white px-8 py-4 rounded-full font-bold hover:bg-white/10 transition backdrop-blur-sm">
                    Konsultasi Gratis
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Bar -->
    <div class="bg-white shadow-md relative z-20 -mt-10 max-w-5xl mx-auto rounded-2xl p-8 grid grid-cols-2 md:grid-cols-4 gap-6 text-center border border-gray-100">
        <div>
            <p class="text-3xl font-bold text-brand">500+</p>
            <p class="text-gray-500 text-sm">Akun Terjual</p>
        </div>
        <div>
            <p class="text-3xl font-bold text-brand">100%</p>
            <p class="text-gray-500 text-sm">Garansi Aman</p>
        </div>
        <div>
            <p class="text-3xl font-bold text-brand">24/7</p>
            <p class="text-gray-500 text-sm">Support CS</p>
        </div>
        <div>
            <p class="text-3xl font-bold text-brand">4.9</p>
            <p class="text-gray-500 text-sm">Rating Toko</p>
        </div>
    </div>

    <!-- Featured Accounts -->
    <section class="py-20 max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-end mb-10">
            <div>
                <h2 class="text-3xl font-bold text-brand-navy">Akun Pilihan Minggu Ini</h2>
                <p class="text-gray-500 mt-2">Koleksi terbaik dengan harga spesial</p>
            </div>
            <a href="{{ route('catalog') }}" class="text-brand font-semibold hover:text-brand-dark hidden md:block">Lihat Semua &rarr;</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($featuredAccounts as $account)
                @include('components.account-card', ['account' => $account])
            @empty
                <div class="col-span-3 text-center py-10 text-gray-500">Belum ada akun tersedia saat ini.</div>
            @endforelse
        </div>
        
        <div class="mt-10 text-center md:hidden">
            <a href="{{ route('catalog') }}" class="inline-block bg-brand text-white px-6 py-3 rounded-full font-semibold">Lihat Semua Akun</a>
        </div>
    </section>
@endsection