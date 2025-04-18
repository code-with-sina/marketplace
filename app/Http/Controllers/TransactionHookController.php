<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomerStatus;
use App\Models\User;

class TransactionHookController extends Controller
{

    protected User $user;
    protected $eventData;


    public function BuyerRequestDebit(Request $request)
    {
        $receipt = $this->decoupleHookReceipt($request);

        if ($receipt) {
            if($request->data['type'] === 'book.transfer.failed') {
                $users = CustomerStatus::where('customerId', $request->data['relationships']['customer']['data']['id'])->first();
                $this->user = $users->user()->first();
            }elseif($request->data['type'] === 'book.transfer.initiated') {
                $users = CustomerStatus::where('customerId', $request->data['relationships']['customer']['data']['id'])->first();
                $this->user = $users->user()->first();
            }elseif($request->data['type'] === 'book.transfer.successful') {
                $users = CustomerStatus::where('customerId', $request->data['relationships']['customer']['data']['id'])->first();
                $this->user = $users->user()->first();
            }
            // $this->eventLogger(user: $this->user);
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
  
}
