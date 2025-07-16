<?php 

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\WhatsAppState;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;



class WhatsappNotificationService 
{

    public function sendNotification(string $chatId, string $message): void
    {
        $payload = [
            'chatId'    => $chatId.'@c.us',
            'message'   => $message
        ];

        $this->makeCallThrough($payload);
    }

    public function makeCallThrough($payload) 
    {
        $url = env("WHATSAPP_API_URL")."/waInstance".env("WHATSAPP_ID_INSTANCE")."/sendMessage".env("WHATSAPP_API_TOKEN_INSTANCE");
        try{
            Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($url, $payload);
        }
        catch(\Exception $e) 
        {
            Log::error($e->getMessage());
        }
    }



    public function getUserStatusTest($id): mixed 
    {
        $user = User::where('id', $id)->first();
        if(!$user){
            return response()->json('sorry we have no record of you', 404);
        }

        if(@$user->whatsappstate()->first() === null) {
            return response()->json('You are yet to verified your whatsapp number', 422);
        }

        if(@$user->whatsappstate()->first() !== null && $user->whatsappstate()->first()->status === "unverified"){
            return response()->json('You are yet to verified your whatsapp number, new customer', 422);
        } 

        $verified = $user->whatsappstate()->first();
        return response()->json($verified);
         
    }



    public function getUserForVerificationTest($id, $optional_number)
    {
        $user = User::where('id', $id)->first();

        if (!empty($optional_number)) {
            $user->whatsappstate()->create([
                'receiptId' => Str::uuid(),
                'optional_whatsapp_number' => $optional_number
            ]);
        } else {
            $user->whatsappstate()->create([
                'receiptId' => Str::uuid()
            ]);
        }

        $messageFromTemplate = "I am here to very my number. my name is " .$user->firstname. " and my email is " .$user->email;

        $url = 'https://wa.me/'.$user->mobile.'?text=' . urlencode($messageFromTemplate);
        return redirect($url); 
    }


    public function verifyUserWhatsappStateTest($mobile, $receiptId): mixed 
    {
        $user  = User::where('mobile', $mobile)->first();


        if ($user) {
            $user->whatsappstate()  // assuming it's a hasOne() or hasMany()
                ->where('status', '!=', 'verified') // optional filter
                ->update([
                    'status'      => 'verified',
                    'verified_at' => now(),
                    'receiptId'   => $receiptId,
                ]);

            return response()->json([
                'source' => 'user > whatsappstate',
                'user' => $user
            ]);
        }

        $whatsAppState = WhatsAppState::where('optional_whatsapp_number', $mobile)->first();

        if ($whatsAppState) {
            $whatsAppState->update([
                'status'      => 'verified',
                'verified_at' => now(),
                'receiptId'   => $receiptId,
            ]);

            return response()->json([
                'source' => 'whats_app_states',
                'whatsapp_state' => $whatsAppState,
                'user' => $whatsAppState->user ?? null,
            ]);
        }

        Log::error('Mobile number not found in users or WhatsAppState.', [
            'mobile' => $mobile
        ]);

        return response()->json([
            'message' => 'Mobile number not found.',
            'mobile' => $mobile
        ], 404);
    }
}