<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menjaga akun Super Admin:
 *  - Jika 2FA aktif, sesi harus sudah melewati langkah kode (flag two_factor_passed).
 *    Ini menutup celah cookie "ingat saya" lama yang memulihkan sesi tanpa kode.
 *  - Jika 2FA wajib (config security.two_factor.required) tapi belum aktif, Super Admin
 *    diarahkan ke halaman pengaturan keamanan sampai selesai.
 *
 * Pemakaian: 'two-factor' (semua halaman) atau 'two-factor:setup' (halaman pengaturan 2FA
 * itu sendiri, yang harus bisa dibuka sebelum 2FA aktif).
 */
class EnsureTwoFactor
{
    public function handle(Request $request, Closure $next, ?string $mode = null): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            return $next($request);
        }

        if ($user->hasTwoFactorEnabled()) {
            if (! $request->session()->get('two_factor_passed')) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Sesi tidak lagi valid. Silakan login ulang dengan kode verifikasi.');
            }

            return $next($request);
        }

        if ($mode !== 'setup' && config('security.two_factor.required')) {
            return redirect()->route('super-admin.security')
                ->with('error', 'Aktifkan verifikasi dua langkah dulu untuk melanjutkan memakai panel Super Admin.');
        }

        return $next($request);
    }
}