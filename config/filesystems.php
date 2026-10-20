<?php

// Folder fisik untuk foto akun & banner. Urutan penentuan:
//  1. UPLOADS_PATH di .env (path absolut, mis. /home/user/domains/domain.com/public_html/uploads)
//  2. <document root>/uploads, jika web server melayani folder yang berisi index.php
//     (cocok untuk DirectAdmin: isi public/ dipindah ke public_html/)
//  3. public/uploads di dalam proyek (layout Laravel standar / php artisan serve)
$uploadsRoot = env('UPLOADS_PATH');

if (! $uploadsRoot) {
    $docRoot = rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');

    $uploadsRoot = ($docRoot !== '' && is_file($docRoot . '/index.php'))
        ? $docRoot . '/uploads'
        : public_path('uploads');
}

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        // Foto akun & banner: disimpan langsung di folder publik (tanpa symlink storage).
        'uploads' => [
            'driver' => 'local',
            'root' => $uploadsRoot,
            'url' => rtrim((string) env('APP_URL', ''), '/') . '/uploads',
            'visibility' => 'public',
            'permissions' => [
                'file' => ['public' => 0644, 'private' => 0600],
                'dir' => ['public' => 0755, 'private' => 0700],
            ],
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    // Symlink `storage` sengaja tidak dipakai (banyak hosting tidak mendukungnya).
    // Upload memakai disk "uploads"; disk "public" hanya tersisa untuk membaca data lama.
    'links' => [],

];