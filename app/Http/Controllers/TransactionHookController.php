<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransactionEvent;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Services\PostBuyRequestService;

class TransactionHookController extends Controller
{

    protected User $user;
    protected $eventData;


    public function handle(Request $request)
    {
        Log::info(['approved' => $request]);
        $receipt = $this->decoupleHookReceipt($request);
       
        if ($receipt) {
            if($request->data['type'] === 'book.transfer.failed') {
               $transaction = TransactionEvent::where('reference',  $request->included[1]['attributes']['reference'])->where('status', 'initiated')->first();
               $transaction->transactionevent()->update([
                    'status' => 'failed', 
                    'event_id' => $this->eventData['data']['id'],
                    'event_type' => $this->eventData['data']['type'],
                    'message'   => $this->eventData['data']['attributes']['failureEventData']['message'] ?? "null",
                    'payload'   => json_encode($this->eventData),
                    'event_time' => $this->eventData['data']['attributes']['createdAt']
                ]);
               
            }elseif($request->data['type'] === 'book.transfer.initiated') {
                $transaction = TransactionEvent::where('reference',  $request->included[1]['attributes']['reference'])->where('status', 'initiated')->first();
                $transaction->transactionevent()->update([
                    'status' => 'failed', 
                    'event_id' => $this->eventData['data']['id'],
                    'event_type' => $this->eventData['data']['type'],
                    'message'   => $this->eventData['data']['attributes']['failureEventData']['message'] ?? "null",
                    'payload'   => json_encode($this->eventData),
                    'event_time' => $this->eventData['data']['attributes']['createdAt']
                ]);
               
            }elseif($request->data['type'] === 'book.transfer.successful') {
                $transaction = TransactionEvent::where('reference',  $request->included[1]['attributes']['reference'])->where('status', 'initiated')->first();
                $transaction->transactionevent()->update([
                    'status' => 'failed', 
                    'event_id' => $this->eventData['data']['id'],
                    'event_type' => $this->eventData['data']['type'],
                    'message'   => $this->eventData['data']['attributes']['failureEventData']['message'] ?? "null",
                    'payload'   => json_encode($this->eventData),
                    'event_time' => $this->eventData['data']['attributes']['createdAt']
                ]);

                app(PostBuyRequestService::class)
                    ->retreiveTempTradeData($transaction->reference)
                    ->createTradeRequest()
                    ->sendAdminNotification()
                    ->notifyRecipient()
                    ->autoCancelTradeRequest()
                    ->successState()
                    ->throwStatus();
               
            }

        } else {
            return response()->json(['error' => 'Invalid signature'], 403);
        }


    }

    public function BuyerApprovalDebit(Request $request)
    {
        $receipt = $this->decoupleHookReceipt($request);

        if ($receipt) {
            $this->eventData = $request->all();
            $this->user = User::where('id', $this->eventData['data']['relationships']['customer']['data']['id'])->first();
            $this->eventLogger(user: $this->user);
        } else {
            return response()->json(['error' => 'Invalid signature'], 403);
        }
    }


    public function PeerPaymentDebit(Request $request)
    {
        $receipt = $this->decoupleHookReceipt($request);

        if ($receipt) {
            $this->eventData = $request->all();
            $this->user = User::where('id', $this->eventData['data']['relationships']['customer']['data']['id'])->first();
            $this->eventLogger(user: $this->user);
        } else {
            return response()->json(['error' => 'Invalid signature'], 403);
        }
    }

    public function FeeDebit(Request $request)
    {
        $receipt = $this->decoupleHookReceipt($request);

        if ($receipt) {
            $this->eventData = $request->all();
            $this->user = User::where('id', $this->eventData['data']['relationships']['customer']['data']['id'])->first();
            $this->eventLogger(user: $this->user);
        } else {
            return response()->json(['error' => 'Invalid signature'], 403);
        }
    }

    public function decoupleHookReceipt($dataRoll) {
        Log::info(['approved' => $dataRoll]);
        $this->eventData = $dataRoll->all();
        $homeSecret = config('app.webhook_secret');
        $getAnchorSignature = $dataRoll->header('x-anchor-signature');
        $hmacSha1Hash = hash_hmac('sha1', $dataRoll->getContent(), $homeSecret, false);
        $base64EncodeHash = base64_encode($hmacSha1Hash);

        if ($getAnchorSignature !== $base64EncodeHash) {
            return false;
            // return response()->json(['error' => 'Invalid signature'], 403);
        }else {
            return true;
        }
    }


    public function eventLogger($user) 
    {

    }


    public function BuyerRequestDebitFailed() 
    {

    }

    public function BuyerRequestDebitInitiated() 
    {

    }

    public function BuyerRequestDebitSuccessful() 
    {

    }

    public function initBuyerRequestDebit($uuid, $reference) 
    {
        $user = User::where('uuid', $uuid)->first();
        $user->transactionevent()->create([
            'type' => 'BuyerRequest',
            'reference' => $reference,
            'status' => 'initiated',
        ]);
        
    }

    public function postBuyerRequestDebit($data) 
    {

    }
  
}
