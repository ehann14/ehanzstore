<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\CocAccount;
use App\Models\Review;
use App\Models\VisitorEvent;
use App\Support\Uploads;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Halaman utama: banner slider di atas, daftar akun dijual di bawahnya.
     */
    public function home()
    {
        $banners = Banner::active()->get();

        // Akun terjual (masih bergaransi) tetap tampil, tapi di urutan setelah yang tersedia
        $featuredAccounts = CocAccount::listed()
            ->with(['primaryImage', 'latestCompletedSale.approvedReview'])
            ->orderByRaw("sale_status = 'available' desc")
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get();

        // Chip filter Town Hall yang stoknya memang ada
        $townHalls = CocAccount::available()
            ->where('listing_type', 'account')
            ->select('town_hall')
            ->distinct()
            ->orderByDesc('town_hall')
            ->pluck('town_hall');

        $totalAvailable = CocAccount::available()->count();

        // Testimoni permanen: diambil langsung dari tabel reviews (status disetujui),
        // tidak bergantung pada akun yang sudah hilang dari katalog setelah garansi berakhir.
        $testimonials = Review::approved()
            ->with('account')
            ->latest('id')
            ->take(12)
            ->get();

        // Rating rata-rata + jumlah akun terjual (dari tabel reviews dan sales)
        $stats = Review::publicStats();

        return view('public.home', compact(
            'banners',
            'featuredAccounts',
            'townHalls',
            'totalAvailable',
            'testimonials',
            'stats'
        ));
    }

    /**
     * Katalog akun dengan pencarian, filter, dan pengurutan.
     */
    public function catalog(Request $request)
    {
        // Akun tersedia + akun terjual yang garansinya masih aktif (tersedia diurutkan lebih dulu)
        $query = CocAccount::listed()
            ->with(['primaryImage', 'latestCompletedSale.approvedReview'])
            ->orderByRaw("sale_status = 'available' desc");

        // Pencarian berdasarkan nama atau kode akun
        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('account_code', 'like', '%' . $search . '%');
            });
        }

        // Jenis: akun pemain / clan (kosong = semua)
        $type = in_array($request->input('type'), ['account', 'clan'], true) ? $request->input('type') : null;
        if ($type) {
            $query->where('listing_type', $type);
        }

        // Filter Town Hall (tidak berlaku untuk clan)
        if ($request->filled('th') && $type !== 'clan') {
            $query->where('town_hall', (int) $request->input('th'));
        }

        // Filter lanjutan: harga, level hero minimum, email, proteksi
        $this->applyAdvancedFilters($query, $request);

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

        return view('public.catalog', compact('accounts', 'type'));
    }

    /** Hero yang bisa difilter => kolom di database. */
    public const HERO_COLUMNS = [
        'bk' => 'barbarian_king_level',
        'aq' => 'archer_queen_level',
        'mp' => 'minion_prince_level',
        'gw' => 'grand_warden_level',
        'rc' => 'royal_champion_level',
        'dd' => 'dragon_duke_level',
    ];

    /**
     * Filter lanjutan katalog. Semua input dibersihkan dulu (angka saja),
     * jadi nilai aneh dari URL tidak akan membuat error.
     */
    private function applyAdvancedFilters($query, Request $request): void
    {
        $min = $this->digits($request->input('price_min'));
        $max = $this->digits($request->input('price_max'));

        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }

        // Filter khusus clan: level, capital hall, member, liga minimum
        if ($request->input('type') === 'clan') {
            $lv = $this->digits($request->input('clan_level_min'));
            $ch = $this->digits($request->input('capital_min'));
            $mb = $this->digits($request->input('members_min'));

            if ($lv) {
                $query->where('clan_level', '>=', $lv);
            }
            if ($ch) {
                $query->where('capital_hall_level', '>=', $ch);
            }
            if ($mb) {
                $query->where('clan_members', '>=', $mb);
            }

            $league = $request->input('league_min');
            $idx    = is_string($league) ? array_search($league, CocAccount::CLAN_LEAGUES, true) : false;
            if ($idx !== false && $idx > 0) {
                $query->whereIn('clan_league', array_slice(CocAccount::CLAN_LEAGUES, $idx));
            }

            return; // filter hero / email / proteksi tidak relevan untuk clan
        }

        // Level hero minimum, contoh: Archer Queen minimal 60
        $heroKey = $request->input('hero');
        $heroMin = $this->digits($request->input('hero_min'));

        if ($heroMin !== null && $heroMin > 0) {
            if (isset(self::HERO_COLUMNS[$heroKey])) {
                $query->where(self::HERO_COLUMNS[$heroKey], '>=', $heroMin);
            }
        }

        if ($request->boolean('email')) {
            $query->where('email_change_status', 'Changeable');
        }

        if ($request->boolean('protected')) {
            $query->where('protection_status', 'Protected');
        }
    }

    /** Ambil angka bulat dari input (mis. "1.500.000" atau "Rp 1500000"); kosong -> null. */
    private function digits($value): ?int
    {
        if ($value === null || is_array($value)) {
            return null;
        }

        $clean = preg_replace('/\D+/', '', (string) $value);

        return $clean === '' ? null : (int) min((int) $clean, 999999999);
    }

    /**
     * Detail akun berdasarkan kode akun.
     */
    public function detail($account_code)
    {
        $account = CocAccount::listed()
            ->with(['primaryImage', 'latestCompletedSale.approvedReview'])
            ->where('account_code', $account_code)
            ->firstOrFail();

        // Catat kunjungan ke detail akun
        $this->trackEvent(
            'view_account',
            request()->path(),
            $account->id
        );

        return view('public.detail', compact('account'));
    }

    /**
     * Mencatat aktivitas pengunjung.
     */
    public function trackEvent(
        string $type,
        ?string $path = null,
        ?int $accountId = null
    ) {
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
    
    public function warranty()
    {
        return view('public.warranty');
    }

    /**
     * Menampilkan file upload jika file fisiknya tidak ditemukan oleh Apache
     * (mis. foto lama yang masih ada di storage/app/public).
     * Hanya mengizinkan gambar dari folder accounts dan banners.
     */
    public function upload(string $path)
    {
        abort_unless(
            preg_match('#^(accounts|banners)/[A-Za-z0-9._-]+\.(jpe?g|png|webp|gif)$#i', $path),
            404
        );

        $file = Uploads::locate($path);

        abort_unless($file, 404);

        return response()->file($file, [
            'Cache-Control'          => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}