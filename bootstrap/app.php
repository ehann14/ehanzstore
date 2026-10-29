<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Middleware\CheckMaintenanceMode;
use App\Http\Middleware\EnsureTwoFactor;
use App\Http\Middleware\ExpireHotDeals;
use App\Http\Middleware\NoStoreForSensitivePages;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\VisitorTracking;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Hosting di belakang proxy/Cloudflare: percayai header X-Forwarded-* agar
        // Laravel tahu request-nya HTTPS (mencegah form ber-action http:// dan cookie sesi hilang).
        $middleware->trustProxies(at: '*');

        // Registrasi alias middleware role
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'two-factor' => EnsureTwoFactor::class,
        ]);

        // Halaman berisi form/token tidak boleh disimpan cache browser HP (token basi -> 419)
        $middleware->appendToGroup('web', NoStoreForSensitivePages::class);

        // Mode maintenance (dinyalakan dari panel Super Admin): pengunjung umum melihat halaman maintenance.
        // Dipasang di grup web (setelah sesi aktif) supaya admin yang sudah login tetap bisa membuka situs.
        $middleware->appendToGroup('web', CheckMaintenanceMode::class);

        // Hot Deal yang sudah lewat masa berlakunya diakhiri otomatis (tanpa cron)
        $middleware->append(ExpireHotDeals::class);

        // Menjalankan tracking pengunjung secara global
        $middleware->append(VisitorTracking::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Total upload melebihi post_max_size PHP -> kembali ke form dengan pesan jelas
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            return back()->withInput($request->except('images'))->withErrors([
                'images' => 'Total ukuran foto terlalu besar untuk server (post_max_size). Kurangi jumlah/ukuran foto.',
            ]);
        });

        // 419 (token CSRF tidak cocok / sesi habis): jangan tampilkan halaman error,
        // kembalikan pengguna ke form dengan token baru dan pesan yang jelas.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            $message = 'Halaman sudah kedaluwarsa (sesi habis). Silakan coba lagi.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 419);
            }

            // Halaman login: kirim ke login lagi dengan token baru
            if ($request->is('login', 'login/*')) {
                return redirect()->route('login')->with('error', $message);
            }

            // Sisanya: kembali ke halaman sebelumnya, isian form dipertahankan (kecuali password/token)
            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation', 'images'))
                ->with('error', $message);
        });
    })->create();