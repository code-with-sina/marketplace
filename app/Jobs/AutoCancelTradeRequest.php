<?php

namespace App\Jobs;



use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\TradeRequest;
use App\Services\RejectTradeService;

class AutoCancelTradeRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;
    /**
     * Create a new job instance.
     */
    public function __construct($id, $owner)
    {
        $this->data = ['id' => $id, 'owner' => $owner];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $verify = TradeRequest::where('id', $this->data['id'])->whereIn('status', ['rejected', 'cancelled', 'accepted'])->exists();
        if (!$verify) {
            app(RejectTradeService::class)
                ->validate($this->data)
                ->validateTradeExists()
                ->ReverseDebit()
                ->updateTrade()
                ->informStakeholder()
                ->throwState();
        }
    }
}
