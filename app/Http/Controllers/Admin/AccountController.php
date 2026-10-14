<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CocAccount;
use App\Models\CocAccountImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    private const MAX_IMAGES = 5;

    public function index()
    {
        $query = CocAccount::with('primaryImage')->orderByDesc('created_at');

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
        $validated = $request->validate($this->rules(), $this->messages());
        unset($validated['images']);

        $account = CocAccount::create([
            'account_code'       => 'EHZ-' . strtoupper(Str::random(6)),
            'created_by'         => Auth::id(),
            'sale_status'        => 'available',
            'publication_status' => 'draft', // draft dulu agar admin bisa cek
            ...$validated,
        ]);

        $this->storeImages($request, $account);

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

        $rules = $this->rules();
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

        $account->update($validated);
        $this->storeImages($request, $account);

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

        $account->update(array_filter($data));

        return back()->with('success', 'Status akun diperbarui.');
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

        return back()->with('success', 'Foto dihapus.');
    }

    public function destroy(CocAccount $account)
    {
        $this->authorizeAccount($account);

        foreach ($account->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $account->delete();

        return redirect()->route('admin.accounts.index')->with('success', 'Akun dihapus.');
    }

    // ------------------------------------------------------------------

    private function authorizeAccount(CocAccount $account): void
    {
        if ($account->created_by !== Auth::id() && !Auth::user()->isSuperAdmin()) {
            abort(403);
        }
    }

    private function rules(): array
    {
        return [
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
            'email_change_status'  => 'nullable|in:Original,Changed,Unknown',
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