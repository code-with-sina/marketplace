<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Otp;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;



class DestroyOtp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $otp;
    /**
     * Create a new job instance.
     */
    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->isExpired();
    }


    public function isExpired()
    {
        $destroy = Otp::find($this->otp);
        if($destroy->expires_at < Carbon::now() && $destroy->status !== 'used') {
            Otp::find($this->otp)->update(['status' => 'destroyed']);
        }
    }
}
