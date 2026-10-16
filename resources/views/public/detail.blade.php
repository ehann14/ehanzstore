@extends('layouts.app')

@section('title', $account->name . ' - EHANZSTORE.ID')

@php
    $shareUrl   = route('account.detail', $account->account_code);
    $sharePrice = 'Rp ' . number_format($account->price, 0, ',', '.');
    $shareTitle = $account->name . ' (TH ' . $account->town_hall . ') - ' . $sharePrice;
    $shareText  = 'Akun Clash of Clans ' . $account->name . ' | TH ' . $account->town_hall
                . ' | Kode ' . $account->account_code . ' | ' . $sharePrice . ' - EHANZSTORE.ID';
    
    // Perbaikan: Menggunakan asset('storage/...') untuk kompatibilitas symlink & hosting
    $ogImage    = ($account->primaryImage && !empty($account->primaryImage->image_path))
                ? asset('storage/' . $account->primaryImage->image_path)
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

    <div class="grid lg:grid-cols-2 gap-12">

        {{-- Galeri Foto --}}
        <div class="space-y-4">
            @php
                $primaryImg = $account->images->firstWhere('is_primary', true)
                    ?? $account->images->first();
            @endphp

            <div class="aspect-[4/3] rounded-3xl overflow-hidden bg-midnight border border-white/10 relative group">
                @if($primaryImg)
                    <img
                        id="mainImage"
                        src="{{ asset('storage/' . $primaryImg->image_path) }}"
                        alt="{{ $account->name }}"
                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                    >
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
                            onclick="changeMainImage('{{ asset('storage/' . $img->image_path) }}', this)"
                            class="aspect-square rounded-xl overflow-hidden border-2 {{ $img->id === ($primaryImg->id ?? null) ? 'border-electric' : 'border-transparent hover:border-white/30' }} transition-all thumbnail-btn"
                        >
                            <img
                                src="{{ asset('storage/' . $img->image_path) }}"
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
                        TH {{ $account->town_hall }}
                    </span>
                </div>

                <p class="text-slate-400 text-sm font-mono bg-white/5 inline-block px-3 py-1.5 rounded-lg border border-white/5">
                    KODE: {{ $account->account_code }}
                </p>
            </div>

            {{-- Harga dan Tombol Pembelian --}}
            <div class="glass rounded-2xl p-6 space-y-4 border-l-4 border-electric">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-1">
                        Harga Jual
                    </p>

                    <p class="text-4xl font-display font-bold text-white">
                        Rp {{ number_format($account->price, 0, ',', '.') }}
                    </p>
                </div>

                <a
                    href="https://wa.me/6282130559126?text={{ urlencode('Halo Admin EHANZSTORE, saya tertarik dengan akun ' . $account->name . ' (Kode: ' . $account->account_code . '). Apakah masih tersedia?') }}"
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
                    Spesifikasi Akun
                </h3>

                @php
                    $specs = [
                        [
                            'label' => 'Barbarian King',
                            'val' => 'Lv. ' . ($account->barbarian_king_level ?? 0),
                        ],
                        [
                            'label' => 'Archer Queen',
                            'val' => 'Lv. ' . ($account->archer_queen_level ?? 0),
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
                            'label' => 'Builder',
                            'val' => ($account->builder_count ?? 5) . ' Orang',
                        ],
                        [
                            'label' => 'Gems',
                            'val' => number_format($account->gems ?? 0) . ' Gems',
                        ],
                    ];
                @endphp

                <div class="grid grid-cols-2 gap-4">
                    @foreach($specs as $spec)
                        <div class="bg-white/5 rounded-xl p-4 border border-white/5">
                            <p class="text-[10px] text-slate-500 uppercase tracking-wider mb-1">
                                {{ $spec['label'] }}
                            </p>

                            <p class="text-sm font-bold text-white">
                                {{ $spec['val'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Informasi Keamanan --}}
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

                        <span class="text-sm font-bold {{ $account->email_change_status === 'Changeable' ? 'text-green-400' : 'text-yellow-400' }}">
                            {{ $account->email_change_status === 'Changeable' ? 'Bisa Ganti' : 'Tidak Bisa Ganti' }}
                        </span>
                    </div>
                </div>
            </div>

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
</div>
@endsection

@push('scripts')
<script>
    function changeMainImage(src, button) {
        const mainImage = document.getElementById('mainImage');

        if (mainImage) {
            mainImage.src = src;
        }

        document.querySelectorAll('.thumbnail-btn').forEach(btn => {
            btn.classList.remove('border-electric');
            btn.classList.add('border-transparent');
        });

        button.classList.remove('border-transparent');
        button.classList.add('border-electric');
    }

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