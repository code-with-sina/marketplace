<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WalletCreationState extends Mailable
{
    use Queueable, SerializesModels;

    protected $content;


    /**
     * Create a new message instance.
     */
    public function __construct($content)
    {
        $this->content = $content;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address("no-reply@p2p.ratefy.co", "Ratefy"),
            subject: 'Wallet Creation Status',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admins.walletcreationstatus',
            with: [
                'name'      => $this->content['name'],
                'content'   => $this->content['message']
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
