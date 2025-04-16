<?php

namespace App\Jobs;

use App\Http\Controllers\MessengerController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TradeRequestNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $wallet_name;
    public $wallet_id;
    public $item_for;
    public $item_id;
    public $amount;
    public $owner;
    public $recipient;
    public $status;
    public $duration;
    /**
     * Create a new job instance.
     */
    public function __construct($wallet_name, $wallet_id, $item_for, $item_id, $amount, $owner, $recipient, $status, $duration)
    {
        //
        $this->wallet_name = $wallet_name;
        $this->wallet_id = $wallet_id;
        $this->item_for = $item_for;
        $this->item_id = $item_id;
        $this->amount = $amount;
        $this->owner = $owner;
        $this->recipient = $recipient;
        $this->status = $status;
        $this->duration = $duration;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $messenger = app(MessengerController::class);
        $messenger->sendInitiatedTradeRequestNotification(
            owner: $this->owner,
            recipient: $this->recipient,
            amount: $this->amount,
            itemFor: $this->item_for,
            itemId: $this->item_id,
            walletName: $this->wallet_name
        );
    }
}
