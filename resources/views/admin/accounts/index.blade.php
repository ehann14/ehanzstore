@extends('layouts.admin')

@section('title', 'Kelola Akun')

@section('content')
<div class="max-w-6xl mx-auto pb-20">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Kelola Akun COC</h1>
            <p class="text-sm text-slate-400 mt-1">Akun berstatus draft tidak tampil di katalog.</p>
        </div>
        <a href="{{ route('admin.accounts.create') }}"
           class="inline-flex items-center justify-center gap-2 bg-electric hover:bg-blue-500 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition shadow-lg shadow-electric/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Akun
        </a>
    </div>

    {{-- ===== Dua bagian: Akun Ready & Akun Terjual ===== --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <a href="{{ route('admin.accounts.index', ['tab' => 'ready']) }}"
           class="rounded-2xl border p-4 transition {{ $tab === 'ready' ? 'bg-emerald-500/10 border-emerald-500/40' : 'glass-panel border-white/5 hover:border-emerald-500/30' }}">
            <p class="text-[11px] uppercase tracking-wider {{ $tab === 'ready' ? 'text-emerald-300' : 'text-slate-500' }}">Akun Ready</p>
            <p class="text-2xl font-bold text-white mt-1">{{ $readyCount }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5 truncate">Nilai tersedia Rp {{ number_format($readyValue, 0, ',', '.') }}</p>
        </a>
        <a href="{{ route('admin.accounts.index', ['tab' => 'sold']) }}"
           class="rounded-2xl border p-4 transition {{ $tab === 'sold' ? 'bg-red-500/10 border-red-500/40' : 'glass-panel border-white/5 hover:border-red-500/30' }}">
            <p class="text-[11px] uppercase tracking-wider {{ $tab === 'sold' ? 'text-red-300' : 'text-slate-500' }}">Akun Terjual</p>
            <p class="text-2xl font-bold text-white mt-1">{{ $soldCount }}</p>
            <p class="text-[11px] text-slate-500 mt-0.5 truncate">Sudah laku &middot; {{ min($soldCount, (int) config('store.sold_showcase_limit', 15)) }} terbaru tampil di katalog</p>
        </a>
    </div>

    <form method="GET" action="{{ route('admin.accounts.index') }}" class="flex gap-2 mb-6">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari kode atau nama akun..."
               class="flex-1 min-w-0 bg-midnight border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-electric">
        <button type="submit" class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm text-slate-200 transition">Cari</button>
        @if($search !== '')
            <a href="{{ route('admin.accounts.index', ['tab' => $tab]) }}" class="px-4 py-2.5 rounded-xl border border-white/10 text-sm text-slate-400 hover:text-white transition">Reset</a>
        @endif
    </form>

    @include('admin.stale._alert')

    @if($accounts->isEmpty())
        <div class="glass-panel rounded-3xl p-16 text-center border border-white/5">
            <div class="w-16 h-16 bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            </div>
            @if($search !== '')
                <p class="text-slate-300 mb-1 font-medium">Tidak ada hasil untuk "{{ $search }}".</p>
                <p class="text-sm text-slate-500">Coba kata kunci lain.</p>
            @elseif($tab === 'sold')
                <p class="text-slate-300 mb-1 font-medium">Belum ada akun yang terjual.</p>
                <p class="text-sm text-slate-500">Akun yang ditandai terjual akan muncul di sini.</p>
            @else
                <p class="text-slate-300 mb-1 font-medium">Belum ada akun ready.</p>
                <p class="text-sm text-slate-500">Klik "Tambah Akun" untuk memposting akun pertama.</p>
            @endif
        </div>
    @else
        @php
            // Gaya dasar semua tombol aksi pada kartu (warna & border ditambahkan per tombol).
            $btn = 'inline-flex items-center justify-center w-full sm:w-auto px-3 py-2 rounded-lg border text-xs sm:text-sm font-medium transition whitespace-nowrap';

            // Booking (DP) yang sedang menahan akun-akun di halaman ini
            $holds = \App\Models\Booking::holdingMap($accounts->pluck('id')->all());

            // Etalase akun terjual: yang tampil di katalog publik (maks. N terbaru)
            $showcaseIds   = \App\Models\CocAccount::soldShowcaseIds();
            $showcaseLimit = (int) config('store.sold_showcase_limit', 15);
        @endphp

        @if($tab === 'ready')
            {{-- Toolbar centang: pilih semua untuk diskon massal (Last Clash Sale, dll) --}}
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 mb-3 px-1">
                <label class="inline-flex items-center gap-2 text-sm text-slate-300 cursor-pointer select-none">
                    <input type="checkbox" id="bulk-check-all" class="w-5 h-5 rounded border-white/20 bg-brand-950/50 text-electric focus:ring-electric">
                    Pilih semua di halaman ini
                </label>
                @if($accounts->total() > $accounts->count())
                    <button type="button" id="bulk-all-pages" class="hidden text-xs font-semibold text-electric hover:underline">
                        Pilih semua {{ $accounts->total() }} akun{{ $search !== '' ? ' hasil pencarian' : '' }} (semua halaman)
                    </button>
                @endif
                <span class="text-[11px] text-slate-500">Centang akun untuk mengatur diskon sekaligus.</span>
            </div>
        @endif

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
                        @if($tab === 'ready' && $account->sale_status === 'available')
                            @php
                                $bulkBase = $account->hasDiscount() ? (float) $account->original_price : (float) $account->price;
                            @endphp
                            <label class="shrink-0 pt-1 cursor-pointer" title="Pilih untuk diskon massal">
                                <input type="checkbox" name="ids[]" value="{{ $account->id }}" form="bulk-form" data-bulk-check
                                       data-code="{{ $account->account_code }}" data-base="{{ (int) $bulkBase }}"
                                       data-cost="{{ $account->isConsignment() ? 0 : (int) $account->cost_price }}"
                                       class="w-5 h-5 rounded border-white/20 bg-brand-950/50 text-electric focus:ring-electric">
                            </label>
                        @endif
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
                                        @if($account->isConsignment())
                                            <span class="px-2 py-0.5 rounded-md bg-amber-500/15 text-amber-300 text-[11px] font-bold border border-amber-500/30" title="Titip jual dari {{ $account->consignor_name }}">TITIP</span>
                                        @endif
                                    </div>
                                    <h3 class="font-semibold text-white truncate text-base sm:text-lg" title="{{ $account->name }}">{{ $account->name }}</h3>
                                </div>

                                <div class="text-right shrink-0">
                                    <p class="text-blue-400 font-bold text-base sm:text-lg whitespace-nowrap">Rp {{ number_format($priceShown, 0, ',', '.') }}</p>
                                    @if($doneSale)
                                        <p class="text-[10px] uppercase tracking-wider text-slate-500">Harga jual</p>
                                    @elseif($account->hasDiscount())
                                        <p class="text-[11px] text-slate-500 whitespace-nowrap">
                                            <span class="line-through">Rp {{ number_format((float) $account->original_price, 0, ',', '.') }}</span>
                                            <span class="ml-1 px-1.5 py-0.5 rounded bg-orange-500/15 text-orange-300 font-bold">-{{ $account->discountPercent() }}%</span>
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <p class="text-[11px] sm:text-xs text-slate-500 mt-1">
                                @if($account->isConsignment())
                                    Setor ke {{ $account->consignor_name }} Rp {{ number_format($costShown, 0, ',', '.') }}
                                    &middot; {{ $doneSale ? 'Komisi' : 'Est. komisi' }}
                                @else
                                    Modal Rp {{ number_format($costShown, 0, ',', '.') }}
                                    &middot; {{ $doneSale ? 'Laba' : 'Est. laba' }}
                                @endif
                                <span class="font-semibold whitespace-nowrap {{ $profitShown < 0 ? 'text-red-400' : ($profitShown > 0 ? 'text-emerald-400' : 'text-slate-400') }}">{{ $profitShown < 0 ? '-' : '' }}Rp {{ number_format(abs($profitShown), 0, ',', '.') }}</span>
                            </p>

                            <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium whitespace-nowrap {{ $published ? 'bg-electric/15 text-blue-300 border-electric/30' : 'bg-amber-500/15 text-amber-300 border-amber-500/30' }}">
                                    {{ $published ? 'Terbit' : 'Draft' }}
                                </span>
                                @if($account->isScheduled())
                                    <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium whitespace-nowrap bg-violet-500/15 text-violet-300 border-violet-500/30"
                                          title="Terbit otomatis sesuai jadwal">
                                        Tayang {{ $account->publishAtLabel() }}
                                    </span>
                                @endif
                                <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium whitespace-nowrap {{ $saleBadge[1] }}">{{ $saleBadge[0] }}</span>

                                @if($account->sale_status === 'sold' && $published)
                                    @if(in_array((int) $account->id, $showcaseIds, true))
                                        <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium whitespace-nowrap bg-emerald-500/15 text-emerald-300 border-emerald-500/30"
                                              title="Terlihat pembeli di katalog & beranda">Tampil di katalog</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full border text-[11px] font-medium whitespace-nowrap bg-slate-500/15 text-slate-300 border-slate-500/30"
                                              title="Melebihi batas {{ $showcaseLimit }} akun terjual terbaru, jadi disembunyikan otomatis">Tersembunyi otomatis</span>
                                    @endif
                                @endif

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

@if($tab === 'ready' && $accounts->isNotEmpty())
    {{-- Bar aksi: muncul saat ada akun yang dicentang --}}
    <div id="bulk-bar" class="hidden fixed bottom-20 lg:bottom-6 inset-x-3 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 z-40">
        <div class="flex flex-wrap items-center justify-center gap-2 rounded-2xl border border-electric/40 bg-midnight/95 backdrop-blur px-4 py-3 shadow-2xl shadow-black/50">
            <span class="text-sm font-semibold text-white"><span id="bulk-count">0</span> akun dipilih</span>
            <button type="button" id="bulk-open" class="px-4 py-2 rounded-xl bg-electric hover:bg-blue-500 text-white text-sm font-semibold transition">Atur Diskon</button>
            <button type="submit" form="bulk-form" name="action" value="end" id="bulk-end"
                    class="px-3 py-2 rounded-xl border border-white/10 text-slate-300 hover:bg-white/5 text-sm font-medium transition">Akhiri Diskon</button>
            <button type="button" id="bulk-clear" class="px-3 py-2 rounded-xl text-slate-500 hover:text-white text-sm transition">Batal</button>
        </div>
    </div>

    {{-- Modal pengaturan diskon massal. Checkbox di kartu terhubung ke form ini lewat atribut form="bulk-form". --}}
    <div id="bulk-modal" class="hidden fixed inset-0 z-50 items-end sm:items-center justify-center bg-black/70 p-3">
        <form id="bulk-form" method="POST" action="{{ route('admin.accounts.bulk-price') }}"
              class="w-full max-w-md max-h-[92vh] overflow-y-auto rounded-2xl border border-white/10 bg-midnight p-5 space-y-4">
            @csrf
            <input type="hidden" name="select_all" id="bulk-select-all" value="0">
            <input type="hidden" name="q" value="{{ $search }}">

            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-white">Diskon Massal</h2>
                    <p class="text-xs text-slate-400 mt-0.5"><span id="bulk-count-2">0</span> akun akan diubah sekaligus.</p>
                </div>
                <button type="button" id="bulk-close" class="text-slate-500 hover:text-white text-2xl leading-none">&times;</button>
            </div>

            <div>
                <p class="block text-xs font-semibold text-slate-400 mb-2">Jenis potongan</p>
                <div class="grid grid-cols-2 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="mode" value="percent" class="peer sr-only" checked>
                        <span class="block text-center rounded-xl border border-white/10 py-2.5 text-sm font-semibold text-slate-300 peer-checked:border-electric peer-checked:bg-electric/15 peer-checked:text-white transition">Persen (%)</span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="mode" value="fixed" class="peer sr-only">
                        <span class="block text-center rounded-xl border border-white/10 py-2.5 text-sm font-semibold text-slate-300 peer-checked:border-electric peer-checked:bg-electric/15 peer-checked:text-white transition">Rupiah (Rp)</span>
                    </label>
                </div>
            </div>

            <div>
                <label for="bulk-value" class="block text-xs font-semibold text-slate-400 mb-2">Besar potongan <span id="bulk-unit" class="text-slate-500 font-normal">(%)</span></label>
                <input type="number" id="bulk-value" name="value" min="0" step="any" inputmode="decimal" placeholder="Contoh: 5"
                       class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition">
                <div id="bulk-chips" class="flex flex-wrap gap-2 mt-2"></div>
                <p id="bulk-preview" class="text-[11px] text-slate-400 mt-2 min-h-[1rem]"></p>
            </div>

            <label class="flex items-start gap-3 cursor-pointer select-none">
                <input type="checkbox" name="is_hot_deal" value="1" checked
                       class="mt-0.5 w-4 h-4 rounded border-white/20 bg-brand-950/50 text-orange-500 focus:ring-orange-500">
                <span>
                    <span class="block text-sm font-semibold text-white">Tandai sebagai Hot Deal</span>
                    <span class="block text-[11px] text-slate-500 mt-0.5">Tampil di bagian Hot Deal beranda selama promo berjalan.</span>
                </span>
            </label>

            <div>
                <label for="bulk-ends" class="block text-xs font-semibold text-slate-400 mb-2">Promo berakhir pada (WIB) <span class="text-slate-600 font-normal">(opsional)</span></label>
                <input type="datetime-local" id="bulk-ends" name="hot_deal_ends_at"
                       class="w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-brand-500 outline-none transition [color-scheme:dark]">
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach([1 => '+1 hari', 3 => '+3 hari', 7 => '+7 hari'] as $d => $lbl)
                        <button type="button" data-bulk-end="{{ $d }}" class="px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-[11px] font-semibold text-slate-300 transition">{{ $lbl }}</button>
                    @endforeach
                    <button type="button" data-bulk-end="0" class="px-3 py-1.5 rounded-lg border border-white/10 text-[11px] font-semibold text-slate-500 hover:text-white hover:bg-white/5 transition">Kosongkan</button>
                </div>
                <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">Setelah waktu ini lewat, harga otomatis kembali ke harga normal. Kosongkan jika tanpa batas waktu.</p>
            </div>

            <div class="rounded-xl border border-white/10 bg-white/[0.02] p-3 text-[11px] text-slate-400 leading-relaxed">
                Harga sebelum diskon tersimpan sebagai harga coret. Akun yang sudah diskon dihitung dari harga normalnya (tidak ditumpuk).
                Akun yang jadi di bawah modal atau sedang di-booking akan dilewati.
            </div>

            <div class="flex gap-2 pt-1">
                <button type="button" id="bulk-cancel" class="flex-1 px-4 py-3 rounded-xl border border-white/10 text-slate-300 hover:bg-white/5 text-sm font-medium transition">Batal</button>
                <button type="submit" name="action" value="apply" id="bulk-submit"
                        class="flex-1 px-4 py-3 rounded-xl bg-electric hover:bg-blue-500 text-white text-sm font-semibold transition">Terapkan Diskon</button>
            </div>
        </form>
    </div>
@endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('bulk-form');
        if (!form) return;

        const checks   = () => Array.from(document.querySelectorAll('[data-bulk-check]'));
        const checked  = () => checks().filter(c => c.checked);
        const bar      = document.getElementById('bulk-bar');
        const modal    = document.getElementById('bulk-modal');
        const allBox   = document.getElementById('bulk-check-all');
        const allPages = document.getElementById('bulk-all-pages');
        const flag     = document.getElementById('bulk-select-all');
        const valueIn  = document.getElementById('bulk-value');
        const unit     = document.getElementById('bulk-unit');
        const chips    = document.getElementById('bulk-chips');
        const preview  = document.getElementById('bulk-preview');
        const ends     = document.getElementById('bulk-ends');
        const rp       = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
        const total    = {{ (int) $accounts->total() }};
        const mode     = () => form.querySelector('input[name="mode"]:checked').value;

        const PRESETS = { percent: [5, 10, 15, 20, 30], fixed: [5000, 10000, 25000, 50000, 100000] };

        function count() { return flag.value === '1' ? total : checked().length; }

        function refresh() {
            const n = count();
            document.getElementById('bulk-count').textContent = n;
            document.getElementById('bulk-count-2').textContent = n;
            bar.classList.toggle('hidden', n === 0);
            const all = checks();
            allBox.checked = all.length > 0 && all.every(c => c.checked);
            if (allPages) allPages.classList.toggle('hidden', !(allBox.checked && flag.value !== '1'));
            renderPreview();
        }

        function newPrice(base, v) {
            return mode() === 'percent' ? Math.round(base * (1 - v / 100)) : base - v;
        }

        function renderPreview() {
            const v = parseFloat(valueIn.value);
            const sel = checked();
            preview.className = 'text-[11px] text-slate-400 mt-2 min-h-[1rem]';
            if (!sel.length || isNaN(v) || v <= 0) { preview.textContent = ''; return; }
            if (mode() === 'percent' && v > 90) { preview.textContent = 'Diskon persen maksimal 90%.'; preview.className = 'text-[11px] text-amber-400 mt-2'; return; }
            const first = sel[0], base = +first.dataset.base, np = newPrice(base, v);
            let below = 0;
            sel.forEach(c => { const p = newPrice(+c.dataset.base, v); if (p <= 0 || (+c.dataset.cost > 0 && p < +c.dataset.cost)) below++; });
            let t = 'Contoh ' + first.dataset.code + ': ' + rp(base) + ' → ' + rp(Math.max(0, np));
            if (below) t += ' · ' + below + ' akun terpilih di halaman ini akan dilewati (di bawah modal)';
            preview.textContent = t;
            if (below) preview.className = 'text-[11px] text-amber-400 mt-2';
        }

        function renderChips() {
            const isPct = mode() === 'percent';
            unit.textContent = isPct ? '(%)' : '(Rp)';
            valueIn.placeholder = isPct ? 'Contoh: 5' : 'Contoh: 5000';
            chips.innerHTML = '';
            PRESETS[mode()].forEach(n => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'px-3 py-1.5 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 text-[11px] font-semibold text-slate-300 transition';
                b.textContent = isPct ? n + '%' : rp(n);
                b.addEventListener('click', () => { valueIn.value = n; renderPreview(); });
                chips.appendChild(b);
            });
            renderPreview();
        }

        const open  = () => { modal.classList.remove('hidden'); modal.classList.add('flex'); renderChips(); setTimeout(() => valueIn.focus(), 50); };
        const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); };

        document.addEventListener('change', e => {
            if (e.target.matches('[data-bulk-check]')) { flag.value = '0'; refresh(); }
        });
        allBox.addEventListener('change', () => { flag.value = '0'; checks().forEach(c => c.checked = allBox.checked); refresh(); });
        if (allPages) allPages.addEventListener('click', () => { flag.value = '1'; allPages.classList.add('hidden'); refresh(); });
        document.getElementById('bulk-open').addEventListener('click', open);
        ['bulk-close', 'bulk-cancel'].forEach(id => document.getElementById(id).addEventListener('click', close));
        modal.addEventListener('click', e => { if (e.target === modal) close(); });
        document.getElementById('bulk-clear').addEventListener('click', () => { flag.value = '0'; checks().forEach(c => c.checked = false); refresh(); });
        form.querySelectorAll('input[name="mode"]').forEach(r => r.addEventListener('change', renderChips));
        valueIn.addEventListener('input', renderPreview);

        // Tombol cepat tanggal berakhir: sekarang + N hari dalam WIB
        const fmt = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false });
        function wib(ms) {
            const p = {};
            fmt.formatToParts(new Date(ms)).forEach(x => { p[x.type] = x.value; });
            return p.year + '-' + p.month + '-' + p.day + 'T' + (p.hour === '24' ? '00' : p.hour) + ':' + p.minute;
        }
        form.querySelectorAll('[data-bulk-end]').forEach(b => b.addEventListener('click', () => {
            const d = Number(b.dataset.bulkEnd);
            ends.value = d ? wib(Date.now() + d * 86400000) : '';
        }));

        // Konfirmasi sebelum menyimpan (harga langsung berubah di katalog)
        form.addEventListener('submit', e => {
            const act = e.submitter ? e.submitter.value : 'apply';
            const n = count();
            if (n === 0) { e.preventDefault(); alert('Centang minimal satu akun dulu.'); return; }
            if (act === 'apply') {
                const v = parseFloat(valueIn.value);
                if (isNaN(v) || v <= 0) { e.preventDefault(); alert('Isi besar potongan dulu.'); return; }
                const t = mode() === 'percent' ? v + '%' : rp(v);
                if (!confirm('Terapkan diskon ' + t + ' ke ' + n + ' akun? Harga di katalog langsung berubah.')) e.preventDefault();
            } else if (!confirm('Akhiri diskon ' + n + ' akun dan kembalikan ke harga normal?')) {
                e.preventDefault();
            }
        });

        refresh();
    })();

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