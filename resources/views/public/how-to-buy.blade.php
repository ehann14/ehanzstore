@extends('layouts.app')

@section('title', 'Cara Membeli Akun - EHANZSTORE.ID')

@section('content')
    <!-- Hero Section -->
    <section class="bg-brand-navy pt-32 pb-20 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
            <div class="absolute top-20 right-20 w-64 h-64 bg-blue-400 rounded-full blur-3xl"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 text-center relative z-10">
            <span class="inline-block py-1 px-3 rounded-full bg-white/10 text-blue-200 text-xs font-semibold mb-6 border border-white/20 backdrop-blur-sm">
                Panduan Transaksi Aman
            </span>
            <h1 class="text-3xl md:text-5xl font-bold text-white mb-6 leading-tight">
                Cara Membeli Akun COC <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 to-white">di EHANZSTORE.ID</span>
            </h1>
            <p class="text-lg text-blue-100 max-w-2xl mx-auto font-light">
                Proses pembelian dirancang sederhana, aman, dan transparan. Ikuti 4 langkah mudah berikut untuk mendapatkan akun impianmu.
            </p>
        </div>
    </section>

    <!-- Steps Section -->
    <section class="py-20 max-w-5xl mx-auto px-4 -mt-10 relative z-20">
        <div class="grid md:grid-cols-2 gap-8">
            
            <!-- Step 1 -->
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition group">
                <div class="w-14 h-14 bg-brand/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand group-hover:text-white transition-colors text-brand">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">1. Pilih Akun Idaman</h3>
                <p class="text-gray-600 leading-relaxed text-sm">
                    Jelajahi katalog kami dan gunakan filter Town Hall atau harga untuk menemukan akun yang sesuai budget dan kebutuhanmu. Klik "Lihat Detail" untuk spesifikasi lengkap.
                </p>
            </div>

            <!-- Step 2 -->
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition group">
                <div class="w-14 h-14 bg-brand/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand group-hover:text-white transition-colors text-brand">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">2. Hubungi Admin via WA</h3>
                <p class="text-gray-600 leading-relaxed text-sm">
                    Klik tombol "Beli via WhatsApp" di halaman detail akun. Kamu akan diarahkan ke chat admin dengan pesan otomatis berisi kode akun. Konfirmasi ketersediaan dan minta foto/video real-time jika perlu.
                </p>
            </div>

            <!-- Step 3 -->
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition group">
                <div class="w-14 h-14 bg-brand/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand group-hover:text-white transition-colors text-brand">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">3. Lakukan Pembayaran</h3>
                <p class="text-gray-600 leading-relaxed text-sm">
                    Setelah deal, admin akan mengirimkan nomor rekening/e-wallet. Lakukan pembayaran sesuai nominal yang disepakati. Kirimkan bukti transfer ke chat WhatsApp untuk verifikasi.
                </p>
            </div>

            <!-- Step 4 -->
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition group">
                <div class="w-14 h-14 bg-brand/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-brand group-hover:text-white transition-colors text-brand">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">4. Serah Terima Akun</h3>
                <p class="text-gray-600 leading-relaxed text-sm">
                    Setelah pembayaran terkonfirmasi, admin akan mengirimkan data login (email & password). Segera ganti password dan aktifkan 2FA untuk keamanan maksimal. Garansi berlaku sesuai ketentuan.
                </p>
            </div>

        </div>

        <!-- CTA Box -->
        <div class="mt-16 bg-brand-navy rounded-3xl p-8 md:p-12 text-center relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-brand-dark to-brand opacity-90"></div>
            <div class="relative z-10">
                <h3 class="text-2xl md:text-3xl font-bold text-white mb-4">Masih Bingung atau Punya Pertanyaan?</h3>
                <p class="text-blue-100 mb-8 max-w-xl mx-auto">Tim kami siap membantu kamu 24/7. Jangan ragu untuk konsultasi gratis sebelum memutuskan membeli.</p>
                <a href="https://wa.me/6282130559126?text=Halo%20Admin%20EHANZSTORE%2C%20saya%20ingin%20bertanya%20tentang%20cara%20membeli%20akun." 
                   target="_blank"
                   class="inline-flex items-center gap-2 bg-white text-brand-navy px-8 py-4 rounded-full font-bold hover:bg-blue-50 transition shadow-xl">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    Chat Admin Sekarang
                </a>
            </div>
        </div>
    </section>

    <!-- FAQ Mini Section -->
    <section class="py-16 max-w-3xl mx-auto px-4">
        <h2 class="text-2xl font-bold text-center text-brand-navy mb-10">Pertanyaan Umum</h2>
        <div class="space-y-4">
            <details class="group bg-white rounded-xl border border-gray-200 open:border-brand/30 transition">
                <summary class="flex justify-between items-center p-6 cursor-pointer list-none font-semibold text-gray-800">
                    Apakah akun yang dijual asli dan bukan hasil hack?
                    <span class="transition group-open:rotate-180">
                        <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                    </span>
                </summary>
                <div class="text-gray-600 px-6 pb-6 text-sm leading-relaxed">
                    Ya, 100% akun original. Kami hanya menjual akun yang dikembangkan secara legal. Setiap akun telah melalui proses verifikasi internal sebelum dipublikasikan di katalog.
                </div>
            </details>

            <details class="group bg-white rounded-xl border border-gray-200 open:border-brand/30 transition">
                <summary class="flex justify-between items-center p-6 cursor-pointer list-none font-semibold text-gray-800">
                    Bagaimana sistem garansinya?
                    <span class="transition group-open:rotate-180">
                        <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                    </span>
                </summary>
                <div class="text-gray-600 px-6 pb-6 text-sm leading-relaxed">
                    Kami memberikan garansi personal selama 7 hari setelah serah terima. Jika akun terkena recall atau masalah kepemilikan dalam periode tersebut, kami akan mengganti dengan akun setara atau refund penuh.
                </div>
            </details>

            <details class="group bg-white rounded-xl border border-gray-200 open:border-brand/30 transition">
                <summary class="flex justify-between items-center p-6 cursor-pointer list-none font-semibold text-gray-800">
                    Metode pembayaran apa saja yang diterima?
                    <span class="transition group-open:rotate-180">
                        <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                    </span>
                </summary>
                <div class="text-gray-600 px-6 pb-6 text-sm leading-relaxed">
                    Kami menerima transfer bank (BCA, BRI, Mandiri, BNI) dan e-wallet (DANA, OVO, GoPay, ShopeePay). Detail rekening akan dikirimkan via WhatsApp setelah kesepakatan harga.
                </div>
            </details>
        </div>
    </section>
@endsection