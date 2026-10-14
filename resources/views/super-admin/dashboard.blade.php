@extends('layouts.admin')

@section('title', 'Dashboard Super Admin')

@section('content')
@php
    $rupiah  = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $delta   = $yesterdayVisitors > 0 ? round((($todayVisitors - $yesterdayVisitors) / $yesterdayVisitors) * 100) : null;
    $hasData = collect($visitorStats)->sum('visitors') > 0;

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
    ];

    $statusBadge = [
        'completed' => ['Selesai', 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'],
        'pending'   => ['Menunggu', 'bg-amber-500/15 text-amber-300 border-amber-500/30'],
        'cancelled' => ['Batal', 'bg-red-500/15 text-red-300 border-red-500/30'],
    ];
@endphp

<div class="max-w-7xl mx-auto pb-16">

    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Dashboard Analitik</h1>
            <p class="text-sm text-slate-400 mt-1">Ringkasan performa toko dan aktivitas pengunjung terkini.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="glass-panel px-4 py-2 rounded-xl text-right">
                <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Diperbarui</p>
                <p class="text-sm text-brand-400 font-mono">{{ now()->format('d M Y, H:i') }}</p>
            </div>
            <a href="{{ route('super-admin.sales') }}"
               class="inline-flex items-center gap-2 bg-electric hover:bg-blue-500 text-white px-4 py-3 rounded-xl text-sm font-semibold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Konfirmasi Penjualan
            </a>
        </div>
    </div>

    <!-- Statistik -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
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

    <!-- Grafik & akun populer -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 glass-panel rounded-2xl p-5 sm:p-6 flex flex-col">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="font-bold text-white">Tren Pengunjung</h3>
                    <p class="text-xs text-slate-500">Pengunjung unik per hari, 7 hari terakhir</p>
                </div>
                <a href="{{ route('super-admin.analytics') }}" class="text-xs text-electric hover:text-blue-300 font-medium transition">Detail &rarr;</a>
            </div>
            <div class="relative flex-grow h-72">
                <canvas id="visitorChart"></canvas>
                @unless($hasData)
                    <div class="absolute inset-0 flex items-center justify-center text-slate-500 text-sm text-center bg-slate-900/60 rounded-xl">
                        Belum ada data kunjungan dalam 7 hari terakhir.
                    </div>
                @endunless
            </div>
        </div>

        <div class="glass-panel rounded-2xl p-5 sm:p-6">
            <h3 class="font-bold text-white mb-1">Akun Paling Diminati</h3>
            <p class="text-xs text-slate-500 mb-5">Berdasarkan jumlah kunjungan halaman detail</p>
            <div class="space-y-2.5 max-h-80 overflow-y-auto pr-1 custom-scrollbar">
                @forelse($topAccounts as $i => $item)
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/5 hover:border-electric/30 transition">
                        <span class="w-7 h-7 shrink-0 rounded-lg bg-electric/15 text-electric text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-white truncate">{{ $item->account?->name ?? 'Akun dihapus' }}</p>
                            <p class="text-[11px] text-slate-500 font-mono">
                                {{ $item->account?->account_code ?? '-' }}
                                @if($item->account) &middot; TH {{ $item->account->town_hall }} @endif
                            </p>
                        </div>
                        <span class="shrink-0 bg-electric/15 text-brand-300 text-xs font-bold px-2 py-1 rounded-lg">{{ $item->views }}x</span>
                    </div>
                @empty
                    <div class="text-center py-12 text-slate-500 text-sm">Belum ada akun yang dilihat pengunjung.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Transaksi terbaru -->
    <div class="glass-panel rounded-2xl p-5 sm:p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-bold text-white">Transaksi Terbaru</h3>
            <a href="{{ route('super-admin.sales') }}" class="text-xs text-electric hover:text-blue-300 font-medium transition">Lihat semua &rarr;</a>
        </div>

        @if($recentSales->isEmpty())
            <div class="text-center py-10 text-slate-500 text-sm">Belum ada transaksi tercatat.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[560px]">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500">
                            <th class="pb-3 font-semibold">Kode</th>
                            <th class="pb-3 font-semibold">Akun</th>
                            <th class="pb-3 font-semibold">Harga</th>
                            <th class="pb-3 font-semibold">Status</th>
                            <th class="pb-3 font-semibold text-right">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($recentSales as $sale)
                            @php $b = $statusBadge[$sale->transaction_status] ?? ['-', 'bg-slate-500/15 text-slate-300 border-slate-500/30']; @endphp
                            <tr class="hover:bg-white/[0.03] transition">
                                <td class="py-3 font-mono text-xs text-slate-400">{{ $sale->transaction_code }}</td>
                                <td class="py-3 text-white">{{ $sale->account?->name ?? $sale->account_code_snapshot }}</td>
                                <td class="py-3 font-semibold text-brand-400">{{ $rupiah($sale->sale_price) }}</td>
                                <td class="py-3"><span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $b[1] }}">{{ $b[0] }}</span></td>
                                <td class="py-3 text-right text-xs text-slate-500">{{ ($sale->sold_at ?? $sale->created_at)->format('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const stats = @json($visitorStats);
    const ctx = document.getElementById('visitorChart').getContext('2d');

    Chart.defaults.color = '#64748b';
    Chart.defaults.font.family = 'Inter, sans-serif';

    const gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(59,130,246,0.35)');
    gradient.addColorStop(1, 'rgba(59,130,246,0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: stats.map(s => s.label),
            datasets: [{
                label: 'Pengunjung unik',
                data: stats.map(s => s.visitors),
                borderColor: '#3b82f6',
                backgroundColor: gradient,
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#020617',
                pointBorderColor: '#3b82f6',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, padding: 10 }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(255,255,255,0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>
@endpush