<?php

namespace App\Support;

/**
 * Menerjemahkan teks User-Agent menjadi keterangan perangkat yang mudah dibaca
 * (mis. "Chrome di Android 14"). Tanpa library tambahan; cukup untuk daftar perangkat login.
 */
class DeviceInfo
{
    /** @return array{browser: string, os: string, model: ?string, type: string} type: mobile|tablet|desktop */
    public static function parse(?string $ua): array
    {
        $ua = trim((string) $ua);

        if ($ua === '') {
            return ['browser' => 'Browser tidak dikenal', 'os' => 'Perangkat tidak dikenal', 'model' => null, 'type' => 'desktop'];
        }

        // ----- Sistem operasi & jenis perangkat -----
        $os    = 'Perangkat tidak dikenal';
        $type  = 'desktop';
        $model = null;

        if (preg_match('/iPhone|iPod/i', $ua)) {
            $os   = 'iOS' . (preg_match('/OS (\d+)[_.]/i', $ua, $m) ? ' ' . $m[1] : '');
            $type = 'mobile';
            $model = 'iPhone';
        } elseif (preg_match('/iPad/i', $ua)) {
            $os   = 'iPadOS' . (preg_match('/OS (\d+)[_.]/i', $ua, $m) ? ' ' . $m[1] : '');
            $type = 'tablet';
            $model = 'iPad';
        } elseif (preg_match('/Android/i', $ua)) {
            $os   = 'Android' . (preg_match('/Android (\d+)/i', $ua, $m) ? ' ' . $m[1] : '');
            $type = preg_match('/Mobile/i', $ua) ? 'mobile' : 'tablet';

            // Chrome versi baru menyamarkan model menjadi "K": abaikan
            if (preg_match('/Android [\d.]+; ([^;)]+)/i', $ua, $m)) {
                $candidate = trim($m[1]);
                if ($candidate !== '' && strcasecmp($candidate, 'K') !== 0 && stripos($candidate, 'Build/') === false) {
                    $model = mb_substr($candidate, 0, 40);
                }
            }
        } elseif (preg_match('/Windows/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/CrOS/i', $ua)) {
            $os = 'ChromeOS';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux|X11/i', $ua)) {
            $os = 'Linux';
        }

        // ----- Browser (urutan penting: Edge/Opera/Samsung membawa kata "Chrome") -----
        if (preg_match('/Edg(e|A|iOS)?\//i', $ua)) {
            $browser = 'Edge';
        } elseif (preg_match('/OPR\/|Opera|OPT\//i', $ua)) {
            $browser = 'Opera';
        } elseif (preg_match('/SamsungBrowser/i', $ua)) {
            $browser = 'Samsung Internet';
        } elseif (preg_match('/Firefox\/|FxiOS/i', $ua)) {
            $browser = 'Firefox';
        } elseif (preg_match('/CriOS|Chrome\//i', $ua)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari\//i', $ua)) {
            $browser = 'Safari';
        } else {
            $browser = 'Browser tidak dikenal';
        }

        return ['browser' => $browser, 'os' => $os, 'model' => $model, 'type' => $type];
    }
}