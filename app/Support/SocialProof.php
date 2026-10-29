<?php

namespace App\Support;

use App\Models\CocAccount;
use App\Models\Sale;
use App\Models\VisitorEvent;
use Throwable;

/**
 * Social proof di halaman detail akun, dihitung dari data nyata tabel visitor_events:
 *  - views_today : jumlah pengunjung unik yang membuka akun ini hari ini (WIB)
 *  - live        : pengunjung lain yang membuka akun ini dalam beberapa menit terakhir
 *  - inquiries   : pengunjung unik yang menekan tombol "Beli via WhatsApp" akun ini
 *                  dalam beberapa jam terakhir
 *
 * Angka tidak pernah dipalsukan. Tiap angka baru tampil setelah mencapai batas minimum
 * di config/store.php (social_proof), supaya angka sekecil "1 orang" tidak terpajang.
 * Pengunjung unik dihitung dari anonymous_session_id.
 */
class SocialProof
{
    public static function enabled(): bool
    {
        return (bool) config('store.social_proof.enabled', true);
    }

    /** Hasil kosong: tidak ada yang ditampilkan. */
    public static function empty(): array
    {
        return [
            'show'          => false,
            'views_today'   => null,
            'live'          => null,
            'inquiries'     => null,
            'inquiry_hours' => (int) config('store.social_proof.inquiry_window_hours', 6),
        ];
    }

    /**
     * @param string|null $ownSession id sesi pengunjung ini, supaya "sedang melihat" hanya menghitung orang lain
     */
    public static function forAccount(CocAccount $account, ?string $ownSession = null): array
    {
        $result = self::empty();

        if (! self::enabled()) {
            return $result;
        }

        try {
            $cfg = (array) config('store.social_proof', []);

            $minViews     = max(1, (int) ($cfg['min_views_today'] ?? 3));
            $liveMinutes  = max(1, (int) ($cfg['live_window_minutes'] ?? 5));
            $minLive      = max(1, (int) ($cfg['min_live'] ?? 1));
            $inqHours     = max(1, (int) ($cfg['inquiry_window_hours'] ?? 6));
            $minInquiries = max(1, (int) ($cfg['min_inquiries'] ?? 1));

            $events = fn (string $type) => VisitorEvent::where('coc_account_id', $account->id)
                ->where('event_type', $type);

            // Awal hari ini menurut WIB, dikonversi ke zona waktu database (UTC)
            $startToday = now(Sale::DISPLAY_TZ)->startOfDay()->setTimezone(config('app.timezone'));

            $today = (int) $events('view_account')
                ->where('created_at', '>=', $startToday)
                ->distinct()
                ->count('anonymous_session_id');

            $live = (int) $events('view_account')
                ->where('created_at', '>=', now()->subMinutes($liveMinutes))
                ->when($ownSession, fn ($q) => $q->where('anonymous_session_id', '!=', $ownSession))
                ->distinct()
                ->count('anonymous_session_id');

            $inquiries = (int) $events('click_whatsapp')
                ->where('created_at', '>=', now()->subHours($inqHours))
                ->distinct()
                ->count('anonymous_session_id');

            $result['views_today']   = $today >= $minViews ? $today : null;
            $result['live']          = $live >= $minLive ? $live : null;
            $result['inquiries']     = $inquiries >= $minInquiries ? $inquiries : null;
            $result['inquiry_hours'] = $inqHours;
            $result['show']          = $result['views_today'] || $result['live'] || $result['inquiries'];
        } catch (Throwable $e) {
            // Social proof hanya pelengkap: gagal menghitung tidak boleh merusak halaman detail.
            report($e);

            return self::empty();
        }

        return $result;
    }

    /**
     * Apakah user agent ini crawler / pratinjau link (WhatsApp, Googlebot, dsb)?
     * Kunjungan mereka tidak dicatat supaya angka pengunjung tidak menggelembung.
     */
    public static function isBot(?string $userAgent): bool
    {
        $ua = trim((string) $userAgent);

        if ($ua === '') {
            return true;
        }

        return (bool) preg_match(
            '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|curl|wget|python|httpclient|headless|lighthouse|pingdom|uptime|monitor/i',
            $ua
        );
    }
}