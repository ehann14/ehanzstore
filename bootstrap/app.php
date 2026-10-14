<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\VisitorTracking;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Registrasi alias middleware role
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

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
    })->create();