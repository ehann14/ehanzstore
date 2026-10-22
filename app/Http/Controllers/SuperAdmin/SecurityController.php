<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Pengaturan keamanan Super Admin: aktifkan / kelola verifikasi dua langkah.
 */
class SecurityController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = [
            'user'     => $user,
            'enabled'  => $user->hasTwoFactorEnabled(),
            'required' => (bool) config('security.two_factor.required'),
        ];

        if (! $data['enabled']) {
            // Secret sementara disimpan di sesi sampai kode pertama dikonfirmasi
            $secret = $request->session()->get('two_factor.pending_secret');

            if (! $secret) {
                $secret = Totp::generateSecret();
                $request->session()->put('two_factor.pending_secret', $secret);
            }

            $data['secret']     = $secret;
            $data['prettySecret'] = Totp::pretty($secret);
            $data['otpauthUri'] = Totp::uri($secret, $user->email, config('security.two_factor.issuer'));
        }

        return view('super-admin.security', $data);
    }

    /** Buat secret baru (mis. salah scan / ganti aplikasi) sebelum 2FA aktif. */
    public function refresh(Request $request)
    {
        abort_if(Auth::user()->hasTwoFactorEnabled(), 403);

        $request->session()->forget('two_factor.pending_secret');

        return redirect()->route('super-admin.security');
    }

    /** Konfirmasi kode pertama -> 2FA aktif, tampilkan kode pemulihan satu kali. */
    public function enable(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        abort_if($user->hasTwoFactorEnabled(), 403);

        $request->validate(['code' => ['required', 'string', 'max:10']]);

        $secret = $request->session()->get('two_factor.pending_secret');

        if (! $secret) {
            return redirect()->route('super-admin.security')->with('error', 'Sesi pengaturan habis. Scan ulang kode QR.');
        }

        $step = Totp::verify($secret, $request->input('code'));

        if ($step === null) {
            return back()->withErrors(['code' => 'Kode salah. Pastikan jam HP sudah otomatis, lalu coba kode terbaru.']);
        }

        $codes = User::generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret'         => $secret,
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at'   => now(),
            'two_factor_last_step'      => $step,
        ])->save();

        $request->session()->forget('two_factor.pending_secret');
        $request->session()->put('two_factor_passed', true);

        ActivityLog::record('two_factor_enabled', $user, 'Verifikasi dua langkah diaktifkan');

        return redirect()->route('super-admin.security')
            ->with('recovery_codes', $codes)
            ->with('success', 'Verifikasi dua langkah aktif. Simpan kode pemulihan di bawah sekarang.');
    }

    /** Buat ulang kode pemulihan (kode lama langsung tidak berlaku). */
    public function regenerateRecovery(Request $request)
    {
        $user = $this->confirmedUser($request);

        if (! $user instanceof User) {
            return $user;
        }

        $codes = User::generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        ActivityLog::record('two_factor_recovery_reset', $user, 'Kode pemulihan dibuat ulang');

        return redirect()->route('super-admin.security')
            ->with('recovery_codes', $codes)
            ->with('success', 'Kode pemulihan baru dibuat. Kode lama tidak berlaku lagi.');
    }

    /** Nonaktifkan 2FA (hanya bila tidak diwajibkan oleh config). */
    public function disable(Request $request)
    {
        abort_if(config('security.two_factor.required'), 403, '2FA diwajibkan untuk Super Admin.');

        $user = $this->confirmedUser($request);

        if (! $user instanceof User) {
            return $user;
        }

        $user->clearTwoFactor();
        $request->session()->forget('two_factor_passed');

        ActivityLog::record('two_factor_disabled', $user, 'Verifikasi dua langkah dinonaktifkan');

        return redirect()->route('super-admin.security')->with('success', 'Verifikasi dua langkah dinonaktifkan.');
    }

    // ------------------------------------------------------------------

    /**
     * Tindakan sensitif: wajib password DAN kode 2FA saat ini.
     * Mengembalikan User bila lolos, atau RedirectResponse berisi error bila gagal.
     */
    private function confirmedUser(Request $request): User|\Illuminate\Http\RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        abort_unless($user->hasTwoFactorEnabled(), 403);

        $request->validate([
            'password' => ['required', 'string'],
            'code'     => ['required', 'string', 'max:10'],
        ]);

        if (! Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Password salah.']);
        }

        $step = Totp::verify($user->two_factor_secret, $request->input('code'), 1, $user->two_factor_last_step);

        if ($step === null) {
            return back()->withErrors(['code' => 'Kode salah atau sudah dipakai. Tunggu kode berikutnya lalu coba lagi.']);
        }

        $user->forceFill(['two_factor_last_step' => $step])->save();

        return $user;
    }
}