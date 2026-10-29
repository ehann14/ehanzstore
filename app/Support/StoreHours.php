<?php

namespace App\Support;

use App\Models\Sale;
use Illuminate\Support\Carbon;

/**
 * Jam operasional toko (WIB, format 24 jam). Jam buka dan tutup diatur di
 * config/store.php (operating_hours), bawaan 06:00 sampai 22:00 WIB.
 * Dipakai badge "Buka / Tutup" dan tombol WhatsApp melayang.
 */
class StoreHours
{
    private const DEFAULT_OPEN  = '06:00';
    private const DEFAULT_CLOSE = '22:00';

    /** "H:i" -> menit sejak tengah malam. Nilai tidak valid diganti bawaan. */
    private static function minutes(?string $value, string $fallback): int
    {
        foreach ([$value, $fallback] as $candidate) {
            if (preg_match('/^(\d{1,2}):(\d{2})$/', trim((string) $candidate), $m)
                && (int) $m[1] <= 23 && (int) $m[2] <= 59) {
                return (int) $m[1] * 60 + (int) $m[2];
            }
        }

        return 0;
    }

    /** 360 -> "06.00" */
    private static function clock(int $minutes): string
    {
        return sprintf('%02d.%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /** Rentang jam untuk teks, mis. "06.00 - 22.00 WIB". */
    public static function rangeLabel(): string
    {
        [$open, $close] = self::bounds();

        return self::clock($open) . ' - ' . self::clock($close) . ' WIB';
    }

    /** @return array{0:int,1:int} menit buka dan menit tutup */
    private static function bounds(): array
    {
        return [
            self::minutes(config('store.operating_hours.open'), self::DEFAULT_OPEN),
            self::minutes(config('store.operating_hours.close'), self::DEFAULT_CLOSE),
        ];
    }

    /** Status toko pada waktu tertentu (bawaan: sekarang), dihitung menurut WIB. */
    public static function status(?Carbon $at = null): array
    {
        [$open, $close] = self::bounds();

        $now     = ($at ?? now())->clone()->timezone(Sale::DISPLAY_TZ);
        $minutes = $now->hour * 60 + $now->minute;

        // Jam tutup lebih kecil dari jam buka berarti buka melewati tengah malam.
        $isOpen = $open <= $close
            ? ($minutes >= $open && $minutes < $close)
            : ($minutes >= $open || $minutes < $close);

        $noteOpen   = 'sampai ' . self::clock($close) . ' WIB';
        $noteClosed = 'buka lagi ' . self::clock($open) . ' WIB';

        return [
            'open'        => $isOpen,
            'label'       => $isOpen ? 'Buka' : 'Tutup',
            'range'       => self::rangeLabel(),
            'note_open'   => $noteOpen,
            'note_closed' => $noteClosed,
            'note'        => $isOpen ? $noteOpen : $noteClosed,
            'open_min'    => $open,
            'close_min'   => $close,
        ];
    }
}