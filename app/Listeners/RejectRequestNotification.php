<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\TradeRequest as Traded;
use App\Events\RejectRequestEvent;
use App\Notifications\RejectRequestMail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RejectRequestNotification
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
    public function handle(RejectRequestEvent $event): void
    {
        $user = User::where('uuid', $event->owner)->first();
        $recipient = User::where('uuid', $event->recipient)->first();
        $recipient->notify(new RejectRequestMail($event->amount, $event->amountInNaira,  $recipient, $user,  $event->item_id, $event->wallet_name, $event->item));
        Notification::route('sms', $user->mobile)->notify(new Traded($user->firstname, 'Hi there, Someone just rejected a trade of ' . $event->amount));
    }
}
