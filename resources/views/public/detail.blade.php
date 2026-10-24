@extends('layouts.app')

@section('title', $account->name . ' - EHANZSTORE.ID')

@php
    $shareUrl   = route('account.detail', $account->account_code);
    $sharePrice = 'Rp ' . number_format($account->price, 0, ',', '.');
    $isClan     = $account->isClan();
    $noun       = $account->noun();
    $shareTitle = $account->name . ' (' . ($isClan ? 'Clan Lv. ' . $account->clan_level : 'TH ' . $account->town_hall) . ') - ' . $sharePrice;
    $shareText  = ($isClan ? 'Clan Clash of Clans ' : 'Akun Clash of Clans ') . $account->name
                . ' | ' . ($isClan ? 'Lv. ' . $account->clan_level . ' | ' . $account->clan_league : 'TH ' . $account->town_hall)
                . ($account->tag() ? ' | Tag ' . $account->tag() : '')
                . ' | Kode ' . $account->account_code . ' | ' . $sharePrice . ' - EHANZSTORE.ID';
    
    // Perbaikan: Menggunakan asset('storage/...') untuk kompatibilitas symlink & hosting
    $ogImage    = ($account->primaryImage && !empty($account->primaryImage->image_path))
                ? upload_url($account->primaryImage->image_path)
                : null;
@endphp

@push('meta')
    <link rel="canonical" href="{{ $shareUrl }}">
    <meta property="og:type" content="product">
    <meta property="og:site_name" content="EHANZSTORE.ID">
    <meta property="og:title" content="{{ $shareTitle }}">
    <meta property="og:description" content="{{ $shareText }}">
    <meta property="og:url" content="{{ $shareUrl }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $ogImage }}">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    <meta name="twitter:title" content="{{ $shareTitle }}">
    <meta name="twitter:description" content="{{ $shareText }}">
@endpush

@section('content')
<div class="pt-28 pb-20 max-w-7xl mx-auto px-4">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-8">
        <a href="{{ route('home') }}" class="hover:text-electric transition">Beranda</a>
        <span>/</span>
        <a href="{{ route('catalog') }}" class="hover:text-electric transition">Katalog</a>
        <span>/</span>
        <span class="text-white font-medium truncate">{{ $account->name }}</span>
    </nav>

    <div class="grid lg:grid-cols-2 gap-8 lg:gap-12">

        {{-- Galeri Foto --}}
        <div class="space-y-4">
            @php
                $primaryImg = $account->images->firstWhere('is_primary', true)
                    ?? $account->images->first();
            @endphp

            <div class="aspect-[4/3] rounded-3xl overflow-hidden bg-midnight border border-white/10 relative">
                @if($primaryImg)
                    <button type="button" id="openLightboxBtn" aria-label="Lihat foto penuh dan zoom"
                            class="block w-full h-full cursor-zoom-in">
                        <img
                            id="mainImage"
                            src="{{ upload_url($primaryImg->image_path) }}"
                            alt="{{ $account->name }}"
                            class="w-full h-full object-contain"
                        >
                    </button>
                    <span class="pointer-events-none absolute bottom-3 right-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-black/60 backdrop-blur text-white text-[11px] font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                        Perbesar
                    </span>
                @else
                    <div class="w-full h-full flex flex-col items-center justify-center text-slate-600">
                        <svg class="w-16 h-16 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm">Tidak ada foto tersedia</p>
                    </div>
                @endif
            </div>

            {{-- Thumbnail Foto --}}
            @if($account->images->count() > 1)
                <div class="grid grid-cols-5 gap-3">
                    @foreach($account->images as $img)
                        <button
                            type="button"
                            onclick="changeMainImage('{{ upload_url($img->image_path) }}', this)"
                            class="aspect-square rounded-xl overflow-hidden border-2 {{ $img->id === ($primaryImg->id ?? null) ? 'border-electric' : 'border-transparent hover:border-white/30' }} transition-all thumbnail-btn"
                        >
                            <img
                                src="{{ upload_url($img->image_path) }}"
                                alt="Foto {{ $account->name }}"
                                class="w-full h-full object-cover"
                            >
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Informasi Produk --}}
        <div class="space-y-8">

            {{-- Nama Akun --}}
            <div>
                <div class="flex items-start justify-between gap-4 mb-4">
                    <h1 class="text-3xl md:text-4xl font-display font-bold text-white leading-tight">
                        {{ $account->name }}
                    </h1>

                    <span class="shrink-0 px-3 py-1.5 bg-electric/20 text-electric text-xs font-bold rounded-lg border border-electric/30">
                        {{ $account->badgeLabel() }}
                    </span>
                </div>

                <p class="text-slate-400 text-sm font-mono bg-white/5 inline-block px-3 py-1.5 rounded-lg border border-white/5">
                    KODE: {{ $account->account_code }}
                </p>
            </div>

            {{-- Harga dan Tombol Pembelian --}}
            <div class="glass rounded-2xl p-6 space-y-4 border-l-4 border-electric">
                @php
                    $isAvailable  = $account->sale_status === 'available';
                    $showDiscount = $isAvailable && $account->hasDiscount();
                @endphp
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">
                            Harga Jual
                        </p>
                        @if($isAvailable && $account->isHotDeal())
                            <x-hot-deal-badge />
                        @endif
                    </div>

                    @if($showDiscount)
                        <div class="flex items-center gap-2 mb-0.5">
                            <p class="text-base text-slate-500 line-through">
                                Rp {{ number_format($account->original_price, 0, ',', '.') }}
                            </p>
                            <span class="px-2 py-0.5 rounded-md bg-red-500/15 border border-red-500/30 text-red-300 text-xs font-bold">
                                Diskon {{ $account->discountPercent() }}%
                            </span>
                        </div>
                    @endif

                    <p class="text-4xl font-display font-bold {{ $showDiscount ? 'text-orange-400' : 'text-white' }}">
                        Rp {{ number_format($account->price, 0, ',', '.') }}
                    </p>

                    @if($showDiscount)
                        <p class="text-xs text-emerald-400 mt-1">
                            Kamu hemat Rp {{ number_format($account->discountAmount(), 0, ',', '.') }}
                        </p>
                    @endif
                </div>

                @if($account->sale_status !== 'available')
                    <div class="w-full text-center bg-red-500/10 border border-red-500/30 text-red-300 font-bold py-4 rounded-xl">
                        {{ ucfirst($noun) }} ini sudah terjual
                    </div>
                    @php
                        $warrantySale = $account->latestCompletedSale;
                        $warrantyLeft = $warrantySale?->warrantyRemainingText();
                    @endphp
                    @if($warrantyLeft)
                        <p class="text-center text-xs text-emerald-300">Garansi pembeli: <span {!! $warrantySale->countdownAttrs('aktif · sisa ', 'sudah berakhir') !!}>aktif &middot; {{ $warrantyLeft }}</span></p>
                    @endif
                    @if($warrantySale?->approvedReview)
                        @php $rv = $warrantySale->approvedReview; @endphp
                        <div class="rounded-xl bg-white/[0.04] border border-white/5 p-4">
                            <x-stars :rating="$rv->rating" />
                            <p class="text-sm text-slate-300 mt-2 whitespace-pre-line">&ldquo;{{ $rv->comment }}&rdquo;</p>
                            <p class="text-xs text-slate-500 mt-2">&mdash; {{ $rv->reviewer_name }}, pembeli</p>
                        </div>
                    @endif
                    <a href="{{ route('catalog') }}" class="block text-center text-sm text-electric hover:text-white transition">
                        Lihat {{ $noun }} lain yang tersedia
                    </a>
                @else
                <a
                    href="{{ account_wa_url($account, $storeWa ?? null) }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="flex items-center justify-center gap-3 w-full bg-green-600 hover:bg-green-500 text-white font-bold py-4 rounded-xl transition-all shadow-lg shadow-green-900/20 hover:shadow-green-900/40 active:scale-[0.98]"
                >
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/>
                    </svg>

                    Beli via WhatsApp
                </a>

                <p class="text-[10px] text-slate-500 text-center">
                    Anda akan diarahkan ke chat admin dengan pesan otomatis.
                </p>

                {{-- Bandingkan dengan akun lain (2 sampai 3 akun) --}}
                <button type="button" data-compare-btn
                        data-code="{{ $account->account_code }}"
                        data-name="{{ $account->name }}"
                        data-type="{{ $account->listing_type }}"
                        data-img="{{ $ogImage }}"
                        data-price="{{ (int) $account->price }}"
                        aria-pressed="false"
                        class="flex items-center justify-center gap-2 w-full border border-white/10 bg-white/5 hover:bg-white/10 text-slate-200 text-sm font-semibold py-3 rounded-xl transition aria-pressed:bg-electric/20 aria-pressed:border-electric aria-pressed:text-white">
                    <svg data-cmp-off class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14m7-7H5"/></svg>
                    <svg data-cmp-on class="hidden w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span data-compare-label data-off="Bandingkan dengan {{ $noun }} lain" data-on="Ditambahkan ke perbandingan">Bandingkan dengan {{ $noun }} lain</span>
                </button>
                @endif

                {{-- Informasi Garansi --}}
                <a
                    href="{{ route('warranty') }}"
                    class="flex items-center justify-center gap-2 text-xs text-green-400 hover:text-green-300 transition pt-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>

                    Bergaransi 7 hari, lihat ketentuannya
                </a>

                {{-- Bagikan --}}
                <div class="pt-4 mt-1 border-t border-white/10">
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-3">Bagikan akun ini</p>

                    <button type="button" id="nativeShareBtn"
                            class="hidden w-full mb-2 items-center justify-center gap-2 bg-electric hover:bg-blue-500 text-white text-sm font-semibold py-3 rounded-xl transition active:scale-[0.98]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        Bagikan
                    </button>

                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" id="copyLinkBtn"
                                class="flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10 border border-white/10 text-white text-xs font-semibold py-2.5 rounded-xl transition">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            <span>Salin Link</span>
                        </button>

                        <a href="https://wa.me/?text={{ rawurlencode($shareText . "\n" . $shareUrl) }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center justify-center gap-2 bg-green-600/20 hover:bg-green-600/30 border border-green-500/30 text-green-300 text-xs font-semibold py-2.5 rounded-xl transition">
                            WhatsApp
                        </a>

                        <a href="https://t.me/share/url?url={{ rawurlencode($shareUrl) }}&text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center justify-center gap-2 bg-sky-600/20 hover:bg-sky-600/30 border border-sky-500/30 text-sky-300 text-xs font-semibold py-2.5 rounded-xl transition">
                            Telegram
                        </a>

                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($shareUrl) }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center justify-center gap-2 bg-blue-600/20 hover:bg-blue-600/30 border border-blue-500/30 text-blue-300 text-xs font-semibold py-2.5 rounded-xl transition">
                            Facebook
                        </a>

                        <a href="https://twitter.com/intent/tweet?url={{ rawurlencode($shareUrl) }}&text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10 border border-white/10 text-slate-200 text-xs font-semibold py-2.5 rounded-xl transition">
                            X
                        </a>

                        <a href="mailto:?subject={{ rawurlencode($shareTitle) }}&body={{ rawurlencode($shareText . "\n" . $shareUrl) }}"
                           class="flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10 border border-white/10 text-slate-200 text-xs font-semibold py-2.5 rounded-xl transition">
                            Email
                        </a>
                    </div>
                </div>
            </div>

            {{-- Spesifikasi Akun --}}
            <div class="space-y-4">
                <h3 class="text-lg font-display font-bold text-white border-b border-white/10 pb-2">
                    {{ $isClan ? 'Informasi Clan' : 'Informasi Akun' }}
                </h3>

                @php
                    // Tag selalu paling awal (setelah nama), lalu data lainnya
                    $tagSpec = [
                        'label' => $isClan ? 'Tag Clan' : 'Tag Akun',
                        'val'   => $account->tag() ?: '-',
                        'mono'  => true,
                        'wide'  => true,
                        'copy'  => $account->tag(),
                    ];

                    $specs = $isClan ? [
                        $tagSpec,
                        ['label' => 'Level Clan', 'val' => 'Lv. ' . ($account->clan_level ?? 0)],
                        ['label' => 'Capital Hall', 'val' => 'Lv. ' . ($account->capital_hall_level ?? 0)],
                        ['label' => 'Member', 'val' => ($account->clan_members ?? 0) . ' / ' . \App\Models\CocAccount::CLAN_MAX_MEMBERS],
                        ['label' => 'Liga Clan', 'val' => $account->clan_league ?: 'Unranked'],
                    ] : [
                        $tagSpec,
                        ['label' => 'Level Akun', 'val' => $account->account_level ? 'Lv. ' . $account->account_level : '-'],
                        ['label' => 'Tukang', 'val' => ($account->builder_count ?? 5) . ' Orang'],
                        ['label' => 'Gems', 'val' => number_format($account->gems ?? 0, 0, ',', '.') . ' Gems'],
                        ['label' => 'CN', 'val' => $account->cn_information ?: '-', 'wide' => true],
                        [
                            'label' => 'Barbarian King',
                            'val' => 'Lv. ' . ($account->barbarian_king_level ?? 0),
                        ],
                        [
                            'label' => 'Archer Queen',
                            'val' => 'Lv. ' . ($account->archer_queen_level ?? 0),
                        ],
                        [
                            'label' => 'Minion Prince',
                            'val' => 'Lv. ' . ($account->minion_prince_level ?? 0),
                        ],
                        [
                            'label' => 'Grand Warden',
                            'val' => 'Lv. ' . ($account->grand_warden_level ?? 0),
                        ],
                        [
                            'label' => 'Royal Champion',
                            'val' => 'Lv. ' . ($account->royal_champion_level ?? 0),
                        ],
                        [
                            'label' => 'Dragon Duke',
                            'val' => 'Lv. ' . ($account->dragon_duke_level ?? 0),
                        ],
                    ];
                @endphp

                <div class="grid grid-cols-2 gap-4">
                    @foreach($specs as $spec)
                        <div class="bg-white/5 rounded-xl p-4 border border-white/5 {{ !empty($spec['wide']) ? 'col-span-2' : '' }}">
                            <p class="text-[10px] text-slate-500 uppercase tracking-wider mb-1">
                                {{ $spec['label'] }}
                            </p>

                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-bold text-white {{ !empty($spec['mono']) ? 'font-mono' : '' }} break-words">
                                    {{ $spec['val'] }}
                                </p>

                                @if(!empty($spec['copy']))
                                    <button type="button" id="copyTagBtn" data-tag="{{ $spec['copy'] }}"
                                            class="shrink-0 text-[11px] font-semibold text-slate-300 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 px-2.5 py-1.5 rounded-lg transition">
                                        Salin
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Informasi Keamanan (hanya akun pemain) --}}
            @unless($isClan)
            <div class="space-y-4">
                <h3 class="text-lg font-display font-bold text-white border-b border-white/10 pb-2">
                    Informasi Keamanan
                </h3>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-white/5 border border-white/5">
                        <span class="text-sm text-slate-400">
                            Status Proteksi
                        </span>

                        <span class="text-sm font-bold {{ $account->protection_status === 'Protected' ? 'text-green-400' : 'text-yellow-400' }}">
                            {{ $account->protection_status === 'Protected' ? 'ON' : 'OFF' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-white/5 border border-white/5">
                        <span class="text-sm text-slate-400">
                            Status Email/Gmail
                        </span>

                        <span class="text-sm font-bold {{ $account->email_change_status === 'Changeable' ? ($account->emailChangeDaysLeft() ? 'text-amber-400' : 'text-green-400') : 'text-yellow-400' }}">
                            {{ $account->emailStatusLabel() }}
                        </span>
                    </div>
                </div>
            </div>
            @endunless

            {{-- Deskripsi --}}
            @if($account->description)
                <div class="space-y-2">
                    <h3 class="text-lg font-display font-bold text-white border-b border-white/10 pb-2">
                        Deskripsi
                    </h3>

                    <p class="text-sm text-slate-300 leading-relaxed whitespace-pre-line">{{ $account->description }}</p>
                </div>
            @endif

        </div>
    </div>

    {{-- ===================== AKUN SERUPA ===================== --}}
    @if(isset($similar) && $similar->isNotEmpty())
        @php
            $isSoldNow = $account->sale_status === 'sold';
            $simNoun   = $account->noun();
        @endphp

        <section class="mt-16 sm:mt-20 pt-10 border-t border-white/10">
            @if($isSoldNow)
                <div class="mb-6 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-5 py-4">
                    <p class="font-semibold text-amber-200">{{ ucfirst($simNoun) }} ini sudah terjual.</p>
                    <p class="text-sm text-amber-100/70 mt-0.5">Ini {{ $simNoun }} lain yang masih tersedia dengan spesifikasi dan harga mirip.</p>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 sm:gap-4 mb-5 sm:mb-6">
                <div>
                    <h2 class="font-display text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
                        <span class="w-1.5 h-6 rounded-full bg-electric"></span>
                        {{ $account->isClan() ? 'CLAN SERUPA' : 'AKUN SERUPA' }}
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        @if($account->isClan())
                            Clan dengan rentang harga yang mirip.
                        @else
                            Town Hall dan rentang harga yang mirip dengan TH {{ $account->town_hall }}.
                        @endif
                    </p>
                </div>
                <a href="{{ route('catalog', array_filter(['type' => $account->listing_type, 'th' => $account->isClan() ? null : $account->town_hall])) }}"
                   class="inline-flex items-center gap-2 text-sm font-medium text-electric hover:text-white transition self-start sm:self-auto">
                    Lihat semua
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
                @foreach($similar as $item)
                    @include('components.account-card', ['account' => $item])
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

@push('scripts')
@php
    $galleryUrls = $account->images->map(function ($i) {
        return upload_url($i->image_path);
    })->values();

    $startImg   = $account->images->firstWhere('is_primary', true) ?? $account->images->first();
    $startIndex = $startImg
        ? max(0, (int) $account->images->search(fn ($i) => $i->id === $startImg->id))
        : 0;
@endphp

{{-- Lightbox foto akun: tampil penuh + zoom --}}
<div id="lightbox" class="hidden fixed inset-0 z-[100] bg-black/95" role="dialog" aria-modal="true" aria-label="Foto akun {{ $account->name }}">
    <div id="lbStage" class="absolute inset-0 flex items-center justify-center overflow-hidden touch-none select-none">
        <img id="lbImg" src="" alt="{{ $account->name }}" draggable="false"
             class="max-w-full max-h-full will-change-transform" style="transform-origin:center center">
    </div>

    <div class="absolute top-0 inset-x-0 flex items-center justify-between gap-3 p-3 sm:p-4 bg-gradient-to-b from-black/70 to-transparent pointer-events-none">
        <span id="lbCounter" class="pointer-events-auto px-3 py-1.5 rounded-full bg-white/10 text-white text-xs font-semibold">1 / 1</span>

        <div class="pointer-events-auto flex items-center gap-2">
            <button type="button" id="lbZoomOut" aria-label="Perkecil" class="w-10 h-10 shrink-0 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
            </button>
            <button type="button" id="lbReset" aria-label="Ukuran asli" class="h-10 min-w-[3.5rem] px-3 rounded-full bg-white/10 hover:bg-white/20 text-white text-xs font-semibold transition">
                <span id="lbZoomLabel">100%</span>
            </button>
            <button type="button" id="lbZoomIn" aria-label="Perbesar" class="w-10 h-10 shrink-0 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </button>
            <button type="button" id="lbClose" aria-label="Tutup" class="w-10 h-10 shrink-0 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition ml-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    @if($account->images->count() > 1)
        <button type="button" id="lbPrev" aria-label="Foto sebelumnya" class="w-10 h-10 shrink-0 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition absolute left-2 sm:left-4 top-1/2 -translate-y-1/2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" id="lbNext" aria-label="Foto berikutnya" class="w-10 h-10 shrink-0 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition absolute right-2 sm:right-4 top-1/2 -translate-y-1/2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
    @endif

    <p class="absolute bottom-4 inset-x-0 px-4 text-center text-[11px] text-slate-400 pointer-events-none">
        Cubit atau scroll untuk zoom &middot; ketuk 2&times; untuk memperbesar &middot; geser untuk pindah foto
    </p>
</div>

<script>
    const galleryImages = @json($galleryUrls);
    let galleryIndex = {{ $startIndex }};

    // Tandai foto aktif di gambar utama + thumbnail
    function setMainByIndex(i) {
        galleryIndex = i;

        const mainImage = document.getElementById('mainImage');
        if (mainImage && galleryImages[i]) {
            mainImage.src = galleryImages[i];
        }

        document.querySelectorAll('.thumbnail-btn').forEach(function (btn, idx) {
            btn.classList.toggle('border-electric', idx === i);
            btn.classList.toggle('border-transparent', idx !== i);
        });
    }

    function changeMainImage(src, button) {
        setMainByIndex(Math.max(0, galleryImages.indexOf(src)));
    }

    // ---- Lightbox foto: zoom (scroll / cubit / tombol), geser, ganti foto ----
    (function () {
        const box      = document.getElementById('lightbox');
        const stage    = document.getElementById('lbStage');
        const img      = document.getElementById('lbImg');
        const counter  = document.getElementById('lbCounter');
        const zoomText = document.getElementById('lbZoomLabel');
        const closeBtn = document.getElementById('lbClose');
        if (!box || !galleryImages.length) return;

        const MIN = 1, MAX = 5;
        let scale = 1, tx = 0, ty = 0;
        let isOpen = false, lastFocus = null, lastTap = 0;
        let drag = null, pinch = null;
        const pointers = new Map();

        function apply(animate) {
            img.style.transition = animate ? 'transform .2s ease' : 'none';
            img.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')';
            img.style.cursor = scale > 1 ? 'grab' : 'zoom-in';
            zoomText.textContent = Math.round(scale * 100) + '%';
        }

        // Batasi geseran supaya foto tidak keluar dari layar
        function clamp() {
            const maxX = Math.max(0, (img.clientWidth * scale - stage.clientWidth) / 2);
            const maxY = Math.max(0, (img.clientHeight * scale - stage.clientHeight) / 2);
            tx = Math.min(maxX, Math.max(-maxX, tx));
            ty = Math.min(maxY, Math.max(-maxY, ty));
        }

        // Zoom dengan titik (px, py) relatif ke tengah layar tetap di tempatnya
        function setZoom(next, px, py) {
            next = Math.min(MAX, Math.max(MIN, next));
            const k = next / scale;
            tx = px - k * (px - tx);
            ty = py - k * (py - ty);
            scale = next;
        }

        function zoomBy(factor, animate) {
            setZoom(scale * factor, 0, 0);
            clamp();
            apply(animate);
        }

        function reset(animate) {
            scale = 1; tx = 0; ty = 0;
            apply(animate);
        }

        function rel(x, y) {
            const r = stage.getBoundingClientRect();
            return { x: x - r.left - r.width / 2, y: y - r.top - r.height / 2 };
        }

        function show(i) {
            const n = galleryImages.length;
            const idx = (i % n + n) % n;
            img.src = galleryImages[idx];
            counter.textContent = (idx + 1) + ' / ' + n;
            reset(false);
            setMainByIndex(idx);

            // muat foto sebelah lebih dulu supaya pindah foto terasa cepat
            [idx + 1, idx - 1].forEach(function (j) {
                new Image().src = galleryImages[(j % n + n) % n];
            });
        }

        function openBox(i) {
            lastFocus = document.activeElement;
            box.classList.remove('hidden');
            document.documentElement.style.overflow = 'hidden';
            isOpen = true;
            show(typeof i === 'number' ? i : galleryIndex);
            closeBtn.focus();
        }

        function closeBox() {
            if (!isOpen) return;
            isOpen = false;
            box.classList.add('hidden');
            document.documentElement.style.overflow = '';
            pointers.clear(); drag = null; pinch = null;
            if (lastFocus && lastFocus.focus) lastFocus.focus();
        }

        function pinchState() {
            const pts = Array.from(pointers.values());
            const a = pts[0], b = pts[1];
            const mid = rel((a.x + b.x) / 2, (a.y + b.y) / 2);
            return { dist: Math.hypot(a.x - b.x, a.y - b.y) || 1, mx: mid.x, my: mid.y };
        }

        // ---- Pointer: geser (saat di-zoom), cubit 2 jari, ketuk 2x, geser pindah foto ----
        stage.addEventListener('pointerdown', function (e) {
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            stage.setPointerCapture(e.pointerId);
            pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

            if (pointers.size === 1) {
                drag = { sx: e.clientX, sy: e.clientY, tx0: tx, ty0: ty, t0: Date.now(), moved: false, onImg: e.target === img };
            } else if (pointers.size === 2) {
                drag = null;
                pinch = pinchState();
            }
        });

        stage.addEventListener('pointermove', function (e) {
            if (!pointers.has(e.pointerId)) return;
            pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

            if (pointers.size >= 2 && pinch) {
                const s = pinchState();
                setZoom(scale * s.dist / pinch.dist, s.mx, s.my);
                tx += s.mx - pinch.mx;
                ty += s.my - pinch.my;
                clamp();
                apply(false);
                pinch = s;
            } else if (drag) {
                const dx = e.clientX - drag.sx, dy = e.clientY - drag.sy;
                if (Math.abs(dx) > 6 || Math.abs(dy) > 6) drag.moved = true;
                if (scale > 1) {
                    tx = drag.tx0 + dx;
                    ty = drag.ty0 + dy;
                    clamp();
                    apply(false);
                    img.style.cursor = 'grabbing';
                }
            }
        });

        function endPointer(e) {
            if (!pointers.has(e.pointerId)) return;
            pointers.delete(e.pointerId);

            if (pinch) {
                if (pointers.size < 2) pinch = null;
                drag = null;
                return;
            }
            if (!drag) return;

            const d = drag;
            drag = null;
            const dx = e.clientX - d.sx, dy = e.clientY - d.sy;

            if (!d.moved && e.type === 'pointerup' && Date.now() - d.t0 < 500) {
                // Ketukan di area kosong = tutup
                if (!d.onImg) { closeBox(); return; }

                // Ketuk 2x pada foto = zoom masuk / reset
                const now = Date.now();
                if (now - lastTap < 300) {
                    lastTap = 0;
                    if (scale > 1) {
                        reset(true);
                    } else {
                        const p = rel(e.clientX, e.clientY);
                        setZoom(2.5, p.x, p.y);
                        clamp();
                        apply(true);
                    }
                } else {
                    lastTap = now;
                }
            } else if (scale === 1 && galleryImages.length > 1
                       && Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy) * 1.5) {
                // Geser kiri/kanan saat tidak di-zoom = foto berikutnya / sebelumnya
                show(galleryIndex + (dx < 0 ? 1 : -1));
            }
        }
        stage.addEventListener('pointerup', endPointer);
        stage.addEventListener('pointercancel', endPointer);

        // Scroll mouse / touchpad = zoom ke arah kursor
        stage.addEventListener('wheel', function (e) {
            e.preventDefault();
            const delta = e.deltaMode === 1 ? e.deltaY * 16 : e.deltaY;
            const p = rel(e.clientX, e.clientY);
            setZoom(scale * Math.exp(-delta * 0.0015), p.x, p.y);
            clamp();
            apply(false);
        }, { passive: false });

        // ---- Tombol ----
        function on(id, fn) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', fn);
        }
        on('lbClose', closeBox);
        on('lbZoomIn', function () { zoomBy(1.5, true); });
        on('lbZoomOut', function () { zoomBy(1 / 1.5, true); });
        on('lbReset', function () { reset(true); });
        on('lbPrev', function () { show(galleryIndex - 1); });
        on('lbNext', function () { show(galleryIndex + 1); });
        on('openLightboxBtn', function () { openBox(galleryIndex); });

        // ---- Keyboard ----
        document.addEventListener('keydown', function (e) {
            if (!isOpen) return;
            if (e.key === 'Escape') closeBox();
            else if (e.key === 'ArrowRight' && galleryImages.length > 1) show(galleryIndex + 1);
            else if (e.key === 'ArrowLeft' && galleryImages.length > 1) show(galleryIndex - 1);
            else if (e.key === '+' || e.key === '=') zoomBy(1.5, true);
            else if (e.key === '-') zoomBy(1 / 1.5, true);
            else if (e.key === '0') reset(true);
        });

        window.addEventListener('resize', function () {
            if (isOpen) { clamp(); apply(false); }
        });
    })();

    // ---- Salin tag akun / clan ----
    (function () {
        const btn = document.getElementById('copyTagBtn');
        if (!btn) return;
        const tag = btn.dataset.tag;

        function done() {
            btn.textContent = 'Tersalin!';
            setTimeout(function () { btn.textContent = 'Salin'; }, 1800);
        }

        function fallback() {
            const ta = document.createElement('textarea');
            ta.value = tag;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); done(); }
            catch (e) { window.prompt('Salin tag ini:', tag); }
            document.body.removeChild(ta);
        }

        btn.addEventListener('click', function () {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(tag).then(done).catch(fallback);
            } else {
                fallback();
            }
        });
    })();

    // ---- Fitur bagikan ----
    (function () {
        const shareData = {
            title: @json($shareTitle),
            text: @json($shareText),
            url: @json($shareUrl)
        };

        // Tombol "Bagikan" bawaan perangkat (muncul di HP / browser yang mendukung)
        const nativeBtn = document.getElementById('nativeShareBtn');
        if (nativeBtn && navigator.share) {
            nativeBtn.classList.remove('hidden');
            nativeBtn.classList.add('flex');
            nativeBtn.addEventListener('click', function () {
                navigator.share(shareData).catch(function () {});
            });
        }

        // Salin link
        const copyBtn = document.getElementById('copyLinkBtn');
        if (!copyBtn) return;
        const label = copyBtn.querySelector('span');

        function done() {
            label.textContent = 'Tersalin!';
            setTimeout(function () { label.textContent = 'Salin Link'; }, 1800);
        }

        function fallbackCopy() {
            const ta = document.createElement('textarea');
            ta.value = shareData.url;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); done(); }
            catch (e) { window.prompt('Salin link ini:', shareData.url); }
            document.body.removeChild(ta);
        }

        copyBtn.addEventListener('click', function () {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(shareData.url).then(done).catch(fallbackCopy);
            } else {
                fallbackCopy();
            }
        });
    })();
</script>
@endpush