<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Hapus riwayat aktivitas (khusus Super Admin).
 * Setiap penghapusan massal dicatat sebagai satu catatan baru supaya tetap ada jejak siapa yang membersihkan.
 */
class ActivityLogController extends Controller
{
    /** Hapus satu catatan. */
    public function destroy(ActivityLog $log)
    {
        $log->delete();

        return back()->with('success', 'Catatan aktivitas dihapus.');
    }

    /** Hapus catatan yang dicentang. */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ], [
            'ids.required' => 'Pilih minimal satu catatan yang akan dihapus.',
            'ids.min'      => 'Pilih minimal satu catatan yang akan dihapus.',
        ]);

        $deleted = ActivityLog::whereIn('id', $data['ids'])->delete();

        ActivityLog::record('delete_activity_logs', null, "Menghapus {$deleted} catatan terpilih", ['count' => $deleted]);

        return back()->with('success', "{$deleted} catatan aktivitas dihapus.");
    }

    /** Bersihkan banyak catatan sekaligus (butuh password Super Admin). */
    public function clear(Request $request)
    {
        $data = $request->validate([
            'scope'    => ['required', 'in:older_7,older_30,older_90,filtered,all'],
            'password' => ['required', 'string'],
            'user_id'  => ['nullable', 'integer'],
            'action'   => ['nullable', 'string', 'max:50'],
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date'],
        ], [
            'password.required' => 'Masukkan password kamu untuk konfirmasi.',
        ]);

        if (! Hash::check($data['password'], Auth::user()->password)) {
            return back()->with('error', 'Password salah. Riwayat aktivitas tidak dihapus.');
        }

        $query = ActivityLog::query();

        switch ($data['scope']) {
            case 'older_7':
            case 'older_30':
            case 'older_90':
                $days = (int) substr($data['scope'], 6);
                $query->where('created_at', '<', now()->subDays($days));
                $label = "lebih lama dari {$days} hari";
                break;

            case 'filtered':
                $query->when(! empty($data['user_id']), fn ($q) => $q->where('user_id', $data['user_id']))
                      ->when(! empty($data['action']) && array_key_exists($data['action'], ActivityLog::ACTIONS), fn ($q) => $q->where('action', $data['action']))
                      ->when(! empty($data['from']), fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                      ->when(! empty($data['to']), fn ($q) => $q->whereDate('created_at', '<=', $data['to']));

                if (empty($data['user_id']) && empty($data['action']) && empty($data['from']) && empty($data['to'])) {
                    return back()->with('error', 'Tidak ada filter yang aktif. Pilih "Semua catatan" bila ingin menghapus semuanya.');
                }

                $label = 'sesuai filter';
                break;

            default:
                $label = 'semua catatan';
                break;
        }

        $deleted = $query->delete();

        ActivityLog::record('clear_activity_logs', null, "Membersihkan riwayat ({$label})", ['count' => $deleted, 'scope' => $data['scope']]);

        return redirect()
            ->route('super-admin.activity-logs')
            ->with('success', "{$deleted} catatan aktivitas dihapus ({$label}).");
    }
}