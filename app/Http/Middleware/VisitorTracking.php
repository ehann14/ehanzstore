<?php

namespace App\Http\Middleware;

use App\Models\VisitorEvent;
use App\Support\SocialProof;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class VisitorTracking
{
    /** Penanda "penghapusan data lama sudah jalan hari ini". */
    private const PRUNE_LOCK_KEY = 'visitor_events:pruned';

    /** Batas putaran hapus per request (x prune_chunk baris) supaya tidak memberatkan pengunjung. */
    private const PRUNE_MAX_CHUNKS = 20;

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            // Cek apakah ini page_view atau event spesifik (misal klik WA)
            $eventType = 'page_view';

            if ($request->has('track') && $request->get('track') === 'whatsapp_click') {
                $eventType = 'click_whatsapp';
            }

            try {
                VisitorEvent::create([
                    'anonymous_session_id' => session()->getId(),
                    'event_type' => $eventType,
                    'page_path' => mb_substr($request->path(), 0, 255),
                    'event_metadata' => $this->metadata($request),
                ]);
            } catch (Throwable $e) {
                // Tracking tidak boleh membuat halaman ikut error
                report($e);
            }
        }

        return $response;
    }

    /**
     * Dijalankan setelah respons terkirim ke pengunjung: hapus data lama sekali sehari.
     * Cadangan untuk hosting tanpa cron (dengan cron, `visitors:prune` di scheduler sudah cukup).
     */
    public function terminate(Request $request, Response $response): void
    {
        $days = (int) config('store.visitor_tracking.retention_days', 90);

        if ($days <= 0) {
            return;
        }

        try {
            // Cache::add hanya berhasil untuk satu request per hari
            if (! Cache::add(self::PRUNE_LOCK_KEY, true, now()->addDay())) {
                return;
            }

            $chunk   = (int) config('store.visitor_tracking.prune_chunk', 1000);
            $deleted = VisitorEvent::pruneOlderThan($days, $chunk, self::PRUNE_MAX_CHUNKS);

            // Masih ada tumpukan lama: buka kunci supaya request berikutnya melanjutkan
            if ($deleted >= $chunk * self::PRUNE_MAX_CHUNKS) {
                Cache::forget(self::PRUNE_LOCK_KEY);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Hanya kunjungan manusia ke halaman publik yang berhasil dibuka yang dicatat.
     */
    private function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        // Hanya respons sukses (2xx). Redirect, 404 hasil scan bot (/wp-login.php dsb), maintenance (503) dan error dilewati.
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return false;
        }

        // Bukan asset, admin, login, endpoint token, atau file non-halaman (favicon, robots, dsb)
        if ($request->is(...(array) config('store.visitor_tracking.ignore_paths', []))) {
            return false;
        }

        // Prefetch browser bukan kunjungan nyata
        $purpose = strtolower((string) ($request->headers->get('sec-purpose') ?: $request->headers->get('purpose')));
        if (str_contains($purpose, 'prefetch')) {
            return false;
        }

        // Bot, crawler, pratinjau link, dan request tanpa user agent
        return ! SocialProof::isBot($request->userAgent());
    }

    /**
     * Metadata seperlunya: hanya domain situs asal bila pengunjung datang dari luar.
     * IP dan user agent sengaja tidak disimpan.
     */
    private function metadata(Request $request): ?array
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        if (! is_string($host) || $host === '' || strcasecmp($host, $request->getHost()) === 0) {
            return null;
        }

        return ['ref' => mb_substr(strtolower($host), 0, 100)];
    }
}