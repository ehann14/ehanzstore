@extends('layouts.admin')

@section('title', 'Akun Belum Terjual')

@section('content')
@php
    $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');

    // Bangun URL filter sambil mempertahankan filter lain
    $filterUrl = fn (array $override) => route('super-admin.unsold', array_filter(
        array_merge(['type' => $type, 'publication' => $publication, 'q' => $search ?: null, 'sort' => $sort !== 'price_desc' ? $sort : null], $override),
        fn ($v) => $v !== null && $v !== ''
    ));

    $hasFilter = $type || $publication || $search !== '';
@endphp

<div class="max-w-7xl mx-auto pb-20">

    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Akun Belum Terjual</h1>
        <p class="text-sm text-slate-400 mt-1">
            Semua akun dan clan yang masih berstatus tersedia (termasuk draft), lengkap dengan total harga jualnya.
        </p>
    </div>

    {{-- Ringkasan: mengikuti filter yang aktif --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 sm:gap-4 mb-2">
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-2xl sm:text-3xl font-display font-bold text-white">{{ number_format($totals['count']) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Akun belum terjual</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5 border border-electric/30">
            <p class="text-lg sm:text-2xl font-display font-bold text-white truncate">{{ $rupiah($totals['price']) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Total harga jual</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-lg sm:text-2xl font-display font-bold text-white truncate">{{ $rupiah($totals['capital']) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Total modal tertahan</p>
        </div>
        <div class="glass-panel rounded-2xl p-4 sm:p-5">
            <p class="text-lg sm:text-2xl font-display font-bold truncate {{ $totals['profit'] < 0 ? 'text-red-300' : 'text-emerald-300' }}">{{ ($totals['profit'] < 0 ? '-' : '') . $rupiah(abs($totals['profit'])) }}</p>
            <p class="text-[11px] sm:text-xs text-slate-500 uppercase tracking-wider font-semibold mt-1">Estimasi laba</p>
        </div>
    </div>
    <p class="text-[11px] text-slate-600 mb-6 leading-relaxed">
        @if($hasFilter) Angka di atas sesuai filter yang sedang dipakai. @endif
        Estimasi laba = harga jual - modal, hanya dihitung dari akun yang modalnya sudah diisi
        @if($totals['no_cost'] > 0)
            ({{ $totals['no_cost'] }} akun belum diisi modalnya).
        @else
            (semua modal sudah terisi).
        @endif
    </p>

    {{-- Filter jenis & status --}}
    <div class="flex flex-col lg:flex-row lg:items-center gap-3 mb-4">
        <div class="flex gap-2 overflow-x-auto">
            @foreach([[null, 'Semua', $counts['all']], ['account', 'Akun', $counts['account']], ['clan', 'Clan', $counts['clan']]] as [$key, $label, $n])
                <a href="{{ $filterUrl(['type' => $key]) }}"
                   class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide transition {{ $type === $key ? 'bg-electric text-white' : 'bg-white/5 text-slate-400 border border-white/5 hover:bg-white/10 hover:text-white' }}">
                    {{ $label }} ({{ $n }})
                </a>
            @endforeach
        </div>
        <div class="flex gap-2 overflow-x-auto">
            @foreach([[null, 'Semua status', $counts['all']], ['published', 'Terbit', $counts['published']], ['draft', 'Draft', $counts['draft']]] as [$key, $label, $n])
                <a href="{{ $filterUrl(['publication' => $key]) }}"
                   class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wide transition {{ $publication === $key ? 'bg-emerald-500 text-white' : 'bg-white/5 text-slate-400 border border-white/5 hover:bg-white/10 hover:text-white' }}">
                    {{ $label }} ({{ $n }})
                </a>
            @endforeach
        </div>
    </div>

    {{-- Cari & urutkan --}}
    <form method="GET" action="{{ route('super-admin.unsold') }}" class="flex flex-col sm:flex-row gap-3 mb-6">
        @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
        @if($publication)<input type="hidden" name="publication" value="{{ $publication }}">@endif
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama atau kode akun..."
               class="flex-1 min-w-0 px-4 py-2.5 rounded-xl bg-white/5 border border-white/10 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-electric">
        <select name="sort" onchange="this.form.submit()"
                class="px-4 py-2.5 rounded-xl bg-slate-900 border border-white/10 text-sm text-slate-200 focus:outline-none focus:border-electric">
            @foreach($sorts as $key => $label)
                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-electric hover:bg-blue-500 text-white text-sm font-semibold transition">Cari</button>
        @if($hasFilter || $sort !== 'price_desc')
            <a href="{{ route('super-admin.unsold') }}" class="px-5 py-2.5 rounded-xl border border-white/10 text-sm text-slate-300 text-center hover:bg-white/5 transition">Reset</a>
        @endif
    </form>

    @if($accounts->isEmpty())
        <div class="glass-panel rounded-3xl p-16 text-center border border-white/5">
            <div class="w-16 h-16 bg-emerald-500/10 rounded-full flex items-center justify-center mx-auto mb-4 text-emerald-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-slate-200 mb-1 font-medium">{{ $hasFilter ? 'Tidak ada akun yang cocok dengan filter.' : 'Semua akun sudah terjual.' }}</p>
            <p class="text-sm text-slate-500">{{ $hasFilter ? 'Coba ubah atau reset filter.' : 'Tidak ada stok yang tersisa.' }}</p>
        </div>
    @else
        <div class="glass-panel rounded-2xl border border-white/5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 border-b border-white/5">
                            <th class="px-4 py-3 font-semibold">Akun</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Terpajang</th>
                            <th class="px-4 py-3 font-semibold text-right">Harga jual</th>
                            <th class="px-4 py-3 font-semibold text-right">Modal</th>
                            <th class="px-4 py-3 font-semibold text-right">Est. laba</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($accounts as $account)
                            @php
                                $imageUrl = $account->primaryImage?->image_path ? upload_url($account->primaryImage->image_path) : null;
                                $hasCost  = (float) $account->cost_price > 0;
                                $profit   = $account->estimatedProfit();
                                $listed   = $account->daysListed();
                                $isBooked = in_array((int) $account->id, $heldIds, true);
                            @endphp
                            <tr class="hover:bg-white/[0.03] transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-12 h-12 shrink-0 rounded-lg overflow-hidden bg-midnight border border-white/10">
                                            @if($imageUrl)
                                                <img src="{{ $imageUrl }}" alt="{{ $account->name }}" loading="lazy" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-900">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="px-2 py-0.5 rounded-md bg-electric/15 text-blue-300 text-[10px] font-bold uppercase">{{ $account->badgeLabel() }}</span>
                                                <span class="text-[11px] font-mono text-slate-500">{{ $account->account_code }}</span>
                                            </div>
                                            <p class="font-semibold text-white truncate max-w-[16rem]">{{ $account->name }}</p>
                                            @if($account->creator)
                                                <p class="text-[11px] text-slate-600">oleh {{ $account->creator->name }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col items-start gap-1">
                                        @if($account->publication_status === 'published')
                                            <span class="px-2.5 py-0.5 rounded-full border text-[11px] font-semibold bg-emerald-500/15 text-emerald-300 border-emerald-500/30">Terbit</span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full border text-[11px] font-semibold bg-slate-500/15 text-slate-300 border-slate-500/30">Draft</span>
                                        @endif
                                        @if($isBooked)
                                            <span class="px-2.5 py-0.5 rounded-full border text-[11px] font-semibold bg-amber-500/15 text-amber-300 border-amber-500/30">Di-booking</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-400 whitespace-nowrap">{{ $listed }} hari</td>
                                <td class="px-4 py-3 text-right font-bold text-white whitespace-nowrap">{{ $rupiah($account->price) }}</td>
                                <td class="px-4 py-3 text-right text-slate-400 whitespace-nowrap">
                                    @if($hasCost) {{ $rupiah($account->cost_price) }} @else <span class="text-slate-600">belum diisi</span> @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    @if($hasCost)
                                        <span class="{{ $profit < 0 ? 'text-red-300' : 'text-emerald-300' }}">{{ ($profit < 0 ? '-' : '') . $rupiah(abs($profit)) }}</span>
                                    @else
                                        <span class="text-slate-600">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.accounts.edit', $account) }}"
                                       class="inline-flex px-3 py-1.5 rounded-lg border border-white/10 text-xs font-semibold text-slate-300 hover:bg-white/5 transition">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-white/10 bg-white/[0.03] text-sm font-bold">
                            <td class="px-4 py-3 text-slate-300" colspan="3">Total {{ number_format($totals['count']) }} akun{{ $hasFilter ? ' (sesuai filter)' : '' }}</td>
                            <td class="px-4 py-3 text-right text-white whitespace-nowrap">{{ $rupiah($totals['price']) }}</td>
                            <td class="px-4 py-3 text-right text-slate-300 whitespace-nowrap">{{ $rupiah($totals['capital']) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap {{ $totals['profit'] < 0 ? 'text-red-300' : 'text-emerald-300' }}">{{ ($totals['profit'] < 0 ? '-' : '') . $rupiah(abs($totals['profit'])) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-8">{{ $accounts->links() }}</div>

        <p class="text-[11px] text-slate-600 mt-6 leading-relaxed">
            "Belum terjual" = status penjualan masih tersedia, baik yang sudah terbit maupun yang masih draft.
            Akun berstatus arsip dan akun yang sudah dihapus tidak dihitung. Baris total mencakup semua halaman, bukan hanya halaman ini.
        </p>
    @endif
</div>
@endsection