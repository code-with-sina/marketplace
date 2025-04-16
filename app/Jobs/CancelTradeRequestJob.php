<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Events\CancelTradeRequestEvent;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CancelTradeRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;

    public $recipient;
    public $owner;
    public $wallet_name;
    public $item_id;
    public $amount;
    public $item;
    public $amountInNaira;



    /**
     * Create a new job instance.
     */
    public function __construct($recipient, $owner,  $amount, $amountInNaira, $item, $wallet_name, $item_id)
    {
        $this->recipient    = $recipient;
        $this->owner        = $owner;
        $this->amount       = $amount;
        $this->item         = $item;
        $this->wallet_name  = $wallet_name;
        $this->item_id      = $item_id;
        $this->amountInNaira = $amountInNaira;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        CancelTradeRequestEvent::dispatch($this->owner, $this->recipient, $this->amount, $this->amountInNaira, $this->item, $this->wallet_name, $this->item_id);
    }
}
