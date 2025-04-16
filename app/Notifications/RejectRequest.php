<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Messages\ShortMessageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RejectRequest extends Notification implements ShouldQueue
{
    use Queueable;
    public $to;
    public $amount;

    /**
     * Create a new notification instance.
     */
    public function __construct($to = null, $amount)
    {
        $this->to = $to;
        $this->amount = $amount;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return  ['sms' => ShortMessageChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toShortMessage(object $notifiable): ShortMessageService 
    {
        return  (new ShortMessageService)->to($this->to)->message($this->amount);
    }
}
