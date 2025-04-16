<?php

namespace App\UserFacades;

use App\Models\User;
use Illuminate\Support\Carbon;
trait HasActivityLog 
{
    protected User $user;
    protected $device;
    protected $ip;

    public function getData($uuid, $device, $ip) 
    {
        $this->user = $uuid;
        $this->device = $device;
        $this->ip = $ip;
        return $this;
    }

    public function updateActivity() 
    {
        $this->user->activity()->create([
            'last_login'      => Carbon::now(),
            'ip_address'      => $this->ip,
            'device'          => $this->device
        ]);
    }
}