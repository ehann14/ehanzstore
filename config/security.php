<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batas percobaan login
    |--------------------------------------------------------------------------
    | max_attempts    : gagal berturut-turut per email+IP sebelum diblokir sementara
    | ip_max_attempts : gagal total dari satu IP (semua email) sebelum diblokir
    | decay_seconds   : lama blokir / jendela hitungan, dalam detik
    */
    'login' => [
        'max_attempts'    => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'ip_max_attempts' => (int) env('LOGIN_IP_MAX_ATTEMPTS', 20),
        'decay_seconds'   => (int) env('LOGIN_DECAY_SECONDS', 900),
    ],

    /*
    |--------------------------------------------------------------------------
    | Verifikasi dua langkah (2FA) Super Admin
    |--------------------------------------------------------------------------
    | required      : true = Super Admin wajib mengaktifkan 2FA sebelum memakai panel
    | issuer        : nama yang tampil di aplikasi authenticator
    | challenge_ttl : batas waktu mengisi kode setelah password benar (detik)
    | max_attempts  : kode salah berturut-turut sebelum login diulang
    | decay_seconds : lama blokir kode salah (detik)
    */
    'two_factor' => [
        'required'      => (bool) env('TWO_FACTOR_REQUIRED', false),
        'issuer'        => (string) env('TWO_FACTOR_ISSUER', env('APP_NAME', 'EHANZSTORE.ID')),
        'challenge_ttl' => (int) env('TWO_FACTOR_CHALLENGE_TTL', 300),
        'max_attempts'  => (int) env('TWO_FACTOR_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('TWO_FACTOR_DECAY_SECONDS', 900),
    ],

];