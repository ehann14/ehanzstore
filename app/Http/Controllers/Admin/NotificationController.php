<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/** Lonceng notifikasi admin (dipanggil berkala oleh layout admin). */
class NotificationController extends Controller
{
    /** Jumlah belum dibaca + 15 notifikasi terbaru. */
    public function feed(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $items = $user->notifications()->latest()->limit(15)->get()->map(fn ($n) => [
                'id'      => $n->id,
                'kind'    => $n->data['kind'] ?? 'info',
                'title'   => $n->data['title'] ?? 'Notifikasi',
                'message' => $n->data['message'] ?? '',
                'url'     => $n->data['url'] ?? null,
                'read'    => $n->read_at !== null,
                'time'    => $n->created_at?->diffForHumans(),
            ])->values();

            return response()->json([
                'unread' => $user->unreadNotifications()->count(),
                'items'  => $items,
            ]);
        } catch (Throwable $e) {
            // Tabel notifications belum dibuat: lonceng tetap tampil kosong, halaman tidak error.
            return response()->json(['unread' => 0, 'items' => [], 'missing_table' => true]);
        }
    }

    /** Tandai satu notifikasi sudah dibaca. */
    public function read(Request $request, string $id): JsonResponse
    {
        try {
            $request->user()->notifications()->where('id', $id)->first()?->markAsRead();
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true]);
    }

    /** Tandai semua sudah dibaca. */
    public function readAll(Request $request): JsonResponse
    {
        try {
            $request->user()->unreadNotifications()->update(['read_at' => now()]);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true]);
    }
}