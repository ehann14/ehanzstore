<?php

namespace App\Support;

/**
 * TOTP (RFC 6238) tanpa paket tambahan: kompatibel dengan Google Authenticator,
 * Microsoft Authenticator, Authy, 1Password, dll. (SHA-1, 6 digit, 30 detik).
 */
class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD   = 30;
    private const DIGITS   = 6;

    /** Secret acak 160 bit dalam Base32 (32 karakter). */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function base32Encode(string $binary): string
    {
        $bits = '';
        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $out;
    }

    public static function base32Decode(string $base32): string
    {
        $base32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $base32));

        $bits = '';
        foreach (str_split($base32) as $char) {
            $bits .= str_pad(decbin(strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }

    public static function currentStep(?int $time = null): int
    {
        return intdiv($time ?? time(), self::PERIOD);
    }

    /** Kode 6 digit untuk satu langkah waktu. */
    public static function codeAt(string $secret, int $step): string
    {
        $hash   = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;

        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
                | ((ord($hash[$offset + 1]) & 0xFF) << 16)
                | ((ord($hash[$offset + 2]) & 0xFF) << 8)
                |  (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Cocokkan kode dengan secret (toleransi +/- $window langkah = selisih jam perangkat).
     *
     * @param  int|null  $afterStep  Langkah yang sudah dipakai sebelumnya; langkah <= ini ditolak (anti replay).
     * @return int|null  Langkah waktu yang cocok, atau null bila kode salah.
     */
    public static function verify(string $secret, string $code, int $window = 1, ?int $afterStep = null, ?int $time = null): ?int
    {
        $code = preg_replace('/\s+/', '', $code);

        if (! preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return null;
        }

        $current = self::currentStep($time);
        $matched = null;

        // Semua langkah dicek tanpa berhenti di tengah, agar waktu respons tidak membocorkan apa pun.
        for ($i = -$window; $i <= $window; $i++) {
            $step = $current + $i;

            if ($afterStep !== null && $step <= $afterStep) {
                continue;
            }

            if (hash_equals(self::codeAt($secret, $step), $code) && $matched === null) {
                $matched = $step;
            }
        }

        return $matched;
    }

    /** URI otpauth:// untuk QR code / input manual di aplikasi authenticator. */
    public static function uri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . self::DIGITS
            . '&period=' . self::PERIOD;
    }

    /** Tampilkan secret per 4 karakter agar mudah diketik: ABCD EFGH ... */
    public static function pretty(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }
}