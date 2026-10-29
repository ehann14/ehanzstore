<?php

namespace App\Http\Middleware;

use App\Support\Maintenance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Saat mode maintenance menyala, pengunjung umum melihat halaman maintenance (HTTP 503).
 * Yang tetap bisa lewat: halaman login, panel admin, dan admin/super admin yang sudah login.
 */
class CheckMaintenanceMode
{
    /** Path yang selalu boleh diakses. */
    private const ALLOWED = [
        'login', 'login/*', 'logout',
        'admin', 'admin/*',
        'super-admin', 'super-admin/*',
        'csrf-token', 'up',
        // Gambar halaman maintenance (route fallback) harus tetap bisa dimuat pengunjung.
        'uploads/maintenance/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $settings = Maintenance::settings();

        if (! $settings['active']) {
            return $next($request);
        }

        if ($request->is(...self::ALLOWED) || auth()->check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $settings['title'] . '. ' . $settings['message']], 503, ['Retry-After' => 3600]);
        }

        return response()
            ->view('maintenance', [
                'title'   => $settings['title'],
                'message' => $settings['message'],
                'until'   => $settings['until'],
                'image'   => $settings['image_url'],
            ], 503)
            ->header('Retry-After', '3600')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}