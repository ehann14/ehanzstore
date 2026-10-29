@extends('layouts.app')

@section('title', 'Syarat & Ketentuan Booking DP - EHANZSTORE.ID')
@section('description', 'Syarat dan ketentuan booking akun Clash of Clans dengan DP di EHANZSTORE.ID: besar DP, batas waktu pembayaran, pelunasan, pembatalan, dan pengembalian DP.')

@section('content')
@php
    $percent  = \App\Models\Booking::dpPercent();
    $rest     = 100 - $percent;
    $payHours = (int) config('store.booking_pay_hours', 24);
    $holdDays = (int) config('store.booking_hold_days', 3);
    $maxPhone = (int) config('store.booking_max_per_phone', 2);
    $waAsk    = $storeWaUrl . '?text=' . rawurlencode('Halo Admin EHANZSTORE, saya mau tanya soal booking DP.');

    $sections = [
        [
            't' => 'Pengertian booking & DP',
            'items' => [
                "Booking adalah cara menahan satu akun/clan supaya tidak dibeli orang lain dengan membayar uang muka (DP) sebesar {$percent}% dari harga.",
                'DP dibulatkan ke atas ke kelipatan Rp 1.000 dan tidak pernah melebihi harga akun.',
                'Harga akun dikunci sesuai harga saat booking dibuat, walaupun setelahnya ada perubahan harga atau promo.',
            ],
        ],
        [
            't' => 'Cara booking',
            'items' => [
                'Pilih akun yang masih tersedia, klik tombol Booking di halaman detail, isi nama dan nomor WhatsApp aktif, lalu setujui syarat ini.',
                'Setelah booking dibuat, kamu mendapat kode booking dan halaman pribadi untuk memantau statusnya. Simpan link halaman tersebut.',
                'Booking baru sah (akun ditahan) setelah admin mengonfirmasi bahwa DP sudah diterima.',
                'Pembayaran hanya melalui rekening/e-wallet resmi yang diberikan admin lewat WhatsApp. Website ini tidak memproses pembayaran. Waspada pihak yang mengatasnamakan EHANZSTORE.',
            ],
        ],
        [
            't' => 'Batas waktu pembayaran DP',
            'items' => [
                "DP wajib dibayar paling lambat {$payHours} jam sejak booking dibuat.",
                'Jika DP belum dibayar sampai batas waktu, booking batal otomatis dan akun tersedia kembali untuk pembeli lain. Tidak ada biaya apa pun.',
                'Sudah transfer tetapi booking sudah batal? Segera kirim bukti transfer ke admin. Admin akan mengaktifkan kembali booking selama akun belum diambil orang lain; jika tidak bisa, DP dikembalikan penuh.',
            ],
        ],
        [
            't' => 'Masa penahanan & pelunasan',
            'items' => [
                "Setelah DP dikonfirmasi, akun ditahan untukmu selama {$holdDays} hari.",
                "Sisa pembayaran ({$rest}% dari harga) wajib dilunasi dalam masa penahanan tersebut.",
                'Perpanjangan masa pelunasan hanya berlaku bila disetujui admin sebelum batas waktu habis.',
                'DP adalah bagian dari harga: sisa pelunasan = harga yang dikunci dikurangi DP.',
            ],
        ],
        [
            't' => 'Jika pembeli membatalkan',
            'items' => [
                'Pembatalan sebelum DP dibayar tidak dikenakan biaya.',
                'Pembatalan setelah DP diterima: DP hangus dan tidak dapat dikembalikan, karena akun sudah ditahan dan tidak ditawarkan ke pembeli lain.',
                'Tidak melunasi sampai batas waktu berakhir dianggap pembatalan oleh pembeli: booking berakhir, akun dibuka kembali untuk umum, dan DP hangus, kecuali admin menyetujui lain secara tertulis di WhatsApp.',
            ],
        ],
        [
            't' => 'Jika toko tidak dapat menyerahkan akun',
            'items' => [
                'Bila akun tidak dapat diserahkan (misalnya tidak sesuai deskripsi, bermasalah, atau tidak lagi tersedia), DP dikembalikan 100%.',
                'Pengembalian dilakukan maksimal 1x24 jam hari kerja setelah disepakati, ke rekening/e-wallet yang sama dengan pembayaran DP.',
                'Booking yang terindikasi spam atau memakai data palsu dapat dibatalkan toko; DP yang sudah masuk dikembalikan.',
            ],
        ],
        [
            't' => 'Ketentuan lain',
            'items' => [
                'Satu akun hanya bisa memiliki satu booking aktif pada satu waktu.',
                "Satu nomor WhatsApp maksimal {$maxPhone} booking aktif sekaligus.",
                'Data yang diisi harus benar dan nomor WhatsApp harus aktif karena dipakai untuk konfirmasi dan serah terima.',
                'Setelah pelunasan dan serah terima, garansi mengikuti Ketentuan Garansi EHANZSTORE dan masa garansi dihitung sejak serah terima.',
                'Dengan mencentang persetujuan saat booking, kamu dianggap telah membaca dan menyetujui seluruh ketentuan di halaman ini.',
            ],
        ],
    ];
@endphp

<section class="relative pt-36 sm:pt-40 pb-14 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-royal/10 to-transparent pointer-events-none"></div>
    <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-electric/10 border border-electric/30 text-blue-300 text-xs font-bold tracking-wide mb-6">
            Booking Akun
        </div>
        <h1 class="font-display font-extrabold text-4xl md:text-5xl text-white mb-5">
            Syarat &amp; Ketentuan <span class="text-electric glow-text">DP {{ $percent }}%</span>
        </h1>
        <p class="text-slate-400 max-w-2xl mx-auto text-lg">Tahan akun incaranmu lebih dulu dengan uang muka, lalu lunasi saat siap. Baca aturan mainnya di bawah ini.</p>
    </div>
</section>

<div class="max-w-4xl mx-auto px-4 -mt-2 mb-12">
    <div class="glass-strong rounded-3xl p-6 grid grid-cols-3 gap-4 text-center">
        <div>
            <p class="font-display font-extrabold text-3xl sm:text-4xl text-white">{{ $percent }}<span class="text-electric text-xl sm:text-2xl">%</span></p>
            <p class="text-[11px] sm:text-xs text-slate-400 mt-1">DP dari harga akun</p>
        </div>
        <div class="border-x border-white/10">
            <p class="font-display font-extrabold text-3xl sm:text-4xl text-white">{{ $payHours }}<span class="text-electric text-xl sm:text-2xl"> jam</span></p>
            <p class="text-[11px] sm:text-xs text-slate-400 mt-1">Batas bayar DP</p>
        </div>
        <div>
            <p class="font-display font-extrabold text-3xl sm:text-4xl text-white">{{ $holdDays }}<span class="text-electric text-xl sm:text-2xl"> hari</span></p>
            <p class="text-[11px] sm:text-xs text-slate-400 mt-1">Batas pelunasan</p>
        </div>
    </div>
</div>

<section class="max-w-4xl mx-auto px-4 space-y-5 mb-16">
    @foreach($sections as $i => $sec)
        <div class="glass rounded-3xl p-6 sm:p-7 border border-white/5">
            <h2 class="font-display font-extrabold text-xl text-white flex items-center gap-3 mb-4">
                <span class="w-8 h-8 shrink-0 rounded-full bg-electric/15 text-electric text-sm flex items-center justify-center">{{ $i + 1 }}</span>
                {{ $sec['t'] }}
            </h2>
            <ul class="space-y-2.5 text-sm text-slate-300 leading-relaxed">
                @foreach($sec['items'] as $item)
                    <li class="flex gap-3"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-electric shrink-0"></span><span>{{ $item }}</span></li>
                @endforeach
            </ul>
        </div>
    @endforeach
</section>

<div class="max-w-4xl mx-auto px-4 pb-24 text-center">
    <p class="text-slate-400 mb-5">Masih ragu atau butuh penjelasan? Tanya admin dulu, gratis.</p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="{{ route('catalog') }}" class="inline-flex items-center justify-center bg-electric hover:bg-blue-500 text-white font-bold px-8 py-3.5 rounded-full transition shadow-lg shadow-electric/20">Pilih Akun</a>
        <a href="{{ $waAsk }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center bg-white/5 hover:bg-white/10 border border-white/10 text-white font-semibold px-8 py-3.5 rounded-full transition">Tanya Admin</a>
    </div>
</div>
@endsection