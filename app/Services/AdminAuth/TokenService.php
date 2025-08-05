<?php

namespace App\Services\AdminAuth;

use App\Models\AdminAuth;
use App\Models\AdminToken;
use Illuminate\Support\Facades\Http;
use App\Services\AdminAuth\LoginService;




class TokenService {
    protected $admin;
    protected $token;
    public function createToken($id)
    {
        $otp = random_int(100000, 999999);
        $this->admin =  AdminAuth::where('id', $id)->first();
        $this->admin->admintoken()->create([
            'token' => $otp,
            'expires_at' => now()->addMinutes(10),
            'status'    => 'active'
        ]);

        $sent = $this->sendTokenSMS($otp);
        if(!$sent->status() == 200) 
        {
            return (object)["status"=> 400, "message"=> "There is an issue with sending OTP at the moment"];
        }
        
        return (object)["status" => 200, "message" => "Otp has been sent", "redirect_url" => "https://p2p.ratefy.co/admin/auth/otp"];
    }


    public function checkToken($token) 
    {
        
        $this->token = AdminToken::where('token', $token)->first();

        if (! $this->token) {
            return (object)[
                'status' => 400,
                'message' => 'Sorry, this token does not exist',
            ];
        }

        if ($this->token->expires_at < now()) {
            $this->token->update(['status' => 'used']);
            return (object)[
                'status' => 400,
                'message' => 'Sorry, this token has expired',
            ];
        }

        if ($this->token->status === 'used') {
            return (object)[
                'status' => 400,
                'message' => 'Sorry, this token has already been used',
            ];
        }

        if ($this->token->status === 'active') {
            $admin = AdminAuth::find($this->token->admin_auth_id);
            
            if (! $admin) {
                return (object)[
                    'status' => 404,
                    'message' => 'Admin account not found',
                ];
            }

            $response = app(LoginService::class)->loginFromToken($admin->email, $this->token);

            return (object)[
                'status' => $response->status,
                'message' => $response->message,
            ];
        }

        return (object)[
            'status' => 400,
            'message' => 'Invalid token status',
        ];
    }
    

    public function sendTokenSMS($otp) 
    {
        $sent = Http::post('https://api.ng.termii.com/api/sms/send', [
            'from'  => 'N-Alert',
            'to'    => '+234'.substr($this->admin->mobile, 1),
            'sms'   => 'Dear Ratefy Admin, your authentication code ' .$otp. '. Do not share',
            'type'  => 'plain',
            'channel' => 'dnd',
            'api_key'   => 'TLN6WXNS4VtM5n08puP15RPhsZhDRfyH64Ybi47mEkG5dFyQQ7DtCnYpk4eNk4',
        ]);

        return $sent;
    }
}