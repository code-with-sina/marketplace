<?php

namespace App\Notifications;

use App\Models\Rate;
use App\Models\BuyerOffer;
use App\Models\SellerOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class RejectRequestMail extends Notification
{
    use Queueable;
    public $amount;
    public $item;
    public $user;
    public $recipient;
    public $item_id;
    public $wallet_name;
    public $amountInNaira;

    /**
     * Create a new notification instance.
     */
    public function __construct($amount, $amountInNaira, $user,  $recipient, $item_id, $wallet_name, $item)
    {
        $this->amount       = $amount;
        $this->user         = $user;
        $this->recipient    = $recipient;
        $this->item_id      = $item_id;
        $this->wallet_name  = $wallet_name;
        $this->item         = $item;
        $this->amountInNaira = $amountInNaira;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        if ($this->item == "sell") {
            $amountToSend = Rate::latest()->first();
            $amountInNaira = (int)$amountToSend->rate_normal * (int)$this->amount;
            $paymentOption = $this->getSellerItemOption($this->item_id);

            return (new MailMessage)
                ->subject('Trade Request Rejected')
                ->markdown('mail.trades.rejecttraderequest', ['amount' => $this->amount, 'user' => $this->user, 'recipient' => $this->recipient,  'wallet' => $this->wallet_name, 'amountInNaira' => $this->amountInNaira, 'paymentOptions' => $paymentOption->paymentoption->option]);
        } else {
            $amountToSend = Rate::latest()->first();
            $amountInNaira = (int)$amountToSend->rate_normal * (int)$this->amount;
            $paymentOption = $this->getBuyerItemOption($this->item_id);

            return (new MailMessage)
                ->subject('Trade Request Rejected')
                ->markdown('mail.trades.rejecttraderequest', ['amount' => $this->amount, 'user' => $this->user, 'recipient' => $this->recipient,  'wallet' => $this->wallet_name, 'amountInNaira' => $this->amountInNaira, 'paymentOptions' => $paymentOption->paymentoption->option]);
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Hi there, a trade you placed has been rejected, worth about ' . $this->amount . '. <br> kindly check.',
            'amount'  =>  $this->amount
            // 'item'  => $this->item 
        ];
    }

    public function getSellerItemOption($id)
    {
        $data = BuyerOffer::where('id', $id)->where('status', 'active')->first();
        return $data;
    }


    public function getBuyerItemOption($id)
    {
        $data = SellerOffer::where('id', $id)->where('status', 'active')->first();
        return $data;
    }
}
