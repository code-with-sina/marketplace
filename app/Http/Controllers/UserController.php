<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\NetAuthToken;
use App\Prop\FeeDeterminantAid;
use App\UserFacades\HasActivityLog;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http as OnlineAjax;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use HasActivityLog;
    public function singleUser()
    {
        $user = User::find(auth()->user()->id);
        return response()->json([
            'data'      => $user->load(['authorization', 'lastactivity', 'tag', 'miniprofile']),
        ], 200);
    }


    public function updateUser(Request $request)
    {
        $request->validate([
            'lastname'        => ['required', 'string'],
            'firstname'       => ['required', 'string'],
        ]);
        $user = User::find(auth()->user()->id);
        $user->update([
            'firstname' => $request->firstname,
            'lastname'  => $request->lastname
        ]);
        return response()->json([
            'message'      => 'updated successfully',
            'status'       => 200
        ], 200);
    }

    public function updateActivitySingle(Request $request)
    {
        $upsert = User::find(auth()->user()->id);
        $this->getData(uuid: $upsert, ip: $request->ip, device: $request->device)->updateActivity();
    }

    public function updateAuthorizationSingle(Request $request)
    {
        $upsert = User::find(auth()->user()->id);
        $upsert->authorization()->update([$request->single_column => $request->data]);
        return response()->json([
            'data'      => $upsert->load(['authorization', 'activity', 'tag', 'miniprofile']),
        ], 200);
    }

    public function updateAuthorizationKyc(Request $request)
    {
        $upsert = User::find(auth()->user()->id);
        $upsert->authorization()->update([$request->single_column => $request->data]);
        return response()->json([
            'data'      => $upsert->load(['authorization', 'activity', 'tag', 'miniprofile']),
        ], 200);
    }

    public function updateAuthorizationMultiple(Request $request)
    {
        $upsert = User::find(auth()->user()->id);
        $upsert->authorization()->update($request->multi_column);
        return response()->json([
            'data'      => $upsert->load(['authorization', 'activity', 'tag', 'miniprofile']),
        ], 200);
    }

    public function updateTagSingle(Request $request)
    {
        $upsert = User::find(auth()->user()->id);
        $upsert->tag()->update([$request->single_column => $request->data]);
        return response()->json([
            'data'      => $upsert->load(['authorization', 'activity', 'tag', 'miniprofile']),
        ], 200);
    }

    public function updateTagMultiple(Request $request)
    {
        $upsert = User::find(auth()->user()->id);
        $upsert->tag()->update($request->multi_column);
        return response()->json([
            'data'      => $upsert->load(['authorization', 'activity', 'tag', 'miniprofile']),
        ], 200);
    }

    public function updateDetailSingle(Request $request)
    {
        $upsert = User::find(auth()->user()->id);
        $upsert->detail()->update([$request->single_column => $request->data]);
        return response()->json([
            'data'      => $upsert->load(['authorization', 'activity', 'tag', 'miniprofile']),
        ], 200);
    }

    public function updateDetailMultiple(Request $request)
    {
        $upsert = User::find(auth()->user()->id);
        $upsert->detail()->update($request->multi_column);
        return response()->json([
            'data'      => $upsert->load(['authorization', 'activity', 'tag', 'miniprofile']),
        ], 200);
    }


    public function getStatus()
    {

        $authUser = auth()->user();
        $user = User::find($authUser->id);

        if(!$user)
        {
            return response()->json([
                'message'   => 'User not found',
                'status'    => 400
            ], 400);
    
        }

        $authorization = $user->authorization()->first();

        if (!$authorization) {
            return response()->json(['message' => 'Authorization not found'], 404);
        }

        $whatsapp = $user->whatsappstate()->first();
        $whatsappState = $whatsapp?->status ?? 'unverified';

        $status = [
            'profile'           => $authorization->profile,
            'email'             => $authorization->email,
            'kyc'               => $authorization->kyc === "approved" && $authorization->type === "both" ? "approved" : "pending",
            'work-experience'   => $authorization->type,
            'whatsapp'          => $whatsappState,
        ];

        return response()->json($status, 200);

    }

    public function confirmBroadcastAuth(Request $request)
    {
        $getToken = new NetAuthToken();
        $token = $getToken->where('token', $request->token)->first();
        if (!empty($token)) {
            $getToken->where('token', $request->token)->update(['is_revoked' => 1]);
            return response()->json([
                'message'   => 'revoked'
            ], 200);
        } else {
            return response()->json([
                'message'   => 'Invalid token'
            ], 401);
        }
    }


    public function updateUserFromExpress(Request $request)
    {
        $user = new User();
        $user->where('uuid', $request->uuid)->update([
            'exp_id' => $request->exp_id
        ]);

        return response()->json(200);
    }

    public function testthis($direction, $id)
    {
        $offerDetail = new FeeDeterminantAid();
        $offerItem = $offerDetail->detailOffer(direction: $direction, id: $id);

        return response()->json($offerItem->percentage == null ? 'null' : $offerItem->percentage);
    }


    public function navigationAuth()
    {
        $subdomain = "ratefy.co/";
        $user = User::find(auth()->user()->id);
        if ($user->exp_id === null) {
            $state = $this->broadcastAuthenticatedNavigatingUser(user: $user);
            if ($state === 200) {
                $tokes = $this->broadcastAuthenticatedUser(token: auth()->user()->createToken(auth()->user()->username)->plainTextToken, userId: auth()->user()->id, fromDomain: request()->getHttpHost());
                if ($tokes !== null) {
                    return response([
                        'status'    => 200,
                        'subdomain' => 'https://' . $subdomain . 'users/auth-test/' . $tokes
                    ]);
                }
            }
        } else {
            $tokes = $this->broadcastAuthenticatedUser(token: auth()->user()->createToken(auth()->user()->username)->plainTextToken, userId: auth()->user()->id, fromDomain: request()->getHttpHost());
            if ($tokes !== null) {
                return response([
                    'status'    => 200,
                    'subdomain' => 'https://' . $subdomain . 'users/auth-test/' . $tokes
                ]);
            }
        }
    }


    public function broadcastAuthenticatedNavigatingUser($user)
    {
        $payload =  [
            'name'          => $user->firstname . ' ' . $user->lastname,
            'username'      => $user->username,
            'email'         => $user->email,
            'mobile_number' => $user->mobile,
            'uuid'          => $user->uuid,
            'password'      => $user->password,
            'emailCode'     => md5(Hash::make($user->username))
        ];

        $check = OnlineAjax::post('https://ratefy.co/api/user-reg-api', $payload);
        if ($check->status() === 200) {
            return 200;
        }
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


            OnlineAjax::post('https://ratefy.co/api/authenticating', [
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


    public function autoLogout(Request $request)
    {
        $user = User::where('uuid', $request->uuid)->first();
        $user->tokens()->delete();
        Session::flush();
        Session::regenerate();
        return response()->noContent();
    }


    public function resendVerifyLink(Request $request)
    {
        $user = auth()->user();
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message'   => 'Verification link sent successfully',
            'status'    => 200
        ], 200);
    }
}
