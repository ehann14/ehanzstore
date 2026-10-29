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
        @php
            // Gaya dasar semua tombol aksi pada kartu (warna & border ditambahkan per tombol).
            $btn = 'inline-flex items-center justify-center w-full sm:w-auto px-3 py-2 rounded-lg border text-xs sm:text-sm font-medium transition whitespace-nowrap';

            // Booking (DP) yang sedang menahan akun-akun di halaman ini
            $holds = \App\Models\Booking::holdingMap($accounts->pluck('id')->all());
        @endphp

        <div class="space-y-4">
            @foreach($accounts as $account)
                @php
                    $published = $account->publication_status === 'published';
                    $saleBadge = [
                        'available' => ['Tersedia', 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'],
                        'sold'      => ['Terjual', 'bg-red-500/15 text-red-300 border-red-500/30'],
                        'archived'  => ['Arsip', 'bg-slate-500/15 text-slate-300 border-slate-500/30'],
                    ][$account->sale_status] ?? ['-', 'bg-slate-500/15 text-slate-300 border-slate-500/30'];

                    $imageUrl = null;
                    if ($account->primaryImage && !empty($account->primaryImage->image_path)) {
                        $imageUrl = upload_url($account->primaryImage->image_path);
                    }

                    // Akun terjual: pakai angka transaksi (harga nego & modal saat itu); selain itu estimasi dari listing.
                    $doneSale   = $account->sale_status === 'sold' ? $account->latestCompletedSale : null;
                    // Modal transaksi bisa 0 pada data lama; kalau begitu pakai modal yang tersimpan di akun.
                    $costShown  = $doneSale
                        ? ((float) $doneSale->cost_price > 0 ? (float) $doneSale->cost_price : (float) $account->cost_price)
                        : (float) $account->cost_price;
                    $profitShown = $doneSale ? ((float) $doneSale->sale_price - $costShown) : $account->estimatedProfit();
                    $priceShown  = $doneSale ? (float) $doneSale->sale_price : (float) $account->price;

                    // Booking DP yang menahan akun ini (hanya untuk akun yang masih tersedia)
                    $hold = $account->sale_status === 'available' ? ($holds[$account->id] ?? null) : null;
                @endphp

                <div class="glass-panel rounded-2xl border border-white/5 hover:border-electric/30 transition-colors overflow-hidden">

                    {{-- ===== Info akun ===== --}}
                    <div class="p-3 sm:p-4 flex items-start gap-3 sm:gap-4">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 shrink-0 rounded-xl overflow-hidden bg-midnight border border-white/10 relative">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}"
                                     alt="{{ $account->name }}"
                                     loading="lazy"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                     class="w-full h-full object-cover">
                                <div class="hidden w-full h-full absolute inset-0 items-center justify-center text-slate-700 bg-slate-900">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-900">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <span class="text-[11px] font-mono text-slate-500">{{ $account->account_code }}</span>
                                        <span class="px-2 py-0.5 rounded-md bg-electric/15 text-electric text-[11px] font-bold">{{ $account->badgeLabel() }}</span>
                                    </div>
                                    <h3 class="font-semibold text-white truncate text-base sm:text-lg" title="{{ $account->name }}">{{ $account->name }}</h3>
                                </div>

                                <div class="text-right shrink-0">
                                    <p class="text-blue-400 font-bold text-base sm:text-lg whitespace-nowrap">Rp {{ number_format($priceShown, 0, ',', '.') }}</p>
                                    @if($doneSale)
                                        <p class="text-[10px] uppercase tracking-wider text-slate-500">Harga jual</p>
                                    @endif
                                </div>
                            </div>

                            <p class="text-[11px] sm:text-xs text-slate-500 mt-1">
                                Modal Rp {{ number_format($costShown, 0, ',', '.') }}
                                &middot; {{ $doneSale ? 'Laba' : 'Est. laba' }}
                                <span class="font-semibold whitespace-nowrap {{ $profitShown < 0 ? 'text-red-400' : ($profitShown > 0 ? 'text-emerald-400' : 'text-slate-400') }}">{{ $profitShown < 0 ? '-' : '' }}Rp {{ number_format(abs($profitShown), 0, ',', '.') }}</span>
                            </p>

                            <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium whitespace-nowrap {{ $published ? 'bg-electric/15 text-blue-300 border-electric/30' : 'bg-amber-500/15 text-amber-300 border-amber-500/30' }}">
                                    {{ $published ? 'Terbit' : 'Draft' }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium whitespace-nowrap {{ $saleBadge[1] }}">{{ $saleBadge[0] }}</span>

                                @if($hold)
                                    <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium whitespace-nowrap bg-amber-500/15 text-amber-300 border-amber-500/30">
                                        {{ $hold->status === 'pending' ? 'Booking · menunggu DP' : 'Di-booking (DP masuk)' }}
                                    </span>
                                    <span {!! $hold->countdownAttrs($hold->status === 'pending' ? 'Batas bayar DP · sisa ' : 'Harus lunas · sisa ', 'Waktu habis') !!}
                                          class="px-2 py-0.5 rounded-full border text-[11px] font-medium whitespace-nowrap bg-white/5 text-slate-300 border-white/10">
                                        Batas {{ $hold->deadlineLabel() }}
                                    </span>
                                @endif

                                @if($doneSale)
                                    @php [$wText, $wCls] = $doneSale->warrantyBadge(); @endphp
                                    <span {!! $doneSale->countdownAttrs('Garansi aktif · sisa ', 'Garansi berakhir', true) !!}
                                          class="px-2 py-0.5 rounded-full border text-[11px] font-medium whitespace-nowrap {{ $wCls }}">{{ $wText }}</span>
                                @endif
                            </div>

                            @if($hold)
                                <p class="text-[11px] sm:text-xs text-slate-500 mt-2 truncate">
                                    Booking <span class="font-mono">{{ $hold->booking_code }}</span>
                                    &middot; Pembeli: <span class="text-slate-300">{{ $hold->buyer_name }}</span>
                                    @if($hold->status === 'active')
                                        &middot; DP Rp {{ number_format((float) $hold->dp_amount, 0, ',', '.') }}
                                        &middot; Sisa <span class="text-amber-300 font-semibold">Rp {{ number_format($hold->remaining(), 0, ',', '.') }}</span>
                                    @endif
                                </p>
                            @endif

                            @if($doneSale && $doneSale->hasBuyerData())
                                <p class="text-[11px] sm:text-xs text-slate-500 mt-2 truncate">
                                    Pembeli: <span class="text-slate-300">{{ $doneSale->buyer_name }}</span>
                                    @if($doneSale->transaction_code)
                                        &middot; <span class="font-mono">{{ $doneSale->transaction_code }}</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- ===== Aksi ===== --}}
                    <div class="px-3 sm:px-4 py-3 border-t border-white/5 bg-black/20 flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2">

                        {{-- Aksi transaksi (kiri) --}}
                        @if($account->sale_status === 'sold' && $doneSale)
                            <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                                <a href="{{ route('admin.sales.buyer', $doneSale) }}"
                                   class="{{ $btn }} border-emerald-500/30 text-emerald-300 hover:bg-emerald-500/10">Data Pembeli</a>

                                @if($doneSale->warranty_expires_at)
                                    @php
                                        $warrantyInfo = "Halo kak, terima kasih sudah belanja di EHANZSTORE.ID 🙏\n"
                                            . "Kode transaksi: {$doneSale->transaction_code}\n\n"
                                            . ($doneSale->invoiceUrl()
                                                ? "🧾 Invoice / bukti transaksi:\n" . $doneSale->invoiceUrl() . "\n\n"
                                                : '')
                                            . "🛡 Cek sisa garansi {$doneSale->warrantyDays()} hari kapan saja di:\n"
                                            . route('warranty.check', ['kode' => $doneSale->transaction_code]);
                                    @endphp
                                    <button type="button" data-copy="{{ $warrantyInfo }}" data-done="Info tersalin!"
                                            onclick="copyReviewLink(this)"
                                            class="{{ $btn }} border-sky-500/30 text-sky-300 hover:bg-sky-500/10">
                                        Salin Info Garansi
                                    </button>
                                @endif

                                @if($doneSale->invoiceUrl())
                                    <a href="{{ $doneSale->invoiceUrl() }}" target="_blank" rel="noopener"
                                       class="{{ $btn }} border-violet-500/30 text-violet-300 hover:bg-violet-500/10">Lihat Invoice</a>
                                @endif

                                @if($doneSale->reviewUrl())
                                    <button type="button" data-copy="{{ $doneSale->reviewUrl() }}"
                                            onclick="copyReviewLink(this)"
                                            class="{{ $btn }} border-amber-500/30 text-amber-300 hover:bg-amber-500/10">
                                        {{ $doneSale->review ? 'Ulasan: sudah isi' : 'Salin Link Ulasan' }}
                                    </button>
                                @endif
                            </div>
                        @elseif($account->sale_status === 'available' && $hold)
                            <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                                @if($hold->status === 'active')
                                    {{-- DP sudah masuk: pelunasan = tandai terjual (booking otomatis selesai, DP dihitung sebagai bagian bayar) --}}
                                    <a href="{{ route('admin.accounts.sell', ['account' => $account->id, 'booking' => $hold->id]) }}"
                                       class="{{ $btn }} border-emerald-500/30 text-emerald-300 hover:bg-emerald-500/10">Lunas &rarr; Terjual</a>
                                @endif
                                <a href="{{ route('admin.bookings.index', ['status' => 'open', 'q' => $hold->booking_code]) }}"
                                   class="{{ $btn }} border-amber-500/30 text-amber-300 hover:bg-amber-500/10">Kelola Booking</a>
                            </div>
                        @elseif($account->sale_status === 'available')
                            <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                                <a href="{{ route('admin.accounts.sell', $account) }}"
                                   class="{{ $btn }} border-emerald-500/30 text-emerald-300 hover:bg-emerald-500/10">Terjual (Lunas)</a>
                                <a href="{{ route('admin.accounts.booking', $account) }}"
                                   class="{{ $btn }} border-amber-500/30 text-amber-300 hover:bg-amber-500/10">Booking / DP</a>
                            </div>
                        @endif

                        {{-- Pendorong: aksi umum rata kanan di layar lebar --}}
                        <div class="hidden sm:block sm:flex-1"></div>

                        {{-- Aksi umum (kanan) --}}
                        <div class="grid grid-cols-3 sm:flex gap-2">
                            <form action="{{ route('admin.accounts.status', $account) }}" method="POST">
                                @csrf
                                <input type="hidden" name="publication_status" value="{{ $published ? 'draft' : 'published' }}">
                                <button type="submit" class="{{ $btn }} border-white/10 text-slate-300 hover:bg-white/5">
                                    {{ $published ? 'Sembunyikan' : 'Terbitkan' }}
                                </button>
                            </form>

                            <a href="{{ route('admin.accounts.edit', $account) }}"
                               class="{{ $btn }} border-white/10 text-slate-300 hover:bg-white/5">Edit</a>

                            <form action="{{ route('admin.accounts.destroy', $account) }}" method="POST"
                                  onsubmit="return confirm('Hapus akun {{ $account->account_code }}? Foto ikut terhapus.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="{{ $btn }} border-red-500/20 text-red-400 hover:bg-red-500/10">Hapus</button>
                            </form>
                        </div>
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