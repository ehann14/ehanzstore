@extends('layouts.app')

@section('title', 'Cek Garansi - EHANZSTORE.ID')
@section('description', 'Cek sisa garansi 7 hari akun Clash of Clans kamu dengan memasukkan kode transaksi EHANZSTORE.ID.')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
@php
    $inputCls = 'w-full bg-white/5 border border-white/10 rounded-xl pl-12 pr-4 py-4 text-base sm:text-lg font-mono tracking-wider uppercase text-white placeholder-slate-500 placeholder:normal-case placeholder:tracking-normal placeholder:font-sans focus:border-electric focus:bg-white/10 outline-none transition';

    $waClaim = $sale
        ? $storeWaUrl . '?text=' . rawurlencode("Halo Admin EHANZSTORE, saya mau klaim garansi.\nKode transaksi: {$sale->transaction_code}\nKode akun: {$sale->account_code_snapshot}\nMasalah: ")
        : $storeWaUrl;

    $waAsk = $storeWaUrl . '?text=' . rawurlencode('Halo Admin EHANZSTORE, saya mau tanya soal garansi akun saya.' . ($raw !== '' ? "\nKode transaksi: " . strtoupper($raw) : ''));
@endphp

<section class="relative pt-28 sm:pt-36 pb-20 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-royal/10 to-transparent pointer-events-none"></div>

    <div class="max-w-2xl mx-auto px-4 relative z-10">

        {{-- Judul --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-green-500/10 border border-green-500/30 text-green-400 text-xs font-semibold uppercase tracking-widest mb-5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Cek Garansi
            </div>
            <h1 class="font-display text-3xl sm:text-5xl font-bold text-white tracking-tight">
                Sisa Garansi <span class="text-electric glow-text">Kamu</span>
            </h1>
            <p class="text-slate-400 mt-4 text-sm sm:text-base">
                Masukkan kode transaksi dari admin untuk melihat sisa garansi 7 hari akunmu.
            </p>
        </div>

        {{-- Form --}}
        <form action="{{ route('warranty.check') }}" method="GET" class="glass-strong rounded-3xl p-5 sm:p-6 border border-white/5 shadow-2xl shadow-black/40">
            <label for="kode" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Kode Transaksi</label>
            <div class="relative">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 8V5a2 2 0 012-2z"/></svg>
                <input id="kode" type="text" name="kode" value="{{ $raw }}" maxlength="20" required
                       autocomplete="off" autocapitalize="characters" spellcheck="false"
                       placeholder="Contoh: TRX-AB12CD34" class="{{ $inputCls }}">
            </div>
            <p class="text-[11px] text-slate-500 mt-2">Kode dikirim admin lewat WhatsApp saat serah terima akun. Huruf besar/kecil tidak berpengaruh.</p>

            @if($error)
                <p class="mt-3 text-sm text-red-400">{{ $error }}</p>
            @endif

            <button type="submit" class="mt-4 w-full bg-electric hover:bg-blue-500 text-white font-bold py-3.5 rounded-xl transition active:scale-[0.98] shadow-lg shadow-electric/20">
                Cek Garansi
            </button>
        </form>

        {{-- Hasil --}}
        @if($state)
            <div class="mt-6">

                @if($state === 'not_found')
                    <div class="rounded-3xl p-6 sm:p-8 bg-gradient-to-br from-red-900/25 to-rose-900/10 border border-red-500/20 text-center">
                        <div class="w-14 h-14 mx-auto rounded-full bg-red-500/10 border border-red-500/30 text-red-400 flex items-center justify-center mb-4">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </div>
                        <h2 class="font-display text-xl font-bold text-white">Kode transaksi tidak ditemukan</h2>
                        <p class="text-sm text-slate-400 mt-2 leading-relaxed">Pastikan kode sama persis dengan yang dikirim admin. Kalau masih tidak ketemu, tanyakan langsung ke admin.</p>
                        <a href="{{ $waAsk }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 mt-5 bg-green-600 hover:bg-green-500 text-white px-6 py-3 rounded-full text-sm font-bold transition">
                            Tanya Admin via WhatsApp
                        </a>
                    </div>

                @elseif($state === 'not_applicable')
                    <div class="rounded-3xl p-6 sm:p-8 glass border border-white/10 text-center">
                        <h2 class="font-display text-xl font-bold text-white">Garansi tidak berlaku</h2>
                        <p class="text-sm text-slate-400 mt-2 leading-relaxed">Transaksi <span class="font-mono text-slate-300">{{ $sale->transaction_code }}</span> belum selesai atau sudah dibatalkan, jadi belum ada garansi yang berjalan.</p>
                        <a href="{{ $waAsk }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 mt-5 bg-white/5 hover:bg-white/10 border border-white/10 text-white px-6 py-3 rounded-full text-sm font-semibold transition">
                            Hubungi Admin
                        </a>
                    </div>

                @elseif($state === 'not_started')
                    <div class="rounded-3xl p-6 sm:p-8 bg-gradient-to-br from-amber-900/25 to-amber-900/5 border border-amber-500/20 text-center">
                        <h2 class="font-display text-xl font-bold text-white">Garansi belum dimulai</h2>
                        <p class="text-sm text-slate-400 mt-2 leading-relaxed">
                            Transaksi <span class="font-mono text-slate-300">{{ $sale->transaction_code }}</span> sudah tercatat, tetapi waktu serah terima akun belum dikonfirmasi admin.
                            Garansi 7 hari dihitung sejak serah terima selesai.
                        </p>
                        <a href="{{ $waAsk }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-2 mt-5 bg-green-600 hover:bg-green-500 text-white px-6 py-3 rounded-full text-sm font-bold transition">
                            Konfirmasi ke Admin
                        </a>
                    </div>

                @else
                    @php
                        $active     = $state === 'active';
                        $account    = $sale->account;
                        $imageUrl   = $account?->primaryImage?->image_path ? upload_url($account->primaryImage->image_path) : null;
                        [$badgeText, $badgeCls] = $sale->warrantyBadge();
                        $lowTime    = $active && $progress['remaining'] < 15;
                        $barCls     = $lowTime ? 'bg-amber-400' : 'bg-emerald-400';
                    @endphp

                    <div class="glass-strong rounded-3xl border {{ $active ? 'border-emerald-500/20' : 'border-red-500/20' }} overflow-hidden shadow-2xl shadow-black/40">

                        {{-- Akun --}}
                        <div class="flex items-center gap-4 p-4 sm:p-5 border-b border-white/5">
                            <div class="w-20 h-16 shrink-0 rounded-xl overflow-hidden bg-midnight border border-white/10">
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] text-slate-500 uppercase tracking-wider">Akun yang dibeli</p>
                                <p class="font-display font-bold text-white truncate">{{ $account?->name ?? $sale->account_code_snapshot }}</p>
                                <p class="text-[11px] font-mono text-slate-500">{{ $sale->account_code_snapshot }}</p>
                            </div>
                        </div>

                        {{-- Status & sisa waktu --}}
                        <div class="p-5 sm:p-6 text-center">
                            <span {!! $sale->countdownAttrs('Garansi aktif · sisa ', 'Garansi berakhir', true) !!}
                                  class="inline-block px-3 py-1 rounded-full border text-xs font-semibold {{ $badgeCls }}">{{ $badgeText }}</span>

                            @if($active)
                                <p class="font-display text-4xl sm:text-5xl font-bold text-white mt-4">
                                    {{ ucfirst($sale->warrantyRemainingText()) }}
                                </p>
                                <p class="text-xs text-slate-500 mt-1">dari total {{ \App\Models\Sale::WARRANTY_DAYS }} hari garansi</p>

                                <div class="mt-5 h-2.5 rounded-full bg-white/10 overflow-hidden" role="progressbar"
                                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress['remaining'] }}">
                                    <div class="h-full rounded-full {{ $barCls }}" style="width: {{ $progress['remaining'] }}%"></div>
                                </div>
                                <div class="flex justify-between text-[11px] text-slate-500 mt-1.5">
                                    <span>Serah terima</span>
                                    <span>Garansi berakhir</span>
                                </div>
                            @else
                                <p class="font-display text-3xl sm:text-4xl font-bold text-white mt-4">Garansi Berakhir</p>
                                <p class="text-sm text-slate-400 mt-2">Masa garansi {{ \App\Models\Sale::WARRANTY_DAYS }} hari untuk akun ini sudah selesai.</p>
                            @endif
                        </div>

                        {{-- Rincian --}}
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-px bg-white/5 border-t border-white/5 text-sm">
                            <div class="bg-slate-900/60 p-4">
                                <dt class="text-[11px] text-slate-500 uppercase tracking-wider">Kode transaksi</dt>
                                <dd class="font-mono text-slate-200 mt-1">{{ $sale->transaction_code }}</dd>
                            </div>
                            <div class="bg-slate-900/60 p-4">
                                <dt class="text-[11px] text-slate-500 uppercase tracking-wider">Pembeli</dt>
                                <dd class="text-slate-200 mt-1">{{ $buyer ?? '-' }}</dd>
                            </div>
                            <div class="bg-slate-900/60 p-4">
                                <dt class="text-[11px] text-slate-500 uppercase tracking-wider">Serah terima</dt>
                                <dd class="text-slate-200 mt-1">{{ $sale->handoverLabel() ?? '-' }}</dd>
                            </div>
                            <div class="bg-slate-900/60 p-4">
                                <dt class="text-[11px] text-slate-500 uppercase tracking-wider">{{ $active ? 'Garansi berakhir' : 'Berakhir pada' }}</dt>
                                <dd class="text-slate-200 mt-1">{{ $sale->warrantyUntilLabel() }}</dd>
                            </div>
                        </dl>

                        {{-- Aksi --}}
                        <div class="p-5 sm:p-6 border-t border-white/5 text-center">
                            @if($active)
                                <p class="text-sm text-slate-400 mb-4">Ada masalah dengan akunmu? Ajukan klaim sebelum garansi berakhir.</p>
                                <a href="{{ $waClaim }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-500 text-white px-7 py-3.5 rounded-full font-bold transition shadow-lg shadow-green-900/30">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                                    Klaim Garansi via WhatsApp
                                </a>
                            @else
                                <p class="text-sm text-slate-400 mb-4">Punya pertanyaan soal transaksi ini? Admin siap membantu.</p>
                                <a href="{{ $waAsk }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10 border border-white/10 text-white px-7 py-3.5 rounded-full font-semibold transition">
                                    Hubungi Admin
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <p class="text-center text-sm text-slate-500 mt-8">
            Ingin tahu apa saja yang dijamin?
            <a href="{{ route('warranty') }}" class="text-electric hover:text-white transition">Baca ketentuan garansi</a>
        </p>
    </div>
</section>
@endsection