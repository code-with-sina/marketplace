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
use App\Services\WhatsappNotificationService;

class TradeRequestNotification
{
    private const secondSend = "freelancingandi@gmail.com";
    private const thirdSend = "judithmbama6@gmail.com";
    private const sendA = "2347064530382";
    private const sendB = "2348113800308";
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
            Notification::route('sms', trim('+2347026300404', '+'))->notify(new Traded(trim('+2347026300404', '+'), 'Hi, ' . $recipient->firstname . ' just placed a trade of  ' . $event->amount . ' units. Powered by Ratefy.'));

            

            $this->makeWhatsappNotification(self::sendA, 'Hi, ' . $recipient->firstname . ' just placed a trade of  ' . $event->amount . ' units. Powered by Ratefy.');
            $this->makeWhatsappNotification(self::sendB, 'Hi, ' . $recipient->firstname . ' just placed a trade of  ' . $event->amount . ' units. Powered by Ratefy.');

            $user->notify(new TradeRequestMail($event->amount, $event->amountInNaira, $user, $recipient, $event->item_id, $event->wallet_name, $event->item));
        }else {
            Notification::route('sms', trim($user->mobile, '+'))->notify(new Traded(trim($user->mobile, '+'), 'Hi, ' . $recipient->firstname . ' just placed a trade of  ' . $event->amount . ' units. Powered by Ratefy.'));

            $this->makeWhatsappNotification(self::sendA, 'Hi, ' . $recipient->firstname . ' just placed a trade of  ' . $event->amount . ' units. Powered by Ratefy.');
            $this->makeWhatsappNotification(self::sendB, 'Hi, ' . $recipient->firstname . ' just placed a trade of  ' . $event->amount . ' units. Powered by Ratefy.');

            

            $user->notify(new TradeRequestMail($event->amount, $event->amountInNaira, $user, $recipient, $event->item_id, $event->wallet_name, $event->item));
        }  
    }


    public function makeWhatsappNotification($recipient, $message) {
        Log::info("I was here at sending notification::actual message whatsapp sent");
        app(WhatsappNotificationService::class)->sendNotification($recipient, $message);
    }

    
}