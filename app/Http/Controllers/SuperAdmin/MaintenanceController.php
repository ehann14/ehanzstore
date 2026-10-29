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

        // Perkiraan selesai ditampilkan dalam WIB, format 24 jam (tanpa AM/PM)
        $until = $settings['until']?->clone()->timezone(Sale::DISPLAY_TZ);

        $untilDate   = $until?->format('Y-m-d') ?? '';
        $untilHour   = $until?->format('H') ?? '';
        $untilMinute = $until?->format('i') ?? '';
        $untilLabel  = $until ? $until->format('d M Y, H:i') . ' WIB' : null;

        return view('super-admin.maintenance', compact('settings', 'untilDate', 'untilHour', 'untilMinute', 'untilLabel'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'active'       => ['nullable', 'boolean'],
            'title'        => ['nullable', 'string', 'max:80'],
            'message'      => ['nullable', 'string', 'max:500'],
            'until_date'   => ['nullable', 'date_format:Y-m-d'],
            'until_hour'   => ['nullable', 'required_with:until_date', 'integer', 'between:0,23'],
            'until_minute' => ['nullable', 'integer', 'between:0,59'],
            'image'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'remove_image' => ['nullable', 'boolean'],
        ], [
            'title.max'      => 'Judul maksimal 80 karakter.',
            'message.max'    => 'Pesan maksimal 500 karakter.',
            'until_date.date_format'  => 'Tanggal perkiraan selesai tidak valid.',
            'until_hour.required_with' => 'Pilih jam perkiraan selesai (00-23, WIB).',
            'until_hour.integer'      => 'Jam perkiraan selesai tidak valid.',
            'until_hour.between'      => 'Jam perkiraan selesai harus antara 00 dan 23.',
            'until_minute.integer'    => 'Menit perkiraan selesai tidak valid.',
            'until_minute.between'    => 'Menit perkiraan selesai harus antara 00 dan 59.',
            'image.image'    => 'File harus berupa gambar.',
            'image.mimes'    => 'Format gambar harus JPG, PNG, atau WebP.',
            'image.max'      => 'Ukuran gambar maksimal 3 MB.',
            'image.uploaded' => 'Gambar gagal diunggah. Ukurannya melebihi batas upload server, coba kecilkan dulu.',
        ]);

        $active  = $request->boolean('active');

        // Tanggal + jam (00-23) + menit dibaca sebagai WIB, lalu disimpan sebagai UTC oleh Maintenance::save().
        $until = null;

        if (! empty($data['until_date'])) {
            $until = Carbon::createFromFormat(
                'Y-m-d H:i',
                sprintf('%s %02d:%02d', $data['until_date'], (int) $data['until_hour'], (int) ($data['until_minute'] ?? 0)),
                Sale::DISPLAY_TZ
            );
        }

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