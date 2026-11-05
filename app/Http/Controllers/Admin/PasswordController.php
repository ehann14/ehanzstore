<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Throwable;

/**
 * Ganti password sendiri. Dipakai Admin Posting dan Super Admin (satu halaman, dua role).
 *
 * Alur keamanan:
 *  - Wajib password lama.
 *  - Super Admin dengan 2FA aktif wajib juga kode authenticator saat ini.
 *  - Setelah berhasil, semua perangkat lain dikeluarkan (sesi dihapus + remember_token diganti),
 *    perangkat yang dipakai sekarang tetap login. Sama seperti DeviceController::logoutOthers.
 */
class PasswordController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        return view('admin.password', [
            'needsCode' => $user->isSuperAdmin() && $user->hasTwoFactorEnabled(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user      = $request->user();
        $needsCode = $user->isSuperAdmin() && $user->hasTwoFactorEnabled();

        $rules = [
            'current_password' => ['required', 'string'],
            'password'         => [
                'required', 'string', 'confirmed', 'different:current_password',
                Password::min(8)->letters()->numbers(),
            ],
        ];

        if ($needsCode) {
            $rules['code'] = ['required', 'string', 'max:10'];
        }

        $request->validate($rules, [
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
            'password.different' => 'Password baru harus berbeda dari password lama.',
            'password.min'       => 'Password baru minimal 8 karakter.',
            'password.letters'   => 'Password baru harus mengandung huruf.',
            'password.numbers'   => 'Password baru harus mengandung angka.',
        ]);

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Password lama salah.']);
        }

        if ($needsCode) {
            $step = Totp::verify(
                $user->two_factor_secret,
                $request->input('code'),
                1,
                $user->two_factor_last_step
            );

            if ($step === null) {
                return back()->withErrors(['code' => 'Kode salah atau sudah dipakai. Tunggu kode berikutnya lalu coba lagi.']);
            }

            $user->forceFill(['two_factor_last_step' => $step]);
        }

        $deleted = 0;

        try {
            // Cast 'hashed' pada model User otomatis meng-hash password baru.
            $user->forceFill(['password' => $request->input('password')]);
            $user->setRememberToken(Str::random(60));
            $user->save();

            if (config('session.driver') === 'database') {
                $deleted = DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $request->session()->getId())
                    ->delete();
            }

            // Beri cookie "ingat saya" baru untuk perangkat ini (Super Admin tidak memakainya).
            Auth::login($user, ! $user->isSuperAdmin());
            $request->session()->regenerate();
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Gagal mengganti password. Coba lagi.');
        }

        ActivityLog::record(
            'change_password',
            $user,
            'Mengganti password',
            ['sessions' => $deleted]
        );

        return redirect()->route('admin.password')->with(
            'success',
            'Password berhasil diganti.' . ($deleted > 0 ? " {$deleted} sesi di perangkat lain dikeluarkan." : '')
        );
    }
}