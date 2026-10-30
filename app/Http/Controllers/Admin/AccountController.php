<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CocAccount;
use App\Models\CocAccountImage;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\Uploads;
use App\Support\Watermark;

class AccountController extends Controller
{
    private const MAX_IMAGES = 10;

    public function index(Request $request)
    {
        // Admin biasa hanya melihat akun miliknya; super admin melihat semua.
        $base = fn () => CocAccount::query()
            ->when(!Auth::user()->isSuperAdmin(), fn ($q) => $q->where('created_by', Auth::id()));

        // Dua bagian: "ready" (belum terjual: tersedia + arsip) dan "sold" (sudah terjual)
        $tab = $request->query('tab') === 'sold' ? 'sold' : 'ready';

        $search = trim((string) $request->query('q', ''));

        $query = $base()->with(['primaryImage', 'latestCompletedSale.review']);

        if ($tab === 'sold') {
            $query->where('sale_status', 'sold')
                ->orderByDesc(
                    Sale::select('id')
                        ->whereColumn('sales.coc_account_id', 'coc_accounts.id')
                        ->where('transaction_status', 'completed')
                        ->orderByDesc('id')
                        ->limit(1)
                )
                ->orderByDesc('coc_accounts.updated_at');
        } else {
            $query->where('sale_status', '!=', 'sold')
                ->orderByDesc('created_at');
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->paginate(10)->withQueryString();

        // Ringkasan untuk kedua tab (tidak terpengaruh pencarian)
        $readyCount = $base()->where('sale_status', '!=', 'sold')->count();
        $soldCount  = $base()->where('sale_status', 'sold')->count();
        $readyValue = (float) $base()->where('sale_status', 'available')->sum('price');

        return view('admin.accounts.index', compact(
            'accounts', 'tab', 'search', 'readyCount', 'soldCount', 'readyValue'
        ));
    }

    public function create()
    {
        return view('admin.accounts.create');
    }

    public function store(Request $request)
    {
        $this->normalizeCode($request);

        $type = $request->input('listing_type') === 'clan' ? 'clan' : 'account';

        $rules = $this->rules(null, $type);
        $rules['listing_type'] = 'required|in:account,clan';

        $validated = $request->validate($rules, $this->messages());
        unset($validated['images']);

        if ($errors = $this->consignmentEmailErrors($request, $type)) {
            return back()->withInput()->withErrors($errors);
        }

        $validated['listing_type']        = $type;
        $validated['cost_price']          = $validated['cost_price'] ?? 0;
        $this->applyConsignment($validated, $request);
        $this->applyDiscount($validated, $request);
        $this->applyPublishSchedule($validated, willBePublished: false);
        $this->applyAccountInfoDefaults($validated);
        $validated['protection_status']   ??= 'Unprotected';
        $validated['email_change_status'] ??= 'Unchangeable';
        $this->applyEmailDelay($validated);

        // Clan tidak punya Town Hall (kolom database wajib diisi, jadi 0)
        if ($type === 'clan') {
            $validated['town_hall'] = 0;
        }

        $account = CocAccount::create([
            'created_by'         => Auth::id(),
            'sale_status'        => 'available',
            'publication_status' => 'draft', // draft dulu agar admin bisa cek
            ...$validated,
        ]);

        $failed = $this->storeImages($request, $account);

        ActivityLog::record('create_account', $account, 'Akun ' . $account->account_code);

        return $this->withUploadWarning(
            redirect()->route('admin.accounts.index')->with('success', ($type === 'clan' ? 'Clan' : 'Akun') . ' berhasil ditambahkan (status: draft).'),
            $failed
        );
    }

    /** Admin tidak punya halaman detail terpisah: buka detail = langsung ke halaman edit. */
    public function show(CocAccount $account)
    {
        $this->authorizeAccount($account);

        return redirect()->route('admin.accounts.edit', $account);
    }

    public function edit(CocAccount $account)
    {
        $this->authorizeAccount($account);
        $account->load('images');

        return view('admin.accounts.edit', compact('account'));
    }

    public function update(Request $request, CocAccount $account)
    {
        $this->authorizeAccount($account);

        $this->normalizeCode($request);

        // Jenis (akun/clan) tidak bisa diubah setelah dibuat
        $rules = $this->rules($account, $account->listing_type ?? 'account');
        $rules['publication_status'] = 'required|in:draft,published';
        $rules['sale_status']        = 'required|in:available,sold,archived';

        $validated = $request->validate($rules, $this->messages());
        unset($validated['images']);

        if ($errors = $this->consignmentEmailErrors($request, $account->listing_type ?? 'account', $account)) {
            return back()->withInput()->withErrors($errors);
        }

        // Batas total foto (yang sudah ada + yang baru)
        $newCount = count($request->file('images', []));
        if ($account->images()->count() + $newCount > self::MAX_IMAGES) {
            return back()->withInput()->withErrors([
                'images' => 'Total foto maksimal ' . self::MAX_IMAGES . '. Hapus foto lama dulu.',
            ]);
        }

        // Status "terjual" tidak langsung disimpan: admin harus mengisi data pembeli dulu
        // (untuk garansi 7/14/21 hari). Perubahan data lainnya tetap disimpan sekarang.
        $goingToSold = ($validated['sale_status'] ?? null) === 'sold' && $account->sale_status !== 'sold';
        if ($goingToSold) {
            unset($validated['sale_status']);
        }

        $validated['cost_price'] = $validated['cost_price'] ?? 0;
        $this->applyConsignment($validated, $request, $account);
        $this->applyDiscount($validated, $request);
        $this->applyPublishSchedule($validated, willBePublished: $validated['publication_status'] === 'published');
        $this->applyAccountInfoDefaults($validated);

        $this->applyEmailDelay($validated, $account);

        $account->update($validated);

        // Akun yang sudah terjual: modal pada transaksinya ikut diperbarui. Daftar akun, laporan
        // dan laba membaca modal dari transaksi (snapshot), bukan dari akun, jadi tanpa ini
        // modal yang baru diisi di form akun terlihat "tidak tersimpan".
        // Disinkronkan juga bila modal akun > 0 tapi transaksinya masih 0 (data lama yang belum sinkron).
        // Akun titipan dilewati: bagian pemilik di transaksinya dihitung dari harga jual akhir, bukan dari form akun.
        if ($account->sale_status === 'sold' && ! $account->isConsignment()) {
            $sale = $account->sales()
                ->where('transaction_status', 'completed')
                ->latest('id')
                ->first();

            if ($sale) {
                $accountCost = (float) $account->cost_price;
                $saleCost    = (float) $sale->cost_price;

                if ($account->wasChanged('cost_price') || ($accountCost > 0 && $saleCost !== $accountCost)) {
                    $sale->update(['cost_price' => $accountCost]);
                }
            }
        }

        ActivityLog::record('update_account', $account, 'Akun ' . $account->account_code, [
            'changed' => array_values(array_diff(array_keys($account->getChanges()), ['updated_at'])),
        ]);

        $failed = $this->storeImages($request, $account);

        if ($goingToSold) {
            return $this->withUploadWarning(
                redirect()
                    ->route('admin.accounts.sell', $account)
                    ->with('success', 'Perubahan data tersimpan. Lengkapi data pembeli untuk menandai akun terjual.'),
                $failed
            );
        }

        return $this->withUploadWarning(
            redirect()->route('admin.accounts.index')->with('success', 'Data akun diperbarui.'),
            $failed
        );
    }

    /** Ubah status terbit / penjualan dari daftar akun. */
    public function updateStatus(Request $request, CocAccount $account)
    {
        $this->authorizeAccount($account);

        $data = $request->validate([
            'publication_status' => 'nullable|in:draft,published',
            'sale_status'        => 'nullable|in:available,sold,archived',
        ]);

        // Menandai terjual wajib lewat form data pembeli (garansi 7/14/21 hari).
        if (($data['sale_status'] ?? null) === 'sold' && $account->sale_status !== 'sold') {
            return redirect()
                ->route('admin.accounts.sell', $account)
                ->with('success', 'Isi data pembeli untuk menandai akun terjual.');
        }

        $changes = array_filter($data);

        // Terbit / sembunyikan manual berarti jadwal tayang tidak diperlukan lagi
        if (isset($changes['publication_status'])) {
            $changes['publish_at'] = null;
        }

        $account->update($changes);

        ActivityLog::record('update_status', $account, 'Akun ' . $account->account_code, [
            'changes' => array_filter($data),
        ]);

        return back()->with('success', 'Status akun diperbarui.');
    }

    /** Form data pembeli: dibuka saat akun akan ditandai terjual. */
    public function sell(CocAccount $account)
    {
        $this->authorizeAccount($account);

        // Sudah terjual: arahkan ke form data pembeli milik transaksinya.
        if ($account->sale_status === 'sold') {
            $sale = $account->sales()->where('transaction_status', 'completed')->latest('id')->first();

            return $sale
                ? redirect()->route('admin.sales.buyer', $sale)
                : redirect()->route('admin.accounts.index')->with('error', 'Transaksi akun ini tidak ditemukan.');
        }

        $account->load('primaryImage');

        return view('admin.accounts.sell', compact('account'));
    }

    /** Simpan data pembeli, catat transaksi, dan tandai akun terjual (garansi mulai dihitung). */
    public function storeSale(Request $request, CocAccount $account)
    {
        $this->authorizeAccount($account);

        $validated = $request->validate(Sale::buyerRules(), Sale::buyerMessages());

        $sale = Sale::completeSale($account->id, $validated);

        if (! $sale) {
            return redirect()->route('admin.accounts.index')->with('error', 'Akun ini sudah berstatus terjual.');
        }

        ActivityLog::record('sell_account', $account, 'Akun ' . $account->account_code, [
            'transaction' => $sale->transaction_code,
            'buyer'       => $sale->buyer_name,
            'price'       => $sale->sale_price,
            'cost'        => $sale->cost_price,
            'profit'      => $sale->profit(),
            ...($sale->is_consignment ? [
                'consignor'  => $sale->consignor_name,
                'commission' => $sale->commission_amount,
                'payout'     => $sale->consignor_payout,
            ] : []),
        ]);

        $consignNote = $sale->is_consignment
            ? ' Titip jual: setor ke pemilik (' . $sale->consignor_name . ') Rp ' . number_format((float) $sale->consignor_payout, 0, ',', '.') . '.'
            : '';

        return redirect()->route('admin.accounts.index')->with(
            'success',
            "Akun {$account->account_code} terjual ke {$sale->buyer_name}. " . ($sale->is_consignment ? 'Komisi' : 'Laba') . ': Rp ' . number_format($sale->profit(), 0, ',', '.') . ". Garansi aktif sampai {$sale->warrantyUntilLabel()}." . $consignNote
        );
    }

    /** Form untuk melengkapi / mengubah data pembeli pada transaksi yang sudah ada. */
    public function buyer(Sale $sale)
    {
        $this->authorizeSale($sale);

        $account = $sale->account;
        $account->load('primaryImage');

        return view('admin.accounts.sell', compact('account', 'sale'));
    }

    public function updateBuyer(Request $request, Sale $sale)
    {
        $this->authorizeSale($sale);

        $validated = $request->validate(Sale::buyerRules(), Sale::buyerMessages());

        // Titip jual yang sudah dilunasi ke pemilik: harga jual dikunci supaya angka setoran tidak berubah diam-diam.
        if ($sale->is_consignment
            && $sale->consignorIsPaid()
            && (float) $validated['price'] !== (float) $sale->sale_price) {
            return back()->withInput()->withErrors([
                'price' => 'Setoran ke pemilik sudah ditandai lunas, jadi harga jual tidak bisa diubah. Batalkan tanda lunas di menu Titip Jual dulu.',
            ]);
        }

        Sale::updateBuyer($sale, $validated);

        // Modal yang diisi di form pembeli juga disimpan ke akunnya, supaya form Edit Akun
        // dan daftar akun menampilkan angka yang sama (tidak saling menimpa dengan 0).
        // Akun titipan dilewati: modalnya adalah bagian pemilik yang dihitung otomatis.
        if (! $sale->is_consignment && isset($validated['cost_price']) && $validated['cost_price'] !== '') {
            $sale->account?->update(['cost_price' => $validated['cost_price']]);
        }

        ActivityLog::record('update_buyer', $sale->account, 'Akun ' . $sale->account_code_snapshot, [
            'transaction' => $sale->transaction_code,
            'buyer'       => $sale->buyer_name,
        ]);

        return redirect()->route('admin.accounts.index', ['tab' => 'sold'])->with(
            'success',
            "Data pembeli diperbarui. Garansi aktif sampai {$sale->warrantyUntilLabel()}."
        );
    }

    public function destroyImage(CocAccount $account, CocAccountImage $image)
    {
        $this->authorizeAccount($account);
        abort_if((int) $image->coc_account_id !== (int) $account->id, 404);

        $wasPrimary = $image->is_primary;
        Uploads::delete($image->image_path);
        $image->delete();

        // Jika foto utama dihapus, jadikan foto pertama yang tersisa sebagai utama
        if ($wasPrimary) {
            $account->images()->first()?->update(['is_primary' => true]);
        }

        ActivityLog::record('delete_image', $account, 'Akun ' . $account->account_code);

        return back()->with('success', 'Foto dihapus.');
    }

    /** Hapus beberapa foto sekaligus (pilih semua / pilih sebagian). */
    public function destroyImages(Request $request, CocAccount $account)
    {
        $this->authorizeAccount($account);

        $ids = array_values(array_filter((array) $request->input('image_ids', []), 'is_numeric'));

        if (empty($ids)) {
            return back()->withErrors(['images' => 'Pilih minimal satu foto untuk dihapus.']);
        }

        // Hanya foto milik akun ini yang boleh dihapus
        $images = $account->images()->whereIn('id', $ids)->get();

        foreach ($images as $image) {
            Uploads::delete($image->image_path);
            $image->delete();
        }

        // Jika foto utama ikut terhapus, jadikan foto pertama yang tersisa sebagai utama
        if (! $account->images()->where('is_primary', true)->exists()) {
            $account->images()->orderBy('sort_order')->orderBy('id')->first()?->update(['is_primary' => true]);
        }

        ActivityLog::record('delete_image', $account, 'Akun ' . $account->account_code . ' (' . $images->count() . ' foto)');

        return back()->with('success', $images->count() . ' foto dihapus.');
    }

    public function destroy(CocAccount $account)
    {
        $this->authorizeAccount($account);

        foreach ($account->images as $image) {
            Uploads::delete($image->image_path);
        }

        ActivityLog::record('delete_account', $account, 'Akun ' . $account->account_code);

        $account->delete();

        return redirect()->route('admin.accounts.index')->with('success', 'Akun dihapus.');
    }

    // ------------------------------------------------------------------

    /** Hanya transaksi selesai yang punya data pembeli/garansi; admin hanya boleh untuk akun miliknya. */
    private function authorizeSale(Sale $sale): void
    {
        abort_if($sale->transaction_status !== 'completed', 404);

        $user = Auth::user();

        if (! $user->isSuperAdmin()
            && $sale->account?->created_by !== $user->id
            && $sale->processed_by !== $user->id) {
            abort(403);
        }
    }

    private function authorizeAccount(CocAccount $account): void
    {
        if ($account->created_by !== Auth::id() && !Auth::user()->isSuperAdmin()) {
            abort(403);
        }
    }

    /** Kode dibuat manual oleh admin: rapikan spasi & jadikan huruf besar. */
    private function normalizeCode(Request $request): void
    {
        $request->merge([
            'account_code' => strtoupper(trim((string) $request->input('account_code'))),
            'player_tag'   => CocAccount::normalizeTag($request->input('player_tag')),
            'clan_tag'     => CocAccount::normalizeTag($request->input('clan_tag')),
        ]);
    }

    private function rules(?CocAccount $account = null, string $type = 'account'): array
    {
        $common = [
            // unique dicek ke semua baris (termasuk akun yang sudah dihapus/soft delete)
            // karena kolom di database memang UNIQUE.
            'account_code'         => [
                'required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coc_accounts', 'account_code')->ignore($account?->id),
            ],
            'name'                 => 'required|string|max:255',
            // kolom DB decimal(12,2) -> jaga di bawah batas supaya tidak error 1264
            'price'                => 'required|numeric|min:0|max:999999999',
            // harga coret (harga normal sebelum diskon): opsional, harus lebih besar dari harga jual
            'original_price'       => 'nullable|numeric|min:0|max:999999999|gt:price',
            'is_hot_deal'          => 'nullable|boolean',
            // masa berlaku promo (diisi dalam WIB). Kosong = tanpa batas waktu.
            'hot_deal_ends_at'     => [
                'nullable', 'date',
                function (string $attribute, mixed $value, \Closure $fail) {
                    try {
                        $end = Carbon::parse($value, Sale::DISPLAY_TZ);
                    } catch (\Throwable) {
                        $fail('Format tanggal berakhir promo tidak valid.');

                        return;
                    }

                    if ($end->lte(now())) {
                        $fail('Tanggal berakhir promo harus di masa depan.');
                    }
                },
            ],
            // harga modal: hanya terlihat admin, tidak pernah tampil di halaman publik
            'cost_price'           => 'nullable|numeric|min:0|max:999999999',
            // titip jual (konsinyasi): pemilik akun + komisi toko. Hanya admin yang melihat, tidak pernah tampil di publik.
            'is_consignment'       => 'nullable|boolean',
            'consignor_name'       => [Rule::requiredIf(fn () => request()->boolean('is_consignment')), 'nullable', 'string', 'min:2', 'max:100'],
            'consignor_phone'      => [
                'nullable', 'string', 'max:25',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! request()->boolean('is_consignment') || blank($value)) {
                        return;
                    }

                    $phone = Sale::normalizePhone($value);

                    if (! $phone || ! preg_match('/^628[0-9]{8,12}$/', $phone)) {
                        $fail('Nomor WhatsApp pemilik tidak valid. Contoh: 0812-3456-7890.');
                    }
                },
            ],
            'commission_type'      => [Rule::requiredIf(fn () => request()->boolean('is_consignment')), 'nullable', Rule::in(array_keys(CocAccount::COMMISSION_TYPES))],
            'commission_value'     => [
                Rule::requiredIf(fn () => request()->boolean('is_consignment')),
                'nullable', 'numeric', 'min:0', 'max:999999999',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! request()->boolean('is_consignment') || $value === null || $value === '') {
                        return;
                    }

                    if (request()->input('commission_type') === 'fixed') {
                        if ((float) $value > (float) request()->input('price', 0)) {
                            $fail('Komisi nominal tidak boleh lebih besar dari harga jual.');
                        }
                    } elseif ((float) $value > CocAccount::COMMISSION_MAX_PERCENT) {
                        $fail('Komisi persen maksimal ' . CocAccount::COMMISSION_MAX_PERCENT . '%.');
                    }
                },
            ],
            // syarat wajib titip akun: konfirmasi email bisa diganti ke Gmail pembeli (dicek di consignmentEmailErrors)
            'consignor_email_ok'   => 'nullable|boolean',
            // jadwal tayang otomatis (WIB). Kosong = tidak dijadwalkan. Hanya dipakai bila status Draft.
            'publish_date'         => ['nullable', 'required_with:publish_hour', 'date_format:Y-m-d'],
            'publish_hour'         => ['nullable', 'required_with:publish_date', 'integer', 'between:0,23'],
            'publish_minute'       => ['nullable', 'integer', 'between:0,59'],
            'description'          => 'nullable|string|max:5000',
            'images'               => 'nullable|array|max:' . self::MAX_IMAGES,
            // 5 MB per foto; foto dari browser sudah dikompres otomatis (~0.5 MB)
            'images.*'             => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ];

        if ($type === 'clan') {
            return $common + [
                // tag clan: huruf & angka yang dipakai Clash of Clans (setelah #)
                'clan_tag'           => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Z]{3,19}$/'],
                'clan_level'         => 'required|integer|min:1|max:100',
                'capital_hall_level' => 'required|integer|min:0|max:15',
                'clan_members'       => 'required|integer|min:0|max:' . CocAccount::CLAN_MAX_MEMBERS,
                'clan_league'        => ['required', Rule::in(CocAccount::CLAN_LEAGUES)],
            ];
        }

        return $common + [
            // tag akun: huruf & angka yang dipakai Clash of Clans (setelah #)
            'player_tag'           => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Z]{3,19}$/'],
            'town_hall'            => 'required|integer|min:1|max:18',
            'account_level'        => 'nullable|integer|min:0|max:500',
            'builder_count'        => 'nullable|integer|min:0|max:6',
            'gems'                 => 'nullable|integer|min:0|max:99999999',
            'cn_information'       => 'nullable|string|max:255',
            'barbarian_king_level' => 'nullable|integer|min:0|max:100',
            'archer_queen_level'   => 'nullable|integer|min:0|max:100',
            'minion_prince_level'  => 'nullable|integer|min:0|max:100',
            'grand_warden_level'   => 'nullable|integer|min:0|max:100',
            'royal_champion_level' => 'nullable|integer|min:0|max:100',
            'dragon_duke_level'    => 'nullable|integer|min:0|max:100',
            'protection_status'    => 'nullable|in:Protected,Unprotected',
            'email_change_status'  => 'nullable|in:Changeable,Unchangeable',
            'email_change_days'    => 'nullable|integer|min:0|max:365',
        ];
    }

    /**
     * Kolom angka informasi akun tidak boleh NULL di database. Jika input dikosongkan
     * (Laravel mengubah string kosong jadi null), pakai nilai bawaan kolomnya.
     * Hanya menyentuh kolom yang memang dikirim form (akun pemain), clan tidak terpengaruh.
     */
    private function applyAccountInfoDefaults(array &$validated): void
    {
        foreach (['account_level' => 0, 'builder_count' => 5, 'gems' => 0] as $col => $default) {
            if (array_key_exists($col, $validated) && $validated[$col] === null) {
                $validated[$col] = $default;
            }
        }
    }

    /**
     * Ubah input "email bisa diganti dalam N hari" menjadi tanggal email_change_available_at.
     * - Bila hari diisi > 0, status otomatis menjadi Bisa Ganti. Bila status Tidak Bisa Ganti
     *   dan hari kosong/0, tanggal dikosongkan.
     * - Kosong / 0 = email sudah bisa diganti sekarang.
     * - Saat edit, jika angka hari sama dengan sisa hari saat ini, tanggal lama dipertahankan
     *   (supaya menyimpan form tanpa mengubah angka tidak mengulang hitungan dari awal).
     */
    private function applyEmailDelay(array &$validated, ?CocAccount $account = null): void
    {
        $days = $validated['email_change_days'] ?? null;
        unset($validated['email_change_days']);

        // Clan tidak punya status email, jadi tidak disentuh
        if (! array_key_exists('email_change_status', $validated)) {
            return;
        }

        // Admin mengisi jumlah hari (> 0) berarti maksudnya "Bisa Ganti dalam N hari".
        // Tanpa ini, angka diam-diam dibuang bila radio masih "Tidak Bisa Ganti"
        // dan data terlihat "tidak tersimpan" (tidak ada pesan error apa pun).
        if ((int) $days > 0) {
            $validated['email_change_status'] = 'Changeable';
        }

        if ($validated['email_change_status'] !== 'Changeable' || ! $days) {
            $validated['email_change_available_at'] = null;

            return;
        }

        if ($account && $account->emailChangeDaysLeft() === (int) $days) {
            return; // tidak berubah
        }

        $validated['email_change_available_at'] = now()->addDays((int) $days);
    }

    /**
     * Rapikan input harga coret & Hot Deal.
     * - Harga coret kosong / tidak lebih besar dari harga jual -> disimpan NULL (tidak ada diskon).
     * - Hot Deal berasal dari checkbox: tidak dicentang = false.
     * - Tanggal berakhir dibaca sebagai WIB lalu disimpan sebagai UTC. Hanya disimpan bila
     *   memang ada promo (ada harga coret atau Hot Deal dicentang); selain itu dikosongkan.
     */
    private function applyDiscount(array &$validated, Request $request): void
    {
        $orig = $validated['original_price'] ?? null;

        $validated['original_price'] = ($orig !== null && $orig !== '' && (float) $orig > (float) ($validated['price'] ?? 0))
            ? $orig
            : null;

        $validated['is_hot_deal'] = $request->boolean('is_hot_deal');

        $end     = $validated['hot_deal_ends_at'] ?? null;
        $hasDeal = $validated['original_price'] !== null || $validated['is_hot_deal'];

        $validated['hot_deal_ends_at'] = ($hasDeal && filled($end))
            ? Carbon::parse($end, Sale::DISPLAY_TZ)->setTimezone(config('app.timezone'))
            : null;
    }

    /**
     * Ubah input tanggal + jam + menit (WIB) menjadi kolom publish_at (disimpan dalam zona waktu aplikasi).
     * - Akun yang langsung terbit (atau tanggal dikosongkan) -> publish_at = NULL.
     * - Jadwal harus di masa depan, kalau tidak form dikembalikan dengan pesan error.
     * Kolom bantu publish_date/hour/minute dibuang supaya tidak ikut ke database.
     */
    private function applyPublishSchedule(array &$validated, bool $willBePublished): void
    {
        $date   = $validated['publish_date'] ?? null;
        $hour   = $validated['publish_hour'] ?? null;
        $minute = $validated['publish_minute'] ?? null;

        unset($validated['publish_date'], $validated['publish_hour'], $validated['publish_minute']);

        if ($willBePublished || blank($date)) {
            $validated['publish_at'] = null;

            return;
        }

        $at = Carbon::createFromFormat(
            'Y-m-d H:i',
            sprintf('%s %02d:%02d', $date, (int) $hour, (int) ($minute ?? 0)),
            Sale::DISPLAY_TZ
        );

        if ($at->lte(now())) {
            throw ValidationException::withMessages([
                'publish_date' => 'Jadwal tayang harus di masa depan (WIB).',
            ]);
        }

        $validated['publish_at'] = $at->setTimezone(config('app.timezone'));
    }

    private function messages(): array
    {
        return [
            'images.max'        => 'Maksimal ' . self::MAX_IMAGES . ' foto.',
            'images.*.image'    => 'File harus berupa gambar.',
            'images.*.mimes'    => 'Format foto harus JPG, PNG, atau WebP.',
            'images.*.max'      => 'Ukuran tiap foto maksimal 5 MB.',
            'images.*.uploaded' => 'Foto gagal diunggah: ukuran melebihi batas server (upload_max_filesize di php.ini).',
            'account_code.required' => 'Kode akun wajib diisi.',
            'account_code.regex'    => 'Kode akun hanya boleh huruf, angka, minus (-) dan underscore (_), tanpa spasi.',
            'account_code.unique'   => 'Kode akun sudah dipakai, gunakan kode lain.',
            'account_code.max'      => 'Kode akun maksimal 30 karakter.',
            'email_change_days.integer' => 'Jumlah hari harus berupa angka bulat.',
            'email_change_days.max'     => 'Jumlah hari maksimal 365.',
            'price.max'         => 'Harga terlalu besar (maksimal Rp 999.999.999).',
            'original_price.numeric' => 'Harga coret harus berupa angka.',
            'original_price.min'     => 'Harga coret tidak boleh negatif.',
            'original_price.max'     => 'Harga coret terlalu besar (maksimal Rp 999.999.999).',
            'original_price.gt'      => 'Harga coret harus lebih besar dari harga jual. Kosongkan jika tidak ada diskon.',
            'hot_deal_ends_at.date'  => 'Format tanggal berakhir promo tidak valid.',
            'publish_date.date_format'    => 'Tanggal jadwal tayang tidak valid.',
            'publish_date.required_with'  => 'Pilih tanggal jadwal tayang.',
            'publish_hour.required_with'  => 'Pilih jam jadwal tayang (00-23, WIB).',
            'publish_hour.integer'        => 'Jam jadwal tayang tidak valid.',
            'publish_hour.between'        => 'Jam jadwal tayang harus antara 00 dan 23.',
            'publish_minute.integer'      => 'Menit jadwal tayang tidak valid.',
            'publish_minute.between'      => 'Menit jadwal tayang harus antara 00 dan 59.',
            'player_tag.regex'  => 'Tag akun tidak valid. Gunakan huruf dan angka saja, contoh: #P2LQ8Y0R.',
            'clan_tag.regex'    => 'Tag clan tidak valid. Gunakan huruf dan angka saja, contoh: #2Y8LQ0R.',
            'builder_count.max' => 'Jumlah tukang maksimal 6.',
            'cost_price.numeric' => 'Harga modal harus berupa angka.',
            'cost_price.min'     => 'Harga modal tidak boleh negatif.',
            'cost_price.max'     => 'Harga modal terlalu besar (maksimal Rp 999.999.999).',
            'consignor_name.required'    => 'Nama pemilik akun wajib diisi untuk titip jual.',
            'consignor_name.min'         => 'Nama pemilik minimal 2 karakter.',
            'consignor_name.max'         => 'Nama pemilik maksimal 100 karakter.',
            'commission_type.required'   => 'Pilih jenis komisi (persen atau nominal).',
            'commission_type.in'         => 'Jenis komisi tidak dikenal.',
            'commission_value.required'  => 'Isi besar komisi toko.',
            'commission_value.numeric'   => 'Komisi harus berupa angka.',
            'commission_value.min'       => 'Komisi tidak boleh negatif.',
            'commission_value.max'       => 'Komisi terlalu besar.',
        ];
    }

    /**
     * Rapikan input titip jual (konsinyasi).
     * - Checkbox tidak dicentang: semua data pemilik & komisi dikosongkan.
     * - Akun yang sudah terjual TIDAK diubah lagi: angka resminya sudah tersimpan di transaksi
     *   (snapshot), jadi input titip jual dari form diabaikan.
     * Harga modal akun titipan dihitung otomatis oleh CocAccount (modal = harga - komisi).
     */
    private function applyConsignment(array &$validated, Request $request, ?CocAccount $account = null): void
    {
        $keys = ['is_consignment', 'consignor_name', 'consignor_phone', 'commission_type', 'commission_value', 'consignor_email_ok'];

        if ($account && $account->sale_status === 'sold') {
            foreach ($keys as $key) {
                unset($validated[$key]);
            }

            return;
        }

        if (! $request->boolean('is_consignment')) {
            $validated['is_consignment']   = false;
            $validated['consignor_name']   = null;
            $validated['consignor_phone']  = null;
            $validated['commission_type']  = 'percent';
            $validated['commission_value'] = 0;
            $validated['consignor_email_ok'] = false;

            return;
        }

        $validated['is_consignment']   = true;
        $validated['consignor_name']   = trim((string) ($validated['consignor_name'] ?? ''));
        $validated['consignor_phone']  = Sale::normalizePhone($validated['consignor_phone'] ?? null);
        $validated['commission_type']  = $validated['commission_type'] === 'fixed' ? 'fixed' : 'percent';
        $validated['commission_value'] = $validated['commission_value'] ?? 0;
        $validated['consignor_email_ok'] = $request->boolean('consignor_email_ok');
    }

    /**
     * Syarat WAJIB titip akun: email akun harus bisa diganti ke Gmail pembeli.
     * - Status email harus "Bisa Ganti" (atau hari tunggu diisi), bukan "Tidak Bisa Ganti".
     * - Hari tunggu tidak boleh melebihi batas di config('store.consign_max_email_wait_days') (bawaan 0 = harus sudah bisa diganti sekarang).
     * - Clan tidak punya email, akun yang sudah terjual tidak diperiksa lagi (sudah dikunci).
     *
     * @return array<string, string> pesan error (semua ditampilkan di kotak titip jual), kosong bila lolos
     */
    private function consignmentEmailErrors(Request $request, string $type, ?CocAccount $account = null): array
    {
        if ($type === 'clan' || ! $request->boolean('is_consignment')) {
            return [];
        }

        if ($account && $account->sale_status === 'sold') {
            return [];
        }

        if (! $request->boolean('consignor_email_ok')) {
            return ['consignor_email_ok' => 'Syarat wajib titip akun: centang konfirmasi bahwa email akun bisa diganti ke Gmail pembeli.'];
        }

        $days   = (int) $request->input('email_change_days', 0);
        $max    = max(0, (int) config('store.consign_max_email_wait_days', 0));
        $status = $request->input('email_change_status');

        if ($status !== 'Changeable' && $days <= 0) {
            return ['consignor_email_ok' => 'Akun titipan wajib berstatus email "Bisa Ganti" ke Gmail pembeli. Jika email tidak bisa diganti, akun tidak bisa dititip.'];
        }

        if ($days > $max) {
            return ['consignor_email_ok' => $max === 0
                ? 'Akun titipan harus sudah bisa ganti email sekarang (kosongkan atau isi 0 hari).'
                : "Akun titipan maksimal menunggu {$max} hari sampai email bisa diganti."];
        }

        return [];
    }

    /**
     * Simpan foto ke folder uploads dan catat path-nya di database.
     * Foto pertama otomatis menjadi foto utama bila akun belum punya.
     * Foto yang gagal ditulis ke disk TIDAK dicatat di database (supaya tidak
     * ada gambar rusak); jumlah yang gagal dikembalikan untuk ditampilkan ke admin.
     */
    private function storeImages(Request $request, CocAccount $account): int
    {
        if (!$request->hasFile('images')) {
            return 0;
        }

        $hasPrimary = $account->images()->where('is_primary', true)->exists();
        $order      = (int) $account->images()->max('sort_order');
        $failed     = 0;

        foreach ($request->file('images') as $file) {
            try {
                $path = Watermark::store($file, 'accounts');
            } catch (\Throwable $e) {
                report($e);
                $failed++;
                continue;
            }

            $order++;
            CocAccountImage::create([
                'coc_account_id' => $account->id,
                'image_path'     => $path,
                'is_primary'     => !$hasPrimary,
                'sort_order'     => $order,
            ]);
            $hasPrimary = true;
        }

        return $failed;
    }

    /** Tambahkan pesan error ke redirect bila ada foto yang gagal disimpan. */
    private function withUploadWarning($redirect, int $failed)
    {
        if ($failed < 1) {
            return $redirect;
        }

        return $redirect->with(
            'error',
            $failed . ' foto gagal disimpan di server. Periksa izin tulis folder uploads, lalu unggah ulang foto tersebut.'
        );
    }
}