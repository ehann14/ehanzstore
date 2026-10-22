<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Jalan keluar darurat bila Super Admin kehilangan HP DAN kode pemulihan.
 * Hanya bisa dijalankan dari server (SSH / terminal hosting).
 *
 *   php artisan twofactor:reset admin@ehanzstore.id
 */
class ResetTwoFactor extends Command
{
    protected $signature = 'twofactor:reset {email : Email pengguna}';

    protected $description = 'Matikan verifikasi dua langkah milik satu pengguna (untuk pemulihan darurat)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Pengguna tidak ditemukan.');

            return self::FAILURE;
        }

        if (! $user->hasTwoFactorEnabled()) {
            $this->info('2FA pengguna ini memang belum aktif.');

            return self::SUCCESS;
        }

        $user->clearTwoFactor();
        ActivityLog::recordFor($user->id, 'two_factor_disabled', $user, '2FA direset lewat artisan (pemulihan darurat)');

        $this->info("2FA untuk {$user->email} dimatikan. Login lalu aktifkan ulang di menu Keamanan.");

        return self::SUCCESS;
    }
}