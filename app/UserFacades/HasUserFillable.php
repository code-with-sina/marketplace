<?php

namespace App\UserFacades;

use App\Models\User;
use Illuminate\Support\Carbon;


trait HasUserFillable
{
    protected User $userUuid;
    protected $ip;
    protected $device;
    public function setUser($uuid, $ip, $device)
    {
        $this->userUuid = $uuid;
        $this->ip = $ip;
        $this->device = $device;

        return $this;
    }

    public function processCreate()
    {
        $this->userUuid->authorization()->create([
            'priviledge'    => 'blocked',
            'email'         => 'unverified',
            'type'          => 'none',
            'profile'       => 'no_profile'
        ]);
        $this->userUuid->activity()->create([
            'last_login'      => Carbon::now(),
            'ip_address'      => $this->ip,
            'device'          => $this->device
        ]);
        $this->userUuid->tag()->create([
            'avatar_name'   => 'null',
            'avatar_color'  => 'null',
            'avatar_image'  => 'null',
            'alias'         => 'null',
            'pen_name'      => 'null',
            'avatar_color'  => 'null',
        ]);
    }
}
