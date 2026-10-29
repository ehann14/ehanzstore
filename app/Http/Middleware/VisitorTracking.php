<?php

namespace App\Http\Middleware;

use App\Models\VisitorEvent;
use Closure;
use Illuminate\Http\Request;
use Throwable;

class VisitorTracking
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Hanya track GET untuk halaman publik (bukan asset, admin, login, atau endpoint token)
        if (
            $request->isMethod('GET')
            && $response->getStatusCode() !== 503 // halaman maintenance tidak dihitung sebagai kunjungan
            && ! $request->is('admin/*', 'super-admin/*', 'uploads/*', 'login', 'login/*', 'csrf-token', 'up')
        ) {
            // Cek apakah ini page_view atau event spesifik (misal klik WA)
            $eventType = 'page_view';

            if ($request->has('track') && $request->get('track') === 'whatsapp_click') {
                $eventType = 'click_whatsapp';
            }

            try {
                VisitorEvent::create([
                    'anonymous_session_id' => session()->getId(),
                    'event_type' => $eventType,
                    'page_path' => $request->path(),
                    'event_metadata' => json_encode([
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'referer' => $request->header('referer'),
                    ]),
                ]);
            } catch (Throwable $e) {
                // Tracking tidak boleh membuat halaman ikut error
                report($e);
            }
        }

        return $response;
    }
}