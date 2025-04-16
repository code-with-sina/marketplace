<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use App\Notifications\WalletCreationNotification;

class WalletNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $uuid;
    public $message;
    public $notificationType;

    /**
     * Create a new job instance.
     */
    public function __construct($message, $uuid, $notificationType)
    {
        $this->uuid = $uuid;
        $this->message = $message;
        $this->notificationType = $notificationType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::where('uuid', $this->uuid)->first();
        $user->notify(new WalletCreationNotification($this->message));
    }
}
