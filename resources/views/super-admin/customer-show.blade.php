@extends('layouts.admin')

@section('title', 'Pelanggan ' . $name)

@section('content')
@php
    $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');

    $statusBadge = [
        'completed' => ['Selesai', 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'],
        'cancelled' => ['Dibatalkan', 'bg-red-500/15 text-red-300 border-red-500/30'],
    ];

    $bookingBadge = [
        'pending'   => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
        'active'    => 'bg-blue-500/15 text-blue-300 border-blue-500/30',
        'completed' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
        'cancelled' => 'bg-red-500/15 text-red-300 border-red-500/30',
        'expired'   => 'bg-slate-500/15 text-slate-300 border-slate-500/30',
    ];
@endphp

<div class="max-w-5xl mx-auto pb-20">

    <a href="{{ route('super-admin.customers') }}" class="inline-flex items-center gap-1 text-sm text-slate-400 hover:text-white mb-4 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Semua pelanggan
    </a>

    {{-- Identitas --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-display font-bold text-white break-words">{{ $name }}</h1>
                @if($stats['orders'] >= 3)
                    <span class="px-2.5 py-1 rounded-md border text-[11px] font-bold uppercase bg-emerald-500/15 text-emerald-300 border-emerald-500/30">Langganan</span>
                @elseif($stats['orders'] >= 2)
                    <span class="px-2.5 py-1 rounded-md border text-[11px] font-bold uppercase bg-electric/15 text-blue-300 border-electric/30">Pembeli berulang</span>
                @endif
            </div>
            <p class="text-sm text-slate-400 mt-1">{{ $local }}</p>
            @if($names->count() > 1)
                <p class="text-[11px] text-slate-600 mt-1">Pernah memakai nama: {{ $names->implode(', ') }}</p>
            @endif
        </div>
        <a href="{{ $waUrl }}" target="_blank" rel="noopener"
           class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold transition">
            Chat WhatsApp
        </a>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-8">
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-2xl font-display font-bold text-white">{{ $stats['orders'] }}x</p>
            <p class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold mt-1">Pembelian selesai</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5 border border-electric/30">
            <p class="text-lg sm:text-xl font-display font-bold text-white truncate">{{ $rupiah($stats['spent']) }}</p>
            <p class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold mt-1">Total belanja</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-lg sm:text-xl font-display font-bold truncate {{ $stats['profit'] < 0 ? 'text-red-300' : 'text-emerald-300' }}">{{ ($stats['profit'] < 0 ? '-' : '') . $rupiah(abs($stats['profit'])) }}</p>
            <p class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold mt-1">Laba dari pelanggan ini</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-lg sm:text-xl font-display font-bold text-white truncate">{{ $rupiah($stats['average']) }}</p>
            <p class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold mt-1">Rata-rata per transaksi</p>
        </div>
    </div>
    <p class="text-[11px] text-slate-600 -mt-5 mb-8">
        @if($stats['first'])
            Pembelian pertama {{ $stats['first']->format('d M Y') }}, terakhir {{ $stats['last']->format('d M Y') }}.
        @endif
        @if($stats['warranty'] > 0)
            <span class="text-emerald-400">{{ $stats['warranty'] }} garansi masih aktif.</span>
        @endif
    </p>

    {{-- Catatan --}}
    <div class="glass-panel rounded-2xl border border-white/5 p-5 mb-8">
        <h2 class="font-display font-bold text-white mb-1">Catatan</h2>
        <p class="text-xs text-slate-500 mb-3">Hanya terlihat oleh Super Admin. Kosongkan lalu simpan untuk menghapus catatan.</p>

        <form method="POST" action="{{ route('super-admin.customers.note', $phone) }}">
            @csrf
            @method('PUT')
            <textarea name="note" rows="4" maxlength="2000" placeholder="Contoh: sering minta TH tinggi, bayar selalu tepat waktu, suka dihubungi malam hari."
                      class="w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-electric">{{ old('note', $note->note ?? '') }}</textarea>
            @error('note')<p class="text-xs text-red-400 mt-1">{{ $message }}</p>@enderror

            <div class="flex flex-wrap items-center justify-between gap-3 mt-3">
                <p class="text-[11px] text-slate-600">
                    @if($note && $note->updated_at)
                        Terakhir diubah {{ $note->updated_at->clone()->timezone(\App\Models\Sale::DISPLAY_TZ)->format('d M Y, H:i') }} WIB{{ $note->updater ? ' oleh ' . $note->updater->name : '' }}
                    @endif
                </p>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-electric hover:bg-blue-500 text-white text-sm font-semibold transition">Simpan catatan</button>
            </div>
        </form>
    </div>

    {{-- Riwayat pembelian --}}
    <h2 class="font-display font-bold text-white mb-3">Riwayat pembelian ({{ $sales->count() }})</h2>

    @if($sales->isEmpty())
        <div class="glass-panel rounded-2xl border border-white/5 p-8 text-center text-sm text-slate-500 mb-8">Belum ada transaksi untuk nomor ini.</div>
    @else
        <div class="glass-panel rounded-2xl border border-white/5 overflow-hidden mb-8">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 border-b border-white/5">
                            <th class="px-4 py-3 font-semibold">Transaksi</th>
                            <th class="px-4 py-3 font-semibold">Akun</th>
                            <th class="px-4 py-3 font-semibold text-right">Harga</th>
                            <th class="px-4 py-3 font-semibold">Garansi</th>
                            <th class="px-4 py-3 font-semibold">Ulasan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($sales as $sale)
                            @php
                                [$sLabel, $sClass] = $statusBadge[$sale->transaction_status] ?? [ucfirst($sale->transaction_status), 'bg-slate-500/15 text-slate-300 border-slate-500/30'];
                                [$wLabel, $wClass] = $sale->warrantyBadge();
                            @endphp
                            <tr class="hover:bg-white/[0.03] transition-colors align-top">
                                <td class="px-4 py-3">
                                    <p class="font-mono text-xs text-slate-300">{{ $sale->transaction_code }}</p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $sale->transactionTimeLabel() }} WIB</p>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-md border text-[10px] font-bold uppercase {{ $sClass }}">{{ $sLabel }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-white font-medium truncate max-w-[14rem]">{{ $sale->account?->name ?? '(akun dihapus)' }}</p>
                                    <p class="text-[11px] font-mono text-slate-500">{{ $sale->account_code_snapshot }}</p>
                                </td>
                                <td class="px-4 py-3 text-right text-white whitespace-nowrap">{{ $rupiah($sale->sale_price) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-0.5 rounded-md border text-[10px] font-bold {{ $wClass }}">{{ $wLabel }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if($sale->review)
                                        <span class="text-amber-300 font-semibold">★ {{ $sale->review->rating }}</span>
                                        <span class="text-slate-500">({{ $sale->review->status === 'approved' ? 'tampil' : ($sale->review->status === 'pending' ? 'menunggu' : 'ditolak') }})</span>
                                    @else
                                        <span class="text-slate-600">Belum ada</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Riwayat booking --}}
    @if($bookings->isNotEmpty())
        <h2 class="font-display font-bold text-white mb-3">Riwayat booking DP ({{ $bookings->count() }})</h2>
        <div class="glass-panel rounded-2xl border border-white/5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[620px] text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 border-b border-white/5">
                            <th class="px-4 py-3 font-semibold">Booking</th>
                            <th class="px-4 py-3 font-semibold">Akun</th>
                            <th class="px-4 py-3 font-semibold text-right">DP</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($bookings as $b)
                            <tr class="hover:bg-white/[0.03] transition-colors">
                                <td class="px-4 py-3">
                                    <p class="font-mono text-xs text-slate-300">{{ $b->booking_code }}</p>
                                    <p class="text-[11px] text-slate-500">{{ $b->created_at->clone()->timezone(\App\Models\Sale::DISPLAY_TZ)->format('d M Y, H:i') }} WIB</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-white truncate max-w-[14rem]">{{ $b->account_name_snapshot }}</p>
                                    <p class="text-[11px] font-mono text-slate-500">{{ $b->account_code_snapshot }}</p>
                                </td>
                                <td class="px-4 py-3 text-right text-white whitespace-nowrap">
                                    {{ $rupiah($b->dp_amount) }}
                                    <p class="text-[11px] font-semibold {{ $b->isPaid() ? 'text-emerald-300' : 'text-amber-300' }}">{{ $b->paymentLabel() }}</p>
                                    <p class="text-[11px] text-slate-500">{{ \App\Models\Booking::DP_LABELS[$b->dp_state] ?? $b->dp_state }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-0.5 rounded-md border text-[10px] font-bold uppercase {{ $bookingBadge[$b->status] ?? $bookingBadge['expired'] }}">
                                        {{ \App\Models\Booking::STATUS_LABELS[$b->status] ?? $b->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection