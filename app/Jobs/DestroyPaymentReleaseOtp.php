<?php

namespace App\Jobs;



use Illuminate\Bus\Queueable;
use App\Models\ReleasePaymentOtp;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DestroyPaymentReleaseOtp implements ShouldQueue
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
    [
        $destroy = ReleasePaymentOtp::find($this->otp);
        if($destroy->expires_at < Carbon::now() && $destroy->status !== 'used') {
            Otp::find($this->otp)->update(['status' => 'destroyed']);
        }
    ]
}
