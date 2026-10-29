@extends('layouts.admin')

@section('title', 'Booking Akun')

@section('content')
@php
    $fieldClass = 'w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-electric';
    $tabs = [
        'open'    => ['Aktif', null],
        'pending' => ['Menunggu DP', $counts['pending']],
        'active'  => ['DP diterima', $counts['active']],
        'decide'  => ['Perlu keputusan DP', $counts['decide']],
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
            Pembeli booking dengan DP {{ \App\Models\Booking::dpPercent() }}%. Tandai DP diterima setelah bukti transfer dicek; akun otomatis ditahan
            {{ config('store.booking_hold_days') }} hari untuk pelunasan. Setelah lunas, lanjutkan lewat tombol <b class="text-slate-200">Proses Penjualan</b>.
        </p>
    </div>

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
                    $img    = $acc?->primaryImage?->image_path ? upload_url($acc->primaryImage->image_path) : null;
                    $wa     = $b->buyerWhatsappUrl(
                        'Halo ' . $b->buyer_name . ', ini EHANZSTORE. Booking ' . $b->booking_code . ' untuk akun ' . $b->account_code_snapshot
                        . ' (DP ' . $rp($b->dp_amount) . '). Detail: ' . $b->publicUrl()
                    );
                @endphp

                <div class="glass-panel rounded-3xl p-4 sm:p-5">
                    <div class="flex flex-col lg:flex-row gap-4 lg:items-center">
                        <div class="flex items-center gap-4 min-w-0 flex-1">
                            <div class="w-16 h-16 shrink-0 rounded-2xl overflow-hidden bg-midnight border border-white/10">
                                @if($img)<img src="{{ $img }}" alt="" class="w-full h-full object-cover">@endif
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="font-mono text-xs text-slate-400">{{ $b->booking_code }}</span>
                                    <span class="px-2.5 py-0.5 rounded-full border text-[11px] font-semibold {{ $b->statusTone() }}">{{ $b->statusLabel() }}</span>
                                    <span class="px-2.5 py-0.5 rounded-full bg-white/5 border border-white/10 text-[11px] text-slate-300">DP: {{ $b->dpLabel() }}</span>
                                </div>
                                <h3 class="font-semibold text-white truncate">{{ $b->account_code_snapshot }} &middot; {{ $b->account_name_snapshot }}</h3>
                                <p class="text-sm text-slate-300 truncate">{{ $b->buyer_name }} &middot; <span class="font-mono text-slate-400">{{ $b->localPhone() }}</span></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3 text-center lg:w-[360px] shrink-0">
                            <div class="rounded-2xl bg-white/[0.04] p-2.5">
                                <p class="text-[10px] uppercase tracking-wider text-slate-500">Harga</p>
                                <p class="text-sm font-bold text-white mt-0.5">{{ $rp($b->price_snapshot) }}</p>
                            </div>
                            <div class="rounded-2xl bg-electric/10 p-2.5">
                                <p class="text-[10px] uppercase tracking-wider text-blue-300">DP {{ $b->dp_percent }}%</p>
                                <p class="text-sm font-bold text-white mt-0.5">{{ $rp($b->dp_amount) }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/[0.04] p-2.5">
                                <p class="text-[10px] uppercase tracking-wider text-slate-500">Sisa</p>
                                <p class="text-sm font-bold text-white mt-0.5">{{ $rp($b->remaining()) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-white/5 flex flex-col lg:flex-row lg:items-center gap-3">
                        <div class="text-xs text-slate-400 space-y-0.5 flex-1">
                            <p>Dibuat {{ $b->createdLabel() }} WIB</p>
                            @if($b->isHolding() && $b->expires_at)
                                <p class="text-amber-300 font-semibold">
                                    {{ $b->status === 'pending' ? 'Batas bayar DP' : 'Batas pelunasan' }}:
                                    <span {!! $b->countdownAttrs('sisa ', 'Waktu habis') !!}>{{ $b->deadlineLabel() }}</span>
                                </p>
                            @elseif($b->cancel_reason)
                                <p>{{ $b->cancel_reason }}</p>
                            @endif
                            @if($b->status === 'expired' && $b->dp_state === 'held')
                                <p class="text-amber-300 font-semibold">Lewat batas pelunasan. DP {{ $rp($b->dp_amount) }} masih ditahan, tentukan nasibnya.</p>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            <a href="{{ $wa }}" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-green-600/90 hover:bg-green-500 text-white text-xs font-bold transition">Chat Pembeli</a>
                            <a href="{{ $b->publicUrl() }}" target="_blank" rel="noopener" class="px-4 py-2 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 text-slate-200 text-xs font-semibold transition">Halaman Booking</a>

                            @if($b->status === 'pending' || ($b->status === 'expired' && $b->dp_state === 'unpaid'))
                                <form method="POST" action="{{ route('admin.bookings.confirm', $b) }}" onsubmit="return confirm('Konfirmasi DP {{ $rp($b->dp_amount) }} sudah masuk ke rekening?')">
                                    @csrf
                                    <button class="px-4 py-2 rounded-full bg-electric hover:bg-blue-500 text-white text-xs font-bold transition">
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
                            @endif

                            @if(in_array($b->status, ['pending', 'active'], true))
                                <div x-data="{ open: false }" class="relative">
                                    <button type="button" @click="open = !open" class="px-4 py-2 rounded-full bg-red-500/10 hover:bg-red-500/20 border border-red-500/30 text-red-300 text-xs font-bold transition">Batalkan</button>
                                    <div x-show="open" x-cloak @click.outside="open = false" x-transition
                                         class="absolute right-0 bottom-full mb-2 z-30 w-72 rounded-3xl glass-strong p-4 shadow-2xl">
                                        <form method="POST" action="{{ route('admin.bookings.cancel', $b) }}" class="space-y-3">
                                            @csrf
                                            @if($b->status === 'active')
                                                <p class="text-xs text-slate-300 font-semibold">Siapa yang membatalkan?</p>
                                                <label class="flex items-start gap-2 text-xs text-slate-300"><input type="radio" name="by" value="buyer" checked class="mt-0.5 accent-red-500"> <span><b>Pembeli</b> membatalkan: DP hangus</span></label>
                                                <label class="flex items-start gap-2 text-xs text-slate-300"><input type="radio" name="by" value="store" class="mt-0.5 accent-red-500"> <span><b>Toko</b> membatalkan: DP dikembalikan</span></label>
                                            @else
                                                <input type="hidden" name="by" value="buyer">
                                                <p class="text-xs text-slate-300">DP belum dibayar, jadi pembatalan tidak berdampak ke uang.</p>
                                            @endif
                                            <input type="text" name="reason" maxlength="200" placeholder="Alasan (opsional)" class="{{ $fieldClass }}">
                                            <button class="w-full py-2 rounded-full bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition">Ya, batalkan booking</button>
                                        </form>
                                    </div>
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