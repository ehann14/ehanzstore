<?php

namespace App\Support;

use App\Models\StoreSetting;
use Throwable;

/**
 * Pengaturan pembayaran yang tampil di halaman booking pembeli: gambar QRIS + catatan.
 * Disimpan di tabel store_settings (kunci payment_qris_image, payment_qris_note).
 */
class PaymentSettings
{
    public const KEY_IMAGE = 'payment_qris_image';
    public const KEY_NOTE  = 'payment_qris_note';
    public const DIR       = 'payment';

    protected static ?array $cache = null;

    /** @return array{image:string,image_url:string,note:string} */
    public static function qris(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        $image = '';
        $note  = '';

        try {
            $image = Uploads::normalize((string) StoreSetting::get(self::KEY_IMAGE, ''));
            $note  = trim((string) StoreSetting::get(self::KEY_NOTE, ''));
        } catch (Throwable $e) {
            // Tabel/DB bermasalah: halaman booking tetap tampil tanpa QRIS.
        }

        // Hanya gambar di folder uploads/payment yang diterima.
        if (! preg_match('#^' . self::DIR . '/[^/]+$#', $image)) {
            $image = '';
        }

        return static::$cache = [
            'image'     => $image,
            'image_url' => $image !== '' ? '/uploads/' . $image : '',
            'note'      => $note,
        ];
    }

    /** Simpan. $image: null = tidak berubah, '' = hapus, path = gambar baru. */
    public static function save(?string $image, string $note): void
    {
        if ($image !== null) {
            StoreSetting::set(self::KEY_IMAGE, $image);
        }

        StoreSetting::set(self::KEY_NOTE, $note);
        static::$cache = null;
    }
}