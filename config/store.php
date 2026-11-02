<?php

return [

    // Akun tersedia yang belum laku lebih dari sekian hari dianggap "stok lama"
    'stale_days' => (int) env('STALE_STOCK_DAYS', 30),

    // Saran penurunan harga berdasarkan lama akun belum laku: [min hari => persen turun]
    // Dicek dari yang terbesar. Di bawah ambang terkecil dipakai persen terkecil.
    'stale_price_tiers' => [
        90 => 15,
        60 => 10,
        30 => 5,
    ],

    // Jam ramai (WIB, format HH:MM): jadi tombol cepat di pemilih jam jadwal tayang akun
    'peak_hours' => ['12:00', '19:00', '20:00', '21:00'],

    // Etalase akun terjual: akun terjual yang sudah terbit tetap tampil di katalog & beranda
    // (sebagai bukti sosial) walau garansinya sudah habis. Yang tampil hanya N terjual terbaru;
    // bila lebih dari itu, yang terlama otomatis disembunyikan. Sembunyikan manual: tombol
    // "Sembunyikan" di tab Akun Terjual (akun draft tidak dihitung).
    'sold_showcase_limit' => (int) env('SOLD_SHOWCASE_LIMIT', 15),

    // Jumlah akun serupa di bawah halaman detail
    'similar_limit' => 4,

    // ------------------------------------------------------------------
    // Booking akun (DP)
    // ------------------------------------------------------------------

    // Persen DP dari harga akun saat booking
    'booking_dp_percent' => (int) env('BOOKING_DP_PERCENT', 30),

    // Batas waktu pembeli membayar DP setelah booking dibuat (jam). Lewat dari ini booking batal otomatis.
    'booking_pay_hours' => (int) env('BOOKING_PAY_HOURS', 24),

    // Batas pelunasan bawaan setelah DP diterima admin (hari). Selama masa ini akun sudah bisa dimainkan pembeli, tapi data akun (email, nomor proteksi) belum diganti sampai lunas.
    'booking_hold_days' => (int) env('BOOKING_HOLD_DAYS', 7),

    // Pilihan batas pelunasan yang bisa dipilih admin (hari). Lewat batas = akun ditarik kembali, DP hangus.
    'booking_hold_options' => [7, 14],

    // Maksimal booking aktif (menunggu DP / DP diterima) per nomor WhatsApp
    'booking_max_per_phone' => (int) env('BOOKING_MAX_PER_PHONE', 2),

    // ------------------------------------------------------------------
    // Garansi bertingkat
    //   7 hari  = email akun SUDAH diganti ke Gmail pembeli
    //  14 hari  = email akun BELUM diganti ke Gmail pembeli
    //  21 hari  = Garansi Plus, pembeli membayar biaya tambahan di bawah ini
    // ------------------------------------------------------------------
    'warranty_plus_fee' => (int) env('WARRANTY_PLUS_FEE', 5000),

    // ------------------------------------------------------------------
    // Titip akun (konsinyasi): angka yang tampil di halaman syarat & dipakai validasi admin
    // ------------------------------------------------------------------

    // Komisi toko bawaan yang ditampilkan di halaman syarat (persen dari harga jual). Besar komisi
    // per akun tetap diisi admin di form akun dan disepakati lewat WhatsApp sebelum listing.
    'consign_commission_percent' => (int) env('CONSIGN_COMMISSION_PERCENT', 15),

    // Syarat WAJIB: email akun harus bisa diganti ke Gmail pembeli. Nilai ini = batas maksimal hari
    // tunggu sebelum email bisa diganti. 0 = email harus SUDAH bisa diganti sekarang.
    'consign_max_email_wait_days' => (int) env('CONSIGN_MAX_EMAIL_WAIT_DAYS', 0),

    // Pemilik wajib mengganti email ke Gmail pembeli paling lambat sekian jam setelah akun laku.
    'consign_handover_hours' => (int) env('CONSIGN_HANDOVER_HOURS', 24),

    // Setoran bagian pemilik dikirim paling lambat sekian jam setelah serah terima selesai.
    'consign_payout_hours' => (int) env('CONSIGN_PAYOUT_HOURS', 24),

    // Lama masa titip. Lewat dari ini pemilik boleh menarik akun atau memperpanjang titipan (hari).
    'consign_listing_days' => (int) env('CONSIGN_LISTING_DAYS', 30),

    // ------------------------------------------------------------------
    // Social proof di halaman detail akun (angka nyata dari tabel visitor_events)
    // ------------------------------------------------------------------
    'social_proof' => [
        // Matikan semua tampilan social proof dengan SOCIAL_PROOF_ENABLED=false
        'enabled' => (bool) env('SOCIAL_PROOF_ENABLED', true),

        // "Dilihat N orang hari ini": baru tampil bila pengunjung unik hari ini >= angka ini
        'min_views_today' => (int) env('SOCIAL_PROOF_MIN_VIEWS', 3),

        // "N orang lain sedang melihat": pengunjung lain dalam sekian menit terakhir
        'live_window_minutes' => (int) env('SOCIAL_PROOF_LIVE_MINUTES', 5),
        'min_live'            => (int) env('SOCIAL_PROOF_MIN_LIVE', 1),

        // "N orang menanyakan": yang menekan tombol Beli via WhatsApp dalam sekian jam terakhir
        'inquiry_window_hours' => (int) env('SOCIAL_PROOF_INQUIRY_HOURS', 6),
        'min_inquiries'        => (int) env('SOCIAL_PROOF_MIN_INQUIRIES', 1),
    ],

    // ------------------------------------------------------------------
    // Jam operasional toko (WIB, format 24 jam "H:i").
    // Dipakai badge Buka/Tutup dan tombol WhatsApp melayang.
    // ------------------------------------------------------------------
    'operating_hours' => [
        'open'  => env('STORE_OPEN_TIME', '06:00'),
        'close' => env('STORE_CLOSE_TIME', '22:00'),
    ],

];