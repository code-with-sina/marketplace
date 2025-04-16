<?php 


namespace App\Otp;

use Carbon\Carbon;
use App\Models\Otp;
use App\Models\User;
use App\Mail\SendOtp;
use App\Jobs\DestroyOtp;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OneTimePassword {
    public static function generatePassword() {
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        return $otpCode;
    }

    public static function checkAuthentication() {
        $user = Auth::user();
        if($user !== null) {
            return $user;
        }else {
            return false;
        }
    }

    public static function savePassword($user, $otp, $amount, $accountId) {
        $savedOtp = $user->otp()->create([
            'otp_code'      => $otp, 
            'expires_at'    => Carbon::now()->addMinutes(10),
            'amount'        => $amount,
            'accountId'     => $accountId, 
            'tally'         => uniqid(date('Y').time())
        ]);

        self::destroyedSavePassword($savedOtp->id);
        self::mailOtp(code: $savedOtp->otp_code, user: $user);
        return $savedOtp;
    }

    public static function destroyedSavePassword($otp) {
        DestroyOtp::dispatch($otp)->delay(Carbon::now()->addMinutes(10));
    }

    public static function determinant($count) {
        if($count->status == 'used') {
            return 'This OTP has been used';
        }elseif($count->status == 'destroyed') {
            return 'This OTP is expired';
        }else {
            return true;
        }
    }

    public static function mailOtp($code, $user) {
        Mail::to($user)->send(new SendOtp($code, $user->firstname));
    }

    public function getOtps() {
        return response()->json(Otp::all());
    }

    public static function initProcess($amount, $accountId) {
        $user = self::checkAuthentication();
        if($user !== false) {
            $user->otp()->where('status', 'waiting')->update(['status' => 'destroyed']);
            $otp = self::generatePassword();
            $tally = self::savePassword(otp: $otp, amount: $amount, accountId: $accountId, user: $user);
            return ["message" => "OTP has been sent to your mail box", "hash" => $tally->tally];
        }else {
            return "You are not authenticated";
        } 
    }

    public static function reProcess($tally) {
        $user = self::checkAuthentication();
        if($user !== false) {
            $pastTransaction = $user->otp()->where('tally', $tally)->first();
            $user->otp()->where('tally', $tally)->update(['status' => 'destroyed']);
            $otp = self::generatePassword();
            self::savePassword(otp: $otp, amount: $pastTransaction->amount, accountId: $pastTransaction->accountId, user: $user);
            return "OTP has been sent to your mail box";
        }else {
            return "You are not authenticated";
        }
    }

    public static function confirmPassword($otp) {
        $user = self::checkAuthentication();
        if($user !== false) {
           $count =  $user->otp()->where('otp_code', $otp)->first();
           if($count !== null) {
                $check = self::determinant(count: $count);
                if($check === true) {
                    $user->otp()->where('otp_code', $otp)->update(['status' => 'used']);
                    return ['status' => 200, 'message' => $count];
                }else{
                    return ['status' => 400, 'message' => $check];
                }
                
           }else {
                return ['status' => 400, 'message' => 'You have an invalid code'];
           }
            
        }else {
            return ['status' => 400, 'message' => 'You are not authenticated'];
        }
    }


    
}