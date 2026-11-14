<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pengecil gambar untuk menghemat bandwidth hosting.
 *
 * - Banner: diperkecil (maks 1600 px) lalu disimpan WebP (JPEG bila GD tanpa WebP).
 *   Banner PNG 2 MB biasanya turun jadi ~100-250 KB.
 * - Foto akun: diperkecil (maks 1600 px) dan dikompres ulang JPEG (watermark tetap ikut).
 *
 * Dipakai di dua tempat:
 *   1. Saat upload baru (storeBanner)  -> file baru langsung kecil.
 *   2. Tombol "Optimalkan gambar" di Super Admin -> merapikan file lama yang sudah terlanjur besar.
 */
class ImageOptimizer
{
    public const BANNER_MAX_SIDE  = 1600;
    public const BANNER_QUALITY   = 80;
    public const PHOTO_MAX_SIDE   = 1600;
    public const PHOTO_QUALITY    = 78;

    /** File di bawah ukuran ini (dan tidak melebihi batas sisi) dianggap sudah cukup kecil. */
    public const SKIP_BELOW_BYTES = 200 * 1024;

    /** Batas gambar yang diproses per klik, supaya tidak kena timeout hosting. */
    public const BATCH = 12;

    public static function available(): bool
    {
        return extension_loaded('gd');
    }

    public static function supportsWebp(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatefromwebp');
    }

    /** Upload banner baru: langsung dikecilkan. Gagal mengecilkan -> simpan file asli. */
    public static function storeBanner(UploadedFile $file): string
    {
        if (self::available()) {
            try {
                $out = self::encode($file->getRealPath(), self::BANNER_MAX_SIDE, self::BANNER_QUALITY, true);

                if ($out !== null && strlen($out['data']) < $file->getSize()) {
                    $path = 'banners/' . bin2hex(random_bytes(20)) . '.' . $out['ext'];
                    Uploads::put($path, $out['data']);

                    return $path;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return Uploads::store($file, 'banners');
    }

    /**
     * Ringkasan folder uploads: ukuran per folder dan file terbesar.
     *
     * @return array{total:int,dirs:array<string,array{files:int,bytes:int}>,top:array<int,array{path:string,bytes:int}>}
     */
    public static function scan(int $top = 8): array
    {
        $root  = Uploads::root();
        $dirs  = [];
        $files = [];
        $total = 0;

        foreach (Uploads::DIRS as $dir) {
            $dirs[$dir] = ['files' => 0, 'bytes' => 0];
            $path = $root . DIRECTORY_SEPARATOR . $dir;

            if (! is_dir($path)) {
                continue;
            }

            foreach (@scandir($path) ?: [] as $name) {
                $full = $path . DIRECTORY_SEPARATOR . $name;

                if ($name === '.' || $name === '..' || ! is_file($full)) {
                    continue;
                }

                $bytes = (int) @filesize($full);
                $dirs[$dir]['files']++;
                $dirs[$dir]['bytes'] += $bytes;
                $total += $bytes;
                $files[] = ['path' => $dir . '/' . $name, 'bytes' => $bytes];
            }
        }

        usort($files, fn ($a, $b) => $b['bytes'] <=> $a['bytes']);

        return ['total' => $total, 'dirs' => $dirs, 'top' => array_slice($files, 0, $top)];
    }

    /** Berapa gambar (banner + foto akun) yang masih layak dikecilkan. */
    public static function pendingCount(): int
    {
        $count = 0;

        foreach (self::targets() as $t) {
            if (self::needsWork($t['file'], $t['kind'])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Kecilkan gambar lama. Mengembalikan ringkasan hasil.
     *
     * @return array{processed:int,saved:int,remaining:int,skipped:int,errors:int}
     */
    public static function run(int $limit = self::BATCH): array
    {
        @set_time_limit(120);

        $result = ['processed' => 0, 'saved' => 0, 'remaining' => 0, 'skipped' => 0, 'errors' => 0];

        if (! self::available()) {
            return $result;
        }

        foreach (self::targets() as $t) {
            if (! self::needsWork($t['file'], $t['kind'])) {
                continue;
            }

            if ($result['processed'] >= $limit) {
                $result['remaining']++;

                continue;
            }

            try {
                $before = (int) filesize($t['file']);
                $banner = $t['kind'] === 'banner';
                $out    = self::encode(
                    $t['file'],
                    $banner ? self::BANNER_MAX_SIDE : self::PHOTO_MAX_SIDE,
                    $banner ? self::BANNER_QUALITY : self::PHOTO_QUALITY,
                    $banner
                );

                $result['processed']++;

                // Hanya ganti kalau benar-benar lebih kecil (minimal 10%).
                if ($out === null || strlen($out['data']) > $before * 0.9) {
                    $result['skipped']++;

                    continue;
                }

                $dir     = explode('/', $t['relative'])[0];
                $newPath = $dir . '/' . bin2hex(random_bytes(20)) . '.' . $out['ext'];

                // Nama BARU supaya cache browser/CDN pengunjung tidak menyajikan versi lama.
                Uploads::put($newPath, $out['data']);

                DB::table($t['table'])->where('id', $t['id'])->update(['image_path' => $newPath]);

                // File lama dihapus hanya setelah database menunjuk ke yang baru
                // dan tidak ada baris lain yang masih memakainya.
                if (! self::stillReferenced($t['relative'])) {
                    Uploads::delete($t['relative']);
                }

                $result['saved'] += max(0, $before - strlen($out['data']));
            } catch (Throwable $e) {
                report($e);
                $result['errors']++;
            }
        }

        return $result;
    }

    /** Semua gambar yang dipakai database: banner dan foto akun. */
    private static function targets(): array
    {
        $rows = [];

        $sources = [
            ['banners', 'banner'],
            ['coc_account_images', 'photo'],
        ];

        foreach ($sources as [$table, $kind]) {
            try {
                $list = DB::table($table)->select('id', 'image_path')->get();
            } catch (Throwable $e) {
                continue;
            }

            foreach ($list as $row) {
                $relative = Uploads::normalize($row->image_path);

                if ($relative === '' || ! in_array(explode('/', $relative)[0], ['banners', 'accounts'], true)) {
                    continue;
                }

                $file = Uploads::root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

                if (! is_file($file)) {
                    continue;
                }

                $rows[] = [
                    'table'    => $table,
                    'kind'     => $kind,
                    'id'       => $row->id,
                    'relative' => $relative,
                    'file'     => $file,
                ];
            }
        }

        return $rows;
    }

    private static function needsWork(string $file, string $kind): bool
    {
        $size = (int) @filesize($file);
        $info = @getimagesize($file);

        if (! $info || $info[2] === IMAGETYPE_GIF) {
            return false;
        }

        $max = $kind === 'banner' ? self::BANNER_MAX_SIDE : self::PHOTO_MAX_SIDE;

        // Banner non-WebP dihitung masih bisa dihemat bila cukup besar.
        return $size > self::SKIP_BELOW_BYTES || max($info[0], $info[1]) > $max;
    }

    private static function stillReferenced(string $relative): bool
    {
        foreach (['banners', 'coc_account_images'] as $table) {
            try {
                if (DB::table($table)->where('image_path', 'like', '%' . $relative)->exists()) {
                    return true;
                }
            } catch (Throwable $e) {
                return true; // ragu -> jangan hapus
            }
        }

        return false;
    }

    /**
     * Baca gambar, perkecil, lalu encode.
     *
     * @return array{data:string,ext:string}|null
     */
    private static function encode(string $path, int $maxSide, int $quality, bool $preferWebp): ?array
    {
        $src = self::load($path);

        if (! $src) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);

        if (max($w, $h) > $maxSide) {
            $scale = $maxSide / max($w, $h);
            $nw    = max(1, (int) round($w * $scale));
            $nh    = max(1, (int) round($h * $scale));
            $dst   = imagecreatetruecolor($nw, $nh);

            $useWebp = $preferWebp && self::supportsWebp();

            if ($useWebp) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            } else {
                imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        ob_start();

        if ($preferWebp && self::supportsWebp()) {
            imagepalettetotruecolor($src);
            imagealphablending($src, true);
            imagesavealpha($src, true);
            imagewebp($src, null, $quality);
            $ext = 'webp';
        } else {
            // JPEG tidak punya transparansi: ratakan ke latar putih.
            $flat = imagecreatetruecolor(imagesx($src), imagesy($src));
            imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
            imagecopy($flat, $src, 0, 0, 0, 0, imagesx($src), imagesy($src));
            imagedestroy($src);
            $src = $flat;

            imageinterlace($src, true); // progressive: tampil lebih cepat di koneksi lambat
            imagejpeg($src, null, $quality);
            $ext = 'jpg';
        }

        $data = ob_get_clean();
        imagedestroy($src);

        return $data !== false && $data !== '' ? ['data' => $data, 'ext' => $ext] : null;
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
}