<?php 

namespace App\Services\AdminAuth;

use App\Models\AdminAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;

class ResetPasswordService
{
    public function resetPassword($email, $password, $token) 
    {
        $status = Password::broker('admin')->reset(
            ["email" => $email,"password"=> $password,"token"=> $token], 
            function($admin) use ($password) {
                $admin->password = Hash::make($password);
                $admin->setRememberToken(Str::random(60));
                $admin->save();

                event(new PasswordReset($admin));
            }
        );

        return (object)[
            "message" => $status === Password::PASSWORD_RESET ? "Password has been reset successfully." : "Failed to reset password.", 
            "status"=> $status === Password::PASSWORD_RESET ? 200 : 400
        ];
    }
}