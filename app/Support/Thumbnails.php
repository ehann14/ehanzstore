<?php

namespace App\Support;

use Throwable;

/**
 * Thumbnail foto akun yang dibuat otomatis saat pertama kali diminta.
 *
 * Kartu katalog hanya menampilkan gambar ±200-400 px, tetapi sebelumnya mengunduh foto
 * asli (sampai 1600 px). Sekarang kartu memakai URL seperti:
 *     /uploads/thumbs/480/accounts/abc.jpg
 * Bila file itu belum ada, Apache meneruskannya ke Laravel (PublicController::thumb) yang
 * membuat dan menyimpannya. Permintaan berikutnya dilayani langsung oleh Apache (tanpa PHP),
 * dengan header cache 1 tahun dari uploads/.htaccess.
 *
 * Hanya lebar yang ada di WIDTHS yang diterima, supaya tidak bisa dipakai membanjiri disk.
 */
class Thumbnails
{
    public const WIDTHS  = [320, 480, 800];
    public const QUALITY = 76;

    /** URL thumbnail; kembali ke URL asli bila file bukan foto akun / formatnya tidak didukung. */
    public static function url(?string $path, int $width = 480): string
    {
        $raw = trim((string) $path);

        if ($raw === '' || preg_match('#^(https?:)?//#i', $raw)) {
            return upload_url($raw);
        }

        $relative = Uploads::normalize($raw);

        if (! preg_match('#^accounts/[A-Za-z0-9._-]+\.(jpe?g|png|webp)$#i', $relative)) {
            return upload_url($raw);
        }

        if (! in_array($width, self::WIDTHS, true)) {
            $width = 480;
        }

        return '/uploads/thumbs/' . $width . '/' . $relative;
    }

    /**
     * Path file thumbnail (dibuat bila belum ada). Null bila gagal -> pemanggil memakai file asli.
     * Gambar yang sudah lebih kecil dari lebar target disalin apa adanya (tidak diperbesar).
     */
    public static function make(string $relative, int $width): ?string
    {
        if (! in_array($width, self::WIDTHS, true)) {
            return null;
        }

        $relative = Uploads::normalize($relative);
        $target   = self::targetPath($relative, $width);

        if (is_file($target)) {
            return $target;
        }

        $source = Uploads::locate($relative);

        if (! $source) {
            return null;
        }

        try {
            $info = @getimagesize($source);

            if (! $info) {
                return null;
            }

            $dir = dirname($target);

            if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
                return null;
            }

            [$w, $h] = $info;

            if ($w <= $width) {
                // Sudah kecil: salin saja supaya Apache bisa melayani langsung.
                return self::atomicWrite($target, (string) file_get_contents($source)) ? $target : null;
            }

            if (! ImageOptimizer::available()) {
                return null;
            }

            $img = ImageOptimizer::load($source);

            if (! $img) {
                return null;
            }

            $nw  = $width;
            $nh  = max(1, (int) round($h * ($width / $w)));
            $dst = imagecreatetruecolor($nw, $nh);

            $ext   = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
            $alpha = in_array($ext, ['png', 'webp'], true);

            if ($alpha) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            } else {
                imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            }

            imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, imagesx($img), imagesy($img));
            imagedestroy($img);

            ob_start();

            if ($ext === 'png') {
                imagepng($dst, null, 9);
            } elseif ($ext === 'webp' && ImageOptimizer::supportsWebp()) {
                imagewebp($dst, null, self::QUALITY);
            } else {
                imageinterlace($dst, true);
                imagejpeg($dst, null, self::QUALITY);
            }

            $data = ob_get_clean();
            imagedestroy($dst);

            if ($data === false || $data === '') {
                return null;
            }

            return self::atomicWrite($target, $data) ? $target : null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Hapus semua thumbnail milik satu file asli (dipanggil saat foto dihapus / diganti). */
    public static function forget(string $relative): void
    {
        $relative = Uploads::normalize($relative);

        foreach (self::WIDTHS as $width) {
            $file = self::targetPath($relative, $width);

            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private static function targetPath(string $relative, int $width): string
    {
        return Uploads::root() . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $width
            . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    /** Tulis ke file sementara lalu rename, supaya Apache tidak pernah melayani file setengah jadi. */
    private static function atomicWrite(string $target, string $data): bool
    {
        $tmp = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';

        if (@file_put_contents($tmp, $data) === false) {
            return false;
        }

        @chmod($tmp, 0644);

        if (! @rename($tmp, $target)) {
            @unlink($tmp);

            return false;
        }

        return true;
    }
}