<?php

namespace App\Services\AdminAuth;


use App\Enums\AdminRole;
use App\Models\AdminAuth;
use App\Enums\AdminAccess;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

use App\Mail\AdminAccountCreatedMailable;
use App\Notifications\AdminAuthNotification;
use Illuminate\Support\Facades\Notification; 

class RegistrationService 
{
    public function registerFromSuperAdmin($firstname, $lastname, $email, $password, $mobile)
    {
        $user = AdminAuth::create([
            'firstname'     => $firstname,
            'lastname'      => $lastname,
            'email'         => $email,
            'mobile'        => $mobile,
            'uuid'          => Str::uuid(),
            'password'      => Hash::make($password),
            'role'          => AdminRole::SuperAdmin,
            'access'        => AdminAccess::High,
        ]);

        Notification::route('mail', $email)->notify(new AdminAuthNotification($password, $email));

        return $user;
    }

    public function registerFromAdmin($firstname, $lastname, $email, $mobile, $role) 
    {
        $symbols = '!@#$%^&*()_+-=<>?';
        $random = Str::random(10) . substr(str_shuffle($symbols), 0, 10);
        $password = str_shuffle($random); 


        $user = AdminAuth::create([
            'firstname'     => $firstname,
            'lastname'      => $lastname,
            'email'         => $email,
            'mobile'        => $mobile,
            'uuid'          => Str::uuid(),
            'password'      => Hash::make($password),
            'role'          => $role, 
            "access"        => AdminAccess::Low,
        ]);

        Notification::route('mail', $email)->notify(new AdminAuthNotification($password, $email));
        return $user;
    }
}