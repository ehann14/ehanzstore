@extends('layouts.app')

@section('title', ($type ?? null) === 'clan' ? 'Jual Beli Clan COC - EHANZSTORE.ID' : 'Jual Beli Akun COC TH Max - EHANZSTORE.ID')

@section('content')
    <!-- Hero Catalog -->
    <section class="relative pt-28 sm:pt-40 pb-8 sm:pb-16 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-royal/10 to-transparent pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
            <h1 class="font-display text-3xl sm:text-4xl md:text-6xl font-bold text-white mb-6 tracking-tight">
                {{ $type === 'clan' ? 'Gudang Clan' : 'Gudang Akun' }}
            </h1>

            <!-- Tab jenis: Semua / Akun / Clan -->
            <div class="inline-flex p-1 rounded-2xl glass-strong border border-white/10">
                @foreach(['' => 'Semua', 'account' => 'Akun COC', 'clan' => 'Clan COC'] as $key => $label)
                    <a href="{{ route('catalog', array_filter(['type' => $key, 'search' => request('search'), 'sort' => request('sort')])) }}"
                       class="px-4 sm:px-6 py-2 rounded-xl text-xs sm:text-sm font-semibold transition {{ ($type ?? '') === (string) $key ? 'bg-electric text-white shadow-lg shadow-electric/25' : 'text-slate-400 hover:text-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Floating Filter Bar -->
    <div class="relative md:sticky md:top-24 z-30 px-4 mb-8 md:mb-12">
        <div class="max-w-5xl mx-auto glass-strong rounded-2xl p-2 flex flex-col md:flex-row items-center gap-2 shadow-2xl shadow-black/50 border border-white/10">
            
            <!-- Search Input -->
            <div class="relative w-full md:w-1/3 group">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-500 group-focus-within:text-electric transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <form action="{{ route('catalog') }}" method="GET" class="w-full">
                    @foreach(request()->except(['search', 'page']) as $k => $v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ $type === 'clan' ? 'Cari nama clan...' : 'Cari TH16, Max Hero...' }}" 
                           class="w-full bg-midnight/50 border border-white/5 rounded-xl pl-12 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:border-electric focus:ring-1 focus:ring-electric outline-none transition-all">
                </form>
            </div>

            @if($type !== 'clan')
            <div class="hidden md:block w-px h-8 bg-white/10"></div>

            <!-- TH Filter Pills -->
            <div id="thScroller"
                 class="md:flex-1 w-full min-w-0 overflow-x-auto overflow-y-hidden no-scrollbar px-2 py-1 cursor-grab select-none"
                 style="-webkit-overflow-scrolling: touch; overscroll-behavior-x: contain; touch-action: pan-x;">
                <form action="{{ route('catalog') }}" method="GET" class="flex flex-nowrap gap-2 w-max min-w-full pr-2">
                    @foreach(request()->except(['th', 'page']) as $k => $v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
                    
                    <button type="submit" name="th" value="" 
                            class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all border {{ !request('th') ? 'bg-electric text-white border-electric shadow-[0_0_15px_rgba(59,130,246,0.4)]' : 'bg-white/5 text-slate-400 border-white/5 hover:bg-white/10 hover:text-white' }}">
                        Semua TH
                    </button>
                    
                    @for($i = 9; $i <= 18; $i++)
                        <button type="submit" name="th" value="{{ $i }}" 
                                class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition-all border {{ request('th') == $i ? 'bg-electric text-white border-electric shadow-[0_0_15px_rgba(59,130,246,0.4)]' : 'bg-white/5 text-slate-400 border-white/5 hover:bg-white/10 hover:text-white' }}">
                            TH {{ $i }}
                        </button>
                    @endfor
                </form>
            </div>
            @endif

            <!-- Sort Dropdown (custom) -->
            @php
                $sortOptions = [
                    ''           => ['Terbaru Masuk',   'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'price_asc'  => ['Harga Termurah',  'M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12'],
                    'price_desc' => ['Harga Tertinggi', 'M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4'],
                    'discount_desc' => ['Diskon Terbesar', 'M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 8V5a2 2 0 012-2z'],
                ];
                $currentSort = array_key_exists((string) request('sort'), $sortOptions) ? (string) request('sort') : '';
            @endphp
            <div class="w-full md:w-56 shrink-0 relative" id="sortDropdown">
                <form action="{{ route('catalog') }}" method="GET" class="w-full" id="sortForm">
                    @foreach(request()->except(['sort', 'page']) as $k => $v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
                    <input type="hidden" name="sort" id="sortInput" value="{{ $currentSort }}">

                    <button type="button" id="sortToggle" aria-haspopup="listbox" aria-expanded="false"
                            class="w-full flex items-center justify-between gap-3 bg-midnight/50 border border-white/5 hover:border-white/20 rounded-xl px-4 py-3 text-sm text-white outline-none transition-all">
                        <span class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 text-electric shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortOptions[$currentSort][1] }}"/></svg>
                            <span class="truncate font-medium">{{ $sortOptions[$currentSort][0] }}</span>
                        </span>
                        <svg id="sortChevron" class="w-4 h-4 text-slate-400 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div id="sortMenu" role="listbox"
                         class="hidden absolute left-0 right-0 top-full mt-2 z-50 p-1.5 rounded-xl border border-white/10 bg-slate-900/95 backdrop-blur-xl shadow-2xl shadow-black/60 origin-top">
                        @foreach($sortOptions as $val => [$label, $icon])
                            @php $isActive = $currentSort === (string) $val; @endphp
                            <button type="button" role="option" aria-selected="{{ $isActive ? 'true' : 'false' }}" data-value="{{ $val }}"
                                    class="sort-opt w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm text-left transition-colors {{ $isActive ? 'bg-electric/15 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <svg class="w-4 h-4 shrink-0 {{ $isActive ? 'text-electric' : 'text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
                                <span class="flex-1">{{ $label }}</span>
                                @if($isActive)
                                    <svg class="w-4 h-4 text-electric shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Filter Lanjutan -->
    @php
        $isClanTab = $type === 'clan';
        $advKeys   = ['price_min', 'price_max', 'hero', 'hero_min', 'email', 'protected', 'hot', 'clan_level_min', 'capital_min', 'members_min', 'league_min'];
        $advCount  = collect([
            request()->filled('price_min') || request()->filled('price_max'),
            request()->boolean('hot'),
            !$isClanTab && request()->filled('hero') && request()->filled('hero_min'),
            !$isClanTab && request()->boolean('email'),
            !$isClanTab && request()->boolean('protected'),
            $isClanTab && request()->filled('clan_level_min'),
            $isClanTab && request()->filled('capital_min'),
            $isClanTab && request()->filled('members_min'),
            $isClanTab && request()->filled('league_min'),
        ])->filter()->count();
        $heroOpts  = [
            'bk' => 'Barbarian King (Raja Barbar)',
            'aq' => 'Archer Queen (Ratu Pemanah)',
            'mp' => 'Minion Prince',
            'gw' => 'Grand Warden',
            'rc' => 'Royal Champion (Jawara Royal)',
            'dd' => 'Dragon Duke (Adipati Naga)',
        ];
        $fieldCls = 'w-full bg-midnight/60 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-electric focus:ring-1 focus:ring-electric outline-none transition';
        $lblCls   = 'block text-[10px] font-semibold text-slate-500 uppercase tracking-wider mb-1.5';
    @endphp
    <div class="max-w-5xl mx-auto px-4 -mt-4 md:-mt-6 mb-8 md:mb-10">
        <button type="button" id="advToggle" aria-expanded="{{ $advCount ? 'true' : 'false' }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border text-xs font-semibold transition {{ $advCount ? 'border-electric/50 bg-electric/10 text-white' : 'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 12h12m-8 8h4"/></svg>
            Filter Lanjutan
            @if($advCount)
                <span class="min-w-[18px] h-[18px] px-1 rounded-full bg-electric text-white text-[10px] font-bold flex items-center justify-center">{{ $advCount }}</span>
            @endif
            <svg id="advChevron" class="w-3.5 h-3.5 transition-transform {{ $advCount ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <form action="{{ route('catalog') }}" method="GET" id="advPanel"
              class="{{ $advCount ? '' : 'hidden' }} mt-3 glass-strong rounded-2xl p-4 sm:p-5 border border-white/10 shadow-xl shadow-black/30">
            @foreach(request()->except(array_merge($advKeys, ['page'])) as $k => $v)
                @if(!is_array($v)) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
            @endforeach

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Rentang harga -->
                <div>
                    <label class="{{ $lblCls }}">Rentang Harga (Rp)</label>
                    <div class="flex items-center gap-2">
                        <input type="text" inputmode="numeric" name="price_min" value="{{ request('price_min') }}" placeholder="Minimal" class="{{ $fieldCls }} money">
                        <span class="text-slate-600">&ndash;</span>
                        <input type="text" inputmode="numeric" name="price_max" value="{{ request('price_max') }}" placeholder="Maksimal" class="{{ $fieldCls }} money">
                    </div>
                </div>

                @if($isClanTab)
                <!-- Level clan & capital hall -->
                <div>
                    <label class="{{ $lblCls }}">Level Clan Minimum</label>
                    <input type="number" name="clan_level_min" min="1" max="100" value="{{ request('clan_level_min') }}" placeholder="Contoh: 15" class="{{ $fieldCls }}">
                </div>
                <div>
                    <label class="{{ $lblCls }}">Capital Hall Minimum</label>
                    <input type="number" name="capital_min" min="1" max="15" value="{{ request('capital_min') }}" placeholder="Contoh: 8" class="{{ $fieldCls }}">
                </div>
                <div>
                    <label class="{{ $lblCls }}">Member Minimum</label>
                    <input type="number" name="members_min" min="1" max="{{ \App\Models\CocAccount::CLAN_MAX_MEMBERS }}" value="{{ request('members_min') }}" placeholder="Contoh: 40" class="{{ $fieldCls }}">
                </div>
                <div>
                    <label class="{{ $lblCls }}">Liga Clan Minimum</label>
                    <select name="league_min" class="{{ $fieldCls }}">
                        <option value="" class="bg-slate-900">Semua liga</option>
                        @foreach(\App\Models\CocAccount::CLAN_LEAGUES as $league)
                            @if($league !== 'Unranked')
                                <option value="{{ $league }}" class="bg-slate-900" @selected(request('league_min') === $league)>{{ $league }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                @else
                <!-- Hero minimum -->
                <div>
                    <label class="{{ $lblCls }}">Level Hero Minimum</label>
                    <div class="flex items-center gap-2">
                        <select name="hero" class="{{ $fieldCls }}">
                            <option value="" class="bg-slate-900">Pilih hero</option>
                            @foreach($heroOpts as $key => $label)
                                <option value="{{ $key }}" class="bg-slate-900" @selected(request('hero') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="number" name="hero_min" min="1" max="100" value="{{ request('hero_min') }}" placeholder="Lv" class="{{ $fieldCls }} !w-24 shrink-0">
                    </div>
                </div>
                @endif
            </div>

            <!-- Toggle Hot Deal, email & proteksi -->
            <div class="flex flex-wrap gap-3 mt-4">
                <label class="cursor-pointer select-none">
                    <input type="checkbox" name="hot" value="1" class="peer sr-only" @checked(request()->boolean('hot'))>
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-white/10 bg-white/5 text-xs font-semibold text-slate-300 transition peer-checked:border-orange-400/50 peer-checked:bg-orange-500/15 peer-checked:text-orange-300 peer-focus-visible:ring-2 peer-focus-visible:ring-electric">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177A7.547 7.547 0 016.648 6.61a.75.75 0 00-1.152-.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 011.925-3.545 3.75 3.75 0 013.255 3.717z"/></svg>
                        Hot Deal saja
                    </span>
                </label>
                @unless($isClanTab)
                <label class="cursor-pointer select-none">
                    <input type="checkbox" name="email" value="1" class="peer sr-only" @checked(request()->boolean('email'))>
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-white/10 bg-white/5 text-xs font-semibold text-slate-300 transition peer-checked:border-emerald-400/50 peer-checked:bg-emerald-500/15 peer-checked:text-emerald-300 peer-focus-visible:ring-2 peer-focus-visible:ring-electric">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Email Bisa Ganti
                    </span>
                </label>
                <label class="cursor-pointer select-none">
                    <input type="checkbox" name="protected" value="1" class="peer sr-only" @checked(request()->boolean('protected'))>
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-white/10 bg-white/5 text-xs font-semibold text-slate-300 transition peer-checked:border-emerald-400/50 peer-checked:bg-emerald-500/15 peer-checked:text-emerald-300 peer-focus-visible:ring-2 peer-focus-visible:ring-electric">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Proteksi Aktif
                    </span>
                </label>
                @endunless
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 mt-5 pt-4 border-t border-white/5">
                @if($advCount)
                    <a href="{{ route('catalog', request()->except(array_merge($advKeys, ['page']))) }}"
                       class="text-center px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 transition">Reset Filter Lanjutan</a>
                @endif
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-electric hover:bg-blue-500 text-white text-sm font-bold transition">Terapkan Filter</button>
            </div>
        </form>
    </div>

    <!-- Product Grid -->
    <div class="max-w-7xl mx-auto px-4 pb-20">
        <div class="flex justify-between items-center mb-8 px-2">
            <p class="text-sm text-slate-500">Ditemukan <span class="text-electric font-bold">{{ $accounts->total() }}</span> {{ $type === 'clan' ? 'clan' : ($type === 'account' ? 'akun' : 'akun & clan') }} siap kirim</p>
            @if(request()->anyFilled(['search','th','sort','price_min','price_max','hero_min','clan_level_min','capital_min','members_min','league_min']) || request()->boolean('email') || request()->boolean('protected') || request()->boolean('hot'))
                <a href="{{ route('catalog') }}" class="text-xs text-red-400 hover:text-red-300 font-medium flex items-center gap-1 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Reset Filter
                </a>
            @endif
        </div>

        @if($accounts->count() > 0)
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6">
                @foreach($accounts as $account)
                    @include('components.account-card', ['account' => $account])
                @endforeach
            </div>

            <div class="mt-16 flex justify-center">
                {{ $accounts->onEachSide(1)->links('vendor.pagination.tailwind') }}
            </div>
        @else
            <div class="glass rounded-3xl p-8 sm:p-20 text-center border border-white/5">
                <div class="w-20 h-20 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-6 animate-pulse">
                    <svg class="w-10 h-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-xl font-display font-bold text-white mb-2">Stok Kosong Sementara</h3>
                <p class="text-slate-500 text-sm mb-8">Akun bagus cepat laku. Chat admin untuk request spesifikasi tertentu.</p>
                <a href="https://wa.me/6282130559126" class="inline-flex items-center gap-2 bg-electric hover:bg-blue-500 text-white px-6 py-3 rounded-full text-sm font-bold transition shadow-lg shadow-electric/20">
                    Request ke Admin
                </a>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const el = document.getElementById('thScroller');
        if (!el) return;

        // Geser otomatis ke TH yang sedang aktif
        const active = el.querySelector('button.bg-electric');
        if (active && active.getAttribute('value')) {
            el.scrollLeft = active.offsetLeft - (el.clientWidth - active.offsetWidth) / 2;
        }

        // Scroll mouse (wheel) -> geser horizontal
        el.addEventListener('wheel', function (e) {
            if (el.scrollWidth <= el.clientWidth) return;
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                el.scrollLeft += e.deltaY;
                e.preventDefault();
            }
        }, { passive: false });

        // Drag dengan mouse (di HP sudah otomatis lewat sentuhan)
        let down = false, moved = false, startX = 0, startLeft = 0;
        el.addEventListener('mousedown', function (e) {
            down = true; moved = false;
            startX = e.pageX; startLeft = el.scrollLeft;
            el.classList.add('cursor-grabbing');
        });
        window.addEventListener('mouseup', function () {
            down = false;
            el.classList.remove('cursor-grabbing');
        });
        window.addEventListener('mousemove', function (e) {
            if (!down) return;
            const dx = e.pageX - startX;
            if (Math.abs(dx) > 5) moved = true;
            el.scrollLeft = startLeft - dx;
        });
        // Cegah klik tombol TH kalau user sebenarnya sedang menggeser
        el.addEventListener('click', function (e) {
            if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
        }, true);
    })();
    // Dropdown urutan (custom)
    (function () {
        const wrap = document.getElementById('sortDropdown');
        if (!wrap) return;
        const toggle = document.getElementById('sortToggle');
        const menu = document.getElementById('sortMenu');
        const chev = document.getElementById('sortChevron');
        const input = document.getElementById('sortInput');
        const form = document.getElementById('sortForm');

        const setOpen = (open) => {
            menu.classList.toggle('hidden', !open);
            chev.classList.toggle('rotate-180', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.classList.toggle('border-electric', open);
        };

        toggle.addEventListener('click', (e) => { e.stopPropagation(); setOpen(menu.classList.contains('hidden')); });
        menu.querySelectorAll('.sort-opt').forEach((btn) => {
            btn.addEventListener('click', () => { input.value = btn.dataset.value; form.submit(); });
        });
        document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) setOpen(false); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
    })();
    // Filter lanjutan: buka/tutup panel + format ribuan di kolom harga
    (function () {
        const btn = document.getElementById('advToggle');
        const panel = document.getElementById('advPanel');
        const chev = document.getElementById('advChevron');
        if (btn && panel) {
            btn.addEventListener('click', () => {
                const open = panel.classList.toggle('hidden') === false;
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                chev.classList.toggle('rotate-180', open);
            });
        }
        document.querySelectorAll('#advPanel .money').forEach((el) => {
            const fmt = () => {
                const d = el.value.replace(/\D+/g, '');
                el.value = d ? Number(d).toLocaleString('id-ID') : '';
            };
            fmt();
            el.addEventListener('input', fmt);
        });
    })();
</script>
@endpush