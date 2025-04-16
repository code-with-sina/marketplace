<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TradeCompletionSuccess extends Mailable
{
    use Queueable, SerializesModels;

    public $amount;
    public $user;
    public $recipient;
    public $item_id;
    public $wallet_name;
    public $item;
    public  $amount_to_receive;
    // public $amountInNaira;
    // public $recipientname;
    /**
     * Create a new message instance.
     */
    public function __construct($amount, $user, $recipient, $item_id, $wallet_name, $item,  $amount_to_receive)
    {
        // $this->recipientname = $recipientname;

        $this->amount       =   $amount;
        $this->user         =   $user;
        $this->recipient    =   $recipient;
        $this->item_id      =   $item_id;
        $this->wallet_name  =   $wallet_name;
        $this->item         =   $item;
        $this->amount_to_receive =  $amount_to_receive;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address("no-reply@p2p.ratefy.co", "Ratefy"),
            subject: 'Trade Completion Success',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.trades.tradecompletionsuccess',
            with: [
                'amount'        => $this->amount,
                'user'          => $this->user,
                'recipient'     => $this->recipient,
                'item_id'       => $this->item_id,
                'wallet_name'   => $this->wallet_name,
                'item'          => $this->item,
                'amount_to_receive' => $this->amount_to_receive

            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
