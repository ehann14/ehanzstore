<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort_order')->orderByDesc('id')->get();

        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);

        $data['image_path'] = $request->file('image')->store('banners', 'public');
        $data['link_url']   = Banner::buildLink($data['link_type'], $data['link_value'] ?? null);
        $data['is_active']  = $request->boolean('is_active', true);
        $data['created_by'] = Auth::id();
        unset($data['image']);

        $banner = Banner::create($data);

        ActivityLog::record('create_banner', $banner, 'Banner "' . $banner->title . '"');

        return redirect()->route('admin.banners.index')->with('success', 'Banner berhasil diposting.');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $this->validated($request, false);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($banner->image_path);
            $data['image_path'] = $request->file('image')->store('banners', 'public');
        }

        $data['link_url']  = Banner::buildLink($data['link_type'], $data['link_value'] ?? null);
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image']);

        $banner->update($data);

        ActivityLog::record('update_banner', $banner, 'Banner "' . $banner->title . '"');

        return redirect()->route('admin.banners.index')->with('success', 'Banner diperbarui.');
    }

    /** Aktif / nonaktifkan banner tanpa menghapusnya. */
    public function toggle(Banner $banner)
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        ActivityLog::record('toggle_banner', $banner, 'Banner "' . $banner->title . '"', [
            'is_active' => $banner->is_active,
        ]);

        return back()->with('success', $banner->is_active ? 'Banner ditampilkan.' : 'Banner disembunyikan.');
    }

    /** Tombol X di pojok kanan banner. */
    public function destroy(Banner $banner)
    {
        Storage::disk('public')->delete($banner->image_path);

        ActivityLog::record('delete_banner', $banner, 'Banner "' . $banner->title . '"');

        $banner->delete();

        return back()->with('success', 'Banner dihapus.');
    }

    private function validated(Request $request, bool $imageRequired): array
    {
        $validated = $request->validate([
            'title'      => 'required|string|max:120',
            'image'      => ($imageRequired ? 'required' : 'nullable') . '|image|mimes:jpeg,png,jpg,webp|max:4096',
            'link_type'  => 'required|in:none,whatsapp,instagram,custom',
            'link_value' => [
                'nullable', 'string', 'max:255',
                'required_if:link_type,whatsapp,instagram,custom',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('link_type') === 'custom'
                        && (! filter_var($value, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $value))) {
                        $fail('Link custom harus berupa URL lengkap, contoh: https://contoh.com');
                    }
                },
            ],
            'sort_order' => 'nullable|integer|min:0|max:9999',
        ]);

        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        return $validated;
    }
}