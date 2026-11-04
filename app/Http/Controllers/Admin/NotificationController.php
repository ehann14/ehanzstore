<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Notifications\PushTestNotification;
use App\Support\WebPush;
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

    /**
     * Dipanggil service worker saat ada push masuk: notifikasi yang belum dibaca dan baru dibuat
     * (3 menit terakhir). Isinya diambil dari sini, bukan dari layanan push, jadi tetap privat.
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $items = $request->user()->unreadNotifications()
                ->where('created_at', '>=', now()->subMinutes(3))
                ->oldest()->limit(5)->get()
                ->map(fn ($n) => [
                    'id'      => $n->id,
                    'title'   => $n->data['title'] ?? 'Notifikasi',
                    'message' => $n->data['message'] ?? '',
                    'url'     => $n->data['url'] ?? null,
                ])->values();
        } catch (Throwable $e) {
            $items = [];
        }

        return response()->json(['items' => $items]);
    }

    /** Simpan langganan push HP/browser ini. */
    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'url:https', 'max:2000']]);

        try {
            PushSubscription::updateOrCreate(
                ['endpoint_hash' => PushSubscription::hashFor($data['endpoint'])],
                [
                    'user_id'    => $request->user()->id,
                    'endpoint'   => $data['endpoint'],
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
                ]
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'Tabel push_subscriptions belum dibuat.'], 500);
        }

        return response()->json(['ok' => true]);
    }

    /** Matikan push di HP/browser ini. */
    public function unsubscribe(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:2000']]);

        try {
            PushSubscription::where('user_id', $request->user()->id)
                ->where('endpoint_hash', PushSubscription::hashFor($data['endpoint']))
                ->delete();
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json(['ok' => true]);
    }

    /** Kirim notifikasi uji ke semua perangkat milik user yang login. */
    public function test(Request $request): JsonResponse
    {
        $checks = [
            'https'   => $request->isSecure(),
            'openssl' => extension_loaded('openssl'),
        ];

        try {
            // Catatan dibuat DULU, baru push dikirim: service worker mengambil isinya dari /push/pending
            $request->user()->notify(new PushTestNotification());

            $results = WebPush::sendNowToUser($request->user()->id);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false, 'message' => 'Gagal mengirim tes: ' . mb_substr($e->getMessage(), 0, 160), 'checks' => $checks], 500);
        }

        return response()->json([
            'ok'      => collect($results)->contains('ok', true),
            'devices' => $results,
            'checks'  => $checks,
        ]);
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