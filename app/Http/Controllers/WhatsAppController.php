<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsappNotificationService;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;


class WhatsAppController extends Controller
{
    
    private const TOKEN = '7353866b0d014f1f86349165d0f5b9351e09a986821b440480'; 

    public function handle(Request $req, WhatsappNotificationService $verifier): Response
    {
        
        $auth = $req->header('Authorization', '');
        if (!empty(self::TOKEN) && $auth !== 'Bearer ' . self::TOKEN) {
            Log::warning('Unauthorized webhook access attempt', ['header' => $auth]);
            return response('Unauthorized', 401);
        }

        $data = $req->json()->all();
        $type = $data['typeWebhook'] ?? null;

        if (!$type) {
            Log::error('Malformed webhook payload (no typeWebhook)', $data);
            return response('Bad Request', 400);
        }

        Log::info("Green‑API webhook received", ['type' => $type]);

        // Handle incoming message
        if ($type === 'incomingMessageReceived') {
            $sender = $data['senderData']['sender'] ?? null;

            if ($sender) {
                $receiptId = $data['idMessage'] ?? 'greenapi_' . now()->timestamp;
                $verifier->verifyUserWhatsappStateTest($sender, $receiptId);
            }
        }

        // Handle other types as needed...
        elseif (in_array($type, ['statusInstanceChanged', 'stateInstanceChanged'])) {
            Log::info("Instance status changed", [
                'status' => $data['statusInstance'] ?? 'unknown',
            ]);
        }

        return response('OK', 200);
    }


    public function getUserWhatsappStatus(WhatsappNotificationService $whatsapp)
    {
        $response = $whatsapp->getUserStatus(auth()->user()->id);

        return response()->json($response->message, $response->status);
    }


    public function initiateWhatsappVerification(WhatsappNotificationService $whatsapp, Request $request) 
    {
        $validate = Validator::make($request->all(), [
            'optional_number' => 'nullable|string|max:11'
        ]);

        if ($validate->fails()) {
            return response()->json(['errors' => $validate->errors()], 422);
        }
        $mobile = $this->formatToInternational($request->optional_number);
        $response = $whatsapp->getUserForVerification(auth()->user()->id, $mobile);

        return response()->json($response, $response->status);
    }


    function formatToInternational($mobile) {
        // Remove any non-digit characters just in case
        $mobile = preg_replace('/\D/', '', $mobile);

        // Replace starting 0 with +234
        if (preg_match('/^0\d{10}$/', $mobile)) {
            return '+234' . substr($mobile, 1);
        }

        return $mobile; // return as-is if it doesn't match the expected local format
    }
}
