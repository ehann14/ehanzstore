@extends('layouts.app')

@section('title', 'EHANZSTORE.ID - Jual Beli Akun Clash of Clans Terpercaya')

@section('content')

    {{-- ===================== BANNER SLIDER ===================== --}}
    <section class="pt-20 sm:pt-28 px-4">
        <div class="max-w-7xl mx-auto">

            @if($banners->count())
                <div x-data="bannerSlider({{ $banners->count() }})"
                     @mouseenter="pause()" @mouseleave="play()"
                     @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)"
                     class="relative overflow-hidden rounded-2xl sm:rounded-3xl border border-white/10 bg-slate-900 shadow-2xl shadow-black/40 md:aspect-[8/3]">

                    @foreach($banners as $i => $banner)
                        @php
                            $tag   = $banner->link_url ? 'a' : 'div';
                            $attrs = $banner->link_url ? 'href="' . e($banner->link_url) . '" target="_blank" rel="noopener"' : '';
                        @endphp
                        <{{ $tag }} {!! $attrs !!}
                            x-show="current === {{ $i }}"
                            x-transition:enter="transition-opacity duration-700"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition-opacity duration-700 absolute inset-0"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            @if($i > 0) x-cloak @endif
                            class="relative md:absolute md:inset-0 block {{ $banner->link_url ? 'cursor-pointer' : '' }}">
                            <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}"
                                 class="w-full h-auto md:h-full block object-cover" {{ $i > 0 ? 'loading=lazy' : '' }}>
                        </{{ $tag }}>
                    @endforeach

                    @if($banners->count() > 1)
                        <!-- Panah -->
                        <button @click="prev()" aria-label="Sebelumnya"
                                class="hidden sm:flex absolute left-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-black/40 hover:bg-black/70 backdrop-blur text-white items-center justify-center transition z-10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button @click="next()" aria-label="Berikutnya"
                                class="hidden sm:flex absolute right-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-black/40 hover:bg-black/70 backdrop-blur text-white items-center justify-center transition z-10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>

                        <!-- Indikator -->
                        <div class="absolute bottom-2 sm:bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
                            @foreach($banners as $i => $banner)
                                <button @click="go({{ $i }})" aria-label="Banner {{ $i + 1 }}"
                                        :class="current === {{ $i }} ? 'w-7 bg-white' : 'w-2 bg-white/40 hover:bg-white/70'"
                                        class="h-2 rounded-full transition-all duration-300"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                {{-- Fallback jika belum ada banner yang diposting admin --}}
                <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl border border-white/10 bg-gradient-to-br from-royal via-[#123B8A] to-midnight py-10 md:py-0 md:aspect-[8/3] flex items-center">
                    <div class="absolute -right-20 -top-20 w-80 h-80 rounded-full bg-electric/30 blur-3xl"></div>
                    <div class="relative px-6 sm:px-12 max-w-2xl">
                        <p class="text-xs font-bold tracking-widest text-blue-200 uppercase mb-3">EHANZSTORE.ID</p>
                        <h1 class="font-display text-2xl sm:text-4xl md:text-5xl font-bold text-white leading-tight">
                            Akun Clash of Clans Terpercaya
                        </h1>
                        <p class="hidden sm:block text-blue-100/80 mt-3">Transaksi aman via WhatsApp, akun dicek sebelum listing, ada garansi.</p>
                        <a href="{{ $storeWaUrl }}" target="_blank" rel="noopener"
                           class="inline-flex mt-5 bg-white text-royal font-semibold px-5 py-2.5 rounded-xl text-sm hover:bg-blue-50 transition">
                            Chat Admin
                        </a>
                    </div>
                </div>
            @endif

            {{-- Jaminan singkat --}}
            <div class="flex md:grid md:grid-cols-4 gap-3 mt-4 overflow-x-auto no-scrollbar snap-x -mx-4 px-4 md:mx-0 md:px-0">
                @php
                    $perks = [
                        ['t' => 'Garansi 7 Hari',     'd' => 'Perlindungan setelah beli'],
                        ['t' => 'Akun Dicek Dulu',    'd' => 'Data sesuai sebelum listing'],
                        ['t' => 'Email Bisa Ganti',   'd' => 'Serah terima aman'],
                        ['t' => 'Respon Cepat',       'd' => 'Admin aktif via WhatsApp'],
                    ];
                @endphp
                @foreach($perks as $p)
                    <div class="snap-start shrink-0 md:shrink min-w-[200px] md:min-w-0 flex items-center gap-3 rounded-xl bg-white/[0.03] border border-white/5 px-4 py-3">
                        <span class="w-8 h-8 shrink-0 rounded-lg bg-electric/10 text-electric flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-white leading-tight">{{ $p['t'] }}</p>
                            <p class="text-[11px] text-slate-500 truncate">{{ $p['d'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== AKUN DIJUAL ===================== --}}
    <section id="akun" class="max-w-7xl mx-auto px-4 pt-10 sm:pt-16 pb-16 sm:pb-20">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 sm:gap-4 mb-5 sm:mb-6">
            <div>
                <h2 class="font-display text-xl sm:text-3xl font-bold text-white flex items-center gap-2">
                    <span class="w-1.5 h-6 sm:h-7 rounded-full bg-electric"></span>
                    AKUN DIJUAL
                </h2>
                <p class="text-sm text-slate-500 mt-1">{{ $totalAvailable }} akun siap kirim, diurutkan dari yang terbaru.</p>
            </div>
            <a href="{{ route('catalog') }}" class="inline-flex items-center gap-2 text-sm font-medium text-electric hover:text-white transition">
                Lihat semua
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>

        {{-- Filter cepat Town Hall --}}
        @if($townHalls->count())
            <div class="flex gap-2 overflow-x-auto no-scrollbar pb-2 mb-6">
                <a href="{{ route('catalog') }}" class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide bg-electric text-white">Semua</a>
                @foreach($townHalls as $th)
                    <a href="{{ route('catalog', ['th' => $th]) }}"
                       class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide bg-white/5 text-slate-400 border border-white/5 hover:bg-white/10 hover:text-white transition">
                        TH {{ $th }}
                    </a>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-5">
            @forelse($featuredAccounts as $account)
                @include('components.account-card', ['account' => $account])
            @empty
                <div class="col-span-full rounded-2xl border border-white/5 bg-white/[0.02] p-14 text-center text-slate-500">
                    Stok sedang kosong. Chat admin untuk request akun sesuai kebutuhanmu.
                </div>
            @endforelse
        </div>

        @if($featuredAccounts->count())
            <div class="mt-10 text-center">
                <a href="{{ route('catalog') }}"
                   class="inline-flex items-center gap-2 px-7 py-3 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 text-white text-sm font-semibold transition">
                    Lihat Semua Akun
                </a>
            </div>
        @endif
    </section>

    {{-- ===================== CTA ===================== --}}
    <section class="max-w-7xl mx-auto px-4 pb-16 sm:pb-20">
        <div class="relative overflow-hidden rounded-2xl sm:rounded-3xl bg-gradient-to-r from-royal to-[#123B8A] px-6 sm:px-12 py-8 sm:py-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-white/10 blur-3xl"></div>
            <div class="relative">
                <h3 class="font-display text-xl sm:text-2xl font-bold text-white">Tidak menemukan akun yang dicari?</h3>
                <p class="text-blue-100/80 text-sm mt-1">Sebutkan kebutuhan dan budgetmu, admin bantu carikan.</p>
            </div>
            <div class="relative flex flex-wrap gap-3">
                <a href="{{ $storeWaUrl }}" target="_blank" rel="noopener"
                   class="px-6 py-3 rounded-xl bg-white text-royal font-semibold text-sm hover:bg-blue-50 transition">WhatsApp</a>
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
            init() { this.play(); },
            go(i) { this.current = (i + this.total) % this.total; },
            next() { this.go(this.current + 1); },
            prev() { this.go(this.current - 1); },
            play() {
                this.pause();
                if (this.total > 1) this.timer = setInterval(() => this.next(), 5000);
            },
            pause() { if (this.timer) { clearInterval(this.timer); this.timer = null; } },
            touchStart(e) { this.startX = e.changedTouches[0].clientX; },
            touchEnd(e) {
                const dx = e.changedTouches[0].clientX - this.startX;
                if (Math.abs(dx) > 50) { dx < 0 ? this.next() : this.prev(); this.play(); }
            },
        };
    }
</script>
@endpush