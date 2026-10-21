@extends('layouts.admin')

@section('title', 'Transaksi')

@section('content')
@php
    $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');

    $statusBadge = [
        'completed' => ['Selesai', 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'],
        'pending'   => ['Menunggu', 'bg-amber-500/15 text-amber-300 border-amber-500/30'],
        'cancelled' => ['Batal', 'bg-red-500/15 text-red-300 border-red-500/30'],
    ];
    $payBadge = [
        'paid'   => ['Lunas', 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'],
        'unpaid' => ['Belum bayar', 'bg-slate-500/15 text-slate-300 border-slate-500/30'],
    ];
    $inputCls = 'w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-electric focus:bg-white/10 outline-none transition';
@endphp

<div class="max-w-7xl mx-auto pb-16">

    <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Transaksi Penjualan</h1>
            <p class="text-sm text-slate-400 mt-1">Riwayat penjualan dan konfirmasi penjualan baru.</p>
        </div>
        <button type="button" onclick="openSaleModal()"
                class="inline-flex items-center justify-center gap-2 bg-electric hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Konfirmasi Penjualan
        </button>
    </div>

    <!-- Ringkasan -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
        <div class="glass-panel rounded-2xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2">Total Pendapatan</p>
            <p class="text-2xl font-display font-bold text-white">{{ $rupiah($stats['revenue']) }}</p>
        </div>
        <div class="glass-panel rounded-2xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2">Total Modal</p>
            <p class="text-2xl font-display font-bold text-white">{{ $rupiah($stats['cost']) }}</p>
        </div>
        <div class="glass-panel rounded-2xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2">Total Laba</p>
            <p class="text-2xl font-display font-bold {{ $stats['profit'] < 0 ? 'text-red-400' : 'text-emerald-400' }}">{{ $stats['profit'] < 0 ? '-' : '' }}{{ $rupiah(abs($stats['profit'])) }}</p>
        </div>
        <div class="glass-panel rounded-2xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2">Transaksi Selesai</p>
            <p class="text-2xl font-display font-bold text-white">{{ number_format($stats['completed']) }}</p>
        </div>
        <div class="glass-panel rounded-2xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2">Menunggu</p>
            <p class="text-2xl font-display font-bold text-white">{{ number_format($stats['pending']) }}</p>
        </div>
    </div>

    <!-- Daftar transaksi -->
    @if($sales->isEmpty())
        <div class="glass-panel rounded-3xl p-16 text-center">
            <p class="text-slate-300 mb-1">Belum ada transaksi.</p>
            <p class="text-sm text-slate-500">Klik "Konfirmasi Penjualan" untuk mencatat penjualan pertama.</p>
        </div>
    @else
        <!-- Tabel (desktop) -->
        <div class="hidden md:block glass-panel rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 border-b border-white/5 whitespace-nowrap">
                            <th class="px-4 py-3.5 font-semibold">Kode</th>
                            <th class="px-4 py-3.5 font-semibold">Akun</th>
                            <th class="px-4 py-3.5 font-semibold">Pembeli &amp; Garansi</th>
                            <th class="px-4 py-3.5 font-semibold">Harga Jual</th>
                            <th class="px-4 py-3.5 font-semibold">Modal</th>
                            <th class="px-4 py-3.5 font-semibold">Laba</th>
                            <th class="px-4 py-3.5 font-semibold">Status</th>
                            <th class="px-4 py-3.5 font-semibold">Diproses</th>
                            <th class="px-4 py-3.5 font-semibold text-right">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($sales as $sale)
                            @php
                                $sb = $statusBadge[$sale->transaction_status] ?? ['-', 'bg-slate-500/15 text-slate-300 border-slate-500/30'];
                                $pb = $payBadge[$sale->payment_status] ?? ['-', 'bg-slate-500/15 text-slate-300 border-slate-500/30'];
                            @endphp
                            <tr class="hover:bg-white/[0.03] transition align-top">
                                <td class="px-4 py-4 font-mono text-xs text-slate-400 whitespace-nowrap">{{ $sale->transaction_code }}</td>
                                <td class="px-4 py-4 min-w-[150px]">
                                    <p class="text-white font-medium">{{ $sale->account?->name ?? 'Akun dihapus' }}</p>
                                    <p class="text-[11px] text-slate-500 font-mono">{{ $sale->account_code_snapshot }}</p>
                                    @if($sale->notes)
                                        <p class="text-[11px] text-slate-500 mt-1 max-w-[180px] truncate" title="{{ $sale->notes }}">{{ $sale->notes }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 min-w-[190px]">
                                    @if($sale->hasBuyerData())
                                        <p class="text-white text-sm">{{ $sale->buyer_name }}</p>
                                        @if($sale->buyer_phone)
                                            <a href="{{ $sale->whatsappUrl() }}" target="_blank" rel="noopener" class="text-[11px] text-emerald-400 hover:underline">{{ $sale->localPhone() }}</a>
                                        @endif
                                    @elseif($sale->transaction_status === 'completed')
                                        <p class="text-xs text-amber-300">Data pembeli belum diisi</p>
                                    @else
                                        <p class="text-xs text-slate-500">-</p>
                                    @endif

                                    @if($sale->transaction_status === 'completed')
                                        @php [$wText, $wCls] = $sale->warrantyBadge(); @endphp
                                        <span {!! $sale->countdownAttrs('Garansi aktif · sisa ', 'Garansi berakhir', true) !!} class="mt-1.5 inline-flex whitespace-nowrap px-2.5 py-0.5 rounded-full border text-[11px] font-medium {{ $wCls }}">{{ $wText }}</span>
                                        @if($sale->warranty_expires_at)
                                            <p class="text-[11px] text-slate-500 mt-1 whitespace-nowrap">s/d {{ $sale->warrantyUntilLabel() }}</p>
                                        @endif
                                        <a href="{{ route('admin.sales.buyer', $sale) }}" class="block text-[11px] text-electric hover:underline mt-1">
                                            {{ $sale->hasBuyerData() ? 'Ubah data pembeli' : 'Isi data pembeli' }}
                                        </a>
                                    @endif
                                </td>
                                <td class="px-4 py-4 font-semibold text-brand-400 whitespace-nowrap">{{ $rupiah($sale->sale_price) }}</td>
                                <td class="px-4 py-4 text-slate-400 whitespace-nowrap">{{ $rupiah($sale->cost_price) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <p class="font-semibold {{ $sale->profit() < 0 ? 'text-red-400' : 'text-emerald-400' }}">{{ $sale->profit() < 0 ? '-' : '' }}{{ $rupiah(abs($sale->profit())) }}</p>
                                    @if($sale->profitMargin() !== null)
                                        <p class="text-[11px] text-slate-500">{{ str_replace('.', ',', (string) $sale->profitMargin()) }}%</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-col items-start gap-1.5">
                                        <span class="whitespace-nowrap px-2.5 py-0.5 rounded-full border text-[11px] font-medium {{ $sb[1] }}">{{ $sb[0] }}</span>
                                        <span class="whitespace-nowrap px-2.5 py-0.5 rounded-full border text-[11px] font-medium {{ $pb[1] }}">{{ $pb[0] }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-slate-300 whitespace-nowrap">{{ $sale->processor?->name ?? '-' }}</td>
                                <td class="px-4 py-4 text-right text-xs text-slate-500 whitespace-nowrap">{{ $sale->transactionTimeLabel() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Kartu (mobile) -->
        <div class="md:hidden space-y-3">
            @foreach($sales as $sale)
                @php
                    $sb = $statusBadge[$sale->transaction_status] ?? ['-', 'bg-slate-500/15 text-slate-300 border-slate-500/30'];
                    $pb = $payBadge[$sale->payment_status] ?? ['-', 'bg-slate-500/15 text-slate-300 border-slate-500/30'];
                @endphp
                <div class="glass-panel rounded-2xl p-4">
                    <div class="flex justify-between items-start gap-3 mb-2">
                        <div class="min-w-0">
                            <p class="text-white font-semibold truncate">{{ $sale->account?->name ?? 'Akun dihapus' }}</p>
                            <p class="text-[11px] text-slate-500 font-mono">{{ $sale->transaction_code }} &middot; {{ $sale->account_code_snapshot }}</p>
                        </div>
                        <p class="font-bold text-brand-400 text-sm shrink-0">{{ $rupiah($sale->sale_price) }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $sb[1] }}">{{ $sb[0] }}</span>
                        <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $pb[1] }}">{{ $pb[0] }}</span>
                        <span class="text-[11px] text-slate-500 ml-auto">{{ $sale->transactionTimeLabel() }}</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">
                        Modal {{ $rupiah($sale->cost_price) }} &middot; Laba
                        <span class="font-semibold {{ $sale->profit() < 0 ? 'text-red-400' : 'text-emerald-400' }}">{{ $sale->profit() < 0 ? '-' : '' }}{{ $rupiah(abs($sale->profit())) }}</span>
                    </p>
                    <p class="text-[11px] text-slate-500 mt-1">Diproses oleh {{ $sale->processor?->name ?? '-' }}</p>
                    @if($sale->transaction_status === 'completed')
                        @php [$wText, $wCls] = $sale->warrantyBadge(); @endphp
                        <div class="mt-2 pt-2 border-t border-white/5 flex flex-wrap items-center gap-2">
                            <span class="text-[11px] {{ $sale->hasBuyerData() ? 'text-slate-300' : 'text-amber-300' }}">
                                {{ $sale->hasBuyerData() ? 'Pembeli: ' . $sale->buyer_name : 'Data pembeli belum diisi' }}
                            </span>
                            <span {!! $sale->countdownAttrs('Garansi aktif · sisa ', 'Garansi berakhir', true) !!} class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $wCls }}">{{ $wText }}</span>
                            <a href="{{ route('admin.sales.buyer', $sale) }}" class="text-[11px] text-electric hover:underline ml-auto">
                                {{ $sale->hasBuyerData() ? 'Ubah' : 'Isi data pembeli' }}
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-8">{{ $sales->links() }}</div>
    @endif
</div>
@endsection

@push('outside-forms')
<!-- Modal konfirmasi penjualan -->
<div id="saleModal" class="hidden fixed inset-0 z-[90] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
    <div class="glass-strong w-full max-w-2xl rounded-3xl p-6 sm:p-8 border border-white/10 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h3 class="text-xl font-display font-bold text-white">Konfirmasi Penjualan</h3>
                <p class="text-sm text-slate-400 mt-1">Isi data pembeli untuk garansi {{ \App\Models\Sale::WARRANTY_DAYS }} hari. Akun tetap tampil di katalog dengan label Terjual sampai garansi berakhir, lalu otomatis disembunyikan.</p>
            </div>
            <button type="button" onclick="closeSaleModal()" class="text-slate-500 hover:text-white transition" aria-label="Tutup">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        @if($errors->any())
            <div class="mb-5 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
                @foreach($errors->all() as $err)<p>{{ $err }}</p>@endforeach
            </div>
        @endif

        <form action="{{ route('super-admin.sales.confirm') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label for="saleAccount" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Akun</label>
                <select id="saleAccount" name="account_id" required class="{{ $inputCls }}">
                    <option value="" disabled {{ old('account_id') ? '' : 'selected' }} class="bg-slate-900">Pilih akun yang terjual&hellip;</option>
                    @foreach($availableAccounts as $acc)
                        <option value="{{ $acc->id }}" data-price="{{ (int) $acc->price }}" data-cost="{{ (int) $acc->cost_price }}" class="bg-slate-900" @selected(old('account_id') == $acc->id)>
                            {{ $acc->account_code }} &mdash; {{ $acc->badgeLabel() }} &mdash; {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
                @if($availableAccounts->isEmpty())
                    <p class="text-xs text-amber-300 mt-2">Tidak ada akun berstatus tersedia.</p>
                @endif
            </div>

            @include('admin.sales._buyer-fields', ['sale' => null, 'defaultPrice' => 0, 'idPrefix' => 'sale'])

            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeSaleModal()" class="flex-1 py-3 rounded-xl border border-white/10 text-slate-300 hover:bg-white/5 transition">Batal</button>
                <button type="submit" class="flex-1 py-3 rounded-xl bg-electric hover:bg-blue-500 text-white font-bold transition">Konfirmasi</button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
    const saleModal = document.getElementById('saleModal');
    function openSaleModal()  { saleModal.classList.remove('hidden'); }
    function closeSaleModal() { saleModal.classList.add('hidden'); }

    saleModal.addEventListener('click', e => { if (e.target === saleModal) closeSaleModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSaleModal(); });

    document.getElementById('saleAccount').addEventListener('change', function () {
        const opt   = this.selectedOptions[0];
        const price = opt?.dataset.price;
        const cost  = opt?.dataset.cost;
        if (price !== undefined) {
            const p = document.getElementById('sale_price');
            p.value = price;
            p.dispatchEvent(new Event('input'));
        }
        if (cost !== undefined) {
            const c = document.getElementById('sale_cost');
            c.value = cost;
            c.dispatchEvent(new Event('input'));
        }
    });

    @if($errors->any())
        openSaleModal();
    @endif
</script>
@endpush