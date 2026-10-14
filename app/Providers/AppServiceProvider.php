<?php

namespace App\Providers;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Kontak toko dibagikan ke layout publik. Nilainya dibaca dari store_settings
        // (bisa diubah Super Admin), dengan nilai bawaan jika belum diatur.
        $contact = null;

        View::composer(
            ['layouts.app', 'layouts.partials.*', 'public.*'],
            function ($view) use (&$contact) {
                if ($contact === null) {
                    try {
                        $wa = preg_replace('/\D+/', '', (string) StoreSetting::get('whatsapp_number', '6282130559126'));
                        $ig = ltrim((string) StoreSetting::get('instagram_username', 'ehanzstore.id'), '@');
                    } catch (\Throwable $e) {
                        // tabel belum ada (mis. saat migrate pertama kali)
                        $wa = '6282130559126';
                        $ig = 'ehanzstore.id';
                    }

                    $local = '0' . ltrim(preg_replace('/^62/', '', $wa), '0');

                    $contact = [
                        'storeWa'        => $wa,
                        'storeWaUrl'     => 'https://wa.me/' . $wa,
                        'storeWaDisplay' => preg_replace('/^(\d{4})(\d{4})(\d+)$/', '$1-$2-$3', $local),
                        'storeIg'        => $ig,
                        'storeIgUrl'     => 'https://instagram.com/' . $ig,
                    ];
                }

                $view->with($contact);
            }
        );
    }
}