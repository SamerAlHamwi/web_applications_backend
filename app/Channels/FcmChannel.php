<?php
// app/Channels/FcmChannel.php

namespace App\Channels;

use Illuminate\Notifications\Notification;

class FcmChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        if (!$notifiable->fcm_token) {
            return;
        }

        $notification->toFcm($notifiable);
    }
}
