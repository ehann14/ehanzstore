<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\PaymentSettings;
use App\Support\Uploads;
use Illuminate\Http\Request;

/** Pengaturan pembayaran (QRIS) yang tampil di halaman booking pembeli. */
class PaymentController extends Controller
{
    public function edit()
    {
        return view('super-admin.payment', ['qris' => PaymentSettings::qris()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'qris'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
            'remove_qris' => ['nullable', 'boolean'],
            'note'        => ['nullable', 'string', 'max:300'],
        ], [
            'qris.image'    => 'File harus berupa gambar.',
            'qris.mimes'    => 'Format gambar harus JPG, PNG, atau WebP.',
            'qris.max'      => 'Ukuran gambar maksimal 3 MB.',
            'qris.uploaded' => 'Gambar gagal diunggah. Ukurannya melebihi batas upload server, coba kecilkan dulu.',
            'note.max'      => 'Catatan maksimal 300 karakter.',
        ]);

        $current = PaymentSettings::qris()['image'];
        $arg     = null;   // null = tidak berubah, '' = hapus, path = baru
        $old     = null;

        if ($request->hasFile('qris')) {
            try {
                $arg = Uploads::store($request->file('qris'), PaymentSettings::DIR);
            } catch (\Throwable $e) {
                report($e);

                return back()->withInput()->withErrors(['qris' => 'Gambar gagal disimpan di server. Periksa izin tulis folder uploads.']);
            }

            $old = $current;
        } elseif ($request->boolean('remove_qris') && $current !== '') {
            $arg = '';
            $old = $current;
        }

        PaymentSettings::save($arg, trim((string) ($data['note'] ?? '')));

        if ($old) {
            Uploads::delete($old);
        }

        ActivityLog::record('update_payment_settings', null, 'Pengaturan pembayaran (QRIS)', [
            'qris' => $arg === null ? 'tidak berubah' : ($arg === '' ? 'dihapus' : 'diganti'),
        ]);

        return back()->with('success', $arg === ''
            ? 'QRIS dihapus. Halaman booking pembeli kembali hanya menampilkan tombol minta info pembayaran.'
            : 'Pengaturan pembayaran disimpan.');
    }
}