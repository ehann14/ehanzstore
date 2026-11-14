@php
    $rupiah  = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');

    $statusBadge = [
        'completed' => ['Selesai', 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'],
        'pending'   => ['Menunggu', 'bg-amber-500/15 text-amber-300 border-amber-500/30'],
        'cancelled' => ['Batal', 'bg-red-500/15 text-red-300 border-red-500/30'],
    ];
@endphp
        @if($recentSales->isEmpty())
            <div class="text-center py-10 text-slate-500 text-sm">Belum ada transaksi tercatat.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[680px]">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500">
                            <th class="pb-3 font-semibold">Kode</th>
                            <th class="pb-3 font-semibold">Akun</th>
                            <th class="pb-3 font-semibold">Harga</th>
                            <th class="pb-3 font-semibold">Modal</th>
                            <th class="pb-3 font-semibold">Laba</th>
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
                                <td class="py-3 text-slate-400">{{ $rupiah($sale->cost_price) }}</td>
                                <td class="py-3 font-semibold {{ $sale->profit() < 0 ? 'text-red-400' : 'text-emerald-400' }}">{{ $sale->profit() < 0 ? '-' : '' }}{{ $rupiah(abs($sale->profit())) }}</td>
                                <td class="py-3"><span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $b[1] }}">{{ $b[0] }}</span></td>
                                <td class="py-3 text-right text-xs text-slate-500">{{ $sale->transactionTimeLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif