{{--
    Tombol + formulir booking DP di halaman detail akun.
    Pemakaian: <x-booking-box :account="$account" />
    Modal dipindah ke <body> (x-teleport) supaya tidak terpotong oleh panel kaca (backdrop-filter) di sekitarnya.
--}}
@props(['account'])
@php
    $price     = (float) $account->price;
    $dp        = \App\Models\Booking::calculateDp($price);
    $percent   = \App\Models\Booking::dpPercent();
    $payHours  = (int) config('store.booking_pay_hours', 24);
    $allowsDp  = \App\Models\Booking::allowsDp($account);
    $holdDays  = \App\Models\Booking::holdDaysText($account);   // "maksimal 5" (akun) / "7 atau 14" (clan)
    $maxDays   = \App\Models\Booking::maxHoldDaysFor($account);
    $rp        = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $bag       = $errors->getBag('booking');
    $fieldCls  = 'w-full bg-white/5 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-electric transition';
@endphp

@if(! $account->bookingEnabled())
    <p class="text-[11px] text-slate-400 text-center rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2">
        Booking DP sedang tidak dibuka untuk {{ $account->noun() }} ini. Silakan beli <b class="text-slate-200">lunas</b> lewat WhatsApp.
    </p>
@elseif($dp > 0 && ! $allowsDp)
    <p class="text-[11px] text-slate-400 text-center rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2">
        Booking DP hanya untuk akun yang Gmail-nya belum bisa diganti. {{ $account->noun() }} ini bisa langsung dibeli <b class="text-slate-200">lunas</b> lewat WhatsApp.
    </p>
@elseif($dp > 0)
<div x-data="{ open: {{ $bag->any() ? 'true' : 'false' }} }">
    <button type="button" @click="open = true"
            class="flex items-center justify-center gap-2 w-full border border-electric/40 bg-electric/10 hover:bg-electric/20 text-blue-200 font-bold py-3.5 rounded-full transition active:scale-[0.98]">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Booking dulu, DP {{ $percent }}% ({{ $rp($dp) }})
    </button>
    <p class="text-[10px] text-slate-500 text-center mt-2">
        Amankan akun ini untukmu, langsung bisa dimainkan setelah DP diterima. Sisanya dilunasi sebelum data akun diganti.
        @if($maxDays !== null)Gmail akun ini baru bisa diganti dalam {{ $maxDays }} hari, jadi masa DP <b class="text-slate-300">maksimal {{ $maxDays }} hari</b>.@endif
        <a href="{{ route('booking.terms') }}" class="text-electric hover:text-white underline-offset-2 hover:underline">Syarat &amp; ketentuan DP</a>
    </p>

    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity
             @keydown.escape.window="open = false"
             class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/80 backdrop-blur-sm"
             @click.self="open = false">
            <div x-show="open" x-transition
                 class="w-full sm:max-w-md max-h-[94vh] overflow-y-auto rounded-t-[2rem] sm:rounded-[2rem] bg-slate-900 border border-white/10 shadow-2xl p-6 sm:p-8"
                 role="dialog" aria-modal="true" aria-label="Booking akun">

                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h3 class="font-display font-extrabold text-xl text-white">Booking {{ $account->noun() }} ini</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $account->account_code }} &middot; {{ $account->name }}</p>
                    </div>
                    <button type="button" @click="open = false" class="w-9 h-9 shrink-0 rounded-full bg-white/5 hover:bg-white/10 text-slate-300 flex items-center justify-center" aria-label="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Rincian -->
                <div class="rounded-3xl bg-white/[0.04] border border-white/5 p-4 mb-4 space-y-2 text-sm">
                    <div class="flex justify-between text-slate-400"><span>Harga {{ $account->noun() }} (dikunci)</span><span class="text-white font-semibold">{{ $rp($price) }}</span></div>
                    <div class="flex justify-between text-slate-400"><span>DP {{ $percent }}% dibayar sekarang</span><span class="text-electric font-extrabold">{{ $rp($dp) }}</span></div>
                    <div class="flex justify-between text-slate-400 pt-2 border-t border-white/5"><span>Sisa pelunasan</span><span class="text-white font-semibold">{{ $rp($price - $dp) }}</span></div>
                </div>

                <ul class="text-xs text-slate-400 space-y-1.5 mb-5 list-disc pl-5">
                    <li>DP dibayar maksimal <b class="text-slate-200">{{ $payHours }} jam</b> setelah booking, lewat itu booking batal otomatis.</li>
                    <li>Setelah DP diterima admin, akun <b class="text-slate-200">sudah bisa langsung dimainkan</b>, tapi data akun (Gmail, nomor proteksi, dll.) baru bisa diganti setelah lunas. Batas pelunasan <b class="text-slate-200">{{ $holdDays }} hari</b>@if($maxDays !== null) (sampai Gmail akun bisa diganti)@endif.</li>
                    <li>DP <b class="text-slate-200">tidak dikembalikan</b> bila pembeli membatalkan atau tidak melunasi tepat waktu.</li>
                </ul>

                @if($bag->has('booking'))
                    <div class="mb-4 p-3 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs">{{ $bag->first('booking') }}</div>
                @endif

                <form method="POST" action="{{ route('booking.store', $account->account_code) }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="bk_name" class="block text-xs font-semibold text-slate-400 mb-1.5">Nama kamu</label>
                        <input id="bk_name" type="text" name="buyer_name" required minlength="2" maxlength="100" value="{{ old('buyer_name') }}" placeholder="Nama lengkap / panggilan" class="{{ $fieldCls }}">
                        @if($bag->has('buyer_name'))<p class="text-red-400 text-xs mt-1">{{ $bag->first('buyer_name') }}</p>@endif
                    </div>

                    <div>
                        <label for="bk_phone" class="block text-xs font-semibold text-slate-400 mb-1.5">No. WhatsApp aktif</label>
                        <input id="bk_phone" type="tel" name="buyer_phone" required inputmode="tel" maxlength="25" value="{{ old('buyer_phone') }}" placeholder="0812-3456-7890" class="{{ $fieldCls }}">
                        @if($bag->has('buyer_phone'))<p class="text-red-400 text-xs mt-1">{{ $bag->first('buyer_phone') }}</p>@endif
                    </div>

                    <label class="flex items-start gap-3 text-xs text-slate-300 cursor-pointer">
                        <input type="checkbox" name="terms" value="1" required class="mt-0.5 w-4 h-4 rounded accent-electric shrink-0">
                        <span>Saya sudah membaca dan menyetujui
                            <a href="{{ route('booking.terms') }}" target="_blank" rel="noopener" class="text-electric underline">syarat &amp; ketentuan booking DP</a>.</span>
                    </label>
                    @if($bag->has('terms'))<p class="text-red-400 text-xs -mt-2">{{ $bag->first('terms') }}</p>@endif

                    <button type="submit" class="w-full bg-electric hover:bg-blue-500 text-white font-extrabold py-3.5 rounded-full transition shadow-lg shadow-electric/25 active:scale-[0.98]">
                        Booking Sekarang
                    </button>
                    <p class="text-[10px] text-slate-500 text-center">Pembayaran DP dilakukan lewat WhatsApp admin. Tidak ada pembayaran di website ini.</p>
                </form>
            </div>
        </div>
    </template>
</div>
@endif