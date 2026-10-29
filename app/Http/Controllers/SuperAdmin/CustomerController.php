<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\CustomerNote;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Halaman pelanggan: pembeli dikelompokkan per nomor WhatsApp (sales.buyer_phone).
 * Hanya transaksi selesai yang dihitung. Pembeli berulang = 2 transaksi selesai atau lebih.
 */
class CustomerController extends Controller
{
    public const SORTS = [
        'recent' => 'Pembelian terbaru',
        'orders' => 'Pembelian terbanyak',
        'spent'  => 'Total belanja terbesar',
    ];

    public const FILTERS = [
        'all'    => 'Semua',
        'repeat' => 'Pembeli berulang',
        'noted'  => 'Punya catatan',
    ];

    /** Transaksi selesai yang punya nomor WhatsApp pembeli. */
    private function baseSales()
    {
        return DB::table('sales')
            ->where('transaction_status', 'completed')
            ->whereNotNull('buyer_phone')
            ->where('buyer_phone', '!=', '');
    }

    /** Waktu dari database (UTC) -> objek Carbon di zona waktu tampilan (WIB). */
    private function localTime(?string $value): ?Carbon
    {
        return $value
            ? Carbon::parse($value, config('app.timezone'))->timezone(Sale::DISPLAY_TZ)
            : null;
    }

    public function index(Request $request)
    {
        $input = $request->validate([
            'filter' => ['nullable', Rule::in(array_keys(self::FILTERS))],
            'q'      => ['nullable', 'string', 'max:100'],
            'sort'   => ['nullable', Rule::in(array_keys(self::SORTS))],
        ]);

        $filter = $input['filter'] ?? 'all';
        $search = trim((string) ($input['q'] ?? ''));
        $sort   = $input['sort'] ?? 'recent';

        $query = $this->baseSales()
            ->selectRaw(
                'buyer_phone,'
                . ' COUNT(*) as orders,'
                . ' COALESCE(SUM(sale_price), 0) as total_spent,'
                . ' COALESCE(SUM(sale_price - COALESCE(cost_price, 0)), 0) as total_profit,'
                . ' MAX(COALESCE(sold_at, created_at)) as last_at,'
                . ' MIN(COALESCE(sold_at, created_at)) as first_at'
            )
            ->groupBy('buyer_phone');

        if ($search !== '') {
            $like      = '%' . addcslashes($search, '%_\\') . '%';
            $phoneLike = Sale::normalizePhone($search);
            $phoneLike = $phoneLike ? '%' . addcslashes($phoneLike, '%_\\') . '%' : null;

            // Cocok bila nama pembeli, nomor WA, atau catatannya mengandung kata yang dicari.
            $query->where(function ($w) use ($like, $phoneLike) {
                $w->whereIn('buyer_phone', function ($s) use ($like, $phoneLike) {
                    $s->from('sales')->select('buyer_phone')
                      ->where(function ($x) use ($like, $phoneLike) {
                          $x->where('buyer_name', 'like', $like);

                          if ($phoneLike) {
                              $x->orWhere('buyer_phone', 'like', $phoneLike);
                          }
                      });
                })->orWhereIn('buyer_phone', function ($s) use ($like) {
                    $s->from('customer_notes')->select('phone')->where('note', 'like', $like);
                });
            });
        }

        if ($filter === 'repeat') {
            $query->havingRaw('COUNT(*) >= 2');
        } elseif ($filter === 'noted') {
            $query->whereIn('buyer_phone', function ($s) {
                $s->from('customer_notes')->select('phone')->whereNotNull('note')->where('note', '!=', '');
            });
        }

        match ($sort) {
            'orders' => $query->orderByDesc('orders')->orderByDesc('last_at'),
            'spent'  => $query->orderByDesc('total_spent')->orderByDesc('last_at'),
            default  => $query->orderByDesc('last_at'),
        };

        $customers = $query->orderBy('buyer_phone')->paginate(20)->withQueryString();

        // Nama terbaru + catatan untuk nomor yang tampil di halaman ini
        $phones = $customers->pluck('buyer_phone')->all();

        $names = DB::table('sales')
            ->whereIn('buyer_phone', $phones)
            ->whereNotNull('buyer_name')
            ->where('buyer_name', '!=', '')
            ->orderByDesc('id')
            ->get(['buyer_phone', 'buyer_name'])
            ->unique('buyer_phone')
            ->pluck('buyer_name', 'buyer_phone');

        $notes = CustomerNote::whereIn('phone', $phones)->get()->keyBy('phone');

        $customers->getCollection()->transform(function ($c) use ($names, $notes) {
            $c->name       = $names[$c->buyer_phone] ?? 'Tanpa nama';
            $c->note       = trim((string) ($notes[$c->buyer_phone]->note ?? ''));
            $c->local      = preg_replace('/^62/', '0', $c->buyer_phone);
            $c->wa_url     = 'https://wa.me/' . $c->buyer_phone;
            $c->last_time  = $this->localTime($c->last_at);
            $c->first_time = $this->localTime($c->first_at);

            return $c;
        });

        // Ringkasan seluruh pelanggan (tidak ikut filter)
        $perPhone = $this->baseSales()
            ->selectRaw('buyer_phone, COUNT(*) as n, COALESCE(SUM(sale_price), 0) as spent')
            ->groupBy('buyer_phone');

        $row = DB::query()->fromSub($perPhone, 't')->selectRaw(
            'COUNT(*) as customers,'
            . ' COALESCE(SUM(CASE WHEN n >= 2 THEN 1 ELSE 0 END), 0) as repeaters,'
            . ' COALESCE(SUM(n), 0) as orders,'
            . ' COALESCE(SUM(spent), 0) as spent,'
            . ' COALESCE(SUM(CASE WHEN n >= 2 THEN spent ELSE 0 END), 0) as repeat_spent'
        )->first();

        $summary = [
            'customers'    => (int) $row->customers,
            'repeaters'    => (int) $row->repeaters,
            'orders'       => (int) $row->orders,
            'spent'        => (float) $row->spent,
            'repeat_spent' => (float) $row->repeat_spent,
            'repeat_rate'  => $row->customers > 0 ? round($row->repeaters / $row->customers * 100) : 0,
        ];

        $counts = [
            'all'    => $summary['customers'],
            'repeat' => $summary['repeaters'],
            'noted'  => CustomerNote::whereNotNull('note')->where('note', '!=', '')->count(),
        ];

        // Transaksi selesai yang tidak punya nomor WA tidak bisa dikelompokkan ke pelanggan.
        $withoutPhone = DB::table('sales')
            ->where('transaction_status', 'completed')
            ->where(fn ($q) => $q->whereNull('buyer_phone')->orWhere('buyer_phone', ''))
            ->count();

        $sorts   = self::SORTS;
        $filters = self::FILTERS;

        return view('super-admin.customers', compact(
            'customers', 'summary', 'counts', 'withoutPhone', 'sorts', 'filters',
            'filter', 'search', 'sort'
        ));
    }

    public function show(string $phone)
    {
        $sales = Sale::with(['account', 'review'])
            ->where('buyer_phone', $phone)
            ->orderByRaw('COALESCE(sold_at, created_at) DESC')
            ->get();

        $note = CustomerNote::with('updater')->where('phone', $phone)->first();

        abort_if($sales->isEmpty() && ! $note, 404);

        $completed = $sales->where('transaction_status', 'completed');

        $stats = [
            'orders'   => $completed->count(),
            'spent'    => (float) $completed->sum('sale_price'),
            'profit'   => (float) $completed->sum(fn (Sale $s) => $s->profit()),
            'average'  => $completed->count() > 0 ? (float) $completed->sum('sale_price') / $completed->count() : 0,
            'warranty' => $completed->filter(fn (Sale $s) => $s->warrantyIsActive())->count(),
        ];

        $lastSale  = $completed->first();
        $firstSale = $completed->last();

        $stats['last']  = $lastSale ? ($lastSale->sold_at ?? $lastSale->created_at)->clone()->timezone(Sale::DISPLAY_TZ) : null;
        $stats['first'] = $firstSale ? ($firstSale->sold_at ?? $firstSale->created_at)->clone()->timezone(Sale::DISPLAY_TZ) : null;

        $name  = $sales->first(fn (Sale $s) => filled($s->buyer_name))?->buyer_name ?? 'Tanpa nama';
        $names = $sales->pluck('buyer_name')->filter()->unique()->values();

        $bookings = Booking::where('buyer_phone', $phone)->latest('id')->limit(20)->get();

        $local = preg_replace('/^62/', '0', $phone);
        $waUrl = 'https://wa.me/' . $phone;

        return view('super-admin.customer-show', compact(
            'phone', 'local', 'waUrl', 'name', 'names', 'sales', 'stats', 'bookings', 'note'
        ));
    }

    public function updateNote(Request $request, string $phone)
    {
        $data = $request->validate(
            ['note' => ['nullable', 'string', 'max:2000']],
            ['note.max' => 'Catatan maksimal 2000 karakter.']
        );

        $text  = trim((string) ($data['note'] ?? ''));
        $local = preg_replace('/^62/', '0', $phone);

        // Hanya nomor yang pernah bertransaksi (atau sudah punya catatan) yang boleh dicatat.
        $known = Sale::where('buyer_phone', $phone)->exists() || CustomerNote::where('phone', $phone)->exists();
        abort_unless($known, 404);

        $existing = CustomerNote::where('phone', $phone)->first();

        if ($text === '') {
            if ($existing) {
                ActivityLog::record('update_customer_note', $existing, 'Hapus catatan pelanggan ' . $local);
                $existing->delete();
            }

            return back()->with('success', 'Catatan pelanggan dihapus.');
        }

        $note = CustomerNote::updateOrCreate(
            ['phone' => $phone],
            ['note' => $text, 'updated_by' => auth()->id()]
        );

        ActivityLog::record('update_customer_note', $note, 'Catatan pelanggan ' . $local);

        return back()->with('success', 'Catatan pelanggan disimpan.');
    }
}