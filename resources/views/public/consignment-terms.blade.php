@extends('layouts.app')

@section('title', 'Syarat Titip Akun untuk Dijual - EHANZSTORE.ID')
@section('description', 'Titip akun Clash of Clans kamu untuk dijual di EHANZSTORE.ID. Syarat wajib: email akun bisa diganti ke Gmail pembeli, dan nomor proteksi bisa diganti ke nomor pembeli bila proteksi ON. Baca komisi, serah terima, dan setoran di sini.')

@section('content')
@php
    $commission   = (int) config('store.consign_commission_percent', 10);
    $handoverHrs  = (int) config('store.consign_handover_hours', 24);
    $payoutHrs    = (int) config('store.consign_payout_hours', 24);
    $listingDays  = (int) config('store.consign_listing_days', 30);
    $maxWait      = max(0, (int) config('store.consign_max_email_wait_days', 0));
    $warranty     = \App\Models\Sale::warrantyRange();
    $waTitip      = $storeWaUrl . '?text=' . rawurlencode("Halo Admin EHANZSTORE, saya mau titip akun untuk dijual.\n\nTH: \nHarga yang diinginkan: \nEmail akun bisa diganti ke Gmail pembeli: ya\nProteksi akun (ON/OFF): \nKalau proteksi ON, nomor proteksi bisa diganti ke nomor pembeli: ya");
    $waAsk        = $storeWaUrl . '?text=' . rawurlencode('Halo Admin EHANZSTORE, saya mau tanya soal titip akun.');

    $sections = [
        [
            't' => 'Syarat akun yang boleh dititip',
            'items' => [
                'Akun adalah milik kamu sendiri, bukan hasil curian, bajakan, atau joki. Admin berhak meminta bukti kepemilikan (video login atau screen share).',
                'Akun bisa dibuka normal, tidak terkena banned, tidak terkunci, dan tidak dalam proses recovery.',
                'Data akun yang kamu sampaikan (Town Hall, level hero, gems, dan lainnya) harus sesuai kenyataan. Admin mengecek lagi sebelum akun ditayangkan.',
                'Nomor WhatsApp pemilik harus aktif karena dipakai untuk kabar penawaran, serah terima, dan setoran.',
                'Dua syarat utama wajib terpenuhi: email akun bisa diganti ke Gmail pembeli, dan bila proteksi akun ON, nomor proteksi juga bisa diganti ke nomor pembeli (lihat kotak Syarat wajib di bawah).',
            ],
        ],
        [
            't' => 'Komisi & harga',
            'items' => [
                "Tidak ada biaya di depan. Komisi toko hanya diambil saat akun laku, umumnya sekitar {$commission}% dari harga jual (bisa berupa persen atau nominal tetap).",
                'Harga jual dan besar komisi disepakati bersama lewat WhatsApp sebelum akun ditayangkan, dan tidak diubah sepihak setelahnya.',
                'Perubahan harga (misalnya turun harga karena lama belum laku) harus disetujui pemilik akun terlebih dulu.',
                'Bagian pemilik = harga jual dikurangi komisi toko. Rinciannya dikirim ke pemilik saat akun terjual.',
            ],
        ],
        [
            't' => 'Proses titip sampai akun laku',
            'items' => [
                'Chat admin, kirim data dan screenshot akun. Admin mengecek, lalu menyepakati harga dan komisi.',
                'Akun ditayangkan di katalog EHANZSTORE. Data pribadi pemilik tidak pernah ditampilkan ke publik.',
                'Selama akun belum laku, akun tetap milik dan dalam kendali pemilik. Kamu tidak perlu menyerahkan password ke pihak lain untuk sekadar menitip.',
                "Masa titip {$listingDays} hari. Setelah itu pemilik boleh memperpanjang atau menarik akunnya kapan saja, selama tidak ada booking/DP aktif atas akun tersebut.",
                'Selama dititip, akun tidak boleh dijual di tempat lain. Kalau akun terjual di tempat lain, wajib kabari admin segera supaya listing diturunkan.',
            ],
        ],
        [
            't' => 'Serah terima ke pembeli',
            'items' => [
                "Setelah pembeli bayar, pemilik wajib mengganti email akun ke Gmail milik pembeli paling lambat {$handoverHrs} jam, dengan didampingi admin lewat WhatsApp.",
                "Untuk akun berproteksi ON, nomor HP proteksi juga wajib diganti ke nomor pembeli dalam batas {$handoverHrs} jam yang sama.",
                'Admin akan menyampaikan alamat Gmail pembeli yang resmi. Jangan memakai alamat dari pihak lain di luar yang diberikan admin.',
                'Pemilik tidak boleh login, memulihkan, atau mengubah akun lagi setelah serah terima selesai, dan tidak boleh menautkan ulang akun ke email, nomor HP, atau perangkat miliknya.',
                'Serah terima dianggap selesai setelah email (dan nomor proteksi, bila proteksi ON) berhasil diganti dan pembeli dapat masuk ke akun.',
            ],
        ],
        [
            't' => 'Setoran uang ke pemilik',
            'items' => [
                "Bagian pemilik dikirim paling lambat {$payoutHrs} jam setelah serah terima selesai, ke rekening atau e-wallet atas nama pemilik.",
                'Pembayaran pembeli hanya lewat jalur resmi admin. Waspada pihak yang mengatasnamakan EHANZSTORE.',
                'Admin mencatat setiap setoran (tanggal dan jumlah) dan mengirim buktinya ke pemilik.',
            ],
        ],
        [
            't' => 'Garansi & tanggung jawab pemilik',
            'items' => [
                "Akun yang sudah terjual mengikuti Ketentuan Garansi EHANZSTORE ({$warranty} hari sejak serah terima).",
                'Jika setelah serah terima akun diambil kembali oleh pemilik, dipulihkan lewat recovery, atau bermasalah karena tindakan pemilik, pemilik wajib mengembalikan bagian yang sudah diterima dan dapat dikenai ganti rugi kepada pembeli.',
                'Jika terbukti ada data yang dipalsukan atau akun bukan milik sendiri, titipan dibatalkan dan pemilik dapat dimasukkan ke daftar hitam toko.',
            ],
        ],
        [
            't' => 'Pembatalan & ketentuan lain',
            'items' => [
                'Pemilik boleh membatalkan titipan sebelum ada pembeli yang membayar. Tidak ada biaya pembatalan.',
                'Toko boleh menolak atau menurunkan akun yang tidak memenuhi syarat, tanpa biaya apa pun kepada pemilik.',
                'Dengan menitipkan akun, pemilik dianggap telah membaca dan menyetujui seluruh ketentuan di halaman ini.',
            ],
        ],
    ];
@endphp

<section class="relative pt-36 sm:pt-40 pb-12 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-b from-royal/10 to-transparent pointer-events-none"></div>
    <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs font-bold tracking-wide mb-6">
            Titip Jual
        </div>
        <h1 class="font-display font-extrabold text-4xl md:text-5xl text-white mb-5">
            Syarat <span class="text-electric glow-text">Titip Akun</span> untuk Dijual
        </h1>
        <p class="text-slate-400 max-w-2xl mx-auto text-lg">Punya akun Clash of Clans yang mau dijual? Titip ke EHANZSTORE, kami yang pasarkan dan urus pembelinya.</p>
    </div>
</section>

{{-- SYARAT WAJIB UTAMA --}}
<div class="max-w-4xl mx-auto px-4 mb-8">
    <div class="rounded-3xl border border-amber-500/40 bg-gradient-to-br from-amber-500/10 to-transparent p-6 sm:p-8">
        <div class="flex items-start gap-4">
            <span class="w-12 h-12 shrink-0 rounded-2xl bg-amber-500/15 text-amber-300 flex items-center justify-center" aria-hidden="true">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </span>
            <div>
                <p class="text-[11px] font-bold tracking-widest text-amber-300 uppercase mb-1">Dua syarat wajib</p>
                <h2 class="font-display font-extrabold text-2xl text-white mb-3">1. Email akun harus bisa diganti ke Gmail pembeli</h2>
                <ul class="space-y-2.5 text-sm text-slate-300 leading-relaxed">
                    <li class="flex gap-3"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span><span>Email/Supercell ID akun harus
                        @if($maxWait === 0) <strong class="text-white">sudah bisa diganti sekarang</strong>@else <strong class="text-white">bisa diganti (maksimal menunggu {{ $maxWait }} hari)</strong>@endif
                        ke alamat Gmail milik pembeli.</span></li>
                    <li class="flex gap-3"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span><span>Akun yang emailnya tidak bisa diganti <strong class="text-white">tidak bisa dititip</strong>, apa pun alasannya, karena pembeli tidak bisa memegang akun sepenuhnya.</span></li>
                    <li class="flex gap-3"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span><span>Admin mengonfirmasi syarat ini sebelum akun ditayangkan. Berlaku untuk akun pemain; clan tidak punya email sehingga tidak terkena syarat ini.</span></li>
                </ul>

                <h2 class="font-display font-extrabold text-2xl text-white mt-6 mb-3">2. Proteksi ON: nomor proteksi harus bisa diganti ke nomor pembeli</h2>
                <ul class="space-y-2.5 text-sm text-slate-300 leading-relaxed">
                    <li class="flex gap-3"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span><span>Kalau akun memakai proteksi (nomor HP terpasang sebagai proteksi), nomor itu <strong class="text-white">harus bisa diganti ke nomor milik pembeli</strong>.</span></li>
                    <li class="flex gap-3"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span><span>Akun berproteksi yang nomornya tidak bisa diganti <strong class="text-white">tidak bisa dititip</strong>, karena nomor lama masih bisa dipakai untuk mengambil akun kembali.</span></li>
                    <li class="flex gap-3"><span class="mt-2 w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span><span>Kalau proteksi OFF, syarat ini tidak berlaku. Admin tetap mengonfirmasinya sebelum akun ditayangkan.</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto px-4 mb-12">
    <div class="glass-strong rounded-3xl p-6 grid grid-cols-3 gap-4 text-center">
        <div>
            <p class="font-display font-extrabold text-3xl sm:text-4xl text-white">Rp 0<span class="text-electric text-xl sm:text-2xl"></span></p>
            <p class="text-[11px] sm:text-xs text-slate-400 mt-1">Biaya di depan</p>
        </div>
        <div class="border-x border-white/10">
            <p class="font-display font-extrabold text-3xl sm:text-4xl text-white">{{ $commission }}<span class="text-electric text-xl sm:text-2xl">%</span></p>
            <p class="text-[11px] sm:text-xs text-slate-400 mt-1">Komisi saat laku</p>
        </div>
        <div>
            <p class="font-display font-extrabold text-3xl sm:text-4xl text-white">{{ $payoutHrs }}<span class="text-electric text-xl sm:text-2xl"> jam</span></p>
            <p class="text-[11px] sm:text-xs text-slate-400 mt-1">Setoran setelah serah terima</p>
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
    <p class="text-slate-400 mb-5">Email akunmu (dan nomor proteksinya, kalau proteksi ON) bisa diganti? Chat admin sekarang, gratis tanpa biaya di depan.</p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <a href="{{ $waTitip }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center bg-electric hover:bg-blue-500 text-white font-bold px-8 py-3.5 rounded-full transition shadow-lg shadow-electric/20">Titip Akun via WhatsApp</a>
        <a href="{{ $waAsk }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center bg-white/5 hover:bg-white/10 border border-white/10 text-white font-semibold px-8 py-3.5 rounded-full transition">Tanya Admin Dulu</a>
    </div>
</div>
@endsection