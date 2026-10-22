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

];