@extends('layouts.admin')

@section('title', 'Analitik')

@section('content')
@php
    $n = fn ($v) => number_format($v);

    $cards = [
        ['Pengunjung Unik', $summary['visitors'], 'text-electric bg-electric/10'],
        ['Tayangan Halaman', $summary['page_views'], 'text-violet-300 bg-violet-500/10'],
        ['Detail Akun Dilihat', $summary['account_views'], 'text-amber-300 bg-amber-500/10'],
        ['Klik WhatsApp', $summary['wa_clicks'], 'text-emerald-300 bg-emerald-500/10'],
    ];

    $eventLabels = [
        'page_view'      => 'Tayangan halaman',
        'view_account'   => 'Lihat detail akun',
        'click_whatsapp' => 'Klik WhatsApp',
        'search'         => 'Pencarian',
    ];

    $eventMax = max(1, (int) $eventBreakdown->max('total'));
    $pageMax  = max(1, (int) $topPages->max('total'));
    $hasData  = collect($dailyStats)->sum('views') > 0;
@endphp

<div class="max-w-7xl mx-auto pb-16">

    <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Analitik Pengunjung</h1>
            <p class="text-sm text-slate-400 mt-1">Perilaku pengunjung selama {{ $range }} hari terakhir.</p>
        </div>

        <div class="inline-flex p-1 rounded-xl glass-panel self-start sm:self-auto">
            @foreach([7, 14, 30] as $r)
                <a href="{{ route('super-admin.analytics', ['range' => $r]) }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $range === $r ? 'bg-electric text-white shadow-lg shadow-electric/20' : 'text-slate-400 hover:text-white' }}">
                    {{ $r }} hari
                </a>
            @endforeach
        </div>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @foreach($cards as [$label, $value, $tone])
            <div class="glass-panel rounded-2xl p-5">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center mb-4 {{ $tone }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ $n($value) }}</p>
                <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    <!-- Grafik -->
    <div class="glass-panel rounded-2xl p-5 sm:p-6 mb-6">
        <div class="mb-5">
            <h3 class="font-bold text-white">Tren Harian</h3>
            <p class="text-xs text-slate-500">Pengunjung unik dibandingkan total aktivitas per hari</p>
        </div>
        <div class="relative h-72">
            <canvas id="trendChart"></canvas>
            @unless($hasData)
                <div class="absolute inset-0 flex items-center justify-center text-slate-500 text-sm text-center bg-slate-900/60 rounded-xl">
                    Belum ada data pada rentang ini.
                </div>
            @endunless
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Jenis aktivitas -->
        <div class="glass-panel rounded-2xl p-5 sm:p-6">
            <h3 class="font-bold text-white mb-5">Jenis Aktivitas</h3>
            <div class="space-y-4">
                @forelse($eventBreakdown as $row)
                    <div>
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="text-slate-300">{{ $eventLabels[$row->event_type] ?? $row->event_type }}</span>
                            <span class="font-semibold text-white">{{ $n($row->total) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-white/5 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-royal to-electric" style="width: {{ round($row->total / $eventMax * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 text-center py-8">Belum ada aktivitas.</p>
                @endforelse
            </div>
        </div>

        <!-- Halaman terpopuler -->
        <div class="glass-panel rounded-2xl p-5 sm:p-6">
            <h3 class="font-bold text-white mb-5">Halaman Terpopuler</h3>
            <div class="space-y-4">
                @forelse($topPages as $row)
                    <div>
                        <div class="flex justify-between gap-3 text-sm mb-1.5">
                            <span class="text-slate-300 font-mono text-xs truncate">/{{ ltrim($row->page_path, '/') }}</span>
                            <span class="font-semibold text-white shrink-0">{{ $n($row->total) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-white/5 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-violet-600 to-violet-400" style="width: {{ round($row->total / $pageMax * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 text-center py-8">Belum ada data halaman.</p>
                @endforelse
            </div>
        </div>

        <!-- Akun terpopuler -->
        <div class="glass-panel rounded-2xl p-5 sm:p-6 lg:col-span-2">
            <h3 class="font-bold text-white mb-5">Akun Paling Banyak Dilihat</h3>
            @if($topAccounts->isEmpty())
                <p class="text-sm text-slate-500 text-center py-8">Belum ada akun yang dilihat pada rentang ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[520px]">
                        <thead>
                            <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500">
                                <th class="pb-3 font-semibold w-10">#</th>
                                <th class="pb-3 font-semibold">Akun</th>
                                <th class="pb-3 font-semibold">Harga</th>
                                <th class="pb-3 font-semibold">Status</th>
                                <th class="pb-3 font-semibold text-right">Dilihat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($topAccounts as $i => $item)
                                <tr class="hover:bg-white/[0.03] transition">
                                    <td class="py-3 text-slate-500 font-mono text-xs">{{ $i + 1 }}</td>
                                    <td class="py-3">
                                        <p class="text-white font-medium">{{ $item->account?->name ?? 'Akun dihapus' }}</p>
                                        <p class="text-[11px] text-slate-500 font-mono">{{ $item->account?->account_code ?? '-' }}@if($item->account) &middot; TH {{ $item->account->town_hall }}@endif</p>
                                    </td>
                                    <td class="py-3 text-brand-400 font-semibold">
                                        {{ $item->account ? 'Rp ' . number_format($item->account->price, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="py-3">
                                        @if($item->account)
                                            @php $sold = $item->account->sale_status === 'sold'; @endphp
                                            <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $sold ? 'bg-red-500/15 text-red-300 border-red-500/30' : 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' }}">
                                                {{ $sold ? 'Terjual' : 'Tersedia' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right font-bold text-white">{{ $n($item->views) }}x</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const daily = @json($dailyStats);

    Chart.defaults.color = '#64748b';
    Chart.defaults.font.family = 'Inter, sans-serif';

    new Chart(document.getElementById('trendChart'), {
        type: 'bar',
        data: {
            labels: daily.map(d => d.label),
            datasets: [
                {
                    type: 'line',
                    label: 'Pengunjung unik',
                    data: daily.map(d => d.visitors),
                    borderColor: '#3b82f6',
                    backgroundColor: '#3b82f6',
                    borderWidth: 2.5,
                    tension: 0.4,
                    pointRadius: 3,
                    order: 1
                },
                {
                    label: 'Total aktivitas',
                    data: daily.map(d => d.views),
                    backgroundColor: 'rgba(139,92,246,0.35)',
                    borderRadius: 6,
                    order: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } },
                tooltip: { backgroundColor: '#0f172a', borderColor: 'rgba(255,255,255,0.1)', borderWidth: 1, padding: 10 }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(255,255,255,0.05)' } },
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } }
            }
        }
    });
</script>
@endpush