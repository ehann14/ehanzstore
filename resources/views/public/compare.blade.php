@extends('layouts.app')

@section('title', 'Bandingkan Akun - EHANZSTORE.ID')

@section('content')
@php
    $count    = $accounts->count();
    $isClan   = $accounts->first()?->isClan() ?? false;
    $noun     = $isClan ? 'clan' : 'akun';
    $codes    = $accounts->pluck('account_code');
    $removeUrl = fn ($code) => route('compare', ['akun' => $codes->reject(fn ($c) => $c === $code)->implode(',')]);

    // Data untuk menyamakan pilihan di localStorage dengan isi halaman ini
    $syncItems = $accounts->map(fn ($a) => [
        'code'  => $a->account_code,
        'name'  => $a->name,
        'type'  => $a->listing_type,
        'img'   => ($a->primaryImage && ! empty($a->primaryImage->image_path)) ? upload_url($a->primaryImage->image_path) : '',
        'price' => (string) (float) $a->price,
    ])->values();

    /*
     * Baris perbandingan. Tiap baris punya skor (angka, untuk menandai nilai terbaik)
     * dan teks (yang ditampilkan). $best: 'max' = makin besar makin bagus, 'min' = makin kecil makin bagus,
     * null = tidak ditandai.
     */
    $rows = [];
    $add = function (string $label, callable $score, callable $text, ?string $best = 'max') use (&$rows, $accounts) {
        $rows[] = [
            'label'  => $label,
            'scores' => $accounts->map($score)->values()->all(),
            'texts'  => $accounts->map($text)->values()->all(),
            'best'   => $best,
        ];
    };

    if ($isClan) {
        $add('Level Clan',    fn ($a) => (int) $a->clan_level,         fn ($a) => 'Lv. ' . (int) $a->clan_level);
        $add('Capital Hall',  fn ($a) => (int) $a->capital_hall_level, fn ($a) => 'Lv. ' . (int) $a->capital_hall_level);
        $add('Member',        fn ($a) => (int) $a->clan_members,       fn ($a) => (int) $a->clan_members . ' / ' . \App\Models\CocAccount::CLAN_MAX_MEMBERS);
        $add('Liga Clan',
            fn ($a) => (int) array_search($a->clan_league, \App\Models\CocAccount::CLAN_LEAGUES, true),
            fn ($a) => $a->clan_league ?: 'Unranked');
    } else {
        $add('Town Hall',     fn ($a) => (int) $a->town_hall,     fn ($a) => 'TH ' . (int) $a->town_hall);
        $add('Level Akun',    fn ($a) => (int) $a->account_level, fn ($a) => $a->account_level ? 'Lv. ' . (int) $a->account_level : '-');
        $add('Tukang',        fn ($a) => (int) $a->builder_count, fn ($a) => (int) $a->builder_count . ' Orang');
        $add('Gems',          fn ($a) => (int) $a->gems,          fn ($a) => number_format((int) $a->gems, 0, ',', '.'));

        foreach ([
            'Barbarian King' => 'barbarian_king_level',
            'Archer Queen'   => 'archer_queen_level',
            'Minion Prince'  => 'minion_prince_level',
            'Grand Warden'   => 'grand_warden_level',
            'Royal Champion' => 'royal_champion_level',
            'Dragon Duke'    => 'dragon_duke_level',
        ] as $label => $col) {
            $add($label, fn ($a) => (int) $a->{$col}, fn ($a) => 'Lv. ' . (int) $a->{$col});
        }

        $add('Status Proteksi',
            fn ($a) => $a->protection_status === 'Protected' ? 1 : 0,
            fn ($a) => $a->protection_status === 'Protected' ? 'ON' : 'OFF');

        $add('Status Email/Gmail',
            fn ($a) => $a->email_change_status === 'Changeable' ? ($a->emailChangeDaysLeft() ? 1 : 2) : 0,
            fn ($a) => $a->emailStatusLabel());

        $add('CN', fn ($a) => null, fn ($a) => $a->cn_information ?: '-', null);
    }

    $labelCell = 'sticky left-0 z-10 bg-[#0a1024] px-3 sm:px-4 py-3 text-left text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-slate-500 align-middle w-24 sm:w-40';
    $tableMin  = (6 + 10 * max($count, 1)) . 'rem';
@endphp

<div class="pt-24 sm:pt-32 pb-24 max-w-7xl mx-auto px-4">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-slate-500 mb-6">
        <a href="{{ route('home') }}" class="hover:text-electric transition">Beranda</a>
        <span>/</span>
        <a href="{{ route('catalog') }}" class="hover:text-electric transition">Katalog</a>
        <span>/</span>
        <span class="text-white font-medium">Bandingkan</span>
    </nav>

    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display text-2xl sm:text-4xl font-bold text-white">Bandingkan {{ ucfirst($noun) }}</h1>
            <p class="text-sm text-slate-500 mt-1">Bandingkan 2 sampai 3 {{ $noun }} berdampingan. Nilai terbaik di tiap baris ditandai hijau.</p>
        </div>
        <a href="{{ route('catalog', $count ? ['type' => $isClan ? 'clan' : 'account'] : []) }}"
           class="inline-flex items-center gap-2 self-start sm:self-auto px-4 py-2 rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 text-white text-sm font-semibold transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ $count && $count < 3 ? 'Tambah ' . $noun . ' lagi' : 'Ke katalog' }}
        </a>
    </div>

    @if($mixed)
        <div class="mb-5 rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
            Akun dan clan tidak bisa dibandingkan sekaligus. Yang ditampilkan hanya {{ $noun }} pertama yang dipilih.
        </div>
    @endif

    @if($count === 0)
        <div class="glass rounded-3xl p-8 sm:p-16 text-center border border-white/5">
            <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
            </div>
            <h2 class="text-xl font-display font-bold text-white mb-2">Belum ada yang dipilih</h2>
            <p class="text-slate-500 text-sm mb-6">Buka katalog, lalu tekan tombol "Bandingkan" di kartu akun (pilih 2 sampai 3). Akun yang sudah terjual tidak bisa dibandingkan.</p>
            <a href="{{ route('catalog') }}" class="inline-flex items-center gap-2 bg-electric hover:bg-blue-500 text-white px-6 py-3 rounded-full text-sm font-bold transition">Buka Katalog</a>
        </div>
    @else
        @if($count < 2)
            <div class="mb-5 rounded-xl border border-electric/30 bg-electric/10 px-4 py-3 text-sm text-blue-100">
                Pilih minimal satu {{ $noun }} lagi dari katalog untuk mulai membandingkan.
            </div>
        @endif

        <div class="overflow-x-auto rounded-2xl border border-white/10 bg-white/[0.02]">
            <table class="w-full table-fixed border-collapse text-sm" style="min-width: {{ $tableMin }}">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 bg-[#0a1024] w-24 sm:w-40"></th>
                        @foreach($accounts as $a)
                            @php
                                $img = ($a->primaryImage && ! empty($a->primaryImage->image_path)) ? upload_url($a->primaryImage->image_path) : null;
                            @endphp
                            <th class="p-2.5 sm:p-4 align-top text-left font-normal">
                                <div class="relative aspect-[4/3] rounded-xl overflow-hidden bg-midnight border border-white/10">
                                    @if($img)
                                        <img src="{{ $img }}" alt="{{ $a->name }}" loading="lazy" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-slate-700 text-xs">Tanpa foto</div>
                                    @endif

                                    <span class="absolute top-2 left-2 px-2 py-0.5 bg-electric text-white text-[10px] sm:text-[11px] font-bold rounded-md shadow-lg uppercase tracking-wide">
                                        {{ $a->badgeLabel() }}
                                    </span>

                                    <a href="{{ $removeUrl($a->account_code) }}" aria-label="Keluarkan {{ $a->name }} dari perbandingan"
                                       class="absolute top-2 right-2 w-7 h-7 rounded-full bg-black/60 hover:bg-red-600 text-white flex items-center justify-center transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </a>

                                    @if($a->isHotDeal())
                                        <x-hot-deal-badge class="absolute bottom-2 left-2" />
                                    @endif
                                </div>

                                <a href="{{ route('account.detail', $a->account_code) }}"
                                   class="block mt-3 font-display font-bold text-sm sm:text-base text-white hover:text-electric transition line-clamp-2">{{ $a->name }}</a>
                                <p class="text-[10px] sm:text-[11px] font-mono text-slate-500 mt-0.5 truncate">{{ $a->account_code }}</p>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    {{-- Harga: yang paling murah ditandai --}}
                    @php
                        $prices    = $accounts->map(fn ($a) => (float) $a->price)->values()->all();
                        $bestPrice = ($count > 1 && count(array_unique($prices)) > 1) ? min($prices) : null;
                    @endphp
                    <tr class="border-t border-white/5 bg-white/[0.02]">
                        <th class="{{ $labelCell }}">Harga</th>
                        @foreach($accounts as $i => $a)
                            @php $isBest = $bestPrice !== null && $prices[$i] == $bestPrice; @endphp
                            <td class="px-2.5 sm:px-4 py-3 align-top">
                                @if($a->hasDiscount())
                                    <p class="text-[11px] sm:text-xs text-slate-500 line-through leading-none">Rp {{ number_format($a->original_price, 0, ',', '.') }}</p>
                                @endif
                                <p class="font-display font-bold text-base sm:text-xl {{ $isBest ? 'text-emerald-300' : ($a->hasDiscount() ? 'text-orange-400' : 'text-white') }}">
                                    Rp {{ number_format($a->price, 0, ',', '.') }}
                                </p>
                                @if($a->hasDiscount())
                                    <span class="inline-block mt-1 px-1.5 py-0.5 rounded bg-red-500/15 border border-red-500/30 text-red-300 text-[10px] font-bold">Hemat {{ $a->discountPercent() }}%</span>
                                @endif
                                @if($isBest)
                                    <span class="block mt-1 text-[10px] font-semibold text-emerald-400">Termurah</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>

                    @foreach($rows as $row)
                        @php
                            $valid  = array_filter($row['scores'], fn ($v) => $v !== null);
                            $target = null;
                            if ($row['best'] && count($valid) > 1 && count(array_unique($valid)) > 1) {
                                $target = $row['best'] === 'max' ? max($valid) : min($valid);
                            }
                        @endphp
                        <tr class="border-t border-white/5">
                            <th class="{{ $labelCell }}">{{ $row['label'] }}</th>
                            @foreach($row['texts'] as $i => $text)
                                @php $isBest = $target !== null && $row['scores'][$i] !== null && $row['scores'][$i] == $target; @endphp
                                <td class="px-2.5 sm:px-4 py-3 align-middle break-words text-xs sm:text-sm {{ $isBest ? 'text-emerald-300 font-bold' : 'text-slate-200' }}">
                                    @if($isBest)
                                        <svg class="inline w-3.5 h-3.5 -mt-0.5 mr-0.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                    {{ $text }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach

                    {{-- Aksi --}}
                    <tr class="border-t border-white/5 bg-white/[0.02]">
                        <th class="{{ $labelCell }}"></th>
                        @foreach($accounts as $a)
                            <td class="px-2.5 sm:px-4 py-4 align-top space-y-2">
                                <a href="{{ account_wa_url($a, $storeWa ?? null) }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="flex items-center justify-center gap-2 w-full bg-green-600 hover:bg-green-500 text-white text-xs sm:text-sm font-bold py-2.5 rounded-xl transition active:scale-[0.98]">
                                    Beli via WA
                                </a>
                                <a href="{{ route('account.detail', $a->account_code) }}"
                                   class="flex items-center justify-center w-full border border-white/10 bg-white/5 hover:bg-white/10 text-slate-200 text-xs sm:text-sm font-semibold py-2.5 rounded-xl transition">
                                    Lihat Detail
                                </a>
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-[11px] text-slate-600 mt-3">Geser tabel ke samping jika ada kolom yang terpotong di layar kecil.</p>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Samakan pilihan di localStorage dengan isi halaman ini (akun yang sudah terjual / dihapus otomatis hilang)
    (function () {
        try {
            localStorage.setItem('ehz_compare', JSON.stringify(@json($syncItems)));
        } catch (e) {}
    })();
</script>
@endpush