<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class RegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public User $user;
    public $ip;
    public $device;
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct($user, $ip, $device)
    {
        $this->user     = $user;
        $this->ip       = $ip;
        $this->device   = $device;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Http::post('https://userbased.ratefy.co/api/create', [
            'uuid'          => $this->user->uuid,
            'email'         => $this->user->email,
            'username'      => $this->user->username,
            'firstname'     => $this->user->firstname,
            'lastname'      => $this->user->lastname,
            'phonenumber'   => $this->user->mobile,
            'ip'            => $this->ip,
            'last_login'    => Carbon::now(),
            'device'        => $this->device
        ]);
    }
}
