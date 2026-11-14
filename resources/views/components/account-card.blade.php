@php
    $isSold = $account->sale_status === 'sold';
    $warrantySale = $isSold ? $account->latestCompletedSale : null;
    $warrantyLeft = $warrantySale?->warrantyRemainingText();
    $warrantyEnded = ! $warrantyLeft && $warrantySale?->warrantyHasEnded();
    $review = $isSold ? $warrantySale?->approvedReview : null;
    $isClan = $account->isClan();
    $showDiscount = ! $isSold && $account->hasDiscount();
    $showHot      = ! $isSold && $account->isHotDeal();
    $isBooked     = ! $isSold && $account->isBooked();
    $dealLeft     = ($showHot || $showDiscount) ? $account->dealRemainingText() : null;
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
    $imageSet = null;
    if ($account->primaryImage && !empty($account->primaryImage->image_path)) {
        // Thumbnail (bukan foto asli) supaya kartu katalog ringan; 800 px hanya diambil layar beresolusi tinggi.
        $imageUrl = thumb_url($account->primaryImage->image_path, 480);
        $imageSet = $imageUrl . ' 480w, ' . thumb_url($account->primaryImage->image_path, 800) . ' 800w';
    }
@endphp

<a href="{{ route('account.detail', $account->account_code) }}"
   class="group relative flex flex-col h-full rounded-3xl overflow-hidden p-2 bg-slate-900/60 border {{ $showHot ? 'border-orange-500/40' : 'border-white/[0.07]' }} hover:border-electric/50 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-[0_22px_48px_-18px_rgba(59,130,246,0.45)]">

    <!-- Gambar (bingkai membulat di dalam kartu) -->
    <div class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-midnight">
        @if($imageUrl)
            <img src="{{ $imageUrl }}"
                 srcset="{{ $imageSet }}"
                 sizes="(min-width: 1280px) 25vw, (min-width: 768px) 33vw, 50vw"
                 alt="{{ $account->name }}"
                 decoding="async"
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
                        <span>Garansi <span {!! $warrantySale->countdownAttrs('sisa ', 'habis') !!}>{{ $warrantyLeft }}</span></span>
                    </span>
                @elseif($warrantyEnded)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-500/20 border border-slate-400/40 text-slate-300 text-[10px] sm:text-xs font-semibold backdrop-blur-sm">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        <span>Garansi habis</span>
                    </span>
                @endif
            </div>
        @endif

        <!-- Badge Town Hall + label Hot Deal -->
        <div class="absolute top-2 left-2 sm:top-3 sm:left-3 z-10 flex flex-col items-start gap-1 sm:gap-1.5">
            <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 bg-electric text-white text-[10px] sm:text-[11px] font-extrabold rounded-full shadow-lg uppercase tracking-wide">
                {{ $account->badgeLabel() }}
            </span>
            @if($showHot)
                <x-hot-deal-badge />
            @endif
            @if($isBooked)
                @php $bookedPaid = $account->isBookedPaid(); @endphp
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full {{ $bookedPaid ? 'bg-emerald-500 text-white' : 'bg-amber-400 text-slate-900' }} text-[10px] sm:text-[11px] font-extrabold shadow-lg">
                    @if($bookedPaid)
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        Dibooking &middot; Sudah TF
                    @else
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Dibooking &middot; Belum TF
                    @endif
                </span>
            @endif
        </div>

        <!-- Persen diskon -->
        @if($showDiscount)
            <span class="absolute bottom-2 left-2 sm:bottom-3 sm:left-3 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full bg-red-600 text-white text-[10px] sm:text-xs font-extrabold shadow-lg z-10">
                -{{ $account->discountPercent() }}%
            </span>
        @endif

        <!-- Tombol bandingkan (hanya untuk yang masih tersedia) -->
        @unless($isSold)
            <button type="button" data-compare-btn
                    data-code="{{ $account->account_code }}"
                    data-name="{{ $account->name }}"
                    data-type="{{ $account->listing_type }}"
                    data-img="{{ $imageUrl }}"
                    data-price="{{ (int) $account->price }}"
                    aria-pressed="false"
                    aria-label="Bandingkan {{ $account->name }}"
                    class="absolute bottom-2 right-2 sm:bottom-3 sm:right-3 z-10 inline-flex items-center gap-1 px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full bg-black/60 backdrop-blur border border-white/20 text-white text-[10px] sm:text-[11px] font-semibold hover:bg-electric/80 aria-pressed:bg-electric aria-pressed:border-electric transition">
                <svg data-cmp-off class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14m7-7H5"/></svg>
                <svg data-cmp-on class="hidden w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span data-compare-label data-off="Bandingkan" data-on="Dipilih" class="hidden sm:inline">Bandingkan</span>
            </button>
        @endunless

        <!-- Badge Gmail Bisa Ganti -->
        @if(!$isClan && $account->email_change_status === 'Changeable')
            @php $emailDays = $account->emailChangeDaysLeft(); @endphp
            <span class="hidden sm:block absolute top-3 right-3 px-2.5 py-1 {{ $emailDays ? 'bg-amber-500/90' : 'bg-emerald-500/90' }} text-white text-[10px] font-bold rounded-full z-10">
                {{ $emailDays ? "Gmail bisa ganti {$emailDays} hari lagi" : 'Gmail Bisa Ganti' }}
            </span>
        @endif
    </div>

    <!-- Konten -->
    <div class="px-2.5 pt-3 pb-2 sm:px-3.5 sm:pt-4 sm:pb-3 flex flex-col flex-grow">
        <h3 class="font-display font-extrabold text-[15px] sm:text-lg leading-tight text-white line-clamp-1 group-hover:text-electric transition-colors">
            {{ $account->name }}
        </h3>

        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
            <p class="text-[10px] sm:text-xs font-semibold tracking-wide text-slate-500 truncate">
                {{ $account->account_code }}
            </p>
            @if(! $isSold && $account->isVerifiedConsignment())
                <x-consignment-verified-badge />
            @endif
        </div>

        <!-- Level Hero -->
        @if(count($heroes))
            <div class="hidden sm:flex flex-wrap gap-1.5 mt-3">
                @foreach($heroes as $abbr => $lvl)
                    <span class="px-2.5 py-0.5 rounded-full bg-white/5 border border-white/5 text-[11px] font-semibold text-slate-300">
                        <span class="text-slate-500">{{ $abbr }}</span> {{ $lvl }}
                    </span>
                @endforeach
            </div>
        @endif

        <!-- Harga dan Detail -->
        <div class="mt-auto pt-3 sm:pt-4 flex items-end justify-between">
            <div>
                <p class="text-[10px] text-slate-500 font-semibold tracking-wide">
                    Harga
                </p>

                @if($showDiscount)
                    <p class="text-[10px] sm:text-xs text-slate-500 line-through leading-none mb-0.5">
                        Rp {{ number_format($account->original_price, 0, ',', '.') }}
                    </p>
                @endif

                <p class="text-base sm:text-2xl leading-none font-display font-extrabold {{ $isSold ? 'text-slate-500 line-through' : ($showDiscount ? 'text-orange-400' : 'text-white') }}">
                    <span class="text-[11px] sm:text-sm font-bold text-slate-400 mr-0.5">Rp</span>{{ number_format($account->price, 0, ',', '.') }}
                </p>

                @if($dealLeft)
                    <p class="mt-1 inline-flex items-center gap-1 text-[10px] sm:text-[11px] font-semibold text-orange-300">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span {!! $account->dealCountdownAttrs() !!}>{{ $dealLeft }}</span>
                    </p>
                @endif
            </div>

            <span class="hidden sm:inline-flex items-center gap-1 text-xs font-bold text-electric group-hover:text-white group-hover:bg-electric border border-electric/40 px-4 py-2 rounded-full transition">
                Detail
                <svg class="w-3 h-3 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
            </span>
        </div>
    </div>

    <!-- Ulasan pembeli (hanya akun terjual yang ulasannya sudah disetujui) -->
    @if($review)
        <div class="mx-2 sm:mx-2.5 mb-2 sm:mb-2.5 rounded-2xl bg-white/[0.04] border border-white/5 p-2.5 sm:p-3">
            <x-stars :rating="$review->rating" size="w-3 h-3 sm:w-3.5 sm:h-3.5" />
            <p class="text-[11px] sm:text-xs text-slate-300 mt-1.5 line-clamp-3">&ldquo;{{ $review->comment }}&rdquo;</p>
            <p class="text-[10px] sm:text-[11px] text-slate-500 mt-1.5">&mdash; {{ $review->reviewer_name }}, pembeli</p>
        </div>
    @endif

</a>