<?php

namespace App\Http\Controllers;

use App\Enums\AdminRole;
use Illuminate\Http\Request;
use App\Enums\RestrictedEmail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Services\AdminAuth\LoginService;
use App\Services\AdminAuth\TokenService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\AdminAuth\ResetLinkService;
use App\Services\AdminAuth\RegistrationService;
use App\Services\AdminAuth\ResetPasswordService;
use App\Services\AdminAuth\ChangePasswordService;


class AdminAuthController extends Controller
{
    public function register(Request $request) 
    {
        $request->merge([
            'email' => strtolower($request->input('email')),
        ]);
        
        $validation = Validator::make($request->all(), [
            "firstname" => 'required|string|max:255|min:3',
            "lastname"  => 'required|string|max:255|min:3',
            "email"     => ['required', 'email', 'unique:admin_auths', Rule::enum(RestrictedEmail::class)],
            'mobile'    => 'required|string|min:11|max:14|unique:admin_auths,mobile',
            'password'  => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        if($validation->fails()) 
        {
            return response()->json($validation->errors(),400);
        }

        $response = app(RegistrationService::class)
        ->registerFromSuperAdmin(
            firstname: $request->firstname, 
            lastname: $request->lastname,
            email: $request->email,
            password: $request->password,
            mobile: $request->mobile
        );

        return response()->json([$response], 200);
    }

    public function createAdmin(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "firstname" => 'required|string|max:255|min:3',
            "lastname"  => 'required|string|max:255|min:3',
            "email"     => 'required|email|unique:admin_auths',
            'mobile'    => 'required|string|min:11|max:14|unique:admin_auths,mobile',
            'role'      => ['required',Rule::enum(AdminRole::class)]
        ]);

        if($validation->fails()) 
        {
            return response()->json($validation->errors(),400);
        }

        $admin = Auth::guard()->user();

        if($admin !== null && $admin->isHigherAccess()) {
                $response = app(RegistrationService::class)->registerFromAdmin(
                firstname: $request->firstname, 
                lastname: $request->lastname,
                email: $request->email,
                mobile: $request->mobile,
                role: $request->role
            );

            return response()->json($response, 200);
        }else {
            return response()->json(["message" => "You are unauthorized to take this action", "status" => 422], 422);
        }
        
    }


    public function login(Request $request) 
    {
        $key = 'otp_attempts:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'status' => 429,
                'message' => 'Too many attempts. Please try again later.',
            ]);
        }

        RateLimiter::hit($key, 60); 
        $validation = Validator::make($request->all(), [
            "email"     => 'required|email',
            'password'  => 'required|string', 
        ]);

        if($validation->fails())
            return response()->json($validation->errors(),400);


        $response = app(LoginService::class)->adminLogin($request->email, $request->password);
        return response()->json($response, $response->status);

    }


    public function otpAuth(Request $request)
    {
        $key = 'otp_attempts:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'status' => 429,
                'message' => 'Too many attempts. Please try again later.',
            ]);
        }
        RateLimiter::hit($key, 1800); 

        $response = app(TokenService::class)->checkToken($request->otp);
        return response()->json($response, $response->status);
    }


    public function forgotPassword(Request $request) 
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if($validation->fails()) 
        {
            return response()->json($validation->errors(),400);
        }

        $response = app(ResetLinkService::class)->processResetLink($request->email);
        return response()->json($response, $response->status);
    }


    public function resetPassword(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "email"     => 'required|email',
            "token"     => 'required|string',
            "password"  => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()]
        ]);

        if($validation->fails())
            return response()->json($validation->errors(),400);


        $response = app(ResetPasswordService::class)
        ->resetPassword($request->email, $request->password, $request->token);

        return response()->json($response, $response->status);
    }

    public function changePassword(Request $request) 
    {
        $validation = Validator::make($request->all(), [
            'current_password'=> 'required|string',
            'password'=> ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()]
        ]);

        if($validation->fails())
        {
            return response()->json($validation->errors(),400);
        }


        $response = app(ChangePasswordService::class)
                    ->processChangePassword($request->current_password, $request->password);
        return response()->json($response, $response->status);
    }



}
