<?php

namespace App\Services\AdminAuth;


use App\Models\AdminAuth;
use Illuminate\Support\Facades\Password;
class ResetLinkService 
{
    public function processResetLink($email)
    {
        $adminEmail = AdminAuth::where("email", $email)->first();
        if(!$adminEmail) {
            return (object)["message" => "Sorry, we do not have this admin email with us", "status" => 400];
        }

        $status = Password::broker('admin')->sendResetLink(["email" => $email]);
        return (object)[
            "message" => $status === Password::RESET_LINK_SENT ? "Password reset link sent to your email." : "Unable to send reset link.",
            "status" => $status === Password::RESET_LINK_SENT ? 200 : 400
            ];
    }
}