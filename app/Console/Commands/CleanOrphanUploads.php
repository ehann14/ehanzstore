<?php

namespace App\Console\Commands;

use App\Support\Housekeeping;
use App\Support\OrphanCleaner;
use Illuminate\Console\Command;

/**
 * Hapus file di folder uploads yang tidak lagi dipakai database
 * (sisa akun atau banner yang sudah dihapus).
 *
 *   php artisan uploads:clean-orphans --dry-run   hanya tampilkan daftar
 *   php artisan uploads:clean-orphans             tampilkan daftar, tanya dulu, lalu hapus
 *   php artisan uploads:clean-orphans --force     hapus tanpa bertanya (untuk cron)
 *   php artisan uploads:clean-orphans --grace=180 lewati file yang lebih muda dari 180 menit
 */
class CleanOrphanUploads extends Command
{
    protected $signature = 'uploads:clean-orphans
                            {--dry-run : Hanya tampilkan file yatim, tidak menghapus}
                            {--force : Hapus tanpa konfirmasi}
                            {--grace= : Lewati file yang lebih muda dari sekian menit (bawaan 60)}';

    protected $description = 'Cari dan hapus file di uploads/ yang tidak dipakai database lagi';

    public function handle(): int
    {
        $grace = $this->option('grace') !== null
            ? max(0, (int) $this->option('grace'))
            : OrphanCleaner::GRACE_MINUTES;

        $scan = OrphanCleaner::scan($grace);

        $this->line('Referensi di database : ' . $scan['referenced'] . ' gambar (' . $scan['missing'] . ' tidak ada di disk)');

        if (! $scan['ok']) {
            $this->error($scan['error']);

            return self::FAILURE;
        }

        if ($scan['recent'] > 0) {
            $this->line('Dilewati (terlalu baru) : ' . $scan['recent'] . ' file');
        }

        if ($scan['count'] === 0) {
            $this->info('Tidak ada file yatim. Folder uploads bersih.');

            return self::SUCCESS;
        }

        $this->table(
            ['File', 'Ukuran', 'Salinan lama', 'Thumbnail'],
            array_map(fn ($f) => [
                $f['path'],
                Housekeeping::bytes($f['bytes']),
                $f['legacy'] ? 'ya' : '-',
                $f['thumbs'] ?: '-',
            ], $scan['files'])
        );

        $this->line($scan['count'] . ' file yatim, total ' . Housekeeping::bytes($scan['bytes']) . '.');

        if ($this->option('dry-run')) {
            $this->comment('Dry run: tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Hapus file-file di atas secara permanen?', false)) {
            $this->comment('Dibatalkan.');

            return self::SUCCESS;
        }

        $r = OrphanCleaner::delete(null, $grace);

        if (! $r['ok']) {
            $this->error($r['error']);

            return self::FAILURE;
        }

        $this->info($r['deleted'] . ' file dihapus, ' . Housekeeping::bytes($r['bytes']) . ' dibebaskan.');

        foreach ($r['failed'] as $path) {
            $this->warn('Gagal dihapus (cek izin folder): ' . $path);
        }

        return $r['failed'] ? self::FAILURE : self::SUCCESS;
    }
}