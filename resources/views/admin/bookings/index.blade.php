@extends('layouts.admin')

@section('title', 'Booking Akun')

@section('content')
@php
    $fieldClass = 'w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-electric';
    $tabs = [
        'open'    => ['Aktif', null],
        'unpaid'  => ['Belum TF', $summary['unpaid_count']],
        'paid'    => ['Sudah TF', null],
        'decide'  => ['Perlu keputusan DP', $counts['decide']],
        'forfeited' => ['DP Hangus', null],
        'completed' => ['Selesai', null],
        'cancelled' => ['Batal', null],
        'expired'   => ['Kedaluwarsa', null],
        'all'     => ['Semua', null],
    ];
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
@endphp

<div class="max-w-6xl mx-auto pb-16">
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-display font-bold text-white">Booking Akun</h1>
        <p class="text-sm text-slate-400 mt-1">
            Booking dibuat pembeli lewat website (DP {{ \App\Models\Booking::dpPercent() }}%) atau dicatat admin lewat tombol <b class="text-slate-200">Booking / DP</b> di Kelola Akun.
            Setelah DP diterima, pembeli sudah bisa memainkan akun tapi data akun belum diganti sampai lunas (batas pelunasan paling lama sampai Gmail akun bisa diganti; mis. Gmail bisa diganti dalam 5 hari = DP maksimal 5 hari). DP hanya untuk akun yang Gmail-nya belum bisa diganti. Setelah lunas, lanjutkan lewat tombol <b class="text-slate-200">Proses Penjualan</b>.
        </p>
        <p class="text-sm text-amber-300/90 mt-2">
            Tidak lunas sampai batas waktu: akun otomatis ditarik kembali (tersedia lagi) dan DP hangus.
            @if($forfeitedTotal > 0)
                Total DP hangus: <b class="text-amber-200">{{ 'Rp ' . number_format($forfeitedTotal, 0, ',', '.') }}</b>.
            @endif
        </p>
    </div>

    <!-- Ringkasan: belum TF vs sudah TF -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
        <a href="{{ route('admin.bookings.index', ['status' => 'unpaid']) }}"
           class="rounded-3xl border border-amber-500/30 bg-amber-500/10 hover:bg-amber-500/15 p-4 transition {{ $filter === 'unpaid' ? 'ring-2 ring-amber-400/60' : '' }}">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                <p class="text-xs font-bold uppercase tracking-wider text-amber-300">Belum TF</p>
            </div>
            <p class="mt-2 text-2xl font-display font-extrabold text-white">{{ $summary['unpaid_count'] }} <span class="text-sm font-semibold text-slate-400">booking</span></p>
            <p class="text-xs text-slate-300 mt-0.5">DP menunggu transfer: <b class="text-amber-200">{{ $rp($summary['unpaid_total']) }}</b></p>
        </a>
        <a href="{{ route('admin.bookings.index', ['status' => 'paid']) }}"
           class="rounded-3xl border border-emerald-500/30 bg-emerald-500/10 hover:bg-emerald-500/15 p-4 transition {{ $filter === 'paid' ? 'ring-2 ring-emerald-400/60' : '' }}">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-300">Sudah TF</p>
            </div>
            <p class="mt-2 text-2xl font-display font-extrabold text-white">{{ $summary['paid_count'] }} <span class="text-sm font-semibold text-slate-400">booking</span></p>
            <p class="text-xs text-slate-300 mt-0.5">
                DP sudah masuk: <b class="text-emerald-200">{{ $rp($summary['paid_total']) }}</b>
                &middot; sisa pelunasan: <b class="text-emerald-200">{{ $rp($summary['paid_outstanding']) }}</b>
            </p>
        </a>
    </div>

    <!-- Pengingat pelunasan: batas pelunasan hari ini / besok -->
    @php $dueSoon = \App\Support\SettlementReminder::dueSoonCount(); @endphp
    @if($dueSoon > 0)
        <a href="{{ route('admin.bookings.index', ['status' => 'paid']) }}"
           class="flex items-center gap-3 rounded-3xl border border-amber-500/40 bg-amber-500/10 hover:bg-amber-500/15 px-4 py-3 mb-6 transition">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse shrink-0"></span>
            <p class="text-sm text-amber-100">
                <b class="text-amber-200">{{ $dueSoon }} booking</b> batas pelunasannya hari ini atau besok.
                <span class="text-amber-300/80">Cari penanda H-1 lalu kirim pengingat lewat WhatsApp.</span>
            </p>
        </a>
    @endif

    <!-- Tab status -->
    <div class="flex gap-2 overflow-x-auto pb-2 mb-4 -mx-1 px-1">
        @foreach($tabs as $key => [$label, $count])
            <a href="{{ route('admin.bookings.index', ['status' => $key, 'q' => $search ?: null]) }}"
               class="shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold transition {{ $filter === $key ? 'bg-electric text-white shadow-lg shadow-electric/20' : 'bg-white/5 text-slate-400 border border-white/5 hover:bg-white/10 hover:text-white' }}">
                {{ $label }}
                @if($count)
                    <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-amber-500 text-slate-900 text-[10px] font-bold flex items-center justify-center">{{ $count }}</span>
                @endif
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.bookings.index') }}" class="flex gap-2 mb-6">
        <input type="hidden" name="status" value="{{ $filter }}">
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari kode booking, nama, no. WA, atau kode akun" class="{{ $fieldClass }}">
        <button class="px-5 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm text-white font-semibold transition">Cari</button>
    </form>

    @if($bookings->isEmpty())
        <div class="glass-panel rounded-3xl p-14 text-center">
            <p class="text-slate-300 font-semibold">Tidak ada booking di tampilan ini.</p>
            <p class="text-sm text-slate-500 mt-1">Booking baru dari pembeli akan muncul di sini.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($bookings as $b)
                @php
                    $acc    = $b->account;
                    $due    = \App\Support\SettlementReminder::daysLeft($b);   // 1 = H-1, 0 = Hari H, null = bukan
                    $img    = $acc?->primaryImage?->image_path ? upload_url($acc->primaryImage->image_path) : null;
                    $waLine = $b->isAwaitingTransfer()
                        ? ' (DP ' . $rp($b->dp_amount) . ') belum kami terima. Mohon transfer DP sebelum batas waktu ya. Detail: '
                        : ($b->status === 'active'
                            ? ' (DP ' . $rp($b->dp_amount) . ') sudah kami terima, sisa pelunasan ' . $rp($b->remaining()) . '. Detail: '
                            : ' (DP ' . $rp($b->dp_amount) . '). Detail: ');
                    $wa     = $b->buyerWhatsappUrl(
                        'Halo ' . $b->buyer_name . ', ini EHANZSTORE. Booking ' . $b->booking_code . ' untuk akun ' . $b->account_code_snapshot
                        . $waLine . $b->publicUrl()
                    );
                @endphp

                <div class="glass-panel rounded-3xl p-4 sm:p-5 border-l-4 {{ $b->paymentEdge() }}">
                    <div class="flex flex-col lg:flex-row gap-4 lg:items-center">
                        <div class="flex items-center gap-4 min-w-0 flex-1">
                            <div class="w-16 h-16 shrink-0 rounded-2xl overflow-hidden bg-midnight border border-white/10">
                                @if($img)<img src="{{ $img }}" alt="" class="w-full h-full object-cover">@endif
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="font-mono text-xs text-slate-400">{{ $b->booking_code }}</span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full border text-[11px] font-extrabold uppercase tracking-wide {{ $b->paymentTone() }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $b->isPaid() ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                        {{ $b->paymentLabel() }}
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-full border text-[11px] font-semibold {{ $b->statusTone() }}">{{ $b->statusLabel() }}</span>
                                    <span class="px-2.5 py-0.5 rounded-full bg-white/5 border border-white/10 text-[11px] text-slate-300">DP: {{ $b->dpLabel() }}</span>
                                    @if($due !== null && $due <= 1)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full border text-[11px] font-extrabold uppercase tracking-wide {{ \App\Support\SettlementReminder::tone($due) }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $due <= 0 ? 'bg-red-400' : 'bg-amber-400' }} animate-pulse"></span>
                                            {{ \App\Support\SettlementReminder::label($due) }}
                                        </span>
                                    @endif
                                </div>
                                <h3 class="font-semibold text-white truncate">{{ $b->account_code_snapshot }} &middot; {{ $b->account_name_snapshot }}</h3>
                                <p class="text-sm text-slate-300 truncate">{{ $b->buyer_name }} &middot; <span class="font-mono text-slate-400">{{ $b->localPhone() }}</span></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 sm:gap-3 text-center lg:w-[360px] shrink-0">
                            <div class="rounded-2xl bg-white/[0.04] p-2 sm:p-2.5 min-w-0">
                                <p class="text-[10px] uppercase tracking-wider text-slate-500">Harga</p>
                                <p class="text-xs sm:text-sm font-bold text-white mt-0.5 break-words">{{ $rp($b->price_snapshot) }}</p>
                            </div>
                            <div class="rounded-2xl p-2 sm:p-2.5 min-w-0 {{ $b->isPaid() ? 'bg-emerald-500/10' : 'bg-amber-500/10' }}">
                                <p class="text-[10px] uppercase tracking-wider {{ $b->isPaid() ? 'text-emerald-300' : 'text-amber-300' }}">DP {{ $b->dp_percent }}% &middot; {{ $b->isPaid() ? 'sudah TF' : 'belum TF' }}</p>
                                <p class="text-xs sm:text-sm font-bold text-white mt-0.5 break-words">{{ $rp($b->dp_amount) }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/[0.04] p-2 sm:p-2.5 min-w-0">
                                <p class="text-[10px] uppercase tracking-wider text-slate-500">Sisa</p>
                                <p class="text-xs sm:text-sm font-bold text-white mt-0.5 break-words">{{ $rp($b->remaining()) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-white/5 flex flex-col lg:flex-row lg:items-center gap-3">
                        <div class="text-xs text-slate-400 space-y-0.5 flex-1">
                            <p>Dibuat {{ $b->createdLabel() }} WIB</p>
                            @if($b->isPaid())
                                <p class="text-emerald-300 font-semibold">
                                    DP {{ $rp($b->dp_amount) }} sudah ditransfer{{ $b->paidAtLabel() ? ', dikonfirmasi ' . $b->paidAtLabel() : '' }}
                                </p>
                            @elseif($b->status === 'pending')
                                <p class="text-amber-300 font-semibold">DP {{ $rp($b->dp_amount) }} belum ditransfer</p>
                            @else
                                <p>Tidak pernah transfer DP</p>
                            @endif
                            @if($b->isHolding() && $b->expires_at)
                                <p class="text-amber-300 font-semibold">
                                    {{ $b->status === 'pending' ? 'Batas bayar DP' : 'Batas pelunasan' }}:
                                    <span {!! $b->countdownAttrs('sisa ', 'Waktu habis') !!}>{{ $b->deadlineLabel() }}</span>
                                </p>
                            @elseif($b->cancel_reason)
                                <p class="{{ $b->isForfeited() ? 'text-red-300 font-semibold' : '' }}">{{ $b->cancel_reason }}</p>
                            @endif
                            @if($b->status === 'expired' && $b->isForfeited())
                                <p class="text-red-300">DP {{ $rp($b->dp_amount) }} hangus. Akun sudah dibuka kembali untuk dijual.</p>
                            @endif
                            @if($b->status === 'expired' && $b->dp_state === 'held')
                                <p class="text-amber-300 font-semibold">Lewat batas pelunasan. DP {{ $rp($b->dp_amount) }} masih ditahan, tentukan nasibnya.</p>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            <a href="{{ $wa }}" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-green-600/90 hover:bg-green-500 text-white text-xs font-bold transition">Chat Pembeli</a>
                            @if($due !== null && $due <= 1)
                                <a href="{{ \App\Support\SettlementReminder::whatsappUrl($b) }}" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-amber-500 hover:bg-amber-400 text-slate-900 text-xs font-extrabold transition">Ingatkan Pelunasan (WA)</a>
                            @endif
                            <a href="{{ $b->publicUrl() }}" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 text-slate-200 text-xs font-semibold transition">Halaman Booking</a>

                            @if($b->status === 'pending' || ($b->status === 'expired' && $b->dp_state === 'unpaid'))
                                <form method="POST" action="{{ route('admin.bookings.confirm', $b) }}" class="flex items-center gap-2" onsubmit="return confirm('Konfirmasi DP {{ $rp($b->dp_amount) }} sudah masuk ke rekening?')">
                                    @csrf
                                    @php
                                        $holdOpts = $acc ? \App\Models\Booking::holdOptionsFor($acc) : \App\Models\Booking::holdOptions();
                                        $holdDef  = $acc ? \App\Models\Booking::holdDaysFor($acc, null) : \App\Models\Booking::defaultHoldDays();
                                    @endphp
                                    @if($holdOpts)
                                    <select name="days" title="Batas pelunasan (maksimal sampai Gmail akun bisa diganti)" class="rounded-full bg-slate-900 border border-white/10 text-xs text-slate-200 px-3 py-2 focus:outline-none focus:border-electric">
                                        @foreach($holdOpts as $opt)
                                            <option value="{{ $opt }}" @selected($opt === $holdDef)>Lunas {{ $opt }} hari</option>
                                        @endforeach
                                    </select>
                                    @else
                                        <span class="text-[11px] text-red-300 max-w-[14rem]">Gmail akun sudah bisa diganti: DP tidak bisa diaktifkan, jual lunas.</span>
                                    @endif
                                    <button @disabled(! $holdOpts) class="px-4 py-2 rounded-full bg-electric hover:bg-blue-500 text-white text-xs font-bold transition disabled:opacity-40 disabled:cursor-not-allowed">
                                        {{ $b->status === 'pending' ? 'DP Sudah Diterima' : 'Aktifkan Lagi (DP Diterima)' }}
                                    </button>
                                </form>
                            @endif

                            @if($b->status === 'active')
                                @if($acc)
                                    <a href="{{ route('admin.accounts.sell', ['account' => $acc->id, 'booking' => $b->id]) }}" class="px-4 py-2 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition">Proses Penjualan</a>
                                @endif
                                <form method="POST" action="{{ route('admin.bookings.extend', $b) }}" class="flex">
                                    @csrf
                                    <input type="hidden" name="days" value="1">
                                    <button class="px-4 py-2 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 text-slate-200 text-xs font-semibold transition">+1 hari</button>
                                </form>
                                <form method="POST" action="{{ route('admin.bookings.extend', $b) }}" class="flex" onsubmit="return confirm('Perpanjang batas pelunasan 7 hari?')">
                                    @csrf
                                    <input type="hidden" name="days" value="7">
                                    <button class="px-4 py-2 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 text-slate-200 text-xs font-semibold transition">+7 hari</button>
                                </form>
                            @endif

                            @if(in_array($b->status, ['pending', 'active'], true))
                                {{-- Form pembatalan: kartu bawah (bottom sheet) di HP, kotak di tengah di layar lebar.
                                     Dipindah ke <body> (x-teleport) supaya tidak terpotong / bergeser oleh panel kaca (backdrop-filter) dan layar HP. --}}
                                <div x-data="{ open: false }">
                                    <button type="button" @click="open = true" class="px-4 py-2 rounded-full bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 text-red-300 text-xs font-bold transition">Batalkan</button>

                                    <template x-teleport="body">
                                        <div x-show="open" x-cloak x-transition.opacity
                                             @keydown.escape.window="open = false"
                                             @click.self="open = false"
                                             class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center bg-black/80 backdrop-blur-sm p-0 sm:p-4">
                                            <div x-show="open" x-transition
                                                 role="dialog" aria-modal="true" aria-label="Batalkan booking {{ $b->booking_code }}"
                                                 class="w-full sm:max-w-sm max-h-[92vh] overflow-y-auto rounded-t-[2rem] sm:rounded-[2rem] bg-slate-900 border border-white/10 shadow-2xl p-5 sm:p-6"
                                                 style="padding-bottom: calc(1.25rem + env(safe-area-inset-bottom, 0px));">

                                                <div class="flex items-start justify-between gap-3 mb-4">
                                                    <div class="min-w-0">
                                                        <h3 class="font-display font-extrabold text-lg text-white">Batalkan booking</h3>
                                                        <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $b->booking_code }} &middot; {{ $b->account_code_snapshot }} &middot; {{ $b->buyer_name }}</p>
                                                    </div>
                                                    <button type="button" @click="open = false" class="w-9 h-9 shrink-0 rounded-full bg-white/5 hover:bg-white/10 text-slate-300 flex items-center justify-center" aria-label="Tutup">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>

                                                <form method="POST" action="{{ route('admin.bookings.cancel', $b) }}" class="space-y-4">
                                                    @csrf
                                                    @if($b->status === 'active')
                                                        <div class="space-y-2.5">
                                                            <p class="text-xs text-slate-300 font-semibold">Siapa yang membatalkan?</p>
                                                            <label class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-3 text-xs text-slate-300 cursor-pointer">
                                                                <input type="radio" name="by" value="buyer" checked class="mt-0.5 w-4 h-4 shrink-0 accent-red-500">
                                                                <span><b class="text-white">Pembeli</b> membatalkan: DP hangus</span>
                                                            </label>
                                                            <label class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-3 text-xs text-slate-300 cursor-pointer">
                                                                <input type="radio" name="by" value="store" class="mt-0.5 w-4 h-4 shrink-0 accent-red-500">
                                                                <span><b class="text-white">Toko</b> membatalkan: DP dikembalikan</span>
                                                            </label>
                                                        </div>
                                                    @else
                                                        <input type="hidden" name="by" value="buyer">
                                                        <p class="text-xs text-slate-300 rounded-2xl border border-white/10 bg-white/[0.03] p-3">DP belum dibayar, jadi pembatalan tidak berdampak ke uang.</p>
                                                    @endif

                                                    <div>
                                                        <label for="cancel-reason-{{ $b->id }}" class="block text-xs font-semibold text-slate-400 mb-1.5">Alasan <span class="font-normal text-slate-500">(opsional)</span></label>
                                                        {{-- text-base di HP mencegah iOS memperbesar layar saat mengetik --}}
                                                        <textarea id="cancel-reason-{{ $b->id }}" name="reason" rows="3" maxlength="200" placeholder="Contoh: pembeli tidak bisa dihubungi"
                                                                  class="{{ $fieldClass }} !text-base sm:!text-sm resize-none"></textarea>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-2 pt-1">
                                                        <button type="button" @click="open = false" class="py-3 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 text-slate-200 text-sm font-semibold transition">Kembali</button>
                                                        <button class="py-3 rounded-full bg-red-600 hover:bg-red-500 text-white text-sm font-bold transition">Ya, batalkan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            @endif

                            @if($b->status === 'expired' && $b->dp_state === 'held')
                                <form method="POST" action="{{ route('admin.bookings.resolve', $b) }}" onsubmit="return confirm('DP dinyatakan hangus?')">
                                    @csrf<input type="hidden" name="outcome" value="forfeit">
                                    <button class="px-4 py-2 rounded-full bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 text-red-300 text-xs font-bold transition">DP Hangus</button>
                                </form>
                                <form method="POST" action="{{ route('admin.bookings.resolve', $b) }}" onsubmit="return confirm('Tandai DP dikembalikan ke pembeli?')">
                                    @csrf<input type="hidden" name="outcome" value="refund">
                                    <button class="px-4 py-2 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 text-slate-200 text-xs font-semibold transition">Kembalikan DP</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($bookings->hasPages())
            <div class="flex items-center justify-between mt-6">
                <span class="text-xs text-slate-500">Halaman {{ $bookings->currentPage() }} dari {{ $bookings->lastPage() }}</span>
                <div class="flex gap-2">
                    @if($bookings->onFirstPage())
                        <span class="px-4 py-2 rounded-full bg-white/5 text-slate-600 text-sm cursor-not-allowed">&larr; Sebelumnya</span>
                    @else
                        <a href="{{ $bookings->previousPageUrl() }}" class="px-4 py-2 rounded-full bg-white/5 hover:bg-white/10 text-slate-200 text-sm transition">&larr; Sebelumnya</a>
                    @endif
                    @if($bookings->hasMorePages())
                        <a href="{{ $bookings->nextPageUrl() }}" class="px-4 py-2 rounded-full bg-white/5 hover:bg-white/10 text-slate-200 text-sm transition">Berikutnya &rarr;</a>
                    @else
                        <span class="px-4 py-2 rounded-full bg-white/5 text-slate-600 text-sm cursor-not-allowed">Berikutnya &rarr;</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
    <style>[x-cloak] { display: none !important; }</style>
    <script src="//unpkg.com/alpinejs" defer></script>
@endpush