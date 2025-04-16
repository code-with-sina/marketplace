<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ShortMessageChannel 
{
    public function send($notifiable, Notification $notification): void
    {
        $message = $notification->toShortMessage($notifiable);
        $message->send();
    }
}
