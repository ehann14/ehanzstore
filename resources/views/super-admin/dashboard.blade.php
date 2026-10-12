@extends('layouts.app')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="pt-28 pb-12 max-w-7xl mx-auto px-4 min-h-[calc(100vh-80px)]">
    
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Dashboard Analitik</h1>
            <p class="text-slate-400">Ringkasan performa toko dan aktivitas pengunjung terkini.</p>
        </div>
        <div class="glass-panel px-4 py-2 rounded-xl text-right">
            <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Terakhir Diperbarui</p>
            <p class="text-sm text-brand-400 font-mono">{{ now()->format('d M Y, H:i') }}</p>
        </div>
    </div>

    <!-- Statistik Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @php
            $cards = [
                ['label' => 'Total Pengunjung', 'val' => $totalVisitors ?? 0, 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                ['label' => 'Pengunjung Hari Ini', 'val' => $todayVisitors ?? 0, 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label' => 'Akun Tersedia', 'val' => $availableAccounts ?? 0, 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                ['label' => 'Akun Terjual', 'val' => $soldAccounts ?? 0, 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z']
            ];
        @endphp

        @foreach($cards as $card)
        <div class="glass-panel rounded-3xl p-6 hover:bg-white/5 transition-all duration-300 group">
            <div class="flex justify-between items-start mb-4">
                <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">{{ $card['label'] }}</p>
                <div class="p-2 rounded-xl bg-brand-500/10 text-brand-400 group-hover:bg-brand-500 group-hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"></path></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($card['val']) }}</p>
        </div>
        @endforeach
    </div>

    <!-- Grafik & Tabel -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Grafik Pengunjung -->
        <div class="lg:col-span-2 glass-panel rounded-3xl p-6 flex flex-col">
            <h3 class="text-lg font-bold text-white mb-6">Tren Pengunjung (7 Hari Terakhir)</h3>
            <div class="relative flex-grow min-h-[300px]">
                <canvas id="visitorChart"></canvas>
                @if(empty($visitorStats))
                    <div class="absolute inset-0 flex items-center justify-center text-slate-500 text-sm">
                        Belum ada data kunjungan dalam 7 hari terakhir.
                    </div>
                @endif
            </div>
        </div>

        <!-- Akun Terpopuler -->
        <div class="glass-panel rounded-3xl p-6">
            <h3 class="text-lg font-bold text-white mb-6">Akun Paling Diminati</h3>
            <div class="space-y-3 max-h-[350px] overflow-y-auto pr-2 custom-scrollbar">
                @forelse($topAccounts ?? [] as $item)
                    <div class="flex items-center justify-between p-3 rounded-2xl bg-white/5 border border-white/5 hover:border-brand-500/30 transition-colors">
                        <div class="overflow-hidden">
                            <p class="text-sm font-bold text-white truncate">{{ $item->account?->name ?? 'Akun Dihapus' }}</p>
                            <p class="text-[10px] text-slate-500 font-mono">{{ $item->account?->account_code }}</p>
                        </div>
                        <span class="bg-brand-500/20 text-brand-300 text-xs font-bold px-2.5 py-1 rounded-lg">
                            {{ $item->views }}x dilihat
                        </span>
                    </div>
                @empty
                    <div class="text-center py-10 text-slate-500 text-sm">
                        Belum ada akun yang dilihat pengunjung.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Hanya jalankan chart jika ada data
    const stats = @json($visitorStats ?? []);
    
    if (stats.length > 0) {
        const ctx = document.getElementById('visitorChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: stats.map(s => s.date),
                datasets: [{
                    label: 'Pengunjung Unik',
                    data: stats.map(s => s.count),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0f172a',
                    pointBorderColor: '#3b82f6',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#64748b' }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { color: '#64748b' }
                    }
                }
            }
        });
    }
</script>
@endpush
@endsection