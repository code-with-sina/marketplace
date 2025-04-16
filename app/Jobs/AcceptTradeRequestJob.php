<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Events\AcceptedTradeRequestEvent;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AcceptTradeRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $owner;
    public $recipient;
    public $amount;
    public $item;
    public $item_id;
    public $wallet_name;
    public $acceptance_id;
    public $session_id;
    public $amountInNaira;

    public $tries = 3;
    /**
     * Create a new job instance.
     */
    public function __construct($owner, $recipient, $amount, $amountInNaira, $item, $item_id, $wallet_name, $acceptance_id, $session_id)
    {
        //
        $this->owner            =   $owner;
        $this->recipient        =   $recipient;
        $this->amount           =   $amount;
        $this->item             =   $item;
        $this->item_id          =   $item_id;
        $this->wallet_name      =   $wallet_name;
        $this->acceptance_id    =   $acceptance_id;
        $this->session_id       =   $session_id;
        $this->amountInNaira    =   $amountInNaira;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        AcceptedTradeRequestEvent::dispatch($this->owner, $this->recipient,  $this->amount, $this->amountInNaira, $this->item, $this->item_id, $this->wallet_name, $this->acceptance_id, $this->session_id);
    }
}
