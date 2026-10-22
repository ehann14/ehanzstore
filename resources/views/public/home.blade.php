@extends('layouts.app')

@section('title', 'EHANZSTORE.ID - Jual Beli Akun Clash of Clans Terpercaya')

@section('content')

    {{-- ===================== BANNER SLIDER (CLEAN & SMOOTH) ===================== --}}
    <section class="pt-20 sm:pt-28 px-4">
        <div class="max-w-7xl mx-auto">

            @if($banners->count())
                <div x-data="bannerSlider({{ $banners->count() }})"
                     @mouseenter="pause()" @mouseleave="play()"
                     @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)"
                     class="relative overflow-hidden rounded-2xl sm:rounded-3xl border border-white/10 bg-slate-900 shadow-2xl shadow-black/40 w-full aspect-[2/1] md:aspect-[8/3] group cursor-grab active:cursor-grabbing">

                    {{-- CONTAINER GAMBAR GESER --}}
                    <div class="flex h-full w-full transition-transform duration-500 ease-out will-change-transform"
                         :style="'transform: translateX(-' + (current * 100) + '%)'">
                        
                        @foreach($banners as $i => $banner)
                            @php
                                $tag   = $banner->link_url ? 'a' : 'div';
                                $attrs = $banner->link_url ? 'href="' . e($banner->link_url) . '" target="_blank" rel="noopener"' : '';
                            @endphp
                            
                            <{{ $tag }} {!! $attrs !!} class="shrink-0 w-full h-full relative block overflow-hidden {{ $banner->link_url ? 'cursor-pointer' : '' }}">
                                {{-- Latar blur: mengisi ruang kosong di HP supaya banner tampil utuh, tidak terpotong --}}
                                <img src="{{ $banner->image_url }}" alt="" aria-hidden="true"
                                     loading="{{ $i === 0 ? 'eager' : 'lazy' }}" draggable="false"
                                     class="md:hidden absolute inset-0 w-full h-full object-cover scale-125 blur-2xl opacity-60 select-none pointer-events-none">
                                <img src="{{ $banner->image_url }}"
                                     alt="{{ $banner->title }}"
                                     loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                                     draggable="false"
                                     class="relative z-[1] w-full h-full object-contain md:object-cover object-center select-none pointer-events-none">
                            </{{ $tag }}>
                        @endforeach
                    </div>

                    @if($banners->count() > 1)
                        <!-- Panah (Desktop Only) -->
                        <button @click.stop="prev()" aria-label="Sebelumnya"
                                class="hidden md:flex absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/40 hover:bg-electric backdrop-blur text-white items-center justify-center transition z-20 border border-white/10 opacity-0 group-hover:opacity-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button @click.stop="next()" aria-label="Berikutnya"
                                class="hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/40 hover:bg-electric backdrop-blur text-white items-center justify-center transition z-20 border border-white/10 opacity-0 group-hover:opacity-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>

                        <!-- Indikator Dots -->
                        <div class="absolute bottom-2 sm:bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-20">
                            @foreach($banners as $i => $banner)
                                <button @click.stop="go({{ $i }})" aria-label="Banner {{ $i + 1 }}"
                                        :class="current === {{ $i }} ? 'w-8 bg-electric' : 'w-2 bg-white/30 hover:bg-white/60'"
                                        class="h-2 rounded-full transition-all duration-300 shadow-sm"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                {{-- Fallback Banner Statis --}}
                <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl border border-white/10 bg-gradient-to-br from-royal via-[#123B8A] to-midnight py-12 md:py-0 flex items-center min-h-[250px] md:aspect-[21/9]">
                    <div class="absolute -right-20 -top-20 w-64 h-64 md:w-96 md:h-96 rounded-full bg-electric/20 blur-3xl animate-pulse"></div>
                    <div class="relative px-6 sm:px-12 max-w-2xl w-full z-10">
                        <p class="text-[10px] sm:text-xs font-bold tracking-widest text-blue-200 uppercase mb-2">EHANZSTORE.ID</p>
                        <h1 class="font-display text-2xl sm:text-4xl md:text-5xl font-bold text-white leading-tight drop-shadow-lg">
                            Akun Clash of Clans Terpercaya
                        </h1>
                        <p class="text-blue-100/80 mt-2 text-sm sm:text-base">Transaksi aman via WhatsApp, akun dicek sebelum listing, ada garansi.</p>
                        <a href="{{ $storeWaUrl }}" target="_blank" rel="noopener"
                           class="inline-flex mt-5 bg-white text-royal font-bold px-5 py-2.5 rounded-xl text-sm hover:bg-blue-50 transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            Chat Admin
                        </a>
                    </div>
                </div>
            @endif

            {{-- Jaminan singkat (Trust Badges) --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 sm:gap-3 mt-3 sm:mt-4">
                @php
                    $perks = [
                        ['t' => 'Garansi 7 Hari',     'd' => 'Perlindungan setelah beli'],
                        ['t' => 'Akun Dicek Dulu',    'd' => 'Data sesuai sebelum listing'],
                        ['t' => 'Email Bisa Ganti',   'd' => 'Serah terima aman'],
                        ['t' => 'Respon Cepat',       'd' => 'Admin aktif via WhatsApp'],
                    ];
                @endphp
                @foreach($perks as $p)
                    <div class="flex items-center gap-2.5 sm:gap-3 rounded-xl bg-white/[0.03] border border-white/5 px-3 sm:px-4 py-2.5 sm:py-3 hover:bg-white/[0.06] transition-colors">
                        <span class="w-8 h-8 sm:w-9 sm:h-9 shrink-0 rounded-lg bg-electric/10 text-electric flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-[13px] sm:text-sm font-semibold text-white leading-tight">{{ $p['t'] }}</p>
                            <p class="text-[10px] sm:text-[11px] text-slate-500 leading-snug mt-0.5 sm:truncate">{{ $p['d'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Bukti sosial: rating rata-rata + jumlah akun terjual (dari tabel reviews & sales) --}}
            @if($stats['sold_count'] > 0)
                <div class="mt-3 sm:mt-4 flex flex-wrap items-center justify-center gap-x-3 gap-y-1.5 rounded-xl bg-white/[0.03] border border-white/5 px-4 py-3 text-sm">
                    @if($stats['average'] !== null)
                        <x-stars :rating="round($stats['average'])" size="w-4 h-4 sm:w-5 sm:h-5" />
                        <span class="font-semibold text-white">{{ number_format($stats['average'], 1, ',', '.') }} <span class="font-normal text-slate-400">dari 5</span></span>
                        <span class="text-slate-600" aria-hidden="true">&middot;</span>
                    @endif
                    <span class="font-semibold text-white">{{ number_format($stats['sold_accounts'], 0, ',', '.') }} <span class="font-normal text-slate-400">akun terjual</span></span>
                    @if($stats['sold_clans'] > 0)
                        <span class="text-slate-600" aria-hidden="true">&middot;</span>
                        <span class="font-semibold text-white">{{ number_format($stats['sold_clans'], 0, ',', '.') }} <span class="font-normal text-slate-400">clan terjual</span></span>
                    @endif
                </div>
            @endif
        </div>
    </section>

    {{-- ===================== HOT DEAL ===================== --}}
    @if(isset($hotDeals) && $hotDeals->count())
        <section id="hot-deal" class="max-w-7xl mx-auto px-4 pt-10 sm:pt-14">
            <div class="mb-5 sm:mb-6">
                <h2 class="font-display text-xl sm:text-3xl font-bold text-white flex items-center gap-2">
                    <span class="w-1.5 h-6 sm:h-7 rounded-full bg-gradient-to-b from-orange-400 to-red-600"></span>
                    HOT DEAL
                    <svg class="w-5 h-5 sm:w-7 sm:h-7 text-orange-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177A7.547 7.547 0 016.648 6.61a.75.75 0 00-1.152-.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 011.925-3.545 3.75 3.75 0 013.255 3.717z"/></svg>
                </h2>
                <p class="text-sm text-slate-500 mt-1">Harga spesial untuk stok pilihan. Stok terbatas, siapa cepat dia dapat.</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-5">
                @foreach($hotDeals as $account)
                    @include('components.account-card', ['account' => $account])
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===================== AKUN & CLAN DIJUAL (dipisah, termasuk yang terjual) ===================== --}}
    @php
        $homeBlocks = [
            ['type' => 'account', 'available' => $featuredAccounts, 'sold' => $soldAccounts, 'total' => $totalAvailableAccounts],
            ['type' => 'clan',    'available' => $featuredClans,    'sold' => $soldClans,    'total' => $totalAvailableClans],
        ];
    @endphp

    @foreach($homeBlocks as $block)
        @php
            $type        = $block['type'];
            $available   = $block['available'];
            $sold        = $block['sold'];
            $total       = $block['total'];
            $isClanBlock = $type === 'clan';
            $noun        = $isClanBlock ? 'clan' : 'akun';
            $catalogUrl  = route('catalog', ['type' => $type]);
        @endphp

    <section id="{{ $isClanBlock ? 'clan' : 'akun' }}" class="max-w-7xl mx-auto px-4 pt-10 sm:pt-16 pb-6 sm:pb-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 sm:gap-4 mb-5 sm:mb-6">
            <div>
                <h2 class="font-display text-xl sm:text-3xl font-bold text-white flex items-center gap-2">
                    <span class="w-1.5 h-6 sm:h-7 rounded-full bg-electric"></span>
                    {{ $isClanBlock ? 'CLAN DIJUAL' : 'AKUN DIJUAL' }}
                </h2>
                <p class="text-sm text-slate-500 mt-1">{{ $total }} {{ $noun }} siap kirim, diurutkan dari yang terbaru.</p>
            </div>
            <a href="{{ $catalogUrl }}" class="inline-flex items-center gap-2 text-sm font-medium text-electric hover:text-white transition self-start sm:self-auto">
                Lihat semua
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>

        @if(! $isClanBlock && isset($townHalls) && $townHalls->count())
            <div class="flex gap-2 overflow-x-auto no-scrollbar pb-2 mb-6 -mx-4 px-4 sm:mx-0 sm:px-0 snap-x">
                <a href="{{ route('catalog', ['type' => 'account']) }}" class="snap-start shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide bg-electric text-white shadow-lg shadow-electric/20">Semua</a>
                @foreach($townHalls as $th)
                    <a href="{{ route('catalog', ['type' => 'account', 'th' => $th]) }}"
                       class="snap-start shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide bg-white/5 text-slate-400 border border-white/5 hover:bg-white/10 hover:text-white transition">
                        TH {{ $th }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-5">
            @forelse($available as $account)
                @include('components.account-card', ['account' => $account])
            @empty
                <div class="col-span-full rounded-2xl border border-white/5 bg-white/[0.02] p-14 text-center text-slate-500">
                    Stok {{ $noun }} sedang kosong. Chat admin untuk request {{ $noun }} sesuai kebutuhanmu.
                </div>
            @endforelse
        </div>

        @if($available->count())
            <div class="mt-10 text-center">
                <a href="{{ $catalogUrl }}"
                   class="inline-flex items-center gap-2 px-7 py-3 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 text-white text-sm font-semibold transition">
                    Lihat Semua {{ $isClanBlock ? 'Clan' : 'Akun' }}
                </a>
            </div>
        @endif
    </section>
    @endforeach

    <div class="pb-10 sm:pb-12"></div>

    {{-- ===================== TESTIMONI PEMBELI (PERMANEN) ===================== --}}
    @if($testimonials->count())
        <section id="testimoni" class="max-w-7xl mx-auto px-4 pb-16 sm:pb-20">
            <div class="mb-5 sm:mb-6">
                <h2 class="font-display text-xl sm:text-3xl font-bold text-white flex items-center gap-2">
                    <span class="w-1.5 h-6 sm:h-7 rounded-full bg-electric"></span>
                    TESTIMONI PEMBELI
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    @if($stats['average'] !== null)
                        Rating {{ number_format($stats['average'], 1, ',', '.') }} dari 5 &middot;
                    @endif
                    {{ number_format($stats['sold_accounts'], 0, ',', '.') }} akun
                    @if($stats['sold_clans'] > 0)
                        dan {{ number_format($stats['sold_clans'], 0, ',', '.') }} clan
                    @endif
                    terjual. Ulasan asli dari pembeli kami.
                </p>
            </div>

            <div x-data="{ all: false }">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-5">
                    @foreach($testimonials as $i => $review)
                        <figure @if($i >= 6) x-show="all" style="display: none;" @endif
                                class="flex flex-col rounded-2xl border border-white/10 bg-white/[0.03] p-5 hover:bg-white/[0.06] transition-colors">
                            <x-stars :rating="$review->rating" />
                            <blockquote class="mt-3 text-sm text-slate-300 leading-relaxed flex-1 break-words">
                                &ldquo;{{ $review->comment }}&rdquo;
                            </blockquote>
                            <figcaption class="mt-4 flex items-center gap-3 pt-4 border-t border-white/5">
                                <span class="w-9 h-9 shrink-0 rounded-full bg-electric/15 text-electric flex items-center justify-center text-sm font-bold uppercase">
                                    {{ mb_substr($review->reviewer_name, 0, 1) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-white truncate">{{ $review->reviewer_name }}</p>
                                    <p class="text-[11px] text-slate-500 truncate">
                                        Pembeli terverifikasi
                                        @if($review->account) &middot; {{ $review->account->name }} @endif
                                    </p>
                                </div>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>

                @if($testimonials->count() > 6)
                    <div class="mt-8 text-center">
                        <button type="button" @click="all = !all"
                                class="inline-flex items-center gap-2 px-7 py-3 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 text-white text-sm font-semibold transition">
                            <span x-text="all ? 'Tampilkan lebih sedikit' : 'Lihat testimoni lainnya'"></span>
                        </button>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ===================== AKUN & CLAN TERJUAL (paling bawah) ===================== --}}
    @if($soldAccounts->count() || $soldClans->count())
        <section id="terjual" class="max-w-7xl mx-auto px-4 pb-16 sm:pb-20">
            @foreach([
                ['noun' => 'akun', 'title' => 'AKUN TERJUAL', 'items' => $soldAccounts],
                ['noun' => 'clan', 'title' => 'CLAN TERJUAL', 'items' => $soldClans],
            ] as $i => $group)
                @if($group['items']->count())
                    <div class="{{ $i > 0 && $soldAccounts->count() ? 'mt-12 sm:mt-14' : '' }}">
                        <div class="mb-5">
                            <h2 class="font-display text-lg sm:text-2xl font-bold text-white flex items-center gap-2">
                                <span class="w-1.5 h-5 sm:h-6 rounded-full bg-red-500"></span>
                                {{ $group['title'] }}
                            </h2>
                            <p class="text-sm text-slate-500 mt-1">{{ ucfirst($group['noun']) }} yang baru laku dan masih dalam masa garansi pembeli.</p>
                        </div>

                        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-5">
                            @foreach($group['items'] as $account)
                                @include('components.account-card', ['account' => $account])
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </section>
    @endif

    {{-- ===================== CTA ===================== --}}
    <section class="max-w-7xl mx-auto px-4 pb-16 sm:pb-20">
        <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-gradient-to-r from-royal to-[#123B8A] px-6 sm:px-12 py-8 sm:py-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6 border border-white/10">
            <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-white/10 blur-3xl"></div>
            <div class="relative z-10">
                <h3 class="font-display text-xl sm:text-2xl font-bold text-white">Tidak menemukan akun yang dicari?</h3>
                <p class="text-blue-100/80 text-sm mt-1">Sebutkan kebutuhan dan budgetmu, admin bantu carikan.</p>
            </div>
            <div class="relative z-10 flex flex-wrap gap-3">
                <a href="{{ $storeWaUrl }}" target="_blank" rel="noopener"
                   class="px-6 py-3 rounded-xl bg-white text-royal font-semibold text-sm hover:bg-blue-50 transition shadow-lg">WhatsApp</a>
                <a href="{{ $storeIgUrl }}" target="_blank" rel="noopener"
                   class="px-6 py-3 rounded-xl border border-white/30 text-white font-semibold text-sm hover:bg-white/10 transition">Instagram</a>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    function bannerSlider(total) {
        return {
            current: 0,
            total: total,
            timer: null,
            startX: 0,
            isDragging: false,
            
            init() { this.play(); },
            go(i) { this.current = (i + this.total) % this.total; },
            next() { this.go(this.current + 1); },
            prev() { this.go(this.current - 1); },
            
            play() {
                this.pause();
                if (this.total > 1) this.timer = setInterval(() => this.next(), 5000);
            },
            pause() { if (this.timer) { clearInterval(this.timer); this.timer = null; } },
            
            touchStart(e) { 
                this.startX = e.changedTouches[0].clientX; 
                this.isDragging = true;
                this.pause(); 
            }, 
            
            touchEnd(e) {
                if (!this.isDragging) return;
                const endX = e.changedTouches[0].clientX;
                const dx = endX - this.startX;
                if (Math.abs(dx) > 50) { dx < 0 ? this.next() : this.prev(); }
                this.isDragging = false;
                this.play(); 
            },
        };
    }
</script>
@endpush