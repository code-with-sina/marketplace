<?php

namespace App\Http\Controllers\Auth;


use Illuminate\Support\Str;
use App\Models\NetAuthToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\UserFacades\HasActivityLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;


class AuthenticatedSessionController extends Controller
{
    use HasActivityLog;
    public $domains = ['express' => "ratefy.co/", 'blog'  => "blog.ratefy.co/", 'forum'  => "forum.ratefy.co/"];
    public $subdomain;
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): Response
    {
        $request->authenticate();
        $request->session()->regenerate();
        $token =  auth()->user()->createToken(auth()->user()->username)->plainTextToken;
        $this->getData(uuid: auth()->user(), ip: $request->ip, device: $request->device)->updateActivity();

        if (!empty($request->input('subdomain'))) {
            if (array_key_exists($request->input('subdomain'), $this->domains)) {
                $this->subdomain = $this->domains[$request->input('subdomain')];
                $tokes = $this->broadcastAuthenticatedUser(token: $token, userId: auth()->user()->id, fromDomain: $request->getHttpHost());
                if ($tokes === null) {
                    return response([
                        'status'    => 200,
                        'subdomain' => null,
                        'token' => $token
                    ]);
                } else {
                    return response([
                        'status'    => 200,
                        'subdomain' => 'https://' . $this->subdomain . 'users/auth-test/' . $tokes,
                        'token' => $token
                    ]);
                }
            }
        } else {
            return response([
                'status'    => 200,
                'subdomain' => null,
                'token' => $token
            ]);
        }


        
    }



    // public function store(LoginRequest $request)
    // {
    //     $request->authenticate();
    //     $request->session()->regenerate();
    //     $token =  auth()->user()->createToken(auth()->user()->username)->plainTextToken;
    //     $this->getData(uuid: auth()->user(), ip: $request->ip, device: $request->device)->updateActivity();

    //     if (!empty($request->input('subdomain'))) {
    //         if (array_key_exists($request->input('subdomain'), $this->domains)) {
    //             $this->subdomain = $this->domains[$request->input('subdomain')];
    //             $tokes = $this->broadcastAuthenticatedUser(token: $token, userId: auth()->user()->id, fromDomain: $request->getHttpHost());
    //             if ($tokes === null) {
    //                 return response()->json([
    //                     'status'    => 200,
    //                     'subdomain' => null,
    //                     'token' => $token
    //                 ]);
    //             } else {
    //                 return response()->json([
    //                     'status'    => 200,
    //                     'subdomain' => 'https://' . $this->subdomain . 'users/auth-test/' . $tokes,
    //                     'token' => $token
    //                 ]);
    //             }
    //         }
    //     } else {
    //         return response()->json([
    //             'status'    => 200,
    //             'subdomain' => null,
    //             'token' => $token
    //         ]);
    //     }
    // }

    

    /**
     * Destroy an authenticated session.
     */
    // public function destroy(Request $request): Response
    // {
    //     Auth::guard('web')->logout();

    //     $request->session()->invalidate();

    //     $request->session()->regenerateToken();

    //     return response()->noContent();
    // }


    public function destroy(Request $request)
    {
        Http::post('https://ratefy.co/api/user-logout-api', [
            'uuid' => auth()->user()->uuid,
        ]);

        Auth::guard('web')->logout();


        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function broadcastAuthenticatedUser($token, $userId, $fromDomain)
    {
        if (auth()->user()->exp_id !== null) {
            $encrypted = $this->makeCrypt(token: $token);

            $broadcastAuthentication = new NetAuthToken();
            $broadcastAuthentication->create([
                'user_id' => $userId,
                'token' => $token,
                'username' => auth()->user()->username,
                'by_subdomain' => $fromDomain,
                'expires_at' => now()->addMinutes(5)
            ]);


            Http::post('https://ratefy.co/api/authenticating', [
                'token' => $encrypted['token'],
                'grant_pass' => $encrypted['grant_pass'],
                'user_id' => auth()->user()->exp_id,
                'from_subdomain' => $fromDomain
            ]);

            return $encrypted['token'];
        } else {
            return null;
        }
    }

    public function makeCrypt($token)
    {
        $password = Str::password(16, true, true, false, false);
        $method = 'AES-256-CBC';
        $key = hash('sha256', $password, true);
        $iv = random_bytes(openssl_cipher_iv_length($method));
        $encrypted = openssl_encrypt($token, $method, $key, 0, $iv);
        $encryptedData = base64_encode($iv . $encrypted);
        $urlSafeToken = strtr($encryptedData, ['+' => '-', '/' => '_', '=' => '']);
        return ['token' => $urlSafeToken, 'grant_pass' => $password];
    }
}
