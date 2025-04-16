<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNotify extends Mailable
{
    use Queueable, SerializesModels;
    public $content;
    public $direction;
    public $fromUser;
    /**
     * Create a new message instance.
     */
    public function __construct($direction, $content, $fromUser)
    {
        $this->content      = $content;
        $this->direction    = $direction;
        $this->fromUser     = $fromUser;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address("no-reply@p2p.ratefy.co", "Ratefy"),
            subject: 'A ' .$this->direction.' trade has been created' 

        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.admin.notify',
            with: [
                'content'       => $this->content,
                'direction'     => $this->direction,
                'fromUser'      => $this->fromUser

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
