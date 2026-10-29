<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Melarang browser (terutama Chrome/Safari HP) menyimpan atau memulihkan halaman
 * berisi form dari cache / back-forward cache. Tanpa ini, tombol "back" menampilkan
 * form lama dengan token CSRF basi sehingga saat dikirim muncul error 419.
 */
class NoStoreForSensitivePages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $request->isMethod('GET')
            && $request->is(
                'login', 'login/*',
                'admin', 'admin/*',
                'super-admin', 'super-admin/*',
                'ulasan/*', 'cek-garansi', 'cek-garansi/*', 'invoice/*', 'csrf-token'
            )
        ) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}