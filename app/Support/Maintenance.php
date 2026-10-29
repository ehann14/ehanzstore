<?php

namespace App\Support;

use App\Models\StoreSetting;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Mode maintenance berbasis database (tabel store_settings), bisa dinyalakan
 * dari panel Super Admin tanpa SSH / "php artisan down".
 *
 * Kunci yang dipakai di store_settings:
 *   maintenance_mode, maintenance_title, maintenance_message,
 *   maintenance_until, maintenance_image (path relatif, mis. "maintenance/xxx.webp")
 */
class Maintenance
{
    public const DEFAULT_TITLE   = 'Sedang Maintenance';
    public const DEFAULT_MESSAGE = 'Kami sedang melakukan perawatan sistem supaya belanja kamu makin nyaman dan aman. Website akan segera kembali normal.';

    protected static ?array $cache = null;

    /** Semua pengaturan maintenance (satu query per request). */
    public static function settings(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        $raw = [];

        try {
            $raw = StoreSetting::where('key', 'like', 'maintenance\_%')->pluck('value', 'key')->all();
        } catch (Throwable $e) {
            // Tabel belum ada / database bermasalah: anggap maintenance mati supaya situs tidak terkunci.
        }

        $until = null;

        if (! empty($raw['maintenance_until'])) {
            try {
                $until = Carbon::parse($raw['maintenance_until']);
            } catch (Throwable $e) {
                $until = null;
            }
        }

        // Gambar kustom hanya diterima bila berada di folder uploads/maintenance.
        $image = Uploads::normalize($raw['maintenance_image'] ?? '');

        if (! preg_match('#^maintenance/[^/]+$#', $image)) {
            $image = '';
        }

        return static::$cache = [
            'active'    => ($raw['maintenance_mode'] ?? '0') === '1',
            'title'     => trim((string) ($raw['maintenance_title'] ?? '')) ?: self::DEFAULT_TITLE,
            'message'   => trim((string) ($raw['maintenance_message'] ?? '')) ?: self::DEFAULT_MESSAGE,
            'until'     => $until,
            'image'     => $image,
            'image_url' => $image !== '' ? '/uploads/' . $image : '',
        ];
    }

    public static function isActive(): bool
    {
        return static::settings()['active'];
    }

    public static function forget(): void
    {
        static::$cache = null;
    }

    /**
     * Simpan pengaturan. $until berupa waktu WIB (atau null).
     *
     * $image: null = gambar tidak diubah, '' = hapus gambar kustom (kembali ke ilustrasi bawaan),
     * "maintenance/xxx.webp" = pakai gambar baru.
     */
    public static function save(bool $active, ?string $title, ?string $message, ?Carbon $until, ?string $image = null): void
    {
        StoreSetting::set('maintenance_mode', $active ? '1' : '0');
        StoreSetting::set('maintenance_title', (string) $title);
        StoreSetting::set('maintenance_message', (string) $message);
        StoreSetting::set('maintenance_until', $until ? $until->clone()->utc()->toIso8601String() : '');

        if ($image !== null) {
            StoreSetting::set('maintenance_image', $image);
        }

        static::forget();
    }
}