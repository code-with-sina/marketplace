<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class WalletCreationNotification extends Notification
{
    use Queueable;
    public $message;
    public $notificationType;

    /**
     * Create a new notification instance.
     */
    public function __construct($message, $notificationType)
    {
        $this->message = $message;
        $this->notificationType = $notificationType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'data' => $this->message,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toBroadcast($notifiable)
    {
        return (new BroadcastMessage([
            'data' => $this->message,
        ]))->onConnection('database')->onQueue('broadcasts');
    }

    public function databaseType(object $notifiable): string
    {
        return $notificationType;
    }
}
