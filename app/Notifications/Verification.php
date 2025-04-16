<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Notification;

class Verification extends VerifyEmail 
{
    use Queueable;
    public $prefix;
    public $name;
    /**
     * Create a new notification instance.
     */
    public function __construct($name)
    {
        $this->prefix = 'https://your-spa-url-here.com/verify-email?url=';
        $this->name = $name;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        
        $verificationUrl = $this->verificationUrl($notifiable);
        return (new MailMessage)->subject('Verify your account with us')->markdown('mail.auth.verification', ['url' => $this->prefix.urlencode($verificationUrl), 'name' => $this->name]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
