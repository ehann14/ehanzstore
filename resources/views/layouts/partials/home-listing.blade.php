{{--
    Satu blok listing di home (dipakai terpisah untuk Akun dan Clan).
    Params: $type ('account'|'clan'), $available, $sold, $total, $townHalls (opsional, hanya akun)
--}}
@php
    $isClanBlock = $type === 'clan';
    $noun        = $isClanBlock ? 'clan' : 'akun';
    $catalogUrl  = $isClanBlock ? route('catalog', ['type' => 'clan']) : route('catalog', ['type' => 'account']);
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

    {{-- Terjual: dipisah dari yang tersedia, hanya jenis ini --}}
    @if($sold->count())
        <div class="mt-12 sm:mt-14">
            <div class="mb-5">
                <h3 class="font-display text-lg sm:text-2xl font-bold text-white flex items-center gap-2">
                    <span class="w-1.5 h-5 sm:h-6 rounded-full bg-red-500"></span>
                    {{ $isClanBlock ? 'CLAN TERJUAL' : 'AKUN TERJUAL' }}
                </h3>
                <p class="text-sm text-slate-500 mt-1">{{ ucfirst($noun) }} yang baru laku dan masih dalam masa garansi pembeli.</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-5">
                @foreach($sold as $account)
                    @include('components.account-card', ['account' => $account])
                @endforeach
            </div>
        </div>
    @endif
</section>