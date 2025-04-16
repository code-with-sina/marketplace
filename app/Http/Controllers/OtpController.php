<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use App\Models\Otp;
use App\Models\User;
use App\Mail\SendOtp;
use App\Jobs\DestroyOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    public function generatePassowrd() {
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        return $otpCode;
    }

    public function checkAuthentication() {
        $user = auth()->user()->uuid;
        if($user !== null) {
            return $user;
        }else {
            return false;
        }
    }

    public function savePassword($otp, $user) {
        $savedOtp = $user->otp()->create([
            'otp_code'      => $otp, 
            'expires_at'    => Carbon::now()->addMinutes(4)
        ]);
        $this->destroyedSavePassword($savedOtp->id);
        $this->mailOtp(code: $savedOtp->otp_code, user: $user);
        return $savedOtp;
    }


    public function destroyedSavePassword($otp) {
        DestroyOtp::dispatch($otp)->delay(Carbon::now()->addMinutes(5));
    }

    
    public function determinant($count) {
        if($count->status == 'used') {
            return 'This OTP has been used';
        }elseif($count->status == 'destroyed') {
            return 'This OTP is expired';
        }else {
            return true;
        }
    }

    public function mailOtp($code, $user) {
        Mail::to($user)->send(new SendOtp($code, $user->firstname));
    }

    public function initProcess(Request $request) {
        $user = $this->checkAuthentication(uuid: $request->uuid);
        if($user !== false) {
            $otp = $this->generatePassowrd();
            $daas = $this->savePassword(otp: $otp, user: $user);
            return response()->json($daas);
        }else {
            return response()->json('You are not authenticated');
        }
        
    }

    public function confirmPassword(Request $request) {
        $user = $this->checkAuthentication(uuid: $request->uuid);
        if($user !== false) {
           $count =  $user->otp()->where('otp_code', $request->otp)->first();
           if($count !== null) {
                $check = $this->determinant(count: $count);
                if($check === true) {
                    $user->otp()->where('otp_code', $request->otp)->update(['status' => 'used']);
                }else{
                    return response()->json([$check]);
                }
                
           }else {
                return response()->json('You have an invalid code');
           }
            
        }else {
            return response()->json('You are not authenticated');
        }
    }


    public function getOtps() {
        return response()->json(Otp::all());
    }


}
