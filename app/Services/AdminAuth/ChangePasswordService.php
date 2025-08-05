<?php

namespace App\Services\AdminAuth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class ChangePasswordService
{

    public function processChangePassword($currentPassword, $password) 
    {
        $admin = Auth::guard()->user();
        if(!Hash::check( $currentPassword, $admin->password)){
            return (object)["message" => "Current password is incorrect.", "status" => 422];
        }

        $admin->password = Hash::make($password);
        $admin->save();

        return (object)["message" => "Password changed successfully.", "status" => 200];
    }

}