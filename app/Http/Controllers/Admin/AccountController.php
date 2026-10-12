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
    public function index()
    {
        $accounts = CocAccount::where('created_by', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('admin.accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('admin.accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'town_hall' => 'required|integer|min:1|max:16',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $account = CocAccount::create([
            'account_code' => 'EHZ-' . strtoupper(Str::random(6)),
            'created_by' => Auth::id(),
            'sale_status' => 'available',
            'publication_status' => 'draft', // Default draft agar admin bisa cek dulu
            ...$validated
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('accounts', 'public');
                CocAccountImage::create([
                    'coc_account_id' => $account->id,
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                ]);
            }
        }

        return redirect()->route('admin.accounts.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(CocAccount $account)
    {
        // Policy check: hanya creator atau super admin
        if ($account->created_by !== Auth::id() && !Auth::user()->isSuperAdmin()) {
            abort(403);
        }
        return view('admin.accounts.edit', compact('account'));
    }

    public function update(Request $request, CocAccount $account)
    {
        // Validasi dan update logic...
        $account->update($request->only(['name', 'price', 'description', 'town_hall']));
        return back()->with('success', 'Data akun diperbarui.');
    }

    public function destroy(CocAccount $account)
    {
        if ($account->created_by !== Auth::id() && !Auth::user()->isSuperAdmin()) {
            abort(403);
        }
        
        // Hapus gambar dari storage
        foreach ($account->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        
        $account->delete();
        return redirect()->route('admin.accounts.index')->with('success', 'Akun dihapus.');
    }
}