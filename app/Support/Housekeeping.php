<?php

namespace App\Support;

use App\Models\VisitorEvent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pembersihan cache & data sampah yang aman dijalankan kapan saja.
 * Tidak menyentuh sesi login, data penjualan, booking, maupun foto.
 */
class Housekeeping
{
    /** Ukuran sampah yang bisa dibersihkan sekarang. */
    public static function stats(): array
    {
        $logBytes = 0;
        foreach (glob(storage_path('logs') . '/*.log') ?: [] as $f) {
            $logBytes += (int) @filesize($f);
        }

        $viewBytes = 0;
        foreach (glob(storage_path('framework/views') . '/*.php') ?: [] as $f) {
            $viewBytes += (int) @filesize($f);
        }

        return [
            'cache_rows'    => self::count('cache'),
            'log_bytes'     => $logBytes,
            'view_bytes'    => $viewBytes,
            'visitor_rows'  => self::count('visitor_events'),
            'visitor_old'   => self::oldVisitors(self::visitorDays()),
            'notif_old'     => self::oldNotifications(),
        ];
    }

    public static function visitorDays(): int
    {
        return max(7, (int) config('store.visitor_tracking.retention_days', 90));
    }

    /**
     * Jalankan pembersihan.
     *
     * @return array<string,string> ringkasan hasil per langkah
     */
    public static function clean(int $visitorDays = 30): array
    {
        $out = [];

        try {
            Artisan::call('cache:clear');
            $out['Cache aplikasi'] = 'dikosongkan';
        } catch (Throwable $e) {
            report($e);
            $out['Cache aplikasi'] = 'gagal';
        }

        try {
            Artisan::call('view:clear');
            $out['Cache tampilan (views)'] = 'dibersihkan';
        } catch (Throwable $e) {
            report($e);
            $out['Cache tampilan (views)'] = 'gagal';
        }

        $freed = 0;
        foreach (glob(storage_path('logs') . '/*.log') ?: [] as $f) {
            $freed += (int) @filesize($f);
            @file_put_contents($f, '');
        }
        $out['File log'] = 'dikosongkan (' . self::bytes($freed) . ')';

        try {
            $deleted = VisitorEvent::pruneOlderThan(max(7, $visitorDays), 1000, 20);
            $out['Data pengunjung lama'] = $deleted . ' baris dihapus';
        } catch (Throwable $e) {
            report($e);
            $out['Data pengunjung lama'] = 'dilewati';
        }

        try {
            if (Schema::hasTable('notifications')) {
                $n = DB::table('notifications')
                    ->whereNotNull('read_at')
                    ->where('created_at', '<', now()->subDays(30))
                    ->delete();
                $out['Notifikasi lama (sudah dibaca)'] = $n . ' baris dihapus';
            }
        } catch (Throwable $e) {
            report($e);
        }

        if (self::refreshUploadsHtaccess()) {
            $out['.htaccess uploads'] = 'diperbarui (cache gambar 1 tahun)';
        }

        return $out;
    }

    /**
     * Pastikan folder uploads memerintahkan browser menyimpan gambar lama-lama.
     * Nama file upload selalu acak & unik, jadi aman di-cache "selamanya".
     */
    public static function refreshUploadsHtaccess(): bool
    {
        $file = Uploads::root() . DIRECTORY_SEPARATOR . '.htaccess';

        if (! is_dir(Uploads::root())) {
            return false;
        }

        $current = is_file($file) ? (string) @file_get_contents($file) : '';

        if (str_contains($current, 'max-age=31536000')) {
            return false;
        }

        return @file_put_contents($file, Uploads::htaccessContents()) !== false;
    }

    private static function count(string $table): int
    {
        try {
            return Schema::hasTable($table) ? (int) DB::table($table)->count() : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }

    private static function oldVisitors(int $days): int
    {
        try {
            return (int) DB::table('visitor_events')->where('created_at', '<', now()->subDays($days))->count();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private static function oldNotifications(): int
    {
        try {
            return Schema::hasTable('notifications')
                ? (int) DB::table('notifications')->whereNotNull('read_at')->where('created_at', '<', now()->subDays(30))->count()
                : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }

    public static function bytes(int|float $b): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($b >= 1024 && $i < count($units) - 1) {
            $b /= 1024;
            $i++;
        }

        return ($i === 0 ? (string) (int) $b : number_format($b, 1, ',', '.')) . ' ' . $units[$i];
    }
}