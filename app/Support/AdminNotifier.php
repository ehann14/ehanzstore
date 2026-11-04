<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewBookingNotification;
use App\Notifications\NewReviewNotification;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Mengirim notifikasi ke admin. Penerima:
 *  - semua Super Admin yang aktif
 *  - admin biasa pemilik akun terkait (created_by), sama seperti lencana menu Booking
 *
 * Semua dibungkus try/catch: kegagalan notifikasi (mis. tabel belum dibuat)
 * tidak boleh menggagalkan pembeli yang sedang mengirim ulasan / booking.
 */
class AdminNotifier
{
    public static function newReview(Review $review): void
    {
        try {
            $review->loadMissing('account');

            $recipients = static::recipients($review->account?->created_by);

            foreach ($recipients as $user) {
                $url = $user->isSuperAdmin()
                    ? route('super-admin.reviews', ['status' => 'pending'], false)
                    : ($review->account ? route('admin.accounts.edit', $review->account->id, false) : null);

                $user->notify(new NewReviewNotification($review, $url));
            }

            // Munculkan juga di HP admin (notifikasi push), walau panel tidak sedang dibuka
            WebPush::notifyUsers($recipients->pluck('id')->all());
        } catch (Throwable $e) {
            report($e);
        }
    }

    public static function newBooking(Booking $booking): void
    {
        try {
            $booking->loadMissing('account');

            $recipients = static::recipients($booking->account?->created_by);

            foreach ($recipients as $user) {
                $user->notify(new NewBookingNotification($booking, route('admin.bookings.index', [], false)));
            }

            // Munculkan juga di HP admin (notifikasi push), walau panel tidak sedang dibuka
            WebPush::notifyUsers($recipients->pluck('id')->all());
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** @return Collection<int, User> */
    protected static function recipients(?int $ownerId): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function ($q) use ($ownerId) {
                $q->where('role', 'super_admin');

                if ($ownerId) {
                    $q->orWhere(fn ($w) => $w->where('role', 'admin')->where('id', $ownerId));
                }
            })
            ->get();
    }
}