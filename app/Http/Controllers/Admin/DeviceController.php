<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Sale;
use App\Support\DeviceInfo;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

/**
 * Daftar perangkat yang sedang login + tombol "keluar dari semua perangkat lain".
 *
 * Penting: login Admin memakai cookie "ingat saya" (lihat LoginController), jadi menghapus baris sesi saja
 * tidak cukup. Perangkat lain akan otomatis login lagi lewat cookie itu. Karena itu remember_token juga
 * diganti, dan perangkat yang sedang dipakai diberi cookie baru supaya tidak ikut keluar.
 */
class DeviceController extends Controller
{
    public function index(Request $request)
    {
        $supported = config('session.driver') === 'database';
        $devices   = collect();

        if ($supported) {
            try {
                $currentId = (string) $request->session()->getId();

                $devices = $this->sessions()
                    ->where('user_id', $request->user()->id)
                    ->orderByDesc('last_activity')
                    ->get()
                    ->map(function ($row) use ($currentId) {
                        $info = DeviceInfo::parse($row->user_agent);
                        $seen = Carbon::createFromTimestamp((int) $row->last_activity);

                        return [
                            'current'      => hash_equals($currentId, (string) $row->id),
                            'browser'      => $info['browser'],
                            'os'           => $info['os'],
                            'model'        => $info['model'],
                            'type'         => $info['type'],
                            'ip'           => $row->ip_address ?: '-',
                            'last_seen'    => $seen->diffForHumans(),
                            'last_seen_at' => $seen->timezone(Sale::DISPLAY_TZ)->format('d M Y, H:i') . ' WIB',
                        ];
                    })
                    ->sortBy(fn ($d) => $d['current'] ? 0 : 1) // perangkat ini selalu di paling atas
                    ->values();
            } catch (Throwable $e) {
                report($e);
                $supported = false;
            }
        }

        return view('admin.devices', [
            'supported' => $supported,
            'devices'   => $devices,
            'others'    => $devices->where('current', false)->count(),
        ]);
    }

    /** Keluarkan semua perangkat lain. Perangkat yang sedang dipakai tetap login. */
    public function logoutOthers(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        $deleted = 0;

        try {
            if (config('session.driver') === 'database') {
                $deleted = $this->sessions()
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $request->session()->getId())
                    ->delete();
            }

            // Batalkan cookie "ingat saya" di semua perangkat, lalu beri cookie baru untuk perangkat ini.
            // Super Admin tidak memakai cookie itu (lihat LoginController), jadi tidak diberi cookie baru.
            $user->setRememberToken(Str::random(60));
            $user->save();
            Auth::login($user, ! $user->isSuperAdmin());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal mengeluarkan perangkat lain. Coba lagi.');
        }

        ActivityLog::record('logout_all_devices', $user, "Keluar dari semua perangkat lain ({$deleted} sesi)", ['sessions' => $deleted]);

        return redirect()->route('admin.devices')->with(
            'success',
            $deleted > 0
                ? "{$deleted} sesi di perangkat lain sudah dikeluarkan."
                : 'Tidak ada sesi lain yang aktif. Login "ingat saya" di perangkat lain tetap dibatalkan.'
        );
    }

    private function sessions()
    {
        return DB::connection(config('session.connection'))->table(config('session.table', 'sessions'));
    }
}