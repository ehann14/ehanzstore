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

    // Batas pelunasan setelah DP diterima admin (hari). Selama masa ini akun ditahan untuk pembeli.
    'booking_hold_days' => (int) env('BOOKING_HOLD_DAYS', 3),

    // Maksimal booking aktif (menunggu DP / DP diterima) per nomor WhatsApp
    'booking_max_per_phone' => (int) env('BOOKING_MAX_PER_PHONE', 2),

];