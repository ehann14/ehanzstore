@php
    $rupiah  = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $delta   = $yesterdayVisitors > 0 ? round((($todayVisitors - $yesterdayVisitors) / $yesterdayVisitors) * 100) : null;

    $cards = [
        [
            'label' => 'Pengunjung Hari Ini',
            'value' => number_format($todayVisitors),
            'sub'   => number_format($totalVisitors) . ' total pengunjung unik',
            'icon'  => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
            'delta' => $delta,
        ],
        [
            'label' => 'Akun Tersedia',
            'value' => number_format($availableAccounts),
            'sub'   => 'dari ' . number_format($totalAccounts) . ' akun terdaftar',
            'icon'  => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        ],
        [
            'label' => 'Akun Terjual',
            'value' => number_format($soldAccounts),
            'sub'   => 'stok keluar sepanjang waktu',
            'icon'  => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        [
            'label' => 'Pendapatan Bulan Ini',
            'value' => $rupiah($monthRevenue),
            'sub'   => $rupiah($totalRevenue) . ' total',
            'icon'  => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        [
            'label' => 'Laba Bulan Ini',
            'value' => ($monthProfit < 0 ? '-' : '') . $rupiah(abs($monthProfit)),
            'sub'   => ($totalProfit < 0 ? '-' : '') . $rupiah(abs($totalProfit)) . ' total (harga jual - modal)',
            'icon'  => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
        ],
    ];
@endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
        @foreach($cards as $card)
            <div class="glass-panel rounded-2xl p-5 hover:bg-white/5 transition group">
                <div class="flex items-start justify-between mb-4">
                    <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">{{ $card['label'] }}</p>
                    <div class="p-2 rounded-xl bg-electric/10 text-electric group-hover:bg-electric group-hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2 flex-wrap">
                    <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ $card['value'] }}</p>
                    @if(isset($card['delta']) && $card['delta'] !== null)
                        <span class="text-xs font-semibold px-1.5 py-0.5 rounded-md {{ $card['delta'] >= 0 ? 'bg-emerald-500/15 text-emerald-300' : 'bg-red-500/15 text-red-300' }}">
                            {{ $card['delta'] >= 0 ? '▲' : '▼' }} {{ abs($card['delta']) }}%
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1.5">{{ $card['sub'] }}</p>
            </div>
        @endforeach
    </div>