<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Messages\ShortMessageService;
use Illuminate\Support\Facades\Log;
use App\Notifications\ShortMessageChannel;
use Illuminate\Notifications\Notification;

class TradeRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public $to;
    public $amount;
 
    public function __construct($to, $amount)
    {
        $this->to = $to;
        $this->amount = $amount;
    }


    public function via(object $notifiable): array
    {
        return  ['sms' => ShortMessageChannel::class];
    }


    public function toShortMessage(object $notifiable): ShortMessageService 
    {
        return  (new ShortMessageService)->to($this->to)->message($this->amount);
    } 
}
