<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CocAccount;
use App\Models\CocAccountImage;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    private const MAX_IMAGES = 5;

    public function index()
    {
        $query = CocAccount::with(['primaryImage', 'latestCompletedSale'])->orderByDesc('created_at');

        // Admin biasa hanya melihat akun miliknya; super admin melihat semua.
        if (!Auth::user()->isSuperAdmin()) {
            $query->where('created_by', Auth::id());
        }

        $accounts = $query->paginate(10);

        return view('admin.accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('admin.accounts.create');
    }

    public function store(Request $request)
    {
        $this->normalizeCode($request);

        $validated = $request->validate($this->rules(), $this->messages());
        unset($validated['images']);

        $validated['protection_status']   ??= 'Unprotected';
        $validated['email_change_status'] ??= 'Unchangeable';

        $account = CocAccount::create([
            'created_by'         => Auth::id(),
            'sale_status'        => 'available',
            'publication_status' => 'draft', // draft dulu agar admin bisa cek
            ...$validated,
        ]);

        $this->storeImages($request, $account);

        ActivityLog::record('create_account', $account, 'Akun ' . $account->account_code);

        return redirect()->route('admin.accounts.index')->with('success', 'Akun berhasil ditambahkan (status: draft).');
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

        $rules = $this->rules($account);
        $rules['publication_status'] = 'required|in:draft,published';
        $rules['sale_status']        = 'required|in:available,sold,archived';

        $validated = $request->validate($rules, $this->messages());
        unset($validated['images']);

        // Batas total foto (yang sudah ada + yang baru)
        $newCount = count($request->file('images', []));
        if ($account->images()->count() + $newCount > self::MAX_IMAGES) {
            return back()->withInput()->withErrors([
                'images' => 'Total foto maksimal ' . self::MAX_IMAGES . '. Hapus foto lama dulu.',
            ]);
        }

        // Status "terjual" tidak langsung disimpan: admin harus mengisi data pembeli dulu
        // (untuk garansi 7 hari). Perubahan data lainnya tetap disimpan sekarang.
        $goingToSold = ($validated['sale_status'] ?? null) === 'sold' && $account->sale_status !== 'sold';
        if ($goingToSold) {
            unset($validated['sale_status']);
        }

        $account->update($validated);

        ActivityLog::record('update_account', $account, 'Akun ' . $account->account_code, [
            'changed' => array_values(array_diff(array_keys($account->getChanges()), ['updated_at'])),
        ]);

        $this->storeImages($request, $account);

        if ($goingToSold) {
            return redirect()
                ->route('admin.accounts.sell', $account)
                ->with('success', 'Perubahan data tersimpan. Lengkapi data pembeli untuk menandai akun terjual.');
        }

        return redirect()->route('admin.accounts.index')->with('success', 'Data akun diperbarui.');
    }

    /** Ubah status terbit / penjualan dari daftar akun. */
    public function updateStatus(Request $request, CocAccount $account)
    {
        $this->authorizeAccount($account);

        $data = $request->validate([
            'publication_status' => 'nullable|in:draft,published',
            'sale_status'        => 'nullable|in:available,sold,archived',
        ]);

        // Menandai terjual wajib lewat form data pembeli (garansi 7 hari).
        if (($data['sale_status'] ?? null) === 'sold' && $account->sale_status !== 'sold') {
            return redirect()
                ->route('admin.accounts.sell', $account)
                ->with('success', 'Isi data pembeli untuk menandai akun terjual.');
        }

        $account->update(array_filter($data));

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
        ]);

        return redirect()->route('admin.accounts.index')->with(
            'success',
            "Akun {$account->account_code} terjual ke {$sale->buyer_name}. Garansi aktif sampai {$sale->warrantyUntilLabel()}."
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

        Sale::updateBuyer($sale, $validated);

        ActivityLog::record('update_buyer', $sale->account, 'Akun ' . $sale->account_code_snapshot, [
            'transaction' => $sale->transaction_code,
            'buyer'       => $sale->buyer_name,
        ]);

        return redirect()->route('admin.accounts.index')->with(
            'success',
            "Data pembeli diperbarui. Garansi aktif sampai {$sale->warrantyUntilLabel()}."
        );
    }

    public function destroyImage(CocAccount $account, CocAccountImage $image)
    {
        $this->authorizeAccount($account);
        abort_if($image->coc_account_id !== $account->id, 404);

        $wasPrimary = $image->is_primary;
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        // Jika foto utama dihapus, jadikan foto pertama yang tersisa sebagai utama
        if ($wasPrimary) {
            $account->images()->first()?->update(['is_primary' => true]);
        }

        ActivityLog::record('delete_image', $account, 'Akun ' . $account->account_code);

        return back()->with('success', 'Foto dihapus.');
    }

    public function destroy(CocAccount $account)
    {
        $this->authorizeAccount($account);

        foreach ($account->images as $image) {
            Storage::disk('public')->delete($image->image_path);
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
        ]);
    }

    private function rules(?CocAccount $account = null): array
    {
        return [
            // unique dicek ke semua baris (termasuk akun yang sudah dihapus/soft delete)
            // karena kolom di database memang UNIQUE.
            'account_code'         => [
                'required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coc_accounts', 'account_code')->ignore($account?->id),
            ],
            'name'                 => 'required|string|max:255',
            'town_hall'            => 'required|integer|min:1|max:16',
            // kolom DB decimal(12,2) -> jaga di bawah batas supaya tidak error 1264
            'price'                => 'required|numeric|min:0|max:999999999',
            'description'          => 'nullable|string|max:5000',
            'barbarian_king_level' => 'nullable|integer|min:0|max:100',
            'archer_queen_level'   => 'nullable|integer|min:0|max:100',
            'grand_warden_level'   => 'nullable|integer|min:0|max:100',
            'royal_champion_level' => 'nullable|integer|min:0|max:100',
            'protection_status'    => 'nullable|in:Protected,Unprotected',
            'email_change_status'  => 'nullable|in:Changeable,Unchangeable',
            'images'               => 'nullable|array|max:' . self::MAX_IMAGES,
            // 5 MB per foto; foto dari browser sudah dikompres otomatis (~0.5 MB)
            'images.*'             => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
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
            'price.max'         => 'Harga terlalu besar (maksimal Rp 999.999.999).',
        ];
    }

    private function storeImages(Request $request, CocAccount $account): void
    {
        if (!$request->hasFile('images')) {
            return;
        }

        $hasPrimary = $account->images()->where('is_primary', true)->exists();
        $order      = (int) $account->images()->max('sort_order');

        foreach ($request->file('images') as $file) {
            $order++;
            CocAccountImage::create([
                'coc_account_id' => $account->id,
                'image_path'     => $file->store('accounts', 'public'),
                'is_primary'     => !$hasPrimary,
                'sort_order'     => $order,
            ]);
            $hasPrimary = true;
        }
    }
}