<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CocAccount;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Link titip untuk penjual (tanpa login).
 *
 * Alur:
 *  1. Admin mengisi NAMA saja -> dibuat akun draft titipan + link sekali pakai.
 *  2. Admin membagikan link ke penjual. Penjual mengisi data & foto sendiri (SellerSubmissionController).
 *  3. Setelah penjual mengirim, link mati. Admin meninjau di Kelola Akun lalu menerbitkan.
 *
 * Super Admin melihat semua; admin biasa hanya link yang ia buat.
 */
class SellerLinkController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $accounts = CocAccount::query()
            ->whereIn('submission_status', [CocAccount::SUBMISSION_PENDING, CocAccount::SUBMISSION_SUBMITTED])
            // yang sudah diterbitkan tidak perlu ditinjau lagi
            ->where(function ($q) {
                $q->where('submission_status', CocAccount::SUBMISSION_PENDING)
                  ->orWhere('publication_status', 'draft');
            })
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->where('created_by', $user->id))
            ->withCount('images')
            ->with('primaryImage')
            ->latest('id')
            ->get();

        $ready   = $accounts->where('submission_status', CocAccount::SUBMISSION_SUBMITTED)->values();
        $waiting = $accounts->where('submission_status', CocAccount::SUBMISSION_PENDING)->values();
        $days    = max(1, (int) config('store.seller_link_days', 7));

        return view('admin.seller-links.index', compact('ready', 'waiting', 'days'));
    }

    /** Buat akun draft (nama saja) + link untuk penjual. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'name.required' => 'Isi nama dulu (misal nama penjual atau label akunnya).',
            'name.min'      => 'Nama minimal 2 karakter.',
            'name.max'      => 'Nama maksimal 100 karakter.',
        ]);

        $account = $this->createWithFreshCode([
            'account_code'          => $this->newCode(),
            'name'                  => trim($data['name']),
            'listing_type'          => 'account',
            'town_hall'             => 0,   // diisi penjual
            'price'                 => 0,   // diisi penjual
            'cost_price'            => 0,
            'protection_status'     => 'Unprotected',
            'email_change_status'   => 'Unchangeable',
            'is_consignment'        => true,
            'commission_type'       => 'percent',
            'commission_value'      => max(0, (int) config('store.consign_commission_percent', 15)),
            'publication_status'    => 'draft',
            'sale_status'           => 'available',
            'created_by'            => Auth::id(),
            'submission_token'      => CocAccount::newSubmissionToken(),
            'submission_status'     => CocAccount::SUBMISSION_PENDING,
            'submission_expires_at' => now()->addDays(max(1, (int) config('store.seller_link_days', 7))),
        ]);

        ActivityLog::record('create_seller_link', $account, 'Link titip ' . $account->name);

        return redirect()->route('admin.seller-links.index')
            ->with('success', 'Link untuk "' . $account->name . '" sudah dibuat. Salin dan kirim ke penjual.');
    }

    /** Buat ulang link (token baru + masa berlaku baru) untuk yang belum diisi atau sudah kedaluwarsa. */
    public function renew(CocAccount $account)
    {
        $this->authorizeAccount($account);

        if ($account->submission_status !== CocAccount::SUBMISSION_PENDING) {
            return back()->with('error', 'Penjual sudah mengisi data akun ini, link tidak perlu dibuat ulang.');
        }

        $account->update([
            'submission_token'      => CocAccount::newSubmissionToken(),
            'submission_expires_at' => now()->addDays(max(1, (int) config('store.seller_link_days', 7))),
        ]);

        return back()->with('success', 'Link baru dibuat. Link yang lama tidak bisa dipakai lagi.');
    }

    /** Batalkan link yang belum diisi: draft kosongnya ikut dihapus. */
    public function destroy(CocAccount $account)
    {
        $this->authorizeAccount($account);

        if ($account->submission_status !== CocAccount::SUBMISSION_PENDING) {
            return back()->with('error', 'Hanya link yang belum diisi penjual yang bisa dibatalkan.');
        }

        $name = $account->name;
        $account->delete();

        ActivityLog::record('delete_account', null, 'Link titip ' . $name . ' dibatalkan');

        return back()->with('success', 'Link "' . $name . '" dibatalkan.');
    }

    /** Kode otomatis untuk titipan: EHZ-T1, EHZ-T2, dst. */
    private function newCode(): string
    {
        return CocAccount::nextCode('account', true);
    }

    /** Simpan akun; bila nomor kode keburu dipakai orang lain, ambil nomor berikutnya lalu coba lagi. */
    private function createWithFreshCode(array $attributes): CocAccount
    {
        for ($try = 1; ; $try++) {
            try {
                return CocAccount::create($attributes);
            } catch (QueryException $e) {
                if ($try >= 5 || ! str_contains($e->getMessage(), 'account_code')) {
                    throw $e;
                }

                $attributes['account_code'] = $this->newCode();
            }
        }
    }

    private function authorizeAccount(CocAccount $account): void
    {
        if ($account->created_by !== Auth::id() && ! Auth::user()->isSuperAdmin()) {
            abort(403);
        }
    }
}