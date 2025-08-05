<?php

namespace App\Mail;

use App\Models\OnboardingLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OnboardingNotifier extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public OnboardingLog $log){}

    public function build() 
    {
        return $this->subject('{Wallet Creation Status}: {$this->log->action}')->view("email.wallet-notify-log");
    }
}
