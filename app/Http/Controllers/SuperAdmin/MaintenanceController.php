<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Sale;
use App\Support\Maintenance;
use App\Support\Uploads;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MaintenanceController extends Controller
{
    private const UPLOAD_FAILED = 'Gambar gagal disimpan di server. Periksa izin tulis folder uploads, lalu coba lagi.';

    public function edit()
    {
        $settings = Maintenance::settings();

        $untilInput = $settings['until']
            ? $settings['until']->clone()->timezone(Sale::DISPLAY_TZ)->format('Y-m-d\TH:i')
            : '';

        return view('super-admin.maintenance', compact('settings', 'untilInput'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'active'       => ['nullable', 'boolean'],
            'title'        => ['nullable', 'string', 'max:80'],
            'message'      => ['nullable', 'string', 'max:500'],
            'until'        => ['nullable', 'date'],
            'image'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'remove_image' => ['nullable', 'boolean'],
        ], [
            'title.max'      => 'Judul maksimal 80 karakter.',
            'message.max'    => 'Pesan maksimal 500 karakter.',
            'until.date'     => 'Format perkiraan selesai tidak valid.',
            'image.image'    => 'File harus berupa gambar.',
            'image.mimes'    => 'Format gambar harus JPG, PNG, atau WebP.',
            'image.max'      => 'Ukuran gambar maksimal 3 MB.',
            'image.uploaded' => 'Gambar gagal diunggah. Ukurannya melebihi batas upload server, coba kecilkan dulu.',
        ]);

        $active  = $request->boolean('active');
        $until   = ! empty($data['until']) ? Carbon::parse($data['until'], Sale::DISPLAY_TZ) : null;
        $current = Maintenance::settings()['image'];

        // null = gambar tidak berubah, '' = hapus gambar kustom, path = gambar baru
        $imageArg = null;
        $oldImage = null;

        if ($request->hasFile('image')) {
            try {
                $imageArg = Uploads::store($request->file('image'), 'maintenance');
            } catch (\Throwable $e) {
                report($e);

                return back()->withInput()->withErrors(['image' => self::UPLOAD_FAILED]);
            }

            $oldImage = $current;
        } elseif ($request->boolean('remove_image') && $current !== '') {
            $imageArg = '';
            $oldImage = $current;
        }

        Maintenance::save($active, $data['title'] ?? '', $data['message'] ?? '', $until, $imageArg);

        // Hapus file lama hanya setelah pengaturan baru tersimpan
        if ($oldImage) {
            Uploads::delete($oldImage);
        }

        ActivityLog::record('toggle_maintenance', null, $active ? 'Mode maintenance dinyalakan' : 'Mode maintenance dimatikan', [
            'maintenance' => $active,
            'image'       => $imageArg === null ? 'tidak berubah' : ($imageArg === '' ? 'dihapus' : 'diganti'),
        ]);

        $message = $active
            ? 'Mode maintenance AKTIF. Pengunjung umum sekarang melihat halaman maintenance. Kamu tetap bisa membuka website karena sedang login.'
            : 'Mode maintenance dimatikan. Website kembali normal untuk semua pengunjung.';

        if ($imageArg !== null) {
            $message .= $imageArg === '' ? ' Gambar kustom dihapus (memakai ilustrasi bawaan).' : ' Gambar maintenance diperbarui.';
        }

        return back()->with('success', $message);
    }

    /**
     * Pratinjau halaman maintenance untuk Super Admin (HTTP 200, tidak mengubah status mode maintenance).
     * Perlu karena admin yang login selalu melihat website normal.
     */
    public function preview()
    {
        $settings = Maintenance::settings();

        return response()
            ->view('maintenance', [
                'title'   => $settings['title'],
                'message' => $settings['message'],
                'until'   => $settings['until'],
                'image'   => $settings['image_url'],
                'preview' => true,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}