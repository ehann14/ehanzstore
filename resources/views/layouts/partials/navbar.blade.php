@php
    $links = [
        ['route' => 'home',       'label' => 'Beranda'],
        ['route' => 'catalog',    'label' => 'Katalog'],
        ['route' => 'how.to.buy', 'label' => 'Cara Beli'],
    ];
@endphp

<nav x-data="{ open: false, search: false }" class="fixed top-0 left-0 right-0 z-50 bg-midnight/90 backdrop-blur-md border-b border-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 sm:gap-4 h-16 sm:h-20">

            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 shrink-0">
                <div class="relative w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center bg-white rounded-xl overflow-hidden shadow-lg">
                    <img src="{{ asset('images/logo.png') }}" alt="EHANZSTORE" class="w-9 h-9 object-contain"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    <div class="hidden absolute inset-0 bg-electric text-white font-display font-bold text-xl items-center justify-center">E</div>
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-display font-bold text-base sm:text-lg text-white tracking-tight">EHANZSTORE</span>
                    <span class="hidden sm:block text-[10px] text-slate-400 font-medium tracking-widest uppercase mt-1">Marketplace COC</span>
                </div>
            </a>

            <!-- Search (desktop) -->
            <form action="{{ route('catalog') }}" method="GET" class="hidden lg:block flex-1 max-w-md mx-4">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari akun, contoh: TH16 atau kode EHZ-..."
                           class="w-full bg-white/5 border border-white/10 rounded-xl pl-11 pr-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-electric focus:bg-white/10 outline-none transition">
                </div>
            </form>

            <div class="flex-1 lg:hidden"></div>

            <!-- Menu desktop -->
            <div class="hidden md:flex items-center gap-1">
                @foreach($links as $link)
                    <a href="{{ route($link['route']) }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs($link['route']) ? 'text-white bg-white/10' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>

            <!-- CTA -->
            <a href="{{ $storeWaUrl }}" target="_blank" rel="noopener"
               class="hidden sm:inline-flex items-center gap-2 bg-electric hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition shadow-lg shadow-electric/20">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                Chat Admin
            </a>

            <!-- Ikon cari (mobile / tablet) -->
            <button @click="search = !search; open = false" class="lg:hidden w-10 h-10 rounded-xl border border-white/10 text-slate-300 hover:text-white hover:bg-white/5 flex items-center justify-center" aria-label="Cari">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>

            <!-- Tombol menu mobile -->
            <button @click="open = !open; search = false" class="md:hidden w-10 h-10 rounded-xl border border-white/10 text-slate-300 hover:text-white hover:bg-white/5 flex items-center justify-center" aria-label="Menu">
                <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="open" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <!-- Kolom cari (mobile / tablet) -->
    <div x-show="search" x-cloak x-transition class="lg:hidden bg-midnight border-t border-white/5 px-4 py-3">
        <form action="{{ route('catalog') }}" method="GET">
            <input type="text" name="search" placeholder="Cari akun, contoh: TH16 atau kode EHZ-..."
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-electric outline-none">
        </form>
    </div>

    <!-- Menu mobile -->
    <div x-show="open" x-cloak x-transition class="md:hidden bg-midnight border-t border-white/5 shadow-2xl">
        <div class="px-4 py-5 space-y-3">
            @foreach($links as $link)
                <a href="{{ route($link['route']) }}"
                   class="block px-2 py-2 text-base font-medium {{ request()->routeIs($link['route']) ? 'text-electric' : 'text-slate-300' }}">
                    {{ $link['label'] }}
                </a>
            @endforeach
            <a href="{{ $storeWaUrl }}" target="_blank" rel="noopener"
               class="block w-full text-center bg-electric text-white py-3 rounded-xl font-semibold">
                Hubungi Admin via WhatsApp
            </a>
        </div>
    </div>
</nav>