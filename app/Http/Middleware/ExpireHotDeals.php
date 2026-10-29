<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use App\Models\CocAccount;
use Closure;
use Illuminate\Http\Request;
use Throwable;

/**
 * Mengakhiri Hot Deal yang masa berlakunya sudah lewat, tanpa cron:
 * setiap ada request, satu query ringan mengembalikan harga ke harga normal
 * dan menghapus label Hot Deal pada promo yang sudah berakhir.
 * Jika tidak ada promo yang berakhir, query tidak menulis apa pun.
 *
 * Cara yang sama dipakai untuk jadwal tayang akun (publish_at): akun draft yang
 * jadwalnya sudah tiba diterbitkan otomatis.
 */
class ExpireHotDeals
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->is('uploads/*', 'track/event', 'up')) {
            try {
                CocAccount::expireHotDeals();
                Booking::expireOverdue(); // booking yang lewat batas waktu dilepas otomatis
                CocAccount::publishDue(); // akun draft yang jadwal tayangnya tiba diterbitkan otomatis
            } catch (Throwable $e) {
                // Mis. kolom belum ada karena migrasi belum dijalankan: jangan sampai situs ikut error.
                report($e);
            }
        }

        return $next($request);
    }
}