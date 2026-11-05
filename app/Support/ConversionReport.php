<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\CocAccount;
use App\Models\Sale;
use App\Models\VisitorEvent;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Laporan konversi untuk dashboard Super Admin.
 *
 *  - funnel      : pengunjung unik -> melihat akun -> booking (DP) -> terjual, pada rentang hari terpilih
 *  - candidates  : akun yang ramai dilihat tapi belum laku (kandidat turun harga), lengkap dengan saran harga
 *  - dp          : total DP hangus per bulan (6 bulan terakhir) + total sepanjang waktu
 *
 * Catatan: pengunjung dihitung dari sesi (tabel visitor_events, disimpan sesuai store.visitor_tracking.retention_days),
 * sedangkan booking dan penjualan dicatat per transaksi dan tidak terhubung ke sesi pengunjung. Jadi persentase
 * funnel adalah perbandingan jumlah, bukan pelacakan per orang.
 */
class ConversionReport
{
    /** Pilihan rentang laporan (hari). */
    public const RANGES = [7, 30, 90];

    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** Rentang dari query string; selain pilihan yang ada dipakai 30 hari. */
    public static function normalizeRange(mixed $value): int
    {
        $days = (int) $value;

        return in_array($days, self::RANGES, true) ? $days : 30;
    }

    /** Minimal pengunjung unik agar akun masuk daftar "ramai dilihat tapi belum laku". */
    public static function minViewers(): int
    {
        return max(1, (int) config('store.conversion.min_viewers', 5));
    }

    public static function build(int $days): array
    {
        // Sama dengan grafik tren dashboard: hitung dari awal hari (days - 1) hari lalu sampai sekarang.
        $since = today()->subDays($days - 1);

        return [
            'days'        => $days,
            'since'       => $since,
            'funnel'      => static::funnel($since),
            'candidates'  => static::candidates($since),
            'dp'          => static::forfeitedDp(),
            'min_viewers' => static::minViewers(),
        ];
    }

    // ------------------------------------------------------------------
    // Funnel
    // ------------------------------------------------------------------

    private static function funnel($since): array
    {
        $events = fn () => VisitorEvent::where('created_at', '>=', $since);

        $visitors = (int) $events()->distinct()->count('anonymous_session_id');
        $viewers  = (int) $events()->where('event_type', 'view_account')->distinct()->count('anonymous_session_id');
        $waClicks = (int) $events()->where('event_type', 'click_whatsapp')->distinct()->count('anonymous_session_id');

        $bookings       = 0;
        $bookingsPaid   = 0;
        $soldViaBooking = 0;

        try {
            $bookings       = (int) Booking::where('created_at', '>=', $since)->count();
            $bookingsPaid   = (int) Booking::where('created_at', '>=', $since)
                ->where('dp_state', '!=', Booking::DP_UNPAID)
                ->count();
            $soldViaBooking = (int) Booking::where('status', Booking::COMPLETED)
                ->where('completed_at', '>=', $since)
                ->count();
        } catch (Throwable $e) {
            // Tabel bookings belum dibuat (migrasi belum dijalankan): bagian booking dianggap 0
        }

        $sold           = (int) Sale::completed()->where('sold_at', '>=', $since)->count();
        $soldViaBooking = min($soldViaBooking, $sold);

        $pct = fn (int $part, int $whole): ?float => $whole > 0 ? round($part / $whole * 100, 1) : null;

        return [
            'visitors'         => $visitors,
            'viewers'          => $viewers,
            'wa_clicks'        => $waClicks,
            'bookings'         => $bookings,
            'bookings_paid'    => $bookingsPaid,
            'sold'             => $sold,
            'sold_via_booking' => $soldViaBooking,
            'sold_direct'      => $sold - $soldViaBooking,
            'view_rate'        => $pct($viewers, $visitors),
            'booking_rate'     => $pct($bookings, $viewers),
            'sold_rate'        => $pct($sold, $viewers),
        ];
    }

    // ------------------------------------------------------------------
    // Akun ramai dilihat tapi belum laku
    // ------------------------------------------------------------------

    /**
     * Akun yang masih tersedia & terbit, dilihat minimal N pengunjung unik pada rentang laporan, dan
     * tidak sedang ditahan booking. Diurutkan dari yang paling banyak dilihat; maksimal 8.
     *
     * @return array<int, array{account: CocAccount, views: int, viewers: int, wa: int, bookings: int, days_listed: int, suggestion: ?array}>
     */
    private static function candidates($since): array
    {
        $rows = VisitorEvent::select(
                'coc_account_id',
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT anonymous_session_id) as viewers')
            )
            ->where('event_type', 'view_account')
            ->where('created_at', '>=', $since)
            ->whereNotNull('coc_account_id')
            ->groupBy('coc_account_id')
            ->havingRaw('COUNT(DISTINCT anonymous_session_id) >= ?', [static::minViewers()])
            ->orderByDesc('viewers')
            ->orderByDesc('views')
            ->limit(100)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $accounts = CocAccount::whereIn('id', $rows->pluck('coc_account_id')->all())
            ->where('sale_status', 'available')
            ->where('publication_status', 'published')
            ->get()
            ->keyBy('id');

        if ($accounts->isEmpty()) {
            return [];
        }

        $ids = $accounts->keys()->all();

        $waClicks = VisitorEvent::where('event_type', 'click_whatsapp')
            ->where('created_at', '>=', $since)
            ->whereIn('coc_account_id', $ids)
            ->groupBy('coc_account_id')
            ->select('coc_account_id', DB::raw('COUNT(DISTINCT anonymous_session_id) as c'))
            ->pluck('c', 'coc_account_id');

        $bookingCounts = collect();

        try {
            $bookingCounts = Booking::whereIn('coc_account_id', $ids)
                ->where('created_at', '>=', $since)
                ->groupBy('coc_account_id')
                ->select('coc_account_id', DB::raw('COUNT(*) as c'))
                ->pluck('c', 'coc_account_id');
        } catch (Throwable $e) {
            // tabel bookings belum ada: anggap tidak ada booking
        }

        $held  = Booking::heldAccountIds();
        $items = [];

        foreach ($rows as $row) {
            $account = $accounts->get($row->coc_account_id);

            // Akun yang sedang dibooking sudah ada peminatnya: bukan kandidat turun harga
            if (! $account || in_array((int) $account->id, $held, true)) {
                continue;
            }

            $items[] = [
                'account'     => $account,
                'views'       => (int) $row->views,
                'viewers'     => (int) $row->viewers,
                'wa'          => (int) $waClicks->get($account->id, 0),
                'bookings'    => (int) $bookingCounts->get($account->id, 0),
                'days_listed' => $account->daysListed(),
                'suggestion'  => $account->priceSuggestion(),
            ];

            if (count($items) >= 8) {
                break;
            }
        }

        return $items;
    }

    // ------------------------------------------------------------------
    // DP hangus per bulan
    // ------------------------------------------------------------------

    /**
     * DP hangus = booking ber-dp_state "forfeited" (pembeli batal setelah DP masuk, atau tidak lunas sampai batas waktu).
     * Dikelompokkan ke bulan (WIB) saat booking berakhir (cancelled_at; bila kosong, updated_at).
     *
     * @return array{months: array<int, array{key: string, label: string, count: int, amount: float}>, this_month: array, six_count: int, six_amount: float, max: float, all_count: int, all_amount: float}
     */
    private static function forfeitedDp(): array
    {
        $tz    = Sale::DISPLAY_TZ;
        $first = now()->timezone($tz)->startOfMonth()->subMonths(5);
        $from  = $first->clone()->setTimezone(config('app.timezone'));

        $months = [];

        for ($i = 0; $i < 6; $i++) {
            $m = $first->clone()->addMonths($i);

            $months[$m->format('Y-m')] = [
                'key'    => $m->format('Y-m'),
                'label'  => self::MONTHS[$m->month - 1] . ' ' . $m->year,
                'count'  => 0,
                'amount' => 0.0,
            ];
        }

        $allCount  = 0;
        $allAmount = 0.0;

        try {
            $rows = Booking::where('dp_state', Booking::DP_FORFEITED)
                ->where(function ($q) use ($from) {
                    $q->where('cancelled_at', '>=', $from)
                      ->orWhere(fn ($x) => $x->whereNull('cancelled_at')->where('updated_at', '>=', $from));
                })
                ->get(['id', 'dp_amount', 'cancelled_at', 'updated_at']);

            foreach ($rows as $booking) {
                $at = $booking->cancelled_at ?? $booking->updated_at;

                if (! $at) {
                    continue;
                }

                $key = $at->clone()->timezone($tz)->format('Y-m');

                if (! isset($months[$key])) {
                    continue;
                }

                $months[$key]['count']++;
                $months[$key]['amount'] += (float) $booking->dp_amount;
            }

            $total     = Booking::where('dp_state', Booking::DP_FORFEITED)
                ->selectRaw('COUNT(*) as c, COALESCE(SUM(dp_amount), 0) as s')
                ->first();
            $allCount  = (int) ($total->c ?? 0);
            $allAmount = (float) ($total->s ?? 0);
        } catch (Throwable $e) {
            // tabel bookings belum ada: semua 0
        }

        $list = array_values($months);

        return [
            'months'     => $list,
            'this_month' => end($list),
            'six_count'  => (int) array_sum(array_column($list, 'count')),
            'six_amount' => (float) array_sum(array_column($list, 'amount')),
            'max'        => (float) max(array_column($list, 'amount')),
            'all_count'  => $allCount,
            'all_amount' => $allAmount,
        ];
    }
}