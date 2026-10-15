@extends('layouts.app')

@section('title', 'Ketentuan Garansi 7 Hari - EHANZSTORE.ID')
@section('description', 'Syarat dan ketentuan garansi 7 hari akun Clash of Clans EHANZSTORE.ID: cakupan garansi, pengecualian, dan cara klaim.')

@section('content')
    @php
        $waClaim = $storeWaUrl . '?text=' . rawurlencode('Halo Admin EHANZSTORE, saya mau klaim garansi akun. Kode akun: ');

        $covered = [
            ['t' => 'Akun terkena recall',            'd' => 'Pemilik lama atau pihak lain mengambil kembali akun dalam masa garansi.'],
            ['t' => 'Akun diretas dari sisi penjual', 'd' => 'Akun hilang akses karena data login lama yang bukan berasal dari kelalaian pembeli.'],
            ['t' => 'Tidak sesuai deskripsi',         'd' => 'Level TH, hero, atau spesifikasi penting lain berbeda dari yang tertulis di halaman produk.'],
            ['t' => 'Akun bermasalah dari awal',      'd' => 'Akun terkena banned atau suspend akibat kondisi sebelum serah terima.'],
            ['t' => 'Data login tidak bisa dipakai',  'd' => 'Akun tidak bisa dibuka sejak serah terima dan bukan karena kesalahan pembeli.'],
        ];

        $excluded = [
            ['t' => 'Klaim di luar 7 hari',           'd' => 'Masa garansi berakhir tepat 7 x 24 jam setelah serah terima dikonfirmasi.'],
            ['t' => 'Kelalaian pembeli',              'd' => 'Membagikan data login ke orang lain, login lewat situs/aplikasi pihak ketiga, atau data bocor dari perangkat pembeli.'],
            ['t' => 'Pelanggaran aturan game',        'd' => 'Akun diblokir karena pemakaian cheat, mod, bot, atau pelanggaran lain setelah serah terima.'],
            ['t' => 'Perubahan dari pihak game',      'd' => 'Perubahan fitur, keseimbangan, atau kebijakan dari developer game.'],
            ['t' => 'Akun dijual atau dipindahtangankan', 'd' => 'Akun sudah dijual ulang atau dialihkan ke pihak lain.'],
            ['t' => 'Berubah pikiran',                'd' => 'Bosan, tidak cocok dengan gaya bermain, atau alasan pribadi lain tanpa masalah pada akun.'],
        ];

        $claimSteps = [
            ['num' => '01', 'title' => 'Hubungi Admin',   'desc' => 'Chat admin via WhatsApp sebelum masa garansi 7 hari berakhir.'],
            ['num' => '02', 'title' => 'Kirim Bukti',     'desc' => 'Sertakan kode akun, bukti pembayaran, serta screenshot atau rekaman layar masalahnya.'],
            ['num' => '03', 'title' => 'Verifikasi',      'desc' => 'Admin memeriksa laporan maksimal 1 x 24 jam pada hari kerja.'],
            ['num' => '04', 'title' => 'Penyelesaian',    'desc' => 'Jika klaim valid, kamu mendapat akun pengganti atau refund.'],
        ];

        $faqs = [
            ['q' => 'Kapan garansi mulai dihitung?', 'a' => 'Garansi dihitung sejak admin dan pembeli sama-sama mengonfirmasi bahwa serah terima akun sudah selesai di chat WhatsApp, bukan sejak tanggal pembayaran.'],
            ['q' => 'Apa bedanya akun pengganti dan refund?', 'a' => 'Akun pengganti adalah akun dengan spesifikasi dan nilai setara akun yang dibeli. Jika belum ada akun yang setara, kamu bisa memilih refund sesuai nominal yang sudah dibayarkan.'],
            ['q' => 'Apakah garansi bisa diperpanjang?', 'a' => 'Garansi standar adalah 7 hari dan tidak otomatis diperpanjang. Jika klaim sedang diproses saat masa garansi berakhir, klaim tetap kami selesaikan selama sudah diajukan sebelum batas waktu.'],
            ['q' => 'Bagaimana jika akun bermasalah karena kesalahan saya?', 'a' => 'Garansi tidak berlaku untuk masalah akibat kelalaian pembeli. Kami tetap bisa membantu mengarahkan langkah pemulihan, tetapi tanpa jaminan penggantian.'],
            ['q' => 'Apakah akun pengganti juga bergaransi?', 'a' => 'Ya. Akun pengganti mendapat sisa masa garansi dari akun sebelumnya.'],
        ];
    @endphp

    <!-- Hero -->
    <section class="relative pt-40 pb-20 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-royal/10 to-transparent pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-green-500/10 border border-green-500/30 text-green-400 text-xs font-semibold uppercase tracking-widest mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Garansi Resmi EHANZSTORE
            </div>
            <h1 class="font-display text-4xl md:text-6xl font-bold text-white mb-6 tracking-tight">
                Garansi <span class="text-electric glow-text">7 Hari</span>
            </h1>
            <p class="text-slate-400 max-w-2xl mx-auto text-lg">
                Beli akun dengan tenang. Jika ada masalah yang memenuhi ketentuan di bawah ini, kami ganti dengan akun setara atau kami kembalikan dana kamu.
            </p>
        </div>
    </section>

    <!-- Ringkasan -->
    <div class="max-w-5xl mx-auto px-4 -mt-10 relative z-20 mb-20">
        <div class="glass-strong rounded-3xl p-6 md:p-8 border border-white/5 shadow-2xl shadow-black/50 grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
            <div>
                <p class="font-display text-4xl font-bold text-white">7 <span class="text-electric text-2xl">Hari</span></p>
                <p class="text-xs text-slate-400 mt-1">Masa garansi sejak serah terima selesai</p>
            </div>
            <div class="sm:border-x border-white/10">
                <p class="font-display text-4xl font-bold text-white">1x24 <span class="text-electric text-2xl">Jam</span></p>
                <p class="text-xs text-slate-400 mt-1">Verifikasi klaim oleh admin</p>
            </div>
            <div>
                <p class="font-display text-4xl font-bold text-white">Ganti <span class="text-electric text-2xl">/ Refund</span></p>
                <p class="text-xs text-slate-400 mt-1">Akun setara atau pengembalian dana</p>
            </div>
        </div>
    </div>

    <!-- Cakupan & Pengecualian -->
    <section class="max-w-6xl mx-auto px-4 mb-24">
        <div class="grid md:grid-cols-2 gap-6">

            <!-- Dijamin -->
            <div class="rounded-3xl p-6 md:p-8 bg-gradient-to-br from-green-900/30 to-emerald-900/20 border border-green-500/20">
                <div class="flex items-center gap-3 mb-6">
                    <span class="w-10 h-10 rounded-xl bg-green-500/10 border border-green-500/30 text-green-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <h2 class="font-display text-xl font-bold text-white">Yang Dijamin</h2>
                </div>
                <ul class="space-y-5">
                    @foreach($covered as $item)
                        <li class="flex gap-3">
                            <svg class="w-5 h-5 text-green-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <div>
                                <p class="text-white font-semibold text-sm">{{ $item['t'] }}</p>
                                <p class="text-slate-400 text-sm leading-relaxed">{{ $item['d'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Tidak dijamin -->
            <div class="rounded-3xl p-6 md:p-8 bg-gradient-to-br from-red-900/25 to-rose-900/10 border border-red-500/20">
                <div class="flex items-center gap-3 mb-6">
                    <span class="w-10 h-10 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </span>
                    <h2 class="font-display text-xl font-bold text-white">Yang Tidak Dijamin</h2>
                </div>
                <ul class="space-y-5">
                    @foreach($excluded as $item)
                        <li class="flex gap-3">
                            <svg class="w-5 h-5 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            <div>
                                <p class="text-white font-semibold text-sm">{{ $item['t'] }}</p>
                                <p class="text-slate-400 text-sm leading-relaxed">{{ $item['d'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <!-- Cara Klaim -->
    <section class="max-w-6xl mx-auto px-4 mb-24">
        <h2 class="font-display text-3xl font-bold text-white text-center mb-3">Cara Klaim Garansi</h2>
        <p class="text-slate-400 text-center mb-12">Prosesnya singkat dan semuanya lewat WhatsApp.</p>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($claimSteps as $step)
                <div class="glass rounded-2xl p-6 border border-white/5 hover:border-electric/30 transition-all duration-300 hover:-translate-y-1">
                    <span class="w-10 h-10 rounded-full bg-midnight border-2 border-electric flex items-center justify-center text-electric font-display font-bold text-sm shadow-[0_0_15px_rgba(59,130,246,0.4)] mb-4">{{ $step['num'] }}</span>
                    <h3 class="font-display font-bold text-white text-lg mb-2">{{ $step['title'] }}</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Bentuk Penyelesaian -->
    <section class="max-w-5xl mx-auto px-4 mb-24">
        <div class="glass-strong rounded-3xl p-8 md:p-10 border border-white/5">
            <h2 class="font-display text-2xl font-bold text-white mb-6 text-center">Bentuk Penyelesaian Klaim</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="rounded-2xl bg-white/[0.03] border border-white/5 p-6">
                    <p class="text-electric font-display font-bold mb-2">1. Akun Pengganti</p>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Kami carikan akun dengan spesifikasi dan nilai yang setara dengan akun yang kamu beli. Akun pengganti mendapat sisa masa garansi akun sebelumnya.
                    </p>
                </div>
                <div class="rounded-2xl bg-white/[0.03] border border-white/5 p-6">
                    <p class="text-electric font-display font-bold mb-2">2. Refund</p>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Jika belum ada akun yang setara atau kamu memilih tidak melanjutkan, dana dikembalikan sesuai nominal yang sudah dibayarkan ke rekening atau e-wallet asal pembayaran.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Syarat Umum -->
    <section class="max-w-3xl mx-auto px-4 mb-24">
        <h2 class="font-display text-3xl font-bold text-white text-center mb-3">Syarat Umum</h2>
        <p class="text-slate-400 text-center mb-10">Harap dibaca sebelum melakukan pembelian.</p>

        <ol class="glass rounded-2xl border border-white/5 p-6 md:p-8 space-y-4 text-sm text-slate-300 leading-relaxed list-decimal list-inside marker:text-electric marker:font-bold">
            <li>Garansi hanya berlaku untuk pembelian resmi melalui admin EHANZSTORE.ID di WhatsApp.</li>
            <li>Masa garansi 7 x 24 jam dihitung sejak admin mengonfirmasi serah terima selesai di chat.</li>
            <li>Klaim harus diajukan sebelum masa garansi berakhir, dengan bukti berupa kode akun, bukti pembayaran, dan screenshot atau rekaman layar.</li>
            <li>Segera setelah menerima akun, pembeli wajib mengamankannya dengan mengganti kata sandi dan email, serta tidak membagikan data login kepada siapa pun.</li>
            <li>Simpan seluruh riwayat chat WhatsApp sebagai bukti transaksi dan serah terima.</li>
            <li>Keputusan akhir atas valid atau tidaknya klaim ada pada admin berdasarkan hasil pemeriksaan, dan akan dijelaskan alasannya kepada pembeli.</li>
            <li>EHANZSTORE.ID berhak menolak klaim yang terbukti tidak benar atau mengandung manipulasi bukti.</li>
            <li>Ketentuan ini dapat diperbarui sewaktu-waktu. Ketentuan yang berlaku adalah yang tercantum di halaman ini pada saat pembelian.</li>
        </ol>
    </section>

    <!-- FAQ -->
    <section class="max-w-3xl mx-auto px-4 pb-20">
        <h2 class="font-display text-3xl font-bold text-white text-center mb-12">Pertanyaan Seputar Garansi</h2>

        <div class="space-y-4">
            @foreach($faqs as $faq)
                <details class="group glass rounded-2xl border border-white/5 open:border-electric/30 transition-all duration-300">
                    <summary class="flex justify-between items-center p-6 cursor-pointer list-none font-semibold text-white hover:text-electric transition-colors">
                        {{ $faq['q'] }}
                        <span class="transition-transform duration-300 group-open:rotate-180 shrink-0 ml-4">
                            <svg class="w-5 h-5 text-slate-500 group-open:text-electric" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </span>
                    </summary>
                    <div class="px-6 pb-6 text-sm text-slate-400 leading-relaxed border-t border-white/5 pt-4">
                        {{ $faq['a'] }}
                    </div>
                </details>
            @endforeach
        </div>

        <!-- CTA -->
        <div class="mt-16 text-center">
            <p class="text-slate-400 mb-6">Ada masalah dengan akun yang masih dalam masa garansi?</p>
            <a href="{{ $waClaim }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-500 text-white px-8 py-4 rounded-full font-bold transition-all shadow-lg shadow-green-900/30 hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                Klaim Garansi via WhatsApp
            </a>
        </div>
    </section>
@endsection