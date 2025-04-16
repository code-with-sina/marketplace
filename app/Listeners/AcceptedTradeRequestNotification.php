<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\AcceptedTradeRequestEvent;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\TradeRequestEvent;
use App\Notifications\TradeRequest;
use App\Notifications\AcceptedTradeMail;

class AcceptedTradeRequestNotification
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
    public function handle(AcceptedTradeRequestEvent $event): void
    {
        $first = User::where('uuid', $event->owner)->first();
        $user = User::where('uuid', $event->recipient)->first();
        $recipient = User::where('uuid', $event->owner)->first();
        $first->notify(new AcceptedTradeMail($event->amount, $event->amountInNaira, $event->acceptance_id, $event->session_id, $recipient, $user,  $event->item_id, $event->wallet_name, $event->item));
        Notification::route('sms', $first->mobile)->notify(new TradeRequest($first->firstname, 'Hi there, The trade of  ' . $event->amount . ' has been accepted. The following email will start your session. You can connect via:: https://market.ratefy.co/transaction/' . $event->session_id . $event->acceptance_id));

        $second = User::where('uuid', $event->recipient)->first();
        $second->notify(new AcceptedTradeMail($event->amount, $event->amountInNaira, $event->acceptance_id, $event->session_id,  $user, $recipient, $event->item_id, $event->wallet_name, $event->item));
        Notification::route('sms', $second->mobile)->notify(new TradeRequest($second->firstname, 'Hi there, The trade of  ' . $event->amount . ' has been accepted. The following email will start your session. You can connect via:: https://market.ratefy.co/transaction/' . $event->session_id . $event->acceptance_id));
    }
}
