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
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="glass-panel rounded-2xl p-5">
            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2">Total Pendapatan</p>
            <p class="text-2xl font-display font-bold text-white">{{ $rupiah($stats['revenue']) }}</p>
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
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500 border-b border-white/5">
                            <th class="px-5 py-4 font-semibold">Kode</th>
                            <th class="px-5 py-4 font-semibold">Akun</th>
                            <th class="px-5 py-4 font-semibold">Harga</th>
                            <th class="px-5 py-4 font-semibold">Status</th>
                            <th class="px-5 py-4 font-semibold">Diproses</th>
                            <th class="px-5 py-4 font-semibold text-right">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($sales as $sale)
                            @php
                                $sb = $statusBadge[$sale->transaction_status] ?? ['-', 'bg-slate-500/15 text-slate-300 border-slate-500/30'];
                                $pb = $payBadge[$sale->payment_status] ?? ['-', 'bg-slate-500/15 text-slate-300 border-slate-500/30'];
                            @endphp
                            <tr class="hover:bg-white/[0.03] transition align-top">
                                <td class="px-5 py-4 font-mono text-xs text-slate-400">{{ $sale->transaction_code }}</td>
                                <td class="px-5 py-4">
                                    <p class="text-white font-medium">{{ $sale->account?->name ?? 'Akun dihapus' }}</p>
                                    <p class="text-[11px] text-slate-500 font-mono">{{ $sale->account_code_snapshot }}</p>
                                    @if($sale->notes)
                                        <p class="text-[11px] text-slate-500 mt-1 max-w-xs truncate" title="{{ $sale->notes }}">{{ $sale->notes }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-semibold text-brand-400 whitespace-nowrap">{{ $rupiah($sale->sale_price) }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $sb[1] }}">{{ $sb[0] }}</span>
                                        <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $pb[1] }}">{{ $pb[0] }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-300">{{ $sale->processor?->name ?? '-' }}</td>
                                <td class="px-5 py-4 text-right text-xs text-slate-500 whitespace-nowrap">{{ ($sale->sold_at ?? $sale->created_at)->format('d M Y, H:i') }}</td>
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
                        <span class="text-[11px] text-slate-500 ml-auto">{{ ($sale->sold_at ?? $sale->created_at)->format('d M Y, H:i') }}</span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-2">Diproses oleh {{ $sale->processor?->name ?? '-' }}</p>
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
    <div class="glass-strong w-full max-w-lg rounded-3xl p-6 sm:p-8 border border-white/10 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h3 class="text-xl font-display font-bold text-white">Konfirmasi Penjualan</h3>
                <p class="text-sm text-slate-400 mt-1">Akun akan ditandai terjual dan disembunyikan dari katalog.</p>
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
                        <option value="{{ $acc->id }}" data-price="{{ (int) $acc->price }}" class="bg-slate-900" @selected(old('account_id') == $acc->id)>
                            {{ $acc->account_code }} &mdash; TH {{ $acc->town_hall }} &mdash; {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
                @if($availableAccounts->isEmpty())
                    <p class="text-xs text-amber-300 mt-2">Tidak ada akun berstatus tersedia.</p>
                @endif
            </div>

            <div>
                <label for="salePrice" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Harga Jual (Rp)</label>
                <input id="salePrice" type="number" name="price" min="0" step="1" required value="{{ old('price') }}" placeholder="0" class="{{ $inputCls }}">
                <p class="text-xs text-slate-500 mt-1.5">Terisi otomatis dari harga akun, bisa diubah jika ada nego.</p>
            </div>

            <div>
                <label for="saleNotes" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Catatan <span class="normal-case text-slate-600">(opsional)</span></label>
                <textarea id="saleNotes" name="notes" rows="3" maxlength="1000" placeholder="Nama pembeli, metode bayar, dll." class="{{ $inputCls }} resize-none">{{ old('notes') }}</textarea>
            </div>

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
        const price = this.selectedOptions[0]?.dataset.price;
        if (price !== undefined) document.getElementById('salePrice').value = price;
    });

    @if($errors->any())
        openSaleModal();
    @endif
</script>
@endpush