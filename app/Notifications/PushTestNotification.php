<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Notifikasi uji coba dari tombol "Kirim tes" di lonceng admin. */
class PushTestNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'kind'    => 'info',
            'title'   => 'Tes notifikasi HP',
            'message' => 'Kalau pesan ini muncul di HP kamu, notifikasi sudah aktif.',
            'url'     => route('admin.dashboard', [], false),
        ];
    }
}