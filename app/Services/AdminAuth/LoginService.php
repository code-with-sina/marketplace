<?php 

namespace App\Services\AdminAuth;

use App\Models\AdminAuth;
use Illuminate\Support\Facades\Hash;



class LoginService {
    public function adminLogin($email, $password)
    {
        $admin = AdminAuth::where('email', $email)->first();

        if (! $admin) {
            return (object)[
                'status' => 404,
                'message' => 'This admin does not exist',
            ];
        }

        if($admin->isSuperadmin()  && $admin->isHigherAccess()) {
            if (! Hash::check($password, $admin->password)) {
            return (object)[
                    'status' => 401,
                    'message' => 'Invalid credentials',
                ];
            }
            $response = app(TokenService::class)->createToken($admin->id);
            return (object)["message" => $response, "status" => $response->status];
        }else {
            if (! Hash::check($password, $admin->password)) {
            return (object)[
                    'status' => 401,
                    'message' => 'Invalid credentials',
                ];
            }

            $admin->tokens()->delete();
            $token = $admin->createToken('admin_auth_token')->plainTextToken;

            return (object)[
                'status' => 200,
                'message' => 'Login successful',
                'token' => $token,
                'admin' => [
                    'id' => $admin->id,
                    'firstname' => $admin->firstname,
                    'lastname' => $admin->lastname,
                    'email' => $admin->email,
                    'role' => $admin->role,
                    'access' => $admin->access,
                ],
            ];
        }

        
    }


    public function loginFromToken($email, $apiToken) 
    {
        if (! $apiToken || $apiToken->status !== 'active' || $apiToken->expires_at < now()) {
            return (object)[
                'status' => 401,
                'message' => 'Token invalid or expired',
            ];
        }

        $admin = AdminAuth::where('email', $email)->first();
        
        $admin->tokens()->delete();
        $token = $admin->createToken('admin_auth_token')->plainTextToken;

        $apiToken->update(['status' => "used"]);
        return (object)[
            'status' => 200,
            'message' =>[
                'message' => 'Login successful',
                'token' => $token,
                'admin' => [
                    'id' => $admin->id,
                    'firstname' => $admin->firstname,
                    'lastname' => $admin->lastname,
                    'email' => $admin->email,
                    'role' => $admin->role,
                    'access' => $admin->access,
                ],
            ],
            
        ];
    }
}