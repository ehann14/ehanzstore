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

    // Jumlah akun serupa di bawah halaman detail
    'similar_limit' => 4,

    // ------------------------------------------------------------------
    // Booking akun (DP)
    // ------------------------------------------------------------------

    // Persen DP dari harga akun saat booking
    'booking_dp_percent' => (int) env('BOOKING_DP_PERCENT', 20),

    // Batas waktu pembeli membayar DP setelah booking dibuat (jam). Lewat dari ini booking batal otomatis.
    'booking_pay_hours' => (int) env('BOOKING_PAY_HOURS', 24),

    // Batas pelunasan bawaan setelah DP diterima admin (hari). Selama masa ini akun ditahan untuk pembeli.
    'booking_hold_days' => (int) env('BOOKING_HOLD_DAYS', 7),

    // Pilihan batas pelunasan yang bisa dipilih admin (hari). Lewat batas = akun ditarik kembali, DP hangus.
    'booking_hold_options' => [7, 14],

    // Maksimal booking aktif (menunggu DP / DP diterima) per nomor WhatsApp
    'booking_max_per_phone' => (int) env('BOOKING_MAX_PER_PHONE', 2),

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

];