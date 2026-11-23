<?php

namespace App\Support;

use App\Models\CocAccount;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Pengingat otomatis untuk admin (tanpa cron, dipicu middleware; atau lewat `php artisan accounts:remind`):
 *
 *  1. Akun draft yang terjadwal tayang (publish_at) dalam N jam ke depan -> "ss ulang spek dulu".
 *  2. Akun yang Gmail-nya sudah lewat masa tunggu (email_change_available_at) -> "Gmail sudah bisa diganti".
 *
 * Setiap pengingat hanya dikirim sekali per jadwal (lihat kolom publish_reminded_for / email_ready_notified_for).
 */
class AccountReminders
{
    /** Jarak minimal antar pengecekan lewat middleware (detik). */
    private const THROTTLE_SECONDS = 60;

    /** Dipanggil dari middleware: dibatasi 1x per menit supaya request biasa tidak terbebani. */
    public static function runThrottled(): void
    {
        try {
            if (! Cache::add('account_reminders_lock', 1, self::THROTTLE_SECONDS)) {
                return;
            }

            static::run();
        } catch (Throwable $e) {
            report($e); // mis. kolom belum ada karena migrasi belum dijalankan
        }
    }

    /** @return array{screenshot:int,email:int} jumlah notifikasi yang dikirim */
    public static function run(): array
    {
        return [
            'screenshot' => static::screenshotReminders(),
            'email'      => static::emailChangeable(),
        ];
    }

    /** Berapa jam sebelum tayang pengingat ss dikirim (atur lewat PUBLISH_REMINDER_HOURS di .env). */
    public static function leadHours(): int
    {
        return max(1, (int) config('store.publish_reminder_hours', 3));
    }

    protected static function screenshotReminders(): int
    {
        $count = 0;

        CocAccount::query()
            ->where('publication_status', 'draft')
            ->whereNotNull('publish_at')
            ->where('publish_at', '>', now())
            ->where('publish_at', '<=', now()->addHours(static::leadHours()))
            ->where(function ($q) {
                $q->whereNull('publish_reminded_for')
                  ->orWhereColumn('publish_reminded_for', '!=', 'publish_at');
            })
            ->get()
            ->each(function (CocAccount $account) use (&$count) {
                // Tandai dulu: kalau pengiriman gagal di tengah jalan, tidak akan terkirim berulang-ulang.
                CocAccount::whereKey($account->id)->update(['publish_reminded_for' => $account->publish_at]);

                AdminNotifier::screenshotReminder($account);
                $count++;
            });

        return $count;
    }

    protected static function emailChangeable(): int
    {
        $count = 0;

        CocAccount::query()
            ->where('listing_type', 'account')
            ->where('email_change_status', 'Changeable')
            ->where('sale_status', '!=', 'sold')
            ->whereNotNull('email_change_available_at')
            ->where('email_change_available_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('email_ready_notified_for')
                  ->orWhereColumn('email_ready_notified_for', '!=', 'email_change_available_at');
            })
            ->get()
            ->each(function (CocAccount $account) use (&$count) {
                CocAccount::whereKey($account->id)->update(['email_ready_notified_for' => $account->email_change_available_at]);

                AdminNotifier::emailChangeable($account);
                $count++;
            });

        return $count;
    }
}