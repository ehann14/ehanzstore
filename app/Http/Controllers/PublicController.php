<?php

namespace App\Http\Controllers;

use App\Models\CocAccount;
use App\Models\VisitorEvent;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Halaman utama.
     */
    public function home()
    {
        $featuredAccounts = CocAccount::available()
            ->with('primaryImage')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return view('public.home', compact('featuredAccounts'));
    }

    /**
     * Katalog akun dengan pencarian, filter, dan pengurutan.
     */
    public function catalog(Request $request)
    {
        $query = CocAccount::available()->with('primaryImage');

        // Pencarian berdasarkan nama atau kode akun
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('account_code', 'like', '%' . $search . '%');
            });
        }

        // Filter Town Hall
        if ($request->filled('th')) {
            $query->where('town_hall', $request->input('th'));
        }

        // Pengurutan katalog
        switch ($request->input('sort')) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $accounts = $query->paginate(12)->withQueryString();

        return view('public.catalog', compact('accounts'));
    }

    /**
     * Detail akun berdasarkan kode akun.
     */
    public function detail($account_code)
    {
        $account = CocAccount::available()
            ->with('primaryImage')
            ->where('account_code', $account_code)
            ->firstOrFail();

        // Catat kunjungan ke detail akun
        $this->trackEvent('view_account', request()->path(), $account->id);

        return view('public.detail', compact('account'));
    }

    /**
     * Mencatat aktivitas pengunjung.
     */
    public function trackEvent(string $type, ?string $path = null, ?int $accountId = null)
    {
        return VisitorEvent::create([
            'anonymous_session_id' => session()->getId(),
            'event_type' => $type,
            'page_path' => $path ?? request()->path(),
            'coc_account_id' => $accountId,
            'created_at' => now(),
        ]);
    }
    public function howToBuy()
{
    return view('public.how-to-buy');
}

}