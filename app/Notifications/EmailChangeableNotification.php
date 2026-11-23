<?php

namespace App\Notifications;

use App\Models\CocAccount;
use Illuminate\Notifications\Notification;

/** Gmail akun sudah melewati masa tunggu dan sekarang bisa diganti. */
class EmailChangeableNotification extends Notification
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
            'kind'    => 'email',
            'title'   => 'Gmail akun sudah bisa diganti',
            'message' => $a->name . ' [' . $a->account_code . '] sekarang sudah bisa ganti Gmail.',
            'url'     => $this->url,
        ];
    }
}