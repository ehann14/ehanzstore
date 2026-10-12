@extends('layouts.app')

@section('title', 'Cara Membeli Akun COC Aman - EHANZSTORE.ID')

@section('content')
    <!-- Hero Section -->
    <section class="relative pt-40 pb-20 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-b from-royal/10 to-transparent pointer-events-none"></div>
        <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
            <h1 class="font-display text-4xl md:text-6xl font-bold text-white mb-6 tracking-tight">
                Transaksi Aman & <span class="text-electric glow-text">Terjamin</span>
            </h1>
            <p class="text-slate-400 max-w-2xl mx-auto text-lg">
                Kami mengutamakan keamanan setiap transaksi. Ikuti panduan sederhana ini untuk mendapatkan akun COC impianmu tanpa was-was.
            </p>
        </div>
    </section>

    <!-- Timeline Steps -->
    <div class="max-w-4xl mx-auto px-4 -mt-10 relative z-20 mb-24">
        <div class="glass-strong rounded-3xl p-8 md:p-12 border border-white/5 shadow-2xl shadow-black/50">
            
            <div class="space-y-12 relative before:absolute before:left-[19px] before:top-4 before:bottom-4 before:w-0.5 before:bg-white/10 md:before:left-1/2 md:before:-translate-x-1/2">
                
                @php
                    $steps = [
                        [
                            'num' => '01', 
                            'title' => 'Pilih Akun & Cek Spesifikasi', 
                            'desc' => 'Jelajahi katalog dan gunakan filter TH/Harga. Baca detail spesifikasi, status proteksi, dan kelengkapan hero di halaman produk.',
                            'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'
                        ],
                        [
                            'num' => '02', 
                            'title' => 'Konfirmasi Ketersediaan via WA', 
                            'desc' => 'Klik tombol "Beli via WhatsApp". Pesan otomatis akan terkirim ke admin. Tanyakan foto/video real-time jika diperlukan sebelum deal.',
                            'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'
                        ],
                        [
                            'num' => '03', 
                            'title' => 'Pembayaran & Verifikasi', 
                            'desc' => 'Transfer sesuai nominal ke rekening/e-wallet resmi EHANZSTORE. Kirim bukti transfer ke chat WA. Admin akan memverifikasi dalam 5-15 menit.',
                            'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'
                        ],
                        [
                            'num' => '04', 
                            'title' => 'Serah Terima & Garansi Aktif', 
                            'desc' => 'Data login dikirim via WA. Segera ganti password & aktifkan 2FA. Garansi personal 7 hari berlaku sejak serah terima selesai.',
                            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                        ]
                    ];
                @endphp

                @foreach($steps as $index => $step)
                <div class="relative flex items-start md:justify-between {{ $index % 2 == 0 ? 'md:flex-row-reverse' : '' }} group">
                    
                    <!-- Number Bubble -->
                    <div class="absolute left-0 md:left-1/2 md:-translate-x-1/2 w-10 h-10 rounded-full bg-midnight border-2 border-electric flex items-center justify-center z-10 shadow-[0_0_15px_rgba(59,130,246,0.4)]">
                        <span class="text-electric font-display font-bold text-sm">{{ $step['num'] }}</span>
                    </div>

                    <!-- Content Card -->
                    <div class="ml-16 md:ml-0 md:w-[calc(50%-40px)] glass rounded-2xl p-6 border border-white/5 hover:border-electric/30 transition-all duration-300 group-hover:-translate-y-1">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 rounded-lg bg-electric/10 text-electric">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"></path></svg>
                            </div>
                            <h3 class="font-display font-bold text-white text-lg">{{ $step['title'] }}</h3>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed">{{ $step['desc'] }}</p>
                    </div>
                </div>
                @endforeach

            </div>
        </div>
    </div>

    <!-- Guarantee Banner -->
    <div class="max-w-5xl mx-auto px-4 mb-24">
        <div class="bg-gradient-to-r from-green-900/40 to-emerald-900/40 border border-green-500/20 rounded-3xl p-8 md:p-10 flex flex-col md:flex-row items-center gap-8 backdrop-blur-sm">
            <div class="shrink-0 w-20 h-20 rounded-full bg-green-500/10 flex items-center justify-center border border-green-500/30">
                <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            </div>
            <div class="text-center md:text-left flex-1">
                <h3 class="font-display text-2xl font-bold text-white mb-2">Garansi Personal 7 Hari</h3>
                <p class="text-slate-300 text-sm leading-relaxed">
                    Jika akun terkena recall, hacked, atau tidak sesuai deskripsi dalam 7 hari setelah serah terima, kami akan mengganti dengan akun setara atau refund penuh. Syarat & ketentuan berlaku. Simpan bukti chat WA sebagai klaim garansi.
                </p>
            </div>
        </div>
    </div>

    <!-- FAQ Section -->
    <section class="max-w-3xl mx-auto px-4 pb-20">
        <h2 class="font-display text-3xl font-bold text-white text-center mb-12">Pertanyaan Umum</h2>
        
        <div class="space-y-4">
            @php
                $faqs = [
                    ['q' => 'Apakah akun ini asli atau hasil hack/cheat?', 'a' => '100% akun original yang dikembangkan secara legal. Kami tidak menjual akun hasil cheat/hack karena berisiko banned permanen. Setiap akun telah diverifikasi sebelum listing.'],
                    ['q' => 'Metode pembayaran apa saja yang diterima?', 'a' => 'Kami menerima Transfer Bank (BCA, BRI, Mandiri, BNI) dan E-Wallet (DANA, OVO, GoPay, ShopeePay). Detail rekening hanya diberikan via WhatsApp setelah kesepakatan harga.'],
                    ['q' => 'Bagaimana jika saya salah transfer atau bayar kurang?', 'a' => 'Hubungi admin segera via WhatsApp dengan menyertakan bukti transfer. Kami akan membantu mencocokkan mutasi. Kelebihan pembayaran akan dikembalikan atau dijadikan saldo untuk pembelian berikutnya.'],
                    ['q' => 'Apakah bisa COD atau bayar di tempat?', 'a' => 'Saat ini kami hanya melayani pembayaran DP/Full Payment via transfer untuk keamanan kedua belah pihak. Sistem COD belum tersedia karena risiko tinggi pada bisnis digital.'],
                    ['q' => 'Berapa lama proses serah terima setelah bayar?', 'a' => 'Normalnya 5-15 menit pada jam kerja (09.00 - 22.00 WIB). Di luar jam operasional, maksimal 1x24 jam. Akun prioritas akan diproses lebih cepat.']
                ];
            @endphp

            @foreach($faqs as $faq)
            <details class="group glass rounded-2xl border border-white/5 open:border-electric/30 transition-all duration-300">
                <summary class="flex justify-between items-center p-6 cursor-pointer list-none font-semibold text-white hover:text-electric transition-colors">
                    {{ $faq['q'] }}
                    <span class="transition-transform duration-300 group-open:rotate-180 shrink-0 ml-4">
                        <svg class="w-5 h-5 text-slate-500 group-open:text-electric" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </span>
                </summary>
                <div class="px-6 pb-6 text-sm text-slate-400 leading-relaxed border-t border-white/5 pt-4">
                    {{ $faq['a'] }}
                </div>
            </details>
            @endforeach
        </div>

        <!-- Final CTA -->
        <div class="mt-16 text-center">
            <p class="text-slate-400 mb-6">Masih punya pertanyaan lain?</p>
            <a href="https://wa.me/6282130559126?text=Halo%20Admin%2C%20saya%20mau%20tanya%20tentang%20cara%20beli%20akun%20COC." 
               target="_blank"
               class="inline-flex items-center gap-2 bg-electric hover:bg-blue-500 text-white px-8 py-4 rounded-full font-bold transition-all shadow-lg shadow-electric/20 hover:shadow-electric/40 hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/></svg>
                Chat Admin Sekarang
            </a>
        </div>
    </section>
@endsection