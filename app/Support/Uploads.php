<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Satu-satunya pintu untuk file upload (foto akun & banner).
 *
 * File disimpan LANGSUNG di folder publik web server (disk "uploads"):
 *   <document root>/uploads/accounts/xxx.jpg
 *   <document root>/uploads/banners/xxx.png
 * sehingga tidak butuh symlink `storage` (yang sering tidak didukung
 * shared hosting / DirectAdmin). Path di database selalu relatif,
 * misalnya "accounts/xxx.jpg"; URL publiknya dibuat oleh upload_url().
 */
class Uploads
{
    public const DISK = 'uploads';

    /** Sub-folder yang diizinkan. */
    public const DIRS = ['accounts', 'banners'];

    public static function disk(): Filesystem
    {
        return Storage::disk(self::DISK);
    }

    /** Folder fisik tempat file upload disimpan. */
    public static function root(): string
    {
        return (string) config('filesystems.disks.' . self::DISK . '.root');
    }

    /** Lokasi lama (sebelum pindah ke folder uploads): storage/app/public. */
    public static function legacyRoot(): string
    {
        return storage_path('app/public');
    }

    /**
     * Ubah path dari database menjadi path relatif yang bersih
     * ("accounts/xxx.jpg"). Data lama berawalan "storage/", "public/" atau
     * "uploads/" ikut dirapikan. URL eksternal dan input kosong -> "".
     */
    public static function normalize(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '' || preg_match('#^(https?:)?//#i', $path)) {
            return '';
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        $path = preg_replace('#^public/#i', '', $path);
        $path = preg_replace('#^(storage|uploads)/#i', '', $path);

        // Buang segmen berbahaya ("..", ".") supaya tidak bisa keluar folder
        $segments = array_filter(
            explode('/', $path),
            fn ($s) => $s !== '' && $s !== '.' && $s !== '..'
        );

        return implode('/', $segments);
    }

    /** Pastikan folder tujuan ada dan terlindungi (.htaccess). Gagal diam-diam bila tidak punya izin. */
    public static function prepare(): void
    {
        $root = self::root();

        foreach (self::DIRS as $dir) {
            $path = $root . DIRECTORY_SEPARATOR . $dir;
            if (! is_dir($path)) {
                @mkdir($path, 0755, true);
            }
        }

        $htaccess = $root . DIRECTORY_SEPARATOR . '.htaccess';
        if (is_dir($root) && ! is_file($htaccess)) {
            @file_put_contents($htaccess, self::htaccess());
        }
    }

    /** Simpan file upload dengan nama acak. Mengembalikan path relatif ("accounts/xxx.jpg"). */
    public static function store(UploadedFile $file, string $dir): string
    {
        self::prepare();

        $path = self::disk()->putFile($dir, $file);

        if (! $path) {
            throw new RuntimeException(
                'Gagal menyimpan file ke folder uploads. Periksa izin tulis folder ' . self::root()
            );
        }

        return $path;
    }

    /** Simpan konten biner (mis. hasil watermark) ke path relatif. */
    public static function put(string $path, string $contents): void
    {
        self::prepare();

        if (! self::disk()->put($path, $contents)) {
            throw new RuntimeException(
                'Gagal menulis file ' . $path . '. Periksa izin tulis folder ' . self::root()
            );
        }
    }

    /** Hapus file (di folder uploads, dan salinan lamanya di storage/app/public bila ada). */
    public static function delete(?string $path): void
    {
        $relative = self::normalize($path);

        if ($relative === '' || ! in_array(explode('/', $relative)[0], self::DIRS, true)) {
            return;
        }

        try {
            self::disk()->delete($relative);
        } catch (\Throwable $e) {
            report($e);
        }

        $legacy = self::legacyRoot() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (is_file($legacy)) {
            @unlink($legacy);
        }
    }

    /**
     * Cari file fisik untuk route fallback /uploads/{path}.
     * Urutan: folder uploads, lalu lokasi lama storage/app/public (data lama).
     * File dari lokasi lama otomatis disalin ke folder uploads supaya
     * permintaan berikutnya dilayani langsung oleh web server.
     */
    public static function locate(string $path): ?string
    {
        $relative = self::normalize($path);

        if (! preg_match('#^(' . implode('|', self::DIRS) . ')/[^/]+$#', $relative)) {
            return null;
        }

        $native = str_replace('/', DIRECTORY_SEPARATOR, $relative);

        if ($found = self::within(self::root(), $native)) {
            return $found;
        }

        if ($legacy = self::within(self::legacyRoot(), $native)) {
            self::prepare();

            $target = self::root() . DIRECTORY_SEPARATOR . $native;
            if (! is_file($target)) {
                @copy($legacy, $target);
                @chmod($target, 0644);
            }

            return $legacy;
        }

        return null;
    }

    /** Path absolut file jika benar-benar berada di dalam $root, selain itu null. */
    private static function within(string $root, string $native): ?string
    {
        $base = realpath($root);
        if (! $base) {
            return null;
        }

        $file = realpath($base . DIRECTORY_SEPARATOR . $native);

        return $file && str_starts_with($file, $base . DIRECTORY_SEPARATOR) && is_file($file)
            ? $file
            : null;
    }

    /** Isi .htaccess untuk folder uploads: tanpa daftar folder & tanpa eksekusi script. */
    private static function htaccess(): string
    {
        return <<<'HTACCESS'
# Folder upload: hanya untuk gambar. Script tidak boleh dijalankan di sini.
Options -Indexes

<FilesMatch "\.(php\d?|phtml|phar|pl|py|cgi|sh)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
</IfModule>

HTACCESS;
    }
}