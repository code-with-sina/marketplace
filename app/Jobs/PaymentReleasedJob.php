<?php

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use App\Events\PaymentReleasedEvent;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PaymentReleasedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $owner;
    public $recipient;
    public $amount;
    public $item;
    public $wallet_name;
    public $item_id;
    public $amount_to_receive;
    /**
     * Create a new job instance.
     */
    public function __construct($owner, $recipient, $amount, $item, $wallet_name, $item_id, $amount_to_receive)
    {
        $this->owner = $owner;
        $this->recipient = $recipient;
        $this->amount = $amount;
        $this->item = $item;
        $this->wallet_name = $wallet_name;
        $this->item_id = $item_id;
        $this->amount_to_receive = $amount_to_receive;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        PaymentReleasedEvent::dispatch($this->owner, $this->recipient, $this->amount, $this->item, $this->wallet_name, $this->item_id, $this->amount_to_receive);
    }
}
