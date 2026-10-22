<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Langkah kedua login Super Admin: kode 6 digit dari aplikasi authenticator,
 * atau salah satu kode pemulihan (sekali pakai) bila HP hilang.
 */
class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        if (! $this->pendingUser($request)) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function verify(Request $request)
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Waktu verifikasi habis. Silakan login ulang.']);
        }

        $request->validate(['code' => ['required', 'string', 'max:32']]);

        $key   = 'two-factor:' . $user->id . '|' . $request->ip();
        $max   = (int) config('security.two_factor.max_attempts');
        $decay = (int) config('security.two_factor.decay_seconds');

        if (RateLimiter::tooManyAttempts($key, $max)) {
            return $this->abortChallenge($request, $user, 'Terlalu banyak kode salah. Login ulang dalam '
                . LoginController::waitText(RateLimiter::availableIn($key)) . '.');
        }

        $input = trim($request->input('code'));
        $usedRecovery = false;

        if (preg_match('/^\d{3}\s?\d{3}$/', $input)) {
            $step = Totp::verify($user->two_factor_secret, $input, 1, $user->two_factor_last_step);
            $ok   = $step !== null;

            if ($ok) {
                $user->forceFill(['two_factor_last_step' => $step])->save();
            }
        } else {
            $ok           = $this->consumeRecoveryCode($user, $input);
            $usedRecovery = $ok;
        }

        if (! $ok) {
            RateLimiter::hit($key, $decay);
            ActivityLog::recordFor($user->id, 'two_factor_failed', $user, 'Kode verifikasi salah');

            if (RateLimiter::tooManyAttempts($key, $max)) {
                return $this->abortChallenge($request, $user, 'Terlalu banyak kode salah. Login ulang dalam '
                    . LoginController::waitText(RateLimiter::availableIn($key)) . '.');
            }

            return back()->withErrors(['code' => 'Kode salah atau sudah kedaluwarsa. Sisa percobaan: '
                . max(0, $max - RateLimiter::attempts($key)) . '.']);
        }

        RateLimiter::clear($key);
        $request->session()->forget(['two_factor.user_id', 'two_factor.expires_at']);

        if ($usedRecovery) {
            ActivityLog::recordFor($user->id, 'two_factor_recovery_used', $user, 'Login memakai kode pemulihan', [
                'codes_left' => $user->recoveryCodesLeft(),
            ]);
        }

        $response = LoginController::completeLogin($request, $user, false, true);

        if ($usedRecovery) {
            $response->with('success', 'Anda masuk memakai kode pemulihan. Sisa ' . $user->recoveryCodesLeft()
                . ' kode. Buat kode baru di menu Keamanan.');
        }

        return $response;
    }

    /** Batal dan kembali ke form login. */
    public function cancel(Request $request)
    {
        $request->session()->forget(['two_factor.user_id', 'two_factor.expires_at']);

        return redirect()->route('login');
    }

    // ------------------------------------------------------------------

    /** User yang sudah lolos password dan menunggu kode (null bila tidak ada / kedaluwarsa). */
    private function pendingUser(Request $request): ?User
    {
        $id      = $request->session()->get('two_factor.user_id');
        $expires = (int) $request->session()->get('two_factor.expires_at', 0);

        if (! $id || now()->getTimestamp() > $expires) {
            $request->session()->forget(['two_factor.user_id', 'two_factor.expires_at']);

            return null;
        }

        $user = User::find($id);

        return ($user && $user->is_active && $user->isSuperAdmin() && $user->hasTwoFactorEnabled()) ? $user : null;
    }

    private function abortChallenge(Request $request, User $user, string $message)
    {
        $request->session()->forget(['two_factor.user_id', 'two_factor.expires_at']);
        ActivityLog::recordFor($user->id, 'login_locked', $user, 'Verifikasi dua langkah diblokir: terlalu banyak kode salah');

        return redirect()->route('login')->withErrors(['email' => $message]);
    }

    /** Cocokkan kode pemulihan dan hapus (sekali pakai). */
    private function consumeRecoveryCode(User $user, string $input): bool
    {
        $input = strtolower(trim($input));
        $codes = $user->two_factor_recovery_codes ?? [];
        $found = null;

        foreach ($codes as $i => $code) {
            if (hash_equals($code, $input) && $found === null) {
                $found = $i;
            }
        }

        if ($found === null) {
            return false;
        }

        unset($codes[$found]);
        $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

        return true;
    }
}