<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\CancelTradeRequestEvent;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CancelTradeRequestMail;
use App\Notifications\TradeRequest as Traded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCancelTradeRequestNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CancelTradeRequestEvent $event): void
    {
        $user = User::where('uuid', $event->recipient)->first();
        $recipient = User::where('uuid', $event->owner)->first();
        $user->notify(new CancelTradeRequestMail($event->amount, $event->amountInNaira, $user, $recipient, $event->item_id, $event->wallet_name, $event->item));
        Notification::route('sms', $user->mobile)->notify(new Traded($user->firstname, 'Hi there, Someone just rejected a trade of '.$event->amount));
    }
}
