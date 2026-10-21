@php
    $isSold = $account->sale_status === 'sold';
    $warrantySale = $isSold ? $account->latestCompletedSale : null;
    $warrantyLeft = $warrantySale?->warrantyRemainingText();
    $review = $isSold ? $warrantySale?->approvedReview : null;
    $isClan = $account->isClan();
    $heroes = $isClan ? array_filter([
        'Lv'     => $account->clan_level,
        'CH'     => $account->capital_hall_level,
        'Member' => $account->clan_members,
        'Liga'   => $account->clan_league && $account->clan_league !== 'Unranked' ? $account->clan_league : null,
    ]) : array_filter([
        'BK' => $account->barbarian_king_level,
        'AQ' => $account->archer_queen_level,
        'MP' => $account->minion_prince_level,
        'GW' => $account->grand_warden_level,
        'RC' => $account->royal_champion_level,
        'DD' => $account->dragon_duke_level,
    ]);
    
    // Pastikan path gambar aman
    $imageUrl = null;
    if ($account->primaryImage && !empty($account->primaryImage->image_path)) {
        // Menggunakan asset() lebih stabil daripada Storage::url() di beberapa konfigurasi server
        $imageUrl = upload_url($account->primaryImage->image_path);
    }
@endphp

<a href="{{ route('account.detail', $account->account_code) }}"
   class="group relative flex flex-col h-full rounded-2xl overflow-hidden bg-slate-900/60 border border-white/[0.07] hover:border-electric/50 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_-15px_rgba(59,130,246,0.35)]">

    <!-- Gambar -->
    <div class="relative aspect-[4/3] overflow-hidden bg-midnight">
        @if($imageUrl)
            <img src="{{ $imageUrl }}"
                 alt="{{ $account->name }}"
                 loading="lazy"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">

            <!-- Fallback jika gambar gagal load (hidden by default) -->
            <div class="hidden w-full h-full absolute inset-0 items-center justify-center text-slate-700 bg-slate-900/50">
                <svg class="w-12 h-12 opacity-50"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="1.5"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @else
            <div class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-900/50">
                <svg class="w-12 h-12 opacity-50"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="1.5"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent pointer-events-none"></div>

        @if($isSold)
            <div class="absolute inset-0 z-[5] bg-slate-950/60 flex flex-col items-center justify-center gap-3 pointer-events-none">
                <span class="px-4 py-1.5 rounded-lg bg-red-600/90 text-white text-xs sm:text-sm font-bold tracking-widest uppercase -rotate-6 shadow-lg">Terjual</span>
                @if($warrantyLeft)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 text-[10px] sm:text-xs font-semibold backdrop-blur-sm">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Garansi <span {!! $warrantySale->countdownAttrs('sisa ', 'berakhir') !!}>{{ $warrantyLeft }}</span></span>
                    </span>
                @endif
            </div>
        @endif

        <!-- Badge Town Hall -->
        <span class="absolute top-2 left-2 sm:top-3 sm:left-3 px-2 py-0.5 sm:px-2.5 sm:py-1 bg-electric text-white text-[10px] sm:text-[11px] font-bold rounded-md shadow-lg uppercase tracking-wide z-10">
            {{ $account->badgeLabel() }}
        </span>

        <!-- Badge Email Bisa Ganti -->
        @if(!$isClan && $account->email_change_status === 'Changeable')
            @php $emailDays = $account->emailChangeDaysLeft(); @endphp
            <span class="hidden sm:block absolute top-3 right-3 px-2 py-1 {{ $emailDays ? 'bg-amber-500/90' : 'bg-emerald-500/90' }} text-white text-[10px] font-semibold rounded-md z-10">
                {{ $emailDays ? "Email bisa ganti {$emailDays} hari lagi" : 'Email Bisa Ganti' }}
            </span>
        @endif
    </div>

    <!-- Konten -->
    <div class="p-3 sm:p-4 flex flex-col flex-grow">
        <h3 class="font-display font-bold text-sm sm:text-base text-white line-clamp-1 group-hover:text-electric transition-colors">
            {{ $account->name }}
        </h3>

        <p class="text-[10px] sm:text-[11px] font-mono text-slate-500 mt-0.5 sm:mt-1 truncate">
            {{ $account->account_code }}
        </p>

        <!-- Level Hero -->
        @if(count($heroes))
            <div class="hidden sm:flex flex-wrap gap-1.5 mt-3">
                @foreach($heroes as $abbr => $lvl)
                    <span class="px-2 py-0.5 rounded bg-white/5 border border-white/5 text-[11px] text-slate-300">
                        <span class="text-slate-500">{{ $abbr }}</span> {{ $lvl }}
                    </span>
                @endforeach
            </div>
        @endif

        <!-- Harga dan Detail -->
        <div class="mt-auto pt-3 sm:pt-4 flex items-end justify-between">
            <div>
                <p class="text-[10px] text-slate-500 uppercase tracking-wider">
                    Harga
                </p>

                <p class="text-sm sm:text-lg font-display font-bold {{ $isSold ? 'text-slate-500 line-through' : 'text-white' }}">
                    Rp {{ number_format($account->price, 0, ',', '.') }}
                </p>
            </div>

            <span class="hidden sm:inline text-xs font-semibold text-electric group-hover:text-white group-hover:bg-electric border border-electric/40 px-3 py-1.5 rounded-lg transition">
                Detail
            </span>
        </div>
    </div>

    <!-- Ulasan pembeli (hanya akun terjual yang ulasannya sudah disetujui) -->
    @if($review)
        <div class="mx-3 sm:mx-4 mb-3 sm:mb-4 rounded-xl bg-white/[0.04] border border-white/5 p-2.5 sm:p-3">
            <x-stars :rating="$review->rating" size="w-3 h-3 sm:w-3.5 sm:h-3.5" />
            <p class="text-[11px] sm:text-xs text-slate-300 mt-1.5 line-clamp-3">&ldquo;{{ $review->comment }}&rdquo;</p>
            <p class="text-[10px] sm:text-[11px] text-slate-500 mt-1.5">&mdash; {{ $review->reviewer_name }}, pembeli</p>
        </div>
    @endif

    <!-- Garis Aksen -->
    <div class="h-1 bg-electric/70 group-hover:bg-electric transition"></div>
</a>