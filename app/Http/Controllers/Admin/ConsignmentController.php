<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CocAccount;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Titip jual (konsinyasi): rekap akun titipan, komisi toko, dan setoran ke pemilik akun.
 *
 *  - Tab "Belum disetor" : akun titipan yang sudah terjual tapi bagian pemilik belum dibayar.
 *  - Tab "Sudah disetor" : riwayat setoran yang sudah dilunasi.
 *  - Tab "Stok titipan"  : akun titipan yang masih tersedia.
 *
 * Super Admin melihat semua; admin biasa hanya titipan yang ia posting / proses.
 */
class ConsignmentController extends Controller
{
    private const TABS = ['unpaid', 'paid', 'stock'];

    public function index(Request $request)
    {
        $tab    = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'unpaid';
        $search = trim((string) $request->query('q', ''));
        $user   = Auth::user();
        $isSuper = $user->isSuperAdmin();

        // Transaksi titipan yang selesai (cakupan sesuai peran)
        $sales = fn () => Sale::consignment()
            ->completed()
            ->when(! $isSuper, function ($q) use ($user) {
                $q->where(function ($w) use ($user) {
                    $w->where('processed_by', $user->id)
                      ->orWhereHas('account', fn ($a) => $a->where('created_by', $user->id));
                });
            });

        // Stok titipan yang masih tersedia
        $stock = fn () => CocAccount::consignment()
            ->where('sale_status', 'available')
            ->when(! $isSuper, fn ($q) => $q->where('created_by', $user->id));

        $like = $search !== '' ? '%' . addcslashes($search, '%_\\') . '%' : null;

        // Ringkasan (tidak terpengaruh pencarian)
        $row = $sales()->selectRaw(
            'COUNT(*) as sold_count,'
            . ' COALESCE(SUM(commission_amount), 0) as commission,'
            . ' COALESCE(SUM(CASE WHEN consignor_paid_at IS NULL THEN consignor_payout ELSE 0 END), 0) as unpaid_total,'
            . ' COALESCE(SUM(CASE WHEN consignor_paid_at IS NULL THEN 1 ELSE 0 END), 0) as unpaid_count,'
            . ' COALESCE(SUM(CASE WHEN consignor_paid_at IS NULL THEN 0 ELSE consignor_payout END), 0) as paid_total,'
            . ' COALESCE(SUM(CASE WHEN consignor_paid_at IS NULL THEN 0 ELSE 1 END), 0) as paid_count'
        )->first();

        $stockRow = $stock()->selectRaw('COUNT(*) as total, COALESCE(SUM(price), 0) as value')->first();

        $summary = [
            'sold'         => (int) $row->sold_count,
            'commission'   => (float) $row->commission,
            'unpaid_total' => (float) $row->unpaid_total,
            'unpaid_count' => (int) $row->unpaid_count,
            'paid_total'   => (float) $row->paid_total,
            'paid_count'   => (int) $row->paid_count,
            'stock_count'  => (int) $stockRow->total,
            'stock_value'  => (float) $stockRow->value,
        ];

        // Rekap per pemilik untuk tab "Belum disetor": siapa yang harus dibayar & berapa
        $owners = collect();

        if ($tab === 'unpaid') {
            $owners = $sales()
                ->whereNull('consignor_paid_at')
                ->selectRaw('consignor_name, consignor_phone, COUNT(*) as total, COALESCE(SUM(consignor_payout), 0) as amount')
                ->groupBy('consignor_name', 'consignor_phone')
                ->orderByDesc('amount')
                ->get();
        }

        if ($tab === 'stock') {
            $items = $stock()
                ->with('primaryImage')
                ->when($like, function ($q) use ($like) {
                    $q->where(function ($w) use ($like) {
                        $w->where('account_code', 'like', $like)
                          ->orWhere('name', 'like', $like)
                          ->orWhere('consignor_name', 'like', $like)
                          ->orWhere('consignor_phone', 'like', $like);
                    });
                })
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString();
        } else {
            $query = $sales()
                ->with('account')
                ->when($tab === 'paid', fn ($q) => $q->whereNotNull('consignor_paid_at'), fn ($q) => $q->whereNull('consignor_paid_at'))
                ->when($like, function ($q) use ($like) {
                    $q->where(function ($w) use ($like) {
                        $w->where('account_code_snapshot', 'like', $like)
                          ->orWhere('consignor_name', 'like', $like)
                          ->orWhere('consignor_phone', 'like', $like)
                          ->orWhere('transaction_code', 'like', $like);
                    });
                });

            // Belum disetor: yang paling lama menunggu di atas. Sudah disetor: yang terbaru di atas.
            $items = ($tab === 'paid'
                ? $query->orderByDesc('consignor_paid_at')
                : $query->orderBy('sold_at'))
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString();
        }

        return view('admin.consignments.index', compact('tab', 'search', 'summary', 'owners', 'items'));
    }

    /** Tandai bagian pemilik sudah disetor (dibayar). Aman bila diklik dua kali. */
    public function markPaid(Sale $sale)
    {
        $this->authorizeSale($sale);

        if ($sale->consignorIsPaid()) {
            return back()->with('error', 'Setoran ini sudah ditandai lunas sebelumnya.');
        }

        $sale->update(['consignor_paid_at' => now()]);

        ActivityLog::record('pay_consignor', $sale->account, 'Akun ' . $sale->account_code_snapshot, [
            'transaction' => $sale->transaction_code,
            'consignor'   => $sale->consignor_name,
            'payout'      => $sale->consignor_payout,
        ]);

        return back()->with(
            'success',
            'Setoran ' . $sale->account_code_snapshot . ' ke ' . $sale->consignor_name
                . ' (Rp ' . number_format((float) $sale->consignor_payout, 0, ',', '.') . ') ditandai lunas.'
        );
    }

    /** Batalkan tanda lunas (salah klik / pembayaran dikembalikan). */
    public function markUnpaid(Sale $sale)
    {
        $this->authorizeSale($sale);

        if (! $sale->consignorIsPaid()) {
            return back()->with('error', 'Setoran ini memang belum ditandai lunas.');
        }

        $sale->update(['consignor_paid_at' => null]);

        ActivityLog::record('unpay_consignor', $sale->account, 'Akun ' . $sale->account_code_snapshot, [
            'transaction' => $sale->transaction_code,
            'consignor'   => $sale->consignor_name,
            'payout'      => $sale->consignor_payout,
        ]);

        return back()->with('success', 'Tanda lunas dibatalkan. Setoran ' . $sale->account_code_snapshot . ' kembali ke daftar belum disetor.');
    }

    /** Hanya transaksi titipan yang selesai; admin biasa hanya untuk akun miliknya / yang ia proses. */
    private function authorizeSale(Sale $sale): void
    {
        abort_if(! $sale->is_consignment || $sale->transaction_status !== 'completed', 404);

        $user = Auth::user();

        if (! $user->isSuperAdmin()
            && $sale->account?->created_by !== $user->id
            && $sale->processed_by !== $user->id) {
            abort(403);
        }
    }
}