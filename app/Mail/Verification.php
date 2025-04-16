<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Verification extends Mailable 
{
    use Queueable, SerializesModels;
    public  $name;
    public $url;
    public $prefix;



    /**
     * Create a new message instance.
     */
    public function __construct($firstname)
    {
        $this->name     = $firstname;
        $this->prefix   = 'https://market.ratefy.co/verify-email?url=';

    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address("no-reply@p2p.ratefy.co", "Ratefy"),
            subject: 'Verification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.auth.verifymail',
            with: [
                'name'              => $this->name,
                'link'              => $this->prefix.$this->verificationUrl()
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
