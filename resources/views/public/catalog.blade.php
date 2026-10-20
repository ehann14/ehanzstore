@extends('layouts.app')

@section('title', 'Jual Beli Akun COC TH Max - EHANZSTORE.ID')

@section('content')
    <!-- Hero Catalog -->
    <section class="relative pt-28 sm:pt-40 pb-8 sm:pb-16 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-royal/10 to-transparent pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
            <h1 class="font-display text-3xl sm:text-4xl md:text-6xl font-bold text-white mb-4 tracking-tight">
                Gudang Akun
            </h1>
    </section>

    <!-- Floating Filter Bar -->
    <div class="relative md:sticky md:top-24 z-30 px-4 mb-8 md:mb-12">
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
            <div id="thScroller"
                 class="md:flex-1 w-full min-w-0 overflow-x-auto overflow-y-hidden no-scrollbar px-2 py-1 cursor-grab select-none"
                 style="-webkit-overflow-scrolling: touch; overscroll-behavior-x: contain; touch-action: pan-x;">
                <form action="{{ route('catalog') }}" method="GET" class="flex flex-nowrap gap-2 w-max min-w-full pr-2">
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

            <!-- Sort Dropdown (custom) -->
            @php
                $sortOptions = [
                    ''           => ['Terbaru Masuk',   'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'price_asc'  => ['Harga Termurah',  'M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12'],
                    'price_desc' => ['Harga Tertinggi', 'M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4'],
                ];
                $currentSort = array_key_exists((string) request('sort'), $sortOptions) ? (string) request('sort') : '';
            @endphp
            <div class="w-full md:w-56 shrink-0 relative" id="sortDropdown">
                <form action="{{ route('catalog') }}" method="GET" class="w-full" id="sortForm">
                    @foreach(request()->except(['sort', 'page']) as $k => $v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
                    <input type="hidden" name="sort" id="sortInput" value="{{ $currentSort }}">

                    <button type="button" id="sortToggle" aria-haspopup="listbox" aria-expanded="false"
                            class="w-full flex items-center justify-between gap-3 bg-midnight/50 border border-white/5 hover:border-white/20 rounded-xl px-4 py-3 text-sm text-white outline-none transition-all">
                        <span class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 text-electric shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortOptions[$currentSort][1] }}"/></svg>
                            <span class="truncate font-medium">{{ $sortOptions[$currentSort][0] }}</span>
                        </span>
                        <svg id="sortChevron" class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div id="sortMenu" role="listbox"
                         class="hidden absolute left-0 right-0 top-full mt-2 z-50 p-1.5 rounded-xl border border-white/10 bg-slate-900/95 backdrop-blur-xl shadow-2xl shadow-black/60 origin-top">
                        @foreach($sortOptions as $val => [$label, $icon])
                            @php $isActive = $currentSort === (string) $val; @endphp
                            <button type="button" role="option" aria-selected="{{ $isActive ? 'true' : 'false' }}" data-value="{{ $val }}"
                                    class="sort-opt w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm text-left transition-colors {{ $isActive ? 'bg-electric/15 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ $isActive ? 'text-electric' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
                                <span class="flex-1">{{ $label }}</span>
                                @if($isActive)
                                    <svg class="w-4 h-4 text-electric shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
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
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6">
                @foreach($accounts as $account)
                    @include('components.account-card', ['account' => $account])
                @endforeach
            </div>

            <div class="mt-16 flex justify-center">
                {{ $accounts->onEachSide(1)->links('vendor.pagination.tailwind') }}
            </div>
        @else
            <div class="glass rounded-3xl p-8 sm:p-20 text-center border border-white/5">
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

@push('scripts')
<script>
    (function () {
        const el = document.getElementById('thScroller');
        if (!el) return;

        // Geser otomatis ke TH yang sedang aktif
        const active = el.querySelector('button.bg-electric');
        if (active && active.getAttribute('value')) {
            el.scrollLeft = active.offsetLeft - (el.clientWidth - active.offsetWidth) / 2;
        }

        // Scroll mouse (wheel) -> geser horizontal
        el.addEventListener('wheel', function (e) {
            if (el.scrollWidth <= el.clientWidth) return;
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                el.scrollLeft += e.deltaY;
                e.preventDefault();
            }
        }, { passive: false });

        // Drag dengan mouse (di HP sudah otomatis lewat sentuhan)
        let down = false, moved = false, startX = 0, startLeft = 0;
        el.addEventListener('mousedown', function (e) {
            down = true; moved = false;
            startX = e.pageX; startLeft = el.scrollLeft;
            el.classList.add('cursor-grabbing');
        });
        window.addEventListener('mouseup', function () {
            down = false;
            el.classList.remove('cursor-grabbing');
        });
        window.addEventListener('mousemove', function (e) {
            if (!down) return;
            const dx = e.pageX - startX;
            if (Math.abs(dx) > 5) moved = true;
            el.scrollLeft = startLeft - dx;
        });
        // Cegah klik tombol TH kalau user sebenarnya sedang menggeser
        el.addEventListener('click', function (e) {
            if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
        }, true);
    })();
    // Dropdown urutan (custom)
    (function () {
        const wrap = document.getElementById('sortDropdown');
        if (!wrap) return;
        const toggle = document.getElementById('sortToggle');
        const menu = document.getElementById('sortMenu');
        const chev = document.getElementById('sortChevron');
        const input = document.getElementById('sortInput');
        const form = document.getElementById('sortForm');

        const setOpen = (open) => {
            menu.classList.toggle('hidden', !open);
            chev.classList.toggle('rotate-180', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.classList.toggle('border-electric', open);
        };

        toggle.addEventListener('click', (e) => { e.stopPropagation(); setOpen(menu.classList.contains('hidden')); });
        menu.querySelectorAll('.sort-opt').forEach((btn) => {
            btn.addEventListener('click', () => { input.value = btn.dataset.value; form.submit(); });
        });
        document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) setOpen(false); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
    })();
</script>
@endpush