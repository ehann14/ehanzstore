@extends('layouts.admin')

@section('title', 'Stok Lama')

@section('content')
@php
    $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $isSuper = auth()->user()->isSuperAdmin();
@endphp

<div class="max-w-6xl mx-auto pb-20">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white">Pengingat Stok Lama</h1>
        <p class="text-sm text-slate-400 mt-1">
            Akun dan clan yang sudah lebih dari {{ $days }} hari terpajang tapi belum laku, lengkap dengan saran penurunan harga.
            @unless($isSuper) Hanya akun milik Anda. @endunless
        </p>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ $summary['count'] }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Stok lama</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ $summary['oldest_days'] }}<span class="text-base text-slate-500"> hari</span></p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Paling lama</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-lg sm:text-2xl font-display font-bold text-white truncate">{{ $rupiah($summary['capital']) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Modal tertahan</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-lg sm:text-2xl font-display font-bold text-white truncate">{{ $rupiah($summary['value']) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Nilai jual</p>
        </div>
    </div>

    {{-- Filter jenis --}}
    <div class="flex gap-2 mb-6 overflow-x-auto">
        @foreach([[null, 'Semua', $counts['all']], ['account', 'Akun', $counts['account']], ['clan', 'Clan', $counts['clan']]] as [$key, $label, $n])
            <a href="{{ route('admin.stale.index', $key ? ['type' => $key] : []) }}"
               class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide transition {{ ($type ?? null) === $key ? 'bg-electric text-white' : 'bg-white/5 text-slate-400 border border-white/5 hover:bg-white/10 hover:text-white' }}">
                {{ $label }} ({{ $n }})
            </a>
        @endforeach
    </div>

    @if($accounts->isEmpty())
        <div class="glass-panel rounded-3xl p-16 text-center border border-white/5">
            <div class="w-16 h-16 bg-emerald-500/10 rounded-full flex items-center justify-center mx-auto mb-4 text-emerald-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-slate-200 mb-1 font-medium">Tidak ada stok lama.</p>
            <p class="text-sm text-slate-500">Semua stok terpajang kurang dari {{ $days }} hari atau sudah laku.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($accounts as $account)
                @php
                    $isClan     = $account->isClan();
                    $listed     = $account->daysListed();
                    $suggestion = $account->priceSuggestion();
                    $timedDeal  = $account->dealEndsAt();
                    $viewCount  = (int) ($views[$account->id] ?? 0);
                    $imageUrl   = $account->primaryImage?->image_path ? upload_url($account->primaryImage->image_path) : null;

                    $ageTone = $listed >= 90 ? 'bg-red-500/15 text-red-300 border-red-500/30'
                             : ($listed >= 60 ? 'bg-orange-500/15 text-orange-300 border-orange-500/30'
                             : 'bg-amber-500/15 text-amber-300 border-amber-500/30');

                    if ($viewCount >= 10) {
                        $hint = 'Ramai dilihat tapi belum laku: harga kemungkinan terlalu tinggi.';
                    } elseif ($viewCount < 3) {
                        $hint = 'Sepi peminat: coba foto lebih jelas, tandai Hot Deal, atau bagikan lagi ke WhatsApp/IG.';
                    } else {
                        $hint = null;
                    }
                @endphp

                <div class="glass-panel rounded-2xl p-3 sm:p-4 border border-white/5 hover:border-amber-500/30 transition-colors">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-4">

                        {{-- Info akun --}}
                        <div class="flex items-center gap-4 min-w-0 flex-1">
                            <div class="w-20 h-20 shrink-0 rounded-xl overflow-hidden bg-midnight border border-white/10">
                                @if($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $account->name }}" loading="lazy" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-900">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded-md bg-electric/15 text-blue-300 text-[10px] font-bold uppercase">{{ $account->badgeLabel() }}</span>
                                    <span class="text-[11px] font-mono text-slate-500">{{ $account->account_code }}</span>
                                    @if($isSuper && $account->creator)
                                        <span class="text-[11px] text-slate-600">&middot; {{ $account->creator->name }}</span>
                                    @endif
                                </div>
                                <h3 class="font-semibold text-white truncate">{{ $account->name }}</h3>
                                <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                    <span class="px-2.5 py-0.5 rounded-full border text-[11px] font-semibold {{ $ageTone }}">{{ $listed }} hari belum laku</span>
                                    <span class="text-[11px] text-slate-500">{{ $viewCount }}x dilihat (30 hari)</span>
                                </div>
                            </div>
                        </div>

                        {{-- Harga & saran --}}
                        <div class="lg:w-[22rem] shrink-0 rounded-xl bg-white/[0.03] border border-white/5 p-3">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Harga sekarang</span>
                                <span class="font-bold text-white">{{ $rupiah($account->price) }}</span>
                            </div>
                            @if((float) $account->cost_price > 0)
                                <div class="flex items-baseline justify-between gap-3 mt-0.5">
                                    <span class="text-[11px] text-slate-600">Modal</span>
                                    <span class="text-xs text-slate-400">{{ $rupiah($account->cost_price) }}</span>
                                </div>
                            @endif

                            <div class="border-t border-white/5 mt-2.5 pt-2.5">
                                @if($suggestion)
                                    <div class="flex items-baseline justify-between gap-3">
                                        <span class="text-[11px] uppercase tracking-wider text-amber-300 font-semibold">Saran turun {{ rtrim(rtrim(number_format($suggestion['percent'], 1, ',', ''), '0'), ',') }}%</span>
                                        <span class="font-bold text-amber-300">{{ $rupiah($suggestion['price']) }}</span>
                                    </div>
                                    @if($suggestion['cost_known'])
                                        <p class="text-[11px] mt-0.5 {{ $suggestion['at_cost'] ? 'text-red-400' : 'text-slate-500' }}">
                                            @if($suggestion['at_cost'])
                                                Mentok di harga modal, laba {{ $rupiah($suggestion['profit']) }}.
                                            @else
                                                Estimasi laba {{ $rupiah($suggestion['profit']) }}.
                                            @endif
                                        </p>
                                    @else
                                        <p class="text-[11px] text-slate-600 mt-0.5">Modal belum diisi, isi di halaman edit agar saran tidak rugi.</p>
                                    @endif
                                @else
                                    <p class="text-[11px] text-slate-500">Harga sudah mendekati modal. Pertimbangkan Hot Deal atau ditahan dulu.</p>
                                @endif
                            </div>
                        </div>

                        {{-- Aksi --}}
                        <div class="flex lg:flex-col gap-2 shrink-0 lg:w-36">
                            @if($suggestion && ! $timedDeal)
                                <form action="{{ route('admin.stale.apply', $account) }}" method="POST" class="flex-1"
                                      onsubmit="return confirm('Turunkan harga {{ $account->account_code }} menjadi {{ $rupiah($suggestion['price']) }}?')">
                                    @csrf
                                    <button type="submit" class="w-full px-3 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-900 text-xs font-bold transition">Terapkan saran</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.accounts.edit', $account) }}"
                               class="flex-1 text-center px-3 py-2 rounded-lg border border-white/10 text-xs font-semibold text-slate-300 hover:bg-white/5 transition">Edit / Hot Deal</a>
                        </div>
                    </div>

                    @if($timedDeal)
                        <p class="text-[11px] text-sky-300 mt-3">Sedang ada promo berbatas waktu, harga diatur lewat halaman edit.</p>
                    @endif
                    @if($hint)
                        <p class="text-[11px] text-slate-500 mt-3">{{ $hint }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-8">{{ $accounts->links() }}</div>

        <p class="text-[11px] text-slate-600 mt-6 leading-relaxed">
            Saran: {{ $days }}+ hari turun 5%, 60+ hari turun 10%, 90+ hari turun 15%, dibulatkan dan tidak pernah di bawah modal.
            Itu hanya patokan, sesuaikan dengan harga pasar.
        </p>
    @endif
</div>
@endsection