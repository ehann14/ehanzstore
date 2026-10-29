@extends('layouts.admin')

@section('title', 'Pelanggan')

@section('content')
@php
    $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');

    // Bangun URL filter sambil mempertahankan filter lain
    $filterUrl = fn (array $override) => route('super-admin.customers', array_filter(
        array_merge(['filter' => $filter !== 'all' ? $filter : null, 'q' => $search ?: null, 'sort' => $sort !== 'recent' ? $sort : null], $override),
        fn ($v) => $v !== null && $v !== ''
    ));

    $hasFilter = $filter !== 'all' || $search !== '';
@endphp

<div class="max-w-7xl mx-auto pb-20">

    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Pelanggan</h1>
        <p class="text-sm text-slate-400 mt-1">
            Pembeli dikelompokkan per nomor WhatsApp dari transaksi yang sudah selesai. Pembeli berulang = 2 transaksi atau lebih.
        </p>
    </div>

    {{-- Ringkasan seluruh pelanggan --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4 mb-2">
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ number_format($summary['customers']) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Total pelanggan</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5 border border-electric/30">
            <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ number_format($summary['repeaters']) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Pembeli berulang ({{ $summary['repeat_rate'] }}%)</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-lg sm:text-2xl font-display font-bold text-white truncate">{{ $rupiah($summary['spent']) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Total belanja semua pelanggan</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-lg sm:text-2xl font-display font-bold text-emerald-300 truncate">{{ $rupiah($summary['repeat_spent']) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Belanja pembeli berulang</p>
        </div>
    </div>
    <p class="text-[11px] text-slate-600 mb-6 leading-relaxed">
        Hanya transaksi selesai yang punya nomor WhatsApp yang dihitung.
        @if($withoutPhone > 0)
            <span class="text-amber-400">{{ $withoutPhone }} transaksi selesai belum punya nomor WA</span>
            sehingga belum masuk daftar. Lengkapi lewat <a href="{{ route('super-admin.sales') }}" class="text-electric hover:underline">halaman Transaksi</a>.
        @else
            Semua transaksi selesai sudah punya nomor WA.
        @endif
    </p>

    {{-- Filter --}}
    <div class="flex gap-2 overflow-x-auto mb-4">
        @foreach($filters as $key => $label)
            <a href="{{ $filterUrl(['filter' => $key === 'all' ? null : $key]) }}"
               class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide transition {{ $filter === $key ? 'bg-electric text-white' : 'bg-white/5 text-slate-400 border border-white/5 hover:bg-white/10 hover:text-white' }}">
                {{ $label }} ({{ $counts[$key] }})
            </a>
        @endforeach
    </div>

    {{-- Cari & urutkan --}}
    <form method="GET" action="{{ route('super-admin.customers') }}" class="flex flex-col sm:flex-row gap-3 mb-6">
        @if($filter !== 'all')<input type="hidden" name="filter" value="{{ $filter }}">@endif
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, nomor WA, atau isi catatan..."
               class="flex-1 min-w-0 px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-electric">
        <select name="sort" onchange="this.form.submit()"
                class="px-4 py-2.5 rounded-xl bg-slate-900 border border-white/10 text-sm text-slate-200 focus:outline-none focus:border-electric">
            @foreach($sorts as $key => $label)
                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-electric hover:bg-blue-500 text-white text-sm font-semibold transition">Cari</button>
        @if($hasFilter || $sort !== 'recent')
            <a href="{{ route('super-admin.customers') }}" class="px-5 py-2.5 rounded-xl border border-white/10 text-sm text-slate-300 text-center hover:bg-white/5 transition">Reset</a>
        @endif
    </form>

    @if($customers->isEmpty())
        <div class="glass-panel rounded-3xl p-16 text-center border border-white/5">
            <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <p class="text-slate-200 mb-1 font-medium">{{ $hasFilter ? 'Tidak ada pelanggan yang cocok.' : 'Belum ada pelanggan.' }}</p>
            <p class="text-sm text-slate-500">{{ $hasFilter ? 'Coba ubah atau reset filter.' : 'Pelanggan muncul setelah ada transaksi selesai dengan nomor WhatsApp pembeli.' }}</p>
        </div>
    @else
        <div class="glass-panel rounded-2xl border border-white/5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 border-b border-white/5">
                            <th class="px-4 py-3 font-semibold">Pelanggan</th>
                            <th class="px-4 py-3 font-semibold">Pembelian</th>
                            <th class="px-4 py-3 font-semibold text-right">Total belanja</th>
                            <th class="px-4 py-3 font-semibold text-right">Laba</th>
                            <th class="px-4 py-3 font-semibold">Terakhir beli</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($customers as $c)
                            <tr class="hover:bg-white/[0.03] transition-colors">
                                <td class="px-4 py-3">
                                    <a href="{{ route('super-admin.customers.show', $c->buyer_phone) }}" class="font-semibold text-white hover:text-electric truncate block max-w-[16rem]">{{ $c->name }}</a>
                                    <a href="{{ $c->wa_url }}" target="_blank" rel="noopener" class="text-[11px] text-emerald-400 hover:underline">{{ $c->local }}</a>
                                    @if($c->note !== '')
                                        <p class="text-[11px] text-amber-300/80 mt-1 truncate max-w-[16rem]" title="{{ $c->note }}">Catatan: {{ $c->note }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-white font-semibold">{{ $c->orders }}x</span>
                                    @if($c->orders >= 3)
                                        <span class="ml-1 px-2 py-0.5 rounded-md border text-[10px] font-bold uppercase bg-emerald-500/15 text-emerald-300 border-emerald-500/30">Langganan</span>
                                    @elseif($c->orders >= 2)
                                        <span class="ml-1 px-2 py-0.5 rounded-md border text-[10px] font-bold uppercase bg-electric/15 text-blue-300 border-electric/30">Berulang</span>
                                    @endif
                                    @if($c->first_time && $c->orders >= 2)
                                        <p class="text-[11px] text-slate-600 mt-1">sejak {{ $c->first_time->format('d M Y') }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-white font-medium whitespace-nowrap">{{ $rupiah($c->total_spent) }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap {{ $c->total_profit < 0 ? 'text-red-300' : 'text-emerald-300' }}">
                                    {{ ($c->total_profit < 0 ? '-' : '') . $rupiah(abs($c->total_profit)) }}
                                </td>
                                <td class="px-4 py-3 text-slate-300 whitespace-nowrap">
                                    {{ $c->last_time?->format('d M Y') ?? '-' }}
                                    @if($c->last_time)
                                        <p class="text-[11px] text-slate-600">{{ $c->last_time->diffForHumans() }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('super-admin.customers.show', $c->buyer_phone) }}" class="text-xs font-semibold text-electric hover:underline">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-8">{{ $customers->links() }}</div>
    @endif
</div>
@endsection