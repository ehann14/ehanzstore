<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CocAccount;
use App\Models\VisitorEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Pengingat stok lama: akun tersedia yang belum laku lebih dari N hari
 * (config store.stale_days), lengkap dengan saran penurunan harga.
 */
class StaleStockController extends Controller
{
    public function index(Request $request)
    {
        $days  = (int) config('store.stale_days', 30);
        $owner = $this->ownerId();

        $type = in_array($request->input('type'), ['account', 'clan'], true) ? $request->input('type') : null;

        $base = fn () => CocAccount::stale($days)
            ->when($owner, fn ($q) => $q->where('created_by', $owner));

        $summary = CocAccount::staleSummary($owner);

        // Paling lama menumpuk di atas
        $accounts = $base()
            ->when($type, fn ($q) => $q->where('listing_type', $type))
            ->with('primaryImage')
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        // Jumlah kunjungan 30 hari terakhir per akun: ramai dilihat tapi tak laku = harga/foto perlu dicek;
        // sepi = perlu dipromosikan.
        $views = $accounts->isEmpty() ? collect() : VisitorEvent::query()
            ->where('event_type', 'view_account')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('coc_account_id', $accounts->pluck('id'))
            ->select('coc_account_id', DB::raw('COUNT(*) as total'))
            ->groupBy('coc_account_id')
            ->pluck('total', 'coc_account_id');

        $counts = [
            'all'     => $summary['count'],
            'account' => $base()->where('listing_type', 'account')->count(),
            'clan'    => $base()->where('listing_type', 'clan')->count(),
        ];

        return view('admin.stale.index', compact('accounts', 'summary', 'views', 'days', 'type', 'counts'));
    }

    /** Terapkan saran harga dengan satu klik. Harga dihitung ulang di server, bukan dari form. */
    public function applyPrice(CocAccount $account)
    {
        $user = Auth::user();

        if ($account->created_by !== $user->id && ! $user->isSuperAdmin()) {
            abort(403);
        }

        $isStale = CocAccount::stale()->whereKey($account->id)->exists();

        if (! $isStale) {
            return back()->with('error', 'Akun ini tidak lagi termasuk stok lama.');
        }

        // Promo berbatas waktu akan mengembalikan harga ke harga normal saat berakhir:
        // ubah lewat form edit supaya tidak tertimpa tanpa disadari.
        if ($account->dealEndsAt()) {
            return back()->with('error', 'Akun ini sedang punya promo berbatas waktu. Ubah harganya lewat halaman edit.');
        }

        $suggestion = $account->priceSuggestion();

        if (! $suggestion) {
            return back()->with('error', 'Tidak ada ruang untuk menurunkan harga lagi tanpa menyentuh modal.');
        }

        $old = (float) $account->price;

        $account->update(['price' => $suggestion['price']]);

        ActivityLog::record('apply_price_suggestion', $account, 'Akun ' . $account->account_code, [
            'old_price'   => $old,
            'new_price'   => $suggestion['price'],
            'days_listed' => $account->daysListed(),
        ]);

        return back()->with(
            'success',
            $account->account_code . ': harga turun dari Rp ' . number_format($old, 0, ',', '.')
                . ' menjadi Rp ' . number_format($suggestion['price'], 0, ',', '.') . '.'
        );
    }

    /** Admin biasa hanya melihat akun miliknya; Super Admin melihat semua. */
    private function ownerId(): ?int
    {
        return Auth::user()->isSuperAdmin() ? null : Auth::id();
    }
}