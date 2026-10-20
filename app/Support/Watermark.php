<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menempelkan logo EHANZSTORE ke foto akun saat diunggah.
 *
 * - 1 logo di pojok kanan bawah (cukup jelas, tapi tidak menutupi isi foto)
 * - 1 logo besar transparan di tengah (supaya tidak bisa dihilangkan dengan crop)
 *
 * Kalau ekstensi GD tidak aktif atau terjadi error, foto asli tetap disimpan
 * (upload tidak pernah gagal gara-gara watermark).
 */
class Watermark
{
    /** Opacity logo pojok & logo tengah (0-100). */
    private const CORNER_OPACITY = 70;
    private const CENTER_OPACITY = 16;

    /** Lebar logo relatif terhadap sisi terpendek foto. */
    private const CORNER_RATIO = 0.17;
    private const CENTER_RATIO = 0.42;

    private const JPEG_QUALITY = 86;

    /**
     * Simpan foto ke disk "public" dengan watermark. Mengembalikan path relatif
     * (contoh: accounts/abc123.jpg), sama seperti $file->store().
     */
    public static function store(UploadedFile $file, string $dir = 'accounts'): string
    {
        $logo = public_path('images/logo.png');

        if (! extension_loaded('gd') || ! is_file($logo)) {
            return $file->store($dir, 'public');
        }

        try {
            $binary = self::apply($file->getRealPath(), $logo);

            if ($binary !== null) {
                $path = $dir . '/' . Str::random(40) . '.jpg';
                Storage::disk('public')->put($path, $binary);

                return $path;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $file->store($dir, 'public');
    }

    /** Mengembalikan isi JPEG yang sudah diberi watermark, atau null kalau gagal. */
    public static function apply(string $sourcePath, string $logoPath): ?string
    {
        $src = self::load($sourcePath);
        if (! $src) {
            return null;
        }

        // imagecreatefromstring membaca format apa pun (logo.png di project ini sebenarnya JPEG)
        $raw  = @file_get_contents($logoPath);
        $logo = $raw ? @imagecreatefromstring($raw) : false;
        if (! $logo) {
            imagedestroy($src);
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $short = min($w, $h);

        // Foto PNG transparan -> latar putih supaya aman disimpan sebagai JPEG
        $canvas = imagecreatetruecolor($w, $h);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $src, 0, 0, 0, 0, $w, $h);
        imagedestroy($src);

        // Logo pojok kanan bawah
        $size   = max(48, (int) round($short * self::CORNER_RATIO));
        $margin = max(8, (int) round($short * 0.025));
        self::overlay($canvas, $logo, $w - $size - $margin, $h - $size - $margin, $size, self::CORNER_OPACITY);

        // Logo tengah transparan
        $big = (int) round($short * self::CENTER_RATIO);
        self::overlay($canvas, $logo, (int) round(($w - $big) / 2), (int) round(($h - $big) / 2), $big, self::CENTER_OPACITY);

        imagedestroy($logo);

        ob_start();
        imagejpeg($canvas, null, self::JPEG_QUALITY);
        $out = ob_get_clean();
        imagedestroy($canvas);

        return $out !== false && $out !== '' ? $out : null;
    }

    private static function load(string $path)
    {
        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $img = @imagecreatefromjpeg($path);
                // Hormati orientasi EXIF dari kamera HP
                if ($img && function_exists('exif_read_data')) {
                    $exif = @exif_read_data($path);
                    $o    = $exif['Orientation'] ?? 1;
                    $img  = match ($o) {
                        3 => imagerotate($img, 180, 0),
                        6 => imagerotate($img, -90, 0),
                        8 => imagerotate($img, 90, 0),
                        default => $img,
                    };
                }
                return $img ?: null;
            case IMAGETYPE_PNG:
                return @imagecreatefrompng($path) ?: null;
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($path) ?: null) : null;
        }

        return null;
    }

    /** Tempel logo (di-resize ke $size px persegi) dengan opacity tertentu. */
    private static function overlay($dst, $logo, int $x, int $y, int $size, int $opacity): void
    {
        $tmp = imagecreatetruecolor($size, $size);
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
        imagefill($tmp, 0, 0, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
        imagecopyresampled($tmp, $logo, 0, 0, 0, 0, $size, $size, imagesx($logo), imagesy($logo));

        // imagecopymerge tidak peduli alpha PNG, tapi logo ini berlatar solid sehingga aman
        imagecopymerge($dst, $tmp, $x, $y, 0, 0, $size, $size, $opacity);
        imagedestroy($tmp);
    }
}