@extends('layouts.admin')

@section('title', 'Booking DP')

@section('content')
@php
    /** @var \App\Models\CocAccount $account */
    $imageUrl   = $account->primaryImage?->image_path ? upload_url($account->primaryImage->image_path) : null;
    $price      = (int) $account->price;
    $options    = \App\Models\Booking::holdOptions();
    $defaultDp  = \App\Models\Booking::calculateDp((float) $account->price);
    $dpVal      = (int) old('dp_amount', $defaultDp);
    $daysVal    = (int) old('hold_days', \App\Models\Booking::defaultHoldDays());
    if (! in_array($daysVal, $options, true)) { $daysVal = \App\Models\Booking::defaultHoldDays(); }
    $inputCls   = 'w-full bg-brand-950/50 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition';
    $labelCls   = 'block text-xs font-semibold text-slate-400 mb-2';
@endphp

<div class="max-w-3xl mx-auto pb-20">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.accounts.index') }}" class="p-2 rounded-xl bg-white/5 hover:bg-white/10 transition text-slate-400 hover:text-white" aria-label="Kembali">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">Booking / DP</h1>
            <p class="text-sm text-slate-400">Untuk pembeli yang bayar DP dulu. Setelah DP masuk, pembeli sudah bisa memainkan akun tapi data akun (email/nomor) belum boleh diganti sampai batas pelunasan; kalau lunas, lanjutkan lewat <b class="text-slate-200">Terjual</b>.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
            <p class="font-semibold mb-1">Booking belum bisa disimpan:</p>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
            </ul>
        </div>
    @endif

    <!-- Ringkasan akun -->
    <div class="glass-panel rounded-2xl p-4 flex items-center gap-4 mb-6 border border-white/5">
        <div class="w-16 h-16 shrink-0 rounded-xl overflow-hidden bg-midnight border border-white/10">
            @if($imageUrl)<img src="{{ $imageUrl }}" alt="{{ $account->name }}" class="w-full h-full object-cover">@endif
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2 mb-0.5">
                <span class="text-[11px] font-mono text-slate-500">{{ $account->account_code }}</span>
                <span class="px-2 py-0.5 rounded-md bg-electric/15 text-electric text-[11px] font-bold">{{ $account->badgeLabel() }}</span>
            </div>
            <h2 class="font-semibold text-white truncate">{{ $account->name }}</h2>
            <p class="text-brand-400 font-bold text-sm">Harga: Rp {{ number_format($price, 0, ',', '.') }}</p>
        </div>
    </div>

    <form action="{{ route('admin.accounts.booking.store', $account) }}" method="POST" class="glass-panel rounded-3xl p-6 md:p-8 space-y-8" id="bookingForm">
        @csrf

        <div class="space-y-4">
            <h3 class="text-sm font-bold text-brand-400 uppercase tracking-wider border-b border-white/5 pb-2">Data Pembeli</h3>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label for="bk_name" class="{{ $labelCls }}">Nama Pembeli <span class="text-red-400">*</span></label>
                    <input id="bk_name" type="text" name="buyer_name" required minlength="2" maxlength="100"
                           value="{{ old('buyer_name') }}" placeholder="Nama lengkap / nama panggilan"
                           class="{{ $inputCls }} @error('buyer_name') border-red-500 @enderror">
                    @error('buyer_name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="bk_phone" class="{{ $labelCls }}">No. WhatsApp Pembeli <span class="text-red-400">*</span></label>
                    <input id="bk_phone" type="tel" name="buyer_phone" required inputmode="tel" maxlength="25"
                           value="{{ old('buyer_phone') }}" placeholder="0812-3456-7890"
                           class="{{ $inputCls }} @error('buyer_phone') border-red-500 @enderror">
                    @error('buyer_phone') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <h3 class="text-sm font-bold text-brand-400 uppercase tracking-wider border-b border-white/5 pb-2">DP &amp; Batas Pelunasan</h3>

            <div>
                <label for="bk_dp" class="{{ $labelCls }}">Nominal DP yang sudah diterima (Rp) <span class="text-red-400">*</span></label>
                <input id="bk_dp" type="number" name="dp_amount" required min="1000" max="{{ max(1000, $price - 1) }}" step="1"
                       value="{{ $dpVal }}" placeholder="0"
                       class="{{ $inputCls }} @error('dp_amount') border-red-500 @enderror">
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach([20, 30, 50] as $pct)
                        <button type="button" data-dp-pct="{{ $pct }}"
                                class="px-3 py-1.5 rounded-lg border border-white/10 text-xs font-semibold text-slate-300 hover:bg-white/5 transition">{{ $pct }}%</button>
                    @endforeach
                </div>
                <p class="text-[11px] text-slate-500 mt-2">Sisa pelunasan: <span id="bk_remaining" class="font-bold text-slate-200">-</span></p>
                @error('dp_amount') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <span class="{{ $labelCls }}">Harus lunas dalam <span class="text-red-400">*</span></span>
                <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Batas pelunasan">
                    @foreach($options as $opt)
                        <label class="cursor-pointer">
                            <input type="radio" name="hold_days" value="{{ $opt }}" required class="peer sr-only" @checked($daysVal === $opt)>
                            <span class="block text-center rounded-xl border border-white/10 bg-brand-950/50 px-3 py-3 transition hover:bg-white/5 text-slate-300
                                         peer-checked:border-amber-500/60 peer-checked:bg-amber-500/15 peer-checked:text-amber-300
                                         peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">
                                <span class="block text-base font-bold">{{ $opt }} hari</span>
                                <span class="block text-[11px] opacity-70">{{ $opt <= 7 ? 'Standar' : 'Lebih longgar' }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('hold_days') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-start gap-3 p-3 rounded-xl bg-amber-500/10 border border-amber-500/20">
                <svg class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <div class="text-sm">
                    <p class="text-amber-300 font-semibold">Lunas paling lambat: <span id="bk_deadline" class="text-white">-</span></p>
                    <p class="text-slate-400 text-xs mt-0.5">Kalau sampai batas itu belum lunas, akun otomatis ditarik kembali (tersedia lagi di katalog) dan DP dinyatakan hangus. Pastikan pembeli sudah paham.</p>
                </div>
            </div>
        </div>

        <div>
            <label for="bk_notes" class="{{ $labelCls }}">Catatan <span class="text-slate-600 font-normal">(opsional)</span></label>
            <textarea id="bk_notes" name="notes" rows="3" maxlength="500" placeholder="Mis. DP via DANA, bukti transfer sudah dicek..."
                      class="{{ $inputCls }} resize-none">{{ old('notes') }}</textarea>
            @error('notes') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3 pt-4 border-t border-white/5">
            <a href="{{ route('admin.accounts.index') }}" class="text-center text-slate-400 hover:text-white text-sm font-medium transition px-4 py-3">Batal</a>
            <button type="submit" class="sm:ml-auto bg-amber-500 hover:bg-amber-400 text-slate-900 font-bold py-3 px-8 rounded-xl transition shadow-lg shadow-amber-500/20 active:scale-95">
                Simpan Booking &amp; Tahan Akun
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const PRICE = {{ $price }};
        const dp    = document.getElementById('bk_dp');
        const rem   = document.getElementById('bk_remaining');
        const dead  = document.getElementById('bk_deadline');
        const form  = document.getElementById('bookingForm');
        if (!dp || !rem || !dead || !form) return;

        const rp = n => 'Rp ' + Math.max(0, Math.round(n)).toLocaleString('id-ID');

        function renderRemaining() {
            rem.textContent = rp(PRICE - (parseFloat(dp.value) || 0));
        }

        function renderDeadline() {
            const checked = form.querySelector('input[name="hold_days"]:checked');
            if (!checked) { dead.textContent = '-'; return; }
            const end = new Date(Date.now() + parseInt(checked.value, 10) * 86400000);
            try {
                dead.textContent = new Intl.DateTimeFormat('id-ID', {
                    timeZone: 'Asia/Jakarta', day: 'numeric', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', hour12: false
                }).format(end) + ' WIB';
            } catch (e) {
                dead.textContent = end.toLocaleString('id-ID');
            }
        }

        dp.addEventListener('input', renderRemaining);
        form.querySelectorAll('input[name="hold_days"]').forEach(r => r.addEventListener('change', renderDeadline));
        form.querySelectorAll('[data-dp-pct]').forEach(btn => btn.addEventListener('click', function () {
            const pct = parseInt(btn.dataset.dpPct, 10);
            dp.value = Math.min(PRICE - 1, Math.ceil(PRICE * pct / 100 / 1000) * 1000);
            renderRemaining();
        }));

        renderRemaining();
        renderDeadline();
    })();
</script>
@endpush