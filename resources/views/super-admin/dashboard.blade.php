@extends('layouts.admin')

@section('title', 'Dashboard Super Admin')

@section('content')
@php
    $rupiah  = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
    $delta   = $yesterdayVisitors > 0 ? round((($todayVisitors - $yesterdayVisitors) / $yesterdayVisitors) * 100) : null;
    $hasData = collect($visitorStats)->sum('visitors') > 0;


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
                <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold flex items-center justify-end gap-1.5">
                    <span id="liveDot" class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                    </span>
                    <span id="liveLabel">Live &middot; diperbarui</span>
                </p>
                <p class="text-sm text-brand-400 font-mono" id="liveUpdated">{{ now()->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }} WIB</p>
            </div>
            <a href="{{ route('super-admin.sales') }}"
               class="inline-flex items-center gap-2 bg-electric hover:bg-blue-500 text-white px-4 py-3 rounded-xl text-sm font-semibold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Konfirmasi Penjualan
            </a>
        </div>
    </div>

    <!-- Statistik -->
    <div data-live="cards">
    @include('super-admin.partials.live-cards')
    </div>

    <!-- Akun belum terjual + total harga -->
    @include('super-admin._unsold-card')

    <!-- Pengingat stok lama -->
    @include('admin.stale._alert')

    <!-- Pengingat garansi -->
    <div data-live="warranty">
    @include('super-admin.partials.live-warranty')
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
                <script type="application/json" data-live-json="visitorStats">@json($visitorStats)</script>
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
            <div data-live="top-accounts" class="space-y-2.5 max-h-80 overflow-y-auto pr-1 custom-scrollbar">
                @include('super-admin.partials.live-top-accounts')
            </div>
        </div>
    </div>

    <!-- Laporan konversi -->
    @include('super-admin._conversion-report')

    <!-- Transaksi terbaru -->
    <div class="glass-panel rounded-2xl p-5 sm:p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-bold text-white">Transaksi Terbaru</h3>
            <a href="{{ route('super-admin.sales') }}" class="text-xs text-electric hover:text-blue-300 font-medium transition">Lihat semua &rarr;</a>
        </div>

        <div data-live="recent">
            @include('super-admin.partials.live-recent')
        </div>
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

    const visitorChart = new Chart(ctx, {
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
    // ---- Auto-update dashboard tanpa refresh halaman ----------------------------
    (function () {
        const INTERVAL = {{ \App\Support\BandwidthSaver::pollMs(20000) }}; // 20 detik (6x lebih jarang saat Mode Hemat Bandwidth aktif)
        const dot = document.getElementById('liveDot');
        const label = document.getElementById('liveLabel');
        const stamp = document.getElementById('liveUpdated');
        let busy = false;

        function setStatus(ok) {
            if (!dot || !label) return;
            dot.classList.toggle('opacity-40', !ok);
            label.innerHTML = ok ? 'Live &middot; diperbarui' : 'Offline &middot; coba lagi...';
        }

        // Endpoint ringan: hanya mengirim potongan data yang berubah (JSON), bukan seluruh halaman.
        // Bila tidak ada yang berubah, server hanya membalas {"same":true} (puluhan byte).
        const LIVE_URL = @json(route('super-admin.dashboard.live', [], false));
        let lastHash = '';

        async function refresh() {
            if (busy || document.hidden) return;
            busy = true;
            try {
                const res = await fetch(LIVE_URL + '?v=' + encodeURIComponent(lastHash), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    cache: 'no-store'
                });
                // sesi habis -> server mengarahkan ke halaman login; hentikan update diam-diam
                if (!res.ok || res.redirected) { setStatus(false); return; }

                const data = await res.json();
                if (!data || !data.v) { setStatus(false); return; }

                if (!data.same) {
                    Object.keys(data.fragments || {}).forEach((name) => {
                        const cur = document.querySelector('[data-live="' + name + '"]');
                        if (cur && cur.innerHTML.trim() !== data.fragments[name].trim()) cur.innerHTML = data.fragments[name];
                    });

                    if (data.visitorStats) {
                        visitorChart.data.labels = data.visitorStats.map(s => s.label);
                        visitorChart.data.datasets[0].data = data.visitorStats.map(s => s.visitors);
                        visitorChart.update('none');
                    }

                    lastHash = data.v;
                }

                if (stamp && window.EhzTime) stamp.textContent = EhzTime.date().replace(/^[^,]+, /, '') + ', ' + EhzTime.time() + ' WIB';
                setStatus(true);
            } catch (e) {
                setStatus(false);
            } finally {
                busy = false;
            }
        }

        setInterval(refresh, INTERVAL);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    })();
</script>
@endpush