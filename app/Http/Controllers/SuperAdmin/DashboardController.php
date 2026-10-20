<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CocAccount;
use App\Models\Sale;
use App\Models\User;
use App\Models\VisitorEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
     * Riwayat aktivitas admin, dengan filter pengguna, aksi, dan tanggal.
     */
    public function activityLogs(Request $request)
    {
        $filters = $request->validate([
            'user_id' => 'nullable|integer',
            'action'  => 'nullable|string|max:50',
            'from'    => 'nullable|date',
            'to'      => 'nullable|date',
        ]);

        $query = ActivityLog::with('user')->latest('id');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['action']) && array_key_exists($filters['action'], ActivityLog::ACTIONS)) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $logs = $query->paginate(25)->withQueryString();

        $stats = [
            'total'        => ActivityLog::count(),
            'today'        => ActivityLog::whereDate('created_at', today())->count(),
            'active_today' => ActivityLog::whereDate('created_at', today())->distinct()->count('user_id'),
        ];

        $users = User::whereIn('role', ['admin', 'super_admin'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('super-admin.activity-logs', compact('logs', 'stats', 'users'));
    }

    /**
     * Konfirmasi penjualan manual oleh Super Admin.
     */
    public function confirmSale(Request $request)
    {
        $data = $request->validate(
            array_merge([
                'account_id' => ['required', Rule::exists('coc_accounts', 'id')->whereNull('deleted_at')],
            ], Sale::buyerRules()),
            array_merge([
                'account_id.required' => 'Pilih akun yang terjual.',
                'account_id.exists'   => 'Akun tidak ditemukan.',
            ], Sale::buyerMessages())
        );

        // Transaksi + data pembeli + garansi 7 hari dibuat atomik; akun yang sama tidak bisa terjual dua kali.
        $sale = Sale::completeSale((int) $data['account_id'], $data);

        if (! $sale) {
            return back()
                ->withInput()
                ->withErrors(['account_id' => 'Akun tersebut sudah berstatus terjual.']);
        }

        ActivityLog::record('confirm_sale', $sale->account, 'Akun ' . $sale->account_code_snapshot, [
            'transaction' => $sale->transaction_code,
            'buyer'       => $sale->buyer_name,
            'price'       => $sale->sale_price,
        ]);

        return back()->with(
            'success',
            "Penjualan dikonfirmasi untuk {$sale->buyer_name}. Garansi aktif sampai {$sale->warrantyUntilLabel()}."
        );
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