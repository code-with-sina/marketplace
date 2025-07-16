<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsappNotificationService;
use Symfony\Component\HttpFoundation\Response;


class WhatsAppController extends Controller
{
    
    private const TOKEN = 'f0bc0330eddd4ec7992cfdc384485a48e60f2c72781f4ab481'; 

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
}
