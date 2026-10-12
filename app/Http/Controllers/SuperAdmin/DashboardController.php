<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CocAccount;
use App\Models\Sale;
use App\Models\User;
use App\Models\VisitorEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard utama Super Admin dengan statistik analitik.
     */
    public function index()
    {
        // 1. Statistik Pengunjung
        $totalVisitors = VisitorEvent::distinct('anonymous_session_id')->count('anonymous_session_id');
        
        $todayVisitors = VisitorEvent::whereDate('created_at', today())
            ->distinct('anonymous_session_id')
            ->count('anonymous_session_id');

        // 2. Statistik Katalog
        $totalAccounts = CocAccount::count();
        $availableAccounts = CocAccount::where('sale_status', 'available')->count();
        $soldAccounts = CocAccount::where('sale_status', 'sold')->count();

        // 3. Data Grafik Pengunjung (7 Hari Terakhir)
        $visitorStats = VisitorEvent::select(
                DB::raw('DATE(created_at) as date'), 
                DB::raw('COUNT(DISTINCT anonymous_session_id) as count')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 4. Akun Paling Sering Dilihat
        $topAccounts = VisitorEvent::select('coc_account_id', DB::raw('count(*) as views'))
            ->whereNotNull('coc_account_id')
            ->where('event_type', 'view_account')
            ->groupBy('coc_account_id')
            ->orderByDesc('views')
            ->take(5)
            ->with('account:id,name,account_code,price,town_hall')
            ->get();

        return view('super-admin.dashboard', compact(
            'totalVisitors', 
            'todayVisitors', 
            'totalAccounts', 
            'availableAccounts', 
            'soldAccounts', 
            'visitorStats', 
            'topAccounts'
        ));
    }

    /**
     * Halaman analitik detail (bisa dikembangkan nanti).
     */
    public function analytics()
    {
        return view('super-admin.analytics');
    }

    /**
     * Halaman daftar transaksi penjualan.
     */
    public function sales()
    {
        $sales = Sale::with(['account', 'processor'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('super-admin.sales', compact('sales'));
    }
    
    /**
     * Halaman manajemen admin.
     */
    public function manageAdmins()
    {
        $admins = User::whereIn('role', ['admin', 'super_admin'])->get();
        return view('super-admin.admins', compact('admins'));
    }

    /**
     * Konfirmasi penjualan manual oleh Super Admin.
     */
    public function confirmSale(Request $request) 
    {
        $request->validate([
            'account_id' => 'required|exists:coc_accounts,id',
            'price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000'
        ]);

        DB::transaction(function () use ($request) {
            $account = CocAccount::findOrFail($request->account_id);
            
            // Buat record penjualan
            Sale::create([
                'transaction_code' => 'TRX-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'coc_account_id' => $account->id,
                'account_code_snapshot' => $account->account_code,
                'sale_price' => $request->price,
                'transaction_status' => 'completed',
                'payment_status' => 'paid',
                'processed_by' => auth()->id(),
                'sold_at' => now(),
                'notes' => $request->notes,
            ]);

            // Update status akun menjadi terjual dan sembunyikan dari katalog
            $account->update([
                'sale_status' => 'sold',
                'publication_status' => 'draft'
            ]);
        });

        return back()->with('success', 'Penjualan berhasil dikonfirmasi dan stok telah diperbarui.');
    }
}