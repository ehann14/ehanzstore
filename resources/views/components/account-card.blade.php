@php
    $heroes = array_filter([
        'BK' => $account->barbarian_king_level,
        'AQ' => $account->archer_queen_level,
        'GW' => $account->grand_warden_level,
        'RC' => $account->royal_champion_level,
    ]);
@endphp

<a href="{{ route('account.detail', $account->account_code) }}"
   class="group relative flex flex-col h-full rounded-2xl overflow-hidden bg-slate-900/60 border border-white/[0.07] hover:border-electric/50 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_18px_40px_-15px_rgba(59,130,246,0.35)]">

    <!-- Gambar -->
    <div class="relative aspect-[4/3] overflow-hidden bg-midnight">
        @if($account->primaryImage)
            <img src="{{ Storage::url($account->primaryImage->image_path) }}"
                 alt="{{ $account->name }}"
                 loading="lazy"
                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">

            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent"></div>
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

        <!-- Badge Town Hall -->
        <span class="absolute top-2 left-2 sm:top-3 sm:left-3 px-2 py-0.5 sm:px-2.5 sm:py-1 bg-electric text-white text-[10px] sm:text-[11px] font-bold rounded-md shadow-lg uppercase tracking-wide">
            TH {{ $account->town_hall }}
        </span>

        <!-- Badge Email Bisa Ganti -->
        @if($account->email_change_status === 'Changeable')
            <span class="hidden sm:block absolute top-3 right-3 px-2 py-1 bg-emerald-500/90 text-white text-[10px] font-semibold rounded-md">
                Email Bisa Ganti
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

                <p class="text-sm sm:text-lg font-display font-bold text-white">
                    Rp {{ number_format($account->price, 0, ',', '.') }}
                </p>
            </div>

            <span class="hidden sm:inline text-xs font-semibold text-electric group-hover:text-white group-hover:bg-electric border border-electric/40 px-3 py-1.5 rounded-lg transition">
                Detail
            </span>
        </div>
    </div>

    <!-- Garis Aksen -->
    <div class="h-1 bg-electric/70 group-hover:bg-electric transition"></div>
</a>
