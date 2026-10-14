<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CocAccount;
use App\Models\Sale;
use App\Models\User;
use App\Models\VisitorEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    /**
     * Dashboard utama Super Admin.
     */
    public function index()
    {
        // Pengunjung
        $totalVisitors     = VisitorEvent::distinct()->count('anonymous_session_id');
        $todayVisitors     = VisitorEvent::whereDate('created_at', today())->distinct()->count('anonymous_session_id');
        $yesterdayVisitors = VisitorEvent::whereDate('created_at', today()->subDay())->distinct()->count('anonymous_session_id');

        // Katalog
        $totalAccounts     = CocAccount::count();
        $availableAccounts = CocAccount::where('sale_status', 'available')->count();
        $soldAccounts      = CocAccount::where('sale_status', 'sold')->count();

        // Pendapatan (hanya transaksi selesai)
        $totalRevenue = Sale::where('transaction_status', 'completed')->sum('sale_price');
        $monthRevenue = Sale::where('transaction_status', 'completed')
            ->whereBetween('sold_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('sale_price');

        // Grafik 7 hari (hari kosong tetap tampil dengan nilai 0)
        $visitorStats = $this->dailyStats(7);

        // Akun paling sering dilihat
        $topAccounts = VisitorEvent::select('coc_account_id', DB::raw('count(*) as views'))
            ->whereNotNull('coc_account_id')
            ->where('event_type', 'view_account')
            ->groupBy('coc_account_id')
            ->orderByDesc('views')
            ->take(5)
            ->with('account:id,name,account_code,price,town_hall')
            ->get();

        // Transaksi terbaru
        $recentSales = Sale::with('account')->latest()->take(5)->get();

        return view('super-admin.dashboard', compact(
            'totalVisitors',
            'todayVisitors',
            'yesterdayVisitors',
            'totalAccounts',
            'availableAccounts',
            'soldAccounts',
            'totalRevenue',
            'monthRevenue',
            'visitorStats',
            'topAccounts',
            'recentSales'
        ));
    }

    /**
     * Analitik detail dengan filter rentang waktu.
     */
    public function analytics(Request $request)
    {
        $range = (int) $request->query('range', 14);
        if (!in_array($range, [7, 14, 30], true)) {
            $range = 14;
        }

        $since = today()->subDays($range - 1);

        $base = fn () => VisitorEvent::where('created_at', '>=', $since);

        $summary = [
            'visitors'      => $base()->distinct()->count('anonymous_session_id'),
            'page_views'    => $base()->where('event_type', 'page_view')->count(),
            'account_views' => $base()->where('event_type', 'view_account')->count(),
            'wa_clicks'     => $base()->where('event_type', 'click_whatsapp')->count(),
        ];

        $dailyStats = $this->dailyStats($range);

        $eventBreakdown = $base()
            ->select('event_type', DB::raw('COUNT(*) as total'))
            ->groupBy('event_type')
            ->orderByDesc('total')
            ->get();

        $topPages = $base()
            ->select('page_path', DB::raw('COUNT(*) as total'))
            ->where('event_type', 'page_view')
            ->whereNotNull('page_path')
            ->groupBy('page_path')
            ->orderByDesc('total')
            ->take(8)
            ->get();

        $topAccounts = $base()
            ->select('coc_account_id', DB::raw('COUNT(*) as views'))
            ->where('event_type', 'view_account')
            ->whereNotNull('coc_account_id')
            ->groupBy('coc_account_id')
            ->orderByDesc('views')
            ->take(10)
            ->with('account:id,name,account_code,price,town_hall,sale_status')
            ->get();

        return view('super-admin.analytics', compact(
            'range',
            'summary',
            'dailyStats',
            'eventBreakdown',
            'topPages',
            'topAccounts'
        ));
    }

    /**
     * Daftar transaksi + form konfirmasi penjualan.
     */
    public function sales()
    {
        $sales = Sale::with(['account', 'processor'])
            ->latest()
            ->paginate(15);

        $stats = [
            'completed' => Sale::where('transaction_status', 'completed')->count(),
            'pending'   => Sale::where('transaction_status', 'pending')->count(),
            'revenue'   => Sale::where('transaction_status', 'completed')->sum('sale_price'),
        ];

        $availableAccounts = CocAccount::where('sale_status', 'available')
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'name', 'town_hall', 'price']);

        return view('super-admin.sales', compact('sales', 'stats', 'availableAccounts'));
    }

    /**
     * Daftar admin.
     */
    public function manageAdmins()
    {
        $admins = User::whereIn('role', ['admin', 'super_admin'])
            ->orderByRaw("CASE WHEN role = 'super_admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get();

        return view('super-admin.admins', compact('admins'));
    }

    /**
     * Konfirmasi penjualan manual oleh Super Admin.
     */
    public function confirmSale(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|exists:coc_accounts,id',
            'price'      => 'required|numeric|min:0',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $result = DB::transaction(function () use ($data) {
            $account = CocAccount::lockForUpdate()->findOrFail($data['account_id']);

            // Cegah akun yang sama terjual dua kali
            if ($account->sale_status === 'sold') {
                return false;
            }

            Sale::create([
                'transaction_code'      => 'TRX-' . strtoupper(Str::random(8)),
                'coc_account_id'        => $account->id,
                'account_code_snapshot' => $account->account_code,
                'sale_price'            => $data['price'],
                'transaction_status'    => 'completed',
                'payment_status'        => 'paid',
                'processed_by'          => auth()->id(),
                'sold_at'               => now(),
                'notes'                 => $data['notes'] ?? null,
            ]);

            $account->update([
                'sale_status'        => 'sold',
                'publication_status' => 'draft',
            ]);

            return true;
        });

        if (!$result) {
            return back()
                ->withInput()
                ->withErrors(['account_id' => 'Akun tersebut sudah berstatus terjual.']);
        }

        return back()->with('success', 'Penjualan berhasil dikonfirmasi dan stok telah diperbarui.');
    }

    /**
     * Statistik harian, hari tanpa data diisi 0 supaya grafik tidak bolong.
     */
    private function dailyStats(int $days): array
    {
        $start = today()->subDays($days - 1);

        $rows = VisitorEvent::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(DISTINCT anonymous_session_id) as visitors'),
                DB::raw('COUNT(*) as views')
            )
            ->where('created_at', '>=', $start)
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $d   = $start->copy()->addDays($i);
            $row = $rows->get($d->toDateString());

            $out[] = [
                'date'     => $d->toDateString(),
                'label'    => $d->format('d M'),
                'visitors' => (int) ($row->visitors ?? 0),
                'views'    => (int) ($row->views ?? 0),
            ];
        }

        return $out;
    }
}