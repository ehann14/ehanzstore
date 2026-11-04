<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

/** Booking akun baru dari pembeli (menunggu DP dibayar). */
class NewBookingNotification extends Notification
{
    public function __construct(
        public Booking $booking,
        public ?string $url = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $b = $this->booking;

        return [
            'kind'    => 'booking',
            'title'   => 'Booking akun baru',
            'message' => $b->buyer_name . ' (' . $b->localPhone() . ') booking '
                . $b->account_name_snapshot . ' [' . $b->account_code_snapshot . ']. '
                . 'DP Rp ' . number_format((float) $b->dp_amount, 0, ',', '.')
                . ' · kode ' . $b->booking_code . '.',
            'url'     => $this->url,
        ];
    }
}