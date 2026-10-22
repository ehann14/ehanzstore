<?php

namespace App\Http\Middleware;

use App\Models\CocAccount;
use Closure;
use Illuminate\Http\Request;
use Throwable;

/**
 * Mengakhiri Hot Deal yang masa berlakunya sudah lewat, tanpa cron:
 * setiap ada request, satu query ringan mengembalikan harga ke harga normal
 * dan menghapus label Hot Deal pada promo yang sudah berakhir.
 * Jika tidak ada promo yang berakhir, query tidak menulis apa pun.
 */
class ExpireHotDeals
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->is('uploads/*', 'track/event', 'up')) {
            try {
                CocAccount::expireHotDeals();
            } catch (Throwable $e) {
                // Mis. kolom belum ada karena migrasi belum dijalankan: jangan sampai situs ikut error.
                report($e);
            }
        }

        return $next($request);
    }
}