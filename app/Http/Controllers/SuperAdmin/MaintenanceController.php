<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Sale;
use App\Support\Maintenance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MaintenanceController extends Controller
{
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
            'active'  => ['nullable', 'boolean'],
            'title'   => ['nullable', 'string', 'max:80'],
            'message' => ['nullable', 'string', 'max:500'],
            'until'   => ['nullable', 'date'],
        ], [
            'title.max'   => 'Judul maksimal 80 karakter.',
            'message.max' => 'Pesan maksimal 500 karakter.',
            'until.date'  => 'Format perkiraan selesai tidak valid.',
        ]);

        $active = $request->boolean('active');
        $until  = ! empty($data['until']) ? Carbon::parse($data['until'], Sale::DISPLAY_TZ) : null;

        Maintenance::save($active, $data['title'] ?? '', $data['message'] ?? '', $until);

        ActivityLog::record('toggle_maintenance', null, $active ? 'Mode maintenance dinyalakan' : 'Mode maintenance dimatikan', [
            'maintenance' => $active,
        ]);

        return back()->with('success', $active
            ? 'Mode maintenance AKTIF. Pengunjung umum sekarang melihat halaman maintenance. Kamu tetap bisa membuka website karena sedang login.'
            : 'Mode maintenance dimatikan. Website kembali normal untuk semua pengunjung.');
    }
}