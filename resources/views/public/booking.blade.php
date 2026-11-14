@extends('layouts.app')

@section('title', 'Booking ' . $booking->booking_code . ' - EHANZSTORE.ID')

@push('meta')
    <meta name="robots" content="noindex, nofollow">
    {{-- Pembeli sering keluar ke aplikasi bank/e-wallet lalu kembali: jangan muat ulang halaman otomatis. --}}
    <meta name="no-auto-reload" content="1">
@endpush

@section('content')
@php
    $rp        = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $acc       = $booking->account;
    $img       = $acc?->primaryImage?->image_path ? thumb_url($acc->primaryImage->image_path, 480) : null;
    $payHours  = (int) config('store.booking_pay_hours', 24);
    $holdDays  = \App\Models\Booking::holdDaysText($acc);
    $status    = $booking->status;
    $holding   = $booking->isHolding();

    $waText = function (string $line) use ($booking) {
        return 'Halo Admin EHANZSTORE, ' . $line . "\n\nKode booking: " . $booking->booking_code
            . "\nAkun: " . $booking->account_code_snapshot . ' - ' . $booking->account_name_snapshot
            . "\nNama: " . $booking->buyer_name
            . "\nLink: " . $booking->publicUrl();
    };

    $waPay    = $storeWaUrl . '?text=' . rawurlencode($waText('saya sudah booking dan ingin bayar DP ' . $rp($booking->dp_amount) . '. Mohon info rekening/e-wallet pembayaran.'));
    $waProof  = $storeWaUrl . '?text=' . rawurlencode($waText('saya sudah transfer DP ' . $rp($booking->dp_amount) . '. Berikut bukti transfernya.'));
    $waPelunasan = $storeWaUrl . '?text=' . rawurlencode($waText('saya mau melunasi sisa pembayaran ' . $rp($booking->remaining()) . '.'));
    $waHelp   = $storeWaUrl . '?text=' . rawurlencode($waText('saya mau tanya soal booking ini.'));

    $steps = [
        ['n' => '1', 'title' => 'Booking dibuat',         'done' => true],
        ['n' => '2', 'title' => 'Bayar DP ' . $rp($booking->dp_amount), 'done' => in_array($status, ['active', 'completed'], true)],
        ['n' => '3', 'title' => 'Lunasi sisa ' . $rp($booking->remaining()), 'done' => $status === 'completed'],
    ];
@endphp

<div class="pt-28 sm:pt-32 {{ $status === 'pending' && $holding ? 'pb-36 sm:pb-20' : 'pb-20' }} max-w-3xl mx-auto px-4">

    {{-- Pita pemberitahuan status berubah (pembeli yang memilih kapan memuat ulang) --}}
    <div id="statusBanner" hidden class="fixed inset-x-3 z-50 sm:max-w-md sm:mx-auto rounded-2xl border border-electric/40 bg-slate-900 shadow-2xl px-4 py-3 flex items-center gap-3"
         style="top: calc(env(safe-area-inset-top, 0px) + 5rem)" role="status">
        <p class="text-sm text-slate-100 flex-1">Status booking diperbarui.</p>
        <button type="button" id="statusReload" class="shrink-0 px-4 py-2 rounded-full bg-electric hover:bg-blue-500 text-white text-xs font-bold">Muat ulang</button>
    </div>

    @if(session('booking_created'))
        <div class="mb-6 rounded-3xl border border-emerald-500/30 bg-emerald-500/10 p-5 text-sm text-emerald-200">
            <p class="font-display font-extrabold text-emerald-100 text-lg">Booking berhasil dibuat 🎉</p>
            <p class="mt-1">Simpan halaman ini (tambahkan ke bookmark atau screenshot kode bookingmu). Lanjutkan dengan membayar DP lewat WhatsApp admin sebelum batas waktu di bawah.</p>
        </div>
    @endif

    <!-- Kepala -->
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <h1 class="font-display font-extrabold text-3xl sm:text-4xl text-white">Booking <span class="text-electric">{{ $booking->booking_code }}</span></h1>
        <span class="px-3 py-1 rounded-full border text-xs font-bold {{ $booking->statusTone() }}">{{ $booking->statusLabel() }}</span>
    </div>

    <!-- Akun -->
    <div class="glass-strong rounded-3xl p-4 sm:p-5 flex items-center gap-4 mb-6">
        <div class="w-20 h-20 sm:w-24 sm:h-24 shrink-0 rounded-2xl overflow-hidden bg-midnight border border-white/10">
            @if($img)<img src="{{ $img }}" alt="{{ $booking->account_name_snapshot }}" class="w-full h-full object-cover">@endif
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold text-slate-500">{{ $booking->account_code_snapshot }}</p>
            <h2 class="font-display font-extrabold text-lg sm:text-xl text-white truncate">{{ $booking->account_name_snapshot }}</h2>
            <p class="text-sm text-slate-400 mt-0.5">Atas nama <span class="text-slate-200 font-semibold">{{ $booking->buyer_name }}</span></p>
            @if($acc && $acc->publication_status === 'published')
                <a href="{{ route('account.detail', $acc->account_code) }}" class="inline-block mt-1 text-xs text-electric hover:text-white transition">Lihat detail akun &rarr;</a>
            @endif
        </div>
    </div>

    <!-- Progres -->
    <div class="grid grid-cols-3 gap-2 sm:gap-3 mb-6">
        @foreach($steps as $s)
            <div class="rounded-2xl p-3 text-center border {{ $s['done'] ? 'border-emerald-500/30 bg-emerald-500/10' : 'border-white/5 bg-white/[0.03]' }}">
                <div class="w-7 h-7 mx-auto rounded-full flex items-center justify-center text-xs font-extrabold {{ $s['done'] ? 'bg-emerald-500 text-slate-900' : 'bg-white/10 text-slate-300' }}">
                    @if($s['done'])&#10003;@else{{ $s['n'] }}@endif
                </div>
                <p class="text-[11px] sm:text-xs mt-2 font-semibold {{ $s['done'] ? 'text-emerald-200' : 'text-slate-400' }}">{{ $s['title'] }}</p>
            </div>
        @endforeach
    </div>

    <!-- Rincian harga -->
    <div class="glass rounded-3xl p-5 sm:p-6 mb-6">
        <div class="space-y-3 text-sm">
            <div class="flex justify-between text-slate-400"><span>Harga akun (dikunci saat booking)</span><span class="text-white font-semibold">{{ $rp($booking->price_snapshot) }}</span></div>
            <div class="flex justify-between text-slate-400"><span>DP {{ $booking->dp_percent }}%</span><span class="text-electric font-extrabold text-base">{{ $rp($booking->dp_amount) }}</span></div>
            <div class="flex justify-between text-slate-400 pt-3 border-t border-white/10"><span>Sisa pelunasan</span><span class="text-white font-extrabold text-base">{{ $rp($booking->remaining()) }}</span></div>
        </div>
    </div>

    <!-- Status & langkah berikutnya -->
    @if($status === 'pending' && $holding)
        <div class="rounded-3xl border border-amber-500/30 bg-amber-500/10 p-5 sm:p-6 mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-amber-300">Menunggu pembayaran DP</p>
            <p class="font-display font-extrabold text-2xl text-white mt-1" {!! $booking->countdownAttrs('Sisa waktu ', 'Waktu habis') !!}>{{ $booking->deadlineLabel() }}</p>
            <p class="text-xs text-amber-100/70 mt-1">Bayar DP maksimal {{ $payHours }} jam sejak booking dibuat, yaitu sebelum {{ $booking->deadlineLabel() }}. Lewat dari itu booking batal otomatis.</p>

            @if($qris['image_url'])
                {{-- QRIS langsung di halaman: tidak perlu menunggu info dari admin --}}
                <div class="mt-5 rounded-3xl bg-slate-950/50 border border-white/10 p-4 sm:p-5 text-center">
                    <p class="text-xs font-bold uppercase tracking-wider text-blue-300">Bayar dengan QRIS</p>
                    <p class="text-sm text-slate-400 mt-2">Nominal DP yang harus dibayar</p>
                    <p class="font-display font-extrabold text-4xl text-white mt-1">{{ $rp($booking->dp_amount) }}</p>
                    <button type="button" id="copyAmount" data-amount="{{ (int) $booking->dp_amount }}"
                            class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 text-slate-200 text-xs font-semibold transition">
                        <span id="copyLabel">Salin nominal</span>
                    </button>

                    <div class="mt-4 mx-auto w-full max-w-[19rem] rounded-2xl bg-white p-3">
                        <img src="{{ $qris['image_url'] }}" alt="QRIS pembayaran EHANZSTORE" class="w-full h-auto block" decoding="async">
                    </div>

                    <a href="{{ $qris['image_url'] }}" download="QRIS-EHANZSTORE" class="mt-4 inline-flex items-center justify-center gap-2 bg-electric hover:bg-blue-500 text-white font-bold px-6 py-3 rounded-full transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
                        Unduh QRIS
                    </a>

                    @if($qris['note'])
                        <p class="mt-3 text-xs text-slate-300 whitespace-pre-line">{{ $qris['note'] }}</p>
                    @endif

                    <p class="mt-3 text-[11px] text-slate-500 leading-relaxed">
                        Tips: unduh QRIS, buka e-wallet atau m-banking, pilih scan dari galeri, lalu isi nominal persis seperti di atas.
                        Setelah bayar, kembali ke halaman ini dan tekan <b class="text-slate-300">Kirim Bukti Transfer</b>. Halaman ini tidak memuat ulang sendiri.
                    </p>
                </div>

                <ol class="mt-5 space-y-2 text-sm text-slate-200 list-decimal pl-5">
                    <li>Bayar DP <b>{{ $rp($booking->dp_amount) }}</b> lewat QRIS di atas.</li>
                    <li>Screenshot bukti transfer, lalu tekan <b>Kirim Bukti Transfer</b> dan lampirkan gambarnya di WhatsApp (ikon klip).</li>
                    <li>Setelah admin mengonfirmasi, akun sudah bisa langsung kamu mainkan selama masa pelunasan {{ $holdDays }} hari (data akun diganti setelah lunas).</li>
                </ol>

                <div class="mt-5 flex flex-col sm:flex-row gap-3">
                    <a href="{{ $waProof }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-500 text-white font-bold px-6 py-3 rounded-full transition">Kirim Bukti Transfer</a>
                    <a href="{{ $waPay }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-semibold px-6 py-3 rounded-full transition">Bayar lewat rekening bank? Tanya admin</a>
                </div>
            @else
            <ol class="mt-4 space-y-2 text-sm text-slate-200 list-decimal pl-5">
                <li>Klik <b>Minta Info Pembayaran</b> untuk chat admin (kode booking terisi otomatis).</li>
                <li>Transfer DP <b>{{ $rp($booking->dp_amount) }}</b> ke rekening/e-wallet resmi yang diberikan admin.</li>
                <li>Kirim bukti transfer. Setelah admin mengonfirmasi, akun sudah bisa langsung kamu mainkan selama masa pelunasan {{ $holdDays }} hari (data akun diganti setelah lunas).</li>
            </ol>

            <div class="mt-5 flex flex-col sm:flex-row gap-3">
                <a href="{{ $waPay }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-500 text-white font-bold px-6 py-3 rounded-full transition">Minta Info Pembayaran</a>
                <a href="{{ $waProof }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-semibold px-6 py-3 rounded-full transition">Kirim Bukti Transfer</a>
            </div>
            @endif
        </div>

        {{-- Bar tetap di bawah (HP): pembeli yang kembali dari aplikasi bank langsung melihat tombol kirim bukti --}}
        <div class="sm:hidden fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-slate-950/95 px-4 pt-3" style="padding-bottom: calc(env(safe-area-inset-bottom, 0px) + .75rem)">
            <a href="{{ $waProof }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 w-full bg-green-600 active:bg-green-500 text-white font-bold py-3.5 rounded-full">
                Sudah transfer? Kirim bukti
            </a>
        </div>

    @elseif($status === 'active' && $holding)
        <div class="rounded-3xl border border-emerald-500/30 bg-emerald-500/10 p-5 sm:p-6 mb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-300">DP diterima &middot; akun sudah bisa dimainkan</p>
            <p class="font-display font-extrabold text-2xl text-white mt-1" {!! $booking->countdownAttrs('Lunasi dalam ', 'Waktu habis') !!}>{{ $booking->deadlineLabel() }}</p>
            <p class="text-xs text-emerald-100/70 mt-1">Data akun (Gmail, nomor proteksi, dll.) baru bisa diganti ke milikmu setelah lunas. Lunasi sisa {{ $rp($booking->remaining()) }} sebelum {{ $booking->deadlineLabel() }}. Perlu waktu tambahan? Hubungi admin sebelum batas waktu habis.</p>
            <div class="mt-5">
                <a href="{{ $waPelunasan }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-500 text-white font-bold px-6 py-3 rounded-full transition">Lunasi via WhatsApp</a>
            </div>
        </div>

    @elseif($status === 'completed')
        <div class="rounded-3xl border border-electric/30 bg-electric/10 p-5 sm:p-6 mb-6">
            <p class="font-display font-extrabold text-xl text-white">Pembelian selesai. Terima kasih! 🎉</p>
            <p class="text-sm text-blue-100/80 mt-1">DP kamu sudah dihitung sebagai bagian pembayaran. Garansi mengikuti ketentuan transaksi dan bisa dicek di halaman <a href="{{ route('warranty.check') }}" class="underline text-white">Cek Garansi</a>.</p>
        </div>

    @elseif($status === 'cancelled' || $status === 'expired')
        <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-5 sm:p-6 mb-6">
            <p class="font-display font-extrabold text-xl text-white">Booking {{ $status === 'expired' ? 'sudah kedaluwarsa' : 'dibatalkan' }}</p>
            @if($booking->cancel_reason)<p class="text-sm text-slate-400 mt-1">{{ $booking->cancel_reason }}</p>@endif

            @php
                $dpNote = match ($booking->dp_state) {
                    'unpaid'    => 'DP belum dibayar, jadi tidak ada dana yang ditahan.',
                    'held'      => 'DP ' . $rp($booking->dp_amount) . ' sedang ditinjau admin. Hubungi admin bila ada pertanyaan.',
                    'forfeited' => 'DP ' . $rp($booking->dp_amount) . ' dinyatakan hangus sesuai syarat & ketentuan.',
                    'refunded'  => 'DP ' . $rp($booking->dp_amount) . ' dikembalikan ke kamu. Belum menerima? Hubungi admin.',
                    default     => '',
                };
            @endphp
            @if($dpNote)<p class="text-sm text-slate-300 mt-3">{{ $dpNote }}</p>@endif
            <a href="{{ route('catalog') }}" class="inline-flex mt-4 bg-electric hover:bg-blue-500 text-white font-bold px-6 py-3 rounded-full transition">Lihat akun lain</a>
        </div>
    @endif

    <div class="text-center space-y-2 text-xs text-slate-500">
        <p>Dibuat {{ $booking->createdLabel() }} WIB &middot; <a href="{{ route('booking.terms') }}" class="text-electric hover:text-white underline-offset-2 hover:underline">Syarat &amp; ketentuan booking DP</a></p>
        <p><a href="{{ $waHelp }}" target="_blank" rel="noopener" class="text-slate-400 hover:text-white transition">Ada pertanyaan? Chat admin</a></p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        // ----- Salin nominal DP -----
        var copyBtn = document.getElementById('copyAmount');
        if (copyBtn) {
            var label = document.getElementById('copyLabel');
            copyBtn.addEventListener('click', function () {
                var amount = copyBtn.getAttribute('data-amount');
                var done = function () { label.textContent = 'Tersalin: ' + amount; setTimeout(function () { label.textContent = 'Salin nominal'; }, 2500); };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(amount).then(done, function () { legacy(amount, done); });
                } else { legacy(amount, done); }
            });
            function legacy(text, done) {
                var t = document.createElement('textarea');
                t.value = text; t.setAttribute('readonly', ''); t.style.cssText = 'position:fixed;opacity:0;top:0';
                document.body.appendChild(t); t.select();
                try { document.execCommand('copy'); done(); } catch (e) {}
                document.body.removeChild(t);
            }
        }

        // ----- Cek status diam-diam: TIDAK pernah memuat ulang halaman sendiri -----
        var banner = document.getElementById('statusBanner');
        var initial = @json(['status' => $status, 'dp_state' => $booking->dp_state]);
        var statusUrl = '{{ route('booking.status', $booking->token) }}';
        if (!banner || (initial.status !== 'pending' && initial.status !== 'active')) { return; }

        var busy = false, last = Date.now();
        function check() {
            if (busy || document.hidden) { return; }
            busy = true; last = Date.now();
            fetch(statusUrl, { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) {
                    if (d && (d.status !== initial.status || d.dp_state !== initial.dp_state)) { banner.hidden = false; }
                })
                .catch(function () {})
                .then(function () { busy = false; });
        }

        document.getElementById('statusReload').addEventListener('click', function () { window.location.reload(); });
        document.addEventListener('visibilitychange', function () { if (!document.hidden) { check(); } });
        window.addEventListener('pageshow', function (e) { if (e.persisted) { check(); } });
        setInterval(function () { if (Date.now() - last >= 30000) { check(); } }, 5000);
    })();
</script>
@endpush