@extends('layouts.app')

@section('title', 'Jual Beli Akun COC TH Max - EHANZSTORE.ID')

@section('content')
    <!-- Hero Catalog -->
    <section class="relative pt-40 pb-16 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-royal/10 to-transparent pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
            <h1 class="font-display text-4xl md:text-6xl font-bold text-white mb-4 tracking-tight">
                Gudang Akun <span class="text-electric glow-text">TH 9 - TH 16</span>
            </h1>
            <p class="text-slate-400 max-w-xl mx-auto">Filter berdasarkan Town Hall, harga, atau kelengkapan hero. Semua akun sudah dicek real-time sebelum listing.</p>
        </div>
    </section>

    <!-- Floating Filter Bar -->
    <div class="sticky top-24 z-30 px-4 mb-12">
        <div class="max-w-5xl mx-auto glass-strong rounded-2xl p-2 flex flex-col md:flex-row items-center gap-2 shadow-2xl shadow-black/50 border border-white/10">
            
            <!-- Search Input -->
            <div class="relative w-full md:w-1/3 group">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-electric transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <form action="{{ route('catalog') }}" method="GET" class="w-full">
                    @foreach(request()->except(['search', 'page']) as $k => $v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari TH16, Max Hero..." 
                           class="w-full bg-midnight/50 border border-white/5 rounded-xl pl-12 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:border-electric focus:ring-1 focus:ring-electric outline-none transition-all">
                </form>
            </div>

            <div class="hidden md:block w-px h-8 bg-white/10"></div>

            <!-- TH Filter Pills -->
            <div class="flex-1 w-full overflow-x-auto no-scrollbar flex gap-2 px-2 py-1">
                <form action="{{ route('catalog') }}" method="GET" class="flex gap-2 w-full">
                    @foreach(request()->except(['th', 'page']) as $k => $v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
                    
                    <button type="submit" name="th" value="" 
                            class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all border {{ !request('th') ? 'bg-electric text-white border-electric shadow-[0_0_15px_rgba(59,130,246,0.4)]' : 'bg-white/5 text-slate-400 border-white/5 hover:bg-white/10 hover:text-white' }}">
                        Semua TH
                    </button>
                    
                    @for($i = 9; $i <= 16; $i++)
                        <button type="submit" name="th" value="{{ $i }}" 
                                class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all border {{ request('th') == $i ? 'bg-electric text-white border-electric shadow-[0_0_15px_rgba(59,130,246,0.4)]' : 'bg-white/5 text-slate-400 border-white/5 hover:bg-white/10 hover:text-white' }}">
                            TH {{ $i }}
                        </button>
                    @endfor
                </form>
            </div>

            <!-- Sort Dropdown -->
            <div class="w-full md:w-auto shrink-0">
                <form action="{{ route('catalog') }}" method="GET" class="w-full">
                    @foreach(request()->except(['sort', 'page']) as $k => $v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
                    <select name="sort" onchange="this.form.submit()" class="w-full bg-midnight/50 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:border-electric outline-none cursor-pointer appearance-none">
                        <option value="" {{ !request('sort') ? 'selected' : '' }}>Terbaru Masuk</option>
                        <option value="price_asc" {{ request('sort')=='price_asc'?'selected':'' }}>Harga Termurah</option>
                        <option value="price_desc" {{ request('sort')=='price_desc'?'selected':'' }}>Harga Tertinggi</option>
                    </select>
                </form>
            </div>
        </div>
    </div>

    <!-- Product Grid -->
    <div class="max-w-7xl mx-auto px-4 pb-20">
        <div class="flex justify-between items-center mb-8 px-2">
            <p class="text-sm text-slate-500">Ditemukan <span class="text-electric font-bold">{{ $accounts->total() }}</span> akun siap kirim</p>
            @if(request()->anyFilled(['search','th','sort']))
                <a href="{{ route('catalog') }}" class="text-xs text-red-400 hover:text-red-300 font-medium flex items-center gap-1 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Reset Filter
                </a>
            @endif
        </div>

        @if($accounts->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($accounts as $account)
                    @include('components.account-card', ['account' => $account])
                @endforeach
            </div>

            <div class="mt-16 flex justify-center">
                {{ $accounts->onEachSide(1)->links('vendor.pagination.tailwind') }}
            </div>
        @else
            <div class="glass rounded-3xl p-20 text-center border border-white/5">
                <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                    <svg class="w-10 h-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-xl font-display font-bold text-white mb-2">Stok Kosong Sementara</h3>
                <p class="text-slate-500 text-sm mb-8">Akun bagus cepat laku. Chat admin untuk request spesifikasi tertentu.</p>
                <a href="https://wa.me/6282130559126" class="inline-flex items-center gap-2 bg-electric hover:bg-blue-500 text-white px-6 py-3 rounded-full text-sm font-bold transition shadow-lg shadow-electric/20">
                    Request ke Admin
                </a>
            </div>
        @endif
    </div>
@endsection