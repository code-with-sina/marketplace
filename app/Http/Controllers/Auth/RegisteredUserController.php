<?php

namespace App\Http\Controllers\Auth;


use App\Models\User;
use App\Models\NetAuthToken;
use App\Jobs\RegistrationJob;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\Signup;
use App\Mail\Verification;
use App\Notifications\Verification as NotificationsVerification;
use App\UserFacades\HasRegistrationValidation;
use Illuminate\Support\Carbon;

use App\UserFacades\HasUserFillable;

class RegisteredUserController extends Controller
{
    use HasUserFillable, HasRegistrationValidation;
    public $domains = ['express' => "ratefy.co/", 'blog'  => "blog.ratefy.co/", 'forum'  => "forum.ratefy.co/"];
    public $subdomain;
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): Response
    {
        if ($request->header('User-Agents') !== 'Ratefy') {
            return response(['message' => 'bad header'], 400);
        } else {
            $validationParams = [
                'firstname'              => $request->firstname ?? null,
                'lastname'               => $request->lastname ?? null,
                'username'               => $request->username ?? null,
                'email'                  => $request->email ?? null,
                'mobile'                 => '+234' . mb_substr($request->mobile, 1) ?? null,
                'password'               => $request->password ?? null,
                'password_confirmation'  => $request->password_confirmation ?? null,
                'ip'                     => $request->ip ?? null,
                'device'                 => $request->device ?? null
            ];

            $data = $this->params(parameter: $validationParams)->validateUser();

            if ($data['status'] == '422') {
                return response(['message' => $data['data']], 422);
            } else {


                $user = User::create([
                    'firstname'     => $data['data']->firstname,
                    'lastname'      => $data['data']->lastname,
                    'username'      => $data['data']->username,
                    'email'         => $data['data']->email,
                    'mobile'        => $data['data']->mobile,
                    'uuid'          => Str::uuid(),
                    'password'      => Hash::make($data['data']->password),
                    'circulated'    => 'both'
                ]);

                $state = $this->broadcastUserToExpress(user: $user);
                if ($state === 200) {
                    $this->setUser(uuid: $user, ip: $data['data']->ip, device: $data['data']->device)->processCreate();

                    Auth::login($user);
                    $token = Auth::user()->createToken(auth()->user()->username)->plainTextToken;
                    Mail::to($user)->send(new Signup($user->firstname));
                    event(new Registered($user));

                    if (!empty($request->input('subdomain'))) {
                        if (array_key_exists($request->input('subdomain'), $this->domains)) {
                            $this->subdomain = $this->domains[$request->input('subdomain')];
                            $tokes = $this->broadcastAuthenticatedUser(token: $token, userId: auth()->user()->id, fromDomain: $request->getHttpHost());
                            if ($tokes === null) {
                                return response([
                                    'status'    => 200,
                                    'subdomain' => null,
                                    'token' => $token,
                                    "user" => auth()->user()->uuid
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
                        return response(['data' => $user, 'subdomain' => null, 'token' => $token], 200);
                    }
                }
            }
        }
    }


    public function broadcastUserToExpress($user)
    {
        $check = Http::post('https://ratefy.co/api/user-reg-api', $this->convertToExpressStandard(user: $user));
        if ($check->status() === 200) {
            return 200;
        }
    }


    public function convertToExpressStandard($user)
    {
        return  [
            'name'          => $user->firstname . ' ' . $user->lastname,
            'username'      => $user->username,
            'email'         => $user->email,
            'mobile_number' => $user->mobile,
            'uuid'          => $user->uuid,
            'password'      => $user->password,
            'emailCode'     => md5(Hash::make($user->username))
        ];
    }

    public function broadcastAuthenticatedUser($token, $userId, $fromDomain)
    {

        $checkUser = User::find(auth()->user()->id);
        if ($checkUser->exp_id !== null) {
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
                'user_id' => $checkUser->exp_id,
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
