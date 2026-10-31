{{-- Menu bawah untuk pembeli di HP (gaya sama dengan bottom bar admin). Desktop memakai navbar atas. --}}
@php
    $bottomItems = [
        ['label' => 'Beranda', 'href' => route('home'),              'active' => request()->routeIs('home'),
         'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['label' => 'Katalog', 'href' => route('catalog'),           'active' => request()->routeIs('catalog'),
         'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
        ['label' => 'Titip Jual', 'href' => route('consignment.terms'), 'active' => request()->routeIs('consignment.terms'),
         'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Garansi', 'href' => route('warranty'),          'active' => request()->routeIs('warranty', 'warranty.check'),
         'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
    ];

    $drawerLinks = [
        ['label' => 'Cara Beli',            'href' => route('how.to.buy'),      'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Cek Garansi',          'href' => route('warranty.check'),  'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
        ['label' => 'Syarat Booking DP',    'href' => route('booking.terms'),   'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['label' => 'Syarat Titip Akun',    'href' => route('consignment.terms'), 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
    ];
@endphp

<div x-data="{ menu: false }" @keydown.escape.window="menu = false" class="md:hidden">

    <!-- Bottom bar (HP) -->
    <nav id="buyerBottomNav" class="fixed inset-x-0 bottom-0 z-40 bg-midnight/90 backdrop-blur-xl border-t border-white/10"
         style="padding-bottom: env(safe-area-inset-bottom, 0px)" aria-label="Navigasi utama">
        <div class="grid grid-cols-5">
            @foreach($bottomItems as $item)
                <a href="{{ $item['href'] }}"
                   class="relative flex flex-col items-center justify-center gap-1 py-2.5 text-[10.5px] font-medium transition-colors {{ $item['active'] ? 'text-electric' : 'text-slate-400 active:text-white' }}"
                   @if($item['active']) aria-current="page" @endif>
                    @if($item['active'])<span class="absolute top-0 h-0.5 w-8 rounded-full bg-electric"></span>@endif
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $item['active'] ? 2.2 : 1.8 }}" d="{{ $item['icon'] }}"/></svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
            <button type="button" @click="menu = true"
                    class="relative flex flex-col items-center justify-center gap-1 py-2.5 text-[10.5px] font-medium text-slate-400 active:text-white"
                    aria-haspopup="dialog" aria-controls="buyerDrawer">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16"/></svg>
                Menu
            </button>
        </div>
    </nav>

    <!-- Menu lengkap: panel geser dari bawah -->
    <div id="buyerDrawer" x-show="menu" x-cloak class="fixed inset-0 z-[60]" role="dialog" aria-modal="true" aria-label="Menu lengkap">
        <div x-show="menu" x-transition.opacity @click="menu = false" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

        <div x-show="menu"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="absolute inset-x-0 bottom-0 max-h-[88vh] flex flex-col glass-strong rounded-t-3xl border-t border-white/10 shadow-2xl">

            <div class="flex items-center justify-between px-5 pt-4 pb-2 shrink-0">
                <div class="mx-auto absolute left-1/2 -translate-x-1/2 top-2 w-10 h-1 rounded-full bg-white/20"></div>
                <p class="font-display font-bold text-white text-lg">Menu</p>
                <button type="button" @click="menu = false" aria-label="Tutup"
                        class="w-9 h-9 rounded-xl border border-white/10 text-slate-300 hover:text-white hover:bg-white/5 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="flex-1 min-h-0 overflow-y-auto overscroll-contain px-3 py-2 custom-scrollbar">
                @foreach($drawerLinks as $l)
                    <a href="{{ $l['href'] }}" class="flex items-center gap-3 px-3 py-3.5 rounded-xl text-sm font-medium text-slate-300 hover:text-white hover:bg-white/5 active:bg-white/10 transition">
                        <span class="w-9 h-9 shrink-0 rounded-lg bg-white/5 text-electric flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $l['icon'] }}"/></svg>
                        </span>
                        {{ $l['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="px-4 pt-3 pb-3 shrink-0 border-t border-white/5 grid grid-cols-2 gap-2"
                 style="padding-bottom: calc(env(safe-area-inset-bottom, 0px) + .75rem)">
                <a href="{{ $storeWaUrl }}" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center bg-electric hover:bg-blue-500 text-white font-bold py-3 rounded-xl text-sm transition">Chat Admin</a>
                <a href="{{ $storeIgUrl }}" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center bg-white/5 hover:bg-white/10 border border-white/10 text-white font-semibold py-3 rounded-xl text-sm transition">Instagram</a>
            </div>
        </div>
    </div>
</div>