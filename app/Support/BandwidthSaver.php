<?php

namespace App\Support;

use App\Models\StoreSetting;
use Throwable;

/**
 * "Mode Hemat Bandwidth": memperlambat polling otomatis di panel admin.
 *
 * Dashboard Super Admin mengunduh ulang seluruh halaman HTML tiap 20 detik dan lonceng
 * notifikasi menembak server tiap 20 detik selama tab terbuka. Satu tab yang dibiarkan
 * semalaman bisa menghabiskan ratusan MB. Mode ini (bawaan: AKTIF) membuat interval lebih jarang.
 */
class BandwidthSaver
{
    public const KEY    = 'bandwidth_saver';
    public const FACTOR = 6;

    protected static ?bool $cache = null;

    public static function enabled(): bool
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        try {
            return static::$cache = StoreSetting::get(self::KEY, '1') !== '0';
        } catch (Throwable $e) {
            return static::$cache = true;
        }
    }

    public static function set(bool $on): void
    {
        StoreSetting::set(self::KEY, $on ? '1' : '0');
        static::$cache = $on;
    }

    /** Interval polling (ms) setelah memperhitungkan mode hemat. */
    public static function pollMs(int $normalMs): int
    {
        return static::enabled() ? $normalMs * self::FACTOR : $normalMs;
    }
}