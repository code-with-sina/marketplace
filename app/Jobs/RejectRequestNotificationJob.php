<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RejectRequestNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $owner;
    public $id;
    /**
     * Create a new job instance.
     */
    public function __construct($owner, $id)
    {
        //
        $this->owner = $owner;
        $this->id = $id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        TradeRequest::where('id', $this->id)
                        ->where('owner', $this->owner)
                        ->update(['status' => 'rejected']);

        $amount = TradeRequest::where('id', $this->id)->where('owner', $this->owner)->first();
        Http::post('https://p2p.ratefy.co/api/reject-trade-request', [
            // 'item'  => $amount->id,
            // 'uuid'  => $this->owner,
            // 'amount' => $amount->amount,
            "owner"         =>  $amount->owner,
            "recipient"     =>  $amount->recipient,
            "amount"        =>  $amount->amount,
            'item'          =>  $amount->item_for,
            'item_id'       =>  $amount->item_id,
            'wallet_name'   =>  $unique->wallet_name
        ]);
    }
}
