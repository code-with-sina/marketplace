<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProfileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;
    public $payload;

    /**
     * Create a new job instance.
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Http::post('https://profilebased.ratefy.co/api/create-profile', [
            "uuid"      =>  $this->payload['uuid'],
            "sex"       =>  $this->payload['sex'],
            "dob"       =>  $this->payload['dob'],
            "address"   =>  $this->payload['address'],
            "city"      =>  $this->payload['city'],
            "state"     =>  $this->payload['state'],
            "country"   =>  $this->payload['country'],
            "zip_code"  =>  $this->payload['zip_code']
        ]);
    }
}
