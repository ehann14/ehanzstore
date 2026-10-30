<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CocAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Daftar akun/clan yang belum terjual (sale_status = available)
 * beserta total harga jual, total modal, dan estimasi labanya.
 */
class UnsoldAccountController extends Controller
{
    /** Pilihan urutan: kunci => label. */
    public const SORTS = [
        'price_desc'  => 'Harga tertinggi',
        'price_asc'   => 'Harga terendah',
        'oldest'      => 'Paling lama terpajang',
        'newest'      => 'Terbaru',
        'profit_desc' => 'Laba terbesar',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'type'        => ['nullable', Rule::in(['account', 'clan'])],
            'publication' => ['nullable', Rule::in(['published', 'draft'])],
            'q'           => ['nullable', 'string', 'max:100'],
            'sort'        => ['nullable', Rule::in(array_keys(self::SORTS))],
        ]);

        $type        = $filters['type'] ?? null;
        $publication = $filters['publication'] ?? null;
        $search      = trim((string) ($filters['q'] ?? ''));
        $sort        = $filters['sort'] ?? 'price_desc';

        // Basis: semua yang belum terjual. Akun yang dihapus (soft delete) otomatis tidak ikut.
        $unsold = fn () => CocAccount::where('sale_status', 'available');

        $filtered = fn () => $unsold()
            ->when($type, fn ($q) => $q->where('listing_type', $type))
            ->when($publication, fn ($q) => $q->where('publication_status', $publication))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';

                $q->where(function ($w) use ($like) {
                    $w->where('name', 'like', $like)
                      ->orWhere('account_code', 'like', $like);
                });
            });

        // Total dihitung dari SEMUA hasil filter (bukan hanya halaman yang sedang tampil).
        // Estimasi laba hanya memakai akun yang modalnya sudah diisi, supaya tidak menggelembung.
        $row = $filtered()->selectRaw(
            'COUNT(*) as total_count,'
            . ' COALESCE(SUM(price), 0) as total_price,'
            . ' COALESCE(SUM(CASE WHEN is_consignment = 1 THEN 0 ELSE cost_price END), 0) as total_capital,'
            . ' COALESCE(SUM(CASE WHEN cost_price > 0 THEN price - cost_price ELSE 0 END), 0) as total_profit,'
            . ' COALESCE(SUM(CASE WHEN cost_price > 0 THEN 0 ELSE 1 END), 0) as no_cost_count'
        )->first();

        $totals = [
            'count'    => (int) $row->total_count,
            'price'    => (float) $row->total_price,
            'capital'  => (float) $row->total_capital,
            'profit'   => (float) $row->total_profit,
            'no_cost'  => (int) $row->no_cost_count,
        ];

        $query = $filtered()->with(['primaryImage', 'creator']);

        match ($sort) {
            'price_asc'   => $query->orderBy('price')->orderBy('id'),
            'oldest'      => $query->orderBy('created_at')->orderBy('id'),
            'newest'      => $query->orderByDesc('created_at')->orderByDesc('id'),
            'profit_desc' => $query->orderByRaw('(price - cost_price) DESC')->orderBy('id'),
            default       => $query->orderByDesc('price')->orderBy('id'),
        };

        $accounts = $query->paginate(20)->withQueryString();

        // Jumlah per jenis & per status terbit (untuk label tab filter)
        $typeCounts = $unsold()
            ->select('listing_type', DB::raw('COUNT(*) as n'))
            ->groupBy('listing_type')
            ->pluck('n', 'listing_type');

        $pubCounts = $unsold()
            ->select('publication_status', DB::raw('COUNT(*) as n'))
            ->groupBy('publication_status')
            ->pluck('n', 'publication_status');

        $counts = [
            'all'       => (int) $typeCounts->sum(),
            'account'   => (int) ($typeCounts['account'] ?? 0),
            'clan'      => (int) ($typeCounts['clan'] ?? 0),
            'published' => (int) ($pubCounts['published'] ?? 0),
            'draft'     => (int) ($pubCounts['draft'] ?? 0),
        ];

        $heldIds = Booking::heldAccountIds();
        $sorts   = self::SORTS;

        return view('super-admin.unsold', compact(
            'accounts', 'totals', 'counts', 'heldIds', 'sorts',
            'type', 'publication', 'search', 'sort'
        ));
    }
}