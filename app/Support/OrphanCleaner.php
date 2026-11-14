<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pembersih file yatim di folder uploads.
 *
 * "Yatim" = file di uploads/ (accounts, banners, maintenance, payment) yang tidak lagi
 * ditunjuk oleh database, misalnya sisa akun atau banner yang sudah dihapus.
 *
 * Sumber referensi di database:
 *   - banners.image_path
 *   - coc_account_images.image_path
 *   - store_settings (maintenance_image, payment_qris_image, dan nilai lain yang berbentuk path upload)
 *
 * Pengaman (semuanya bertujuan: lebih baik tidak menghapus daripada salah hapus):
 *   1. Bila salah satu tabel referensi gagal dibaca, pemindaian dibatalkan.
 *   2. Bila database tidak punya referensi gambar sama sekali padahal ada file, dibatalkan.
 *   3. Bila lebih dari separuh gambar yang ditunjuk database tidak ditemukan di folder ini,
 *      folder/database kemungkinan tidak cocok (UPLOADS_PATH salah, salinan lokal, dsb), dibatalkan.
 *   4. File yang lebih muda dari masa tenggang (bawaan 60 menit) dilewati, karena bisa jadi
 *      baru diunggah dan barisnya belum tersimpan di database.
 *   5. Saat menghapus, daftar dipindai ULANG dan hanya file yang masih yatim yang dihapus.
 */
class OrphanCleaner
{
    public const GRACE_MINUTES = 60;

    /** Batas persentase referensi database yang boleh hilang sebelum dianggap salah folder. */
    private const MAX_MISSING_RATIO = 0.5;

    /**
     * @return array{
     *   ok:bool, error:?string, files:array<int,array{path:string,dir:string,bytes:int,mtime:int,legacy:bool,thumbs:int}>,
     *   count:int, bytes:int, referenced:int, missing:int, recent:int
     * }
     */
    public static function scan(int $graceMinutes = self::GRACE_MINUTES): array
    {
        $result = [
            'ok'         => true,
            'error'      => null,
            'files'      => [],
            'count'      => 0,
            'bytes'      => 0,
            'referenced' => 0,
            'missing'    => 0,
            'recent'     => 0,
        ];

        $refs = self::referenced();

        if ($refs === null) {
            return self::fail($result, 'Database tidak bisa dibaca penuh, jadi pemindaian dibatalkan demi keamanan.');
        }

        $found = self::collect();

        if (empty($refs) && ! empty($found)) {
            return self::fail($result, 'Database tidak memuat satu pun referensi gambar padahal folder uploads berisi file. Dibatalkan supaya tidak menghapus semuanya.');
        }

        $missing = 0;

        foreach (array_keys($refs) as $relative) {
            if (! isset($found[$relative]) || ! ($found[$relative]['uploads'] || $found[$relative]['legacy'])) {
                $missing++;
            }
        }

        $result['referenced'] = count($refs);
        $result['missing']    = $missing;

        if (count($refs) > 0 && ($missing / count($refs)) > self::MAX_MISSING_RATIO) {
            return self::fail(
                $result,
                $missing . ' dari ' . count($refs) . ' gambar di database tidak ditemukan di folder uploads ini. '
                . 'Kemungkinan UPLOADS_PATH salah atau database dan folder bukan pasangan (mis. salinan lokal). Penghapusan dinonaktifkan.'
            );
        }

        $cutoff = time() - max(0, $graceMinutes) * 60;

        foreach ($found as $relative => $info) {
            if (isset($refs[$relative])) {
                continue;
            }

            if ($info['mtime'] > $cutoff) {
                $result['recent']++;

                continue;
            }

            $result['files'][] = [
                'path'   => $relative,
                'dir'    => explode('/', $relative)[0],
                'bytes'  => $info['bytes'],
                'mtime'  => $info['mtime'],
                'legacy' => $info['legacy'],
                'thumbs' => $info['thumbs'],
            ];
            $result['bytes'] += $info['bytes'];
        }

        usort($result['files'], fn ($a, $b) => [$a['dir'], -$a['bytes'], $a['path']] <=> [$b['dir'], -$b['bytes'], $b['path']]);

        $result['count'] = count($result['files']);

        return $result;
    }

    /**
     * Hapus file yatim. $only = daftar path yang dipilih (null = semua yang yatim).
     * Daftar dipindai ulang, jadi path yang sudah dipakai lagi atau terlalu baru tidak akan dihapus.
     *
     * @param  array<int,string>|null  $only
     * @return array{ok:bool,error:?string,deleted:int,bytes:int,skipped:int,failed:array<int,string>}
     */
    public static function delete(?array $only = null, int $graceMinutes = self::GRACE_MINUTES): array
    {
        @set_time_limit(120);

        $out  = ['ok' => true, 'error' => null, 'deleted' => 0, 'bytes' => 0, 'skipped' => 0, 'failed' => []];
        $scan = self::scan($graceMinutes);

        if (! $scan['ok']) {
            $out['ok']    = false;
            $out['error'] = $scan['error'];

            return $out;
        }

        $wanted = null;

        if ($only !== null) {
            $wanted = [];

            foreach ($only as $p) {
                $n = Uploads::normalize((string) $p);

                if ($n !== '') {
                    $wanted[$n] = true;
                }
            }
        }

        $orphans = [];

        foreach ($scan['files'] as $f) {
            $orphans[$f['path']] = $f;
        }

        if ($wanted !== null) {
            $out['skipped'] = count(array_diff_key($wanted, $orphans));
        }

        foreach ($orphans as $relative => $f) {
            if ($wanted !== null && ! isset($wanted[$relative])) {
                continue;
            }

            Uploads::delete($relative);

            if (self::stillExists($relative)) {
                $out['failed'][] = $relative;

                continue;
            }

            $out['deleted']++;
            $out['bytes'] += $f['bytes'];
        }

        return $out;
    }

    /**
     * Semua path relatif yang dipakai database, atau null bila ada tabel yang gagal dibaca.
     *
     * @return array<string,true>|null
     */
    public static function referenced(): ?array
    {
        $refs = [];

        try {
            foreach (['banners', 'coc_account_images'] as $table) {
                foreach (DB::table($table)->pluck('image_path') as $value) {
                    self::addRef($refs, $value);
                }
            }

            // Semua nilai pengaturan toko yang berbentuk path upload (gambar maintenance, QRIS, dst).
            foreach (DB::table('store_settings')->pluck('value') as $value) {
                self::addRef($refs, $value);
            }
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return $refs;
    }

    private static function addRef(array &$refs, mixed $value): void
    {
        $relative = Uploads::normalize(is_string($value) ? $value : '');

        if ($relative !== '' && preg_match('#^(' . implode('|', Uploads::DIRS) . ')/[^/]+$#', $relative)) {
            $refs[$relative] = true;
        }
    }

    /**
     * Semua file yang ada di disk, dikelompokkan per path relatif:
     * file di uploads/, salinan lama di storage/app/public, dan thumbnail turunannya.
     *
     * @return array<string,array{uploads:bool,legacy:bool,thumbs:int,bytes:int,mtime:int}>
     */
    private static function collect(): array
    {
        $found = [];
        $sep   = DIRECTORY_SEPARATOR;

        foreach (Uploads::DIRS as $dir) {
            foreach ([['uploads', Uploads::root()], ['legacy', Uploads::legacyRoot()]] as [$kind, $root]) {
                foreach (self::files($root . $sep . $dir) as $name => $full) {
                    self::note($found, $dir . '/' . $name, $full, $kind);
                }
            }
        }

        // Thumbnail dibuat otomatis dari foto akun: uploads/thumbs/<lebar>/accounts/<nama>
        foreach (Thumbnails::WIDTHS as $width) {
            foreach (self::files(Uploads::root() . $sep . 'thumbs' . $sep . $width . $sep . 'accounts') as $name => $full) {
                self::note($found, 'accounts/' . $name, $full, 'thumb');
            }
        }

        return $found;
    }

    /** @return array<string,string> nama file => path penuh (tanpa file tersembunyi dan file sementara) */
    private static function files(string $dir): array
    {
        $list = [];

        if (! is_dir($dir)) {
            return $list;
        }

        foreach (@scandir($dir) ?: [] as $name) {
            $full = $dir . DIRECTORY_SEPARATOR . $name;

            if ($name[0] === '.' || str_ends_with($name, '.tmp') || ! is_file($full)) {
                continue;
            }

            $list[$name] = $full;
        }

        return $list;
    }

    private static function note(array &$found, string $relative, string $full, string $kind): void
    {
        $found[$relative] ??= ['uploads' => false, 'legacy' => false, 'thumbs' => 0, 'bytes' => 0, 'mtime' => 0];

        $found[$relative]['bytes'] += (int) @filesize($full);
        $found[$relative]['mtime']  = max($found[$relative]['mtime'], (int) @filemtime($full));

        if ($kind === 'thumb') {
            $found[$relative]['thumbs']++;
        } else {
            $found[$relative][$kind] = true;
        }
    }

    private static function stillExists(string $relative): bool
    {
        $native = str_replace('/', DIRECTORY_SEPARATOR, $relative);

        return is_file(Uploads::root() . DIRECTORY_SEPARATOR . $native)
            || is_file(Uploads::legacyRoot() . DIRECTORY_SEPARATOR . $native);
    }

    private static function fail(array $result, string $message): array
    {
        $result['ok']    = false;
        $result['error'] = $message;

        return $result;
    }
}