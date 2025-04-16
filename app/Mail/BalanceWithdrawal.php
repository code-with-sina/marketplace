<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BalanceWithdrawal extends Mailable
{
    use Queueable, SerializesModels;
    public $amount;
    public $firstname;
    /**
     * Create a new message instance.
     */
    public function __construct($amount, $firstname)
    {
        $this->amount = $amount;
        $this->firstname = $firstname;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address("no-reply@p2p.ratefy.co", "Ratefy"),
            subject: 'Your money is on its way🥳',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.account.balancewithdrawal',
            with: [
                'amount'    => $this->amount,
                'firstname' => $this->firstname
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
