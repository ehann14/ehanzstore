<?php

namespace App\Http\Middleware;

use App\Models\VisitorEvent;
use Closure;
use Illuminate\Http\Request;

class VisitorTracking
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Hanya track jika method GET dan bukan asset/admin
        if ($request->isMethod('GET') && !$request->is('admin/*') && !$request->is('super-admin/*') && !$request->is('uploads/*')) {
            
            // Cek apakah ini page_view atau event spesifik (misal klik WA)
            $eventType = 'page_view';
            
            // Contoh deteksi klik WhatsApp dari URL parameter atau referrer jika ada
            if ($request->has('track') && $request->get('track') === 'whatsapp_click') {
                $eventType = 'click_whatsapp';
            }

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
        }

        return $response;
    }
}