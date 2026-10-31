<?php

namespace App\Support;

use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data untuk ekspor transaksi dan laporan bulanan.
 *
 * Definisi:
 *  - Omzet (di laporan ini) = Harga Jual - Modal, dihitung dari transaksi SELESAI saja.
 *  - Bulan mengikuti waktu WIB; database menyimpan UTC, jadi batas bulan dikonversi dulu.
 *  - TH terlaris = Town Hall dengan jumlah akun terjual terbanyak (clan dihitung terpisah).
 */
class SalesReport
{
    public const TZ = Sale::DISPLAY_TZ;

    /** Ekspresi tanggal transaksi: tanggal terjual, atau tanggal dibuat bila kosong. */
    private const TIME_EXPR = 'COALESCE(sales.sold_at, sales.created_at)';

    public const STATUS_LABELS = [
        'completed' => 'Selesai',
        'pending'   => 'Menunggu',
        'cancelled' => 'Batal',
    ];

    public const PAYMENT_LABELS = [
        'paid'   => 'Lunas',
        'unpaid' => 'Belum bayar',
    ];

    // ------------------------------------------------------------------
    // Rentang waktu
    // ------------------------------------------------------------------

    /** Normalisasi "YYYY-MM"; nilai tidak valid -> bulan ini (WIB). */
    public static function normalizeMonth(?string $month): string
    {
        if (is_string($month) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return $month;
        }

        return Carbon::now(self::TZ)->format('Y-m');
    }

    /** @return array{start:Carbon,end:Carbon,from:string,to:string,label:string,key:string} */
    public static function monthRange(string $month): array
    {
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $month . '-01 00:00:00', self::TZ);
        $end   = $start->copy()->endOfMonth();

        return [
            'start' => $start,
            'end'   => $end,
            'from'  => $start->copy()->utc()->format('Y-m-d H:i:s'),
            'to'    => $end->copy()->utc()->format('Y-m-d H:i:s'),
            'label' => $start->copy()->locale('id')->translatedFormat('F Y'),
            'key'   => $month,
        ];
    }

    public static function dayStartUtc(string $date): string
    {
        return Carbon::parse($date, self::TZ)->startOfDay()->utc()->format('Y-m-d H:i:s');
    }

    public static function dayEndUtc(string $date): string
    {
        return Carbon::parse($date, self::TZ)->endOfDay()->utc()->format('Y-m-d H:i:s');
    }

    // ------------------------------------------------------------------
    // Laporan bulanan
    // ------------------------------------------------------------------

    /** Ringkasan transaksi selesai dalam rentang UTC [$from, $to]. */
    public static function summary(string $from, string $to): array
    {
        $row = DB::table('sales')
            ->leftJoin('coc_accounts', 'coc_accounts.id', '=', 'sales.coc_account_id')
            ->where('sales.transaction_status', 'completed')
            ->whereRaw(self::TIME_EXPR . ' BETWEEN ? AND ?', [$from, $to])
            ->selectRaw(
                'COUNT(*) as sold, '
                . 'COALESCE(SUM(sales.sale_price), 0) as revenue, '
                . 'COALESCE(SUM(sales.cost_price), 0) as cost, '
                . 'COALESCE(SUM(sales.sale_price - sales.cost_price), 0) as profit, '
                . "COALESCE(SUM(coc_accounts.listing_type = 'clan'), 0) as clans"
            )
            ->first();

        $sold  = (int) ($row->sold ?? 0);
        $clans = (int) ($row->clans ?? 0);

        return [
            'sold'     => $sold,
            'accounts' => max(0, $sold - $clans),
            'clans'    => $clans,
            'revenue'  => (float) ($row->revenue ?? 0),
            'cost'     => (float) ($row->cost ?? 0),
            'profit'   => (float) ($row->profit ?? 0),
        ];
    }

    /**
     * Town Hall terlaris (hanya akun pemain), urut jumlah terjual lalu omzet.
     *
     * @return Collection<int, object{th:int, sold:int, revenue:float, profit:float}>
     */
    public static function topTownHalls(string $from, string $to): Collection
    {
        return DB::table('sales')
            ->join('coc_accounts', 'coc_accounts.id', '=', 'sales.coc_account_id')
            ->where('sales.transaction_status', 'completed')
            ->where('coc_accounts.listing_type', 'account')
            ->whereRaw(self::TIME_EXPR . ' BETWEEN ? AND ?', [$from, $to])
            ->groupBy('coc_accounts.town_hall')
            ->selectRaw(
                'coc_accounts.town_hall as th, COUNT(*) as sold, '
                . 'SUM(sales.sale_price) as revenue, '
                . 'SUM(sales.sale_price - sales.cost_price) as profit'
            )
            ->orderByDesc('sold')
            ->orderByDesc('profit')
            ->orderByDesc('th')
            ->get()
            ->map(fn ($r) => (object) [
                'th'      => (int) $r->th,
                'sold'    => (int) $r->sold,
                'revenue' => (float) $r->revenue,
                'profit'  => (float) $r->profit,
            ]);
    }

    /** Data lengkap laporan satu bulan (WIB). */
    public static function monthly(?string $month): array
    {
        $month = self::normalizeMonth($month);
        $range = self::monthRange($month);
        $prev  = self::monthRange($range['start']->copy()->subMonthNoOverflow()->format('Y-m'));

        $summary     = self::summary($range['from'], $range['to']);
        $prevSummary = self::summary($prev['from'], $prev['to']);
        $townHalls   = self::topTownHalls($range['from'], $range['to']);

        // Bisa lebih dari satu TH kalau jumlah terjualnya sama-sama tertinggi
        $max     = (int) ($townHalls->max('sold') ?? 0);
        $topList = $max > 0 ? $townHalls->where('sold', $max)->pluck('th')->all() : [];

        $profitChange = $prevSummary['profit'] != 0.0
            ? ($summary['profit'] - $prevSummary['profit']) / abs($prevSummary['profit']) * 100
            : null;

        $transactions = self::transactions([
            'status' => 'completed',
            '_from'  => $range['from'],
            '_to'    => $range['to'],
        ]);

        return [
            'month'        => $month,
            'range'        => $range,
            'prevLabel'    => $prev['label'],
            'summary'      => $summary,
            'prev'         => $prevSummary,
            'profitChange' => $profitChange,
            'townHalls'    => $townHalls,
            'topTh'        => $topList,
            'transactions' => $transactions,
        ];
    }

    // ------------------------------------------------------------------
    // Daftar transaksi
    // ------------------------------------------------------------------

    /**
     * Filter: status (all|completed|pending|cancelled), from/to (tanggal WIB, Y-m-d).
     * Untuk pemakaian internal bisa memberi _from/_to (UTC) langsung.
     */
    public static function transactions(array $filters = []): Collection
    {
        $query = Sale::with(['account', 'processor']);

        $status = $filters['status'] ?? 'completed';
        if (in_array($status, array_keys(self::STATUS_LABELS), true)) {
            $query->where('sales.transaction_status', $status);
        }

        $from = $filters['_from'] ?? (! empty($filters['from']) ? self::dayStartUtc($filters['from']) : null);
        $to   = $filters['_to'] ?? (! empty($filters['to']) ? self::dayEndUtc($filters['to']) : null);

        if ($from) {
            $query->whereRaw(self::TIME_EXPR . ' >= ?', [$from]);
        }
        if ($to) {
            $query->whereRaw(self::TIME_EXPR . ' <= ?', [$to]);
        }

        return $query
            ->orderByRaw(self::TIME_EXPR . ' asc')
            ->orderBy('sales.id')
            ->limit(5000)
            ->get();
    }

    /** Ubah koleksi Sale menjadi baris datar siap ekspor. */
    public static function rows(Collection $sales): array
    {
        return $sales->map(function (Sale $s) {
            $account = $s->account;
            $isClan  = $account?->isClan() ?? false;
            $time    = ($s->sold_at ?? $s->created_at)->clone()->timezone(self::TZ)->locale('id');

            return [
                'time'       => $time->translatedFormat('d M Y H:i'),
                'code'       => $s->transaction_code,
                'acc_code'   => $s->account_code_snapshot,
                'name'       => $account?->name ?? '(akun dihapus)',
                'type'       => $isClan ? 'Clan' : 'Akun',
                'th'         => ($account && ! $isClan && $account->town_hall) ? 'TH ' . $account->town_hall : '-',
                'buyer'      => $s->buyer_name ?: '-',
                'price'      => (float) $s->sale_price,
                'cost'       => (float) $s->cost_price,
                'profit'     => $s->profit(),
                'status'     => self::STATUS_LABELS[$s->transaction_status] ?? $s->transaction_status,
                'payment'    => self::PAYMENT_LABELS[$s->payment_status] ?? $s->payment_status,
                'method'     => $s->payment_method ?: '-',
                'processor'  => $s->processor?->name ?? '-',
                'completed'  => $s->transaction_status === 'completed',
            ];
        })->all();
    }

    /** Total hanya dari transaksi selesai. */
    public static function totals(array $rows): array
    {
        $done = array_filter($rows, fn ($r) => $r['completed']);

        return [
            'count'  => count($done),
            'price'  => array_sum(array_column($done, 'price')),
            'cost'   => array_sum(array_column($done, 'cost')),
            'profit' => array_sum(array_column($done, 'profit')),
        ];
    }

    // ------------------------------------------------------------------
    // Laporan komisi titip jual (konsinyasi)
    // ------------------------------------------------------------------

    /**
     * Ringkasan transaksi titipan SELESAI dalam rentang UTC [$from, $to].
     *  - commission   : komisi toko (sales.commission_amount)
     *  - payout       : bagian pemilik akun (sales.consignor_payout)
     *  - unpaid_*     : bagian pemilik yang belum disetor (consignor_paid_at kosong)
     */
    public static function consignmentSummary(string $from, string $to): array
    {
        $row = DB::table('sales')
            ->where('sales.is_consignment', 1)
            ->where('sales.transaction_status', 'completed')
            ->whereRaw(self::TIME_EXPR . ' BETWEEN ? AND ?', [$from, $to])
            ->selectRaw(
                'COUNT(*) as sold, '
                . 'COALESCE(SUM(sales.sale_price), 0) as revenue, '
                . 'COALESCE(SUM(sales.commission_amount), 0) as commission, '
                . 'COALESCE(SUM(sales.consignor_payout), 0) as payout, '
                . 'COALESCE(SUM(CASE WHEN sales.consignor_paid_at IS NULL THEN sales.consignor_payout ELSE 0 END), 0) as unpaid_total, '
                . 'COALESCE(SUM(CASE WHEN sales.consignor_paid_at IS NULL THEN 1 ELSE 0 END), 0) as unpaid_count, '
                . 'COALESCE(SUM(CASE WHEN sales.consignor_paid_at IS NULL THEN 0 ELSE sales.consignor_payout END), 0) as paid_total, '
                . 'COALESCE(SUM(CASE WHEN sales.consignor_paid_at IS NULL THEN 0 ELSE 1 END), 0) as paid_count'
            )
            ->first();

        return [
            'sold'         => (int) ($row->sold ?? 0),
            'revenue'      => (float) ($row->revenue ?? 0),
            'commission'   => (float) ($row->commission ?? 0),
            'payout'       => (float) ($row->payout ?? 0),
            'unpaid_total' => (float) ($row->unpaid_total ?? 0),
            'unpaid_count' => (int) ($row->unpaid_count ?? 0),
            'paid_total'   => (float) ($row->paid_total ?? 0),
            'paid_count'   => (int) ($row->paid_count ?? 0),
        ];
    }

    /**
     * Pemilik akun titipan, diurutkan dari yang paling aktif (jumlah akun terjual terbanyak
     * dalam rentang, lalu komisi terbesar).
     *
     * @return Collection<int, object{name:string, phone:?string, sold:int, revenue:float, commission:float, payout:float, unpaid:float}>
     */
    public static function consignmentOwners(string $from, string $to): Collection
    {
        return DB::table('sales')
            ->where('sales.is_consignment', 1)
            ->where('sales.transaction_status', 'completed')
            ->whereRaw(self::TIME_EXPR . ' BETWEEN ? AND ?', [$from, $to])
            ->groupBy('sales.consignor_name', 'sales.consignor_phone')
            ->selectRaw(
                'sales.consignor_name as name, sales.consignor_phone as phone, COUNT(*) as sold, '
                . 'SUM(sales.sale_price) as revenue, '
                . 'SUM(sales.commission_amount) as commission, '
                . 'SUM(sales.consignor_payout) as payout, '
                . 'SUM(CASE WHEN sales.consignor_paid_at IS NULL THEN sales.consignor_payout ELSE 0 END) as unpaid'
            )
            ->orderByDesc('sold')
            ->orderByDesc('commission')
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => (object) [
                'name'       => $r->name ?: '(tanpa nama)',
                'phone'      => $r->phone ? preg_replace('/^62/', '0', $r->phone) : null,
                'sold'       => (int) $r->sold,
                'revenue'    => (float) $r->revenue,
                'commission' => (float) $r->commission,
                'payout'     => (float) $r->payout,
                'unpaid'     => (float) $r->unpaid,
            ]);
    }

    /**
     * Setoran ke pemilik yang masih menunggu, dari SEMUA bulan (bukan hanya bulan laporan),
     * supaya utang setoran bulan lalu tidak terlewat.
     *
     * @return array{count:int, total:float, oldest:?string}
     */
    public static function consignmentUnpaidAllTime(): array
    {
        $row = DB::table('sales')
            ->where('sales.is_consignment', 1)
            ->where('sales.transaction_status', 'completed')
            ->whereNull('sales.consignor_paid_at')
            ->selectRaw(
                'COUNT(*) as total_count, COALESCE(SUM(sales.consignor_payout), 0) as total, '
                . 'MIN(' . self::TIME_EXPR . ') as oldest'
            )
            ->first();

        $oldest = ($row && $row->oldest)
            ? Carbon::parse($row->oldest, 'UTC')->timezone(self::TZ)->locale('id')->translatedFormat('d M Y')
            : null;

        return [
            'count'  => (int) ($row->total_count ?? 0),
            'total'  => (float) ($row->total ?? 0),
            'oldest' => $oldest,
        ];
    }

    /** Data lengkap laporan komisi titipan satu bulan (WIB). */
    public static function consignment(?string $month): array
    {
        $month = self::normalizeMonth($month);
        $range = self::monthRange($month);
        $prev  = self::monthRange($range['start']->copy()->subMonthNoOverflow()->format('Y-m'));

        $summary     = self::consignmentSummary($range['from'], $range['to']);
        $prevSummary = self::consignmentSummary($prev['from'], $prev['to']);
        $owners      = self::consignmentOwners($range['from'], $range['to']);

        // Pemilik teraktif: bisa lebih dari satu kalau jumlah terjualnya sama-sama tertinggi
        $max     = (int) ($owners->max('sold') ?? 0);
        $topList = $max > 0 ? $owners->where('sold', $max)->pluck('name')->unique()->values()->all() : [];

        $commissionChange = $prevSummary['commission'] != 0.0
            ? ($summary['commission'] - $prevSummary['commission']) / abs($prevSummary['commission']) * 100
            : null;

        $transactions = Sale::with('account')
            ->consignment()
            ->completed()
            ->whereRaw(self::TIME_EXPR . ' BETWEEN ? AND ?', [$range['from'], $range['to']])
            ->orderByRaw(self::TIME_EXPR . ' asc')
            ->orderBy('sales.id')
            ->limit(5000)
            ->get();

        return [
            'month'            => $month,
            'range'            => $range,
            'prevLabel'        => $prev['label'],
            'summary'          => $summary,
            'prev'             => $prevSummary,
            'commissionChange' => $commissionChange,
            'unpaidAll'        => self::consignmentUnpaidAllTime(),
            'owners'           => $owners,
            'topOwners'        => $topList,
            'transactions'     => $transactions,
            'rows'             => self::consignmentRows($transactions),
        ];
    }

    /** Ubah koleksi Sale titipan menjadi baris datar siap tampil / ekspor. */
    public static function consignmentRows(Collection $sales): array
    {
        return $sales->map(function (Sale $s) {
            $account = $s->account;
            $time    = ($s->sold_at ?? $s->created_at)->clone()->timezone(self::TZ)->locale('id');
            $paidAt  = $s->consignor_paid_at?->clone()->timezone(self::TZ)->locale('id');

            return [
                'time'       => $time->translatedFormat('d M Y H:i'),
                'code'       => $s->transaction_code,
                'acc_code'   => $s->account_code_snapshot,
                'name'       => $account?->name ?? '(akun dihapus)',
                'owner'      => $s->consignor_name ?: '-',
                'phone'      => $s->consignorLocalPhone() ?: '-',
                'price'      => (float) $s->sale_price,
                'commission' => (float) $s->commission_amount,
                'payout'     => (float) $s->consignor_payout,
                'paid'       => $paidAt !== null,
                'paid_label' => $paidAt ? 'Lunas ' . $paidAt->translatedFormat('d M Y') : 'Belum disetor',
            ];
        })->all();
    }

    public static function rupiah(float|int $n): string
    {
        return ($n < 0 ? '-' : '') . 'Rp ' . number_format(abs($n), 0, ',', '.');
    }
}