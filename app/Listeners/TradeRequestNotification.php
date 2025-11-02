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
    private const secondSend = "freelancingandi@gmail.com";
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

        if(strtolower($user->email) === self::secondSend) {
            Notification::route('sms', trim($user->mobile, '+'))->notify(new Traded(trim($user->mobile, '+'), 'Hi, ' . $recipient->firstname . ' just placed a trade of  ' . $event->amount . ' units. Powered by Ratefy.'));
            Notification::route('sms', trim('+2348113800308', '+'))->notify(new Traded(trim('+2348113800308', '+'), 'Hi, ' . $recipient->firstname . ' just placed a trade of  ' . $event->amount . ' units. Powered by Ratefy.'));
            $user->notify(new TradeRequestMail($event->amount, $event->amountInNaira, $user, $recipient, $event->item_id, $event->wallet_name, $event->item));
        }else {
             Notification::route('sms', trim($user->mobile, '+'))->notify(new Traded(trim($user->mobile, '+'), 'Hi, ' . $recipient->firstname . ' just placed a trade of  ' . $event->amount . ' units. Powered by Ratefy.'));
            $user->notify(new TradeRequestMail($event->amount, $event->amountInNaira, $user, $recipient, $event->item_id, $event->wallet_name, $event->item));
        }
    }


    
}