<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Events\PaymentReleasedEvent;
use Illuminate\Support\Facades\Mail;
use App\Mail\TradeCompletionSuccess;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TradeRequest as Traded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PaymentReleasedListener
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
    public function handle(PaymentReleasedEvent $event): void
    {
        $user = User::where('uuid', $event->recipient)->first();
        $recipient = User::where('uuid', $event->owner)->first();
        // Mail::to($request->user())->send(new OrderShipped($order));
        Mail::to($user)->send(new TradeCompletionSuccess($event->amount, $user->firstname, $recipient->firstname, $event->item_id, $event->wallet_name, $event->item, $event->amount_to_receive));
        // $user->notify(new TradeCompletionSuccess($event->amount, $user, $recipient, $event->item_id, $event->wallet_name, $event->item));
        Notification::route('sms', $user->mobile)->notify(new Traded($user->firstname, 'Hi there, You have a successful trade of' . $event->amount));
    }
}
