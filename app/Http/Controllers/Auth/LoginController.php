<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /**
     * Hash bcrypt palsu: dipakai agar waktu respons sama baik email terdaftar maupun tidak
     * (mencegah orang menebak email admin dari kecepatan respons).
     */
    private const DUMMY_HASH = '$2y$12$nL7cAj6cpc1gV9F2/Gt2R.wH2xT.P92fF3Yke8FrhjcIL/Tmyw5Xa';

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email    = Str::lower(trim($credentials['email']));
        $emailKey = self::emailKey($email, $request->ip());
        $ipKey    = self::ipKey($request->ip());

        $maxAttempts   = (int) config('security.login.max_attempts');
        $ipMaxAttempts = (int) config('security.login.ip_max_attempts');
        $decay         = (int) config('security.login.decay_seconds');

        $user = User::where('email', $email)->first();

        // 1) Sedang diblokir? Tolak sebelum memeriksa password sama sekali.
        if (RateLimiter::tooManyAttempts($emailKey, $maxAttempts) || RateLimiter::tooManyAttempts($ipKey, $ipMaxAttempts)) {
            $seconds = max(RateLimiter::availableIn($emailKey), RateLimiter::availableIn($ipKey));

            if ($user) {
                ActivityLog::recordFor($user->id, 'login_locked', $user, 'Login ditolak: terlalu banyak percobaan');
            }

            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan login. Coba lagi dalam ' . self::waitText($seconds) . '.',
            ])->onlyInput('email');
        }

        // 2) Periksa password. Hash::check tetap dijalankan walau email tidak ada.
        $valid = Hash::check($credentials['password'], $user?->password ?? self::DUMMY_HASH) && $user !== null;

        if (! $valid) {
            RateLimiter::hit($emailKey, $decay);
            RateLimiter::hit($ipKey, $decay);

            if ($user) {
                ActivityLog::recordFor($user->id, 'login_failed', $user, 'Password salah');
            }

            $left = max(0, $maxAttempts - RateLimiter::attempts($emailKey));

            return back()->withErrors([
                'email' => $left > 0
                    ? "Email atau password salah. Sisa percobaan: {$left}."
                    : 'Email atau password salah. Login diblokir sementara, coba lagi dalam ' . self::waitText($decay) . '.',
            ])->onlyInput('email');
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Akun Anda telah dinonaktifkan.'])->onlyInput('email');
        }

        // Password benar: hapus hitungan gagal untuk email ini (hitungan IP tetap, agar tidak bisa di-reset penyerang)
        RateLimiter::clear($emailKey);

        // 3) Super Admin dengan 2FA aktif belum login: minta kode dulu.
        if ($user->isSuperAdmin() && $user->hasTwoFactorEnabled()) {
            $request->session()->regenerate();
            $request->session()->put('two_factor.user_id', $user->id);
            $request->session()->put('two_factor.expires_at', now()->addSeconds((int) config('security.two_factor.challenge_ttl'))->getTimestamp());

            return redirect()->route('two-factor.challenge');
        }

        // "Ingat saya" tidak berlaku untuk Super Admin: cookie-nya akan melewati verifikasi dua langkah.
        $remember = $request->boolean('remember') && ! $user->isSuperAdmin();

        return $this->completeLogin($request, $user, $remember);
    }

    /** Selesaikan login: buat sesi, catat aktivitas, arahkan sesuai role. Dipakai juga oleh TwoFactorController. */
    public static function completeLogin(Request $request, User $user, bool $remember = false, bool $twoFactorPassed = false): RedirectResponse
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();

        if ($twoFactorPassed) {
            $request->session()->put('two_factor_passed', true);
        }

        ActivityLog::record('login', $user, 'Masuk ke panel');

        return $user->isSuperAdmin()
            ? redirect()->intended('/super-admin/dashboard')
            : redirect()->intended('/admin/dashboard');
    }

    public function logout(Request $request)
    {
        ActivityLog::record('logout', Auth::user(), 'Keluar dari panel');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public static function emailKey(string $email, ?string $ip): string
    {
        return 'login:email:' . sha1(Str::lower($email) . '|' . $ip);
    }

    public static function ipKey(?string $ip): string
    {
        return 'login:ip:' . $ip;
    }

    public static function waitText(int $seconds): string
    {
        return $seconds >= 60
            ? (int) ceil($seconds / 60) . ' menit'
            : max(1, $seconds) . ' detik';
    }
}