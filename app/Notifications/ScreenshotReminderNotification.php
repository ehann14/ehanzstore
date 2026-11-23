<?php

namespace App\Notifications;

use App\Models\CocAccount;
use Illuminate\Notifications\Notification;

/** Pengingat: akun terjadwal tayang sebentar lagi, ss ulang spek akunnya dulu. */
class ScreenshotReminderNotification extends Notification
{
    public function __construct(
        public CocAccount $account,
        public ?string $url = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $a = $this->account;

        return [
            'kind'    => 'screenshot',
            'title'   => 'Ss ulang spek sebelum tayang',
            'message' => $a->name . ' [' . $a->account_code . '] tayang otomatis ' . $a->publishAtLabel()
                . '. Cek & ss ulang spek akunnya dulu supaya foto sesuai kondisi terbaru.',
            'url'     => $this->url,
        ];
    }
}