@extends('layouts.admin')

@section('title', 'Kelola Akun')

@section('content')
<div class="max-w-6xl mx-auto pb-20">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white">Kelola Akun COC</h1>
            <p class="text-sm text-slate-400 mt-1">{{ $accounts->total() }} akun &middot; akun berstatus draft tidak tampil di katalog.</p>
        </div>
        <a href="{{ route('admin.accounts.create') }}"
           class="inline-flex items-center justify-center gap-2 bg-electric hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition shadow-lg shadow-electric/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Akun
        </a>
    </div>

    @include('admin.stale._alert')

    @if($accounts->isEmpty())
        <div class="glass-panel rounded-3xl p-16 text-center border border-white/5">
            <div class="w-16 h-16 bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            </div>
            <p class="text-slate-300 mb-1 font-medium">Belum ada akun.</p>
            <p class="text-sm text-slate-500">Klik "Tambah Akun" untuk memposting akun pertama.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($accounts as $account)
                @php
                    $published = $account->publication_status === 'published';
                    $saleBadge = [
                        'available' => ['Tersedia', 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'],
                        'sold'      => ['Terjual', 'bg-red-500/15 text-red-300 border-red-500/30'],
                        'archived'  => ['Arsip', 'bg-slate-500/15 text-slate-300 border-slate-500/30'],
                    ][$account->sale_status] ?? ['-', 'bg-slate-500/15 text-slate-300 border-slate-500/30'];
                    
                    // Perbaikan path gambar
                    $imageUrl = null;
                    if ($account->primaryImage && !empty($account->primaryImage->image_path)) {
                        $imageUrl = upload_url($account->primaryImage->image_path);
                    }
                @endphp
                <div class="glass-panel rounded-2xl p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center gap-4 border border-white/5 hover:border-electric/30 transition-colors">
                    <div class="flex items-center gap-4 min-w-0 flex-1">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 shrink-0 rounded-xl overflow-hidden bg-midnight border border-white/10 relative group">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" 
                                     alt="{{ $account->name }}" 
                                     loading="lazy" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                     class="w-full h-full object-cover">
                                <!-- Fallback Icon -->
                                <div class="hidden w-full h-full absolute inset-0 items-center justify-center text-slate-700 bg-slate-900">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-900">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-[11px] font-mono text-slate-500">{{ $account->account_code }}</span>
                                <span class="px-2 py-0.5 rounded-md bg-electric/15 text-electric text-[11px] font-bold">{{ $account->badgeLabel() }}</span>
                            </div>
                            <h3 class="font-semibold text-white truncate text-lg">{{ $account->name }}</h3>
                            <p class="text-brand-400 font-bold text-sm mt-0.5">Rp {{ number_format($account->price, 0, ',', '.') }}</p>
                            @php
                                // Akun terjual: pakai angka transaksi (harga nego & modal saat itu); selain itu estimasi dari listing.
                                $doneSale   = $account->sale_status === 'sold' ? $account->latestCompletedSale : null;
                                // Modal transaksi bisa 0 pada data lama; kalau begitu pakai modal yang tersimpan di akun.
                                $costShown  = $doneSale
                                    ? ((float) $doneSale->cost_price > 0 ? (float) $doneSale->cost_price : (float) $account->cost_price)
                                    : (float) $account->cost_price;
                                $profitShown = $doneSale ? ((float) $doneSale->sale_price - $costShown) : $account->estimatedProfit();
                            @endphp
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Modal Rp {{ number_format($costShown, 0, ',', '.') }}
                                &middot; {{ $doneSale ? 'Laba' : 'Est. laba' }}
                                <span class="font-semibold {{ $profitShown < 0 ? 'text-red-400' : ($profitShown > 0 ? 'text-emerald-400' : 'text-slate-400') }}">{{ $profitShown < 0 ? '-' : '' }}Rp {{ number_format(abs($profitShown), 0, ',', '.') }}</span>
                            </p>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $published ? 'bg-electric/15 text-blue-300 border-electric/30' : 'bg-amber-500/15 text-amber-300 border-amber-500/30' }}">
                                    {{ $published ? 'Terbit' : 'Draft' }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $saleBadge[1] }}">{{ $saleBadge[0] }}</span>
                                @if($account->sale_status === 'sold' && $account->latestCompletedSale)
                                    @php [$wText, $wCls] = $account->latestCompletedSale->warrantyBadge(); @endphp
                                    <span {!! $account->latestCompletedSale->countdownAttrs('Garansi aktif · sisa ', 'Garansi berakhir', true) !!} class="px-2 py-0.5 rounded-full border text-[11px] font-medium {{ $wCls }}">{{ $wText }}</span>
                                    @if($account->latestCompletedSale->hasBuyerData())
                                        <span class="text-[11px] text-slate-500 self-center">Pembeli: {{ $account->latestCompletedSale->buyer_name }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:flex gap-2 sm:shrink-0">
                        <form action="{{ route('admin.accounts.status', $account) }}" method="POST">
                            @csrf
                            <input type="hidden" name="publication_status" value="{{ $published ? 'draft' : 'published' }}">
                            <button type="submit" class="w-full px-3 py-2 rounded-lg border border-white/10 text-xs sm:text-sm text-slate-300 hover:bg-white/5 transition whitespace-nowrap">
                                {{ $published ? 'Sembunyikan' : 'Terbitkan' }}
                            </button>
                        </form>
                        @if($account->sale_status === 'sold')
                            @if($account->latestCompletedSale)
                                <a href="{{ route('admin.sales.buyer', $account->latestCompletedSale) }}"
                                   class="text-center px-3 py-2 rounded-lg border border-emerald-500/30 text-xs sm:text-sm text-emerald-300 hover:bg-emerald-500/10 transition whitespace-nowrap">Data Pembeli</a>
                                @if($account->latestCompletedSale->warranty_expires_at)
                                    @php
                                        $warrantySale = $account->latestCompletedSale;
                                        $warrantyInfo = "Halo kak, terima kasih sudah belanja di EHANZSTORE.ID 🙏\n"
                                            . "Kode transaksi: {$warrantySale->transaction_code}\n"
                                            . "Cek sisa garansi {$warrantySale->warrantyDays()} hari kapan saja di:\n"
                                            . route('warranty.check', ['kode' => $warrantySale->transaction_code]);
                                    @endphp
                                    <button type="button" data-copy="{{ $warrantyInfo }}" data-done="Info tersalin!"
                                            onclick="copyReviewLink(this)"
                                            class="text-center px-3 py-2 rounded-lg border border-sky-500/30 text-xs sm:text-sm text-sky-300 hover:bg-sky-500/10 transition whitespace-nowrap">
                                        Salin Info Garansi
                                    </button>
                                @endif
                                @if($account->latestCompletedSale->reviewUrl())
                                    <button type="button" data-copy="{{ $account->latestCompletedSale->reviewUrl() }}"
                                            onclick="copyReviewLink(this)"
                                            class="text-center px-3 py-2 rounded-lg border border-amber-500/30 text-xs sm:text-sm text-amber-300 hover:bg-amber-500/10 transition whitespace-nowrap">
                                        {{ $account->latestCompletedSale->review ? 'Ulasan: sudah isi' : 'Salin Link Ulasan' }}
                                    </button>
                                @endif
                            @endif
                        @elseif($account->sale_status === 'available')
                            <a href="{{ route('admin.accounts.sell', $account) }}"
                               class="text-center px-3 py-2 rounded-lg border border-emerald-500/30 text-xs sm:text-sm text-emerald-300 hover:bg-emerald-500/10 transition whitespace-nowrap">Tandai Terjual</a>
                        @endif
                        <a href="{{ route('admin.accounts.edit', $account) }}"
                           class="text-center px-3 py-2 rounded-lg border border-white/10 text-xs sm:text-sm text-slate-300 hover:bg-white/5 transition">Edit</a>
                        <form action="{{ route('admin.accounts.destroy', $account) }}" method="POST"
                              onsubmit="return confirm('Hapus akun {{ $account->account_code }}? Foto ikut terhapus.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-full px-3 py-2 rounded-lg border border-red-500/20 text-xs sm:text-sm text-red-400 hover:bg-red-500/10 transition">Hapus</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">{{ $accounts->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function copyReviewLink(btn) {
        const url = btn.dataset.copy;
        const done = () => {
            const old = btn.textContent;
            btn.textContent = btn.dataset.done || 'Link tersalin!';
            setTimeout(() => btn.textContent = old, 1800);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(done);
        } else {
            const t = document.createElement('textarea');
            t.value = url; document.body.appendChild(t); t.select();
            document.execCommand('copy'); t.remove(); done();
        }
    }
</script>
@endpush