<?php

namespace App\Console\Commands;

use App\Models\VisitorEvent;
use App\Support\SocialProof;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Menjaga tabel visitor_events tidak membengkak.
 *
 *   php artisan visitors:prune            hapus event lebih tua dari batas retensi (config store.visitor_tracking)
 *   php artisan visitors:prune --days=60  pakai batas lain
 *   php artisan visitors:prune --scrub    sekali jalan setelah update: buang baris milik bot dan kosongkan
 *                                         IP / user agent pada data lama
 */
class PruneVisitorEvents extends Command
{
    protected $signature = 'visitors:prune
                            {--days= : Hapus event lebih tua dari sekian hari (bawaan: VISITOR_RETENTION_DAYS)}
                            {--scrub : Hapus baris bot lama dan kosongkan metadata (IP & user agent) data lama}';

    protected $description = 'Hapus data pengunjung lama dan bersihkan metadata yang tidak diperlukan';

    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('store.visitor_tracking.retention_days', 90);

        if ($this->option('scrub')) {
            $this->scrub();
        }

        if ($days <= 0) {
            $this->info('Retensi dimatikan (0 hari), tidak ada event yang dihapus berdasarkan umur.');

            return self::SUCCESS;
        }

        $deleted = VisitorEvent::pruneOlderThan($days, (int) config('store.visitor_tracking.prune_chunk', 1000));

        $this->info("Event lebih tua dari {$days} hari dihapus: {$deleted} baris.");

        return self::SUCCESS;
    }

    /**
     * Data lama: buang kunjungan bot / file non-halaman, lalu kosongkan metadata sisanya.
     * Metadata lama tersimpan sebagai JSON yang di-encode dua kali, jadi di-decode bertahap.
     */
    private function scrub(): void
    {
        $bots = 0;
        $noise = 0;
        $cleared = 0;

        // Path non-halaman tanpa wildcard (favicon.ico, robots.txt, login, dst)
        $exactPaths = array_values(array_filter(
            (array) config('store.visitor_tracking.ignore_paths', []),
            fn ($p) => ! str_contains($p, '*')
        ));

        if ($exactPaths) {
            $noise = DB::table('visitor_events')->whereIn('page_path', $exactPaths)->delete();
        }

        DB::table('visitor_events')
            ->whereNotNull('event_metadata')
            ->chunkById(500, function ($rows) use (&$bots, &$cleared) {
                $botIds = [];
                $keepIds = [];

                foreach ($rows as $row) {
                    $meta = json_decode((string) $row->event_metadata, true);

                    if (is_string($meta)) {
                        $meta = json_decode($meta, true);
                    }

                    $ua = is_array($meta) ? ($meta['user_agent'] ?? null) : null;

                    // Metadata tak terbaca: jangan dianggap bot, cukup dikosongkan
                    if (is_array($meta) && SocialProof::isBot($ua)) {
                        $botIds[] = $row->id;
                    } else {
                        $keepIds[] = $row->id;
                    }
                }

                if ($botIds) {
                    $bots += DB::table('visitor_events')->whereIn('id', $botIds)->delete();
                }

                if ($keepIds) {
                    $cleared += DB::table('visitor_events')->whereIn('id', $keepIds)->update(['event_metadata' => null]);
                }
            });

        $this->info("Scrub: {$bots} baris bot dihapus, {$noise} baris non-halaman dihapus, metadata {$cleared} baris dikosongkan.");
    }
}