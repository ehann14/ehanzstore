<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Perlindungan spam untuk form publik yang membunyikan lonceng admin (ulasan & booking).
 *
 * Dua lapis, keduanya ringan dan tidak mengganggu pembeli asli:
 *  1. Honeypot: kolom jebakan yang disembunyikan dari manusia. Bot biasanya mengisi semua kolom.
 *  2. Throttle per IP: batas jumlah kiriman per jam dan per hari. Ini tambahan di atas
 *     middleware throttle per menit di routes/web.php (batas burst).
 *
 * Penghitung disimpan di cache (database), jadi tetap berlaku antar request.
 */
class SpamGuard
{
    /** Nama kolom jebakan. Sama dengan form titip penjual supaya konsisten. */
    public const HONEYPOT = 'website';

    /**
     * Batas kiriman per IP. Format: [maksimal kiriman, jendela waktu dalam detik].
     * Bisa diubah lewat config/store.php bila perlu (kunci spam_limits.<form>).
     */
    protected const DEFAULT_LIMITS = [
        'booking' => [[3, 3600], [8, 86400]],
        'review'  => [[3, 3600], [6, 86400]],
    ];

    /** Kolom jebakan terisi = hampir pasti bot. */
    public static function honeypotTripped(Request $request): bool
    {
        return filled($request->input(self::HONEYPOT));
    }

    /**
     * Apakah IP ini sudah melewati batas kiriman? Hanya memeriksa, tidak mencatat.
     *
     * @return int|null null = boleh lanjut. Angka = jumlah detik sampai boleh mengirim lagi.
     */
    public static function blockedFor(Request $request, string $form): ?int
    {
        $ip = (string) $request->ip();

        foreach (static::limits($form) as [$max, $seconds]) {
            $key = static::key($form, $ip, $seconds);

            if (RateLimiter::tooManyAttempts($key, $max)) {
                return max(1, RateLimiter::availableIn($key));
            }
        }

        return null;
    }

    /**
     * Catat satu kiriman yang BERHASIL dari IP ini. Dipanggil setelah ulasan/booking benar-benar
     * dibuat, jadi salah ketik atau akun yang sudah laku tidak menghabiskan jatah pembeli asli.
     */
    public static function hit(Request $request, string $form): void
    {
        $ip = (string) $request->ip();

        foreach (static::limits($form) as [, $seconds]) {
            RateLimiter::hit(static::key($form, $ip, $seconds), $seconds);
        }
    }

    /** @return array<int, array{0: int, 1: int}> */
    protected static function limits(string $form): array
    {
        return config("store.spam_limits.{$form}", self::DEFAULT_LIMITS[$form] ?? []);
    }

    /** Catat bot yang terjebak (untuk dilihat di storage/logs). Tidak ada notifikasi ke admin. */
    public static function logBot(Request $request, string $form): void
    {
        Log::info("SpamGuard: honeypot terisi pada form {$form}", [
            'ip' => $request->ip(),
            'ua' => substr((string) $request->userAgent(), 0, 120),
        ]);
    }

    /** Teks tunggu yang enak dibaca: "5 menit", "2 jam". */
    public static function waitText(int $seconds): string
    {
        if ($seconds < 90) {
            return $seconds . ' detik';
        }

        if ($seconds < 5400) {
            return (int) ceil($seconds / 60) . ' menit';
        }

        return (int) ceil($seconds / 3600) . ' jam';
    }

    protected static function key(string $form, string $ip, int $seconds): string
    {
        return 'spamguard:' . $form . ':' . $seconds . ':' . sha1($ip);
    }
}