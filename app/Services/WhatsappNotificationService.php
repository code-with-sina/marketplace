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
        $newChatId =  str_replace('+', '', $chatId);
        $payload = [
            'chatId'    => $newChatId.'@c.us',
            'message'   => $message
        ];
        
        Log::info(['payload' => $payload]);
        $this->makeCallThrough($payload);
    }

    public function makeCallThrough($payload) 
    {
        $url = env("WHATSAPP_API_URL")."/waInstance".env("WHATSAPP_ID_INSTANCE")."/sendMessage/".env("WHATSAPP_API_TOKEN_INSTANCE");
        try{
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($url, $payload);
        }
        catch(\Exception $e) 
        {
            Log::error($e->getMessage());
        }
    }



    public function getUserStatus($id): mixed 
    {
        $user = User::where('id', $id)->first();
        if(!$user){
            return (object)['message' => 'sorry we have no record of you',  'status' => 400];
        }

        if(@$user->whatsappstate()->first() === null) {
            return (object)['message' =>  'You are yet to verified your whatsapp number',  'status' => 400];
        }

        if(@$user->whatsappstate()->first() !== null && $user->whatsappstate()->first()->status === "unverified"){
            return (object)['message' => 'You are yet to verified your whatsapp number, new customer',  'status' => 400];
        } 

        $verified = $user->whatsappstate()->first();
        return ['message' => $verified, 'status' => 200];
         
    }



    public function getUserForVerification($id, $optional_number)
    {
        $user = User::where('id', $id)->first();

        if (!empty($optional_number)) {
            $user->whatsappstate()->updateOrCreate(
                ['optional_whatsapp_number' => $optional_number],
                [
                    'receiptId' => Str::uuid(),
                    
                ]);
        } else {
            $user->whatsappstate()->updateOrCreate(
                ['optional_whatsapp_number' => $optional_number],
                [
                    'receiptId' => Str::uuid(),
                   
                ]);
        }

        $messageFromTemplate = 
        <<<EOT
        Hello Ratefy,
        Transaction Notifications can be sent to this WhatsApp. I consent to that.
        EOT;



        $mobile = "+2348132104428";

        $url = 'https://wa.me/'.$mobile.'?text=' . urlencode($messageFromTemplate);
        return (object)['statusMessage' =>  'You are now eligible to be authorized', 'message' => $url, 'status' => 200]; 
    }


    public function verifyUserWhatsappStateTest($mobileFromApi, $receiptId): mixed 
    {

        $completeMobile = $this->removeCusSuffix($mobileFromApi);
        $mobile = "+".$completeMobile;
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


    function removeCusSuffix($whatsappId) {
        return str_replace('@c.us', '', $whatsappId);
    }
}