<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Notifications\Notification;

/** Ulasan baru dari pembeli (status pending, menunggu moderasi). */
class NewReviewNotification extends Notification
{
    public function __construct(
        public Review $review,
        public ?string $url = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $account = $this->review->account;
        $stars   = str_repeat('★', (int) $this->review->rating) . str_repeat('☆', 5 - (int) $this->review->rating);

        return [
            'kind'    => 'review',
            'title'   => 'Ulasan baru masuk',
            'message' => $this->review->reviewer_name . ' memberi ' . $stars
                . ($account ? ' untuk ' . $account->name . ' (' . $account->account_code . ')' : '')
                . '. "' . mb_strimwidth((string) $this->review->comment, 0, 90, '…') . '"',
            'url'     => $this->url,
        ];
    }
}