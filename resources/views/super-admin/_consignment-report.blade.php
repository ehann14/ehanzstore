{{--
    Tab "Titipan" pada Laporan Bulanan: komisi toko, setoran belum dibayar, dan pemilik teraktif.
    Variabel: $consign (hasil SalesReport::consignment())
--}}
@php
    $rp = fn ($n) => ($n < 0 ? '-' : '') . 'Rp ' . number_format(abs($n), 0, ',', '.');
    $c   = $consign['summary'];
    $all = $consign['unpaidAll'];
    $chg = $consign['commissionChange'];
@endphp

<!-- Ringkasan titipan -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="glass-panel rounded-2xl p-5">
        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2">Total Komisi Toko</p>
        <p class="text-2xl font-display font-bold text-emerald-400">{{ $rp($c['commission']) }}</p>
        @if($chg !== null)
            <p class="text-[11px] mt-1 {{ $chg >= 0 ? 'text-emerald-400' : 'text-red-400' }}">
                {{ $chg >= 0 ? '+' : '' }}{{ number_format($chg, 1, ',', '.') }}% dari {{ $consign['prevLabel'] }}
            </p>
        @else
            <p class="text-[11px] mt-1 text-slate-500">{{ $consign['prevLabel'] }}: {{ $rp($consign['prev']['commission']) }}</p>
        @endif
    </div>
    <div class="glass-panel rounded-2xl p-5">
        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2">Akun Titipan Terjual</p>
        <p class="text-2xl font-display font-bold text-white">{{ number_format($c['sold']) }}</p>
        <p class="text-[11px] mt-1 text-slate-500">Harga jual {{ $rp($c['revenue']) }}</p>
    </div>
    <div class="glass-panel rounded-2xl p-5">
        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2">Setoran Belum Dibayar</p>
        <p class="text-2xl font-display font-bold {{ $c['unpaid_total'] > 0 ? 'text-amber-300' : 'text-white' }}">{{ $rp($c['unpaid_total']) }}</p>
        <p class="text-[11px] mt-1 text-slate-500">{{ number_format($c['unpaid_count']) }} transaksi bulan ini</p>
    </div>
    <div class="glass-panel rounded-2xl p-5">
        <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2">Bagian Pemilik</p>
        <p class="text-2xl font-display font-bold text-white">{{ $rp($c['payout']) }}</p>
        <p class="text-[11px] mt-1 text-slate-500">Sudah disetor {{ $rp($c['paid_total']) }}</p>
    </div>
</div>

<!-- Setoran tertunggak dari semua bulan -->
@if($all['count'] > 0)
    <div class="rounded-2xl border border-amber-500/30 bg-amber-500/5 p-4 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <p class="text-sm text-amber-100">
            Total setoran belum dibayar dari <strong>semua bulan</strong>:
            <strong>{{ $rp($all['total']) }}</strong> ({{ number_format($all['count']) }} transaksi@if($all['oldest']), paling lama sejak {{ $all['oldest'] }}@endif).
        </p>
        <a href="{{ route('admin.consignments.index', ['tab' => 'unpaid']) }}" class="shrink-0 text-xs font-semibold text-electric hover:underline">Buka menu Titip Jual</a>
    </div>
@endif

<!-- Pemilik teraktif -->
<div class="glass-panel rounded-2xl overflow-hidden mb-6">
    <div class="p-5 border-b border-white/5">
        <h2 class="text-lg font-display font-bold text-white">Pemilik Teraktif</h2>
        <p class="text-[11px] text-slate-500 mt-0.5">Diurutkan dari akun titipan terjual terbanyak bulan ini, lalu komisi terbesar.</p>
        @if($consign['topOwners'])
            <p class="text-sm text-slate-300 mt-3">Teraktif: <span class="font-semibold text-white">{{ implode(', ', $consign['topOwners']) }}</span></p>
        @endif
    </div>

    @if($consign['owners']->isEmpty())
        <p class="p-8 text-center text-sm text-slate-400">Belum ada akun titipan terjual pada {{ $consign['range']['label'] }}.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 border-b border-white/5 whitespace-nowrap">
                        <th class="px-4 py-3.5 font-semibold">#</th>
                        <th class="px-4 py-3.5 font-semibold">Pemilik</th>
                        <th class="px-4 py-3.5 font-semibold text-right">Terjual</th>
                        <th class="px-4 py-3.5 font-semibold text-right">Harga Jual</th>
                        <th class="px-4 py-3.5 font-semibold text-right">Komisi Toko</th>
                        <th class="px-4 py-3.5 font-semibold text-right">Bagian Pemilik</th>
                        <th class="px-4 py-3.5 font-semibold text-right">Belum Disetor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($consign['owners'] as $i => $o)
                        <tr class="hover:bg-white/[0.03] transition">
                            <td class="px-4 py-3.5 text-slate-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-3.5">
                                <p class="text-white font-medium">{{ $o->name }}</p>
                                @if($o->phone) <p class="text-[11px] text-slate-500">{{ $o->phone }}</p> @endif
                            </td>
                            <td class="px-4 py-3.5 text-right text-white font-semibold">{{ number_format($o->sold) }}</td>
                            <td class="px-4 py-3.5 text-right text-slate-300 whitespace-nowrap">{{ $rp($o->revenue) }}</td>
                            <td class="px-4 py-3.5 text-right text-emerald-400 font-semibold whitespace-nowrap">{{ $rp($o->commission) }}</td>
                            <td class="px-4 py-3.5 text-right text-slate-300 whitespace-nowrap">{{ $rp($o->payout) }}</td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap {{ $o->unpaid > 0 ? 'text-amber-300 font-semibold' : 'text-slate-500' }}">{{ $rp($o->unpaid) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Daftar transaksi titipan -->
<div class="glass-panel rounded-2xl overflow-hidden">
    <div class="p-5 border-b border-white/5">
        <h2 class="text-lg font-display font-bold text-white">Transaksi Titipan ({{ count($consign['rows']) }})</h2>
    </div>

    @if(! $consign['rows'])
        <p class="p-8 text-center text-sm text-slate-400">Tidak ada transaksi titipan selesai pada {{ $consign['range']['label'] }}.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 border-b border-white/5 whitespace-nowrap">
                        <th class="px-4 py-3.5 font-semibold">Tanggal</th>
                        <th class="px-4 py-3.5 font-semibold">Akun</th>
                        <th class="px-4 py-3.5 font-semibold">Pemilik</th>
                        <th class="px-4 py-3.5 font-semibold text-right">Harga Jual</th>
                        <th class="px-4 py-3.5 font-semibold text-right">Komisi</th>
                        <th class="px-4 py-3.5 font-semibold text-right">Bagian Pemilik</th>
                        <th class="px-4 py-3.5 font-semibold">Setoran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @foreach($consign['rows'] as $r)
                        <tr class="hover:bg-white/[0.03] transition align-top">
                            <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap">{{ $r['time'] }}</td>
                            <td class="px-4 py-3.5 min-w-[160px]">
                                <p class="text-white font-medium">{{ $r['name'] }}</p>
                                <p class="text-[11px] text-slate-500 font-mono">{{ $r['code'] }} &middot; {{ $r['acc_code'] }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-slate-300">{{ $r['owner'] }}</td>
                            <td class="px-4 py-3.5 text-right text-slate-300 whitespace-nowrap">{{ $rp($r['price']) }}</td>
                            <td class="px-4 py-3.5 text-right text-emerald-400 font-semibold whitespace-nowrap">{{ $rp($r['commission']) }}</td>
                            <td class="px-4 py-3.5 text-right text-slate-300 whitespace-nowrap">{{ $rp($r['payout']) }}</td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full border text-[11px] font-medium {{ $r['paid'] ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30' : 'bg-amber-500/15 text-amber-300 border-amber-500/30' }}">{{ $r['paid_label'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-white/10 font-semibold text-white whitespace-nowrap">
                        <td class="px-4 py-3.5" colspan="3">TOTAL</td>
                        <td class="px-4 py-3.5 text-right">{{ $rp($c['revenue']) }}</td>
                        <td class="px-4 py-3.5 text-right text-emerald-400">{{ $rp($c['commission']) }}</td>
                        <td class="px-4 py-3.5 text-right">{{ $rp($c['payout']) }}</td>
                        <td class="px-4 py-3.5"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>