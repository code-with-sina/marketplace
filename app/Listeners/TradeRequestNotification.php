<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\TradeRequestEvent;
use App\Notifications\TradeRequest as Traded;
use App\Notifications\TradeRequestMail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TradeRequestNotification
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
    public function handle(TradeRequestEvent $event): void
    {
        $user = User::where('uuid', $event->recipient)->first();
        $recipient = User::where('uuid', $event->owner)->first();


        Notification::route('sms', trim($user->mobile, '+'))->notify(new Traded(trim($user->mobile, '+'), 'Hi there, ' . $recipient->firstname . ' has just placed ' . $event->wallet_name . ' trade of  ' . $event->amount . 'USD'));

        $user->notify(new TradeRequestMail($event->amount, $event->amountInNaira, $user, $recipient, $event->item_id, $event->wallet_name, $event->item));
    }
}
